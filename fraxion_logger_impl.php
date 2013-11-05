<?php
/**
 * @author Danny Stevens
 *
 */
class FraxionLoggerImpl {
	private static $debug = false;
	private static $log_file_path;
	private $clientClassName;
	
	private function __construct(
			$clientClassName) {
		// echo ("clientClassName is ".$clientClassName." debug is ".self::$debug);
		$this->clientClassName = $clientClassName;
		
		if (self::$debug) {
			if (self::$log_file_path == null) {
				self::$log_file_path = PluginsPathImpl::get () . "logs" . DIRECTORY_SEPARATOR . "fraxlog.txt";
			}
		}
	}
	
	/**
	 * Return a logger object that will prefix log output with the given class name.
	 * $clientClassName - the name to put as a prefix on each output line written.
	 */
	public static function getLogger(
			$clientClassName) {
		return new FraxionLoggerImpl ( $clientClassName );
	}
	
	/**
	 * Write a line of text out to /log/frax_log.txt
	 * $message - the text message to write.
	 */
	public function writeLOG(
			$msg) {
		if ( self::$debug) {
			$logOutput = "[" . date ( "Y-m-d H:i:s" ) . "][" . $this->clientClassName . "] ";
			$logOutput .= $msg . " \n";
			$fp = fopen ( self::$log_file_path, 'a' );
			fwrite ( $fp,  $logOutput  );
			fclose ( $fp );
		}
	} // end writeLOG
	/**
	 * @return boolean true if the log system is writing debug output.
	 */
	public static function isDebug() {
		return self::$debug;
	}
} // end class FraxionLoggerImpl
?>