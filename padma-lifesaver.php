<?php
/*
Plugin Name:	Padma Lifesaver
Plugin URI:		https://padmaunlimited/plugins/padma-lifesaver
Description:  	Padma Lifesaver plugin allows convert Headway or Blox Templates to Padma Unlimited Templates. Based on the original plugin hw-to-bt from Johnathan.PRO.
Version:      	1.0.8
Author:       	Padma Unlimited Team
Author URI:   	https://www.padmaunlimited.com/
License:      	GPL2
License URI:  	https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  	padma-lifesaver
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

require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/helpers/Async.php');

class PadmaLifesaver extends PadmaLifesaver\helpers\Plugin {

	
	private $source;
	private $source_exist;
	private $source_dir;
	private $exist_source_dir;
	private $exist_padma_dir;

    
    public function __construct($args = false) {

        $this->name 	= plugin_basename(__FILE__);
        $this->pre 		= strtolower(__CLASS__);
        $this->version 	= '1.0.8';

        if( ! $this->detectSource())
            return false;

        
        $_SESSION['padma-life-saver-source'] = $this->source;

        $this->scripts = array(
            'admin' =>  array(
                $this->pre . '-js'  =>  array(
                    'src'           =>  plugins_url('/js/admin.js', __FILE__)
                )
            )
        );

        $this->styles = array(
            'admin' =>  array(
                $this->pre . '-css' =>  array(
                    'src'           =>  plugins_url('/css/admin.css', __FILE__)
                )
            )
        );

        $this->options = array();

        $this->menu_pages = array(
            'Padma Lifesaver'   =>  array(
                'capability'        =>  'edit_dashboard',
                'position'          =>  '1',
                'func'              =>  'Settings',
            ),
        );

        $this->actions = array(
            'plugins_loaded'        =>  false,
            'after_setup_theme'     =>  false,
            'admin_notices'         =>  false,
            //'wp_async_lifesaver_json'  =>  'setPadmaUnlimited'
        );

        $this->filters = array();

        //register the plugin and init assets
        $this->register_plugin($this->name, __FILE__, true);

     

        $this->source_dir   = WP_CONTENT_DIR . '/themes/' . $this->source;
        $this->padma_dir    = WP_CONTENT_DIR . '/themes/padma';
        $this->source_exist = file_exists($this->source_dir);
        $this->padma_exist  = file_exists($this->padma_dir);
        $this->template     = '';
        $this->stylesheet   = '';


        $this->testBeforeGo();

        $basedir            = wp_upload_dir();
        $basedir            = $basedir['basedir'];
        $this->skin_path    = $basedir . '/hwdata.json';

        //new PadmaLifesaver\helpers\json();
        //debug('damm');

       // wp_schedule_single_event( time() + 1, 'setPadmaUnlimited' );

        if($_POST){
            $this->setPadmaUnlimited($_POST['include_widgets']);            
        }

    }

    private function testBeforeGo(){

        if($_POST && $_SERVER['QUERY_STRING'] == 'page=padma-lifesaver'){

            $info_link = '<a href="https://www.padmaunlimited.com/blog/2018/website-migration-from-headway-themes-3-8-x-bloxtheme-1-0-x-to-padma-unlimited-theme-builder/" target="_blank">More information</a>';

            if($this->source == 'headway'){

                if(wp_get_theme()->stylesheet != 'headway'){
                    wp_die('Headway was not found, is Headway theme active? ' . $info_link);
                }

            }elseif ($this->source == 'bloxtheme') {

                if(wp_get_theme()->stylesheet != 'bloxtheme'){
                    wp_die('Blox was not found, is Blox theme active? ' . $info_link);
                }            
            }
        } 
    }
    
    private function getSource() {

        global $current_user;

        $author = implode(' ', array($current_user->user_firstname, $current_user->user_lastname));

        if(empty($author)) {
            $author = $current_user->display_name;
        }

        if(empty($author)) {
            $author = $current_user->user_email;
        }

        if($this->source == 'headway'){

	        if(! class_exists('Headway')) {
	            require_once(dirname(__FILE__) . '/helpers/headway/functions.php');
	            require_once($this->source_dir . '/library/common/application.php');
	        }

	        Headway::init();
	        Headway::load('data/data-portability');

	        HeadwayOption::$current_skin = $_REQUEST['template'];

	        require_once(dirname(__FILE__) . '/helpers/headway/data-portability.php');

	        $info = array(
	            'name'      =>  'Headway export ' . date('m-d-Y'),
	            'author'    =>  $author,
	            'version'   =>  'v' . HEADWAY_VERSION,
	            'image-url' =>  ''
	        );

	        ob_start();
	        $filename 	= PadmaLifesaver\helpers\HeadwayDataPortability::export_skin($info);
	        $skin 		= ob_get_clean();

        }elseif ($this->source == 'bloxtheme') {

        	if(! class_exists('Blox')) {

                require_once(dirname(__FILE__) . '/helpers/bloxtheme/functions.php');
                require_once($this->source_dir . '/library/common/application.php');

            }


            Blox::init();
            Blox::load('data/data-portability');
            BloxOption::$current_skin = $_REQUEST['template'];           
            require_once(dirname(__FILE__) . '/helpers/bloxtheme/data-portability.php');

            $info = array(
                'name'      =>  'Blox export ' . date('m-d-Y'),
                'author'    =>  $author,
                'version'   =>  'v' . BLOX_VERSION,
                'image-url' =>  ''
            );

            ob_start();
            $filename 	= PadmaLifesaver\helpers\BloxDataPortability::export_skin($info);
            $skin 		= ob_get_clean();
        }
        return $skin;

    }



    private function data_replace($string){

        $search_for = array(
            '/headway_/',
            '/bloxtheme_/',
            '/headway\-/',
            '/bloxtheme\-/',
            '/(hw\-)/',
            '/(bt\-)/',
            '/(_hw_)/',
            '/(_bt_)/',
        );

        $replace_for = array(
            'padma_',
            'padma_',
            'padma-',
            'padma-',
            'pu-',
            'pu-',
            '_pu_',
            '_pu_',
        );

        return preg_replace($search_for, $replace_for, $string);

    }

    private function data_serialize($data){

        if(is_object($data))
            $data = (array)$data;

        if(is_serialized($data))
            $data = unserialize($data);

        if(is_array($data)){

            $new_data = array();
            foreach ($data as $key => $value) {
                $new_key            = $this->data_replace($key);
                $new_data[$new_key] = $this->data_serialize($value);
            }
            return $new_data;

        }else{
            return $this->data_replace($data);                
        }

        /*
        if(!is_array($data))
            $data = (array)$data;


        if($this->source == 'headway'){
            $search_for = 'headway';
        }elseif ($this->source == 'bloxtheme') {
            $search_for = 'bloxtheme';
        }

        $new_data = array();
        foreach ($data as $key => $group) {
            
            $group = (array)$group;
            
            foreach ($group as $param => $setting) {

                if(is_serialized($setting)){
                    $settings_data = unserialize( $setting );
                }else{
                    $settings_data = $setting;                    
                }

                if(is_array($settings_data)){     

                    foreach ($settings_data as $k => $value) {

                        if(preg_match('/$search_for/',$k)){
                            $new_key = $this->data_replace($k);
                        }else{
                            $new_key = $k;
                        }

                        //$settings_data[$new_key] = $this->data_replace_array($value);
                        
                    }
                }


                $new_data[$key][$param] = $settings_data;
            }
        }
        return $new_data;
        */
    }

    // $json = $skin
    private function converttoPadmaUnlimited($json) {

        $json       = json_decode($json);
        $imageURl   = 'image-url';
        $data       = array();


        if($this->source == 'headway'){

            // HTW Data
            $data['layout_meta']    = (array)$json->data_hw_layout_meta;
            $data['wrappers']       = (array)$json->data_hw_wrappers;
            $data['blocks']         = (array)$json->data_hw_blocks;
            
        }elseif ($this->source == 'bloxtheme') {

            // Blox Data
            $data['layout_meta']    = (array)$json->data_bt_layout_meta;
            $data['wrappers']       = (array)$json->data_bt_wrappers;
            $data['blocks']         = (array)$json->data_bt_blocks;

        }else{
            return;
        }


        $padmaSkin = array(
            'pu-version'            => '0.1.0',
            'name'                  => $json->name,
            'author'                => $json->author,
            'image-url'             => $json->$imageURl,
            'version'               => $json->version,
            'data_wp_options'       => $this->data_serialize($json->data_wp_options),
            'data_wp_postmeta'      => $this->data_serialize($json->data_wp_postmeta),
            'data_pu_layout_meta'   => $this->data_serialize($data['layout_meta']),
            'data_pu_wrappers'      => $this->data_serialize($data['wrappers']),
            'data_pu_blocks'        => $this->data_serialize($data['blocks']),
        );

        //debug($padmaSkin);

        /*
        if($this->source == 'headway'){

            $json = preg_replace('/(hw)/', 'pu', $json);
            $json = preg_replace('/(headway\_)/', 'padma_', $json);
           
        }elseif ($this->source == 'bloxtheme') {

            $json = preg_replace('/(bt)/', 'pu', $json);
            $json = preg_replace('/(bloxtheme\_)/', 'padma_', $json);

    	}else{
    		return;
    	}


        // pass on general settings
        $json   = json_decode($json);

     
         // WP Data
        
        $data['options'] = (array)$json->data_wp_options;
        $data['postmeta'] = (array)$json->data_wp_postmeta;

        if($this->source == 'headway'){

            // HTW Data
            $data['layout_meta']    = (array)$json->data_hw_layout_meta;
            $data['wrappers']       = (array)$json->data_hw_wrappers;
            $data['blocks']         = (array)$json->data_hw_blocks;
            
        }elseif ($this->source == 'bloxtheme') {

            // Blox Data
            $data['layout_meta']    = (array)$json->data_bt_layout_meta;
            $data['wrappers']       = (array)$json->data_bt_wrappers;
            $data['blocks']         = (array)$json->data_bt_blocks;

        }else{
            return;
        }


        $data_padma = array();
        foreach ($data as $skin_key => $skin_params) {

            debug($skin_key);
            debug($skin_params);
            
            $skin_params_options = array();
            foreach ($skin_params as $key => $option) {
                
                $option = (array)$option;
                //$skin_params_options[$key] = $option;

                foreach ($option as $param => $setting) {
                    if(is_serialized($setting)){
                        $skin_params_options[$key][$param] = unserialize( $setting );
                    }
                }
            }
            $data_padma[$skin_key] = json_encode($skin_params_options);

        }

        debug($data_padma);
        
        

        
        if($this->source == 'headway'){
        	
        	$data_pu_blocks 	= preg_replace('/(\-headway\-)/', '-padma-', $data_pu_blocks);

        }elseif ($this->source == 'bloxtheme'){

			$data_pu_blocks 	= preg_replace('/(\-bloxtheme\-)/', '-padma-', $data_pu_blocks);
        }

        // Setup new data
        $json->data_pu_wrappers     = $data_padma['wrappers'];
        $json->data_wp_options      = $data_padma['options'];
        $json->data_pu_blocks       = $data_padma['blocks'];
        $json->data_pu_layout_meta  = $data_padma['layout_meta'];
        $json->data_pu_postmeta     = $data_padma['postmeta'];
        $json                       = json_encode($json);

        */
        //debug($padmaSkin);
        return json_encode($padmaSkin);
    }


    public function setPadmaUnlimited($include_widgets) {

        debug('wtf');

        if(! class_exists('Padma')) {
            require_once(dirname(__FILE__) . '/helpers/padma/functions.php');
            //require_once($this->padma_dir . '/library/common/functions.php');
            require_once($this->padma_dir . '/library/common/application.php');
            require_once($this->padma_dir . '/library/common/templates.php');
            require_once($this->padma_dir . '/library/common/image-resizer.php');
            require_once($this->padma_dir . '/library/data/data-options.php');
            require_once($this->padma_dir . '/library/data/data-layout-options.php');
            require_once($this->padma_dir . '/library/data/data-snapshots.php');
            require_once($this->padma_dir  . '/library/data/data-portability.php');
            require_once($this->padma_dir  . '/library/visual-editor/visual-editor-ajax.php');
        }
        Padma::init();
        //Padma::load('visual-editor/visual-editor-ajax');
        Padma::load('data/data-portability');		

        if(file_exists($this->skin_path)) {
            
            $json = file_get_contents($this->skin_path);

            PadmaDataPortability::install_skin(json_decode($json, true));
            unlink($this->skin_path);
        }

        if(! empty($include_widgets)) {

            $sidebars_widgets 	= get_option('sidebars_widgets', array());
            $theme_mods 		= get_option('theme_mods_' . $this->source, array());

            if(isset($sidebars_widgets['array_version'])) {
                unset($sidebars_widgets['array_version']);
                $sidebars_widgets = array_filter($sidebars_widgets);
            }

            if(! isset($theme_mods['sidebars_widgets'])) {
                $theme_mods['sidebars_widgets'] = array();
            }

            // migrate hw/blox widgets to padma
            switch($this->template) {
                
                case 'padma':// if Padma in use

                    $sidebars_widgets 					= $theme_mods['sidebars_widgets']['data'];
                    $sidebars_widgets['array_version'] 	= 3;
                    update_option('sidebars_widgets', $sidebars_widgets);
                    break;
                
                case 'bloxtheme':// if Bloxtheme in use

                    $theme_mods['sidebars_widgets']['time'] = time();
                    $theme_mods['sidebars_widgets']['data'] = $sidebars_widgets;
                    update_option('theme_mods_padma', $theme_mods);
                    break;

                case 'headway': // if Headway in use
                    
                    $theme_mods['sidebars_widgets']['time'] = time();
                    $theme_mods['sidebars_widgets']['data'] = $sidebars_widgets;
                    update_option('theme_mods_padma', $theme_mods);
                    break;

                default:    // if any other theme in use
                    update_option('theme_mods_padma', $theme_mods);
                    break;
            }
        }

        $templates 		= PadmaTemplates::get_all();
        $last_template 	= end($templates);
        reset($templates);
        $_POST['skin'] 	= $last_template['id'];

        Padma::load('visual-editor/visual-editor-ajax');

        ob_start();
        PadmaVisualEditorAJAX::secure_method_switch_skin();
        ob_get_clean();
    }


    public function plugins_loaded() {

        if(isset($_REQUEST['PadmaLifesaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'PadmaLifesaver_nonce') !== false) {

                $this->template 	= get_option('template', '');                
                if($this->template != 'headway' && $this->template != 'bloxtheme') {
                    update_option('template', 'padma');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'headway' && $this->stylesheet != 'bloxtheme') {
                    update_option('stylesheet', 'padma');
                }

            } else {
                throw new Exception('PadmaLifesaver nonce invalid');
            }

        } elseif(isset($_REQUEST['lifesaver_install_skin']) && file_exists($this->skin_path)) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'lifesaver_skin_nonce') !== false) {

                $this->template 	= get_option('template', '');
                if($this->template != 'padma') {
                    update_option('template', 'padma');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'padma') {
                    update_option('stylesheet', 'padma');
                }
            } else {
                throw new Exception('PadmaLifesaver nonce invalid');
            }
        }
    }


    public function after_setup_theme() {

        if(isset($_REQUEST['PadmaLifesaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'PadmaLifesaver_nonce') !== false) {

            	$skin = $this->getSource();
                $json = $this->converttoPadmaUnlimited($skin);


                if(! empty($json)) {

                    file_put_contents($this->skin_path, $json);
                    do_action('lifesaver_json', empty($_REQUEST['widgets']) ? 0 : 1);

                    $referer 	= $_SERVER['HTTP_REFERER'];
                    $frags 		= explode('?', $referer);

                    if(isset($frags[1])) {

                        parse_str($frags[1], $query);
                        $query['PadmaLifesaver-convert'] = 'complete';

                        $referer = $frags[0] . '?' . http_build_query($query);
                    }

                    wp_redirect($referer);
                    exit;
                }
            } else {
                throw new Exception('PadmaLifesaver nonce invalid');
            }

        } elseif(isset($_REQUEST['lifesaver_install_skin']) && file_exists($this->skin_path)) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'lifesaver_skin_nonce') !== false) {

                if($this->template != 'padma' ) {
                    update_option('template', $this->template);
                }

                if($this->stylesheet != 'padma') {
                    update_option('stylesheet', $this->stylesheet);
                }

            } else {
                throw new Exception('PadmaLifesaver nonce invalid');
            }
        }
    }


    public function admin_notices() {

        if(isset($_GET['PadmaLifesaver-convert']) && $_GET['PadmaLifesaver-convert'] == 'complete') {
            $this->render_msg(ucfirst($this->source) . ' to Padma Unlimited conversion completed.');
        }

    }


    public function Settings() {

        if(! $this->source_exist) {
            $this->render_err(ucfirst($this->source) . ' theme does not exist');
        }

        if(! $this->padma_exist) {
            $this->render_err('Padma Unlimited does not exist! Please install the latest version.');
            die();
        }

        if($_GET['PadmaLifesaver-convert']!='complete'){
	        $this->render_msg('Make a full backup before start.');
	        $this->render_msg(ucfirst($this->source) . ' detected');        	
        }


        if($this->source == 'headway'){

	        if(! class_exists('HeadwayTemplates')) {
	            require($this->source_dir . '/library/common/image-resizer.php');
	            require_once(dirname(__FILE__) . '/helpers/headway/functions.php');
	            require($this->source_dir . '/library/data/data-options.php');
	            require($this->source_dir . '/library/common/templates.php');
	        }

	        $templates = HeadwayTemplates::get_all();
        	
        }elseif ($this->source == 'bloxtheme') {

        	if(! class_exists('BloxTemplates')) {
	            require($this->source_dir . '/library/common/image-resizer.php');
	            require_once(dirname(__FILE__) . '/helpers/bloxtheme/functions.php');
	            require($this->source_dir . '/library/data/data-options.php');
	            require($this->source_dir . '/library/common/templates.php');
	        }

	        $templates = BloxTemplates::get_all();
        }

        $this->render('settings', array(
            'source_exist'  =>  $this->source_exist,
            'padma_exist'   =>  $this->padma_exist,
            'nonce'     	=>  wp_create_nonce('PadmaLifesaver_nonce'),
            'templates' 	=>  $templates
        ));
    }


    /**
	 *
	 * Detect Source
	 *
	 */
	public function detectSource(){

    	if(class_exists('Headway')) {
    		$this->source = 'headway';
            return true;

        }elseif (class_exists('Blox')) {            
            $this->source = 'bloxtheme';
            return true;

        }elseif (file_exists(WP_CONTENT_DIR . '/themes/headway')) {
            $this->source = 'headway';
            return true;

        }elseif (file_exists(WP_CONTENT_DIR . '/themes/bloxtheme')) {
            $this->source = 'bloxtheme';
            return true;
    	}

        return false;
    }
}

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

$GLOBALS['PadmaLifesaver'] = new PadmaLifesaver();


// Updates
if(is_admin()){
    add_action('after_setup_theme', 'padma_lifesaver_updates');
    function padma_lifesaver_updates(){
        if ( ! empty ( $GLOBALS[ 'PadmaUpdater' ] ) ){
            $GLOBALS[ 'PadmaUpdater' ]->updater('padma-lifesaver',__DIR__);
        }
    }
}