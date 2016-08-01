<?php


class JsonDB_performance
{
     private $startTime;
     private $endTime;

     public function start(){
         $this->startTime = microtime(true);
     }

     public function end(){
         $this->endTime = microtime(true);
     }

     public function __toString(){
         return "This page was created in ". ($this->endTime - $this->startTime) ." seconds";
     }
 }