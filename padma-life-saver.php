<?php
/*
Plugin Name:	Padma Life saver
Plugin URI:		https://padmaunlimited/plugins/padma-life-saver
Description:  	Padma Live Saver plugin allows convert Headway or Blox Templates to Padma Templates. Original plugin hw-to-bt from Johnathan.PRO.
Version:      	0.0.1
Author:       	Plasma Soluciones
Author URI:   	https://plasma.cr
License:      	GPL2
License URI:  	https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  	padma-lifeSaver
Domain Path:  	/languages
Network: false


Padma Services plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Padma Services plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Padma Services plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


defined('ABSPATH') or die( 'Access Forbidden!' );


require_once(dirname(__FILE__) . '/helpers/wp_async_task.php');
require_once(dirname(__FILE__) . '/helpers/Async.php');
require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/includes/class_padmaLifeSaver.php');
require_once(dirname(__FILE__) . '/includes/class_padmaConverter.php');


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

/**
 *
 * Activation hook
 *
 */
function padma_lifeSaver_activate(){
	if ( ! current_user_can( 'activate_plugins' ) )
	return;

	padmaLifeSaver::activation();

}
register_activation_hook( __FILE__, 'padma_lifeSaver_activate');



/**
 *
 * Deactivation hook
 *
 */
function padma_lifeSaver_deactivate(){
	if ( ! current_user_can( 'activate_plugins' ) )
	return;
	
	padmaLifeSaver::deactivation();

}
register_deactivation_hook( __FILE__, 'padma_lifeSaver_deactivate');


/**
 *
 * Menu options
 *
 */
function padma_lifeSaver_menuOptions(){

	$lifeSaver = new padmaLifeSaver();
    add_menu_page( 'Padma Life Saver', 'Padma Life Saver', 'manage_options', 'padma-life-saver', array($lifeSaver,'menuOptionsPage'),'',1);

}

/**
 *
 * Add assets
 *
 */
function padma_lifeSaver_assets_css() {
    wp_enqueue_style('style_plugin', plugins_url( 'css/admin.css' , __FILE__ ) );
}
function padma_lifeSaver_assets_js() {
    wp_enqueue_script('script_plugin', plugins_url( 'js/admin.js' , __FILE__ ) );
}

/**
 *
 * Start process
 *
 */
if (is_admin()) {
	add_action( 'admin_menu', 'padma_lifeSaver_menuOptions');
	add_action( 'admin_enqueue_scripts', 'padma_lifeSaver_assets_css');
	add_action( 'admin_footer', 'padma_lifeSaver_assets_js');
}


debug($_REQUEST);

/*

exit();

$this->menu_pages   = array(
            'Life saver'   =>  array(
                'capability'    =>  'edit_dashboard',
                'position'      =>  '1',
                'func'          =>  'Settings',
            ),
        );




ini_set('memory_limit', '1024M');
function padma_mem(){
	$u = memory_get_usage()/1024.0;
    error_log( $u . " kb");
}

require_once(dirname(__FILE__) . '/helpers/Async.php');
require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/includes/class_life_saver.php');



$GLOBALS['lifesaver'] = new lifesaver();
*/
