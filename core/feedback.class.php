<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the translation of the plugin using the framework
*/
if (!class_exists("feedbackSL")) {
	class feedbackSL {

		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $plugin the name of the plugin (probably <code>str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__)))</code>)
		* @param string $pluginID the pluginID of the plugin (probably <code>$this->pluginID</code>)
		* @return feedbackSL the feedbackSL object
		*/
		function feedbackSL($plugin, $pluginID) {
			$this->plugin = $plugin ; 
			$this->pluginID = $pluginID ; 
		}
		
		/** ====================================================================================================================================================
		* Display the feedback form
		* Please note that the users will send you their comments/feedback at the email used is in the header of the main file of your plugin <code>Author Email : xxx@xxx.com</code>
		* 
		* @return void
		*/

		public function enable_feedback() {
			
			echo "<a name='top_feedback'></a><div id='form_feedback_info'></div><div id='form_feedback'>" ; 
			$_POST['plugin'] = $this->plugin ; 
			
			$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$this->plugin."/".$this->plugin.".php") ; 
			if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Email'])) {
				echo "<p>".__('Your name:', 'SL_framework')." <input id='feedback_name' type='text' name='feedback_name' value='' /></p>" ; 
				echo "<p>".__('Your email (for response):', 'SL_framework')." <input id='feedback_mail' type='text' name='feedback_mail' value='' /></p>" ; 
				echo "<p>".__('Your comments:', 'SL_framework')." </p>" ; 
				echo "<p><textarea id='feedback_comment' style='width:500px;height:400px;'></textarea></p>" ; 
				echo "<p>".__('Please note that additional information on your wordpress installation will be sent to the author in order to help the debugging if needed (such as : the wordpress version, the installed plugins, etc.)', 'SL_framework')." </p>" ; 
				echo "<p id='feedback_submit'><input type='submit' name='add' class='button-primary validButton' onclick='send_feedback(\"".$this->plugin."\", \"".$this->pluginID."\");return false;' value='".__('Send feedback','SL_framework')."' /></p>" ; 
				
				$x = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
				echo "<img id='wait_feedback' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ; 
			} else {
				echo "<p>".__('No email have been provided for the author of this plugin. Therefore, the feedback is impossible', 'SL_framework')."</p>" ; 
			}
			echo "</div>" ; 
			
		}
		
		/** ====================================================================================================================================================
		* Send the feedback form
		* 
		* @access private
		* @return void
		*/
		public function send_feedback() {
			// We sanitize the entries
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$pluginID = preg_replace("/[^a-zA-Z0-9_]/","",$_POST['pluginID']) ; 
			$name = strip_tags($_POST['name']) ; 
			$mail = preg_replace("/[^:\/a-z0-9@A-Z_.-]/","",$_POST['mail']) ; 
			$comment = strip_tags($_POST['comment']) ; 
			
			$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
			
			$to = $info_file['Email'] ; 
			
			
			
			$subject = "[".ucfirst($plugin)."] Feedback of ".$name ; 
			
			$message = "" ; 
			$message .= "From $name (".$mail.")\n\n\n" ; 
			$message .= $comment."\n\n\n" ; 
			$message .= "* Information \n" ; 
			$message .= "**************************************** \n" ; 
			$message .= "Plugin: ".$plugin."\n" ;
			$message .= "Plugin Version: ".$info_file['Version']."\n" ; 
			$message .= "Wordpress Version: ".get_bloginfo('version')."\n" ; 
			$message .= "URL (home): ".get_bloginfo('home')."\n" ; 
			$message .= "URL (site): ".get_bloginfo('siteurl')."\n" ; 
			$message .= "URL (wp): ".get_bloginfo('wpurl')."\n" ; 
			$message .= "Language: ".get_bloginfo('language')."\n" ; 
			$message .= "Charset: ".get_bloginfo('charset')."\n" ; 
			$message .= "\n\n\n" ; 
			$message .= "* Configuration of the plugin \n" ; 
			$message .= "**************************************** \n" ; 
			$options = get_option($pluginID.'_options'); 
			ob_start() ; 
				print_r($options) ; 
			$message .= ob_get_clean() ; 
			$message .= "\n\n\n" ; 
			$message .= "* Activated plugins \n" ; 
			$message .= "**************************************** \n" ; 
			$plugins = get_plugins() ; 
			$active = get_option('active_plugins') ; 
			foreach($plugins as $file=>$p){
				if (array_search($file, $active)!==false) {
					$message .= $p['Name']."(".$p['Version'].") => ".$p['PluginURI']."\n" ; 
				}
			}
			
			
			$headers = "" ; 
			if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$mail)) {
				$headers = "Reply-To: $mail\n".
						"Return-Path: $mail" ; 
			}
			
			$attachments = array();
			
			// send the email
			if (wp_mail( $to, $subject, $message, $headers, $attachments )) {
				echo "<div class='updated  fade'>" ; 
				echo "<p>".__("The feedback has been sent", 'SL_framework')."</p>" ; 
				echo "</div>" ; 
			} else {
				echo "<div class='error  fade'>" ; 
				echo "<p>".__("An error occured sending the email.", 'SL_framework')."</p><p>".__("Make sure that your wordpress is able to send email.", 'SL_framework')."</p>" ; 
				echo "</div>" ; 			
			}

			//Die in order to avoid the 0 character to be printed at the end
			die() ;

		}
		
	}
}

?>