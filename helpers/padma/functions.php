<?php
/**
 * Copied from /padma/library/common/functions.php
 */

/**
 * @todo Document
 **/
function padma_change_to_unix_path($path) {

    return str_replace('\\', '/', $path);

}


/**
 * A simple function to retrieve a key/value pair from the $_GET array or any other user-specified array.  This will automatically return false if the key is not set.
 * 
 * @param string Key to retrieve
 * @param array Optional array to retrieve from.  Default is $_GET
 * 
 * @return mixed
 **/
function padma_get($name, $array = false, $default = null, $fix_data_type = false) {
	
	if ( $array === false )
		$array = $_GET;
	
	if ( (is_string($name) || is_numeric($name)) && !is_float($name) ) {

		if ( is_array($array) && isset($array[$name]) )
			$result = $array[$name];
		elseif ( is_object($array) && isset($array->$name) )
			$result = $array->$name;

	}

	if ( !isset($result) )
		$result = $default;
		
	return !$fix_data_type ? $result : padma_fix_data_type($result);	
		
}

/**
 * Simple alias for get_template_directory_uri()
 * 
 * @uses get_template_directory_uri()
 **/
function padma_url() {

	return apply_filters('padma_url', get_template_directory_uri());

}