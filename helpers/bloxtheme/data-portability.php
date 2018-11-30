<?php
/**
 * Copy from /bloxtheme/library/data/data-portability.php
 */
namespace PadmaLifesaver\helpers;

class BloxDataPortability extends \BloxDataPortability {
    

    public static function export_skin(array $info) {

        global $wpdb;

        do_action('blox_before_export_skin');

        $wp_options_prefix = 'blox_|template=' . \BloxOption::$current_skin . '|_';

        $skin = array(
            'bt-version' => BLOX_VERSION,
            'name' => blox_get('name', $info, 'Unnamed'),
            'author' => blox_get('author', $info),
            'image-url' => blox_get('image-url', $info),
            'version' => blox_get('version', $info),
            'data_wp_options' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE '%s'", $wp_options_prefix . '%'), ARRAY_A),
            'data_wp_postmeta' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '%s'", '_bt_|template=' . \BloxOption::$current_skin . '|_%'), ARRAY_A),
            'data_bt_layout_meta' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->bt_layout_meta WHERE template = '%s'", \BloxOption::$current_skin), ARRAY_A),
            'data_bt_wrappers' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->bt_wrappers WHERE template = '%s'", \BloxOption::$current_skin), ARRAY_A),
            'data_bt_blocks' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->bt_blocks WHERE template = '%s'", \BloxOption::$current_skin), ARRAY_A)
        );

        /* Spit the file out */
        $filename = 'Blox Template - ' . blox_get('name', $info, 'Unnamed');

        if ( blox_get('version', $info) ) {
            $filename .= ' ' . blox_get('version', $info);
        }

        return self::to_json($filename, 'skin', $skin);

    }

    /**
     * Convert array to JSON file and force download.
     *
     * Images will be converted to base64 via BloxDataPortability::encode_images()
     **/
    public static function to_json($filename, $data_type = null, $array) {

        if ( !$array['data-type'] = $data_type )
            die('Missing data type for BloxDataPortability::to_json()');

        echo json_encode($array);

        return $filename;

    }
}