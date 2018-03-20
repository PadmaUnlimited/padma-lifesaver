<?php
/*
Lex Marion Bataller
lexmarionbataller@yahoo.com

Plugin Helper

03/17/2017 Update
*/

namespace PadmaLifeSaver\helpers;

class Plugin extends \WP_Widget {
    var $version = '';
    var $pre = '';
    var $url = '';
    var $plugin_name = '';
    var $plugin_base = '';
    var $debugging = true;
    var $domain = '';

    var $buttons = array();
    var $classes = array();
    var $menu_pages = array();
    var $option_pages = array();
    var $options = array();
    var $scripts = array();
    var $styles = array();
    var $shortcodes = array();
    var $query_vars = array();
    var $custom_pages = array();

    var $actions = array();
    var $filters = array();
    var $widget = array();

    static function toSlug($string) {
        return preg_replace('/[^a-z0-9\-]+/', '-', strtolower($string));
    }

    static function sanitize_slash($string) {
        return str_replace("\\", "/", $string);
    }

    function register_plugin($name = '', $base = '', $retain_options = false) {
        $this->url = explode("&", $_SERVER['REQUEST_URI'])[0];

        $this->domain = str_replace(array("-", "."), "_", $_SERVER['HTTP_HOST']);

        $this->plugin_name = $name;
        $this->plugin_base = self::sanitize_slash(rtrim(dirname($base), '/'));

        //load pages & admin page assets
        $actions = array(
            'admin_init'    =>  'plugin_admin_init',
            'admin_menu'    =>  'plugin_admin_menu',
            'init'          =>  'plugin_init',
            'admin_enqueue_scripts' =>  'plugin_admin_enqueue_scripts',
            'wp_enqueue_scripts'    =>  'plugin_enqueue_scripts',
            'widgets_init'  =>  'register_widget',
        );

        foreach($this->actions as $tag => $action) {
            //convert to array
            if(is_string($action)) {
                $action = array($action => false);
            } else if($action === false) {
                $action = array($action => $action);
            }

            //merge values
            foreach($actions as $t => $a) {
                if($tag == $t) {
                    if(is_array($a)) {
                        foreach($a as $func => $op) {
                            $action[$func] = $op;
                        }
                    } else {
                        $action[$a] = false;
                    }
                    unset($actions[$t]);
                }
            }

            //save changes
            $this->actions[$tag] = $action;
        }
        $this->actions = array_merge($this->actions, $actions);

        //rewrite rules
        $filters = array(
            'query_vars'   =>  array(
                'add_query_vars'    =>  array(
                    'priority'  =>  1,
                )
            ),
            'rewrite_rules_array'   =>  array(
                'add_rewrite_rules' =>  array(
                    'priority'  =>  1,
                )
            )
        );

        foreach($this->filters as $tag => $filter) {
            //convert to array
            if(is_string($filter)) {
                $filter = array($filter => false);
            } else if($filter === false) {
                $filter = array($filter => $filter);
            }

            //merge values
            foreach($filters as $t => $f) {
                if(! strcmp($tag, $t)) {
                    if(is_array($f)) {
                        foreach($f as $func => $op) {
                            $filter[$func] = $op;
                        }
                    } else {
                        $filter[$f] = false;
                    }
                    unset($filters[$t]);
                }
            }

            //save changes
            $this->filters[$tag] = $filter;
        }
        $this->filters = array_merge($this->filters, $filters);

        //set Widget params
        if(! empty($this->widget)) {
            $widget_options = array();
            if(! empty($this->widget['widget_options'])) {
                $widget_options = $this->widget['widget_options'];
            }
            $control_options = array();
            if(! empty($this->widget['control_options'])) {
                $control_options = $this->widget['control_options'];
            }
            $this->id_base = empty($this->pre) ? preg_replace( '/(wp_)?widget_/', '', strtolower(get_class($this)) ) : strtolower($this->pre);
            $this->name = $this->widget['name'];
            $this->option_name = 'widget_' . $this->id_base;
            $this->widget_options = wp_parse_args($widget_options, array('id_base' => $this->id_base));
            $this->control_options = wp_parse_args($control_options, array('id_base' => $this->id_base));
        }

        $this->initialize_actions();
        $this->initialize_filters();

        $this->initialize_classes();
        $this->initialize_options();
        $this->initialize_shortcodes();

        $this->debugging = $this->get_option("debugging") === 'true' ? true : false;

        $this->fill_options();

        if(empty($retain_options)) {
            register_deactivation_hook($base, array($this, 'deactivation_hook'));
        }

        return true;
    }

