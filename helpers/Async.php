<?php

namespace PadmaLifesaver\helpers;

require_once('wp_async_task.php');

class json extends \WP_Async_Task {

    protected $action = 'lifesaver_json';

    protected function prepare_data($data) {
        $settings = array(
            'lifesaver_install_skin'   =>  1,
            'nonce'                     =>  wp_create_nonce('lifesaver_skin_nonce'),
            'include_widgets'           =>  $data[0]
        );
        return $settings;
    }

    protected function run_action() {
        do_action("wp_async_task_$this->action", $_POST['include_widgets']);
    }
}