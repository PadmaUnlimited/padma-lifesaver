<?php
/*
Plugin Name:    Padma Lifesaver
Plugin URI:     https://padmaunlimited/plugins/padma-lifesaver
Description:    Padma Lifesaver plugin allows convert Headway or Blox Templates to Padma Unlimited Templates. Based on the original plugin hw-to-bt from Johnathan.PRO.
Version:        1.0.12
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


class Lifesaver extends PadmaLifesaver\helpers\Plugin {

    
    private $source;
    private $source_exist;
    private $source_dir;
    private $exist_source_dir;
    private $exist_padma_dir;

    
    public function __construct($args = false) {

        $this->name     = plugin_basename(__FILE__);
        $this->pre      = strtolower(__CLASS__);
        $this->version  = '1.0.11';

        $this->detectSource();
        
        $_SESSION['padma-lifesaver-source'] = $this->source;

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
            'wp_async_lifesaver_json'  =>  'setPadmaUnlimited'
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

        new PadmaLifesaver\helpers\json();

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
            $filename   = PadmaLifesaver\helpers\HeadwayDataPortability::export_skin($info);
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
            $filename   = PadmaLifesaver\helpers\BloxDataPortability::export_skin($info);
            $skin       = ob_get_clean();
        }
        return $skin;

    }


    private function data_replace($string){

        $search_for = array(
            '/(hw)/',
            '/(headway\_)/',
            '/(bt)/',
            '/(bloxtheme\_)/',
            '/(\-headway\-)/',
            '/(\-bloxtheme\-)/',
        );

        $replace_for = array(
            'pu',
            'padma_',
            'pu',
            'padma_',
            '-padma-',
            '-padma-',
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

    
    private function converttoPadmaUnlimited($json) {
        
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
        $json                   = json_decode($json);
        $data_pu_blocks         = json_encode($json->data_pu_blocks);
        

        
        if($this->source == 'headway'){
            
            $data_pu_blocks     = preg_replace('/(\-headway\-)/', '-padma-', $data_pu_blocks);

        }elseif ($this->source == 'bloxtheme'){

            $data_pu_blocks     = preg_replace('/(\-bloxtheme\-)/', '-padma-', $data_pu_blocks);
        }

        $json->data_pu_blocks   = json_decode($data_pu_blocks);
        $json                   = json_encode($json);

        return $json;
        
    }


    public function setPadmaUnlimited($include_widgets) {


        if(! class_exists('Padma')) {
            require_once(dirname(__FILE__) . '/helpers/padma/functions.php');
            require_once($this->padma_dir . '/library/common/application.php');
        }
        
        Padma::init();
        Padma::load('data/data-portability');
        Padma::load('visual-editor/visual-editor-ajax');      
        
        if(file_exists($this->skin_path)) {

            $json = file_get_contents($this->skin_path);
            $json = PadmaVisualEditorAJAX::replace_imported_images_variables($json);
            PadmaDataPortability::install_skin( json_decode($json, true));
            
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

        /**
         *
         * Migrate post meta
         *
         */
        $this->migrate_postmeta();
        

        ob_start();
        PadmaVisualEditorAJAX::secure_method_switch_skin();
        ob_get_clean();
    
    }

    public function migrate_postmeta(){

        global $wpdb;

        $meta = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key like '%s' or meta_key like '%s'", $wpdb->esc_like('_hw_') . '%', $wpdb->esc_like('_bt_') . '%')
            , ARRAY_A);
        

        foreach ($meta as $key => $data) {

            $meta_id = $data['meta_id'];
            $post_id = $data['post_id'];

            $meta_key = $data['meta_key'];
            $meta_key =  str_replace('_hw_', '_pu_', $meta_key);
            $meta_key =  str_replace('_bt_', '_pu_', $meta_key);
            
            $meta_value = $data['meta_value'];

            add_post_meta($post_id, $meta_key, $meta_value);

        }
        

    }

    public function plugins_loaded() {

        if(isset($_REQUEST['PadmaLifesaver'])) {

            if(wp_verify_nonce($_REQUEST['nonce'], 'PadmaLifesaver_nonce') !== false) {


                $this->template     = get_option('template', '');                
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

                $this->template     = get_option('template', '');
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

                    $referer    = $_SERVER['HTTP_REFERER'];
                    $frags      = explode('?', $referer);

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
            $this->render_msg(ucfirst($this->source) . ' to Padma Unlimited conversion completed. You can safely uninstall this plugin.');
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
            'nonce'         =>  wp_create_nonce('PadmaLifesaver_nonce'),
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

$GLOBALS['Lifesaver'] = new Lifesaver();


/**
 *
 * Activation hook
 *
 */
function padma_lifesaver_activate(){
    
    if ( ! current_user_can( 'activate_plugins' ) )
    return;

    
    global $wpdb;

    $wpdb->pu_blocks        = $wpdb->prefix . 'pu_blocks';
    $wpdb->pu_wrappers      = $wpdb->prefix . 'pu_wrappers';
    $wpdb->pu_snapshots     = $wpdb->prefix . 'pu_snapshots';
    $wpdb->pu_layout_meta   = $wpdb->prefix . 'pu_layout_meta';

    $tables = array(
        $wpdb->pu_blocks => 'pu_blocks',
        $wpdb->pu_wrappers => 'pu_wrappers',
        $wpdb->pu_snapshots => 'pu_snapshots',
        $wpdb->pu_layout_meta => 'pu_layout_meta',
    );


    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = '';

    if ( ! empty( $wpdb->charset ) ) {
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    }
    if ( ! empty( $wpdb->collate ) ) {
        $charset_collate .= " COLLATE $wpdb->collate";
    }

    foreach ($tables as $table_name => $table_structure) {        

        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {             
                      
            $sql = get_padma_db_structures($table_name, $table_structure);
            //debug($sql);
            dbDelta( $sql );

            if ( function_exists('maybe_convert_table_to_utf8mb4') ) {
                maybe_convert_table_to_utf8mb4( $table_name );
            }

        }
        
    }

}
register_activation_hook( __FILE__, 'padma_lifesaver_activate');



function get_padma_db_structures($table_name, $table_structure){

    $structures = array(
        'pu_blocks' => "CREATE TABLE $table_name (
                      id char(20) NOT NULL,
                      template varchar(100) NOT NULL,
                      layout varchar(80) NOT NULL,
                      type varchar(30) NOT NULL,
                      wrapper_id char(20) NOT NULL,
                      position blob NOT NULL,
                      dimensions blob NOT NULL,
                      settings mediumblob,
                      mirror_id char(20) DEFAULT NULL,
                      legacy_id int(11) unsigned DEFAULT NULL,
                      PRIMARY KEY  (id,template),
                      KEY layout (layout),
                      KEY type (type)
                    ) $charset_collate;",

        'pu_wrappers' => "CREATE TABLE $table_name (
                      id char(20) NOT NULL,
                      template varchar(100) NOT NULL,
                      layout varchar(80) NOT NULL,
                      position tinyint(2) unsigned DEFAULT NULL,
                      settings mediumblob,
                      mirror_id char(20) DEFAULT NULL,
                      legacy_id int(11) unsigned DEFAULT NULL,
                      PRIMARY KEY  (id,template),
                      KEY layout (layout)
                    ) $charset_collate;",
                    
        'pu_layout_meta' => "CREATE TABLE $table_name (
                      meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      template varchar(100) NOT NULL,
                      layout varchar(80) NOT NULL,
                      meta_key varchar(255),
                      meta_value mediumblob,
                      PRIMARY KEY  (meta_id,template),
                      KEY template (layout)
                    ) $charset_collate;",
                    
        'pu_snapshots' => "CREATE TABLE $table_name (
                      id int(11) unsigned NOT NULL AUTO_INCREMENT,
                      template varchar(100) NOT NULL,
                      timestamp datetime NOT NULL,
                      comments text,
                      data_wp_options longblob,
                      data_wp_postmeta longblob,
                      data_pu_layout_meta longblob,
                      data_pu_wrappers longblob,
                      data_pu_blocks longblob,
                      data_other longblob,
                      PRIMARY KEY  (id),
                      KEY template (template)
                    ) $charset_collate;",
    );

    return $structures[$table_structure];
}
