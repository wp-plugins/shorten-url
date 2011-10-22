<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 
/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the svn management of the plugin with the wordpress.org repository
*/
if (!class_exists("svnAdmin")) {
	class svnAdmin {
		
		var $isCompatible ; 
		var $reasonForIncompatibilities ; 
		var $cmd ; 
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @return svnAdmin the box object
		*/
		
		function svnAdmin() {
			$this->reasonForIncompatibilities = "" ; 
			
			// We test if we can use the exec function 
			$disabled = explode(', ', ini_get('disable_functions'));
			if(!in_array('exec', $disabled)){
				// We test if the svn function is available
				exec("svn info 2>&1", $out, $value) ; 
				if ($value==0) {
					$this->cmd = "svn" ; 
					$this->isCompatible = true ; 
					$this->reasonForIncompatibilities = "" ; 
				} else {
					// If no svn function is available we test on which system we are
					if (strtoupper(substr(php_uname(), 0, 3)) != 'WIN') {
						
						// We create a sh file to launch
						$path = WP_CONTENT_DIR."/sedlex/svn" ; 
						$rep_exists = true ; 
						if (!is_dir($path)) {
							$rep_exists = @mkdir($path, 0755, true) ; 
						}
						if ($rep_exists) {
							$path = $path."/svn.sh" ; 
							if (!is_file($path)) {
								$text = '#!/bin/sh'."\n" ; 
								$text .= 'LD_LIBRARY_PATH='.WP_PLUGIN_DIR.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)).'svn_bin:/usr/lib'."\n" ; 
								$text .= 'PATH='.WP_PLUGIN_DIR.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)).'svn_bin:$PATH'."\n" ; 
								$text .= 'export LD_LIBRARY_PATH PATH'."\n" ; 
								$text .= 'exec svn "$@"' ; 
								@file_put_contents($path, $text);		
								@chmod($path,0777); 								
							} 
							if (!is_file($path)) {
								$this->isCompatible = false ; 
								$this->reasonForIncompatibilities =  sprintf(__("The file %s cannot be created. Therefore, no svn script can be created. Please make the folder writable or visit %s to install a SubVersion package on your server.", 'SL_framework'), "<code>".$path."</code>", "<a href='http://subversion.apache.org/packages.html'>Apache SubVersion</a>") ; 
							} else {
								$svnpath = WP_PLUGIN_DIR.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__)).'bin/svn' ;
								@chmod($svnpath,0777); 
								$this->cmd = $path ; 
								$this->isCompatible = true ; 
								$this->reasonForIncompatibilities = "" ;
							}
						} else {
							$this->isCompatible = false ; 
							$this->reasonForIncompatibilities =  sprintf(__("The folder %s does not exists and cannot be created. Therefore, no svn script can be created in that folder. Please create this folder (and make it writable) or visit %s to install a SubVersion package on your server.", 'SL_framework'), "<code>".$path."</code>", "<a href='http://subversion.apache.org/packages.html'>Apache SubVersion</a>") ; 
						}
					} else {
						exec("svn.exe info 2>&1", $out, $value) ; 
						if ($value==0) {
							$this->cmd = "svn.exe" ; 
							$this->isCompatible = true ; 
							$this->reasonForIncompatibilities = "" ; 
						} else {
							$this->isCompatible = false ; 
							$this->reasonForIncompatibilities =  sprintf(__("The operating system of your server is %s. No installation of SVN has been found. Please visit %s to install a SubVersion package on your server.", 'SL_framework'), "<code>".php_uname()."</code>", "<a href='http://subversion.apache.org/packages.html'>Apache SubVersion</a>") ; 
						}
					}
				}
				
			} else {
				$this->isCompatible = false ; 
				$this->reasonForIncompatibilities =  __("The exec function is disabled on your installation. This function is mandatory to be able to use SVN.", 'SL_framework') ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Update or Checkout the following local cache
		* 
		* @return  integer the return value of the svn command
		*/
		function update_checkout($root, $repository, $print=true) {
			$result = $this->my_exec($this->cmd." checkout http://svn.wp-plugins.org/content-table /homez.131/gruson/www/wp-content/sedlex/svn/testdemalade ") ; 
			echo "#" ; 
			echo ($result); 
			echo "#" ; 
			return 33 ; 
			
			echo ".".$this->cmd."." ; 
			
			if  (is_dir($root."/.svn")) {
				echo "<p>".__('SVN command:', 'SL_framework')." <code>update</code></p>\n" ; 
				$return_value = $this->update($root, $print) ; 					
			} else {
				echo "<p>".__('SVN command:', 'SL_framework')." <code>checkout $repository $root </code></p>\n" ; 
				$return_value = $this->checkout($root, $repository, $print) ; 
			}
			return $return_value ; 
		}
		
		function my_exec($cmd){
			echo passthru($cmd, $return);
			return $return ; 
		}
		
		
		/** ====================================================================================================================================================
		* Update the following local cache
		* 
		* @return 
		*/
		function update($root, $print=true) {
			$value = 99 ; 
			
			chdir($root) ; 
			
			exec($this->cmd." cleanup 2>&1", $out, $value) ; 
			exec($this->cmd." revert --recursive . 2>&1", $out, $value) ; 
			exec($this->cmd." update -r BASE 2>&1", $out, $value) ; 
			
			// On affiche
			if ($print) {
				echo "<p class='console'>\n" ; 
				echo sprintf(__('%s returns the following code: %s', 'SL_framework'), "*Update*", "<b>".$value."</b><br/>")."\n" ; 
				foreach ($out as $l) {
					echo $l."<br/>\n" ; 
				}
				echo "</p>\n" ; 
			}
			
			return $value ; 
		}
		
		/** ====================================================================================================================================================
		* Checkout
		* 
		* @return void
		*/
		function checkout($root, $repository, $print=true) {
			$value = 99 ; 
			
			// we delete the repository if exist
			if (is_dir($root)) {
				Utils::rm_rec($root) ; 
			}
			// we create the root file
			if (!is_dir($root)) {
				@mkdir($root, 0777, true) ; 
			}
			
			chdir($root) ; 
			
			exec($this->cmd." -q --non-interactive --ignore-externals --force checkout ".$repository." ".$root." 2>&1", $out, $value) ; 
			
			// On affiche
			if ($print) {
				echo "<p class='console'>\n" ; 
				echo sprintf(__('%s returns the following code: %s ', 'SL_framework'), "*Checkout*", "<b>".$value."</b>")."<br/>\n" ; 
				if ($value == 0) {
					foreach ($out as $l) {
						echo $l."<br/>\n" ; 
					}
				} else {
					echo "\n".__('Checkout has failed! Please retry ...', 'SL_framework')."<br/>\n" ; 
					echo __('Indeed, it is known that the Checkout command have some difficulties to work. You may have to re-test several times (1-20 times) to finally succeed.', 'SL_framework')."<br/>\n" ; 
					echo __('Do not panic: once the checkout have worked one time, the update command will be used and it is far more robust!', 'SL_framework')."<br/>\n" ; 
					if (count($out)>0) {
						echo "<br/>".__('NOTE: The command outputs the following information:', 'SL_framework')."<br/>\n" ; 
						$isBeginSync = false ; 
						foreach ($out as $l) {
							if(strpos($l, $root) === FALSE) {
								echo $l."<br/>\n" ; 
							} else {
								if (!$isBeginSync) {
									$isBeginSync = true ; 
									echo __('The checkout have begun but have been interrupted without any reason!', 'SL_framework')."<br/>\n" ; 
								}
							}
						}
					} 
				} 
				echo "</p>\n" ; 
			}
			
			
			// If it is unsuccessuful,  we delete the local cache
			if ($value!=0) {
				if (is_dir($root)) {
					Utils::rm_rec($root) ; 
				}
			}
			
			return $value ; 
		}
		
		
		/** ====================================================================================================================================================
		* Add
		* 
		* @return void
		*/
		function add($root, $file) {
			$value = 99 ; 
			
			$f = str_replace($root,"",$file) ; 
			$added = false ; 
			
			// Change directory
			chdir($root) ; 
			// Clean the SVN stuff (to remove any lock)
			exec($this->cmd." cleanup 2>&1", $out) ; 
	
			// We first check that the directory are SVN compliant :)
			$ad = explode("/",str_replace(basename($f),"",$f)) ; 
			$list_f = $root ; 
			foreach($ad as $d) {
				$list_f .= $d."/" ; 
				if (!is_dir($list_f)) {
					@mkdir($list_f, 0777, true) ; 
				}
				if (!is_dir($list_f.".svn/")) {
					$added = true ; 
					exec($this->cmd." add ".str_replace($root,'',$list_f)." 2>&1", $out) ; 
				}
			}
			
			// We add the file
			if (!$added) {
				exec($this->cmd." add ".$f." 2>&1", $out, $value) ; 
			}
			echo "<p class='console'>\n" ; 
			echo sprintf(__('%s returns the following code: %s', 'SL_framework'), "*Add*", "<b>".$value."</b><br/>")."\n" ; 
			foreach ($out as $l) {
				echo $l."<br/>\n" ; 
			}
			echo "</p>\n" ; 
			
			return $value ; 
		}
		
		
		/** ====================================================================================================================================================
		* SVN delete
		* 
		* @return void
		*/
		function delete($root, $file) {
			
			$value = 99 ; 
			
			chdir($root) ; 
			
			exec($this->cmd." cleanup 2>&1", $out, $value) ; 
			exec($this->cmd." delete ".$file." 2>&1", $out, $value) ; 
			
			// On affiche
			if ($print) {
				echo "<p class='console'>\n" ; 
				echo sprintf(__('%s returns the following code: %s', 'SL_framework'), "*Delete*", "<b>".$value."</b><br/>")."\n" ; 
				foreach ($out as $l) {
					echo $l."<br/>\n" ; 
				}
				echo "</p>\n" ; 
			}
			
			return $value ; 
		}
		/** ====================================================================================================================================================
		* Commit
		* 
		* @return void
		*/
		function commit($root, $login, $pass, $comment) {
			$value = 99 ; 
			// Change directory
			chdir($root) ; 
								
			// Clean the SVN stuff (to remove any lock)
			exec($this->cmd." cleanup 2>&1", $out) ; 
			
			// We commit the change
			exec($this->cmd." commit ".$f." --username ".$login." --password ".$pass." --message \"".$comment."\" 2>&1", $out, $value) ; 
	
			echo "<p class='console'>\n" ; 
				echo sprintf(__('%s returns the following code: %s', 'SL_framework'), "*Commit*", "<b>".$value."</b><br/>")."\n" ; 
			foreach ($out as $l) {
				echo $l."<br/>\n" ; 
			}
			echo "</p>\n" ; 
			
			return $value ; 
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
				$title = sprintf(__('Update the SVN repository %s with your current local plugin files', 'SL_framework'),'<em>'.$plugin.'</em>') ;
			}
			if ($sens=="to_local") {
				$title = sprintf(__('Overwrite the local plugin %s files with files stored the SVN repository', 'SL_framework'),'<em>'.$plugin.'</em>') ; ;
			}
			
			ob_start() ; 
			echo "<div id='svn_div'>" ; 
			svnAdmin::func_svn_prepare($plugin, $sens) ; 	
			echo "</div>" ; 
			$content = ob_get_clean() ; 	

			$popup = new popupAdmin($title, $content, "") ; 
			$popup->render() ; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for preparing SVN command
		* 
		* @access private
		* @return void
		*/			
		function svn_prepare() {
			// get the arguments
			$plugin = $_POST['plugin'];
			$sens = $_POST['sens'];
			svnAdmin::func_svn_prepare($plugin, $sens) ; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Function for preparing SVN command
		* 
		* @access private
		* @return void
		*/			
		
		function func_svn_prepare($plugin, $sens) {
		
			$local_cache = WP_CONTENT_DIR."/sedlex/svn/".$plugin ; 
			$repository = "http://svn.wp-plugins.org/".$plugin ; 
			$svn = new svnAdmin() ; 
			// On met a jour le cache local !
			echo "<h3>".__('Update the local cache', 'SL_framework')."</h3>" ; 
			$resulta = $svn->update_checkout($local_cache, $repository) ; 
			if ($resulta==0) {
				echo "<br/><h3>".__('Compare the local cache with the plugins files', 'SL_framework')."</h3>" ; 
				$folddiff = new foldDiff() ; 
				
				if ($sens=="to_repo") {
					// DIFF
					$result = $folddiff->diff(WP_PLUGIN_DIR."/".$plugin, $local_cache."/trunk") ; 
					
					$folddiff->render() ; 
					// Confirmation asked
					echo "<h3>".__('Confirmation', 'SL_framework')."</h3>" ; 
					
					echo "<p>".__('Commit comment:', 'SL_framework')."</p>" ; 
					echo 	"<p><textarea cols='70' rows='5' name='svn_comment' id='svn_comment'/></textarea></p>\n" ;  
					echo "<p id='svn_button'><input onclick='svnToRepo(\"".$plugin."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Yes, the SVN version will be deleted and be replaced by the local version', 'SL_framework')."' /></p>" ;  
					echo "<p><img id='wait_svn' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 
				}	
				
				
				if ($sens=="to_local") { 
					$folddiff->diff($local_cache."/trunk", WP_PLUGIN_DIR."/".$plugin) ; 
					$folddiff->render() ; 
					// Confirmation asked
					echo "<h3>".__('Confirmation', 'SL_framework')."</h3>" ; 
					
					echo "<p id='svn_button'><input onclick='repoToSvn(\"".$plugin."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Yes, the local version will be deleted and be replaced by the files stored on the SVN repository', 'SL_framework')."' /></p>" ;  
					echo "<p><img id='wait_svn' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 

				}
			} else {
				echo "<p><a href='#' onClick='reTrySvnPreparation(\"".$plugin."\", \"to_repo\"); return false;'>".__('Retry the SVN preparation!', 'SL_framework')."</a> " ; 
				echo " <img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/refresh.png'>" ; 
				echo " <img id='wait_svn' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 
			} 
		}
		
		/** ====================================================================================================================================================
		* Callback for SVN to repository
		* 
		* @access private
		* @return void
		*/		
		function svn_to_repo() {
			// get the arguments
			$plugin = $_POST['plugin'];
			$comment = $_POST['comment'];

			$path = WP_PLUGIN_DIR."/".$plugin ; 
			$local = WP_CONTENT_DIR."/sedlex/svn/".$plugin ; 

			// We get the list of the diff
			$svn = new svnAdmin() ; 
			$diff = new foldDiff();
			$fold = $diff->diff($path, $local."/trunk");
			
			foreach ($fold as $f) {
			
				// We add the file if needed
				if (($f[1]==2)&&($f[2]!="directory")) { // ADDED
					// On cree le repertoire s il n existe pas
					$directory = str_replace(basename($f[3]),"",$local."/trunk/".$f[3]) ; 
					
					if (!is_dir($directory)) {
						mkdir($directory, 0777, true) ; 
					}
					
					copy ( $path."/".$f[3], $local."/trunk/".$f[3]) ; 
					
					echo "<p>".sprintf(__('Add a file: %s', 'SL_framework')," <code>".$f[3]."</code>")."</p>\n" ; 
					
					$result = $svn->add($local."/trunk/",$f[3]) ; 
					if ($result != 0) {
						// We delete the file 
						unlink($local."/trunk/".$f[3]) ; 
					}
				}
				
				if (($f[1]==3)&&($f[2]!="directory")) { // MODIFIED
					copy ( $path."/".$f[3], $local."/trunk/".$f[3]) ; 
				}
				
				if (($f[1]==1)&&($f[2]!="directory")) {// DELETED
					echo "<p>".sprintf(__('Delete a file: %s', 'SL_framework')," <code>".$f[3]."</code>")."</p>\n" ; 
					$svn->delete($local."/trunk/",$f[3]) ; 
				}
			}
									
			echo "<p>".__('Final commit:', 'SL_framework')." <code>commit</code></p>\n" ; 
			$svn->commit($local, get_option('SL_framework_SVN_login', ""), get_option('SL_framework_SVN_password', ""), $comment) ; 
			
			echo "<p> </p>" ; 
			echo "<p>".__("The commit has ended and you may restart a normal life by closing the window...", 'SL_framework')."</p>" ; 
			echo "<p>".__("Thank you!", 'SL_framework')."</p>" ; 
			
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback for SVN to repository
		* 
		* @access private
		* @return void
		*/		
		
		function repo_to_svn() {
			// get the arguments
			$plugin = $_POST['plugin'];
			$comment = $_POST['comment'];

			$path = WP_PLUGIN_DIR."/".$plugin ; 
			$local = WP_CONTENT_DIR."/sedlex/svn/".$plugin ; 

			// We get the list of the diff
			$svn = new svnAdmin() ; 
			$diff = new foldDiff();
			$fold = $diff->diff($local."/trunk", $path);
			
			echo "<p class='console'>\n" ; 
			echo "To local: <br/>\n" ;
			
			foreach ($fold as $f) {
						
				// We add the file if needed
				if (($f[1]==2)&&($f[2]!="directory")) { // ADDED
					// On cree le repertoire s il n existe pas
					$directory = str_replace(basename($f[3]),"",$path."/".$f[3]) ; 
					
					if (!is_dir($directory)) {
						mkdir($directory, 0777, true) ; 
					}
					
					copy ( $local."/trunk/".$f[3], $path."/".$f[3] ) ; 
					echo "A ".$f[3]."<br/>\n" ;
					
				}
				
				if (($f[1]==3)&&($f[2]!="directory")) { // MODIFIED
					copy ($local."/trunk/".$f[3], $path."/".$f[3]) ; 
					echo "U ".$f[3]."<br/>\n" ;
				}
				
				if (($f[1]==1)&&($f[2]!="directory")) {// DELETED
					unlink ($path."/".$f[3]) ; 
					echo "D ".$f[3]."<br/>\n" ;
				}
			}
			echo "</p>\n" ; 
	
			echo "<p> </p>" ; 
			echo "<p>".__("The overwrite has ended and you may restart a normal life by closing the window...", 'SL_framework')."</p>" ; 
			echo "<p>".__("Thank you!", 'SL_framework')."</p>" ; 
			
			die() ; 
		}
		
		
		
		
		
		
		
		
		
		
		
	}
}

?>