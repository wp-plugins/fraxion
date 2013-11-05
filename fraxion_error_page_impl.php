<?php
class FraxionErrorPageImpl {
	
	/**
	 * Set 'Auth Required' (401) headers.
	 *
	 * @param string $msg
	 *        	Status header content and HTML content.
	 * @param FraxionLoggerImpl $logger
	 *        	optional logger to write to.
	 */
	public function auth_required(
			$msg, 
			FraxionLoggerImpl $logger) {
		$logger->writeLOG( "401: Auth Required " . $msg);
		nocache_headers ();
		// header('WWW-Authenticate: Basic realm="WordPress Atom Protocol"');
		header ( "HTTP/1.1 401 $msg" );
		header ( 'Status: 401 ' . $msg );
		header ( 'Content-Type: text/html' );
		$content = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . '<html>' . '  <head>' . '    <title>401 Unauthorized</title>' . '  </head>' . '  <body>' . '    <h1>401 Unauthorized</h1>' . '    <p>' . $msg . '</p>' . '  </body>' . '</html>';
		echo $content;
		$logger->writeLOG( $content);
		exit ();
	} // end auth_required
	
	/**
	 * Write an error response page (status 400) and exit.
	 * 
	 * @param
	 *        	title Title to display
	 * @param
	 *        	msg Error message to display
	 * @param
	 *        	logger optional logger to write to
	 */
	public static function clientError(
			$title,
			$msg, 
			FraxionLoggerImpl $logger) {
		try {
			$logger->writeLOG( "400: Bad Request " . $title);
			$logger->writeLOG( $msg);
		} catch (Exception $e) {
			$logger->writeLOG($e->getMessage());
		}
		nocache_headers ();
		header ( "HTTP/1.1 400 $title" );
		header ( 'Status: 400 ' . $title );
		header ( 'Content-Type: text/html' );
		$content = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . '<html>' . '  <head>';
		$content = $content . '    <title>' . $title . '</title>' . '  </head>' . '  <body>' . '    <h1>' . $title . '</h1>';
		$content = $content . '    <p>' . $msg . '</p>' . '  </body>' . '</html>';
		echo $content;
		exit ();
	} // end clientError
	
	/**
	 * Write a fatal error response page (status 500) and exit.
	 * 
	 * @param
	 *        	msg Error message to display
	 * @param
	 *        	logger optional logger to write to
	 */
	public static function fatalError(
			$msg, 
			FraxionLoggerImpl $logger) {
		$logger->writeLOG( "500: Internal Server Error " . $msg);
		nocache_headers ();
		header ( "HTTP/1.1 500 $msg" );
		header ( 'Status: 500 ' . $msg );
		header ( 'Content-Type: text/html' );
		$content = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . '<html>' . '  <head>' . '    <title>500 Internal Server Error</title>' . '  </head>' . '  <body>' . '    <h1>500 Internal Server Error</h1>' . '    <p>' . $msg . '</p>' . '  </body>' . '</html>';
		echo $content;
		$logger->writeLOG( $content);
		exit ();
	} // end fatalError
} // end class FraxionErrorPageImpl

?>