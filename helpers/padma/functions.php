<?php
/**
 * Copied from /padma/library/common/functions.php
 */

/**
 * @todo Document
 **/
function padma_change_to_unix_path($path) {

    return str_replace('\\', '/', $path);

}