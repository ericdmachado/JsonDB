<?php

class JsonDB_ID {

    public static function create( ) {

        if(!function_exists('gethostname')){
            function gethostname(){
                return php_uname('n');
            }
        }

        // Return JsonID
        $binaryTimestamp = substr(microtime(true), -4);
        $machineId       = substr(md5(uniqid(rand() . gethostname())), -3);
        $binaryPID       = pack('n', getmypid());
        $counter         = substr(md5(uniqid(rand(), true)), -8, -4);
        $binaryId        = "{$binaryTimestamp}{$machineId}{$machineId}{$counter}";

        // Convert to ASCII
        $id = '';
        for ($i = 0; $i < 12; $i++){
            $id .= sprintf("%02X", ord($binaryId[$i]));
        }

        // Return JsonID
        return $id;
    }
}