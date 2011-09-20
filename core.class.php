<?php
/**
* Core SedLex Plugin
* VersionInclude : 3.0
*/ 

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
			
			// We add an ajax call for the feedback classe
			add_action('wp_ajax_send_feedback', array('feedbackSL','send_feedback')) ; 
			
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
					$number = "<span class='update-plugins count-1' title='title'><span class='update-count'>".$number."</span></span>" ; 
				}
			}
			$page = add_submenu_page($topLevel, $this->pluginName, $this->pluginName . $number, 10, $plugin, array($this,'configuration_page'));
			
			// Different actions

			
			add_action('admin_print_scripts-'.$page, array($this,'javascript_admin'));
			add_action('admin_print_styles-'.$page, array($this,'css_admin'));
			
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
			if (isset($_POST['showhide_advanced'])) {
				if ($_POST['show_advanced']=="true") {
					update_option('SL_framework_show_advanced', true) ; 
					echo "<div class='updated  fade'><p>" ; 
					echo __("The advanced options and output will be displayed now !",'SL_framework') ; 
					echo "</p></div>" ; 
				} else {
					update_option('SL_framework_show_advanced', false) ; 
					echo "<div class='updated  fade'><p>".__('The advanced options and output will be hidden now !','SL_framework')."</p></div>" ; 
				}
			}
			if (isset($_POST['showhide_developpers'])) {
				if ($_POST['show_developpers']=="true") {
					update_option('SL_framework_developpers', true) ; 
					echo "<div class='updated  fade'><p>" ; 
					echo __("The developpers documentations will be displayed now !",'SL_framework') ; 
					echo "</p></div>" ; 
				} else {
					update_option('SL_framework_developpers', false) ; 
					echo "<div class='updated  fade'><p>".__('The developpers documentations will be hidden now !','SL_framework')."</p></div>" ; 
				}
			}

			if (isset($_GET['download'])) {
				$this->getPluginZip($_GET['download']) ; 
			}
			$current_core_used = str_replace(WP_PLUGIN_DIR."/",'',dirname(__FILE__)) ; 
			
			if (get_option('SL_framework_show_advanced', false)){
				$current_fingerprint_core_used = $this->checkCoreOfThePlugin(WP_PLUGIN_DIR."/".$current_core_used."/core.php") ; 
			}
			
			if (isset($_GET['update'])) {
				$path_to_update = base64_decode($_GET['update']) ; 
				$path_from_update = base64_decode($_GET['from']) ; 

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
				echo "<div class='updated  fade'><p>".sprintf(__('%s has been updated with %s !','SL_framework'), $path_to_update, $path_from_update)."</p>" ; 
				

				echo "<p>".sprintf(__('Please click %shere%s to refresh the page and ensure everything is ok!','SL_framework'), "<a href='".remove_query_arg(array("update", "from"))."'>","</a>")."</p></div>" ; 
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
						if (get_option('SL_framework_show_advanced', false)){
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
								if (get_option('SL_framework_show_advanced', false)){
									$info_core = $this->checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR.'/'.$url )."/core.php") ; 
									$hash_plugin = $this->update_hash_plugin(dirname(WP_PLUGIN_DIR."/".$url)) ; 
								}
								$info = $this->get_plugins_data(WP_PLUGIN_DIR."/".$url);
								ob_start() ; 
								?>
									<p><b><?php echo $info['Plugin_Name'] ; ?></b></p>
									<p><a href='admin.php?page=<?php echo $url  ; ?>'><?php echo __('Settings', 'SL_framework') ; ?></a> | <?php echo Utils::byteSize(Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>

								<?php
								
									if (get_option('SL_framework_show_advanced', false)){
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
												$version_on_wordpress = $res->version ; 
												if ($version_on_wordpress != $info['Version']) {
													echo "<p style='color:#660000'>".sprintf(__("This plugin is hosted by wordpress repository and is not up-to-date ! (i.e. %s)", 'SL_framework' ),$version_on_wordpress)." <a href='http://www.wordpress.org/extend/plugins/".$plugin_name."/'>".__('(the repository)', 'SL_framework')."</a></p>" ; 
												} else {
													// We search in the FAQ section if the same hash is found
													if (strpos($res->sections['faq'], $hash_plugin)===false) {
														echo "<p style='color:#660000'>".sprintf(__("This plugin is hosted by wordpress repository with the same version but the plugin is not exactly the same", 'SL_framework' ),$version_on_wordpress)." <a href='http://www.wordpress.org/extend/plugins/".$plugin_name."/'>".__('(the repository)', 'SL_framework')."</a></p>" ; 
													} else {
														echo "<p style='color:#006600'>".sprintf(__("This plugin is hosted by wordpress repository and is up-to-date !", 'SL_framework' ),$version_on_wordpress)." <a href='http://www.wordpress.org/extend/plugins/".$plugin_name."/'>".__('(the repository)', 'SL_framework')."</a></p>" ; 
													}
												}
												echo  "<p>InfoVersion: ".$hash_plugin."</p>" ; 
												echo  "<p>".__('Last update:', 'SL_framework' )." ".$res->last_updated."</p>";
												echo  "<p>".__('Rating:', 'SL_framework' )." ".$res->rating." (".sprintf(__("by %s persons", 'SL_framework' ),$res->num_ratings).")</p>";
												echo  "<p>".__('Number of download:', 'SL_framework' )." ".$res->downloaded."</p>";
											
											}

										}
									}
									

								$cel1 = new adminCell(ob_get_clean()) ; 
								
								ob_start() ; 
									?>
									<p><?php echo str_replace("<ul>", "<ul style='list-style-type:circle; padding-left:1cm;'>", $info['Description']) ; ?></p>
									<p><?php echo sprintf(__('Version: %s by %s', 'SL_framework'),$info['Version'],$info['Author']) ; ?> (<a href='<?php echo $info['Author_URI'] ; ?>'><?php echo $info['Author_URI'] ; ?></a>)</p>
									<?php
								$cel2 = new adminCell(ob_get_clean()) ; 
								
								if (get_option('SL_framework_show_advanced', false)){
									
									if ($current_fingerprint_core_used != $info_core) {
										$info_core = str_replace('#666666','#660000',$info_core) ;  
										$info_core .= "<p style='color:#666666;font-size:75%;text-align:right'><a href='".add_query_arg(array("update"=>base64_encode($url), "from"=>base64_encode($current_core_used."/".current_core_used.".php")))."'>".sprintf(__('Update with the core of the %s plugin (only if you definitely know what you do)', 'SL_framework'), $current_core_used)."</a></p>" ;  
									}
									
									if ($url == $current_core_used."/".$current_core_used.".php") {
										$info_core .= "<p style='color:#666666;font-size:75%;text-align:right'>[".__('This core is currently used by the framework and plugins !',  'SL_framework')."]</p>" ; 
									} 
									$cel3 = new adminCell( $info_core ) ; 
								}
								
								if (get_option('SL_framework_show_advanced', false)){
									$table->add_line(array($cel1, $cel2, $cel3), '1') ; 
								} else {
									$table->add_line(array($cel1, $cel2), '1') ; 
								}
							}
						}
						echo $table->flush() ; 
						
						
						echo "<form action='".remove_query_arg(array("update", "from"))."' method='POST'>" ; 
						$checked = "" ; 
						if (get_option('SL_framework_show_advanced', false)==true) {
							$checked = "checked" ;
						}
						echo "<p style='text-align:right'>".__('Show the advanced options and parameters:','SL_framework')." <input name='show_advanced' value='true' type='checkbox' $checked> "  ; 
						echo "<input class='button-secondary action' name='showhide_advanced' id='showhide_advanced' value='Show/Hide' type='submit' ></p>"  ; 
						echo "</form>" ; 
						echo "<form action='".remove_query_arg(array("update", "from"))."' method='POST'>" ; 
						$checked = "" ; 
						if (get_option('SL_framework_developpers', false)==true) {
							$checked = "checked" ;
						}
						echo "<p style='text-align:right'>".__('Show the developpers documentation:','SL_framework')." <input name='show_developpers' value='true' type='checkbox' $checked> "  ; 
						echo "<input class='button-secondary action' name='showhide_developpers' id='showhide_developpers' value='Show/Hide' type='submit' ></p>"  ; 
						echo "</form>" ; 						
					$tabs->add_tab(__('List of SL plugins',  'SL_framework'), ob_get_clean() ) ; 
					
					
					if (get_option('SL_framework_developpers', false)==true) {
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
						$tabs->add_tab(__('How to create a new Plugin with the SL framework',  'SL_framework'), ob_get_clean() ) ; 
						
						//======================================================================================
						//= Tab presenting the core documentation
						//======================================================================================
											
						ob_start() ; 
							//$rc = new phpDoc(WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) ."core.php");
							//$classes = $rc->parse() ; 
							$classes = array() ; 
							
							// On liste les fichiers includer par le fichier courant
							$fichier_master = dirname(__FILE__)."/core.php" ; 
							
							$lines = file($fichier_master) ;
						
							foreach ($lines as $lineNumber => $lineContent) {	
								if (preg_match('/^require.*[\'"](.*)[\'"]/',  trim($lineContent),$match)) {
									$chem = dirname(__FILE__)."/".$match[1] ;
									$rc = new phpDoc($chem);
									$classes = array_merge($classes, $rc->parse()) ; 
								}
							}
							
							$this->printDoc($classes) ; 

						$tabs->add_tab(__('Framework documentation',  'SL_framework'), ob_get_clean() ) ; 
					}
					//======================================================================================
					//= Tab for the translation
					//======================================================================================
										
					ob_start() ; 
						$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
						$trans = new translationSL("SL_framework", $plugin) ; 
						$trans->enable_translation() ; 
					$tabs->add_tab(__('Manage translation of the framework',  'SL_framework'), ob_get_clean() ) ; 
								
					echo $tabs->flush() ; 
					
					echo $this->signature; 
					
					?>
				</div>
				<?php
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
		* Print the documentation of classes
		* 
		* @access private
		* @param array $rc the array containing the phpDoc format 
		* @return void
		*/
		
		private function printDoc($rc)  {
			$allowedtags = array('a' => array('href' => array()),'code' => array(), 'p' => array() ,'br' => array() ,'ul' => array() ,'li' => array() ,'strong' => array());
		
			// Print the summary of the method
			echo "<p class='descclass_phpDoc'>".__('Please find hearafter all the possible classes and methods for the development with this framework', 'SL_framework')."</p>" ; 
			echo "<ul>" ; 
			foreach ($rc as $name => $cl) {
				echo "<li class='li_class'><b><a href='#class_".$name."'>".$name."</a></b></li>" ; 
				echo "<ul>" ; 
					foreach ($cl['methods'] as $name_m => $method) {
						if (($method['access']!='private')&&($method['return']!="")) {
							echo "<li class='li_method'><a href='#".$name."_".$name_m."'>".$name_m."</a></li>" ; 
						}
					}				
				echo "</ul>" ; 
			}		
			echo "</ul>" ; 

			foreach ($rc as $name => $cl) {
				echo "<p class='class_phpDoc'><a name='class_".$name."'></a>$name <span class='desc_phpDoc'>".__('[CLASS]', 'SL_framework')."</span></p>" ; 
				
				$cl['description']['comment'] = wp_kses($cl['description']['comment'], $allowedtags);
				$cl['description']['comment'] = explode("\n", $cl['description']['comment'] ) ; 
				foreach($cl['description']['comment'] as $c) {
					if (trim($c)!="") 
						echo "<p class='descclass_phpDoc'>".trim($c)."</p>" ; 
				}
				echo "<p class='descclass_phpDoc'>".__('Here is the method of the class:', 'SL_framework')."</p>" ; 
				
				// Print the summary of the method
				echo "<ul>" ; 
				foreach ($cl['methods'] as $name_m => $method) {
					if (($method['access']!='private')&&($method['return']!="")) {
						echo "<li class='li_method'><a href='#".$name."_".$name_m."'>".$name."::".$name_m."</a></li>" ; 
					}
				}				
				echo "</ul>" ; 
				
				foreach ($cl['methods'] as $name_m => $method) {
					
					if (($method['access']!='private')&&($method['return']!="")) {
						echo "<p class='method_phpDoc'><a name='".$name."_".$name_m."'></a>$name_m <span class='desc_phpDoc'>".__('[METHOD]', 'SL_framework')."</span></p>" ; 
						
						echo "<p class='comment_phpDoc'>";
						echo __('Description:','SL_framework') ; 
						echo "</p>" ; 

						$method['comment'] = wp_kses($method['comment'], $allowedtags);
						$method['comment'] = explode("\n", $method['comment']) ; 
						$typical = " $name_m (" ; 
						
						foreach($method['comment'] as $c) {
							if (trim($c)!="") 
								echo "<p class='comment_each_phpDoc'>".trim($c)."</p>" ; 
						}
						
						ob_start() ;
						if (count($method['param'])>0) {
							echo "<p class='parameter_phpDoc'>" ; 
							echo __('Parameters:','SL_framework') ; 
							echo "</p>" ; 
							
							
							
							foreach ($method['param'] as $p) {
								echo "<p class='param_each_phpDoc'>" ; 
								if (isset($p['default'])) {
									if (is_array($p['default'])) 
										$p['default'] = "[".implode(", ", $p['default'])."]" ; 
									echo "<b>$".$p['name']."</b> ".__('[optional]', 'SL_framework')." (<i>".$p['type']."</i>) ".$p['description']." ".__('(by default, its value is:', 'SL_framework')." ".htmlentities($p['default']).") "; 
								} else{
									echo "<b>$".$p['name']."</b> (<i>".$p['type']."</i>) ".$p['description'] ; 
								}
								
								echo "</p>" ; 
								if ($p['position']>0)
									$typical = $typical.', ' ; 
								if (isset($p['default'])) {
									$typical = $typical."[$".$p['name']."]" ; 
								} else {
									$typical = $typical."$".$p['name'] ; 
								}
							}
						} else {
							echo "<p class='parameter_phpDoc'>" ; 
							echo __('Parameters:','SL_framework')." ".__('No param','SL_framework') ; 
							echo "</p>" ; 
						}
						
						$typical = $typical.") ; " ; 
						
						$return = explode(" ",$method['return']." ",2) ; 
						echo "<p class='return_phpDoc'>".__('Return value:','SL_framework')."</p>" ; 
						echo "<p class='return_each_phpDoc'><b>".$return[0]."</b> ".trim($return[1])."</p>" ; 
						$typical = $return[0].$typical ; 
						
						$echo = ob_get_clean() ; 
						
						echo "<p class='typical_phpDoc'>".__('Typical call:','SL_framework')."</p>" ; 
						echo "<p class='typical_each_phpDoc'><code>".$typical."</code></p>" ; 
						echo $echo ; 
						
						if ($method['see'] !="") {
							echo "<p class='see_phpDoc'>".__('See also:','SL_framework')."</p>" ; 
							if (is_array($method['see'] )) {
								foreach ($method['see'] as $s) {
									echo "<p class='see_each_phpDoc'><a href='#".str_replace('::','_',$s)."'>".$s."</a></p>" ; 
								}
							} else {
								echo "<p class='see_each_phpDoc'><a href='#".str_replace('::','_',$method['see'] )."'>".$method['see'] ."</a></p>" ; 
							}
						}
					}
				}
			}
		}
		
		
		
		
		/** ====================================================================================================================================================
		* Get information on the plugin
		* For instance <code> $info = get_plugins_data(WP_PLUGIN_DIR.'my-plugin/my-plugin.php')</code> will return an array with 
		* 	- the folder of the plugin : <code>$info['Dir_Plugin']</code>
		* 	- the name of the plugin : <code>$info['Plugin_Name']</code>
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
			$plugin_uri = wp_kses(trim($plugin_uri[1]), $plugins_allowedtags);
			$description = wp_kses(wptexturize(trim($description[1])), $plugins_allowedtags);
			$author = wp_kses(trim($author_name[1]), $plugins_allowedtags);
			$author_uri = wp_kses(trim($author_uri[1]), $plugins_allowedtags);;
			$author_email = wp_kses(trim($author_email[1]), $plugins_allowedtags);;
			$framework_email = wp_kses(trim($framework_email[1]), $plugins_allowedtags);;
			$version = wp_kses($version, $plugins_allowedtags);
			
			return array('Dir_Plugin'=>basename(dirname($plugin_file)) , 'Plugin_Name' => $plugin_name, 'Plugin_URI' => $plugin_uri, 'Description' => $description, 'Author' => $author, 'Author_URI' => $author_uri, 'Email' => $author_email, 'Framework_Email' => $framework_email, 'Version' => $version);
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
			
			// On regarde le fichier include pour connaitre la version du core
			if (!file_exists($path)) {
				$resultat .= "<p ".$style.">".__('Version of','SL_framework')." core.php: ??</p>" ; 
			} else {
			
				$lines = file($path);
				// On parcourt le tableau $lines et on affiche le contenu de chaque ligne 
				$ok = false ; 
				foreach ($lines as $lineNumber => $lineContent) {
					if (preg_match('/VersionInclude/',  $lineContent)) {
						$tmp = explode(':',$lineContent) ; 
						$resultat .= "<p ".$style.">".__('Version of','SL_framework')." 'core.php' : ".trim($tmp[1])."" ; 
						$ok = true ; 
						break ; 
					} 
				}  
				if (!$ok) {
					$resultat .= "<p ".$style.">".__('Version of','SL_framework')." 'core.php' : ??" ; 
				}
			}
			
			
			$resultat .= "<hr/>\n" ; 
			
			// We compute the hash of the core folder
			$md5 = Utils::md5_rec(dirname($path).'/core/', array('SL_framework.pot')) ; 
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
			
			$resultat .= "<p ".$style.">".__('MD5 fingerprint of the framework:','SL_framework')." $md5</p>" ; 
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
					if (strpos(".", basename($f[0]))!==false) {
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
	}

}




?>