<?php

/**
 *
 * Live saver class
 * Convert Headway or Blox templates to Padma
 * Based on hw-to-bt by Johnathan.PRO
 *
 */

class lifesaver extends lifesaver\helpers\Plugin {


	private $name;
	private $pre;
	public 	$version;
	private $scripts 	= array();
	private $styles 	= array();
	private $options 	= array();
	private $menu_pages = array();
	private $actions	= array();
	private $filters	= array();
	private $source;
	private $source_dir;
	private $padma_dir;
	private $template;
	private $stylesheet;
	private $skin_path;

    public function __construct($args = false) {

        $this->name 		= plugin_basename(__FILE__);
        $this->pre 			= strtolower(__CLASS__);
        $this->version 		= '1.0.0';
        $this->scripts 		= array(
            'admin' =>  array(
                $this->pre . '-js'	=>  array(
                    'src'	=>  plugins_url('/js/admin.js', __FILE__)
                )
            )
        );
        $this->styles 		= array(
            'admin' =>  array(
                $this->pre . '-css'  =>  array(
                    'src'	=>  plugins_url('/css/admin.css', __FILE__)
                )
            )
        );
        $this->options 		= array();
        $this->menu_pages 	= array(
            'Life saver'   =>  array(
                'capability'    =>  'edit_dashboard',
                'position'      =>  '1',
                'func'          =>  'Settings',
            ),
        );
        $this->actions 		= array(
            'plugins_loaded'        	=>  false,
            'after_setup_theme'     	=>  false,
            'admin_notices'         	=>  false,
            'wp_async_lifesaver_json'  	=>  'setPadma'
        );
        $this->filters 		= array();

        //register the plugin and init assets
        $this->register_plugin($this->name, __FILE__, true);

        $this->source 		= $this->detectSource();
        $this->source_dir 	= WP_CONTENT_DIR . '/themes/' . $this->source;
        $this->padma_dir 	= WP_CONTENT_DIR . '/themes/padma';
        $this->template 	= '';
        $this->stylesheet 	= '';
        $this->skin_path 	= wp_upload_dir()['basedir'] . '/hwdata.json';

        new lifesaver\helpers\json();
    }

    private function detectSource(){

    	if(class_exists('Headway')) {    		
    		return 'headway';

    	}elseif (class_exists('Blox')) {    		
    		return 'bloxtheme';

    	}elseif (file_exists(WP_CONTENT_DIR . '/themes/headway')) {
    		return 'headway';

    	}elseif (file_exists(WP_CONTENT_DIR . '/themes/bloxtheme')) {
    		return 'bloxtheme';    		
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
	            Headway::init();
        		Headway::load('data/data-portability');
        		require_once(dirname(__FILE__) . '/helpers/headway/data-portability.php');

        		$info = array(
		            'name'      =>  'Headway export ' . date('m-d-Y'),
		            'author'    =>  $author,
		            'version'   =>  'v' . HEADWAY_VERSION,
		            'image-url' =>  ''
		        );

	        }
        }elseif ($this->source == 'bloxtheme') {
        	if(! class_exists('Blox')) {
	            require_once(dirname(__FILE__) . '/helpers/bloxtheme/functions.php');
	            require_once($this->source_dir . '/library/common/application.php');
	            Blox::init();
        		Blox::load('data/data-portability');
        		require_once(dirname(__FILE__) . '/helpers/bloxtheme/data-portability.php');

        		$info = array(
		            'name'      =>  'Blox export ' . date('m-d-Y'),
		            'author'    =>  $author,
		            'version'   =>  'v' . BLOX_VERSION,
		            'image-url' =>  ''
		        );
	        }
        }

        PadmaOption::$current_skin = $_REQUEST['template'];        

        ob_start();
        $filename 	= lifesaver\helpers\PadmaDataPortability::export_skin($info);
        $skin 		= ob_get_clean();

