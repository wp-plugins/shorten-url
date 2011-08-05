<?php
/**
* Core SedLex Plugin
* VersionInclude : 3.0
*/ 

require_once('core/admin_table.class.php') ; 
require_once('core/tabs.class.php') ; 
require_once('core/box.class.php') ; 
require_once('core/parameters.class.php') ; 
require_once('core/phpdoc.class.php') ; 
require_once('core/utils.class.php') ; 
require_once('core/zip.class.php') ; 

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
				$page = add_submenu_page($topLevel, __('About...', $this->pluginID), __('About...', $this->pluginID), 10, $topLevel, array($this,'sedlex_information'));

				add_action('admin_print_scripts-'.$page, array($this,'javascript_admin_always'),5);
				add_action('admin_print_styles-'.$page, array($this,'css_admin_always'),5);
				
				add_action('admin_print_scripts-'.$page, array( $this, 'flush_js'), 10000000);
				add_action('admin_print_styles-'.$page, array( $this, 'flush_css'), 10000000);
			}
		
			//add sub menus
			$page = add_submenu_page($topLevel, __($this->pluginName, $this->pluginID), __($this->pluginName, $this->pluginID), 10, $plugin, array($this,'configuration_page'));
			
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
					array( '<a href="admin.php?page='.$plugin.'">'. __('Settings') .'</a>')
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
			load_plugin_textdomain($this->pluginID, WP_PLUGIN_URL.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) . 'lang');
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
				if (mkdir("$path", 0700, true)) {
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
				if (filesize($path)>0) {
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
		
			$dir = opendir(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'); 
			while($file = readdir($dir)) {
				if (preg_match('@\.js$@i',$file)) {
					$path = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'.$file ; 
					$url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'.$file ; 
					if (filesize($path)>0) {
						$this->add_js($url) ; 
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
				if (filesize($path)>0) {
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
				if (mkdir("$path", 0700, true)) {
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
				if (filesize($path)>0) {
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
		
			$dir = opendir(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'); 
			while($file = readdir($dir)) {
				if (preg_match('@\.css$@i',$file)) {
					$path = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'.$file ; 
					$url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'.$file ; 
					if (filesize($path)>0) {
						$this->add_css($url) ; 
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
				if (filesize($path)>0) {
					$this->add_css($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* This function display the configuration page of the core 
		* 
		* @access private
		* @return void
		*/
		function sedlex_information() {
			global $submenu;
			
			if (isset($_GET['download'])) {
				$this->getPluginZip($_GET['download']) ; 
			}
				
				//Information about the SL plugins
			?>
				<div class="wrap">
					<div id="icon-themes" class="icon32"><br></div>
					<h2><?php echo __('Summary page for the plugins developped with the SL framework', $this->pluginID)?></h2>
					<?php echo $this->signature; ?>
					<p>&nbsp;</p>
					<!--debut de personnalisation-->
					<?php
					$plugins = get_plugins() ; 
					$sl_count = 0 ; 
					foreach ($submenu['sedlex.php'] as $ov) {
						$sl_count ++ ; 
					}
?>
					<p>For now, you have installed <?php echo count($plugins) ?> plugins including <b><?php echo $sl_count ; ?> plugins developped with the "SL framework developpment"</b> for plugins:<p/>
<?php
					//======================================================================================
					//= Tab listing all the plugins
					//======================================================================================
					
					$tabs = new adminTabs() ; 
					ob_start() ; 
					
						$table = new adminTable() ; 
						$table->title(array(__("Plugin name", $this->pluginID), __("Description", $this->pluginID), __("Status", $this->pluginID))) ; 
						
						foreach ($submenu['sedlex.php'] as $i => $ov) {

							$ligne++ ; 

							$url = $ov[2] ; 
							
							
							
							if ($i != 0) {
								$info = $this->get_plugins_data(WP_PLUGIN_DIR."/".$url);
								ob_start() ; 
								?>
											<p><b><?php echo $info['Plugin_Name'] ; ?></b></p>
											<p><a href='admin.php?page=<?php echo $url  ; ?>'><?php echo __('Settings') ; ?></a> | <?php echo Utils::byteSize(Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>

								<?php
								$cel1 = new adminCell(ob_get_clean()) ; 
								ob_start() ; 
								?>
											<p><?php echo $info['Description'] ; ?></p>
											<p>Version : <?php echo $info['Version'] ; ?> by <?php echo $info['Author'] ; ?> (<a href='<?php echo $info['Author_URI'] ; ?>'><?php echo $info['Author_URI'] ; ?></a>)</p>

								<?php
								$cel2 = new adminCell(ob_get_clean()) ; 
								$cel3 = new adminCell($this->checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR.'/'.$url )."/core.php") ) ; 
						
								$table->add_line(array($cel1, $cel2, $cel3), '1') ; 
							}
						}
						echo $table->flush() ; 
					
					$tabs->add_tab(__('List of SL plugins',  $this->pluginID), ob_get_clean() ) ; 
					
					//======================================================================================
					//= Tab with a zip file for downloading an empty plugin with a quick tuto
					//======================================================================================
					
					ob_start() ; 
					?>
		
					<div class="adminPost">
					
					<p>The following description proposes a quick tutorial about how to create a plugin with the SL framework.</p>
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
					$tabs->add_tab(__('How to create a new Plugin with the SL framework',  $this->pluginID), ob_get_clean() ) ; 
					
					//======================================================================================
					//= Tab presenting the core documentation
					//======================================================================================
										
					ob_start() ; 
						$rc = new phpDoc(WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) ."core.php");
						$classes = $rc->parse() ; 
						
						// On liste les fichiers includer par le fichier courant
						$fichier_master = dirname(__FILE__)."/core.php" ; 
						
						$lines = file($fichier_master) ;
					
						foreach ($lines as $lineNumber => $lineContent) {
							if (preg_match('/^require.*\([\'"]core\/(.*)[\'"]\)/',  trim($lineContent),$match)) {
								$chem = dirname(__FILE__)."/core/".$match[1] ; 
								$rc = new phpDoc($chem);
								$classes = array_merge($classes, $rc->parse()) ; 
							}
						}
						
						$this->printDoc($classes) ; 

					$tabs->add_tab(__('Framework documentation',  $this->pluginID), ob_get_clean() ) ; 
					

			
					echo $tabs->flush() ; 
					

					echo '<p style="text-align:right;font-size:75%;">The core file used for the SedLex plugins is "'.__FILE__.'"</p>' ; 
					echo $this->signature; 
					
					
					
					?>
				</div>
				<?php
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
					mkdir("$path", 0700, true) ; 
				}
				
				// Copy static files
				Utils::copy_rec($plugin_dir.'/core/templates/css',$path.'/css') ; 
				Utils::copy_rec($plugin_dir.'/core/templates/js',$path.'/js') ; 
				Utils::copy_rec($plugin_dir.'/core/templates/img',$path.'/img') ; 
				Utils::copy_rec($plugin_dir.'/core/templates/lang',$path.'/lang') ; 
				Utils::copy_rec($plugin_dir.'/core',$path.'/core') ; 
				Utils::copy_rec($plugin_dir.'/core.php',$path."/core.php") ; 
				
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
				$zip = new Zip() ; 
				chdir(WP_CONTENT_DIR."/sedlex/new_plugins_zip/") ;
				$zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE); 
				$zip->addDir($folder_name) ; 
				$zip->close() ; 
				
				// Stream the file to the client
				header("Content-Type: application/zip");
				header("Content-Length: " . filesize($file));
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
			echo "<p class='descclass_phpDoc'>Please find hearafter all the possible classes and methods for the development with this framework</p>" ; 
			echo "<ul>" ; 
			foreach ($rc as $name => $cl) {
				echo "<li><b><a href='#class_".$name."'>".$name."</a></b></li>" ; 
				echo "<ul>" ; 
					foreach ($cl['methods'] as $name_m => $method) {
						if (($method['access']!='private')&&($method['return']!="")) {
							echo "<li><a href='#".$name."_".$name_m."'>".$name_m."</a></li>" ; 
						}
					}				
				echo "</ul>" ; 
			}		
			echo "</ul>" ; 

			foreach ($rc as $name => $cl) {
				echo "<p class='class_phpDoc'><a name='class_".$name."'></a>$name <span class='desc_phpDoc'>[CLASS]</span></p>" ; 
				
				$cl['description']['comment'] = wp_kses($cl['description']['comment'], $allowedtags);
				$cl['description']['comment'] = explode("\n", $cl['description']['comment'] ) ; 
				foreach($cl['description']['comment'] as $c) {
					if (trim($c)!="") 
						echo "<p class='descclass_phpDoc'>".trim($c)."</p>" ; 
				}
				echo "<p class='descclass_phpDoc'>Here is the method of the class : </p>" ; 
				
				// Print the summary of the method
				echo "<ul>" ; 
				foreach ($cl['methods'] as $name_m => $method) {
					if (($method['access']!='private')&&($method['return']!="")) {
						echo "<li><a href='#".$name."_".$name_m."'>".$name."::".$name_m."</a></li>" ; 
					}
				}				
				echo "</ul>" ; 
				
				foreach ($cl['methods'] as $name_m => $method) {
					
					if (($method['access']!='private')&&($method['return']!="")) {
						echo "<p class='method_phpDoc'><a name='".$name."_".$name_m."'></a>$name_m <span class='desc_phpDoc'>[METHOD]</span></p>" ; 
						
						echo "<p class='comment_phpDoc'>";
						echo __('Description:',$this->pluginID) ; 
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
							echo __('Parameters:',$this->pluginID) ; 
							echo "</p>" ; 
							
							
							
							foreach ($method['param'] as $p) {
								echo "<p class='param_each_phpDoc'>" ; 
								if (isset($p['default'])) {
									echo "<b>$".$p['name']."</b> [optional] (<i>".$p['type']."</i>) ".$p['description']." (by default, its value is: ".htmlentities($p['default']).") "; 
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
							echo __('Parameters: No param',$this->pluginID) ; 
							echo "</p>" ; 
						}
						
						$typical = $typical.") ; " ; 
						
						$return = explode(" ",$method['return']." ",2) ; 
						echo "<p class='return_phpDoc'>".__('Return value:',$this->pluginID)."</p>" ; 
						echo "<p class='return_each_phpDoc'><b>".$return[0]."</b> ".trim($return[1])."</p>" ; 
						$typical = $return[0].$typical ; 
						
						$echo = ob_get_clean() ; 
						
						echo "<p class='typical_phpDoc'>".__('Typical call:',$this->pluginID)."</p>" ; 
						echo "<p class='typical_each_phpDoc'><code>".$typical."</code></p>" ; 
						echo $echo ; 
						
						if ($method['see'] !="") {
							echo "<p class='see_phpDoc'>".__('See also:',$this->pluginID)."</p>" ; 
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
			$version = wp_kses($version, $plugins_allowedtags);
			
			return array('Dir_Plugin'=>basename(dirname($plugin_file)) , 'Plugin_Name' => $plugin_name, 'Plugin_URI' => $plugin_uri, 'Description' => $description, 'Author' => $author, 'Author_URI' => $author_uri, 'Version' => $version);
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
				$resultat .= "<p ".$style.">Version du 'core.php' : ??</p>" ; 
			} else {
			
				$lines = file($path);
				// On parcourt le tableau $lines et on affiche le contenu de chaque ligne 
				$ok = false ; 
				foreach ($lines as $lineNumber => $lineContent) {
					if (preg_match('/VersionInclude/',  $lineContent)) {
						$tmp = explode(':',$lineContent) ; 
						$resultat .= "<p ".$style.">Version du 'core.php' : ".trim($tmp[1])."" ; 
						$ok = true ; 
						break ; 
					}
				}
				if (!$ok) {
					$resultat .= "<p ".$style.">Version du 'core.php' : ??" ; 
				}
				$resultat .= " (".filesize($path)." octets)</p>" ; 
			}
			
			
			$resultat .= "<hr/>\n" ; 
			
			// On liste les fichiers includer par le fichier courant
			$fichier_master = dirname(__FILE__)."/core.php" ; 
			$lines = file($fichier_master) ;
			
			foreach ($lines as $lineNumber => $lineContent) {
				if (preg_match('/^require.*\([\'"]core\/(.*)[\'"]\)/',  trim($lineContent),$match)) {
					$chem = dirname($path)."/core/".$match[1] ; 
					
					if (!file_exists($chem)) {
						$version = "(file not found)" ; 
						$taille = "" ; 
					} else {
						$lines = file($chem);
						// On parcourt le tableau $lines et on affiche le contenu de chaque ligne 
						$ok = false ; 
						foreach ($lines as $lineNumber => $lineContent) {
							if (preg_match('/Version/',  $lineContent)) {
								$tmp = explode(':',$lineContent) ; 
								$version = trim($tmp[1]) ; 
								$ok = true ; 
								break ; 
							}
						}
						if (!$ok) {
							$version = "??" ; 
						}
						$taille = " (".filesize($chem)." octets)" ; 
					}
					
					
					
					$resultat .= "<p ".$style.">Version du '/core/".$match[1] ."' : $version $taille</p>" ; 
				}
			}
	
			return $resultat ; 
		} 
	}
}




?>