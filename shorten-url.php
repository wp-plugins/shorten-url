<?php
/*
Plugin Name: Short URL
Plugin Tag: shorttag, shortag, bitly, url, short 
Description: <p>Your pages/posts may have a short url hosted by your own domain.</p><p>Replace the internal function of wordpress <code>get_short_link()</code> by a bit.ly like url. </p><p>Instead of having a short link like http://www.yourdomain.com/?p=3564, your short link will be http://www.yourdomain.com/NgH5z (for instance). </p><p>You can configure: </p><ul><li>the length of the short link, </li><li>if the link is prefixed with a static word, </li><li>the characters used for the short link.</li></ul><p>Moreover, you can manage external links with this plugin. The links in your posts will be automatically replace by the short one if available.</p><p>This plugin is under GPL licence. </p>
Version: 1.4.5

Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/plugins/shorten-url/
License: GPL3
*/

require_once('core.php') ; 

class shorturl extends pluginSedLex {
	/** ====================================================================================================================================================
	* Initialisation du plugin
	* 
	* @return void
	*/
	static $instance = false;
	var $path = false;

	protected function _init() {
		global $wpdb ; 
		// Configuration
		$this->pluginName = 'Short URL' ; 
		$this->tableSQL = "id_post mediumint(9) NOT NULL, nb_hits mediumint(9), short_url TEXT DEFAULT '', url_externe VARCHAR( 255 ) NOT NULL DEFAULT '',UNIQUE KEY id_post (id_post, url_externe)" ; 
		$this->path = __FILE__ ; 
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('shorturl','uninstall_removedata'));
		
		//Parametres supplementaires
		add_action('wp_ajax_reset_link', array($this,'reset_link'));
		add_action('wp_ajax_reset_link_external', array($this,'reset_link_external'));
		add_action('wp_ajax_valid_link', array($this,'valid_link'));
		add_action('wp_ajax_valid_link_external', array($this,'valid_link_external'));
		add_action('wp_ajax_cancel_link', array($this,'cancel_link'));
		add_action('wp_ajax_cancel_link_external', array($this,'cancel_link_external'));
		add_action('wp_ajax_delete_link_external', array($this,'delete_link_external'));
		
		// 
		add_action('all_admin_notices', array($this,'verify_permalink'));
		