    function register_widget() {
        if(! empty($this->widget)) {
            // /wp-includes/widgets.php
            global $wp_widget_factory;
            $wp_widget_factory->widgets[$this->pre] = $this;
        }
    }

    function initialize_actions() {
        if(! empty($this->actions)) {
            foreach($this->actions as $tag => $action) {
                if (is_string($action)) {
                    $this->add_action($tag, $action);
                } else if (is_array($action)) {
                    foreach($action as $func => $opts) {
                        $priority = empty($opts['priority']) ? 10 : $opts['priority'];
                        $params = empty($opts['params']) ? 10 : $opts['params'];
                        $this->add_action($tag, $func, $priority, $params);
                    }
                } else {
                    $this->add_action($tag);
                }
            }
        }
    }

    function initialize_filters() {
        if(! empty($this->filters)) {
            foreach($this->filters as $tag => $filter) {
                if (is_string($filter)) {
                    $this->add_filter($tag, $filter);
                } else if (is_array($filter)) {
                    foreach ($filter as $func => $opts) {
                        $priority = empty($opts['priority']) ? 10 : $opts['priority'];
                        $params = empty($opts['params']) ? 1 : $opts['params'];

                        $this->add_filter($tag, $func, $priority, $params);
                    }
                } else {
                    $this->add_filter($tag);
                }
            }
        }
    }

    function plugin_init() {
        $this->add_filter("mce_external_plugins", "add_buttons");
        $this->add_filter('mce_buttons', 'register_buttons');

        $this->custom_pages();
    }

    function add_buttons($buttons) {
        foreach($this->buttons as $button => $opt) {
            $buttons[$button] = $opt['src'];
        }

        return $buttons;
    }

    function register_buttons($buttons) {
        foreach($this->buttons as $button => $opt) {
            $buttons = array_merge($buttons, $opt['buttons']);
        }

        return $buttons;
    }

    function plugin_admin_init() {
        $this->flush_rewrite_rules();

        foreach($this->options as $group => $options) {
            if(is_array($options)) {
                foreach($options as $option => $value) {
                    $this->register_setting($group, $option, $group);
                }
            }
        }
    }

    function plugin_admin_menu() {
        foreach($this->option_pages as $page => $opt) {
            $capability = 'activate_plugins';
            $func = $opt;
            $slug = self::toSlug($page);
            if(is_array($opt)) {
                if(! empty($opt['capability'])) {
                    $capability = $opt['capability'];
                }
                $func = $opt['func'];
                if(! empty($func)) {
                    add_options_page($page, $page, $capability, $slug, array($this, $func));
                }
            } else {
                add_options_page($page, $page, $capability, $slug, array($this, $func));
            }
        }

        foreach($this->menu_pages as $page => $opt) {
            $capability = 'administrator';
            $slug = self::toSlug($page);
            if(is_array($opt)) {
                if(! empty($opt['capability'])) {
                    $capability = $opt['capability'];
                }
                $icon = empty($opt['icon']) ? false : $opt['icon'];
                $position = empty($opt['position']) ? false : $opt['position'];
                if(empty($opt['func'])) {
                    $opt['func'] = $page;
                }
                add_menu_page($page, $page, $capability, $slug, array($this, $opt['func']), $icon, $position);

                if (!empty($opt['sub']) && is_array($opt['sub'])) {
                    foreach ($opt['sub'] as $sub => $op) {
                        if(! is_string($op)) {
                            $sub_capability = empty($op['capability']) ? $capability : $op['capability'];
                            if (empty($op['func'])) {
                                $op['func'] = array($this, $sub);
                            } elseif(is_string($op['func'])) {
                                $op['func'] = array($this, $op['func']);
                            }
                            add_submenu_page($slug, $sub, $sub, $sub_capability, self::toSlug($this->pre . $sub), $op['func']);
                        } else {
                            add_submenu_page($slug, '', '<span style="display:block; margin:1px 0 1px -5px; padding:0; height:1px; line-height:1px; background:#CCCCCC;"></span>', $capability, '#');
                        }
                    }
                }
            } else {
                add_menu_page($slug, $page, $capability, $slug, array($this, $opt), false, false);
            }
        }
    }

