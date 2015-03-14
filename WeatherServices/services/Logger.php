<?php
include_once 'AppKeys.php';

class Logger {
	
	static function output($msg) {
        //echo $msg . "\n";
	}
	
	static function error($msg) {
		$arr = array ("ERROR:"=>$msg);
		echo json_encode($arr);
	}
    
    public static function log($msg)
    {
        $domain = "sys";
        $filename = "sys.log";
        $storage = new SaeStorage();
        
        if($storage->fileExists($domain, $filename)){
			
			$oldMsg = $storage->read($domain, $filename);
        }
        
        $msg = $oldMsg. "\n" . date("Y-m-d H:i:s") . ': ' . $msg;
        $storage->write($domain, $filename, $msg);
    }
      
}

?>