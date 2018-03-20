<?php
/**
 * Copy from /headway/library/data/data-portability.php
 */
namespace PadmaLifeSaver\helpers;

class HeadwayDataPortability extends \HeadwayDataPortability {
    public static function export_skin(array $info) {
        global $wpdb;

        do_action('headway_before_export_skin');

        $wp_options_prefix = 'headway_|template=' . \HeadwayOption::$current_skin . '|_';

        $skin = array(
            'hw-version' => HEADWAY_VERSION,
            'name' => headway_get('name', $info, 'Unnamed'),
            'author' => headway_get('author', $info),
            'image-url' => headway_get('image-url', $info),
            'version' => headway_get('version', $info),
            'data_wp_options' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE '%s'", $wp_options_prefix . '%'), ARRAY_A),
            'data_wp_postmeta' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '%s'", '_hw_|template=' . \HeadwayOption::$current_skin . '|_%'), ARRAY_A),
            'data_hw_layout_meta' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->hw_layout_meta WHERE template = '%s'", \HeadwayOption::$current_skin), ARRAY_A),
            'data_hw_wrappers' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->hw_wrappers WHERE template = '%s'", \HeadwayOption::$current_skin), ARRAY_A),
            'data_hw_blocks' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->hw_blocks WHERE template = '%s'", \HeadwayOption::$current_skin), ARRAY_A)
        );

        /* Spit the file out */
        $filename = 'Headway Template - ' . headway_get('name', $info, 'Unnamed');

        if ( headway_get('version', $info) ) {
            $filename .= ' ' . headway_get('version', $info);
        }

        return self::to_json($filename, 'skin', $skin);

    }

    /**
     * Convert array to JSON file and force download.
     *
     * Images will be converted to base64 via HeadwayDataPortability::encode_images()
     **/
    public static function to_json($filename, $data_type = null, $array) {

        if ( !$array['data-type'] = $data_type )
            die('Missing data type for HeadwayDataPortability::to_json()');

        //$array['image-definitions'] = self::encode_images($array);

        echo json_encode($array);

        return $filename;

    }
}