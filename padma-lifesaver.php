<?php
/*
Plugin Name:    Padma Lifesaver
Plugin URI:     https://padmaunlimited/plugins/padma-lifesaver
Description:    Padma Lifesaver plugin allows convert Headway or Blox Templates to Padma Unlimited Templates. Based on the original plugin hw-to-bt from Johnathan.PRO.
Version:        1.0.8
Author:         Padma Unlimited Team
Author URI:     https://www.padmaunlimited.com/
License:        GPL2
License URI:    https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:    padma-lifesaver
Domain Path:    /languages
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

        /*  Errors  */
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        @ini_set('display_errors', 'Off');
        

        $this->name     = plugin_basename(__FILE__);
        $this->pre      = strtolower(__CLASS__);
        $this->version  = '1.0.7';

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
            'Padma LifeSaver'   =>  array(
                'capability'        =>  'edit_dashboard',
                'position'          =>  '1',
                'func'              =>  'Settings',
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
        $this->padma_exist  = file_exists($this->padma_dir);
        $this->template     = '';
        $this->stylesheet   = '';


        $this->testBeforeGo();

        $basedir            = wp_upload_dir();
        $basedir            = $basedir['basedir'];
        $this->skin_path    = $basedir . '/hwdata.json';

        new PadmaLifeSaver\helpers\json();

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
            $filename   = PadmaLifeSaver\helpers\HeadwayDataPortability::export_skin($info);
            $skin       = ob_get_clean();

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
            $filename   = PadmaLifeSaver\helpers\BloxDataPortability::export_skin($info);
            $skin       = ob_get_clean();
        }
        return $skin;

    }


    // $json = $skin
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

        
        return json_encode($padmaSkin);
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

            $sidebars_widgets   = get_option('sidebars_widgets', array());
            $theme_mods         = get_option('theme_mods_' . $this->source, array());

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

                    $sidebars_widgets                   = $theme_mods['sidebars_widgets']['data'];
                    $sidebars_widgets['array_version']  = 3;
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

        $templates      = PadmaTemplates::get_all();
        $last_template  = end($templates);
        reset($templates);
        $_POST['skin']  = $last_template['id'];

        Padma::load('visual-editor/visual-editor-ajax');

        ob_start();
        PadmaVisualEditorAJAX::secure_method_switch_skin();
        ob_get_clean();
    }


    public function plugins_loaded() {

        if(isset($_REQUEST['PadmaLifeSaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'PadmaLifeSaver_nonce') !== false) {

                $this->template     = get_option('template', '');                
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

                $this->template     = get_option('template', '');
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

                $skin = $this->getSource();
                $json = $this->converttoPadmaUnlimited($skin);

                if(! empty($json)) {

                    file_put_contents($this->skin_path, $json);
                    do_action('life_saver_json', empty($_REQUEST['widgets']) ? 0 : 1);

                    $referer    = $_SERVER['HTTP_REFERER'];
                    $frags      = explode('?', $referer);

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
            $this->render_err('Padma Unlimited does not exist! Please install the latest version.');
            die();
        }

        if($_GET['PadmaLifeSaver-convert']!='complete'){
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
            'nonce'         =>  wp_create_nonce('PadmaLifeSaver_nonce'),
            'templates'     =>  $templates
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


// Updates
if(is_admin()){
    add_action('after_setup_theme', 'padma_lifesaver_updates');
    function padma_lifesaver_updates(){
        if ( ! empty ( $GLOBALS[ 'PadmaUpdater' ] ) ){
            $GLOBALS[ 'PadmaUpdater' ]->updater('padma-lifesaver',__DIR__);
        }
    }
}