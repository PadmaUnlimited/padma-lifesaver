<?php
/*
Plugin Name:	Padma Life Saver
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

require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/helpers/Async.php');

class PadmaLifeSaver extends PadmaLifeSaver\helpers\Plugin {

	
	private $source;
	private $source_exist;
	private $source_dir;
	private $exist_source_dir;
	private $exist_padma_dir;

    
    public function __construct($args = false) {

        $this->name 	= plugin_basename(__FILE__);
        $this->pre 		= strtolower(__CLASS__);
        $this->version 	= '1.0.0';

        $this->detectSource();

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
            'Padma Life Saver'  	=>  array(
                'capability'    	=>  'edit_dashboard',
                'position'      	=>  '1',
                'func'          	=>  'Settings',
            ),
        );

        $this->actions = array(
            'plugins_loaded'        =>  false,
            'after_setup_theme'     =>  false,
            'admin_notices'         =>  false,
            'wp_async_life_saver_json'  =>  'setPadmaUnlimited'
        );

        $this->filters = array();

        //register the plugin and init assets
        $this->register_plugin($this->name, __FILE__, true);
     

        $this->source_dir   = WP_CONTENT_DIR . '/themes/' . $this->source;
        $this->padma_dir    = WP_CONTENT_DIR . '/themes/padma';
        $this->source_exist = file_exists($this->source_dir);
        $this->padma_exist 	= file_exists($this->padma_dir);
        $this->template 	= '';
        $this->stylesheet 	= '';

        $this->skin_path = wp_upload_dir()['basedir'] . '/hwdata.json';        

        new PadmaLifeSaver\helpers\json();

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
	        $filename 	= PadmaLifeSaver\helpers\HeadwayDataPortability::export_skin($info);
	        $skin 		= ob_get_clean();

	        return $skin;

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
            $filename 	= PadmaLifeSaver\helpers\BloxDataPortability::export_skin($info);
            $skin 		= ob_get_clean();

            return $skin;
        }

    }


    private function converttoPadmaUnlimited($json) {

    	//debug($json);

    	if($this->source == 'headway'){

    		$json = preg_replace('/(hw)/', 'pu', $json);
        	$json = preg_replace('/(headway\_)/', 'padma_', $json);

    	}elseif ($this->source == 'bloxtheme') {
    		
    		$json = preg_replace('/(bt)/', 'padma', $json);
        	$json = preg_replace('/(bloxtheme\_)/', 'padma_', $json);       	

    	}   

    	// pass on general settings
        $json 					= json_decode($json);
        $data_pu_blocks 		= json_encode($json->data_pu_blocks);
        
        if($this->source == 'headway'){
        	
        	$data_pu_blocks 		= preg_replace('/(\-headway\-)/', '-padma-', $data_pu_blocks);

        }elseif ($this->source == 'bloxtheme') {
			$data_pu_blocks 		= preg_replace('/(\-bloxtheme\-)/', '-padma-', $data_pu_blocks);
        }

        $json->data_pu_blocks 	= json_decode($data_pu_blocks);

        

        $json = json_encode($json);

        return $json;
    }


    public function setPadmaUnlimited($include_widgets) {

        if(! class_exists('Padma')) {
            require_once(dirname(__FILE__) . '/helpers/padma/functions.php');
            require_once($this->padma_dir . '/library/common/application.php');
        }

        Padma::init();
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

            // migrate hw widgets to blox
            switch($this->template) {
                
                case 'padma':// if Bloxtheme in use

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

        if(isset($_REQUEST['PadmaLifeSaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'PadmaLifeSaver_nonce') !== false) {

                $this->template 	= get_option('template', '');                
                if($this->template != 'headway' && $this->template != 'bloxtheme') {
                    update_option('template', 'padma');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'headway' && $this->stylesheet != 'bloxtheme') {
                    update_option('stylesheet', 'padma');
                }

            } else {
                throw new Exception('PadmaLifeSaver nonce invalid');
            }

        } elseif(isset($_REQUEST['life_saver_install_skin']) && file_exists($this->skin_path)) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'life_saver_skin_nonce') !== false) {

                $this->template 	= get_option('template', '');
                if($this->template != 'padma') {
                    update_option('template', 'padma');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'padma') {
                    update_option('stylesheet', 'padma');
                }
            } else {
                throw new Exception('PadmaLifeSaver nonce invalid');
            }
        }
    }


    public function after_setup_theme() {

        if(isset($_REQUEST['PadmaLifeSaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'PadmaLifeSaver_nonce') !== false) {

                $json = $this->converttoPadmaUnlimited($this->getSource());

                if($this->template != 'headway' && $this->template != 'bloxtheme') {
                    update_option('template', $this->template);
                }

                if($this->stylesheet != 'headway' && $this->stylesheet != 'bloxtheme') {
                    update_option('stylesheet', $this->stylesheet);
                }

                if(! empty($json)) {

                    file_put_contents($this->skin_path, $json);
                    do_action('life_saver_json', empty($_REQUEST['widgets']) ? 0 : 1);

                    $referer 	= $_SERVER['HTTP_REFERER'];
                    $frags 		= explode('?', $referer);

                    if(isset($frags[1])) {

                        parse_str($frags[1], $query);
                        $query['PadmaLifeSaver-convert'] = 'complete';

                        $referer = $frags[0] . '?' . http_build_query($query);
                    }

                    wp_redirect($referer);
                    exit;
                }
            } else {
                throw new Exception('PadmaLifeSaver nonce invalid');
            }

        } elseif(isset($_REQUEST['life_saver_install_skin']) && file_exists($this->skin_path)) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'life_saver_skin_nonce') !== false) {

                if($this->template != 'padma' ) {
                    update_option('template', $this->template);
                }

                if($this->stylesheet != 'padma') {
                    update_option('stylesheet', $this->stylesheet);
                }

            } else {
                throw new Exception('PadmaLifeSaver nonce invalid');
            }
        }
    }


    public function admin_notices() {

        if(isset($_GET['PadmaLifeSaver-convert']) && $_GET['PadmaLifeSaver-convert'] == 'complete') {
            $this->render_msg(ucfirst($this->source) . ' to Padma Unlimited conversion completed.');
        }

    }


    public function Settings() {

        if(! $this->source_exist) {
            $this->render_err(ucfirst($this->source) . ' theme does not exist');
        }

        if(! $this->padma_exist) {
            $this->render_err('Padma Unlimited does not exist');
        }

        $this->render_msg(ucfirst($this->source) . ' detected');


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
            'nonce'     	=>  wp_create_nonce('PadmaLifeSaver_nonce'),
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

    	}elseif (class_exists('Blox')) {    		
    		$this->source = 'bloxtheme';

    	}elseif (file_exists(WP_CONTENT_DIR . '/themes/headway')) {
    		$this->source = 'headway';

    	}elseif (file_exists(WP_CONTENT_DIR . '/themes/bloxtheme')) {
    		$this->source = 'bloxtheme';
    	}

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

$GLOBALS['PadmaLifeSaver'] = new PadmaLifeSaver();