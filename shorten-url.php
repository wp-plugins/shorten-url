<?php
/*
Plugin Name: Short URL
Description: <p>Replacing the internal function of wordpress <code>get_short_link()</code> by a bit.ly like url. </p><p>Instead of having a short link like http://www.yourdomain.com/?p=3564, your short link will be http://www.yourdomain.com/NgH5z (for instance). </p><p>You can configure: <ul><li>the length of the short link, </li><li>if the link is prefixed with a static word, </li><li>the characters used for the short link.</li></ul></p><p>This plugin is under GPL licence. </p>
Version: 1.0.2
Author: SedLex
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
		$this->tableSQL = "id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)" ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
		
		//Paramètres supplementaires
		add_action('wp_ajax_reset_link', array($this,'reset_link'));
		add_action('wp_ajax_valid_link', array($this,'valid_link'));
		add_action('wp_ajax_cancel_link', array($this,'cancel_link'));
		add_filter('get_shortlink', array($this,'get_short_link_filter'), 9, 2);
		add_action( 'template_redirect',array($this,'redirect_404'), 0 );

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
			<?php echo $this->signature ; ?>
			<p>This plugin help you sharing your post with short-links.</p>
			<!--debut de personnalisation-->
		<?php
			$maxnb = 20 ; 
			
			$count = count(get_posts(array('post_status' => 'publish', 'posts_per_page' => -1))) ;
			
			if (isset($_GET['paged'])) {
				$page_cur = $_GET['paged'] ; 
			} else {
				$page_cur = 1 ; 
			}
			
			$page_tot = ceil($count/$maxnb) ; 
			
			$page_inf = max(1,$page_cur-1) ; 
			$page_sup= min($page_tot,$page_cur+1) ; 
			
			// Mise en place de la barre de navigation
			
			$get = $_GET;
			unset($get['paged']) ;
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
	?>		
			<script>jQuery(function($){ $('#tabs').tabs(); }) ; </script>		
			<div id="tabs">
				<ul class="hide-if-no-js">
					<li><a href="#tab-results"><? echo __('Results',$this->pluginName) ?></a></li>					
					<li><a href="#tab-parameters"><? echo __('Parameters',$this->pluginName) ?></a></li>					
				</ul>
				<?php
				//==========================================================================================
				//
				// Premier Onglet 
				//		(bien verifier que id du 1er div correspond a celui indique dans la mise en 
				//			place des onglets)
				//
				//==========================================================================================
				?>
				<div id="tab-results" class="blc-section">
	
					<h3 class="hide-if-js"><? echo __('Results',$this->pluginName) ?></h3>
					<form id="posts-filter" action="<?php echo $_SERVER['PHP_SELF'] ;?>" method="get">
						<div class="tablenav top">
							<div class="tablenav-pages">
								<?php
								// Variable cachee pour reconstruire completement l'URL de la page courante
								foreach ($get as $k => $v) {
								?>
								<input name="<?php echo $k;?>" value="<?php echo $v;?>" size="1" type="hidden"/>
								<?php
								}
								?>	
								<span class="displaying-num"><?php echo $count ; ?> items</span>
								<a class="first-page<?php if ($page_cur == 1) {echo  ' disabled' ; } ?>" <?php if ($page_cur == 1) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the first page" href="<?php echo add_query_arg( 'paged', '1' );?>">&laquo;</a>
								<a class="prev-page<?php if ($page_cur == 1) {echo  ' disabled' ; } ?>" <?php if ($page_cur == 1) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the previous page" href="<?php echo add_query_arg( 'paged', $page_inf );?>">&lsaquo;</a>
								<span class="paging-input"><input class="current-page" title="Current page" name="paged" value="<?php echo $page_cur;?>" size="1" type="text"> of <span class="total-pages"><?php echo $page_tot;?></span></span>
								<a class="next-page<?php if ($page_cur == $page_tot) {echo  ' disabled' ; } ?>" <?php if ($page_cur == $page_tot) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the next page" href="<?php echo add_query_arg( 'paged', $page_sup );?>">&rsaquo;</a>
								<a class="last-page<?php if ($page_cur == $page_tot) {echo  ' disabled' ; } ?>" <?php if ($page_cur == $page_tot) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the last page" href="<?php echo add_query_arg( 'paged', $page_tot );?>">&raquo;</a>			
								<br class="clear">
							</div>
						</div>
					</form>
					<?php 
					// Mise en place du tableau 
					?>
					<table class="widefat fixed" cellspacing="0">
						<thead>
							<tr>
								<tr>
									<th id="cb" class="manage-column column-columnname" scope="col">Titre</th> 
									<th id="columnname" class="manage-column column-columnname" scope="col">Short URL</th>
								</tr>
							</tr>
						</thead>
			
						<tfoot>
							<tr>
								<tr>
									<th class="manage-column column-columnname" scope="col">Titre</th>
									<th class="manage-column column-columnname" scope="col">Short URL</th>
								</tr>
							</tr>
						</tfoot>
						<tbody>
						<?php 
						// lignes du tableau
						// boucle sur les differents elements
						query_posts('post_status=publish&paged='.$page_cur.'&posts_per_page='.$maxnb);
						while (have_posts()) {
							the_post();
							$ligne++ ; 
							
						?>
							<script language="javascript">var site="<?php echo site_url();?>"</script>
							<tr class="<?php if ($ligne%2==1) {echo  'alternate' ; } ?>" valign="top" id="ligne<?php the_ID() ; ?>"> 
								<td class="column-columnname">
									<b><?php echo the_title(); ?></b>
									<img src="<?php echo WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) ?>img/ajax-loader.gif" id="wait<?php the_ID() ; ?>" style="display: none;" />
								</td>
								<td class="column-columnname">
									<span id="lien<?php the_ID() ; ?>" ><a href="<?php echo wp_get_shortlink() ; ?>"><?php echo wp_get_shortlink() ; ?></a></span>
									<div class="row-actions">
										<span><a href="#" onclick="javascript:return false ; " class="resetLink" id="reset<?php the_ID() ; ?>">Reset</a> |</span>
										<span><a href="#" onclick="javascript:return false ; " class="forceLink" id="force<?php the_ID() ; ?>">Force</a></span>
									</div>
								</td>
							</tr>
						<?php 
						}
						// Fin du tableau
						?>
						</tbody>
					</table>
				</div>
				<?php
				//==========================================================================================
				//
				// Deuxieme Onglet 
				//		(bien verifier que id du 1er div correspond a celui indique dans la mise en 
				//			place des onglets)
				//
				//==========================================================================================
				?>
				<div id="tab-parameters" class="blc-section">
				
					<h3 class="hide-if-js"><? echo __('Parameters',$this->pluginName) ?></h3>
					<p><?php echo __('Here is the parameters of the plugin. Please modify them at your convenience.',$this->pluginName) ; ?> </p>
					<p><i><?php echo __('(Please note that the parameters will only be taken in account only for generation of new links)',$this->pluginName) ; ?> </i></p>
				
					<?php
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('Do you want to use the following characters?',$this->pluginName)) ; 
					$params->add_param('low_char', __('Low-case characters ([a-z]):',$this->pluginName)) ; 
					$params->add_param('upp_char', __('Upper-case characters ([A-Z]):',$this->pluginName)) ; 
					$params->add_param('num_char', __('Numeric characters ([0-9]):',$this->pluginName)) ; 
					
					$params->add_title(__('Do you want to use a prefix before your short URL?',$this->pluginName)) ; 
					$params->add_param('prefix', __('Prefix:',$this->pluginName), "@[^a-zA-Z0-9_]@") ; 
					
					$params->add_title(__('What is the length of your short URL (without the prefix)?',$this->pluginName)) ; 
					$params->add_param('length', __('Length:',$this->pluginName)) ; 
					
					$params->flush() ; 
					
					?>
				</div>
			</div>
			<!--fin de personnalisation-->
			<?php echo $this->signature ; ?>
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
		$q = "UPDATE  {$table_name} SET short_url = '".$link."' WHERE id_post=".$idLink ; 
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
			if (!is_numeric(temp_id)) {
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
		$car_longu = 5 ;
		$table_name = $wpdb->prefix . $this->pluginID;
		
		if(is_404()) {
			$param = explode("/", $_SERVER['REQUEST_URI']) ; 
			if (preg_match("/^([a-zA-Z0-9_])*$/",$param[1],$matches)==1) {
				$select = "SELECT id_post FROM {$table_name} WHERE short_url='".$param[1]."'" ; 
				$temp_id = $wpdb->get_var( $select ) ;
				if (is_numeric($temp_id)) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: ".get_permalink($temp_id));
					exit();
				}
			} 
		}
	}
}

$efficientRelatedPosts = shorturl::getInstance();

?>