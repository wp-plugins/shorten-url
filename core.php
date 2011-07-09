<?php
/*
Core SedLex Plugin
VersionInclude : 2.1
*/ 

require_once('core/zip.class.php') ; 
require_once('core/parameters.class.php') ; 
require_once('core/admin_table.class.php') ; 
require_once('core/tabs.class.php') ; 
require_once('core/utils.class.php') ; 
require_once('core/box.class.php') ; 

if (!class_exists('pluginSedLex')) {

	$sedlex_list_scripts = array() ; 
	$sedlex_list_styles = array() ; 

	abstract class pluginSedLex {
	
		protected $pluginID = '';
		protected $pluginName = '';
		protected $signature = '';
		protected $tableSQL = '' ; 
				
		/**
		 * This is our constructor, which is private to force the use of getInstance()
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
			remove_action( 'wp_head', 'wp_shortlink_wp_head');
			
			$this->signature = '<p style="text-align:right;font-size:75%;">&copy; SedLex - <a href="http://www.sedlex.fr/">http://www.sedlex.fr/</a></p>' ; 
		}
		
		
		
		/** ====================================================================================================================================================
		* In order to install the plugin, few things are to be done ...
		* 
		* @return void
		*/
		function install () {
			global $wpdb;
			global $db_version;
		
			$table_name = $wpdb->prefix . $this->pluginID;
			
			if (strlen(trim($this->tableSQL))>0) {
				if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
					$sql = "CREATE TABLE " . $table_name . " (".$this->tableSQL. ");";
			
					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
					dbDelta($sql);
			
					add_option("db_version", $db_version);
				}
			}
		}
		
		/** ====================================================================================================================================================
		* In order to uninstall the plugin, few things are to be done ... or not
		* 
		* @return void
		*/
		function uninstall () {
			//Nothing to do
		}
		
		/** ====================================================================================================================================================
		* Get the option of the plugin
		* 
		* @return variant of the option
		*/
		function get_param($option) {
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
		* @return void
		*/
		function set_param($option, $value) {
			$options = get_option($this->pluginID.'_options');
			$options[$option] = $value ; 
			update_option($this->pluginID.'_options', $options);
		}
		
		/** ====================================================================================================================================================
		* Create the submenu in the admin section
		* 
		* @return void
		*/
		function admin_menu() {   
		
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
		* Add setting option 
		* 
		* @return void
		*/
		function plugin_actions($links, $file) { 
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
		* Translate the plugin...
		* 
		* @return void
		*/
		function init_textdomain() {
			load_plugin_textdomain($this->pluginID, WP_PLUGIN_URL.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) . 'lang');
		}
		
		/** ====================================================================================================================================================
		* Add a JS file in the header
		* 
		* @return void
		*/
		
		function add_js($url) {
			global $sedlex_list_scripts ; 
			$id = str_replace("/",'__',str_replace(WP_PLUGIN_URL."/",'',str_replace('.js','',$url))) ; 
			$sedlex_list_scripts[] = $id ; 
		}
		
		/** ====================================================================================================================================================
		* Add inline JS in the header
		* 
		* @return void
		*/
		
		function add_inline_js($text) {
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
		* Print the <script> tag in the page
		* 
		* @return void
		*/
		
		function flush_js() {
			global $sedlex_list_scripts ; 
			if (!empty($sedlex_list_scripts)) {
				$list = implode(',',$sedlex_list_scripts) ; 
				$url = WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)).'core/load-scripts.php?c=0&load='.$list ; 
				wp_enqueue_script('sedlex_scripts', $url, array() ,date('Ymd'));
				$sedlex_list_scripts = array(); 
			}
		}
		
		/** ====================================================================================================================================================
		* Include the Javascript needed for the admin page
		* 
		* @return void
		*/
		
		function javascript_admin() {
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
		* Include the Javascript needed for the admin page
		* 
		* @return void
		*/
		
		function javascript_admin_always() {
			// For the tabs of the admin page
			wp_enqueue_script('jquery');   
			wp_enqueue_script('jquery-ui-core');   
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-tabs');
			echo '<script> addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!=\'function\'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};</script>' ; 
		}
				
		/** ====================================================================================================================================================
		* Include the Javascript needed for the user/front page
		* 
		* @return void
		*/
		
		function javascript_front() {
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
		* @return void
		*/
		
		function add_css($url) {
			global $sedlex_list_styles ; 
			$id = str_replace("/",'__',str_replace(WP_PLUGIN_URL."/",'',str_replace('.css','',$url))) ; 
			$sedlex_list_styles[] = $id ; 
			//echo "add  ".count($sedlex_list_styles)."; " ; 
		}
		
		/** ====================================================================================================================================================
		* Add inline CSS in the header
		* 
		* @return void
		*/
		
		function add_inline_css($text) {
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
		* Print the <script> tag in the page
		* 
		* @return void
		*/
		
		function flush_css() {
			global $sedlex_list_styles ; 
			
			if (!empty($sedlex_list_styles)) {
				$list = implode(',',$sedlex_list_styles) ; 
				$url = WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)).'core/load-styles.php?c=0&load='.$list ; 
				wp_enqueue_style('sedlex_styles', $url, array() ,date('Ymd'));
				$sedlex_list_styles = array(); 
				//echo "flush $url" ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Include the CSS needed for the admin page
		* 
		* @return void
		*/
		
		function css_admin() {
		
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
		* Include the CSS needed for the admin page
		* 
		* @return void
		*/
		
		function css_admin_always() {
		
			wp_enqueue_style('wp-admin');
			wp_enqueue_style('dashboard');
			wp_enqueue_style('plugin-install');
		
			// For the tabs of the admin page
			$name = 'core/css/tabs_admin.css' ; 
			$url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)) .$name ; 
			if (file_exists($path)) {
				if (filesize($path)>0) {
					$this->add_css($url) ; 
					//wp_enqueue_style('sedlex_styles', $url, array() ,date('Ymd'));// TODO
				}
			}
			
			// For the message of the admin page
			$name = 'core/css/msgbox_admin.css' ; 
			$url = WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)) .$name ; 
			if (file_exists($path)) {
				if (filesize($path)>0) {
					$this->add_css($url) ; 
				}
			}
		}

		
		
		/** ====================================================================================================================================================
		* Include the CSS needed for the user/front page
		* 
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
		* Standard function for the SedLex Plugin information
		* 
		* @return void
		*/
		function sedlex_information() {
			global $submenu;
				
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
					foreach ($plugins as $k=>$v) {
						if (preg_match('/.*SedLex.*/', $v['Author'])>0) {
							$sl_count ++ ; 
						}
					}
?>
					<p>For now, you have installed <?php echo count($plugins) ?> plugins including <b><?php echo $sl_count ; ?> plugins developped with the "SL framework developpment"</b> for plugins:<p/>
<?php
					
					$table = new adminTable() ; 
					$table->title(array(__("Plugin name", $this->pluginID), __("Description", $this->pluginID), __("Status", $this->pluginID))) ; 
					
					foreach ($plugins as $k=>$v) {
						if (preg_match('/.*SedLex.*/', $v['Author'])>0) {
							$ligne++ ; 

							$url = "" ; 
							foreach ($submenu['sedlex.php'] as $ov) {
								if ($ov[0] == $v['Name']) {
									$url = $ov[2] ; 
								}
							}
							ob_start() ; 
							?>
										<p><b><?php echo $v['Name'] ; ?></b></p>
										<p><a href='admin.php?page=<?php echo $url  ; ?>'><?php echo __('Settings') ; ?></a> | <?php echo Utils::byteSize(Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>

							<?php
							$cel1 = new adminCell(ob_get_clean()) ; 
							ob_start() ; 
							?>
										<p><?php echo $v['Description'] ; ?></p>
										<p>Version : <?php echo $v['Version'] ; ?> by <?php echo $v['Author'] ; ?> (<a href='<?php echo $v['AuthorURI'] ; ?>'><?php echo $v['AuthorURI'] ; ?></a>)</p>

							<?php
							$cel2 = new adminCell(ob_get_clean()) ; 
							$cel3 = new adminCell($this->checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR.'/'.$url )."/core.php") ) ; 
					
							$table->add_line(array($cel1, $cel2, $cel3), '1') ; 
						}
					}
					echo $table->flush() ; 

					echo '<p style="text-align:right;font-size:75%;">The core file used for the SedLex plugins is "'.__FILE__.'"</p>' ; 
					echo $this->signature; 
					
					?>
				</div>
				<?php
			}
	
		
		/** ====================================================================================================================================================
		* Check core version of the plugin
		* 
		* @return void
		*/
		
		function checkCoreOfThePlugin($path)  {
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