    function plugin_admin_enqueue_scripts() {
        foreach($this->scripts as $script => $value) {
            if(is_array($value) && ! strcmp($script , 'admin')) {
                foreach($value as $arr => $url) {
                    if(is_array($url)) {
                        $src = empty($url['src']) ? false : $url['src'];
                        $deps = empty($url['dependency']) ? array() : $url['dependency'];
                        if(! is_array($deps)) {
                            $deps = array($deps);
                        }
                        $ver = empty($url['version']) ? $this->version : $url['version'];
                        $in_footer = ! empty($url['footer']) && $url['footer'] === true ? true : false;
                        wp_register_script($arr, $src, $deps, $ver, $in_footer);
                    } else if(! empty($url)) {
                        wp_register_script($arr, $url, array(), $this->version);
                    }
                }
            }
        }
        foreach($this->scripts as $script => $value) {
            if(is_array($value) && $script == 'admin') {
                foreach($value as $arr => $url) {
                    wp_enqueue_script($arr);
                }
            }
        }
        foreach($this->styles as $style => $value) {
            if(is_array($value) && ! strcmp($style , 'admin')) {
                foreach($value as $arr => $url) {
                    if(is_array($url)) {
                        $src = empty($url['src']) ? false : $url['src'];
                        $deps = empty($url['dependency']) ? array() : $url['dependency'];
                        if(! is_array($deps)) {
                            $deps = array($deps);
                        }
                        $ver = empty($url['version']) ? $this->version : $url['version'];
                        wp_register_style($arr, $src, $deps, $ver);
                    } else if(! empty($url)) {
                        wp_register_style($arr, $url, array(), $this->version);
                    }
                }
            }
        }
        foreach($this->styles as $style => $value) {
            if(is_array($value) && $style == 'admin') {
                foreach($value as $arr => $url) {
                    wp_enqueue_style($arr);
                }
            }
        }

        return true;
    }

    function plugin_enqueue_scripts() {
        foreach($this->scripts as $script => $url) {
            if(is_array($url)) {
                $src = empty($url['src']) ? false : $url['src'];
                $deps = empty($url['dependency']) ? array() : $url['dependency'];
                if(! is_array($deps)) {
                    $deps = array($deps);
                }
                $ver = empty($url['version']) ? $this->version : $url['version'];
                $in_footer = (! empty($url['footer']) && $url['footer'] === true) ? true : false;
                wp_register_script($script, $src, $deps, $ver, $in_footer);
            } else if(! empty($url)) {
                wp_register_script($script, $url, array(), $this->version);
            }
        }
        foreach($this->scripts as $script => $url) {
            wp_enqueue_script($script);
        }
        foreach($this->styles as $style => $url) {
            if(is_array($url)) {
                $src = empty($url['src']) ? false : $url['src'];
                $deps = empty($url['dependency']) ? array() : $url['dependency'];
                if(! is_array($deps)) {
                    $deps = array($deps);
                }
                $ver = empty($url['version']) ? $this->version : $url['version'];
                wp_register_style($style, $src, $deps, $ver);
            } else if(! empty($url)) {
                wp_register_style($style, $url, array(), $this->version);
            }
        }
        foreach($this->styles as $style => $url) {
            wp_enqueue_style($style);
        }

        return true;
    }

    function flush_rewrite_rules() {
        global $wp_rewrite;

        $wp_rewrite->flush_rules(false);
    }


    function add_query_vars($vars) {
        foreach($this->query_vars as $shortcode => $v) {
            foreach($v as $var) {
                $vars[] = $var;
            }
        }

        return $vars;
    }

