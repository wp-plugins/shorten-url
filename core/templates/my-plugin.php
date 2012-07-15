<?php
/**
Plugin Name: My Plugin
Plugin Tag: tag
Description: <p>The description of the plugin on this line. </p>
Version: 1.0.0
Framework: SL_Framework
Author: The name of the author
Author URI: http://www.yourdomain.com/
Author Email: youremail@yourdomain.com
Framework Email: sedlex@sedlex.fr
Plugin URI: http://wordpress.org/extend/plugins/my-plugin/
License: GPL3
*/

//Including the framework in order to make the plugin work

require_once('core.php') ; 

/** ====================================================================================================================================================
* This class has to be extended from the pluginSedLex class which is defined in the framework
*/
class my_plugin extends pluginSedLex {
	
	// Declaration of class variables (Please modify)
	var $your_var1 ; 
	var $your_var2 ; 
	var $your_varn ; 

	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;

	protected function _init() {
		global $wpdb ; 

		// Name of the plugin (Please modify)
		$this->pluginName = 'My Plugin' ; 
		
		// The structure of the SQL table if needed (for instance, 'id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)') 
		$this->tableSQL = "" ; 
		// The name of the SQL table (Do no modify except if you know what you do)
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 

		//Initilisation of plugin variables if needed (Please modify)
		$this->your_var1 = 1 ; 
		$this->your_var2 = array() ; 
		$this->your_varn = "n" ; 

		//Configuration of callbacks, shortcode, ... (Please modify)
		// For instance, see 
		//	- add_shortcode (http://codex.wordpress.org/Function_Reference/add_shortcode)
		//	- add_action 
		//		- http://codex.wordpress.org/Function_Reference/add_action
		//		- http://codex.wordpress.org/Plugin_API/Action_Reference
		//	- add_filter 
		//		- http://codex.wordpress.org/Function_Reference/add_filter
		//		- http://codex.wordpress.org/Plugin_API/Filter_Reference
		// Be aware that the second argument should be of the form of array($this,"the_function")
		// For instance add_action( "the_content",  array($this,"modify_content")) : this function will call the function 'modify_content' when the content of a post is displayed
		
		// add_action( "the_content",  array($this,"modify_content")) ; 
		
		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array($this,'uninstall_removedata'));
	}

	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		SL_Debug::log(get_class(), "Update the plugin." , 4) ; 
	}
	
	/**====================================================================================================================================================
	* Function called to return a number of notification of this plugin
	* This number will be displayed in the admin menu
	*
	* @return int the number of notifications available
	*/
	 
	public function _notify() {
		return 0 ; 
	}
	
	/** ====================================================================================================================================================
	* Init javascript for the admin side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('my_plugin_script', plugins_url('/script.js', __FILE__));</code>
	*
	* @return void
	*/
	
	function _admin_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		//$buttons[] = array(__('title', $this->pluginID), '[tag]', '[/tag]', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/img_button.png') ; 
		return $buttons ; 
	}
	
	/**====================================================================================================================================================
	* Function to instantiate the class and make it a singleton
	* This function is not supposed to be modified or called (the only call is declared at the end of this file)
	*
	* @return void
	*/
	
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* Define the default option values of the plugin
	* This function is called when the $this->get_param function do not find any value fo the given option
	* Please note that the default return value will define the type of input form: if the default return value is a: 
	* 	- string, the input form will be an input text
	*	- integer, the input form will be an input text accepting only integer
	*	- string beggining with a '*', the input form will be a textarea
	* 	- boolean, the input form will be a checkbox 
	* 
	* @param string $option the name of the option
	* @return variant of the option
	*/
	public function get_default_option($option) {
		switch ($option) {
			// Alternative default return values (Please modify)
			case 'opt1' 		: return "Default" 		; break ; 
			case 'opt2' 		: return false			; break ; 
			case 'opt3' 		: return 1				; break ; 
			case 'opt4' 		: return "*Hi everyone !"	; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function configuration_page() {
		global $wpdb;
		global $blog_id ; 
		
		SL_Debug::log(get_class(), "Print the configuration page." , 4) ; 

		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">			
			<?php
			//===============================================================================================
			// After this comment, you may modify whatever you want
			?>
			<p><?php echo __("This is the configuration page of the plugin", $this->pluginID) ;?></p>
			<?php
			
			// We check rights
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/test/", "rwx")) ) ;
			
			$tabs = new adminTabs() ; 
			
			ob_start() ; 
			
				// Examples for creating tables
				//----------------------------------
				
				echo "<h5>Tables</h5>" ; 
				$table = new adminTable() ; 
				$table->title(array("Col1", "Col2", "Col3")) ; 
				
				ob_start() ; 
				echo "Cell 1-1" ; 
				$cel1 = new adminCell(ob_get_clean()) ; 		
				ob_start() ; 
				echo "Cell 1-2" ; 
				$cel2 = new adminCell(ob_get_clean()) ; 		
				ob_start() ; 
				echo "Cell 1-3" ; 
				$cel3 = new adminCell(ob_get_clean()) ; 		
				$table->add_line(array($cel1, $cel2, $cel3), '1') ; 
				
				ob_start() ; 
				echo "Cell 2-1" ; 
				$cel1 = new adminCell(ob_get_clean()) ; 		
				ob_start() ; 
				echo "Cell 2-2" ; 
				$cel2 = new adminCell(ob_get_clean()) ; 		
				ob_start() ; 
				echo "Cell 2-3" ; 
				$cel3 = new adminCell(ob_get_clean()) ; 		
				$table->add_line(array($cel1, $cel2, $cel3), '2') ; 

				echo $table->flush() ; 
				
			$tabs->add_tab(__('Summary',  $this->pluginID), ob_get_clean()) ; 	

			ob_start() ; 
				$params = new parametersSedLex($this, "tab-parameters") ; 
				$params->add_title("Title 1") ; 
				$params->add_param('opt1', 'Modify opt1:') ; 
				$params->add_comment("This is a comment") ; 
				$params->add_param('opt2', 'Modify opt2:') ; 
				$params->add_param('opt3', 'Modify opt3:') ; 
				$params->add_comment("This is another comment") ; 
				$params->add_title("Title 2") ; 
				$params->add_param('opt4', 'Modify opt4:') ; 
				$params->flush() ; 
				
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			$frmk = new coreSLframework() ;  
			if (((is_multisite())&&($blog_id == 1))||(!is_multisite())||($frmk->get_param('global_allow_translation_by_blogs'))) {
				ob_start() ; 
					$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
					$trans = new translationSL($this->pluginID, $plugin) ; 
					$trans->enable_translation() ; 
				$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	
			}

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				// A liste of plugin slug to be excluded
				$exlude = array('wp-pirate-search') ; 
				// Replace sedLex by your own author name
				$trans = new otherPlugins("sedLex", $exlude) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			
			// Before this comment, you may modify whatever you want
			//===============================================================================================
			?>
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
}

$my_plugin = my_plugin::getInstance();

?>