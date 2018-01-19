<?php
/**
Plugin Name: Headway to Blox Converter
Description: Convert Headway Templates to Blox Templates
Version: 1.0.0.0
Author: Johnathan.PRO
Author URI: http://Johnathan.PRO
Network: false
License: GPL
 */

defined('ABSPATH') or die( 'Access Forbidden!' );

require_once(dirname(__FILE__) . '/helpers/Plugin.php');
require_once(dirname(__FILE__) . '/helpers/Async.php');

class hwtobt extends hwtobt\helpers\Plugin {
    public function __construct($args = false) {
        $this->name = plugin_basename(__FILE__);
        $this->pre = strtolower(__CLASS__);
        $this->version = '1.0.0.0';

        $this->scripts = array(
            'admin' =>  array(
                $this->pre . '-js'           =>  array(
                    'src'           =>  plugins_url('/js/admin.js', __FILE__)
                )
            )
        );

        $this->styles = array(
            'admin' =>  array(
                $this->pre . '-css'  =>  array(
                    'src'           =>  plugins_url('/css/admin.css', __FILE__)
                )
            )
        );

        $this->options = array();

        $this->menu_pages = array(
            'HW to BT'   =>  array(
                'capability'    =>  'edit_dashboard',
                'position'      =>  '1',
                'func'          =>  'Settings',
            ),
        );

        $this->actions = array(
            'plugins_loaded'        =>  false,
            'after_setup_theme'     =>  false,
            'admin_notices'         =>  false,
            'wp_async_hwtobt_json'  =>  'setBT'
        );

        $this->filters = array();

        //register the plugin and init assets
        $this->register_plugin($this->name, __FILE__, true);

        $this->hw_dir = WP_CONTENT_DIR . '/themes/headway';
        $this->bt_dir = WP_CONTENT_DIR . '/themes/bloxtheme';

        $this->hw = file_exists($this->hw_dir);
        $this->bt = file_exists($this->bt_dir);

        $this->template = '';
        $this->stylesheet = '';

        $this->skin_path = wp_upload_dir()['basedir'] . '/hwdata.json';

        new hwtobt\helpers\json();
    }

