<?php

class JsonDB_Document{
	public function __construct( ) {}
}


class JsonDB_Collection{

	public $data = array( );
	public $path;

	public function __construct( $data ) {
		//inicia a collecion lendo os dados dentro dos arquivos
		if( is_string( $data ) ) {
			$this->data = NULL;
			$this->path = $data;
		}
		//armazena e lê os dados de dentro do cache
		else{
			$this->data = $data;
		}
	}

	//filtra os attributos do objeto
	private function only_keys( $data, $keys) {
		$document = $data;
		$result = array();
		$new_document = new JsonDB_Document();

		//copia somente os attributos do documento que for solicitado
		foreach ( $keys as $key => $value) {
			if( $value ) {
				if( isset( $document->{ $key } ) ) {
					$new_document->{$key} = $document->{ $key };
				}else{
					$new_document->{$key} = NULL;
				}
			}
		}

		//aplica o ID a todo o resultado que voltar do banco
		if(is_null( $keys ) ) {
			$new_document->_id = $document->_id;
		}else{
			if( ! isset( $keys['_id'] ) ) {
				$new_document->_id = $document->_id;
			}else{
				if( $keys['_id'] ) {
					$new_document->_id = $document->_id;
				}
			}
		}

		return $new_document;
	}

	//CLONA O OBJETO PASSADO
	private function clone_document( $document ) {
		$clone = new JsonDB_Document();

		foreach ( $document as $key => $value) {
			$clone->{$key} = $value;
		}

		return $clone;
	}

	public function insert( ) {
		//JsonDB_ID::create()
	}

	public function update( ) {
		//JsonDB_ID::create()
	}

	public function delete( ) {
		//JsonDB_ID::create()
	}

	//FIND IN FILES
	protected function find_files( $query = NULL, array $projection = NULL) {
		
		//pega apenas o arquivo com o id especifico
		if( isset( $query->{'_id'} ) ){
			//pega o caminho fisico do arquivo
			$document = $this->path . DIRECTORY_SEPARATOR . $query->{'_id'} . JsonDB::$extension;

			//resultados
			$result = array();

			//deleta depois de usar
			unset( $query->{'_id'} );

			//verifica se existe um arquivo .json com o ID específico.
			if( file_exists( $document ) ) {
				//pega o conteúdo do arquivo
				$content = json_decode( file_get_contents( $document ) );

				//filtra os itens
				if( count( $query ) ){
					$result = $this->match( $query, $content );
				}

				//TEM RESULTADO E EXISTE FILTRO DE PROJECAO
				if( count( $result ) && ! is_null( $projection ) ){
					//retorna o resultado filtrado.
					$result = $this->only_keys( $result , $projection );
				}

				return $result;
			}else{
				//array vazio sem resultado algum
				return array();
			}
		}
		//pega todos os arquivos dentro do diretório
		else{
			//abre o diretório para leitura
			if ( $dh = opendir( $this->path ) ) {
				//variavel onde será guardado todos os arquivos.
				$results = array();

				//pega todos os arquivos dentro do diretório
				while ( ( $document = readdir( $dh ) ) !== false ) {
					//se o arquivo não for uma pasta
					if( ! is_dir( $document ) ) {
						//pega somente os arquivos com a extensão específica
						if( preg_match('/\\'. JsonDB::$extension .'/', $document) ) {
							//resultados
							$doc = array();

							//pega o caminho completo do documento
							$file = $this->path . DIRECTORY_SEPARATOR . $document;

							//pega o conteúdo do arquivo
							$content = json_decode( file_get_contents( $file ) );

							if( count( $query ) ){
								$doc = $this->match( $query, $content );

								//TEM RESULTADO E EXISTE FILTRO DE PROJECAO
								if( count( $doc ) && ! is_null( $projection ) ){
									//retorna o resultado filtrado.
									$doc = $this->only_keys( $doc , $projection );
								}

								if( count($doc) ){
									array_push( $results , $doc );
								}
							}else{

								if( ! is_null( $projection ) ){
									//retorna o resultado filtrado.
									$content = $this->only_keys( $content , $projection );
								}

								if( count($content) ){
									array_push( $results , $content );
								}
							}
						}
					}
				}

				//fecha o diretório
				closedir( $dh );

				//save result for later use
				//retorna os documentos encontrados
				return $results;
			}else{

				//save result for later use
				//se não conseguir abrir o diretório, retorna uma coleção vazia.
				return array();
			}
		}
	}

	//FIND IN DATA STORED ON CACHE
	protected function find_in_data( $query = NULL, array $projection = NULL ){
		
		if( count($this->data) ){
			$results = array();

			foreach ($this->data as $document) {
				$doc = $document;
				
				if( count( $query ) ){
					$doc = $this->match( $query, $document );
				}

				if( count( $doc ) && ! is_null( $projection ) ){
					//retorna o resultado filtrado.
					$doc = $this->only_keys( $document , $projection );
				}

				if( count($doc) ){
					array_push($results, $doc);
				}
			}

			return $results;
		}else{
			return array();
		}
	}

	//FIND
	public function find( array $query = array(), array $projection = NULL) {
		//first read
		if( is_null( $this->data ) ) {

			pre( 'FIND IN FILES' );

			return $this->get_data( $this->find_files( $this->toOBJ($query), $projection ) );
		}
		//second read
		else{
			pre( 'FIND IN DATA' );

			//save result for later use
			return $this->get_data( $this->find_in_data( $this->toOBJ($query), $projection ) );
		}
	}

	//Convert data in JsonDB_Collection
	private function get_data( $data ) {

		$this->data = new JsonDB_Collection( $data );
		$this->data->path = $this->path;

		return $this->data;
	}

	//CURSOR METHODS
	public function sort( array $order = array( ) ) { //array("key" => -1 ) for DESC or array("key" => 1) for ASC
		return $this->get_data( $this->data);
	}

	public function limit( $limit = false ) { //
		return $this->get_data( $this->data);
	}

	public function skip( $index = false ) { //
		return $this->get_data( $this->data);
	}

	public function each( ) { //??
		return $this->get_data( $this->data);
	}

	public function toArray( ) {
		return json_decode(json_encode( $this->data), true);
	}

	private function clean_document($document){
		return json_encode(json_decode($document));
	}

	private static function recursive($qs, $d){
		$result = array();

		if( is_object($qs) OR is_array($qs) ){
			foreach ($qs as $key => $value) {
				if( isset( $d->{$key} ) ){
					if( is_object($d->{$key} ) || is_array($d->{$key}) ){
						return JsonDB_Collection::recursive( $qs->{$key}, $d->{$key} );
					}else{

						pre($qs->{$key} == '*');

						if(($qs->{$key} === $d->{$key})){
							//return true;
							array_push($result, true);
						}else{
							//return false;
							array_push($result, false);
						}
					}
				}else{
					//return null;
					array_push($result, null);
				}
			}
		}/*else{
			if($qs == '*'){
				array_push($result, true);
			}
		}*/

		return $result;
	}

	private function match( $queries, $document ){
		//SOMENTE UM RESULTADO = TRUE
		//QQ OUTRO VALOR É FALSE
		$results = array_unique(JsonDB_Collection::recursive( $queries, $document ) );

		if(count($results) === 1 ? $results[0] : false){
			return $document;
		}else{
			return array();
		}
	}

	protected function toOBJ( $data ){
		return json_decode(json_encode($data));
	}

	public function pretty( ) {
		pre( $this->data);
		return $this->__toString();
	}

	public function __toString( ) {
		return json_encode( $this->data);
	}
}