<?php

/* 
 * Utilities for testing fraxion_logger objects
 */

/**
 * @param FraxionLoggerImpl $logger test to see if this is a logger that is set to output at debug level
 * @return boolean true if the logger exists and is set to debug
 */
function frax_is_debugging($logger) { // FraxionLoggerImpl
	return $logger != null && $logger->isDebugThis();
}

function var_dump_ret($mixed = null) {
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();
  return $content;
}
