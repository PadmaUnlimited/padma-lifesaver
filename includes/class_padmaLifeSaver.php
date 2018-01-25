<?php


class padmaLifeSaver{


	function __construct(){

	}

	/**
	 *
	 * Activation method
	 *
	 */
	public function activation(){
	}


	/**
	 *
	 * Deactivation method
	 *
	 */
	public function deactivation(){
		wp_clear_scheduled_hook('padmaServices_updateStatistics');
	}

	/**
	 *
	 * Menu options page
	 *
	 */
	public function menuOptionsPage(){
		include(WP_PLUGIN_DIR.'/padma-life-saver/settings.php');
	}

	/**
	 *
	 * Alert box
	 *
	 */

	public function alertBox($message,$type='info'){
		return '<div class="padma-alert '.$type.'"><span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span>'.$message.'</div>';
	}


}