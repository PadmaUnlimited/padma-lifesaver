<?php
/**
Plugin Name: Padma life-save
Description: Convert Headway or Blox Templates to Padma Templates. Original plugin hw-to-bt from Johnathan.PRO.
Version: 1.0.0
Author: Plasma Soluciones
Author URI: http://www.plasma.cr
Network: false
License: GPL
*/

defined('ABSPATH') or die( 'Access Forbidden!' );


require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/includes/live_saver.php');

/**
 *
 * Debug function
 *
 */
if(!function_exists('debug')){
    function debug($data){
        error_log("<pre>".print_r($data,1)."</pre>");
    }
}


$GLOBALS['lifesaver'] = new lifesaver();