        return $skin;
    }

    private function convertToPadma($json) {

    	debug($json);

    	if($this->source == 'headway'){

    		$json = preg_replace('/(hw)/', 'padma', $json);
        	$json = preg_replace('/(headway\_)/', 'padma_', $json);

    	}elseif ($this->source == 'bloxtheme') {
    		
    		$json = preg_replace('/(bt)/', 'padma', $json);
        	$json = preg_replace('/(bloxtheme\_)/', 'padma_', $json);

    	}        

        // pass on general settings
        $json 						= json_decode($json);
        $data_padma_blocks 			= json_encode($json->data_padma_blocks);
        $data_padma_blocks 			= preg_replace('/(\-'.$this->source.'\-)/', '-padma-', $data_padma_blocks);
        $json->data_padma_blocks 	= json_decode($data_padma_blocks);
        $json 						= json_encode($json);

        return $json;

    }

    public function setPadma($include_widgets) {

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

            // migrate headway or blox widgets to padma
            if($this->template == 'padma'){

                $sidebars_widgets 					= $theme_mods['sidebars_widgets']['data'];
                $sidebars_widgets['array_version'] 	= 3;
                update_option('sidebars_widgets', $sidebars_widgets);
            	
            }elseif ($this->template == 'headway' || $this->template == 'bloxtheme' ) {
            	
                $theme_mods['sidebars_widgets']['time'] = time();
                $theme_mods['sidebars_widgets']['data'] = $sidebars_widgets;
                update_option('theme_mods_padma', $theme_mods);
            
            }else{

            	update_option('theme_mods_padma', $theme_mods);

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

        if(isset($_REQUEST['lifesaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'lifesaver_nonce') !== false) {

                $this->template = get_option('template', '');

                if($this->source == 'headway'){
	                if($this->template != 'headway') {
	                    update_option('template', 'headway');
	                }
	                $this->stylesheet = get_option('stylesheet', '');
	                if($this->stylesheet != 'headway') {
	                    update_option('stylesheet', 'headway');
	                }
                }elseif ($this->source == 'bloxtheme') {
                	if($this->template != 'bloxtheme') {
	                    update_option('template', 'bloxtheme');
	                }
	                $this->stylesheet = get_option('stylesheet', '');
	                if($this->stylesheet != 'bloxtheme') {
	                    update_option('stylesheet', 'bloxtheme');
	                }
                }

            } else {
                throw new Exception('Life saver nonce invalid');
            }

        } elseif(isset($_REQUEST['lifesaver_install_skin']) && file_exists($this->skin_path)) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'lifesaver_skin_nonce') !== false) {

                $this->template = get_option('template', '');

                if($this->template != 'padma') {
                    update_option('template', 'padma');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'padma') {
                    update_option('stylesheet', 'padma');
                }
            } else {
                throw new Exception('Life saver nonce invalid');
            }
        }
    }

    public function after_setup_theme() {

        if(isset($_REQUEST['lifesaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'lifesaver_nonce') !== false) {

                $json = $this->convertToPadma($this->getSource());

                if($this->source == 'headway'){
	                if($this->template != 'headway') {
	                    update_option('template', $this->template);
	                }

	                if($this->stylesheet != 'headway') {
	                    update_option('stylesheet', $this->stylesheet);
	                }
                }elseif ($this->source == 'bloxtheme') {
                	if($this->template != 'bloxtheme') {
	                    update_option('template', $this->template);
	                }

	                if($this->stylesheet != 'bloxtheme') {
	                    update_option('stylesheet', $this->stylesheet);
	                }
                }                

                if(! empty($json)) {
                    file_put_contents($this->skin_path, $json);
                    do_action('lifesaver_json', empty($_REQUEST['widgets']) ? 0 : 1);

                    $referer = $_SERVER['HTTP_REFERER'];
                    $frags = explode('?', $referer);

                    if(isset($frags[1])) {
                        parse_str($frags[1], $query);
                        $query['lifesaver-convert'] = 'complete';

                        $referer = $frags[0] . '?' . http_build_query($query);
                    }

                    wp_redirect($referer);
                    exit;
                }
            } else {
                throw new Exception('Life saver nonce invalid');
            }

        } elseif(isset($_REQUEST['lifesaver_install_skin']) && file_exists($this->skin_path)) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'lifesaver_skin_nonce') !== false) {

                if($this->template != 'padma') {
                    update_option('template', $this->template);
                }

                if($this->stylesheet != 'padma') {
                    update_option('stylesheet', $this->stylesheet);
                }
            } else {
                throw new Exception('Life saver nonce invalid');
            }
        }
    }

    public function admin_notices() {
        if(isset($_GET['lifesaver-convert']) && $_GET['lifesaver-convert'] == 'complete') {
            $this->render_msg( ucfirst($this->Source) . ' to Padma conversion completed');
        }
    }

    public function Settings() {

    	$exist_source_dir 	= file_exists($this->source_dir);
    	$exist_padma_dir 	= file_exists($this->padma_dir);

        if(! $exist_source_dir ) {
            $this->render_err( ucfirst($this->source) . ' theme does not exist');
        }

        if(! $exist_padma_dir ) {
            $this->render_err('Padma theme does not exist');
        }

        switch ($this->source) {

        	case 'headway':
		        if( !class_exists('HeadwayTemplates')) {
		            require($this->source_dir . '/library/common/image-resizer.php');
		            require_once(dirname(__FILE__) . '/helpers/headway/functions.php');
		            require($this->source_dir . '/library/data/data-options.php');
		            require($this->source_dir . '/library/common/templates.php');
		        }
		        $templates = HeadwayTemplates::get_all();
        		break;

        	case 'bloxtheme':
        		if( !class_exists('BloxTemplates')) {
		            require($this->source_dir . '/library/common/image-resizer.php');
		            require_once(dirname(__FILE__) . '/helpers/bloxtheme/functions.php');
		            require($this->source_dir . '/library/data/data-options.php');
		            require($this->source_dir . '/library/common/templates.php');
		        }
		        $templates = BloxTemplates::get_all();
        		break;
        	
        	default:
        		return;        		
        		break;
        }
        

        $this->render('settings', array(
            'Source'	=>  $exist_source_dir,
            'Padma'     =>  $exist_padma_dir,
            'nonce'     =>  wp_create_nonce('lifesaver_nonce'),
            'templates' =>  $templates
        ));
    }
}
