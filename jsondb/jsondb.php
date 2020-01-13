<?php 

define('ASC', false);
define('DESC', true);
define('TIMESTAMP', microtime(true));


require_once('jsondb_exception.php');
require_once('jsondb_id.php');
require_once('jsondb_document.php');
require_once('jsondb_collection.php');
require_once('jsondb_performance.php');


class JsonDB {
	public $collections = array();
	public $name = '';
	static $extension = '.json';

	public $database_path;
	public $collection_path;

	public function __construct( $path ){
		$this->setDatabasePath( $path );
	}


	public function setDatabasePath( $path ){

		if(preg_match('/\\\|\//', $path)){
			if(preg_match('/\\\/', $path)){
				$this->db_name = substr($path, strrpos($path, '\\')+1);
				$this->database_path = substr($path, 0, strrpos($path, '\\')+1);
			}else{
				$this->db_name = substr($path, strrpos($path, '/')+1);
				$this->database_path = substr($path, 0, strrpos($path, '/')+1);
			}
		}else{
			$this->db_name = $path;
			$this->database_path = realpath( $path );
		}

		if ( ! is_dir( $this->database_path ) ) {
	            throw new JsonDB_Exception('Path not found');
	
	        }
	        if ( ! is_writeable( $this->database_path ) ) {
	        	chmod($this->database_path, 0777);
	        }
	}


	public function getCollection( $name ) {
		$name = strtolower( $name );
		
		$this->collection_path = $this->database_path  . DIRECTORY_SEPARATOR . $name;

		if(! is_dir($this->collection_path) ){
			mkdir($this->collection_path);
		}

		if(! is_writeable($this->collection_path) ){
			chmod($this->collection_path, 0777);
		}

		$this->collections[$name] = new JsonDB_Collection($this->collection_path);

		return $this->collections[$name];
	}


	public function getCollectionNames(){
		$collections = array();

		if( $dh = opendir( $this->database_path ) ){
			while ( ( $collection = readdir( $dh ) ) !== false ){
				$path = $this->database_path . DIRECTORY_SEPARATOR . $collection;

				//se o arquivo for uma pasta
				if( is_dir( $path ) && ! preg_match('/^\\./', $collection ) ){
					array_push( $collections, $collection );
				}
			}
			//fecha o diretório
			closedir( $dh );
		}

		return $collections;
	}


	public function getName(){
		return $this->db_name;
	}


	public function cloneCollection(){
		return 'N.I';
	}

	public function cloneDatabase(){
		return 'N.I';
	}

	public function copyDatabase(){
		return 'N.I';
	}

	public function dropDatabase(){
		$dir = $this->database_path;
		$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($it,
		             RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) {
		    if ($file->isDir()){
		        rmdir($file->getRealPath());
		    } else {
		        unlink($file->getRealPath());
		    }
		}
	}

	public function set_extension( $extension='.json' ){
		JsonDB::$extension = $extension;
	}

	public function listCommands(){
		$protecteds = array('__', 'listCommands');
		$result = array();

		$methods = get_class_methods($this);

		foreach ( $methods as $method ) {
			if( substr($method, 0, 2) != '__'){
				if( ! in_array($method, $protecteds) ){
					$result[$method] = $method;
				}
			}
		}

		return array_values($result);
	}


	public function __get( $name ) {
		return $this->getCollection( $name );
	}


	public function __toString(){
		return json_encode($this->data);
	}
}