		add_filter('get_shortlink', array($this,'get_short_link_filter'), 9, 2);
		add_action('template_redirect',array($this,'redirect_404'), 1);
	}
	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('shorturl'.'_options') ;
		if (is_multisite()) {
			delete_site_option('shorturl'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'shorturl')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'shorturl' ) ; 
		}
	}
	
	/**
	 * Upgrade function
	 */
	 
	public function _update() {
		global $wpdb;
		$table_name = $this->table_name;
		$old_table_name = $wpdb->prefix . $this->pluginID ; 
		
		// This update aims at upgrading older version of shorten-link to enable to create custom shorturl (i.e. with external URL)
		//  For information previous table are :
		// 	id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)
		// and now it is 
		//	id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', url_externe VARCHAR( 255 ) NOT NULL DEFAULT '' ,UNIQUE KEY id_post (id_post, url_externe)
		
		if($wpdb->get_var("show tables like '$old_table_name'") != $old_table_name) {
			if ( !$wpdb->get_var("SHOW COLUMNS FROM ".$table_name." LIKE 'url_externe'")  ) {
				$wpdb->query("ALTER TABLE ".$table_name." ADD url_externe  VARCHAR( 255 ) NOT NULL DEFAULT '';");
				$wpdb->query("ALTER TABLE ".$table_name." DROP INDEX id_post;") ; 
				$wpdb->query("ALTER TABLE ".$table_name." ADD CONSTRAINT id_post UNIQUE (id_post,url_externe)") ; 
			}
		}
		
		// This update aims at changing the table name from the old table name to the new one
		if($wpdb->get_var("show tables like '$old_table_name'") == $old_table_name) {
			// We delete the new created table
			$wpdb->query("DROP TABLE ".$table_name) ; 
			// We change the name of the old table
			$wpdb->query("ALTER TABLE ".$old_table_name." RENAME TO ".$table_name) ; 
			// Gestion de l'erreur
			ob_start() ; 
			$wpdb->print_error();
			$result = ob_get_clean() ; 
			if (strlen($result)>0) {
				echo $result ; 
				die() ; 
			}
		}
		
		// This update aims at adding the nb_hits fields 
		if ( !$wpdb->get_var("SHOW COLUMNS FROM ".$table_name." LIKE 'nb_hits'")  ) {
			$wpdb->query("ALTER TABLE ".$table_name." ADD nb_hits mediumint(9);");
		}
		
	}
	
	/** ====================================================================================================================================================
	* Verify that permalink option is correct
	* 
	* @return variant of the option
	*/

	function verify_permalink() {
		$rewrite = new WP_Rewrite() ; 
		if ($rewrite->permalink_structure=='') {
			echo '<div class="updated">' ; 
			echo '<p>'.__('The permalink options should not be configured to default in order to enable Short-URL.', $this->pluginID).'</p>' ; 
			echo '<p>'.sprintf(__('Please go to %s page to correct it.', $this->pluginID), "<a href='".admin_url()."options-permalink.php'>Permalink</a>").'</p>' ; 
			echo '</div>' ;	
		}
	}

	/** ====================================================================================================================================================
	* Define the default option value of the plugin
	* 
	* @return variant of the option
	*/
	function get_default_option($option) {
		switch ($option) {
			case 'low_char' 	: return true 	; break ; 
			case 'upp_char' 	: return true 	; break ; 
			case 'num_char' 	: return true 	; break ; 
			case 'prefix' 		: return "" 	; break ; 
			case 'length' 		: return 5 		; break ; 
			case 'removewww' 		: return false 		; break ; 
			case 'changeroot' 		: return false 		; break ; 
			case 'changeroot_url' 		: return "" 	; break ; 
			case 'catch_url' 		: return false 		; break ; 
			case 'catch_url_filter' 		: return "*" 		; break ; 
			
			case 'display_top_in_excerpt' 			: return false ; break ; 
			case 'display_bottom_in_excerpt' 			: return false ; break ; 
			case 'display_top_in_post' 			: return false ; break ; 
			case 'display_bottom_in_post' 			: return false ; break ; 
			case 'display_top_in_page' 			: return false ; break ; 
			case 'display_bottom_in_page' 			: return false ; break ; 
			case 'exclude' : return "*" 		; break ; 
			case 'html'						: return "*<div class='shorten_url'>
   The short URL of the present article is: %short_url%
</div>" 	; break ; 
			case 'css'	 					: return "*.shorten_url { 
	padding: 10px 10px 10px 10px ; 
	border: 1px solid #AAAAAA ; 
	background-color: #EEEEEE ; 
}" 	; break ; 
		
		}
		return null ;
	}


	/** ====================================================================================================================================================
	* The configuration page
	* 
	* @return void
	*/
	function configuration_page() {
		global $wpdb;
		$table_name = $this->table_name;
	
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">
			<?php echo $this->signature ; ?>
			<p><?php echo __('This plugin helps you sharing your post with short-links.', $this->pluginID) ; ?></p>
			
			<!--debut de personnalisation-->
		<?php
		
			// On verifie que les droits sont corrects
			$this->check_folder_rights( array() ) ; 
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
	
			$tabs = new adminTabs() ; 
			
			// Mise en place de la barre de navigation

			ob_start() ; 
				// on identifie la racine des short links
				
				echo '<script language="javascript">var site="'.$this->get_home_url().'"</script>' ; 
				
				$maxnb = 20 ; 
				$table = new adminTable(0, $maxnb, true, true) ; 
				
				// on construit le filtre pour la requete
				$filter = explode(" ", $table->current_filter()) ; 
												
				// Get all posts / pages to be 
				$count_posts = wp_count_posts('all');
				$paged=1 ; 
				
				$result = array() ; 
				
				while (true) {
					query_posts('post_type=any&posts_per_page=100&paged='.$paged);
					if (!have_posts()) {
						break;
					}
					
					$paged ++ ; 
					
					while (have_posts()) {
						the_post();
						// we check if the title match the filter
						$match = true ; 
						$title = get_the_title() ; 
						foreach ($filter as $fi) {
							if ($fi!="") {
								if (strpos($title, $fi)===FALSE) {
									$match = false ; 
									break ; 
								}
							}
						}
						if ($match) {
							$result[] = array(get_the_ID(), $title, wp_get_shortlink(), get_post_type(), $wpdb->get_var("SELECT nb_hits FROM {$table_name} WHERE id_post='".get_the_ID()."'")) ; 
						}
					}
				}
				
				$count = count($result);
				$table->set_nb_all_Items($count) ; 

				$table->title(array(__('Title of your posts/pages', $this->pluginID), __('Short URL', $this->pluginID), __('Type', $this->pluginID), __('Number of clicks', $this->pluginID)) ) ; 

				
				// We order the posts page according to the choice of the user
				if ($table->current_orderdir()=="ASC") {
					$result = Utils::multicolumn_sort($result, $table->current_ordercolumn(), true) ;  
				} else { 
					$result = Utils::multicolumn_sort($result, $table->current_ordercolumn(), false) ;  
				}
				
				// We limit the result to the requested zone
				$result = array_slice($result,($table->current_page()-1)*$maxnb,$maxnb);
				
				// lignes du tableau
				// boucle sur les differents elements
				$ligne = 0 ; 
				foreach ($result as $r) {
					$ligne++ ; 
					ob_start() ; 
					?>
					<b><?php echo $r[1]; ?></b>
					<img src="<?php echo plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) ?>img/ajax-loader.gif" id="wait<?php echo $r[0] ; ?>" style="display: none;" />
					<?php
					$cel1 = new adminCell(ob_get_clean()) ; 	
					ob_start() ; 
					?>
					<span id="lien<?php echo $r[0] ; ?>" ><a href="<?php echo $r[2] ; ?>"><?php echo $r[2] ; ?></a></span>
					<?php
					$cel2 = new adminCell(ob_get_clean()) ; 	
					$cel2->add_action(__("Reset", $this->pluginID), "resetLink") ; 
					$cel2->add_action(__("Edit", $this->pluginID), "forceLink") ; 
					
					if (get_post_status($r[0])=="publish") {
						$cel3 = new adminCell($r[3]) ; 
					} else {
						$cel3 = new adminCell($r[3]." (".get_post_status($r[0]).")") ; 
					}
					
					$select = "SELECT nb_hits FROM {$table_name} WHERE id_post='".$r[0]."'" ; 
					$nb_hits = $wpdb->get_var( $select ) ;
					$cel4 = new adminCell($nb_hits) ; 	
					
					$table->add_line(array($cel1, $cel2, $cel3, $cel4), $r[0]) ; 
				}
				echo $table->flush() ;
			$tabs->add_tab(__('Internal Redirections',  $this->pluginID), ob_get_clean() ) ; 
			
			ob_start() ; 
			
				if (isset($_POST['add'])) {
					$url_ext = str_replace("'", "", $_POST['url_externe']) ; 
					if (!preg_match("/^http/i", $url_ext)) {
						$url_ext = "http://".$url_ext  ; 
					}
					$this->add_external_link($url_ext) ; 
				}
			
				$maxnb = 20 ; 
				
				$table = new adminTable($count, $maxnb, true, true) ; 
				$table->title(array(__('External URL', $this->pluginID), __('Short URL', $this->pluginID), __('Number of clicks', $this->pluginID)) ) ; 
				
				// on construit le filtre pour la requête
				$filter = explode(" ", $table->current_filter()) ; 
				$filter_words = "" ; 
				foreach ($filter as $fi) {
					$filter_words .= " AND " ; 
					$filter_words .= "url_externe like '%".$fi."%'" ; 
				}
				
				$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name." WHERE id_post=0 ".$filter_words." AND url_externe<>''; ") ; 
				$table->set_nb_all_Items($count) ; 
				
				if ($table->current_ordercolumn()==1) {
					$orderby = " ORDER BY url_externe ".$table->current_orderdir() ; 
				} else if ($table->current_ordercolumn()==2) {
					$orderby = " ORDER BY short_url ".$table->current_orderdir() ; 
				} else if ($table->current_ordercolumn()==3) {
					$orderby = " ORDER BY nb_hits ".$table->current_orderdir() ; 
				} 

				$res = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE id_post=0 ".$filter_words." AND url_externe<>'' ".$orderby." LIMIT ".($maxnb*($table->current_page()-1)).", ".$maxnb." ; ") ; 
				
				foreach($res as $r) {
					$id_temp = md5($r->short_url) ; 
					$cel1 = new adminCell("<a href='".$r->url_externe."'>".$r->url_externe."</a><img src='".plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))."img/ajax-loader.gif' id='wait_external".$id_temp."' style='display: none;' />") ; 	
					$cel1->add_action(__("Delete", $this->pluginID), "deleteLink_external('".$id_temp."')") ; 
					$cel2 = new adminCell("<span id='lien_external".$id_temp."'><a href='".$this->get_home_url()."/".$r->short_url."'>".$this->get_home_url()."/".$r->short_url."</a></span>") ; 
					$cel2->add_action(__("Reset", $this->pluginID), "resetLink_external('".$id_temp."')") ; 
					$cel2->add_action(__("Edit", $this->pluginID), "forceLink_external('".$id_temp."')") ; 
					$cel3 = new adminCell($r->nb_hits) ; 	

					$table->add_line(array($cel1, $cel2, $cel3), $id_temp) ; 
				}
				echo $table->flush() ;
				
				ob_start() ; 
					?>
					<form method='post' action='<?echo remove_query_arg("filter_".$table->id, $_SERVER["REQUEST_URI"])?>'>
						<label for='url_externe'><?php echo __('External URL:', $this->pluginID) ; ?></label>
						<input name='url_externe' id='url_externe' type='text' value='' size='40'/><br/>
						<div class="submit">
							<input type="submit" name="add" class='button-primary validButton' value="<?php echo __('Add a new URL to shorten', $this->pluginID) ; ?>" />
						</div>
					</form>
					<?php
				$box = new boxAdmin (__('Add a new URL to shorten', $this->pluginID), ob_get_clean()) ; 
				echo $box->flush() ; 

			$tabs->add_tab(__('External Redirections',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				?>
					<h3 class="hide-if-js"><? echo __('Parameters',$this->pluginID) ?></h3>
				
					<?php
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('Do you want to use the following characters?',$this->pluginID)) ; 
					$params->add_comment(__('These parameters will be taken in account only for generation of new links',$this->pluginID)) ; 
					$params->add_param('low_char', __('Lower-case characters ([a-z]):',$this->pluginID)) ; 
					$params->add_param('upp_char', __('Upper-case characters ([A-Z]):',$this->pluginID)) ; 
					$params->add_param('num_char', __('Numeric characters ([0-9]):',$this->pluginID)) ; 
					
					$params->add_title(__('Do you want to use a prefix before your short URL?',$this->pluginID)) ; 
					$params->add_param('prefix', __('Prefix:',$this->pluginID), "@[^a-zA-Z0-9_]@") ; 
					
					$params->add_title(__('What is the length of your short URL (without the prefix)?',$this->pluginID)) ; 
					$params->add_param('length', __('Length:',$this->pluginID)) ; 

					$params->add_title(__('Customize the short link URL',$this->pluginID)) ; 
					$params->add_param('removewww', __('Remove www:',$this->pluginID)) ; 
					$params->add_comment(sprintf(__('Therefore, the short URL will begin with %s',$this->pluginID), str_replace("://www.", "://", home_url()))) ; 
					$params->add_param('changeroot', __('Change the root URL of the short link:',$this->pluginID), "", "", array("!removewww", "changeroot_url")) ; 
					$params->add_comment(__('If you have a shorter URL pointing to your Wordpress blog, you may configure it here by selecting this option',$this->pluginID)) ; 
					$params->add_param('changeroot_url', __('Your shorter domain URL:',$this->pluginID)) ; 
					
					$params->add_title(__('Do you want to automatically shorten links in post/page?',$this->pluginID)) ; 
					$params->add_param('catch_url', __('Automatic shorten links:',$this->pluginID), "", "", array('catch_url_filter')) ; 
					$params->add_param('catch_url_filter', __('Regexp filter:',$this->pluginID)) ; 
					$params->add_comment(sprintf(__('The above regexp filter is to select the page in which the link urls are shorten. For instance, %s (or %s) configures the plugin to shorten all links, for instance, in pages %s and %s',$this->pluginID), "<code>.*cat_select.*</code>","<code>cat_select</code>", "<code>http://domain.tld/cat_select/</code>", "<code>http://domain.tld/level/cat_select/child/</code>")) ; 
					$params->add_comment(__('Please enter one regexp per line.',$this->pluginID)) ; 
					$params->add_comment(__('If no regexp is entered, links in all pages and posts will be shorten.',$this->pluginID)) ; 

					$params->add_title(__('Display the short URL?',$this->pluginID)) ; 
					$params->add_param('display_top_in_post', "".__('At the top of posts:',$this->pluginID)) ; 
					$params->add_param('display_bottom_in_post', "".__('At the bottom of posts:',$this->pluginID)) ; 
					$params->add_param('display_top_in_page', "".__('At the top of pages:',$this->pluginID)) ; 
					$params->add_param('display_bottom_in_page', "".__('At the bottom of pages:',$this->pluginID)) ; 
					$params->add_param('display_top_in_excerpt', "".__('At the top of excerpt:',$this->pluginID)) ; 
					$params->add_param('display_bottom_in_excerpt', "".__('At the bottom of excerpt:',$this->pluginID)) ; 
					$params->add_param('html', __('Displayed HTML:',$this->pluginID)) ; 
					$params->add_comment_default_value('html') ; 
					$params->add_comment(sprintf(__('Note that %s will be automatically replaced by the shorten URL.', $this->pluginID), "<code>%short_url%</code>")) ; 
					$params->add_comment(sprintf(__('In addition, %s will be replaced by the shorten URL withour any html link.', $this->pluginID), "<code>%short_url_without_link%</code>")) ; 
					$params->add_param('css', __('CSS:',$this->pluginID)) ; 
					$params->add_comment_default_value('css') ; 
					$params->add_param('exclude', __('Page to be excluded:',$this->pluginID)) ; 
					$params->add_comment(sprintf(__("Please enter one entry per line. If the page %s is to be excluded, you may enter %s.",  $this->pluginID), "<code>http://yourdomain.tld/contact/</code>","<code>contact</code>")) ; 
					$params->add_comment(sprintf(__("For instance, %s and %s will exclude the home page.",  $this->pluginID), "<code>^$</code>","<code>^/$</code>")) ; 
					
					$params->flush() ; 
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
					
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	

			echo $tabs->flush() ; 
			
			echo $this->signature ; ?>
		</div>
		<?php
	}

	/** ====================================================================================================================================================
	* Callback for reset Link
	* 
	* @return void
	*/
	function reset_link() {
		global $wpdb;
		$table_name = $this->table_name;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		// Empty the database for the given idLink
		$q = "DELETE  FROM {$table_name} WHERE id_post=".$idLink ; 
		$wpdb->query( $q ) ;
		// Create a new entry
		$link = $this->get_short_link_filter(get_permalink($idLink), $idLink) ; 
		// Return the new URL to the interface
		?>
		<a href="<?php echo $link; ?>"><?php echo $link ; ?></a>
		<?php
		die();
	}

	/** ====================================================================================================================================================
	* Callback for reset Link
	* 
	* @return void
	*/
	function reset_link_external() {
		global $wpdb;
		$table_name = $this->table_name;
		
		// get the arguments
		$idLink = $_POST['idLink'];

		// New short link
		$car_minus = $this->get_param('low_char') ; 
		$car_maxus = $this->get_param('upp_char') ; 
		$car_nombr = $this->get_param('num_char') ; 
		$car_longu = $this->get_param('length') ;
		$temp_url = "" ; 
		
		$char = ($car_maxus ? "ABCDEFGHIJKLMNOPQRSTUVWXYZ" : "" ).($car_minus ? "abcdefghijklmnopqrstuvwxyz" : "" ).($car_nombr ? "1234567890" : "" ) ; 
		$ok = false ; 
		while (!$ok) {
			$result = $this->get_param('prefix').Utils::rand_str($car_longu , $char) ; 
			$select = "SELECT id_post FROM {$table_name} WHERE short_url='".$result."'" ; 
			$temp_id = $wpdb->get_var( $select ) ;
			if (($temp_id==null)||($temp_id===false)||($temp_id==NULL)||(!is_numeric($temp_id))) {
				$ok = true ; 
			}
		}
		
		// Empty the database for the given idLink
		$q = "UPDATE  {$table_name} SET short_url = '".$result."' WHERE id_post=0 AND MD5(short_url)='".$idLink."'"  ;
		$wpdb->query( $q ) ;
		
		// Return the new URL to the interface
		$old_id = $idLink ; 
		$new_id = md5($result) ; 
		$link = $this->get_home_url()."/".$wpdb->get_var("SELECT short_url FROM {$table_name} WHERE id_post=0 AND short_url='".$result."'") ;
		?>
		<a href="<?php echo $link; ?>"><?php echo $link ; ?></a>
		<script>
			jQuery("#wait_external<?php echo $old_id ; ?>").attr("id","wait_external<?php echo $new_id ; ?>");
			jQuery("#lien_external<?php echo $old_id ; ?>").attr("id","lien_external<?php echo $new_id ; ?>");
			jQuery("#ligne<?php echo $old_id ; ?>").attr("id","ligne<?php echo $new_id ; ?>");
			jQuery("#deleteLink_external<?php echo $old_id ; ?>_<?php echo $old_id ; ?>").attr("onclick","javascript: deleteLink_external('<?php echo $new_id ; ?>') ; return false ; ");
			jQuery("#resetLink_external<?php echo $old_id ; ?>_<?php echo $old_id ; ?>").attr("onclick","javascript: resetLink_external('<?php echo $new_id ; ?>') ; return false ; ");
			jQuery("#forceLink_external<?php echo $old_id ; ?>_<?php echo $old_id ; ?>").attr("onclick","javascript: forceLink_external('<?php echo $new_id ; ?>') ; return false ; ");
		</script>
		<?php
		die();		
	}

	/** ====================================================================================================================================================
	* Callback for valid Button
	* 
	* @return void
	*/
	function valid_link() {
		global $wpdb;
		$table_name = $this->table_name;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		$link = $_POST['link'];
		$link = preg_replace("@[^a-zA-Z0-9_.-]@", '', $link);
		
		// Empty the database for the given idLink
		$q = "UPDATE {$table_name} SET short_url = '".$link."' WHERE id_post=".$idLink ; 
		$wpdb->query( $q ) ;
		// Get a  entry
		$link = $this->get_short_link_filter('', $idLink) ; 
		// Return the new URL to the interface
		?>
		<a href="<?php echo $link; ?>"><?php echo $link ; ?></a>
		<?php
		die();
	}
	
	/** ====================================================================================================================================================
	* Callback for valid Button
	* 
	* @return void
	*/
	function valid_link_external() {
		global $wpdb;
		$table_name = $this->table_name;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		$link = $_POST['link'];
		$link = preg_replace("@[^a-zA-Z0-9_.-]@", '', $link);
		
		// Empty the database for the given idLink
		
		$q = "UPDATE  {$table_name} SET short_url = '".$link."' WHERE id_post=0 AND MD5(short_url)='".$idLink."'"  ;
		$wpdb->query( $q ) ;

		// Return the new URL to the interface
		$old_id = $idLink ; 
		$new_id = md5($link) ; 
		$link = $this->get_home_url()."/".$wpdb->get_var("SELECT short_url FROM {$table_name} WHERE id_post=0 AND short_url='".$link."'") ;
		?>
		<a href="<?php echo $link; ?>"><?php echo $link ; ?></a>
		<script>
			jQuery("#wait_external<?php echo $old_id ; ?>").attr("id","wait_external<?php echo $new_id ; ?>");
			jQuery("#lien_external<?php echo $old_id ; ?>").attr("id","lien_external<?php echo $new_id ; ?>");
			jQuery("#ligne<?php echo $old_id ; ?>").attr("id","ligne<?php echo $new_id ; ?>");
			jQuery("#deleteLink_external<?php echo $old_id ; ?>_<?php echo $old_id ; ?>").attr("onclick","javascript: deleteLink_external('<?php echo $new_id ; ?>') ; return false ; ");
			jQuery("#resetLink_external<?php echo $old_id ; ?>_<?php echo $old_id ; ?>").attr("onclick","javascript: resetLink_external('<?php echo $new_id ; ?>') ; return false ; ");
			jQuery("#forceLink_external<?php echo $old_id ; ?>_<?php echo $old_id ; ?>").attr("onclick","javascript: forceLink_external('<?php echo $new_id ; ?>') ; return false ; ");
		</script>
		<?php
		die();
	}


	/** ====================================================================================================================================================
	* Callback for cancel button
	* 
	* @return void
	*/
	function cancel_link() {
		// get the arguments
		$idLink = $_POST['idLink'];
		// Get a entry
		$link = $this->get_short_link_filter('', $idLink) ; 
		// Return the new URL to the interface
		?>
		<a href="<?php echo $link; ?>"><?php echo $link ; ?></a>
		<?php
		die();
	}
	
	/** ====================================================================================================================================================
	* Callback for cancel button
	* 
	* @return void
	*/
	function cancel_link_external() {
		global $wpdb;
		$table_name = $this->table_name;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		// Get a entry
		$link =  home_url()."/".$wpdb->get_var("SELECT short_url FROM {$table_name} WHERE id_post=0 AND MD5(short_url)='".$idLink."'") ;
		// Return the new URL to the interface
		?>
		<a href="<?php echo $link; ?>"><?php echo $link ; ?></a>
		<?php
		die();
	}

	/** ====================================================================================================================================================
	* Callback for delete button
	* 
	* @return void
	*/
	function delete_link_external() {
		global $wpdb;
		$table_name = $this->table_name;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		// Delete a entry
		$q = "DELETE FROM {$table_name} WHERE id_post=0 AND MD5(short_url)='".$idLink."'"  ;
		$wpdb->query( $q ) ;
		die();
	}


	/** ====================================================================================================================================================
	* Filter called when get_short_link is called
	* 
	* @return void
	*/
	
	function get_short_link_filter($url, $post_id) {
		global $post;
		global $wpdb;
				
		$table_name = $this->table_name;
	
		if (!$post_id && $post) $post_id = $post->ID;
	
		// We look if the short URL already exists in the database
		$select = "SELECT short_url FROM {$table_name} WHERE id_post=".$post_id ; 
		$url = $wpdb->get_var( $select ) ;
	
		if ($url!="") {
			return $this->get_home_url()."/".$url ; 
		}
	
		// We generate a new short Url
		$car_minus = $this->get_param('low_char') ; 
		$car_maxus = $this->get_param('upp_char') ; 
		$car_nombr = $this->get_param('num_char') ; 
		$car_longu = $this->get_param('length') ;
		$temp_url = "" ; 
		
		$char = ($car_maxus ? "ABCDEFGHIJKLMNOPQRSTUVWXYZ" : "" ).($car_minus ? "abcdefghijklmnopqrstuvwxyz" : "" ).($car_nombr ? "1234567890" : "" ) ; 
		$ok = false ; 
		while (!$ok) {
			$result = $this->get_param('prefix').Utils::rand_str($car_longu , $char) ; 
			$select = "SELECT id_post FROM {$table_name} WHERE short_url='".$result."'" ; 
			$temp_id = $wpdb->get_var( $select ) ;
			if (($temp_id==null)||($temp_id===false)||((!is_numeric($temp_id))&&($post_id!=0))) {
				$ok = true ; 
				$sql = "DELETE FROM {$table_name} WHERE id_post=".$post_id ; 
				$wpdb->query( $sql ) ;
				$sql = "INSERT INTO {$table_name} (id_post, short_url) VALUES ('{$post_id}', '" . $result . "')" ; 
				$wpdb->query( $sql ) ;
			}
		}
		
		return $this->get_home_url()."/".$result ; 
	}


	/** ====================================================================================================================================================
	* Redirect to the true page
	* 
	* @return void
	*/
	
	function redirect_404() {
		global $post;
		global $wpdb;
		$table_name = $this->table_name;
		
		if(is_404()) {
			$param = explode("/", $_SERVER['REQUEST_URI']) ; 
			if (preg_match("/^([a-zA-Z0-9_.-])*$/",$param[count($param)-1],$matches)==1) {
				$select = "SELECT id_post FROM {$table_name} WHERE short_url='".$param[count($param)-1]."'" ; 
				$temp_id = $wpdb->get_var( $select ) ;
				if (($temp_id==null)||($temp_id===false)) {
					return ; 
				} else if (is_numeric($temp_id)) {
					if ($temp_id==0) {
						$select = "SELECT url_externe FROM {$table_name} WHERE short_url='".$param[count($param)-1]."'" ; 
						$temp_url = $wpdb->get_var( $select ) ;
						$wpdb->query("UPDATE {$table_name} SET nb_hits = IFNULL(nb_hits, 0) + 1 WHERE short_url='".$param[count($param)-1]."'") ;
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: ".$temp_url );
						exit();
					} else {
						$wpdb->query("UPDATE {$table_name} SET nb_hits = IFNULL(nb_hits, 0) + 1 WHERE id_post=".$temp_id) ;
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: ".get_permalink($temp_id));
						exit();
					}
				}
			} 
		}
	}
	
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		$this->add_inline_css($this->get_param('css')) ; 
	}

	/** ====================================================================================================================================================
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {	
		global $post ; 
		$return = preg_replace_callback('#<a([^>]*?)href="([^"]*?)"([^>]*?)>([^<]*?)</a>#i', array($this,"replace_by_short_link"), $content);
		
		// We check whether there is an exclusion
		$exclu = $this->get_param('exclude') ;
		$exclu = explode("\n", $exclu) ;
		foreach ($exclu as $e) {
			$e = trim(str_replace("\r", "", $e)) ; 
			if ($e!="") {
				$e = "#".$e."#i"; 
				if (preg_match($e, get_permalink($post->ID))) {
					return $return ; 
				}
				if (preg_match($e, $_SERVER['REQUEST_URI'])) {
					return $return ; 
				}				
			}
		}

		// If it is the loop and an the_except is called, we leave
		if ($excerpt) {
			// Excerpt
			if ($this->get_param('display_bottom_in_excerpt')) {
				$return =  $return.$this->display_url($post) ;  
			}
			if ($this->get_param('display_top_in_excerpt')) {
				$return =  $this->display_url($post).$return ; 
			}
			return $return; 
		} else {
			// Page
			if ($type=="page") {
				if ($this->get_param('display_bottom_in_page')) {
					$return =  $return.$this->display_url($post) ;  
				}
				if ($this->get_param('display_top_in_page')) {
					$return =  $this->display_url($post).$return ; 
				}
				return $return; 				
			}
			// Post
			if ($type=="post") {
				if ($this->get_param('display_bottom_in_post')) {
					$return =  $return.$this->display_url($post) ;  
				}
				if ($this->get_param('display_top_in_post')) {
					$return =  $this->display_url($post).$return ; 
				}
				return $return; 				
			}
		}
		
		return $return ; 		
	}
	
	/** ====================================================================================================================================================
	* Display short URL in post / page
	*
	* @param object the post
	* @return string html to be displayed
	*/
	
	function display_url($post) {
		$short = wp_get_shortlink($post->ID) ;
		return str_replace('%short_url_without_link%', "$short", str_replace('%short_url%', "<a href='$short'>$short</a>", $this->get_param('html'))) ; 
	}
	
	/** ====================================================================================================================================================
	* Callback pour modifier les liens par des short links
	* 
	* @return void
	*/	
	
	function replace_by_short_link($match) {
		global $wpdb;
		$table_name = $this->table_name;
		
		$match[2] = addslashes(str_replace("'", "", $match[2])) ; 
		
		// Search for existing short link
		$short = $wpdb->get_var( "SELECT short_url FROM {$table_name} WHERE id_post=0 AND url_externe='".$match[2]."'"); 
		if ($short != "") {
			return '<a'.$match[1].'href="'.$this->get_home_url()."/".$short.'"'.$match[3].'>'.$match[4].'</a>';
		} else {
			// Create a new shorlink if applicable
			if ($this->get_param('catch_url')) {
				// the url should begin with http
				if (strpos($match[2],"http")===0) {
					// the url is not an internal url
					if (strpos($match[2],home_url())!==0) {
						$regexp = explode("\n", trim($this->get_param('catch_url_filter'))) ; 
						foreach ($regexp as $r) {
							if (preg_match("/".$r."/i", $_SERVER['REQUEST_URI'])) {
								$result = $this->add_external_link($match[2]) ; 
								return '<a'.$match[1].'href="'.$this->get_home_url()."/".$result.'"'.$match[3].'>'.$match[4].'</a>';
							}
						}
					}
				}
			}
			// default return (we do not change anything)
			return '<a'.$match[1].'href="'.$match[2].'"'.$match[3].'>'.$match[4].'</a>';
		}
	}
	
	/** ====================================================================================================================================================
	* To add an external link in the 
	* 
	* @return the short_url
	*/	
	
	function add_external_link($link) {
		global $wpdb ; 
		$table_name = $this->table_name;
		// We add the shortlink
		$car_minus = $this->get_param('low_char') ; 
		$car_maxus = $this->get_param('upp_char') ; 
		$car_nombr = $this->get_param('num_char') ; 
		$car_longu = $this->get_param('length') ;
		$temp_url = "" ; 
		
		$url_ext = addslashes(str_replace("'", "", $link)) ; 
		
		$char = ($car_maxus ? "ABCDEFGHIJKLMNOPQRSTUVWXYZ" : "" ).($car_minus ? "abcdefghijklmnopqrstuvwxyz" : "" ).($car_nombr ? "1234567890" : "" ) ; 
		$ok = false ; 
		while (!$ok) {
			$result = $this->get_param('prefix').Utils::rand_str($car_longu , $char) ; 
			$select = "SELECT id_post FROM {$table_name} WHERE short_url='".$result."'" ; 
			$temp_id = $wpdb->get_var( $select ) ;
			
			if (($temp_id==null)||($temp_id===false)||(!is_numeric($temp_id))) {
				$ok = true ; 
				$sql = "DELETE FROM {$table_name} WHERE url_externe='".$url_ext."'" ; 
				$wpdb->query( $sql ) ;
				$sql = "INSERT INTO {$table_name} (id_post, short_url, url_externe) VALUES ('0', '" . $result . "', '".$url_ext."')" ; 
				$wpdb->query( $sql ) ;
				return $result ; 
			}
		}
	
	}
	
	
	/** ====================================================================================================================================================
	* To add an external link in the 
	* 
	* @return the short_url
	*/	
	
	function get_home_url() {		
		// on identifie la racine des short links
		$home_url = home_url() ; 
		if ($this->get_param('removewww')) {
			$home_url = str_replace("://www.", "://", home_url()) ; 
		}
		if ($this->get_param('changeroot')) {
			$home_url = $this->get_param('changeroot_url') ; 
			if (strpos($home_url, "http")!==0) {
				$home_url  = "http://".$home_url  ; 
			}
			if (substr($home_url,-1)=="/") {
				$home_url = substr($home_url, 0, -1) ; 
			}
		}
		return $home_url  ; 
	}


}

$shorturl = shorturl::getInstance();

?>