    private function getHW() {
        global $current_user;

        $author = implode(' ', array($current_user->user_firstname, $current_user->user_lastname));

        if(empty($author)) {
            $author = $current_user->display_name;
        }

        if(empty($author)) {
            $author = $current_user->user_email;
        }

        if(! class_exists('Headway')) {
            require_once(dirname(__FILE__) . '/helpers/headway/functions.php');
            require_once($this->hw_dir . '/library/common/application.php');
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
        $filename = hwtobt\helpers\HeadwayDataPortability::export_skin($info);
        $skin = ob_get_clean();

        return $skin;
    }

    private function converttoBT($json) {
        $json = preg_replace('/(hw)/', 'bt', $json);
        $json = preg_replace('/(headway\_)/', 'blox_', $json);

        // pass on general settings
        $json = json_decode($json);
        $data_bt_blocks = json_encode($json->data_bt_blocks);
        $data_bt_blocks = preg_replace('/(\-headway\-)/', '-blox-', $data_bt_blocks);
        $json->data_bt_blocks = json_decode($data_bt_blocks);

        $json = json_encode($json);

        return $json;
    }

    public function setBT($include_widgets) {
        if(! class_exists('Blox')) {
            require_once(dirname(__FILE__) . '/helpers/bloxtheme/functions.php');
            require_once($this->bt_dir . '/library/common/application.php');
        }

        Blox::init();
        Blox::load('data/data-portability');

        if(file_exists($this->skin_path)) {
            $json = file_get_contents($this->skin_path);
            BloxDataPortability::install_skin(json_decode($json, true));
            unlink($this->skin_path);
        }

        if(! empty($include_widgets)) {
            $sidebars_widgets = get_option('sidebars_widgets', array());
            $theme_mods = get_option('theme_mods_headway', array());

            if(isset($sidebars_widgets['array_version'])) {
                unset($sidebars_widgets['array_version']);
                $sidebars_widgets = array_filter($sidebars_widgets);
            }

            if(! isset($theme_mods['sidebars_widgets'])) {
                $theme_mods['sidebars_widgets'] = array();
            }

            // migrate hw widgets to blox
            switch($this->template) {
                case 'bloxtheme':// if Bloxtheme in use
                    $sidebars_widgets = $theme_mods['sidebars_widgets']['data'];
                    $sidebars_widgets['array_version'] = 3;

                    update_option('sidebars_widgets', $sidebars_widgets);
                    break;
                case 'headway': // if Headway in use
                    $theme_mods['sidebars_widgets']['time'] = time();
                    $theme_mods['sidebars_widgets']['data'] = $sidebars_widgets;

                    update_option('theme_mods_bloxtheme', $theme_mods);
                    break;
                default:    // if any other theme in use
                    update_option('theme_mods_bloxtheme', $theme_mods);
                    break;
            }
        }

        $templates = BloxTemplates::get_all();
        $last_template = end($templates);
        reset($templates);
        $_POST['skin'] = $last_template['id'];

        Blox::load('visual-editor/visual-editor-ajax');

        ob_start();
        BloxVisualEditorAJAX::secure_method_switch_skin();
        ob_get_clean();
    }

    public function plugins_loaded() {
        if(isset($_REQUEST['hwtobt'])) {
            if(wp_verify_nonce($_REQUEST['nonce'], 'hwtobt_nonce') !== false) {
                $this->template = get_option('template', '');
                if($this->template != 'headway') {
                    update_option('template', 'headway');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'headway') {
                    update_option('stylesheet', 'headway');
                }
            } else {
                throw new Exception('HW to BT nonce invalid');
            }
        } elseif(isset($_REQUEST['hwtobt_install_skin']) && file_exists($this->skin_path)) {
            if(wp_verify_nonce($_REQUEST['nonce'], 'hwtobt_skin_nonce') !== false) {
                $this->template = get_option('template', '');
                if($this->template != 'bloxtheme') {
                    update_option('template', 'bloxtheme');
                }

                $this->stylesheet = get_option('stylesheet', '');
                if($this->stylesheet != 'bloxtheme') {
                    update_option('stylesheet', 'bloxtheme');
                }
            } else {
                throw new Exception('HW to BT nonce invalid');
            }
        }
    }

    public function after_setup_theme() {
        if(isset($_REQUEST['hwtobt'])) {
            if(wp_verify_nonce($_REQUEST['nonce'], 'hwtobt_nonce') !== false) {
                $json = $this->converttoBT($this->getHW());

                if($this->template != 'headway') {
                    update_option('template', $this->template);
                }

                if($this->stylesheet != 'headway') {
                    update_option('stylesheet', $this->stylesheet);
                }

                if(! empty($json)) {
                    file_put_contents($this->skin_path, $json);
                    do_action('hwtobt_json', empty($_REQUEST['widgets']) ? 0 : 1);

                    $referer = $_SERVER['HTTP_REFERER'];
                    $frags = explode('?', $referer);

                    if(isset($frags[1])) {
                        parse_str($frags[1], $query);
                        $query['hwtobt-convert'] = 'complete';

                        $referer = $frags[0] . '?' . http_build_query($query);
                    }

                    wp_redirect($referer);
                    exit;
                }
            } else {
                throw new Exception('HW to BT nonce invalid');
            }
        } elseif(isset($_REQUEST['hwtobt_install_skin']) && file_exists($this->skin_path)) {
            if(wp_verify_nonce($_REQUEST['nonce'], 'hwtobt_skin_nonce') !== false) {
                if($this->template != 'bloxtheme') {
                    update_option('template', $this->template);
                }

                if($this->stylesheet != 'bloxtheme') {
                    update_option('stylesheet', $this->stylesheet);
                }
            } else {
                throw new Exception('HW to BT nonce invalid');
            }
        }
    }

    public function admin_notices() {
        if(isset($_GET['hwtobt-convert']) && $_GET['hwtobt-convert'] == 'complete') {
            $this->render_msg('Headway to Blox conversion completed');
        }
    }

    public function Settings() {
        if(! $this->hw) {
            $this->render_err('Headway theme does not exist');
        }

        if(! $this->bt) {
            $this->render_err('Blox theme does not exist');
        }

        if(! class_exists('HeadwayTemplates')) {
            require($this->hw_dir . '/library/common/image-resizer.php');
            require_once(dirname(__FILE__) . '/helpers/headway/functions.php');
            require($this->hw_dir . '/library/data/data-options.php');
            require($this->hw_dir . '/library/common/templates.php');
        }

        $templates = HeadwayTemplates::get_all();

        $this->render('settings', array(
            'hw'        =>  $this->hw,
            'bt'        =>  $this->bt,
            'nonce'     =>  wp_create_nonce('hwtobt_nonce'),
            'templates' =>  $templates
        ));
    }
}

$GLOBALS['hwtobt'] = new hwtobt();