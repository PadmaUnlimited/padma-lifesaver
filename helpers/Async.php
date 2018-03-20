<?php

namespace PadmaLifeSaver\helpers;

require_once('wp_async_task.php');

class json extends \WP_Async_Task {

    protected $action = 'life_saver_json';

    protected function prepare_data($data) {
        return array(
            'life_saver_install_skin'   =>  1,
            'nonce'                     =>  wp_create_nonce('life_saver_skin_nonce'),
            'include_widgets'           =>  $data[0]
        );
    }

    protected function run_action() {
        do_action('wp_async_' . $this->action, $_POST['include_widgets']);
    }
}