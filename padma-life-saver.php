<?php
/**
Plugin Name: Padma Life saver
Description: Convert Headway or Blox Templates to Padma Templates. Original plugin hw-to-bt from Johnathan.PRO.
Version: 1.0.0
Author: Plasma Soluciones
Author URI: http://www.plasma.cr
Network: false
License: GPL
*/

defined('ABSPATH') or die( 'Access Forbidden!' );

ini_set('memory_limit', '1024M');
function padma_mem(){
	$u = memory_get_usage()/1024.0;
    error_log( $u . " kb");
}

require_once(dirname(__FILE__) . '/helpers/Async.php');
require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/includes/class_life_saver.php');


$GLOBALS['lifesaver'] = new lifesaver();