    function add_rewrite_rules($rules) {
        global $wp_rewrite;

        if(empty($this->query_vars)) {
            return $rules;
        }

        //retrieve permalink structure
        $struct = array_filter(explode("/", get_option("permalink_structure", false)));

        //retrieve all pages
        $pages = get_pages(array(
            'post_type' =>  'page',
            'post_status'   =>  'publish'
        ));
        //retrieve all posts
        $posts = get_posts(array(
            'post_type'  =>  'post',
            'post_status'   =>  'publish'
        ));

        $posts = array_merge($posts, $pages);   //merge pages & posts for easier looping

        foreach($posts as $post) {
            foreach($this->query_vars as $shortcode => $vars) {
                if (strpos($post->post_content,'[' . $shortcode) !== false) {//scan for shortcode start phrase
                    $params = array();
                    foreach($vars as $index => $var) {
                        switch($post->post_type) {
                            case "post":
                                $params[] = $var . "=" . $wp_rewrite->preg_index(count($struct) + $index);
                                break;
                            case "page":
                                $params[] = $var . "=" . $wp_rewrite->preg_index(1);
                                break;
                        }
                    }
                    $newrules = array();
                    switch($post->post_type) {
                        case "post":
                            $regex = '';

                            //build request URI regex
                            foreach($struct as $s) {
                                switch($s) {
                                    case "%postname%":
                                        $regex .= $post->post_name;
                                        break;
                                    case "%post_id%":
                                        $regex .= $post->ID;
                                        break;
                                    default:
                                        if(preg_match("%(.+)%", $s, $matches) !== FALSE) {
                                            $regex .= "(.+)";
                                        } else {
                                            $regex .= $s;
                                        }
                                        break;
                                }
                                $regex .= "/";
                            }

                            $regex .= "(.+)";

                            $newrules = array($regex => 'index.php?p=' . $post->ID . '&' . implode("&", $params));
                            break;
                        case "page":
                            $newrules = array($post->post_name . "/(.+)" => 'index.php?page_id=' . $post->ID . '&' . implode("&", $params));
                            break;
                    }

                    $rules = $newrules + $rules;
                }
            }
        }

        return $rules;
    }

    function initialize_classes() {
        //init classes here
        if (! empty($this->classes)) {
            foreach ($this->classes as $name => $class) {
                global ${$name};
                if (class_exists($class)) {
                    ${$name} = new $class(false);
                }
            }
        }

        return false;
    }

    function initialize_options() {
        //init opt values here

        foreach($this->options as $group => $options) {
            if(is_array($options)) {
                foreach($options as $option => $value) {
                    $this->add_option($option, $value);
                }
            } else {
                $this->add_option($group, $options);
            }
        }

        return true;
    }

    function fill_options() {
        //assign values

        foreach($this->options as $group => $options) {
            if(is_array($options)) {
                foreach($options as $option => $value) {
                    $this->options[$group][$option] = $this->get_option($option);
                }
            } else {
                $this->options[$group] = $this->get_option($group);
            }
        }
    }

    function deactivation_hook() {
        //delete opt values here

        foreach($this->options as $group => $options) {
            if(is_array($options)) {
                foreach($options as $option => $value) {
                    $this->delete_option($option);
                }
            } else {
                $this->delete_option($group);
            }
        }

        return true;
    }

    function initialize_shortcodes() {
        //init shortcode

        foreach($this->shortcodes as $shortcode => $func) {
            $this->add_shortcode($shortcode, $func);
        }

        return true;
    }

    function custom_pages() {
        //init custom pages

        foreach($this->custom_pages as $page => $attr) {
            $title = empty($attr['title']) ? $page : $attr['title'];

            //create post
            $data = array(
                'post_content' => $attr['content'],
                'post_name' => self::toSlug($page),
                'post_title' => $title,
                'post_status' => "publish",
                'post_type' => "page",
            );

            $page = get_page_by_title($title);

            if($page !== null && is_object($page)) {
                $data['ID'] = $page->ID;
                if($page->post_content != $data['post_content']) {
                    wp_update_post($data);
                }

            } else {
                wp_insert_post($data);
            }
        }
    }

    function add_meta_box($id, $name, $callback, $screen = 'post', $context = 'advanced', $priority = 'default', $args = array()) {
        $id = $this->pre . self::toSlug($id);

        if(is_string($callback)) {
            $callback = array($this, $callback);
        }

        add_meta_box($id, $name, $callback, $screen, $context, $priority, $args);
        return true;
    }

    function wp_add_dashboard_widget($id, $name, $callback, $control = '', $args = array()) {
        wp_add_dashboard_widget($id, $name, array($this, $callback), $control, $args );
        return true;
    }

    function register_setting($group, $name, $callback) {
        register_setting($this->pre . $group, $this->pre . $name, array($this, $callback));
        return true;
    }

    function add_option($name = '', $value = '') {
        if (add_option($this->pre . $name, $value)) {
            return true;
        }

        return false;
    }

    function update_option($name = '', $value = '') {
        if (update_option($this->pre . $name, $value)) {
            return true;
        }

        return false;
    }

    function get_option($name = '', $stripslashes = true) {
        if ($option = get_option($this->pre . $name)) {
            if (@unserialize($option) !== false) {
                return unserialize($option);
            }

            if ($stripslashes == true) {
                $option = stripslashes_deep($option);
            }

            return $option;
        }

        return false;
    }

