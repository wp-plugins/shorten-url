<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class create a page with the other plugins of the author listed
*/

if (!class_exists("otherPlugins")) {
	class otherPlugins {
	   
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $nameAuthor the name of the author for which the plugins has to be listed
		* @param array $exclu a list of excluded plugin (slug name)
		* @return void 
		*/
		
		public function otherPlugins($nameAuthor="", $exclu=array()) {
			$this->nameAuthor = $nameAuthor ; 
			$this->exclu = $exclu ; 
		}
		
		/** ====================================================================================================================================================
		* Display the list of plugins
		* 
		* @return void 
		*/
		
		public function list_plugins() {
			$action = "query_plugins" ; 
			$req->author = $this->nameAuthor; 
			$req->fields = array('sections') ; 
			
			$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
			if ( is_wp_error($request) ) {
				echo  "<p>".__('An Unexpected HTTP Error occurred during the API request.', 'SL_framework' )."</p>";
			} else {
				$res = unserialize($request['body']);
				if ( ! $res ) {
					echo  "<p>???</p>";
				} else {
					foreach ($res->plugins as $plug) {
						$found_exclu = false ; 
						foreach($this->exclu as $e) {
							if ($e == $plug->slug) {
								$found_exclu = true ; 
							}
						}
						if (!$found_exclu) {
							echo "<h3>".$plug->name." (".$plug->version.")</h3>" ; 
							echo "<p style='padding-left:2cm;'>".__('Homepage:', 'SL_framework' )." <a href='".$plug->homepage." target='blank'>".$plug->homepage."</a></p>" ; 
							echo str_replace("<ul>", "<ul style='list-style-type:circle; padding-left:3cm;'>", str_replace('<p>', "<p style='padding-left:2cm;'>" , $plug->description)) ;
							$this->display_screenshot($plug->slug) ; 
							echo "<hr/>" ; 
						}
					}
				}
			}
		}
		
		/** ====================================================================================================================================================
		* Display the screenshot of a plugin
		* 
		* @param string $plugin the name of the plugin (slug name)
		* @return void 
		*/
		
		public function display_screenshot($plugin) {
			$action = "plugin_information" ; 
			$req->slug = $plugin; 
			
			$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
			if ( is_wp_error($request) ) {
				echo  "<p>".__('An Unexpected HTTP Error occurred during the API request.', 'SL_framework' )."</p>";
			} else {
				$res = unserialize($request['body']);
				if ( ! $res ) {
					echo  "<p>???</p>";
				} else {
					$screen = $res->sections['screenshots'] ; 
					$screen = str_replace("</ol>", "", $screen) ; 
					$screen = str_replace("<ol>", "", $screen) ; 
					$screen = str_replace("<li>", "<div class='screenshot_wordpress'>", $screen) ; 
					$screen = str_replace("</li>", "</div>", $screen) ; 
					//<img class="screenshot" src="http://s.wordpress.org/extend/plugins/content-table/screenshot-1.png?r=429528" alt="content-table screenshot 1">
					$screen = preg_replace('#<img([^>]*)src=\'([^\']*?)\'([^>]*)>#isU', '<a href="$2" target="blank"><img$1src="$2"$3></a>', $screen) ; 
					//$screen = preg_replace('#<img([^>]*)src=["\']?([^"\']*)["\']?#isU', '<a href="$2"><img$1src="$2"></a>', $screen) ; 
					
					echo "<div style='padding-left:100px ; '>".$screen."<div style='clear:both;'></div></div>" ; 
				}
			}
		}



	} 
}

?>