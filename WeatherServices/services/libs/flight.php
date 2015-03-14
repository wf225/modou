<?php
require 'flight/Flight.php';

Flight::route('/', function(){
    echo 'hello flight!';
});

//Flight::route('/@name/@id', function($name, $id){
//    echo "hello, $name ($id)!";
//});

Flight::route('/user/@name', function($name){
    echo "hello, $name!";
});

Flight::start();
?>
