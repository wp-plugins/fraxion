<?php

class PluginsPathImpl {
	private static $plugins_path = null;
	private static $plugins_dir_container_path = null;
	public static function get() {
		if (self::$plugins_path == null) {
			$fraxion_plugin_path = explode(DIRECTORY_SEPARATOR, __FILE__ );
			self::$plugins_path = implode(DIRECTORY_SEPARATOR,array_slice($fraxion_plugin_path, 0, -1)) .DIRECTORY_SEPARATOR;
		}
		return self::$plugins_path;
	} // end get
	
	/* Assumes something/plugins/fraxion/__FILE__ and returns the path to "something". */
	public static function getPluginsDirContainer() {
		if (self::$plugins_dir_container_path == null) {
			$fraxion_plugin_path = explode(DIRECTORY_SEPARATOR, __FILE__ );
			self::$plugins_dir_container_path = implode(DIRECTORY_SEPARATOR,array_slice($fraxion_plugin_path, 0, -3)) .DIRECTORY_SEPARATOR;
		}
		return self::$plugins_dir_container_path;
	} // end getPluginsDirContainer
} // end class PluginsPathImpl
?>