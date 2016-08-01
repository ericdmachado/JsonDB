<?php

function pre(){
	echo '<pre>';
	print_r(func_get_args());
	echo '</pre>';
}

require_once('JsonDB/jsondb.php');


$performance = new JsonDB_Performance();
$performance->start();

$db = new JsonDB('database');


$something = array('foo' => array('name'=>'jack', 'last_name'=>'reacher'), 'item' => rand(), 'created_in' => TIMESTAMP, 'updated_in' => TIMESTAMP );


//pre( $db->teste->find( array('foo'=>'bar') ) );

//echo '<h3>Get Collection Names:</h3>';
//pre( $db->getCollectionNames() );

//echo '<h3>List Commands:</h3>';
//pre( $db->listCommands() );

//echo '<h3>Drop Database:</h3>';
//pre( $db->dropDatabase() );


echo '<h3>Result: </h3><hr/>';
//pre($db->produto->find(array()));

$query = array( 
		//'_id' => '393130343635373635373534',
		//'created_in'=>1468860799,
		//'updated_in'=>1468860799,
		//'item' => 4654665,
		//'foo' => array('name' => 'jack')
		"status" => array('entregue'=>true)//
	);

//$query , array('foo' => true) )->find( array(), array('item' => true) )


pre( "result",  $db->produto->find($query) );

///->find()->find()->find()->sort()->limit( 1 )
//echo $data[0]->_id;

//, array('name' => true, 'item'=> true/*, '_id' => false*/ ))->find()



//pre($db->produto->find(array(), array('name' => true, 'item'=> true/*, '_id' => false*/ ))->find());
//->orderBy('foo', DESC)




$performance->end();

echo $performance;