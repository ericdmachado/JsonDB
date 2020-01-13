# JsonDB

Biblioteca em PHP que transforma aquivos .json em um database estilo MongoDB.
Cada Collection é armazenada em um arquivo único, podendo ser consultada de uma forma mais rápida.


![enter image description here](http://gereon.com.br/jsondb/json_db.png)


## Install

Para começar, será necessário informar o caminho onde estará a pasta do seu banco de dados e dar permissão de leitura e escrita na pasta indicada:

    require_once('jsondb/jsondb.php');

    $db = new JsonDB('database');

## Create

    $something = array('foo' => array('name'=>'jack', 'last_name'=>'reacher'));
    
    print_r($db->produto->insert( $something ));

## Read

     $something = $db->produto->find();
     
     print_r($something);

## Update

     $something = $db->produto->update( array('foo'=>'*'), array('foo'=> array('name'=>('Lorem '. rand(1, 9999)))));
     
     print_r($something);

## Delete

    $db->produto->remove( array('foo'=>'*'), true );

## Inline

    $db->inline->insert( $something )->find( array( 'foo'=>'*' ) )->update( array( 'foo'=>'*' ), array( 'foo'=>rand(rand(100, 500),9999999) ) )->remove( array( 'foo'=>'*' ), true );


## Outras funções
#### Get Collection Names

    $db->getCollectionNames();

#### List Commands

    $db->listCommands();

#### Drop Collection

    $db->inline->drop();

#### Drop Database

    $db->dropDatabase();
