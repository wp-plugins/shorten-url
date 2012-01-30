<?php
/**
* Core SedLex Plugin
* VersionInclude : 3.0
*/ 

/* Prevent direct access to this file */
if (!defined('ABSPATH')) {
	exit("Sorry, you are not allowed to access this file directly.");
}

if (!class_exists('pluginSedLex')) {

	$sedlex_list_scripts = array() ; 
	$sedlex_list_styles = array() ; 
	
	/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
	* This PHP class aims at simplifying the developement of new plugin for Wordpress and especially if you do not know how to develop it.
	* Therefore, your plugin class should inherit from this class. Please refer to the HOW TO manual to learn more.
	* 
	* @abstract
	*/
	abstract class pluginSedLex {
	 
		protected $pluginID = '';
		protected $pluginName = '';
		protected $signature = '';
		protected $tableSQL = '' ; 
				
		/** ====================================================================================================================================================
		 * This is our constructor, which is private to force the use of getInstance()
		 *
		 * @return void
		 */
		protected function __construct() {
			if ( is_callable( array($this, '_init') ) ) {
				$this->_init();
			}
			add_action('admin_menu',  array( $this, 'admin_menu'));
			add_filter('plugin_row_meta', array( $this, 'plugin_actions'), 10, 2);
			add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
			add_action('init', array( $this, 'init_textdomain'));
			
			add_action('wp_print_scripts', array( $this, 'javascript_front'), 5);
			add_action('wp_print_styles', array( $this, 'css_front'), 5);
			add_action('wp_print_scripts', array( $this, 'flush_js'), 10000000);
			add_action('wp_print_styles', array( $this, 'flush_css'), 10000000);
			
			// We add an ajax call for the translation classe
			add_action('wp_ajax_translate_add', array('translationSL','translate_add')) ; 
			add_action('wp_ajax_translate_modify', array('translationSL','translate_modify')) ; 
			add_action('wp_ajax_translate_create', array('translationSL','translate_create')) ; 
			add_action('wp_ajax_send_translation', array('translationSL','send_translation')) ; 
			add_action('wp_ajax_update_summary', array('translationSL','update_summary')) ; 
			add_action('wp_ajax_download_translation', array('translationSL','download_translation')) ; 
			add_action('wp_ajax_set_translation', array('translationSL','set_translation')) ; 
			add_action('wp_ajax_update_languages_wp_init', array('translationSL','update_languages_wp_init')) ; 
			add_action('wp_ajax_update_languages_wp_list', array('translationSL','update_languages_wp_list')) ; 
			add_filter('locale', array('translationSL', 'set_locale'), 9999);
		
			
			// We add an ajax call for the feedback classe
			add_action('wp_ajax_send_feedback', array('feedbackSL','send_feedback')) ; 
			
			// We add an ajax call for SVN
			add_action('wp_ajax_svn_show_popup', array($this,'svn_show_popup')) ; 
			add_action('wp_ajax_svn_get_file', array($this,'svn_get_file')) ; 
			add_action('wp_ajax_svn_compare_localRepo', array($this,'svn_compare_localRepo')) ; 
			add_action('wp_ajax_svn_compare_repoLocal', array($this,'svn_compare_repoLocal')) ; 
			add_action('wp_ajax_svn_to_repo', array($this,'svn_to_repo')) ; 
			add_action('wp_ajax_svn_to_local', array($this,'svn_to_local')) ; 
			add_action('wp_ajax_svn_put_file_in_repo', array($this,'svn_put_file_in_repo')) ; 
			add_action('wp_ajax_svn_merge', array($this,'svn_merge')) ; 
			add_action('wp_ajax_svn_put_folder_in_repo', array($this,'svn_put_folder_in_repo')) ; 
			add_action('wp_ajax_svn_delete_in_repo', array($this,'svn_delete_in_repo')) ; 
			add_action('wp_ajax_svn_changeVersionReadme', array($this,'svn_changeVersionReadme')) ; 
			add_action('wp_ajax_svn_saveVersionReadme', array($this,'svn_saveVersionReadme')) ; 
			add_action('wp_ajax_svn_viewDifferences', array($this,'svn_viewDifferences')) ; 
												
			// We add ajax call for enhancing the performance of the information page
			add_action('wp_ajax_pluginInfo', array($this,'pluginInfo')) ; 
			add_action('wp_ajax_coreInfo', array($this,'coreInfo')) ; 
			add_action('wp_ajax_coreUpdate', array($this,'coreUpdate')) ; 
			

			// We remove some functionnalities
			remove_action('wp_head', 'feed_links_extra', 3); // Displays the links to the extra feeds such as category feeds
			remove_action('wp_head', 'feed_links', 2); // Displays the links to the general feeds: Post and Comment Feed
			remove_action('wp_head', 'rsd_link'); // Displays the link to the Really Simple Discovery service endpoint, EditURI link
			remove_action('wp_head', 'wlwmanifest_link'); // Displays the link to the Windows Live Writer manifest file.
			remove_action('wp_head', 'index_rel_link'); // index link
			remove_action('wp_head', 'parent_post_rel_link'); // prev link
			remove_action('wp_head', 'start_post_rel_link'); // start link
			remove_action('wp_head', 'adjacent_posts_rel_link_wp_head'); // Displays relational links for the posts adjacent to the current post.
			remove_action('wp_head', 'wp_generator'); // Displays the XHTML generator that is generated on the wp_head hook, WP version
			//remove_action( 'wp_head', 'wp_shortlink_wp_head');
			
			$this->signature = '<p style="text-align:right;font-size:75%;">&copy; SedLex - <a href="http://www.sedlex.fr/">http://www.sedlex.fr/</a></p>' ; 
			
			$this->frmk = new coreSLframework() ;
		}
		
		/** ====================================================================================================================================================
		* In order to install the plugin, few things are to be done ...
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you activate the plugin
		*  
		* If you have to do some stuff when the plgin is activated (such as update the database format), please create an _update function in your plugin
		* 
		* @access private
		* @see subclass::_update 
		* @see pluginSedLex::uninstall
		* @return void
		*/
		public function install () {
			global $wpdb;
			global $db_version;
		
			$table_name = $wpdb->prefix . $this->pluginID;
			
			if (strlen(trim($this->tableSQL))>0) {
				if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
					$sql = "CREATE TABLE " . $table_name . " (".$this->tableSQL. ") DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
			
					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
					dbDelta($sql);
			
					add_option("db_version", $db_version);
					
					// Gestion de l'erreur
					ob_start() ; 
					$wpdb->print_error();
					$result = ob_get_clean() ; 
					if (strlen($result)>0) {
						echo $result ; 
						die() ; 
					}
				}
			}
			if (method_exists($this,'_update')) {
				$this->_update() ; 
			}
		}

		/** ====================================================================================================================================================
		* Get the plugin ID
		* 
		* @return string the plugin ID string. the string will be empty if it is not a plugin (i.e. the framework)
		*/
		public function getPluginID () {
			$tmp = $this->pluginID ; 
			if ($tmp=="coreSLframework") 
				return "" ; 
			return $tmp ; 
		}
	
		/** ====================================================================================================================================================
		* In order to uninstall the plugin, few things are to be done ... 
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you de-activate the plugin
		* 
		* For now the function does nothing (but have to be declared)
		* 
		* @access private
		* @see pluginSedLex::install
		* @return void
		*/
		public function uninstall () {
			//Nothing to do
		}
		
		/** ====================================================================================================================================================
		* Get the value of an option of the plugin
		* 
		* For instance: <code> echo $this->get_param('opt1') </code> will return the value of the option 'opt1' stored for this plugin. Please note that two different plugins may have options with the same name without any conflict.
		*
		* @see  pluginSedLex::set_param
		* @see parametersSedLex::parametersSedLex
		* @param string $option the name of the option
		* @return mixed  the value of the option requested
		*/
		public function get_param($option) {
			$options = get_option($this->pluginID.'_options');
			if (!isset($options[$option])) {
				$options[$option] = str_replace("*","",$this->get_default_option($option)) ; 
			}
			
			update_option($this->pluginID.'_options', $options);
			return $options[$option] ;
		}
		
		/** ====================================================================================================================================================
		* Set the option of the plugin
		*
		* For instance, <code>$this->set_param('opt1', 'val1')</code> will store the string 'val1' for the option 'opt1'. Any object may be stored in the options
		* 
		* @see  pluginSedLex::get_param
		* @see parametersSedLex::parametersSedLex
		* @param string $option the name of the option
		* @param mixed $value the value of the option to be saved
		* @return void
		*/
		public function set_param($option, $value) {
			$options = get_option($this->pluginID.'_options');
			$options[$option] = $value ; 
			update_option($this->pluginID.'_options', $options);
		}
		
		/** ====================================================================================================================================================
		* Create the menu & submenu in the admin section
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you de-activate the plugin
		* 
		* @access private
		* @return void
		*/
		public function admin_menu() {   
		
			global $menu;
			
			$tmp = explode('/',plugin_basename($this->path)) ; 
			$plugin = $tmp[0]."/".$tmp[0].".php" ; 
			$topLevel = "sedlex.php" ; 
			
			// Fait en sorte qu'il n'y ait qu'un seul niveau 1 pour l'ensemble des plugins que j'ai redige
			foreach ($menu as $i) {
				$key = array_search($topLevel, $i);
				if ($key != '') {
					$menu_added = true;
				}
			}
			if ($menu_added) {
				// Nothing ... because menu is already added
				} else {
				//add main menu
				add_object_page('SL Plugins', 'SL Plugins', 10, $topLevel, array($this,'sedlex_information'));
				$page = add_submenu_page($topLevel, __('About...', 'SL_framework'), __('About...', 'SL_framework'), 10, $topLevel, array($this,'sedlex_information'));

				add_action('admin_print_scripts-'.$page, array($this,'javascript_admin_always'),5);
				add_action('admin_print_styles-'.$page, array($this,'css_admin_always'),5);
				
				add_action('admin_print_scripts-'.$page, array( $this, 'flush_js'), 10000000);
				add_action('admin_print_styles-'.$page, array( $this, 'flush_css'), 10000000);
			}
		
			//add sub menus
			$number = "" ; 
			if (method_exists($this,'_notify')) {
				$number = $this->_notify() ; 
				if (is_numeric($number)) {
					if ($number>0) {
						$number = "<span class='update-plugins count-1' title='title'><span class='update-count'>".$number."</span></span>" ; 
					} else {
						$number = "" ; 
					}
				} else {
					$number = "" ; 
				}
			}
			$page = add_submenu_page($topLevel, $this->pluginName, $this->pluginName . $number, 10, $plugin, array($this,'configuration_page'));
			
			// Different actions

			
			add_action('admin_print_scripts-'.$page, array($this,'javascript_admin'));
			add_action('admin_print_styles-'.$page, array($this,'css_admin'));
			
			if (method_exists($this,'_admin_js_load')) {
				add_action('admin_print_scripts-'.$page, array($this,'_admin_js_load'));
			}
			
			add_action('admin_print_scripts-'.$page, array($this,'javascript_admin_always'),5);
			add_action('admin_print_styles-'.$page, array($this,'css_admin_always'),5);
			
			add_action('admin_print_scripts-'.$page, array( $this, 'flush_js'), 10000000);
			add_action('admin_print_styles-'.$page, array( $this, 'flush_css'), 10000000);


		}
		
		/** ====================================================================================================================================================
		* Add a link in the new link along with the standard activate/deactivate and edit in the plugin admin page.
		* This function is not supposed to be called from your plugin : it is a purely internal function 
		* 
		* @access private
		* @param array $links links such as activate/deactivate and edit
		* @param string $file the related file of the plugin 
		* @return array of new links set with a Settings link added
		*/
		public function plugin_actions($links, $file) { 
			$tmp = explode('/',plugin_basename($this->path)) ; 
			$plugin = $tmp[0]."/".$tmp[0].".php" ; 
			if ($file == $plugin) {
				return array_merge(
					$links,
					array( '<a href="admin.php?page='.$plugin.'">'. __('Settings', 'SL_framework') .'</a>')
				);
			}
			return $links;
		}
		
		/** ====================================================================================================================================================
		* Handler for the 'plugin_action_links' hook. Adds a "Settings" link to this plugin's entry
		* on the plugin list.
		*
		* @access private
		* @param array $links
		* @param string $file
		* @return array
		*/
		function plugin_action_links($links, $file) {
			$tmp = explode('/',plugin_basename($this->path)) ; 
			$plugin = $tmp[0]."/".$tmp[0].".php" ; 
			if ($file == $plugin) {
				return array_merge(
					$links,
					array( '<a href="admin.php?page='.$plugin.'">'. __('Settings', 'SL_framework') .'</a>')
				);
			}
			return $links;
		}
		
		
		/** ====================================================================================================================================================
		* Translate the plugin with international settings
		* This function is not supposed to be called from your plugin : it is a purely internal function
		*
		* In order to enable translation, please add .mo and .po files in the /lang folder of the plugin
		*		
		* @access private
		* @return void
		*/
		public function init_textdomain() {
			load_plugin_textdomain($this->pluginID, false, dirname( plugin_basename( $this->path ) ). '/lang/') ;
			load_plugin_textdomain('SL_framework', false, dirname( plugin_basename( $this->path ) ). '/core/lang/') ;
		}
		
		/** ====================================================================================================================================================
		* Add a javascript file in the header
		* 
		* For instance, <code> $this->add_js('http://www.monserveur.com/wp-content/plugins/my_plugin/js/foo.js') ; </code> will add the 'my_plugin/js/foo.js' in the header.
		* In order to save bandwidth and boost your website, the framework will concat all the added javascript (by this function) and serve the browser with a single js file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_scripts', array( $this, 'your_function'));</code>
		*
		* @param string $url the complete http url of the javascript (this javascript should be an internal javascript i.e. stored by your blog and not, for instance, stored by Google) 
		* @see pluginSedLex::add_inline_js
		* @see pluginSedLex::flush_js
		* @return void
		*/
		
		public function add_js($url) {
			global $sedlex_list_scripts ; 
			$id = str_replace("/",'__',str_replace(WP_PLUGIN_URL."/",'',str_replace('.js','',$url))) ; 
			$sedlex_list_scripts[] = $id ; 
		}
		
		/** ====================================================================================================================================================
		* Add inline javascript in the header
		* 
		* For instance <code> $this->add_inline_js('alert("foo");') ; </code>
		* In order to save bandwidth and boost your website, the framework will concat all the added javascript (by this function) and serve the browser with a single js file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_scripts', array( $this, 'your_function'));</code>
		*
		* @param string $text the javascript to be inserted in the header (without any <script> tags)
		* @see pluginSedLex::add_js
		* @see pluginSedLex::flush_js
		* @return void
		*/
		
		public function add_inline_js($text) {
			global $sedlex_list_scripts ; 
			$id = md5($text) ; 
			// Repertoire de stockage des css inlines
			$path =  WP_CONTENT_DIR."/sedlex/inline_scripts";
			$path_ok = false ; 
			if (!is_dir($path)) {
				if (mkdir("$path", 0755, true)) {
					$path_ok = true ; 				
				}
			} else {
				$path_ok = true ; 
			}
			
			// On cree le machin
			if ($path_ok) {
				$css_f = $path."/".$id.'.js' ; 
				if (!is_file($css_f)) {
					$mf = fopen($css_f , 'w+');
					fputs($mf, $text); 
					fclose($mf) ;
				}
				$sedlex_list_scripts[] = $id ; 
			} else {
				echo "\n<script type='text/javascript'>\n" ; 
				echo $text ; 
				echo "\n</script>\n" ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the  'single' javascript file in the page
		* This function is not supposed to be called from your plugin. This function is called automatically once during the rendering
		* 
		* @access private
		* @see pluginSedLex::add_inline_js
		* @see pluginSedLex::add_js
		* @return void
		*/
		
		public  function flush_js() {
			global $sedlex_list_scripts ; 
			if (!empty($sedlex_list_scripts)) {
				$list = implode(',',$sedlex_list_scripts) ; 
				$url = WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)).'core/load-scripts.php?c=0&load='.$list ; 
				wp_enqueue_script('sedlex_scripts', $url, array() ,date('Ymd'));
				$sedlex_list_scripts = array(); 
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the  admin javascript file which is located in js/js_admin.js (you may modify this file in order to customize the rendering) 
		* This function is not supposed to be called from your plugin. This function is called automatically when you are in the admin page of the plugin
		* 
		* @access private
		* @return void
		*/
		
		public function javascript_admin() {
			$name = 'js/js_admin.js' ; 
			$url = WP_PLUGIN_URL.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_js($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the  admin javascript files which is located in the core (you may NOT modify these files) 
		* This function is not supposed to be called from your plugin. This function is called automatically when you are in the admin page of the plugin
		* 
		* @access private
		* @return void
		*/
		
		public function javascript_admin_always() {
			// For the tabs of the admin page
			wp_enqueue_script('jquery');   
			wp_enqueue_script('jquery-ui-core');   
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-tabs');
			
			echo '<script> addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!=\'function\'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};</script>' ; 
		
			@chmod(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/', 0755);
			
			$dir = @opendir(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'); 
			if ($dir !== false) {
				while($file = readdir($dir)) {
					if (preg_match('@\.js$@i',$file)) {
						$path = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'.$file ; 
						$url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'.$file ; 
						if (@filesize($path)>0) {
							$this->add_js($url) ; 
						}				
					}
				}
			}

		}
				
		/** ====================================================================================================================================================
		* Insert the  admin javascript file which is located in js/js_front.js (you may modify this file in order to customize the rendering) 
		* This function is not supposed to be called from your plugin. This function is called automatically.
		* 
		* @access private
		* @return void
		*/
		
		public function javascript_front() {
			$name = 'js/js_front.js' ; 
			$url = WP_PLUGIN_URL.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_js($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* Add a CSS file in the header
		* 
		* For instance,  <code>$this->add_css('http://www.monserveur.com/wp-content/plugins/my_plugin/js/foo.css') ;</code> will add the 'my_plugin/js/foo.css' in the header.
		* In order to save bandwidth and boost your website, the framework will concat all the added css (by this function) and serve the browser with a single css file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_styles', array( $this, 'your_function'));</code>
		*
		* @param string $url the complete http url of the css file (this css should be an internal javascript i.e. stored by your blog and not, for instance, stored by Google) 
		* @see pluginSedLex::add_inline_css
		* @see pluginSedLex::flush_css
		* @return void
		*/
		
		
		public function add_css($url) {
			global $sedlex_list_styles ; 
			$id = str_replace("/",'__',str_replace(WP_PLUGIN_URL."/",'',str_replace('.css','',$url))) ; 
			$sedlex_list_styles[] = $id ; 
		}
		
		/** ====================================================================================================================================================
		* Add inline CSS in the header
		*
		* For instance,  <code> $this->add_inline_css('.head { color:#FFFFFF; }') ; </code>
		* In order to save bandwidth and boost your website, the framework will concat all the added css (by this function) and serve the browser with a single css file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_styles', array( $this, 'your_function'));</code>
		*
		* @param string $text the css to be inserted in the header (without any <style> tags)
		* @see pluginSedLex::add_css
		* @see pluginSedLex::flush_css
		* @return void
		*/
		
		public function add_inline_css($text) {
			global $sedlex_list_styles ; 
			$id = md5($text) ; 
			// Repertoire de stockage des css inlines
			$path =  WP_CONTENT_DIR."/sedlex/inline_styles";
			$path_ok = false ; 
			if (!is_dir($path)) {
				if (mkdir("$path", 0755, true)) {
					$path_ok = true ; 				
				}
			} else {
				$path_ok = true ; 
			}
			
			// On cree le machin
			if ($path_ok) {
				$css_f = $path."/".$id.'.css' ; 
				if (!is_file($css_f)) {
					$mf = fopen($css_f , 'w+');
					fputs($mf, $text); 
					fclose($mf) ;
				}
				$sedlex_list_styles[] = $id ; 
			} else {
				echo "\n<style type='text/css'>\n" ; 
				echo $text ; 
				echo "\n</style>\n" ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the 'single' css file in the page
		* This function is not supposed to be called from your plugin. This function is called automatically once during the rendering
		* 
		* @access private
		* @see pluginSedLex::add_inline_css
		* @see pluginSedLex::add_css
		* @return void
		*/
		
		public function flush_css() {
			global $sedlex_list_styles ; 
			
			if (!empty($sedlex_list_styles)) {
				$list = implode(',',$sedlex_list_styles) ; 
				$url = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename( __FILE__)).'core/load-styles.php?c=0&load='.$list ; 
				wp_enqueue_style('sedlex_styles', $url, array() ,date('Ymd'));
				$sedlex_list_styles = array(); 
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the  admin css file which is located in css/css_admin.css (you may modify this file in order to customize the rendering) 
		* This function is not supposed to be called from your plugin. This function is called automatically when you are in the admin page of the plugin
		* 
		* @access private
		* @return void
		*/
		
		public function css_admin() {
		
			$name = 'css/css_admin.css' ; 
			$url = WP_PLUGIN_URL.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_css($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the  admin css files which is located in the core (you may NOT modify these files) 
		* This function is not supposed to be called from your plugin. This function is called automatically when you are in the admin page of the plugin
		* 
		* @access private
		* @return void
		*/
		
		public function css_admin_always() {
			wp_enqueue_style('wp-admin');
			wp_enqueue_style('dashboard');
			wp_enqueue_style('plugin-install');
			
			@chmod(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/', 0755);
			$dir = @opendir(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'); 
			if ($dir!==false) {
				while($file = readdir($dir)) {
					if (preg_match('@\.css$@i',$file)) {
						$path = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'.$file ; 
						$url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'.$file ; 
						if (@filesize($path)>0) {
							$this->add_css($url) ; 
						}			
					}
				}
			}
		}

		
		
		/** ====================================================================================================================================================
		* Insert the  admin css file which is located in css/css_front.css (you may modify this file in order to customize the rendering) 
		* This function is not supposed to be called from your plugin. This function is called automatically.
		* 
		* @access private
		* @return void
		*/
		
		function css_front() {
			$name = 'css/css_front.css' ; 
			$url = WP_PLUGIN_URL.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_css($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* This function displays the configuration page of the core 
		* 
		* @access private
		* @return void
		*/
		function sedlex_information() {
			global $submenu;
			
			ob_start() ; 
			$params = new parametersSedLex ($this->frmk) ;
			$params->add_title (__('Advanced options','SL_framework')) ; 
			$params->add_param ("adv_param", __('Show the advanced options:','SL_framework'), "", "", array('adv_svn_login', 'adv_svn_pwd', 'adv_svn_author')) ; 
			$params->add_comment (__('Will display additionnal information on the plugin (including SVN features). Recommended for developpers which develop plugins with this framework.','SL_framework')) ; 
			$params->add_param ("adv_doc", __('Show the developpers documentation:','SL_framework')) ; 
			$params->add_comment (sprintf(__('You should register a new wordpress plugin first on %s.','SL_framework'),"<a href='http://wordpress.org/extend/plugins/add/'>Wordpress.org</a>")) ; 
			$params->add_param ("adv_svn_login", __('What is your SVN Login:','SL_framework')) ; 
			$params->add_comment (sprintf(__('You should have an account on %s before. Thus, the login will be the same!','SL_framework'),"<a href='http://wordpress.org/'>Wordpress.org</a>")) ; 
			$params->add_param ("adv_svn_pwd", __('What is your SVN Password:','SL_framework')) ; 
			$params->add_comment (__('Same comment as above...','SL_framework')) ; 
			$params->add_param ("adv_svn_author", __('What is your Author Name:','SL_framework')) ; 
			$params->add_comment (__('Your author name is the name that is displayed in your plugin.','SL_framework')) ; 
			$params->add_param ("adv_update_trans", __('Do you want to udate the translations files when the plugin page is called:','SL_framework')) ; 
			$params->add_comment (__('This is useful if you develop a plugin, thus you will see when new sentences need to be translated.','SL_framework')) ; 
			
			echo $params->flush() ; 
			$paramSave = ob_get_clean() ; 

			

			if (isset($_GET['download'])) {
				$this->getPluginZip($_GET['download']) ; 
			}
			echo "<a name='top'></a>" ; 
			$current_core_used = str_replace(WP_PLUGIN_DIR."/",'',dirname(__FILE__)) ; 
			
			if ($this->frmk->get_param('adv_param')){
				$current_fingerprint_core_used = $this->checkCoreOfThePlugin(WP_PLUGIN_DIR."/".$current_core_used."/core.php") ; 
			}
						
			//Information about the SL plugins
			?>
				<div class="wrap">
					<div id="icon-themes" class="icon32"><br/></div>
					<h2><?php echo __('Summary page for the plugins developped with the SL framework', 'SL_framework')?></h2>
				</div>
				<div style="padding:20px;">
					<?php echo $this->signature; 
					echo '<p style="text-align:right;font-size:75%;">'.__('The core file used for the SedLex plugins is:', 'SL_framework')." <b>".$current_core_used.'</b></p>' ; 
					?>
					<p>&nbsp;</p>
					<?php
					
					$plugins = get_plugins() ; 
					$sl_count = 0 ; 
					foreach ($submenu['sedlex.php'] as $ov) {
						$sl_count ++ ; 
					}
?>
					<p><?php printf(__("For now, you have installed %d  plugins including %d plugins developped with the 'SL framework':",'SL_framework'), count($plugins), $sl_count)?><p/>
<?php
					
					//======================================================================================
					//= Tab listing all the plugins
					//======================================================================================
			
					$tabs = new adminTabs() ; 
										
					ob_start() ; 
						$table = new adminTable() ; 
						if ($this->frmk->get_param('adv_param')){
							$table->title(array(__("Plugin name", 'SL_framework'), __("Description", 'SL_framework'), __("Status of the core", 'SL_framework'))) ; 
						} else {
							$table->title(array(__("Plugin name", 'SL_framework'), __("Description", 'SL_framework'))) ; 
						}

						foreach ($submenu['sedlex.php'] as $i => $ov) {

							$ligne++ ; 

							$url = $ov[2] ; 
							$plugin_name = explode("/",$url) ;
							$plugin_name = $plugin_name[count($plugin_name)-2] ; 
							
							if ($i != 0) {
								$info = $this->get_plugins_data(WP_PLUGIN_DIR."/".$url);
								ob_start() ; 
								?>
									<p><b><?php echo $info['Plugin_Name'] ; ?></b></p>
									<p><a href='admin.php?page=<?php echo $url  ; ?>'><?php echo __('Settings', 'SL_framework') ; ?></a> | <?php echo Utils::byteSize(Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>

								<?php
								
									if ($this->frmk->get_param('adv_param')){
										echo "<div id='infoPlugin_".md5($url)."'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif'></div><script>pluginInfo('infoPlugin_".md5($url)."', '".$url."', '".$plugin_name."'); </script>" ; 
									}
									

								$cel1 = new adminCell(ob_get_clean()) ; 
								
								ob_start() ; 
									?>
									<p><?php echo str_replace("<ul>", "<ul style='list-style-type:circle; padding-left:1cm;'>", $info['Description']) ; ?></p>
									<p><?php echo sprintf(__('Version: %s by %s', 'SL_framework'),$info['Version'],$info['Author']) ; ?> (<a href='<?php echo $info['Author_URI'] ; ?>'><?php echo $info['Author_URI'] ; ?></a>)</p>
									<?php
								$cel2 = new adminCell(ob_get_clean()) ; 
								
								if ($this->frmk->get_param('adv_param')){
									ob_start() ; 
									echo "<div id='corePlugin_".md5($url)."'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif'></div><script>coreInfo('corePlugin_".md5($url)."', '".$url."', '".$plugin_name."', '".$current_core_used."', '".md5($current_fingerprint_core_used)."', '".$info['Author']."'); </script>" ;
									$cel3 = new adminCell( ob_get_clean() ) ; 
								}
								
								if ($this->frmk->get_param('adv_param')){
									$table->add_line(array($cel1, $cel2, $cel3), '1') ; 
								} else {
									$table->add_line(array($cel1, $cel2), '1') ; 
								}
							}
						}
						echo $table->flush() ; 
					$tabs->add_tab(__('List of SL plugins',  'SL_framework'), ob_get_clean(), WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_list.png" ) ; 
					
					//======================================================================================
					//= Tab for parameters
					//======================================================================================
													
					$tabs->add_tab(__('Parameters of the framework',  'SL_framework'),  $paramSave, WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 					
					
					if ($this->frmk->get_param('adv_doc')==true) {
						//======================================================================================
						//= Tab with a zip file for downloading an empty plugin with a quick tuto
						//======================================================================================
						ob_start() ; 
						?>
			
						<div class="adminPost">
						
						<p><?php echo __("The following description is a quick tutorial on about how to create a plugin with the SL framework. (Please note that the following description is in English for developpers, sorry for this inconvenience)",'SL_framework') ; ?></p>
						<p>&nbsp;</p>
						<div class="toc tableofcontent">
						<h6>Table of content</h6>
						<p style="text-indent: 0cm;"><a href="#Download_the_laquonbspemptynbspraquo_plugin">Download the "&nbsp;empty&nbsp;" plugin</a></p>
						<p style="text-indent: 0cm;"><a href="#The_structure_of_the_folder_of_the_plugin">The structure of the folder of the plugin</a></p>
						<p style="text-indent: 0.5cm;"><a href="#The_laquonbspmy-pluginphpnbspraquo_file">The "&nbsp;my-plugin.php&nbsp;" file</a></p>

						<p style="text-indent: 0.5cm;"><a href="#The_laquonbspcssnbspraquo_folder">The "&nbsp;css&nbsp;" folder</a></p>
						<p style="text-indent: 0.5cm;"><a href="#The_laquonbspjsnbspraquo_folder">The "&nbsp;js&nbsp;" folder</a></p>
						<p style="text-indent: 0.5cm;"><a href="#The_laquonbspimgnbspraquo_folder">The "&nbsp;img&nbsp;" folder</a></p>
						<p style="text-indent: 0.5cm;"><a href="#The_laquonbsplangnbspraquo_folder">The "&nbsp;lang&nbsp;" folder</a></p>
						<p style="text-indent: 0.5cm;"><a href="#The_laquonbspcorenbspraquo_folder_and_laquonbspcorephpnbspraquo_file">The "&nbsp;core&nbsp;" folder and "&nbsp;core.php&nbsp;" file</a></p>

						<p style="text-indent: 0cm;"><a href="#How_to_start_">How to start ?</a></p>
						</div>
						<div class="tableofcontent-end"></div>
						<h2 id="Download_the_laquonbspemptynbspraquo_plugin">Download the "&nbsp;empty&nbsp;" plugin</h2>
						<p>Please specify the name of the plugin (For instance "&nbsp;My Plugin&nbsp;"): <input type="text" name="namePlugin" id="namePlugin" onkeyup="if (value=='') {document.getElementById('downloadPlugin').disabled=true; }else{document.getElementById('downloadPlugin').disabled=false; }"/></p>
						<p>&nbsp;</p>
						<p>Then, you can download the plugin: <input name="downloadPlugin" id="downloadPlugin" class="button-secondary action" value="Download" type="submit" disabled onclick="top.location.href='<?php echo remove_query_arg("noheader",remove_query_arg("download")) ?>&noheader=true&download='+document.getElementById('namePlugin').value ;"></p>
						<h2 id="The_structure_of_the_folder_of_the_plugin">The structure of the folder of the plugin</h2>

						<p><img class="aligncenter" src="<?php echo WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/files_and_folders.png" ; ?>" width="800"/></p>
						<h3 id="The_laquonbspmy-pluginphpnbspraquo_file">The "&nbsp;my-plugin.php&nbsp;" file</h3>
						<p>NOTA : This file may have a different name (i.e. it depends on the name you just specify above).</p>
						<p>This file should be the master piece of your plugin: main part of your code should be written in it.</p>
						<h3 id="The_laquonbspcssnbspraquo_folder">The "&nbsp;css&nbsp;" folder</h3>
						<p>There is only two files in that folder :</p>
						<ul>
						<li><code>css_front.css</code> which is called on the front side of your blog (i.e. the <strong>public side</strong>),</li>

						<li><code>css_admin.css</code> which is called only on the back side of your blog related to your plugin (i.e. the <strong>admin configuration page of your plugin</strong>).</li>
						</ul>
						<p>They are standard CSS files, then you can put whatever CSS code you want in them.</p>
						<h3 id="The_laquonbspjsnbspraquo_folder">The "&nbsp;js&nbsp;" folder</h3>
						<p>There is only two files in that folder :</p>
						<ul>
						<li><code>js_front.js</code> which is called on the front side of your blog (i.e. the <strong>public side</strong>) and on the back side of your blog (i.e. the <strong>admin side</strong>),</li>

						<li><code>js_admin.js</code> which is called only on the back side of your blog related to your plugin (i.e. the <strong>admin configuration page of your plugin</strong>).</li>
						</ul>
						<p>They are standard JS files, then you can put whatever JS code you want in them.</p>
						<h3 id="The_laquonbspimgnbspraquo_folder">The "&nbsp;img&nbsp;" folder</h3>
						<p>You can copy any images in that folder.</p>
						<h3 id="The_laquonbsplangnbspraquo_folder">The "&nbsp;lang&nbsp;" folder</h3>

						<p>Copy any internationalization and localization (i18n) files in that folder. These files have extensions such as .po or .mo.</p>
						<p>Thses files contains translation sof the plugin.</p>
						<p>To generate such files, you may use <a href="http://sourceforge.net/projects/poedit/" target="_blank">POEdit</a>.</p>
						<h3 id="The_laquonbspcorenbspraquo_folder_and_laquonbspcorephpnbspraquo_file">The "&nbsp;core&nbsp;" folder and "&nbsp;core.php&nbsp;" file</h3>

						<p>This folder and file contain code for the framework.</p>
						<p>I do not recommend to modify their contents.</p>
						<h2 id="How_to_start_">How to start ?</h2>
						<p>Programming a plugin is not magic. Thus you should have basic knowledge in:</p>
						<ul>
						<li><a href="http://www.php.net" target="_blank">PHP </a></li>
						<li><a href="http://codex.wordpress.org/Plugins" target="_blank">WordPress&nbsp;</a></li>
						</ul>
						<p>You should then open the <code>my-plugin.php</code> file and follow instructions in comments.</p>

						<p>Moreover, documentation on how to create tables, tabs, etc. are available in the next tab.</p>
						
						</div>
						
						<?php
						$tabs->add_tab(__('How to develop a plugin?',  'SL_framework'), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_how.png") ; 
						
						//======================================================================================
						//= Tab presenting the core documentation
						//======================================================================================
											
						ob_start() ; 
							$classes = array() ; 
							
							// On liste les fichiers includer par le fichier courant
							$fichier_master = dirname(__FILE__)."/core.php" ; 
							
							$lines = file($fichier_master) ;
							
							$rc = new phpDoc();
							foreach ($lines as $lineNumber => $lineContent) {	
								if (preg_match('/^require.*[\'"](.*)[\'"]/',  trim($lineContent),$match)) {
									$chem = dirname(__FILE__)."/".$match[1] ;
									$rc->addFile($chem) ; 
								}
							}
							$rc->parse() ; 
							$rc->flush() ; 

						$tabs->add_tab(__('Framework documentation',  'SL_framework'), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_doc.png") ; 
					}
					//======================================================================================
					//= Tab for the translation
					//======================================================================================
										
					ob_start() ; 
						$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
						$trans = new translationSL("SL_framework", $plugin) ; 
						$trans->enable_translation() ; 
					$tabs->add_tab(__('Manage translation of the framework',  'SL_framework'), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 
								
					echo $tabs->flush() ; 
					
					echo $this->signature; 
					
					?>
				</div>
				<?php
			}
		
		/** ====================================================================================================================================================
		* Callback to get plugin Info
		* 
		* @access private
		* @return void
		*/
		function pluginInfo() {
			// get the arguments
			$plugin_name = $_POST['plugin_name'] ;
			$url = $_POST['url'] ;
			
			$info_core = $this->checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR.'/'.$url )."/core.php") ; 
			$hash_plugin = $this->update_hash_plugin(dirname(WP_PLUGIN_DIR."/".$url)) ; 

			// $action: query_plugins, plugin_information or hot_tags
			// $req is an object
			$action = "plugin_information" ; 
			$req->slug = $plugin_name;
			$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
			if ( is_wp_error($request) ) {
				echo  "<p>".__('An Unexpected HTTP Error occurred during the API request.', 'SL_framework' )."</p>";
			} else {
				$res = unserialize($request['body']);
				if ( ! $res ) {
					echo  "<p>".__('This plugin does not seem to be hosted on the wordpress repository.', 'SL_framework' )."</p>";
				} else {
					echo "<p>".sprintf(__('The Wordpress page: %s', 'SL_framework'),"<a href='http://wordpress.org/extend/plugins/$plugin_name'>http://wordpress.org/extend/plugins/$plugin_name</a>")."</p>" ; 
					$lastUpdate = date_i18n(get_option('date_format') , strtotime($res->last_updated)) ; 
					echo  "<p>".__('Last update:', 'SL_framework' )." ".$lastUpdate."</p>";
					echo  "<div class='inline'>".sprintf(__('Rating: %s', 'SL_framework' ), $res->rating)." &nbsp; &nbsp; </div> " ; 
					echo "<div class='star-holder inline'>" ; 
					echo "<div class='star star-rating' style='width: ".$res->rating."px'></div>" ; 
					echo "<div class='star star5'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/star.gif' alt='5 stars' /></div>" ; 
					echo "<div class='star star4'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/star.gif' alt='4 stars' /></div>" ; 
					echo "<div class='star star3'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/star.gif' alt='3 stars' /></div>" ; 
					echo "<div class='star star2'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/star.gif' alt='2 stars' /></div>" ; 
					echo "<div class='star star1'><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/star.gif' alt='1 stars' /></div>" ; 
					echo "</div> " ; 
					echo " <div class='inline'> &nbsp; (".sprintf(__("by %s persons", 'SL_framework' ),$res->num_ratings).")</div>";
					echo "<br class='clearBoth' />" ; 
					echo  "<p>".__('Number of download:', 'SL_framework' )." ".$res->downloaded."</p>";
				
				}
			}
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback to get plugin Info
		* 
		* @access private
		* @return void
		*/
		function coreInfo() {
			// get the arguments
			$plugin_name = $_POST['plugin_name'] ;
			$url = $_POST['url'] ;
			$author = $_POST['author'] ;
			$current_core_used = $_POST['current_core'] ;
			$current_fingerprint_core_used = $_POST['current_finger'] ;
		
			$info_core = $this->checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR.'/'.$url )."/core.php") ; 
			$hash_plugin = $this->update_hash_plugin(dirname(WP_PLUGIN_DIR."/".$url)) ; 

			if ($current_fingerprint_core_used != md5($info_core)) {
				$info_core = str_replace('#666666','#660000',$info_core) ;  
				$info_core .= "<p style='color:#666666;font-size:75%;text-align:right'><a href='#' onclick='coreUpdate(\"corePlugin_".md5($url)."\", \"".$url."\" , \"".$plugin_name."\" , \"".$current_core_used."\" , \"".$current_fingerprint_core_used."\" , \"".$author."\",  \"".$current_core_used."/".current_core_used.".php\", \"".$url."\") ; return false ; '>".sprintf(__('Update with the core of the %s plugin (only if you definitely know what you do)', 'SL_framework'), $current_core_used)."</a><img id='wait_corePlugin_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ;  
			} 
			
			$info = $this->get_plugins_data(WP_PLUGIN_DIR."/".$url);
			
			// SVN interface
			if ($this->frmk->get_param('adv_param')==true) {
				if (strlen($this->frmk->get_param('adv_svn_author'))>0) {
					if (preg_match("/".$this->frmk->get_param('adv_svn_author')."/i", $author)) {
						$info_core .= "<hr/>" ; 
						$info_core .= "<div><img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/SVN.png' height='24px'><b>".__("SVN management", 'SL_framework')."</b></div>"; 
						$info_core .= "<p style='color:#666666;font-size:85%;'>".sprintf(__('The SVN repository: %s', 'SL_framework'),"<a href='http://svn.wp-plugins.org/$plugin_name'>http://svn.wp-plugins.org/$plugin_name</a>")."</p>" ; 
						// We check if the repository exists
						
						$request = wp_remote_get('http://svn.wp-plugins.org/'.$plugin_name );
						if( is_wp_error( $request ) ) {
							$info_core .= "<p>".sprintf(__("An error occurs when requesting %s", 'SL_framework'), "<a href='http://svn.wp-plugins.org/$plugin_name'>http://svn.wp-plugins.org/$plugin_name</a>") ."</p>" ; 
						} else {
							if ($request['response']['code']!='200') {
								$info_core .= "<p>".sprintf(__("You do not seem to have a repository for Wordpress because %s returns a 404 error. Thus, ask for one here: %s", 'SL_framework'), "<a href='http://svn.wp-plugins.org/$plugin_name'>http://svn.wp-plugins.org/$plugin_name</a>", "<a href='http://wordpress.org/extend/plugins/add/'>Wordpress Repository</a>") ."</p>" ; 
							} else {
								$readme_remote = @file_get_contents('http://svn.wp-plugins.org/'.$plugin_name.'/trunk/readme.txt' );
								$readme_local = @file_get_contents(WP_PLUGIN_DIR."/".$plugin_name.'/readme.txt' );
								if ($readme_remote == $readme_local) {
									$info_core .= "<p style='color:#666666;font-size:85%;'>".__('The SVN repository is identical to your local plugin! You do not need to update...', 'SL_framework')."</p>" ; 
								} else {
									$version_update = "" ; 
									$version_update_bool = false ; 
									// $action: query_plugins, plugin_information or hot_tags
									// $req is an object
									$action = "plugin_information" ; 
									$req->slug = $plugin_name;
									$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
									if ( is_wp_error($request) ) {
										$version_update = " ".__("(Cannot check the version of the plugin due to HTTP Error occurred during the API request)", "SL_framework") ; 
									} else {
										$res = unserialize($request['body']);
										if ( ! $res ) {
											$version_update = "" ; 
											$version_on_wordpress = "0.0" ; 
											$version_update_bool = true ; 
										} else {
											$version_on_wordpress = $res->version ; 
											
											if ($version_on_wordpress != $info['Version']) {
												$version_update = " <b>".sprintf(__("(If you update, a new version will be released, i.e. %s instead of %s)", 'SL_framework' ),$info['Version'], $version_on_wordpress)."</b>" ; 
												$version_update_bool = true ; 
											}
										}
									}
									
									$info_core .= "<p style='color:#660000;font-size:85%;'>".__('The SVN repository is not identical to your local plugin!', 'SL_framework')."</p>" ; 
									$md5 = md5($plugin_name." to_local") ; 
									
									$info_core .=  "<p style='color:#666666;font-size:75%;padding-left:3em;'>" ; 
									$info_core .= "<img id='wait_gm_".$md5."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
									$info_core .= "<img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/arrow-no.png'>&nbsp;" ; 
									$info_core .= __("General modifications:", 'SL_framework') ;
									$info_core .= " <a href='#' onClick='changeVersionReadme(\"".$md5."\", \"".$plugin_name."\"); return false;'>".__("Modify the version of the plugin and the readme.txt file", 'SL_framework') ."</a>" ;
									$info_core .=  "</p>" ;
									
									$info_core .=  "<p style='color:#666666;font-size:75%;padding-left:3em;'>" ; 
									$info_core .= "<img id='wait_svn_".$md5."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
									$info_core .= "<img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/arrow-left.png'>&nbsp;" ; 
									$info_core .= __("Overwrite the local plugin files with files stored the SVN repository", 'SL_framework') ;
									$info_core .= " (<a href='#' onClick='showSvnPopup(\"".$md5."\", \"".$plugin_name."\", \"to_local_quick\"); return false;'><b>".__("Quick", 'SL_framework') ."</b></a>" ;
									$info_core .=  "|<a href='#' onClick='showSvnPopup(\"".$md5."\", \"".$plugin_name."\", \"to_local\"); return false;'>".__("Slow", 'SL_framework')."</a>)" ; 
									$info_core .=  "</p>" ;
									
									$md5 = md5($plugin_name." to_repo") ; 
									$info_core .=  "<p style='color:#666666;font-size:75%;padding-left:3em;'>" ; 
									$info_core .= "<img id='wait_svn_".$md5."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
									$info_core .= "<img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/arrow-right.png'>&nbsp;" ; 
									if ($version_update_bool) {
										$info_core .= __("Update the SVN repository with your current local plugin files", 'SL_framework') ;
										$info_core .= $version_update ; 
										$info_core .=  " (<a href='#' onClick='showSvnPopup(\"".$md5."\", \"".$plugin_name."\", \"to_repo_quick\"); return false;'><b>".__("Quick", 'SL_framework')."</b></a>" ; 
										$info_core .=  "|<a href='#' onClick='showSvnPopup(\"".$md5."\", \"".$plugin_name."\", \"to_repo\"); return false;'>".__("Slow", 'SL_framework')."</a>)" ; 
									} else {
										$info_core .= " <b>".sprintf(__("Please modify the Version in %s in order to be allowed to commit it", 'SL_framework' ), "<em>".$url."</em>")."</b>" ; 								
									}
									$info_core .=  "</p>" ; 
									
									$info_core .=  "<p style='color:#666666;font-size:75%;text-align:right;'>".__("The slow version may be useful if you have issues with the quick version.", 'SL_framework') ."</p>" ; 
								}
							}
						}
					}
				}
			}
			echo $info_core ; 
			die() ; 
		}
		/** ====================================================================================================================================================
		* Callback to get plugin Info
		* 
		* @access private
		* @return void
		*/
		function coreUpdate() {
			// get the arguments
			$path_from_update = $_POST['from'] ;
			$path_to_update = $_POST['to'] ;
			
			$this->checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR."/".$path_from_update)."/core.php") ; 
		
			$path_to_update = explode("/", $path_to_update) ; 
			$path_to_update[count($path_to_update)-1] = "" ; 
			$path_to_update = implode("/", $path_to_update) ; 
			
			$path_from_update = explode("/", $path_from_update) ; 
			$path_from_update[count($path_from_update)-1] = "" ; 
			$path_from_update = implode("/", $path_from_update) ; 
			
			Utils::rm_rec(WP_PLUGIN_DIR."/".$path_to_update."core/") ; 
			Utils::rm_rec(WP_PLUGIN_DIR."/".$path_to_update."core.php") ; 
			Utils::rm_rec(WP_PLUGIN_DIR."/".$path_to_update."core.class.php") ; 
			Utils::rm_rec(WP_PLUGIN_DIR."/".$path_to_update."core.nfo") ; 
			Utils::copy_rec(WP_PLUGIN_DIR."/".$path_from_update."core/", WP_PLUGIN_DIR."/".$path_to_update."core/") ; 
			Utils::copy_rec(WP_PLUGIN_DIR."/".$path_from_update."core.php", WP_PLUGIN_DIR."/".$path_to_update."core.php") ; 
			Utils::copy_rec(WP_PLUGIN_DIR."/".$path_from_update."core.class.php", WP_PLUGIN_DIR."/".$path_to_update."core.class.php") ; 
			Utils::copy_rec(WP_PLUGIN_DIR."/".$path_from_update."core.nfo", WP_PLUGIN_DIR."/".$path_to_update."core.nfo") ; 
			
			$this->coreInfo() ; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* This function update the readme.txt in order to insert the hash of the version
		* Normally the hash will be added in the FAQ
		* 
		* @access private
		* @param string $path the path of the plugin
		* @return string hash of the plugin
		*/
		private function update_hash_plugin($path)  {

			$hash_plugin = Utils::md5_rec($path, array('readme.txt', 'core', 'core.php', 'core.class.php')) ; // Par contre je conserve le core.nfo 
			
			// we recreate the readme.txt
			$lines = file( $path."/readme.txt" , FILE_IGNORE_NEW_LINES );
			$i = 0 ; 
			$toberecreated = false ;  
			$found = false ; 
			$result = array() ; 
			$toomuch = 0 ; 
			for ($i=0; $i<count($lines); $i++) {
				// We convert if UTF-8
				if (seems_utf8($lines[$i])) {
					$lines[$i] = utf8_encode($lines[$i]) ; 
				}
			
				// Do we found any line with InfoVersion ?
				if (preg_match("/InfoVersion:/", $lines[$i])) {
					$found = true ; 
					if (strpos($lines[$i],$hash_plugin)===false) {
						$toomuch ++ ; 
						$lines[$i]="" ;   
						$toberecreated = true ; 
					}
				}
				if (strlen(trim($lines[$i]))>0) {
					$toomuch = 0 ;  
				} else {
					$toomuch ++ ; 
				}
				// We do not add multiple blank lines (i.e. more than 2)
				if ($toomuch<2) {
					$result[] = $lines[$i]  ; 
				}
			}
			
			if (($toberecreated)||(!$found)) {
				file_put_contents( $path."/readme.txt", implode( "\r\n", $result )."\r\n \r\n"."InfoVersion:".$hash_plugin, LOCK_EX ) ; 
			}
			
			return $hash_plugin ; 
		}
	
		/** ====================================================================================================================================================
		* This function returns the plugin zip  
		* 
		* @access private
		* @param string $name the name of the plugin
		* @return void
		*/
		private function getPluginZip($name)  {
			$name = preg_replace("/[^a-zA-Z ]/", "", trim($name)) ; 
			$folder_name = strtolower(str_replace(" ", "-", $name)) ; 
			$id_name = strtolower(str_replace(" ", "_", $name)) ; 
			
			$plugin_dir = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__ ),"",plugin_basename( __FILE__ )) ; 
			
			if ($folder_name!="") {
				// Create the temp folder
				$path = WP_CONTENT_DIR."/sedlex/new_plugins_zip/".$folder_name ; 
				if (!is_dir($path)) {
					mkdir("$path", 0755, true) ; 
				}
				
				// Copy static files
				Utils::copy_rec($plugin_dir.'/core/templates/css',$path.'/css') ; 
				Utils::copy_rec($plugin_dir.'/core/templates/js',$path.'/js') ; 
				Utils::copy_rec($plugin_dir.'/core/templates/img',$path.'/img') ; 
				Utils::copy_rec($plugin_dir.'/core/templates/lang',$path.'/lang') ; 
				Utils::copy_rec($plugin_dir.'/core',$path.'/core') ; 
				Utils::copy_rec($plugin_dir.'/core.php',$path."/core.php") ; 
				Utils::copy_rec($plugin_dir.'/core.class.php',$path."/core.class.php") ; 
				Utils::copy_rec($plugin_dir.'/core.nfo',$path."/core.nfo") ; 
				
				// Copy the dynamic files
				$content = file_get_contents($plugin_dir.'/core/templates/my-plugin.php') ; 
				$content = str_replace("My Plugin", $name, $content ) ; 
				$content = str_replace("my_plugin", $id_name, $content ) ; 
				file_put_contents($path."/".$folder_name.".php", $content);

				$content = file_get_contents($plugin_dir.'/core/templates/readme.txt') ; 
				$content = str_replace("My Plugin", $name, $content ) ; 
				$content = str_replace("my_plugin", $id_name, $content ) ; 
				file_put_contents($path."/readme.txt", $content);

				// Zip the folder
				$file = WP_CONTENT_DIR."/sedlex/new_plugins_zip/".$folder_name.".zip" ; 
				$zip = new PclZip($file) ; 
				$remove = WP_CONTENT_DIR."/sedlex/new_plugins_zip/" ;
				$result = $zip->create($path, PCLZIP_OPT_REMOVE_PATH, $remove); 
				//$result = $zip->create($folder_name,  PCLZIP_OPT_REMOVE_PATH, $folder_name); 
				if ($result == 0) {
    				die("Error : ".$zip->errorInfo(true));
  				}
				
				// Stream the file to the client
				header("Content-Type: application/zip");
				header("Content-Length: " . @filesize($file));
				header("Content-Disposition: attachment; filename=\"".$folder_name.".zip\"");
				readfile($file);
				
				// We stop everything
				unlink($file); 
				Utils::rm_rec($path) ; 
				die() ; 
			}
		}
		
		
		/** ====================================================================================================================================================
		* Get information on the plugin
		* For instance <code> $info = $this->get_plugins_data(WP_PLUGIN_DIR.'/my-plugin/my-plugin.php')</code> will return an array with 
		* 	- the folder of the plugin : <code>$info['Dir_Plugin']</code>
		* 	- the name of the plugin : <code>$info['Plugin_Name']</code>		
		* 	- the tags of the plugin : <code>$info['Plugin_Tag']</code>
		* 	- the url of the plugin : <code>$info['Plugin_URI']</code>
		* 	- the description of the plugin : <code>$info['Description']</code>
		* 	- the name of the author : <code>$info['Author']</code>
		* 	- the url of the author : <code>$info['Author_URI']</code>
		* 	- the version number : <code>$info['Version']</code>
		* 	- the email of the Author : <code>$info['Email']</code>
		* 
		* @param string $plugin_file path of the plugin main file. If no paramater is provided, the file is the current plugin main file.
		* @return array information on Name, Author, Description ...
		*/

		public function get_plugins_data($plugin_file='') {
			if ($plugin_file == "")
				$plugin_file = $this->path ; 
		
			$plugin_data = implode('', file($plugin_file));
			preg_match("|Plugin Name:(.*)|i", $plugin_data, $plugin_name);
			preg_match("|Plugin Tag:(.*)|i", $plugin_data, $plugin_tag);
			preg_match("|Plugin URI:(.*)|i", $plugin_data, $plugin_uri);
			preg_match("|Description:(.*)|i", $plugin_data, $description);
			preg_match("|Author:(.*)|i", $plugin_data, $author_name);
			preg_match("|Author URI:(.*)|i", $plugin_data, $author_uri);
			preg_match("|Author Email:(.*)|i", $plugin_data, $author_email);
			preg_match("|Framework Email:(.*)|i", $plugin_data, $framework_email);
			if (preg_match("|Version:(.*)|i", $plugin_data, $version)) {
				$version = trim($version[1]);
			} else {
				$version = '';
			}
			
			$plugins_allowedtags = array('a' => array('href' => array()),'code' => array(), 'p' => array() ,'ul' => array() ,'li' => array() ,'strong' => array());
			
			$plugin_name = wp_kses(trim($plugin_name[1]), $plugins_allowedtags);
			$plugin_tag = wp_kses(trim($plugin_tag[1]), $plugins_allowedtags);
			$plugin_uri = wp_kses(trim($plugin_uri[1]), $plugins_allowedtags);
			$description = wp_kses(wptexturize(trim($description[1])), $plugins_allowedtags);
			$author = wp_kses(trim($author_name[1]), $plugins_allowedtags);
			$author_uri = wp_kses(trim($author_uri[1]), $plugins_allowedtags);;
			$author_email = wp_kses(trim($author_email[1]), $plugins_allowedtags);;
			$framework_email = wp_kses(trim($framework_email[1]), $plugins_allowedtags);;
			$version = wp_kses($version, $plugins_allowedtags);
			
			return array('Dir_Plugin'=>basename(dirname($plugin_file)) , 'Plugin_Name' => $plugin_name,'Plugin_Tag' => $plugin_tag, 'Plugin_URI' => $plugin_uri, 'Description' => $description, 'Author' => $author, 'Author_URI' => $author_uri, 'Email' => $author_email, 'Framework_Email' => $framework_email, 'Version' => $version);
		}
		
		/** ====================================================================================================================================================
		* Check core version of the plugin
		* 
		* @access private
		* @param string $path path of the plugin
		* @return void
		*/
		
		private function checkCoreOfThePlugin($path)  {
			$resultat = "" ; 
			$style = "style='color:#666666;font-size:85%;'" ; 
			
			
			// We compute the hash of the core folder
			$md5 = Utils::md5_rec(dirname($path).'/core/', array('SL_framework.pot', 'data')) ; 
			if (is_file(dirname($path).'/core.php'))
				$md5 .= file_get_contents(dirname($path).'/core.php') ; 
			if (is_file(dirname($path).'/core.class.php'))
				$md5 .= file_get_contents(dirname($path).'/core.class.php') ; 
				
			$md5 = md5($md5) ; 
			
			$to_be_updated = false ; 
			if (is_file(dirname($path).'/core.nfo')) {
				$info = file_get_contents(dirname($path).'/core.nfo') ; 
				$info = explode("#", $info) ; 
				if ($md5 != $info[0]) {
					unlink(dirname($path).'/core.nfo') ; 
					$to_be_updated = true ; 
				}
				$date = $info[1] ; 
			} else {
				$to_be_updated = true ; 
			}
			
			// we update the info
			if ($to_be_updated) {
				$date = date("YmdHis") ; 
				file_put_contents(dirname($path).'/core.nfo', $md5."#".$date) ; 
			}
			
			$year = substr($date, 0, 4) ; 
			$month = substr($date, 4, 2) ; 
			$day = substr($date, 6, 2) ; 
			$hour = substr($date, 8, 2) ; 
			$min = substr($date, 10, 2) ; 
			$sec = substr($date, 12, 2) ;
			$temp_time = $year.'-'.$month.'-'.$day.' '.$hour.':'. $min.':' .$sec ; 
			$time = strtotime($temp_time) ; 
			
			$resultat .= "<p ".$style.">".sprintf(__('Last update of the core: %s at %s','SL_framework'), "<b>".date("d M Y",$time)."</b>", "<b>".date("H:i:s",$time))."</b></p>" ; 

			return $resultat ; 
		} 
		
		/** ====================================================================================================================================================
		* Ensure that the needed folders are writable by the webserver. 
		* Will check usual folders and files.
		* You may add this in your configuration page <code>$this->check_folder_rights( array(array($theFolderToCheck, "rwx")) ) ;</code>
		* If not a error msg is printed
		* 
		* @param array $folders list of array with a first element (the complete path of the folder to check) and a second element (the needed rights "r", "w" or "x" [or a combination of those])
		* @return void
		*/
		
		public function check_folder_rights ($folders) {
			$f = array(array(WP_CONTENT_DIR.'/sedlex/',"rwx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'readme.txt',"rw"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'css/',"rx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'js/',"rx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'lang/',"rwx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/',"rx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/img/',"rx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/templates/',"rx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/lang/',"rwx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/',"rx"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/',"rx")) ; 
			$folders = array_merge($folders, $f) ; 
			
			$result = "" ; 
			foreach ($folders as $f ) {
				if ( (is_dir($f[0])) || (is_file($f[0])) ) {
					$readable = is_readable($f[0]) ; 
					$writable = is_writable($f[0]) ; 
					$executable = is_executable($f[0]) ; 
					
					@chmod($f[0], 0755) ; 
					
					$pb = false ; 
					if ((strpos($f[1], "r")!==false) && (!$readable)) {
						$pb = true ; 
					}
					if ((strpos($f[1], "x")!==false) && (!$executable)) {
						$pb = true ; 
					}
					if ((strpos($f[1], "w")!==false) && (!$writable)) {
						$pb = true ; 
					}
					
					if ($pb) {
						if  (is_dir($f[0])) 
							$result .= "<p>".sprintf(__('The folder %s is not %s !','SL_framework'), "<code>".$f[0]."</code>", "<code>".$f[1]."</code>")."</p>" ; 
						if  (is_file($f[0])) 
							$result .= "<p>".sprintf(__('The file %s is not %s !','SL_framework'), "<code>".$f[0]."</code>", "<code>".$f[1]."</code>")."</p>" ; 
					}
				} else {
					// We check if the last have an extension
					if (strpos(basename($f[0]) , ".")===false) {
						// It is a folder
						if (!@mkdir($f[0],0755,true)) {
							$result .= "<p>".sprintf(__('The folder %s does not exists and cannot be created !','SL_framework'), "<code>".$f[0]."</code>")."</p>" ; 
						}
					} else {
					
						$foldtemp = str_replace(basename($f[0]), "", str_replace(basename($f[0])."/","", $f[0])) ; 
						// We create the sub folders
						if ((!is_dir($foldtemp))&&(!@mkdir($foldtemp,0755,true))) {
							$result .= "<p>".sprintf(__('The folder %s does not exists and cannot be created !','SL_framework'), "<code>".$foldtemp."</code>")."</p>" ; 
						} else {
							// We touch the file
							@chmod($foldtemp, 0755) ; 
							if (@file_put_contents($f[0], '')===false) {
								$result .= "<p>".sprintf(__('The file %s does not exists and cannot be created !','SL_framework'), "<code>".$f[0]."</code>")."</p>" ; 
							}
						}
					}
				}
			}
			if ($result != "") {
				echo "<div class='error fade'><p>".__('There are some issues with folders rights. Please corret them as soon as possible as they could induce bugs and instabilities.','SL_framework')."</p><p>".__('Please see below:','SL_framework')."</p>".$result."</div>" ; 
			}
			
		}
		
		/** ====================================================================================================================================================
		* Callback for displaying the SVN popup
		* 
		* @access private
		* @return void
		*/		
		
		function svn_show_popup() {
		
			// get the arguments
			$plugin = $_POST['plugin'];
			$sens = $_POST['sens'];

			if ($sens=="to_repo") {
				$title = sprintf(__('Update the SVN repository %s with your current local plugin files (slower version)', 'SL_framework'),'<em>'.$plugin.'</em>') ;
			}
			if ($sens=="to_repo_quick") {
				$title = sprintf(__('Update the SVN repository %s with your current local plugin files', 'SL_framework'),'<em>'.$plugin.'</em>') ;
			}
			if ($sens=="to_local") {
				$title = sprintf(__('Overwrite the local plugin %s files with files stored the SVN repository', 'SL_framework'),'<em>'.$plugin.'</em>') ; ;
			}
			ob_start() ; 
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			Utils::rm_rec($local_cache."/".$plugin) ;
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
				
			if (($sens=="to_repo_quick")||($sens=="to_local_quick")) {
				$revision = $svn->getRevision("/".$plugin."/trunk", true) ;
				$vcc = $svn->getVCC("/".$plugin."/trunk", true) ;
				$revision = $revision['revision'] ; 
				$vcc = $vcc['vcc'] ; 
				
				$res = $svn->getAllFiles("/".$plugin."/trunk", $vcc, $revision, $local_cache."/".$plugin, true) ; 
				echo "<div id='svn_div'>" ; 
				
				// On met a jour le cache local !
				echo "<h3>".__('Updating the local cache', 'SL_framework')."</h3>" ; 

				echo "<div class='console' id='svn_console'>\n" ; 
				foreach ($res['info'] as $inf) {
					$i++ ; 
					if ($inf['folder']) {
						if ($inf['ok']) {
							echo $i.". ".$inf['url']." <span style='color:#669900'>OK</span> (".__('folder created', 'SL_framework').")<br/>" ; 
						} else {
							echo $i.". ".$inf['url']." <span style='color:#CC0000'>KO</span> (".__('folder creation has failed !', 'SL_framework').")<br/>" ; 						
						}
					} else {
						if ($inf['ok']) {
							echo $i.". ".$inf['url']." <span style='color:#669900'>OK</span> (".sprintf(__("%s bytes transfered", 'SL_framework'), $inf['size']).")<br/>" ; 
						} else {
							echo $i.". ".$inf['url']." <span style='color:#CC0000'>KO</span><br/>" ; 						
						}						
					}
				}
				
				
				if ($res['isOK']) {
					echo "</div>\n" ; 
					echo "<script>\n" ; 	
					echo "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
					if ($sens=="to_repo_quick") {
						echo "window.setTimeout( function() { var arguments = {action: 'svn_compare_localRepo', plugin : '".$plugin."'}; jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#innerPopupForm\").html(response); }) ; } , 3000);\r\n" ; 
					}
					if ($sens=="to_local_quick") {
						echo "window.setTimeout( function() { var arguments = {action: 'svn_compare_repoLocal', plugin : '".$plugin."'}; jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#innerPopupForm\").html(response); }) ; } , 3000);\r\n" ; 
					}
					echo "</script>\n" ; 	
					
				} else {
					echo __('An error occurred during the retrieval of files on the server ! Sorry ...', 'SL_framework')."<br/>\n" ; 
					$svn->printRawResult($res['raw_result']) ; 	
					echo "</div>\n" ; 					
				}
				echo "</div>\n" ; 	
				
				
			} else if (($sens=="to_repo")||($sens=="to_local")) {
				
				echo "<div id='svn_div'>" ; 
				
				// On met a jour le cache local !
				echo "<h3>".__('Updating the local cache', 'SL_framework')."</h3>" ; 
				echo "<p>".__('To be sure that you compare the local plugin with the latest repoository files, it is necessary to download it locally. Please wait during the update (your are using the slow version which may be more reliable especially with big plugins)...', 'SL_framework')."</p>" ; 
				
				$files = $svn->listFilesInRepository("/".$plugin."/trunk/", true) ; 
				
				if ($files['isOK']==true) {
					echo "<div class='console' id='svn_console'>\n" ; 
					echo sprintf(__('Update of the files of the %s plugin in progress (%s files)', 'SL_framework'), "<b>".$plugin."</b>", count($files['list']) )."<br/>\n" ; 
					echo "</div>\n" ; 
					echo "<script id='svn_get_files'>" ; 
					$nbfiles = 0 ;
					$tabulation = "" ; 
					$i = 0 ; 
					foreach ($files['list'] as $f) {
						$i++ ; 
						if ($f['folder']==true) {
							@mkdir($local_cache.str_replace('/trunk', '', $f['href']),0777, true) ; 
							if (is_dir($local_cache.str_replace('/trunk', '', $f['href']))) 
								echo $tabulation . "jQuery(\"#svn_console\").append(\"".$i.". ".str_replace('/'.$plugin."/trunk", '', $f['href'])." <span style='color:#669900'>OK</span> (".__('folder created', 'SL_framework').")<br/>\");\r\n" ; 
							else 
								echo $tabulation . "jQuery(\"#svn_console\").append(\"".$i.". ".str_replace('/'.$plugin."/trunk", '', $f['href'])." <span style='color:#CC0000'>KO</span> (".__('folder creation has failed !', 'SL_framework').")<br/>\");\r\n" ; 
						} else {
							echo $tabulation . "jQuery(\"#svn_console\").append(\"".$i.". ".str_replace('/'.$plugin."/trunk", '', $f['href'])."\");\r\n" ; 
							echo $tabulation . "var arguments = {action: 'svn_get_file', from : '".$f['href']."', to : '".$local_cache.str_replace('/trunk', '', $f['href'])."' }\r\n" ;  
							//POST the data and append the results to the results div
							echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console\").append(response+' (".floor(100*$i/count($files['list']))."%)<br/>');\r\n" ;
							echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
							$nbfiles ++ ; 
							$tabulation .= "  " ; 
						}
					}
					// we call the comparison callback
					if ($sens=="to_repo") {
						echo $tabulation . "window.setTimeout( function() { var arguments = {action: 'svn_compare_localRepo', plugin : '".$plugin."'}; jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#innerPopupForm\").html(response); }) ; } , 3000);\r\n" ; 
					}
					
					if ($sens=="to_local") {
						echo $tabulation . "window.setTimeout( function() { var arguments = {action: 'svn_compare_repoLocal', plugin : '".$plugin."'}; jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#innerPopupForm\").html(response); }) ; } , 3000);\r\n" ; 
					}
					// FIN
					for ($i=0 ; $i<$nbfiles ; $i++) {
						$tabulation = substr($tabulation, 0, -2) ; 
						echo $tabulation . "});\r\n" ; 
					}
					echo "</script>" ; 
				} else {
					echo "<div class='console'>\n" ; 
					echo sprintf(__('An error occurred when listing the files of the %s plugin ! Sorry ...', 'SL_framework'), "<b>".$plugin."</b>")."<br/>\n" ; 
					$svn->printRawResult($files['raw_result']) ; 
					echo "</div>\n" ; 
				}
				
				echo "</div>" ; 
			}
			
			$content = ob_get_clean() ; 	
			$current_core_used = str_replace(WP_PLUGIN_DIR."/",'',dirname(__FILE__)) ; 
			$current_fingerprint_core_used = md5($this->checkCoreOfThePlugin(WP_PLUGIN_DIR."/".$current_core_used."/core.php")) ; 
			$info = $this->get_plugins_data(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php");
								
			$popup = new popupAdmin($title, $content, "", "coreInfo('corePlugin_".md5($plugin."/".$plugin.".php")."', '".$plugin."/".$plugin.".php', '".$plugin."' , '".$current_core_used."', '".$current_fingerprint_core_used."', '".$info['Author']."') ; ") ; 
			$popup->render() ; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for retrieving a file on the plugin repository
		* 
		* @access private
		* @return void
		*/		
		
		function svn_get_file() {
			// get the arguments
			$from = $_POST['from'] ;
			$to = $_POST['to'] ;
			
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
			
			// GET the file
			$result = $svn->getFile($from, $to, true) ; 
			if ($result['isOK']) {
				echo " <span style='color:#669900'>OK</span> (".sprintf(__("%s bytes transfered", 'SL_framework'), $result['size']).")" ; 
			} else {
				echo " <span style='color:#CC0000'>KO</span>" ; 
			}
			die() ; 
		}
		
		
		/** ====================================================================================================================================================
		* Callback for comparing a plugin with the repo cache
		* 
		* @access private
		* @return void
		*/		
		
		function svn_compare_localRepo() {
			// get the arguments
			$plugin = $_POST['plugin'] ;
			
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			
			$info_core = $this->checkCoreOfThePlugin(WP_PLUGIN_DIR.'/'.$plugin ."/core.php") ; 
			$hash_plugin = $this->update_hash_plugin(WP_PLUGIN_DIR."/".$plugin) ; 
			
			echo "<h3>".__('Browsing the modifications', 'SL_framework')."</h3>" ; 
			echo "<p>".sprintf(__('Comparing %s with %s', 'SL_framework'), "<em>".WP_PLUGIN_DIR."/".$plugin."/</em>", "<em>".$local_cache."/".$plugin."/"."</em>")."</p>" ; 
			$folddiff = new foldDiff() ; 
			$result = $folddiff->diff(WP_PLUGIN_DIR."/".$plugin, $local_cache."/".$plugin) ; 
			$folddiff->render(true, true) ; 
			
			// Confirmation asked
			echo "<div id='confirm_to_svn'>" ; 
			echo "<h3>".__('Confirmation', 'SL_framework')."</h3>" ; 
			
			echo "<p>".__('Commit comment:', 'SL_framework')."</p>" ; 
			echo 	"<p><textarea cols='70' rows='5' name='svn_comment' id='svn_comment'/></textarea></p>\n" ;  
			echo "<p id='svn_button'><input onclick='svnToRepo(\"".$plugin."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Yes, the SVN version will be deleted and be replaced by the local version', 'SL_framework')."' /></p>" ;  
			
			echo "<script>jQuery('#innerPopupForm').animate({scrollTop: 0}, 10);</script>\r\n" ; 
			echo "</div>" ; 
			
			echo "<p><img id='wait_svn' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 

			echo "<div id='console_svn'></div>\r\n" ; 
						
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for comparing a plugin with the repo cache
		* 
		* @access private
		* @return void
		*/		
		
		function svn_compare_repoLocal() {
			// get the arguments
			$plugin = $_POST['plugin'] ;
			
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
		
			echo "<h3>".__('Browsing the modifications', 'SL_framework')."</h3>" ; 
			echo "<p>".sprintf(__('Comparing %s with %s', 'SL_framework'), "<em>".$local_cache."/".$plugin."/"."</em>", "<em>".WP_PLUGIN_DIR."/".$plugin."/</em>")."</p>" ; 
			$folddiff = new foldDiff() ; 
			$result = $folddiff->diff($local_cache."/".$plugin, WP_PLUGIN_DIR."/".$plugin) ; 
			$folddiff->render(true, true) ; 
			
			// Confirmation asked
			echo "<div id='confirm_to_svn'>" ; 
			echo "<h3>".__('Confirmation', 'SL_framework')."</h3>" ; 
			
			echo "<p id='svn_button'><input onclick='svnToLocal(\"".$plugin."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Yes, the local version will be overwritten with the repository files', 'SL_framework')."' /></p>" ;  
			
			echo "<script>jQuery('#innerPopupForm').animate({scrollTop: 0}, 10);</script>\r\n" ; 
			echo "</div>" ; 
			
			echo "<p><img id='wait_svn' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 

			echo "<div id='console_svn'></div>\r\n" ; 
						
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for sending selected local file to repo
		* 
		* @access private
		* @return void
		*/		
		
		function svn_to_repo() {
			// get the arguments
			$plugin = $_POST['plugin'] ;
			$comment = $_POST['comment'] ;
			$files = $_POST['files'] ;
			echo "<div class='console' id='svn_console2'>\n" ; 
			echo __("Sending to the repository in progress...", "SL_framework")."<br/>----------<br/><br/>" ; 
						
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ;
			$root = "/".$plugin."/trunk/" ; 
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
			
			$result = $svn->prepareCommit($root, $comment, true) ; 
			if ($result['isOK']) {
				echo __("Preparation of the sending done !", "SL_framework")."<br/>" ; 
				echo "</div>\n" ; 
				
				
				echo "<script>" ; 
				$tabulation = "" ; 
				$i = 0 ; 
				//We add the created folder ... we order the folder to add by their string length because the shorter path should be add at first
				$fold2add = array() ; 
				foreach ($files as $f) {
					if ($f[1]=='add_folder') {
						$fold2add = array_merge($fold2add, array($f[0] => strlen($f[0])) ) ; 
					}
				}
				asort($fold2add) ; 
				foreach ($fold2add as $f => $n) {
					$i++ ; 
					$url = WP_PLUGIN_DIR."/".$plugin."/".$f ; 
					$depo = $result['putFolder']."/".$f ; 
					echo $tabulation . "jQuery(\"#svn_console2\").append(\"(A) ".$url." (folder)\");\r\n" ; 
					echo $tabulation . "var arguments = {action: 'svn_put_folder_in_repo', urlfolder : '".$depo."' }\r\n" ;  
					//POST the data and append the results to the results div
					echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console2\").append(response+' (".floor(100*$i/count($files))."%)<br/>');\r\n" ;
					echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
					$nbfiles ++ ; 
					$tabulation .= "  " ; 
				}
				// Now we send/delete the files !!!!
				foreach ($files as $f) {
					
					$url = WP_PLUGIN_DIR."/".$plugin."/".$f[0] ; 
					$depo = $result['putFolder']."/".$f[0] ; 
					if ($f[1]=='add') {
						$i++ ; 
						echo $tabulation . "jQuery(\"#svn_console2\").append(\"(A) ".$url."\");\r\n" ; 
						echo $tabulation . "var arguments = {action: 'svn_put_file_in_repo', file : '".$url."', urldepot : '".$depo."' }\r\n" ;  
						//POST the data and append the results to the results div
						echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console2\").append(response+' (".floor(100*$i/count($files))."%)<br/>');\r\n" ;
						echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
						$nbfiles ++ ; 
						$tabulation .= "  " ; 
					}
					if ($f[1]=='modify') {
						$i++ ; 
						echo $tabulation . "jQuery(\"#svn_console2\").append(\"(U) ".$url."\");\r\n" ; 
						echo $tabulation . "var arguments = {action: 'svn_put_file_in_repo', file : '".$url."', urldepot : '".$depo."' }\r\n" ;  
						//POST the data and append the results to the results div
						echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console2\").append(response+' (".floor(100*$i/count($files))."%)<br/>');\r\n" ;
						echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
						$nbfiles ++ ; 
						$tabulation .= "  " ; 
					}
					if ($f[1]=='delete') {
						$i++ ; 
						echo $tabulation . "jQuery(\"#svn_console2\").append(\"(D) ".$url."\");\r\n" ; 
						echo $tabulation . "var arguments = {action: 'svn_delete_in_repo', url : '".$depo."' }\r\n" ;  
						//POST the data and append the results to the results div
						echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console2\").append(response+' (".floor(100*$i/count($files))."%)<br/>');\r\n" ;
						echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
						$nbfiles ++ ; 
						$tabulation .= "  " ; 
					}
				}
				
				//We delete the deleted folder ... we order (reverse) the folder to delete them by their string length because the shorter path should be add at last
				$fold2delete = array() ; 
				foreach ($files as $f) {
					if ($f[1]=='delete_folder') {
						$fold2delete = array_merge($fold2delete, array($f[0] => strlen($f[0])) ) ; 
					}
				}
				arsort($fold2delete) ; 
				foreach ($fold2delete as $f => $n) {
					$i++ ; 
					$url = WP_PLUGIN_DIR."/".$plugin."/".$f ; 
					$depo = $result['putFolder']."/".$f ;
					echo $tabulation . "jQuery(\"#svn_console2\").append(\"(D) ".$url." (folder)\");\r\n" ; 
					echo $tabulation . "var arguments = {action: 'svn_delete_in_repo', url : '".$depo."' }\r\n" ;  
					//POST the data and append the results to the results div
					echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console2\").append(response+' (".floor(100*$i/count($files))."%)<br/>');\r\n" ;
					echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
					$nbfiles ++ ; 
					$tabulation .= "  " ;
				}
				
				// FIN
				echo $tabulation . "jQuery(\"#svn_console2\").append(' ".__('Sending the file is finished ! Now merging the change in the repository ...', 'SL_framework')."<br/>');\r\n" ; 
				echo $tabulation . "var arguments = {action: 'svn_merge', root : '".$root."', uuid : '".$result['uuid']."', activityFolder : '".$result['activityFolder']."' }\r\n" ;  
				//POST the data and append the results to the results div
				echo $tabulation . "jQuery.post(ajaxurl, arguments, function(response) { jQuery(\"#svn_console2\").append('----------<br/>'+ response+'<br/>');\r\n" ;
				echo $tabulation . "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
				echo $tabulation . "});\r\n" ; 
				
				for ($i=0 ; $i<$nbfiles ; $i++) {
					$tabulation = substr($tabulation, 0, -2) ; 
					echo $tabulation . "});\r\n" ; 
				}
				echo "</script>" ; 
				
			} else {
				echo __("Error while preparing the sending!", "SL_framework")."<br/>" ; 
				echo $svn->printRawResult($result['raw_result']) ; 
				echo "</div>\n" ; 
			}
							
			die() ; 
		}
		
		
		/** ====================================================================================================================================================
		* Callback for putting file into the repo
		* 
		* @access private
		* @return void
		*/		
		
		function svn_put_file_in_repo() {
			// get the arguments
			$file = $_POST['file'] ;
			$urldepot = $_POST['urldepot'] ;
			
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
			
			// PUT the file
			$res = $svn->putFile($urldepot, $file , true) ; 
			if ($res['isOK']) {
				echo " <span style='color:#669900'>OK</span>" ; 
			} else {
				echo " <span style='color:#CC0000'>KO</span><br/>" ;
				echo 	"SVN header : <br/>" ; 
				print_r($res['svn_header']) ; 
				echo "<br/>" ; 
				echo $svn->printRawResult($res['raw_result']) ; 
			}
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for putting folder into the repo
		* 
		* @access private
		* @return void
		*/		
		
		function svn_put_folder_in_repo() {
			// get the arguments
			$urlfolder = $_POST['urlfolder'] ;
			
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
			
			// PUT the file
			$res = $svn->putFolder($urlfolder , true) ; 
			if ($res['isOK']) {
				echo " <span style='color:#669900'>OK</span>" ; 
			} else {
				echo " <span style='color:#CC0000'>KO</span><br/>" ; 
				echo $svn->printRawResult($res['raw_result']) ; 
			}
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for deleting a folder or a file into the repo
		* 
		* @access private
		* @return void
		*/		
		
		function svn_delete_in_repo() {
			// get the arguments
			$url = $_POST['url'] ;
			
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
			
			// PUT the file
			$res = $svn->deleteFileFolder($url , true) ; 
			if ($res['isOK']) {
				echo " <span style='color:#669900'>OK</span>" ; 
			} else {
				echo " <span style='color:#CC0000'>KO</span><br/>" ; 
				echo $svn->printRawResult($res['raw_result']) ; 
			}
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for merging the change in the repo
		* 
		* @access private
		* @return void
		*/		
		
		function svn_merge() {
			// get the arguments
			$root = $_POST['root'] ;
			$uuid = $_POST['uuid'] ;
			$activityFolder = $_POST['activityFolder'] ;
			
			// SVN preparation
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->frmk->get_param('adv_svn_login'), $this->frmk->get_param('adv_svn_pwd') ) ; 
			
			// PUT the file
			$res = $svn->merge($root, $activityFolder.$uuid,  true) ; 
			if ($res['isOK']) {
				echo " <span style='color:#669900'>".sprintf(__("The commit has ended [ %s ]... You should received an email quickly !","SL_framework"), $res['commit_info'])."</span>" ; 
			} else {
				echo " <span style='color:#CC0000'>".__("The commit has ended but there is an error!","SL_framework")."</span>" ; 
				echo $svn->printRawResult($res['raw_result']) ; 
			}
			die() ; 
		}
		
		
		/** ====================================================================================================================================================
		* Callback for putting repo files in the local plugin
		* 
		* @access private
		* @return void
		*/		
		
		function svn_to_local() {
			// get the arguments
			$plugin = $_POST['plugin'] ;
			$files = $_POST['files'] ;
			echo "<div class='console' id='svn_console2'>\n" ; 
			echo __("Sending to the local files in progress...", "SL_framework")."<br/>----------<br/><br/>" ; 
						
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn/".$plugin."/" ;  ;

			//We add the created folder ... we order the folder to add by their string length because the shorter path should be add at first
			$fold2add = array() ; 
			foreach ($files as $f) {
				if ($f[1]=='add_folder') {
					$fold2add = array_merge($fold2add, array($f[0] => strlen($f[0])) ) ; 
				}
			}
			asort($fold2add) ; 
			foreach ($fold2add as $f => $n) {
				$url = WP_PLUGIN_DIR."/".$plugin."/".$f ; 
				if (@mkdir($url, 0777, true)) {
					echo "(A) ".$f." (folder) <span style='color:#669900'>OK</span><br/>" ; 
				} else {
					echo "(A) ".$f." (folder) <span style='color:#CC0000'>KO</span><br/>" ; 
				}
			}
			// Now we send/delete the files !!!!
			foreach ($files as $f) {
				$url = WP_PLUGIN_DIR."/".$plugin."/".$f[0] ; 
				if ($f[1]=='add') {
					if (@copy ($local_cache.$f[0], $url)) {
						echo "(A) ".$f[0]." <span style='color:#669900'>OK</span><br/>" ; 
					} else {
						echo "(A) ".$f[0]." <span style='color:#CC0000'>KO</span><br/>" ; 
					}
				}
				if ($f[1]=='modify') {
					if (@copy ($local_cache.$f[0], $url)) {
						echo "(U) ".$f[0]." <span style='color:#669900'>OK</span><br/>" ; 
					} else {
						echo "(U) ".$f[0]." <span style='color:#CC0000'>KO</span><br/>" ; 
					}
				}
				if ($f[1]=='delete') {
					if (@unlink($url)) {
						echo "(D) ".$f[0]." <span style='color:#669900'>OK</span><br/>" ; 
					} else {
						echo "(D) ".$f[0]." <span style='color:#CC0000'>KO</span><br/>" ; 
					}						
				}
			}
			
			//We delete the deleted folder ... we order (reverse) the folder to delete them by their string length because the shorter path should be add at last
			$fold2delete = array() ; 
			foreach ($files as $f) {
				if ($f[1]=='delete_folder') {
					$fold2delete = array_merge($fold2delete, array($f[0] => strlen($f[0])) ) ; 
				}
			}
			arsort($fold2delete) ; 
			foreach ($fold2delete as $f => $n) {
				$url = WP_PLUGIN_DIR."/".$plugin."/".$f ; 
				if (@unlink($url)) {
					echo "(D) ".$f." <span style='color:#669900'>OK</span><br/>" ; 
				} else {
					echo "(D) ".$f." <span style='color:#CC0000'>KO</span><br/>" ; 
				}						
			}
			echo " <span style='color:#669900'>".__("The rollback has ended ... ","SL_framework")."</span>" ; 

			echo "</div>\n" ; 
			echo "<script>jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);</script>" ; 
				
							
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for changing the version in the main php file
		* 
		* @access private
		* @return void
		*/		
		
		function svn_changeVersionReadme() {
			// get the arguments
			$plugin = $_POST['plugin'];
			
			$info = $this->get_plugins_data(WP_PLUGIN_DIR."/".$plugin.'/'.$plugin.'.php') ; 
			list($descr1, $descr2) = explode("</p>",$info['Description'],2) ; 

			$title = sprintf(__('Change the plugin version for %s', 'SL_framework'),'<em>'.$plugin.'</em>') ;
			
			$version = $info['Version'] ; 
			if ($version!="") {
				$entete = "<div id='readmeVersion'><h3>".__('Version number', 'SL_framework')."</h3>" ; 
				$entete .= "<p>".sprintf(__('The current version of the plugin %s is %s.', 'SL_framework'), "<code>".$plugin."/".$plugin.".php</code>", $version)."</p>" ; 
				$entete .= "<p>".__('Please modify the version:', 'SL_framework')." <input type='text' size='7' name='versionNumberModify' id='versionNumberModify' value='".$version."'></p>" ; 
				$entete .= "<h3>".__('Readme file', 'SL_framework')."</h3>" ; 
				$entete .= "<p>".sprintf(__('The current content of %s is:', 'SL_framework'), "<code>".$plugin."/readme.txt</code>")."</p>" ; 

				// We look now at the readme.txt
				$readme = strip_tags(@file_get_contents(WP_PLUGIN_DIR."/".$plugin."/readme.txt")) ; 
				
				// We detect the current version
				global $wp_version;
				preg_match("/^(\d+)\.(\d+)(\.\d+|)/", $wp_version, $hits);
				$root_tagged_version = $hits[1].'.'.$hits[2];
				$tagged_version = $root_tagged_version;
				if (!empty($hits[3])) $tagged_version .= $hits[3];

				// We construct the default text 
				$default_text = "=== ".$info['Plugin_Name']." ===\n" ; 
				$default_text .= "\n" ; 
				$default_text .= "Author: ".$info['Author']."\n" ; 
				$default_text .= "Contributors: ".$info['Author']."\n" ; 
				$default_text .= "Author URI: ".$info['Author_URI']."\n" ; 
				$default_text .= "Plugin URI: ".$info['Plugin_URI']."\n" ; 
				$default_text .= "Tags: ".$info['Plugin_Tag']."\n" ; 
				$default_text .= "Requires at least: 3.0\n" ; 
				$default_text .= "Tested up to: ".$tagged_version."\n" ; 
				$default_text .= "Stable tag: trunk\n" ; 
				$default_text .= "\n" ; 
				$default_text .= strip_tags($descr1)."\n" ; 
				$default_text .= "\n" ; 
				$default_text .= "== Description ==\n" ; 
				$default_text .= "\n" ; 
				// Change the description form
				$descr2 = str_replace("<li>", "* ", $descr2 ) ; 
				$descr2 = str_replace("<b>", "*", $descr2 ) ; 
				$descr2 = str_replace("</b>", "*", $descr2 ) ; 
				$descr2 = str_replace("</li>", "\n", $descr2 ) ; 
				$descr2 = str_replace("</ul>", "\n", $descr2 ) ; 
				$descr2 = str_replace("</p>", "\n\n", $descr2 ) ; 
				$default_text .= strip_tags($descr1)."\n\n".strip_tags($descr2);
				$default_text .= "= Localization =\n" ; 
				$default_text .= "\n" ; 
				$list_langue = translationSL::list_languages($plugin) ; 
				foreach ($list_langue as $l) {
					$default_text .= "* ".$l."\n" ; 
				}
				$default_text .= "\n" ; 
				$default_text .= "= Features of the framework =\n" ; 
				$default_text .= "\n" ; 
				$default_text .= @file_get_contents(dirname(__FILE__)."/core/data/framework.info"); 
				$default_text .= "\n" ; 
				$default_text .= "\n" ; 
				$default_text .= "== Installation ==\n" ; 
				$default_text .= "\n" ; 
				$default_text .= "1. Upload this folder to your plugin directory (for instance '/wp-content/plugins/')\n" ; 
				$default_text .= "2. Activate the plugin through the 'Plugins' menu in WordPress\n" ; 
				$default_text .= "3. Navigate to the 'SL plugins' box\n" ; 
				$default_text .= "4. All plugins developed with the SL core will be listed in this box\n" ; 
				$default_text .= "5. Enjoy !\n" ; 
				$default_text .= "\n" ; 
				$default_text .= "== Screenshots ==\n" ; 
				$default_text .= "\n" ; 
				// We look for the screenshots
				if (preg_match("/== Screenshots ==(.*)== Changelog ==/s", $readme, $match)) {
					$screen = explode("\n", $match[1]) ; 
					for ($i=1; $i<20 ; $i++) {
						if ( (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.png')) ||  (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.gif')) ||  (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.jpg')) ||  (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.bmp')) ) {
							$found = false ; 
							foreach($screen as $s) {
								if (preg_match("/^".$i."[.]/s", $s)) {
									$found = true ; 
									$default_text .= $s."\n" ; 
								}
								

							}
							if (!$found) {
								$default_text .= $i.". (empty)\n" ; 
							}
						}
					}
				}
				$default_text .= "\n" ; 
				$default_text .= "== Changelog ==" ; 
				// We copy what the readmefile contains
				if (preg_match("/== Changelog ==(.*)== Frequently Asked Questions ==/s", $readme, $match)) {
					$default_text .= $match[1] ; 
				}
				$default_text .= "== Frequently Asked Questions ==" ; 
				// We copy what the readmefile contains
				if (preg_match("/== Frequently Asked Questions ==(.*)InfoVersion/s", $readme, $match)) {
					$default_text .= $match[1] ; 
				}
				// We recopy the infoVersion ligne

				$default_text .= "InfoVersion:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n" ; 



				$content = "<p><textarea id='ReadmeModify' rows='".(count(explode("\n", $readme))+1)."' cols='100%'>".$readme."</textarea></p>" ; 
				$content .= "<p id='svn_button'><input onclick='saveVersionReadme(\"".$plugin."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Save these data', 'SL_framework')."' /><img id='wait_save' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p></div>" ;  
			
				$default_text = "<p><textarea id='ReadmePropose' rows='".(count(explode("\n", $readme))+1)."' cols='100%'>".$default_text."</textarea></p>" ; 
				
				$table = new adminTable() ;
				$table->title(array(__("The current text", "SL_framework"), __("The proposed text", "SL_framework")) ) ;
				$cel1 = new adminCell($content) ;
				$cel2 = new adminCell($default_text) ;
				$table->add_line(array($cel1, $cel2), '1') ;
				$content = $entete.$table->flush() ;
			} else {
				$content = "<div class='error fade'><p>".sprintf(__('There is a problem with the header of %s. It appears that there is no Version header.', 'SL_framework'), "<code>".$plugin."/".$plugin.".php</code>")."</p></div>"; 
			
			}
			
			$current_core_used = str_replace(WP_PLUGIN_DIR."/",'',dirname(__FILE__)) ; 
			$current_fingerprint_core_used = md5($this->checkCoreOfThePlugin(WP_PLUGIN_DIR."/".$current_core_used."/core.php")) ; 
			$popup = new popupAdmin($title, $content, "", "coreInfo('corePlugin_".md5($plugin."/".$plugin.".php")."', '".$plugin."/".$plugin.".php', '".$plugin."' , '".$current_core_used."', '".$current_fingerprint_core_used."', '".$info['Author']."') ; ") ; 
			$popup->render() ; 

			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for viewing the differences between the current files and repository
		* 
		* @access private
		* @return void
		*/		
		
		function svn_saveVersionReadme() {
			// get the arguments
			$plugin = $_POST['plugin'];
			$readme = $_POST['readme'];
			$version = $_POST['version'];
			
			// We clean the readme before saving it
			$readme = str_replace("\\'", "'", $readme) ; 
			$readme = str_replace('\\"', '"', $readme) ; 
			$readme = str_replace('<', '&lt;', $readme) ; 
			$readme = str_replace('>', '&gt;', $readme) ; 
			
			// We save the readme
			@file_put_contents(WP_PLUGIN_DIR."/".$plugin."/readme.txt", $readme) ; 
			
			// We save the version
			$lines = @file(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
			$save = "" ; 
			foreach ($lines as $l) {
				//if ($save!="") {
				//	$save .= "\r\n" ; 
				//}
				if (preg_match("/^Version:(.*)$/i", $l, $match)) {
					$save .= "Version: ".$version."\r\n" ; 
				} else {
					$save .= $l ; 
				}
			}
			@file_put_contents(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php", $save) ; 
			
			echo "<div class='updated fade'><p>".__('The data has been saved. You may close this window.', 'SL_framework')."</p></div>"; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for viewing the differences between the current files and repository
		* 
		* @access private
		* @return void
		*/		
		
		function svn_viewDifferences() {
			die() ; 
		}
	}
	
	/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
	* This PHP class has only for purpose to fake a plugin class and allow parameters administration for the framework.
	* 
	*/
	class coreSLframework extends pluginSedLex {
	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;
	
		/**====================================================================================================================================================
		* Constructor
		*
		* @return void
		*/
		function coreSLframework() {
			$this->path = __FILE__ ; 
			$this->pluginID = get_class() ; 
		}
	
		
		/** ====================================================================================================================================================
		* Define the default option values of the framework
		* 
		* @param string $option the name of the option
		* @return variant of the option
		*/
		public function get_default_option($option) {
			switch ($option) {
				// Alternative default return values (Please modify)
				case 'adv_param' 		: return false 		; break ; 
				case 'adv_doc' 			: return false 		; break ; 
				case 'adv_svn_login' 	: return "" 		; break ; 
				case 'adv_svn_pwd' 		: return "" 		; break ; 
				case 'adv_svn_author' 	: return "" 		; break ; 
				case 'adv_update_trans'	: return false		; break ; 
				case 'lang' 			: return "" 		; break ; 
			}
			return null ;
		}	
	}

}
						


?>