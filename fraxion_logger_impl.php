<?php
/**
 * @author Danny Stevens
 *
 */
class FraxionLoggerImpl {
	private static $debug = false;
	private $clientClassName;
	private $debugThis = false;
	
	private function __construct(
			$clientClassName) {
		$this->clientClassName = $clientClassName;
		$this->debugThis = $this->isDebug();
	}
	
	private function getLogFilePath() {
		return PluginsPathImpl::get () . "logs" . DIRECTORY_SEPARATOR . "fraxlog" . date ( "Y-m-d" ) . ".txt";
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
		if ( $this->isDebugThis()) {
			$logOutput = "[" . date ( "Y-m-d H:i:s" ) . "][" . $this->clientClassName . "] ";
			$logOutput .= $msg . " \n";
			$fp = fopen ( self::getLogFilePath(), 'a' );
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
	
	public static function setDebug($newDebug) {
		self::$debug = $newDebug;
	}
	
	public function isDebugThis() {
		return $this->debugThis || $this->isDebug();
	}
	
	public function setDebugThis($newDebugFlag) {
		$this->debugThis = $newDebugFlag;
	}
} // end class FraxionLoggerImpl
?>