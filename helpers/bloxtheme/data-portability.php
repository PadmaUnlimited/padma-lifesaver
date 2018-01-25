<?php
/**
 * Copy from /bloxtheme/library/data/data-portability.php
 */
namespace lifesaver\helpers;

class PadmaDataPortability extends \PadmaDataPortability {
    public static function export_skin(array $info) {
        global $wpdb;

        do_action('bloxtheme_before_export_skin');

        $wp_options_prefix = 'bloxtheme_|template=' . \PadmaOption::$current_skin . '|_';

        $skin = array(
            'hw-version' => HEADWAY_VERSION,
            'name' => padma_get('name', $info, 'Unnamed'),
            'author' => padma_get('author', $info),
            'image-url' => padma_get('image-url', $info),
            'version' => padma_get('version', $info),
            'data_wp_options' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE '%s'", $wp_options_prefix . '%'), ARRAY_A),
            'data_wp_postmeta' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '%s'", '_hw_|template=' . \PadmaOption::$current_skin . '|_%'), ARRAY_A),
            'data_hw_layout_meta' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->hw_layout_meta WHERE template = '%s'", \PadmaOption::$current_skin), ARRAY_A),
            'data_hw_wrappers' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->hw_wrappers WHERE template = '%s'", \PadmaOption::$current_skin), ARRAY_A),
            'data_hw_blocks' => $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->hw_blocks WHERE template = '%s'", \PadmaOption::$current_skin), ARRAY_A)
        );

        /* Spit the file out */
        $filename = 'Bloxtheme Template - ' . padma_get('name', $info, 'Unnamed');

        if ( padma_get('version', $info) ) {
            $filename .= ' ' . padma_get('version', $info);
        }

        return self::to_json($filename, 'skin', $skin);

    }

    /**
     * Convert array to JSON file and force download.
     *
     * Images will be converted to base64 via PadmaDataPortability::encode_images()
     **/
    public static function to_json($filename, $data_type = null, $array) {

        if ( !$array['data-type'] = $data_type )
            die('Missing data type for PadmaDataPortability::to_json()');

        //$array['image-definitions'] = self::encode_images($array);

        echo json_encode($array);

        return $filename;

    }
}