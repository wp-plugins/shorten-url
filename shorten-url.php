<?php
/*
Plugin Name: Short URL
Description: <p>Replacing the internal function of wordpress <code>get_short_link()</code> by a bit.ly like url. </p><p>Instead of having a short link like http://www.yourdomain.com/?p=3564, your short link will be http://www.yourdomain.com/NgH5z (for instance). </p><p>You can configure: <ul><li>the length of the short link, </li><li>if the link is prefixed with a static word, </li><li>the characters used for the short link.</li></ul></p><p>Moreover, you can manage external links with this plugin. The links in your posts will be automatically replace by the short one if available.</p><p>This plugin is under GPL licence. </p>
Version: 1.1.1
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/extend/plugins/shorten-url/
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
	static $path = false;

	protected function _init() {
		// Configuration
		$this->pluginName = 'Short URL' ; 
		$this->tableSQL = "id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', url_externe VARCHAR( 255 ) NOT NULL DEFAULT '',UNIQUE KEY id_post (id_post, url_externe)" ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
		
		//Paramètres supplementaires
		add_action('wp_ajax_reset_link', array($this,'reset_link'));
		add_action('wp_ajax_reset_link_external', array($this,'reset_link_external'));
		add_action('wp_ajax_valid_link', array($this,'valid_link'));
		add_action('wp_ajax_valid_link_external', array($this,'valid_link_external'));
		add_action('wp_ajax_cancel_link', array($this,'cancel_link'));
		add_action('wp_ajax_cancel_link_external', array($this,'cancel_link_external'));
		add_action('wp_ajax_delete_link_external', array($this,'delete_link_external'));
		add_filter('get_shortlink', array($this,'get_short_link_filter'), 9, 2);
		add_action('template_redirect',array($this,'redirect_404'), 0 );
		
		add_action( "the_content", array($this,"the_content") );

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
	
	
	/**
	 * Upgrade function
	 */
	 
	public function _update() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
		
		// This update aims at upgrading older version of shorten-link to enable to create custom shorturl (i.e. with external URL)
		//  For information previous table are :
		// 	id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)
		// and now it is 
		//	id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', url_externe VARCHAR( 255 ) NOT NULL DEFAULT '' ,UNIQUE KEY id_post (id_post, url_externe)
		
		if ( !$wpdb->get_var("SHOW COLUMNS FROM ".$table_name." LIKE 'url_externe'")  ) {
			$wpdb->query("ALTER TABLE ".$table_name." ADD url_externe  VARCHAR( 255 ) NOT NULL DEFAULT '';");
			$wpdb->query("ALTER TABLE ".$table_name." DROP INDEX id_post;") ; 
			$wpdb->query("ALTER TABLE ".$table_name." ADD CONSTRAINT id_post UNIQUE (id_post,url_externe)") ; 
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
		$table_name = $wpdb->prefix . $this->pluginID;
	
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
			
			$get = $_GET;
			unset($get['paged']) ;			
			ob_start() ; 
				echo '<script language="javascript">var site="'.site_url().'"</script>' ; 
				
				$count = count(get_posts(array('post_status' => 'publish', 'posts_per_page' => -1))) ;
				$maxnb = 20 ; 
				$table = new adminTable($count, $maxnb) ; 
				
				$table->title(array(__('Title of your posts', $this->pluginID), __('Short URL', $this->pluginID)) ) ; 

				// lignes du tableau
				// boucle sur les differents elements
				query_posts('post_status=publish&paged='.$table->current_page().'&posts_per_page='.$maxnb);
				while (have_posts()) {
					the_post();
					$ligne++ ; 
					ob_start() ; 
					?>
					<b><?php echo the_title(); ?></b>
					<img src="<?php echo WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) ?>img/ajax-loader.gif" id="wait<?php the_ID() ; ?>" style="display: none;" />
					<?php
					$cel1 = new adminCell(ob_get_clean()) ; 	
					ob_start() ; 
					?>
					<span id="lien<?php the_ID() ; ?>" ><a href="<?php echo wp_get_shortlink() ; ?>"><?php echo wp_get_shortlink() ; ?></a></span>
					<?php
					$cel2 = new adminCell(ob_get_clean()) ; 	
					$cel2->add_action("Reset", "resetLink") ; 
					$cel2->add_action("Force", "forceLink") ; 
					
					$table->add_line(array($cel1, $cel2), get_the_ID()) ; 
				}
				echo $table->flush() ;
			$tabs->add_tab(__('Results for Posts URL',  $this->pluginID), ob_get_clean() ) ; 
			
			ob_start() ; 
			
				if ($_GET['table_id']=='2') {
					$tabs->activate(2) ;
				}
				if (isset($_POST['add'])) {
					$tabs->activate(2) ;
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
						if (!is_numeric($temp_id)) {
							$ok = true ; 
							$sql = "DELETE FROM {$table_name} WHERE url_externe=".$_POST['url_externe'] ; 
							$wpdb->query( $sql ) ;
							$sql = "INSERT INTO {$table_name} (id_post, short_url, url_externe) VALUES ('0', '" . $result . "', '".$_POST['url_externe']."')" ; 
							$wpdb->query( $sql ) ;
						}
					}
					$wpdb->query("SELECT COUNT(*) FROM ".$table_name." WHERE id_post=0 ; ") ; 
				}
			
				$maxnb = 20 ; 
				
				$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name." WHERE id_post=0 ; ") ; 
				$table = new adminTable($count, $maxnb) ; 
				$table->title(array(__('External URL', $this->pluginID), __('Short URL', $this->pluginID)) ) ; 

				$res = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE id_post=0 LIMIT ".($maxnb*($table->current_page()-1)).", ".$maxnb." ; ") ; 
				
				foreach($res as $r) {
					$id_temp = $r->short_url ; 
					$cel1 = new adminCell("<a href='".$r->url_externe."'>".$r->url_externe."</a><img src='".WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))."img/ajax-loader.gif' id='wait_external".$id_temp."' style='display: none;' />") ; 	
					$cel1->add_action("Delete", "deleteLink_external('".$id_temp."')") ; 
					$cel2 = new adminCell("<span id='lien_external".$id_temp."'><a href='".site_url()."/".$r->short_url."'>".site_url()."/".$r->short_url."</a></span>") ; 
					$cel2->add_action("Reset", "resetLink_external('".$id_temp."')") ; 
					$cel2->add_action("Force", "forceLink_external('".$id_temp."')") ; 
					
					$table->add_line(array($cel1, $cel2), $id_temp) ; 
				}
				echo $table->flush() ;
				
				ob_start() ; 
					?>
					<form method='post' action='<?echo $_SERVER["REQUEST_URI"]?>'>
						<label for='url_externe'><?php echo __('External URL:', $this->pluginID) ; ?></label>
						<input name='url_externe' id='url_externe' type='text' value='' size='40'/><br/>
						<div class="submit">
							<input type="submit" name="add" class='button-primary validButton' value="<?php echo __('Add a new URL to shorten', $this->pluginID) ; ?>" />
						</div>
					</form>
					<?php
				$box = new boxAdmin (__('Add a new URL to shorten', $this->pluginID), ob_get_clean()) ; 
				echo $box->flush() ; 

			$tabs->add_tab(__('Results for External URL',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				?>
					<h3 class="hide-if-js"><? echo __('Parameters',$this->pluginID) ?></h3>
					<p><?php echo __('Here is the parameters of the plugin. Please modify them at your convenience.',$this->pluginID) ; ?> </p>
					<p><i><?php echo __('(Please note that the parameters will only be taken in account only for generation of new links)',$this->pluginID) ; ?> </i></p>
				
					<?php
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('Do you want to use the following characters?',$this->pluginID)) ; 
					$params->add_param('low_char', __('Low-case characters ([a-z]):',$this->pluginID)) ; 
					$params->add_param('upp_char', __('Upper-case characters ([A-Z]):',$this->pluginID)) ; 
					$params->add_param('num_char', __('Numeric characters ([0-9]):',$this->pluginID)) ; 
					
					$params->add_title(__('Do you want to use a prefix before your short URL?',$this->pluginID)) ; 
					$params->add_param('prefix', __('Prefix:',$this->pluginID), "@[^a-zA-Z0-9_]@") ; 
					
					$params->add_title(__('What is the length of your short URL (without the prefix)?',$this->pluginID)) ; 
					$params->add_param('length', __('Length:',$this->pluginID)) ; 
					
					$params->flush() ; 
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() ) ; 	
					
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() ) ; 	

			ob_start() ; 
				echo __('This form is an easy way to contact the author and to discuss issues / incompatibilities / etc.',  $this->pluginID) ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				echo "<p>".__('Here is the plugins developped by the author',  $this->pluginID) ."</p>" ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other possible plugins',  $this->pluginID), ob_get_clean() ) ; 	

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
		$table_name = $wpdb->prefix . $this->pluginID;
		
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
		$table_name = $wpdb->prefix . $this->pluginID;
		
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
			if (!is_numeric($temp_id)) {
				$ok = true ; 
			}
		}
		
		// Empty the database for the given idLink
		$q = "UPDATE  {$table_name} SET short_url = '".$result."' WHERE id_post=0 AND short_url='".$idLink."'"  ;
		$wpdb->query( $q ) ;
		// Get a  entry
		$link = site_url()."/".$wpdb->get_var("SELECT short_url FROM {$table_name} WHERE id_post=0 AND short_url='".$result."'") ;
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
	function valid_link() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		$link = $_POST['link'];
		$link = preg_replace("@[^a-zA-Z0-9_]@", '', $link);
		
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
		$table_name = $wpdb->prefix . $this->pluginID;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		$link = $_POST['link'];
		$link = preg_replace("@[^a-zA-Z0-9_]@", '', $link);
		
		// Empty the database for the given idLink
		$q = "UPDATE  {$table_name} SET short_url = '".$link."' WHERE id_post=0 AND short_url='".$idLink."'"  ;
		$wpdb->query( $q ) ;
		// Get a  entry
		$link = site_url()."/".$wpdb->get_var("SELECT short_url FROM {$table_name} WHERE id_post=0 AND short_url='".$link."'") ;
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
	function cancel_link() {
		global $wpdb;
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
		$table_name = $wpdb->prefix . $this->pluginID;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		// Get a entry
		$link =  site_url()."/".$wpdb->get_var("SELECT short_url FROM {$table_name} WHERE id_post=0 AND short_url='".$idLink."'") ;
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
		$table_name = $wpdb->prefix . $this->pluginID;
		
		// get the arguments
		$idLink = $_POST['idLink'];
		// Delete a entry
		$q = "DELETE FROM {$table_name} WHERE id_post=0 AND short_url='".$idLink."'"  ;
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
		
		$table_name = $wpdb->prefix . $this->pluginID;
	
		if (!$post_id && $post) $post_id = $post->ID;
		if ($post_id) $post = get_post($post_id) ; 
		
		if ($post->post_status != 'publish')
			return "non-published";
	
		// We look if the short URL already exists in the database
		$select = "SELECT short_url FROM {$table_name} WHERE id_post=".$post_id ; 
		$url = $wpdb->get_var( $select ) ;
	
		if ($url!="") {
			return site_url()."/".$url ; 
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
			if (!is_numeric($temp_id)) {
				$ok = true ; 
				$sql = "DELETE FROM {$table_name} WHERE id_post=".$post_id ; 
				$wpdb->query( $sql ) ;
				$sql = "INSERT INTO {$table_name} (id_post, short_url) VALUES ('{$post_id}', '" . $result . "')" ; 
				$wpdb->query( $sql ) ;
			}
		}
		return site_url()."/".$result ; 
	}


	/** ====================================================================================================================================================
	* Redirect to the true page
	* 
	* @return void
	*/
	
	function redirect_404() {
		global $post;
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
		
		if(is_404()) {
			$param = explode("/", $_SERVER['REQUEST_URI']) ; 
			if (preg_match("/^([a-zA-Z0-9_])*$/",$param[1],$matches)==1) {
				$select = "SELECT id_post FROM {$table_name} WHERE short_url='".$param[1]."'" ; 
				$temp_id = $wpdb->get_var( $select ) ;
				if (is_numeric($temp_id)) {
					if ($temp_id==0) {
						$select = "SELECT url_externe FROM {$table_name} WHERE short_url='".$param[1]."'" ; 
						$temp_url = $wpdb->get_var( $select ) ;
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: ".$temp_url );
						exit();
					} else {
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: ".get_permalink($temp_id));
						exit();
					}
				}
			} 
		}
	}
	
	/** ====================================================================================================================================================
	* Called when the content is printed
	* 
	* @return void
	*/	
	
	function the_content($content) {		
		$out = preg_replace_callback('#<a([^>]*?)href="([^"]*?)"([^>]*?)>#i', array($this,"replace_by_short_link"), $content);
		return $out;
	}
	
	/** ====================================================================================================================================================
	* Callback pour modifier les liens par des short links
	* 
	* @return void
	*/	
	
	function replace_by_short_link($match) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
		
		$short = $wpdb->get_var( "SELECT short_url FROM {$table_name} WHERE id_post=0 AND url_externe='".$match[2]."'"); 
		if ($short != "") {
			return '<a'.$match[1].'href="'.site_url()."/".$short.'"'.$match[3].'>';
		} else {
			return '<a'.$match[1].'href="'.$match[2].'"'.$match[3].'>';
		}
	}

}

$shorturl = shorturl::getInstance();

?>