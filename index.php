<!DOCTYPE html>
<html>
	<head>
		<title>JsonDB</title>
	</head>
	<body>
		<?php
			function pre(){
				echo '<pre>';
				print_r(func_get_args());
				echo '</pre>';
			}

			require_once('jsondb/jsondb.php');


			$performance = new JsonDB_Performance();
			$performance->start();

			$db = new JsonDB('database');
			//$db->set_extension('.json');


			echo '<h1>JsonDB</h1><hr/>';

			echo '<h3>Create:</h3>';
			$something = array('foo' => array('name'=>'jack', 'last_name'=>'reacher'), 'item' => rand(1000, 20000), 'created_in' => TIMESTAMP, 'updated_in' => TIMESTAMP );
			pre($db->produto->insert( $something ));


			echo '<hr/><h3>Read:</h3>';
			pre($db->produto->find());


			echo '<hr/><h3>Update:</h3>';
			pre($db->produto->update( array('foo'=>'*'), array('foo'=> array('name'=>('Lorem '. rand(1, 9999))) ) ));


			echo '<hr/><h3>Delete:</h3>';
			//pre($db->produto->remove( array('foo'=>'*'), true ));


			echo '<hr/><h2>Inline</h2>';
			$something = array('foo' => array('name'=>'jack', 'last_name'=>'Inline'), 'item' => rand(1000, 20000), 'created_in' => TIMESTAMP, 'updated_in' => TIMESTAMP );

			pre(
				$db->inline->/*insert( $something )->*/
					find( array( 'foo'=>'*' ) )->
					update( array( 'foo'=>'*' ), array( 'foo'=>rand(rand(100, 500),9999999) ) )/*->
					remove( array( 'foo'=>'*' ), true )*/
				);


			echo '<hr/><h3>Get Collection Names:</h3>';
			pre( $db->getCollectionNames() );

			echo '<hr/><h3>List Commands:</h3>';
			pre( $db->listCommands() );

			//echo '<hr/><h3>Drop Collection:</h3>';
			//pre( $db->inline->drop() );

			//echo '<hr/><h3>Drop Database:</h3>';
			//pre( $db->dropDatabase() );


			/*pre($db->produto->find()->sort( 'item' )->skip( 30 )->limit( 2 )->each(function($document, $key){
				pre($document, $key);
			})->find( array(), array( 'item' => 1 ) ))*/;


			echo '<hr/>';
			$performance->end();
			echo $performance;

		?>
	</body>
</html>
