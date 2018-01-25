<?php


class padmaLifeSaver{

	private $source;
	private $source_dir;
	private $exist_source_dir;
	private $exist_padma_dir;

	function __construct(){
		
		$this->detectSource();
		$this->source_dir   		= WP_CONTENT_DIR . '/themes/' . $this->source;
		$this->padma_dir    		= WP_CONTENT_DIR . '/themes/padma';
    	$this->exist_source_dir   	= file_exists($this->source_dir);
        $this->exist_padma_dir    	= file_exists($this->padma_dir);

        if(! $this->exist_source_dir ) {
            $this->alertBox( ucfirst($this->source) . ' theme does not exist', 'danger');
        }

        if(! $this->exist_padma_dir ) {
            $this->alertBox('Padma theme does not exist','danget');
        }
	}

	/**
	 *
	 * Activation method
	 *
	 */
	public static function activation(){
	}


	/**
	 *
	 * Deactivation method
	 *
	 */
	public static function deactivation(){
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

    /**
     *
     * Get Source
     *
     */
    public function getSource(){
    	return $this->source;
    }
    

    /**
     *
     * Get templates
     *
     */
    public function getTemplates(){

    	switch ($this->source) {

        	case 'headway':
		        if( !class_exists('HeadwayTemplates')) {
		            require($this->source_dir . '/library/common/image-resizer.php');
		            require_once(dirname(__FILE__,2) . '/helpers/headway/functions.php');
		            require($this->source_dir . '/library/data/data-options.php');
		            require($this->source_dir . '/library/common/templates.php');
		        }
		        $templates = HeadwayTemplates::get_all();
        		break;

        	case 'bloxtheme':
        		if( !class_exists('BloxTemplates')) {
		            require($this->source_dir . '/library/common/image-resizer.php');
		            require_once(dirname(__FILE__,2) . '/helpers/bloxtheme/functions.php');
		            require($this->source_dir . '/library/data/data-options.php');
		            require($this->source_dir . '/library/common/templates.php');
		        }
		        $templates = BloxTemplates::get_all();
        		break;
        	
        	default:
        		return;        		
        		break;
        }

        return $templates;
    }


    /**
     *
     * Exist Source dir and Padma Dir ?
     *
     */
    public function dirValidation(){
    	if($this->exist_source_dir && $this->exist_padma_dir){
    		return true;
    	}else{
    		return;
    	}
    }
    

    

	

}