<?php

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


	public function insert( $document ) {
		if(is_array($document)){
			$document = $this->toOBJ($document);
		}

		if(!isset($document->{'_id'})){
			$document->{'_id'} = JsonDB_ID::create();
		}

		if(!isset($document->{'updated_in'})){
			$document->{'updated_in'} = TIMESTAMP;
		}

		if(!isset($document->{'created_in'})){
			$document->{'created_in'} = TIMESTAMP;
		}

		$doc = $this->path . DIRECTORY_SEPARATOR . $document->{'_id'} . JsonDB::$extension;
		$handle = fopen($doc, 'w');

		try{
            if (! flock($handle, LOCK_EX)){
            	throw new JsonDbException("JsonCollection Error: Can't set file-lock");
            }
            	
        	if(false === fwrite($handle, json_encode($document))){
	       		throw new JsonDbException("JsonCollection Error: Can't write data to: ". $doc );
	       	}
        }
        catch(Exception $e){
            fclose($handle);
            throw $e;
        }

        
        return $this->get_data(array($this->clone_document($document)));
	}


	public function save( $document ){
		if(is_object($document)){
			$document = $this->toArray($document, true);
		}

		if(isset($document['_id'])){
			if(isset($document['created_in'])){
				unset($document['created_in']);
			}
			if(isset($document['updated_in'])){
				unset($document['updated_in']);
			}

			return $this->update($document, $document );
		}else{
			if(isset($document['created_in'])){
				unset($document['created_in']);
			}
			if(isset($document['updated_in'])){
				unset($document['updated_in']);
			}

			return $this->insert( $document );
		}
	}


	public function update( $query = array(), $data ) {

		$results = $this->find( $query );

		foreach ($results->data as $document) {
			$update = false;

			foreach ($document as $key => $value) {
				foreach ($data as $nkey => $nvalue) {
					if($key === $nkey){
						if(!is_string($value)){
							$value = json_decode(json_encode($value), true);
						}

						if(json_encode($value) !== json_encode($nvalue)){
							if(is_array($value) && is_array($nvalue)){
								$update = true;
								$document->{$key} = $this->toOBJ(array_merge($value, $nvalue));
							}else{
								$update = true;
								$document->{$key} = $this->toOBJ($nvalue);
							}
						}
					}else{
						if(!isset($document->{$nkey})){
							$update = true;
							$document->{$nkey} =  $this->toOBJ($nvalue);
						}
					}
				}
			}

			//refrash document
			if( $update ){
				unset($document->{'updated_in'});
				$this->insert( $document );
			}
		}

		$this->data = null;
		return $this->find();
	}


	public function remove( $query, $justOne = false ) {
		$results = $this->find( $query );

		foreach ($results->data as $document ) {
			if(isset($document->{'_id'})){
				$doc = $this->path . DIRECTORY_SEPARATOR . $document->_id . JsonDB::$extension;
				if(file_exists($doc)){
					unlink($doc);
					unset($document);

					if( $justOne ){
						$this->data = null;
						return $this->find();
					}
				}
			}
		}

		$this->data = null;
		return $this->find();
	}


	public function drop() {
		if ( $dh = opendir( $this->path ) ) {
			//variavel onde será guardado todos os arquivos.
			$results = array();

			//pega todos os arquivos dentro do diretório
			while ( ( $document = readdir( $dh ) ) !== false ) {
				//se o arquivo não for uma pasta
				if( ! is_dir( $document ) ) {
					//pega somente os arquivos com a extensão específica
					if( preg_match('/'. (JsonDB::$extension === '' ? '' : ('\\'.JsonDB::$extension) ) .'/', $document) ) {
						//pega o caminho completo do documento
						$file = $this->path . DIRECTORY_SEPARATOR . $document;

						unlink( $file );
					}
				}
			}

			//fecha o diretório
			closedir( $dh );

			$this->data = null;
		}


		return $this->find();
	}


	//FIND IN FILES
	protected function find_files( $query = NULL, $projection = NULL) {
		
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
				if( count(get_object_vars($query)) ){
					$content = $this->match( $query, $content );
				}

				//TEM RESULTADO E EXISTE FILTRO DE PROJECAO
				if( count( $content ) && ! is_null( $projection ) ){
					//retorna o resultado filtrado.
					$content = $this->only_keys( $content , $projection );
				}

				return array($this->clone_document($content));
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
						if( preg_match('/'. (JsonDB::$extension === '' ? '' : ('\\'.JsonDB::$extension) ) .'/', $document) ) {
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
									array_push( $results , $this->clone_document($doc) );
								}
							}else{
								if( ! is_null( $projection ) ){
									//retorna o resultado filtrado.
									$content = $this->only_keys( $content , $projection );
								}

								if( count($content) ){
									array_push( $results , $this->clone_document($content) );
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
					array_push($results, $this->clone_document($doc));
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
			$result = $this->find_files( $this->toOBJ($query), $projection );

			//save result for later use
			return $this->get_data( $result );
		}
		//second read
		else{
			$result = $this->find_in_data( $this->toOBJ($query), $projection );

			//save result for later use
			return $this->get_data( $result );
		}
	}

	//Convert data in JsonDB_Collection
	private function get_data( $data ) {

		$this->data = new JsonDB_Collection( $data );
		$this->data->path = $this->path;

		return $this->data;
	}

	//CURSOR METHODS
	public function sort( $key, $order = ASC ) { //array("key" => -1 ) for DESC or array("key" => 1) for ASC
		//função dinâmica para ordenar os itens pela chave $key.
		function lambda_filter_by($k){
			return create_function('$a, $b', 'if(isset($a->'. $k .') && isset($b->'. $k .')){ if($a->'. $k .' == $b->'. $k .'){ return 0; } return ($a->'. $k .' < $b->'. $k .') ? -1 : 1; };');
		}

		//ordena os itens e chama a função lambda
		//que retorna uma função de ordenação.
		usort($this->data, lambda_filter_by($key));

		//se a ordem for DECRESCENTE, inverte o array.
		if($order) $this->data = array_reverse($this->data);

		//retorna a coleção ordenada
		return $this->get_data($this->data);
	}


	public function limit( $limit = 0 ) { //
		if($limit < 0){
			$limit = 0;
		}
		if( $limit ){
			return $this->get_data( array_slice($this->data, 0, $limit) );
		}else{
			return $this->get_data( $this->data );
		}
	}


	public function skip( $index = false ) { //
		if($index){
			return $this->get_data( array_slice($this->data, $index) );
		}else{
			return $this->get_data( $this->data );
		}
	}

	public function each( $callback ) {
		foreach ($this->data as $key => $document) {
			//$callback( $document , $key );
			call_user_func_array($callback, array($document, $key));
		}

		return $this->get_data( $this->data );
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
						if(($qs->{$key} === $d->{$key}) || $qs->{$key} === '*'){
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
		}else{
			if($qs == '*'){
				array_push($result, true);
			}
		}

		return $result;
	}

	private function match( $queries, $document ){
		//SOMENTE UM RESULTADO = TRUE
		//QQ OUTRO VALOR É FALSE
		$results = array_unique(JsonDB_Collection::recursive( $queries, $document ) );

		if(count($results) === 1 ? $results[0] : false){
			return $this->clone_document($document);
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
		return json_encode( $this->data );
	}
}