    function delete_option($name = '') {
        if(delete_option($this->pre . $name)) {
            return true;
        }

        return false;
    }

    function debug($var = array()) {
        if ($this->debugging === true) {
            echo '<pre>' . print_r($var, true) . '</pre>';
            return true;
        }

        return false;
    }

    function url() {
        $url = get_option('siteurl') . substr($this->plugin_base, strlen(realpath(ABSPATH)));
        return $url;
    }

    function add_action($action, $function = '', $priority = 10, $params = 1) {
        if(empty($function)) {
            $function = array($this, $action);
        } elseif('__return_true' != $function && '__return_false' != $function) {
            $function = array($this, $function);
        }
        if(add_action($action, $function, $priority, $params)) {
            return true;
        }

        return false;
    }

    function remove_action($action, $function = '', $priority = 10) {
        if(remove_action($action, array($this, (empty($function)) ? $action : $function), $priority)) {
            return true;
        }

        return false;
    }

    function add_filter($filter, $function = '', $priority = 10, $params = 1) {
        $returns = array(
            '__return_true',
            '__return_false',
            '__return_zero',
            '__return_null',
            '__return_empty_string',
            '__return_empty_array',
        );
        if(empty($function)) {
            $function = array($this, $filter);
        } elseif(! in_array($function, $returns)) {
            $function = array($this, $function);
        }
        if(add_filter($filter, $function, $priority, $params)) {
            return true;
        }

        return false;
    }

    function remove_filter($filter, $function = '', $priority = 10) {
        if(remove_filter($filter, array($this, (empty($function)) ? $filter : $function), $priority)) {
            return true;
        }

        return false;
    }

    function add_shortcode($name, $method) {
        add_shortcode($name, array($this, $method));
        return true;
    }

    function remove_shortcode($name) {
        remove_shortcode($name);
        return true;
    }

    function redirect($location = '', $msgtype = '', $message = '') {
        $url = $location;

        if ($msgtype == "message") {
            $url .= '&' . $this->pre . 'updated=true';
        } elseif ($msgtype == "error") {
            $url .= '&' . $this->pre . 'error=true';
        }

        if (!empty($message)) {
            $url .= '&' . $this->pre . 'message=' . urlencode($message);
        }

        ?>

        <script type="text/javascript">
            window.location = '<?php echo (empty($url)) ? get_option('home') : $url; ?>';
        </script>

        <?php

        flush();
        exit();
    }

    function alert_redirect($location = '', $alert = '') {
        $url = (empty($location)) ? get_option('home') : $location;

        if (!empty($alert)) {
            ?>
            <script type="text/javascript">
                alert('<?php echo $alert; ?>');
            </script>

            <?php
        }

        $this->redirect($url);
        return true;
    }

    function render_msg($message = '') {
        $this->render('msg-top', array('message' => $message), true, 'admin');
    }

    function render_err($message = '') {
        $this->render('err-top', array('message' => $message), true, 'admin');
    }

    function render($file = '', $params = array(), $output = true, $folder = 'admin') {
        if (!empty($file)) {
            $filename = $file . '.php';
            $filepath = $this->plugin_base . '/views/' . $folder . '/';
            $filefull = $filepath . $filename;

            if (file_exists($filefull)) {
                if ($output === false) {
                    ob_start();
                }

                if (!empty($this->classes)) {
                    foreach ($this->classes as $name => $class) {
                        global ${$name};
                    }
                }

                if (!empty($params) && is_array($params)) {
                    foreach ($params as $pkey => $pval) {
                        ${$pkey} = $pval;
                    }
                }

                include($filefull);

                if ($output === false) {
                    $data = ob_get_clean();
                    return $data;
                } else {
                    return true;
                }
            } else {
                $message = 'File "/views/' . $folder . '/' . $filename . '" does not exist!';
                $this->render_err($message);
            }
        }

        return false;
    }

    function delete($path) {
        if(is_dir($path)) {
            $files = array_slice(scandir($path), 2);

            foreach($files as $file) {
                $this->delete($path . '/' . $file);
            }

            rmdir($path);
        } else {
            unlink($path);
        }
    }

    function fdebug($var) {
        echo "Debug Start<pre>";
        var_dump($var);
        echo "</pre>Debug End";
    }

    function jdebug($var) {
        echo '<script type="text/javascript">console.log("' . preg_replace('/\"/', '\"',json_encode($var, true)) . '");</script>';
    }
}

?>