<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the translation of the plugin using the framework
*/
if (!class_exists("translationSL")) {
	class translationSL {
	
		var $domain ; 
		var $plugin ; 
		var $path ; 
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $domain the name of the domain (probably <code>$this->pluginID</code>)
		* @param string $plugin the name of the plugin (probably <code>str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__)))</code>)
		* @return translationSL the translationSL object
		*/
		function translationSL($domain, $plugin) {
			$this->domain = $domain ; 
			$this->path = WP_PLUGIN_DIR."/".$plugin ; 
			$this->plugin = $plugin ; 
			
			$path = $this->path ; 			
			
			if ($domain != "SL_framework") {
				// We create the lang dir
				if (!is_dir($path."/lang/")) {
					mkdir($path."/lang/", 0777, true) ; 
				}
				
				// We check if the .pot file exist
				if (is_file($path."/lang/".$domain .".pot")) {
					// We delete the file
					@unlink($path."/lang/".$domain .".pot");
				} 
				// We generate a new POT file
				$content = "Content-Transfer-Encoding: 8bit\n\n" ; 
				$php = $this->get_php_files($path) ; 
				
				foreach ($php as $f) {
					$lines = file($path."/".$f) ; 
					
					$i = 0 ; 
					foreach($lines as $l) {
						$i++ ; 
						$match_array = array("@__[ ]*\([ ]*\\\"([^\\\"]*)\\\"([^)]*)this->pluginID\)@", 
											 "@__[ ]*\([ ]*'([^']*)'([^)]*)this->pluginID\)@")  ; 
						foreach ($match_array as $reg) {
							if (preg_match_all($reg,$l, $match,PREG_SET_ORDER)) {
								foreach($match as $m) {
									$val[0] = trim($m[1]) ; 
									$pos = strpos($content,'msgid "'.$val[0].'"') ; 
									if ($pos===false) {
										// We translate only the text of the domain of the plugin
										$content .= "#: ".$f.":".$i."\n" ; 
										$content .= "#@ ".$domain."\n" ; // domain
										$value = $val[0] ; 
										// If the string is between simple quote, we escape the double quote
										if ($reg==$match_array[1]) 
											$value = str_replace("\"", "\\\"",$val[0]) ; 
										$content .= 'msgid "'.$value.'"'."\n" ; 
										$content .= 'msgstr ""'."\n\n" ;  
									} else {
										// If the text is already in the POT file, we only add the line number and the file
										$temp = explode("#@ ".$domain."\n".'msgid "'.$val[0].'"'."\n", $content) ; 
										$content = $temp[0]."#: ".$f.":".$i."\n"."#@ ".$domain."\n".'msgid "'.$val[0].'"'."\n".$temp[1] ; 
									}
								}
							} 
						}
					}
				}
				
				file_put_contents($path."/lang/".$domain .".pot", $content) ; 
				
			} else {
			
				// We create the lang dir for the core translation
				$plugin = explode("/",str_replace(basename( __FILE__),"",plugin_basename(__FILE__))); 
				$plugin = $plugin[0] ; 
				$path = WP_PLUGIN_DIR."/".$plugin ; 
				
				if (!is_dir($path."/core/lang/")) {
					mkdir($path."/core/lang/", 0777, true) ; 
				}
				
				// We check if the .pot file exist
				if (is_file($path."/core/lang/SL_framework.pot")) {
					// We delete the file
					@unlink($path."/core/lang/SL_framework.pot");
				} 
				// We generate a new POT file
				$content = "Content-Transfer-Encoding: 8bit\n\n" ; 
				$php = $this->get_php_files($path) ; 
				
				foreach ($php as $f) {
					$lines = file($path."/".$f) ; 
					
					$i = 0 ; 
					foreach($lines as $l) {
						$i++ ; 
						$match_array = array("@__[ ]*\([ ]*\\\"([^\\\"]*)\\\"([^)]*)SL_framework([^)]*)\)@", 
											 "@__[ ]*\([ ]*'([^']*)'([^)]*)SL_framework([^)]*)\)@")  ; 
						foreach ($match_array as $reg) {
							if (preg_match_all($reg,$l, $match,PREG_SET_ORDER)) {
								foreach($match as $m) {
									$val[0] = trim($m[1]) ; 
									$pos = strpos($content,'msgid "'.$val[0].'"') ; 
									if ($pos===false) {
										// We translate only the text of the domain of the plugin
										$content .= "#: ".$f.":".$i."\n" ; 
										$content .= "#@ SL_framework\n" ; // domain
										$value = $val[0] ; 
										// If the string is between simple quote, we escape the double quote
										if ($reg==$match_array[1]) 
											$value = str_replace("\"", "\\\"",$val[0]) ; 
										$content .= 'msgid "'.$value.'"'."\n" ; 
										$content .= 'msgstr ""'."\n\n" ;  
									} else {
										// If the text is already in the POT file, we only add the line number and the file
										$temp = explode("#@ SL_framework\n".'msgid "'.$val[0].'"'."\n", $content) ; 
										$content = $temp[0]."#: ".$f.":".$i."\n"."#@ SL_framework\n".'msgid "'.$val[0].'"'."\n".$temp[1] ; 
									}
								}
							} 
						}
					}
				}
				
				file_put_contents($path."/core/lang/SL_framework.pot", $content) ; 	
			}
		}
		
		/** ====================================================================================================================================================
		* Enable the translation and display the management interface
		* Please note that the translators will be able to send you their translation so that you can add them to your repository (the email used is in the header of the main file of your plugin <code>Author Email : xxx@xxx.com</code>)
		* 
		* @return void
		*/

		public function enable_translation() {
			
			require('translation.inc.php') ;
			
			echo "<a name='top_translation'></a><div id='summary_of_translations'>" ; 
			
			$_POST['domain'] = $this->domain ; 
			$_POST['plugin'] = $this->plugin ; 
			
			$this->summary_translations() ; 
    		
    		echo "</div><a name='edit_translation'></a>" ; 
    		echo "<div id='zone_edit'></div>" ; 

		}


		/** ====================================================================================================================================================
		* Search the plugin directory for the php files
		* 
		* @access private
		* @param string $root the root folder from which the php file are to be searched
		* @return array list of the php files
		*/
		
		private function get_php_files($root, $other='') {
			
			@chmod($root."/".$other, 0755) ; 
			
			$dir=opendir($root."/".$other);
			
			$folder = array() ; 
			$php = array() ; 
			
			while ($f = readdir($dir)) {
				if (is_dir($root."/".$other.$f)) {
					if (preg_match("/^[^.].*/i", $f)) {
						$folder[] = $other.$f ;
					}
				}
				if (is_file($root."/".$other.$f)) {
					if (preg_match("/^[^.].*\.php/i", $f)) {
						$php[] = $other.$f ;
					}
				}
			}
	
			foreach ($folder as $f) {
				$php = array_merge($php, $this->get_php_files($root, $f."/")) ; 
			}
			return $php ; 
		}


		/** ====================================================================================================================================================
		* Callback function for displaying the summary of translation from javascript
		* 
		* @access private
		* @return void
		*/
		
		function update_summary () {
			translationSL::summary_translations() ; 
			die() ; 
		}

		/** ====================================================================================================================================================
		* Displaying the summary of translation
		* 
		* @access private
		* @return void
		*/

		function summary_translations () {
		
			require('translation.inc.php') ;

			$domain = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['domain']) ; 
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$path = WP_PLUGIN_DIR."/".$plugin ; 
			
			$plugin_lien = $plugin;
			
			
			if (strpos($domain,"SL_framework")!==false) {
				$plugin = explode("/",str_replace(basename( __FILE__),"",plugin_basename(__FILE__))); 
				$plugin = $plugin[0] ; 
				$path = WP_PLUGIN_DIR."/".$plugin ; 
			}
			
			// we look into the adequate folder
			if (strpos($domain,"SL_framework")!==false) {
				@chmod($path."/core/lang/", 0755) ; 
				$dir = @opendir($path."/core/lang/"); 
				$dom = "SL_framework" ; 
			} else {
				@chmod($path."/lang/", 0755) ; 
				$dir = @opendir($path."/lang/"); 
				$dom = $domain ; 
			}
			$file = array() ; 
			while ($f = readdir($dir)) {
				if (preg_match("/^[^.].*\.po$/i", $f)) 
					$file[] = $f ;
			}
			
			$nb = count($file) ; 
			
			if (strpos($domain,"SL_framework")!==false) {
				echo "<h3>".sprintf(__("The available translation for the SL framework (contained in the '%s' folder)",'SL_framework'),$plugin)."</h3>" ; 
				echo "<p>".sprintf(__("There is %d languages supported for the 'SL framework'.",'SL_framework'),$nb)."</p>" ; 	
				
				// We count the number of sentences to be translated
				$content_pot = file($path."/core/lang/SL_framework.pot") ;
				$all_count_pot = 0 ; 
				foreach ($content_pot as $ligne_pot) {
					if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_pot))) {
						$all_count_pot ++ ; 
					}
				}
				echo "<p>".sprintf(__("There is %d sentence to be translated in the framework.",'SL_framework'),$all_count_pot)."</p>" ; 	
				
				
			} else {
				echo "<h3>".__("The available translation for this plugin",'SL_framework')."</h3>" ; 
				echo "<p>".sprintf(__("There is %d languages supported for this plugin.",'SL_framework'),$nb)."</p>" ; 
				
				// We count the number of sentences to be translated
				$content_pot = file($path."/lang/".$domain.".pot") ;
				$all_count_pot = 0 ; 
				foreach ($content_pot as $ligne_pot) {
					if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_pot))) {
						$all_count_pot ++ ; 
					}
				}
				echo "<p>".sprintf(__("There is %d sentence to be translated in this plugin.",'SL_framework'),$all_count_pot)."</p>" ; 	
				echo "<p><img src='".WP_PLUGIN_URL."/".$plugin."/core/img/info.png'/>".sprintf(__("Please note that the translation for the framework is available in the %s submenu (see the %s tab).",'SL_framework'),"<em>'<a href='".add_query_arg(array('page'=>'sedlex.php'))."'>".__('About...','SL_framework')."</a>'</em> ", "'<em>".__('Manage translation of the framework','SL_framework')."</em>'")."</p>" ; 	

				
				
			}
			sort($file) ; 
			$i = 1 ; 
			
			$table = new adminTable() ; 
			$table->title (array(__('Language','SL_framework'), __('Ratio %','SL_framework'), __('Translators','SL_framework'))) ; 
			
			foreach ($file as $f) {
					
				if (preg_match("@".$dom."-([a-z]{2}_[a-z]{2})\.po@i",$f, $match)) {
					$code = $match[1] ; 
					$flag = $code_locales[$code]['country-www'] ; 
					$native = $code_locales[$code]['lang-native'] ; 
				} else {
					$flag = 'unknown' ; 
					$native = '??' ; 
				}
				

				// We look for the position
				$style = "" ; 
				$num = 0 ; 
				if ($flag != 'unknown') {
					$i = 1 ; 
					foreach ($flags as $fl) {
   						if ($fl == $flag) {
   							$num = $i;	
   						}
   						$i++ ; 
					}

					// We convert the position of the flag into coordinates of the flags_sprite.png image 
					// Note that there is 12 flags per line
					
					$number_of_flags_per_line = 12 ; 
					$col = $num % $number_of_flags_per_line ;
					$line = floor($num / $number_of_flags_per_line) ;
					
					// Each flag has a width of 18px and an height of 12px
					$style = "background-position: ".($col*-18)."px ".($line*-12)."px;" ; 
				}
				
				// We check if the present author have modify a translation here
				$options = get_option('SL_framework_options');
				if (strpos($domain,"SL_framework")!==false) {
					$info = translationSL::get_info(file($path."/core/lang/".$f), file($path."/core/lang/SL_framework.pot")) ; 
				} else {
					$info = translationSL::get_info(file($path."/lang/".$f), file($path."/lang/".$domain.".pot")) ; 
				}
				$nameTranslator = $options['nameTranslator'] ; 
				$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
				$isEmailAuthor = false ; 
				if (strpos($domain,"SL_framework")!==false) {
					if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Framework_Email'])) {
						$isEmailAuthor = true ; 
					}				
				} else {
					if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Email'])) {
						$isEmailAuthor = true ; 
					}
				}
				
				// We build the table
				$cel_lang = new adminCell("<span class='pt_flag' style='".$style."'>&nbsp;</span><b>".$native."</b>") ;
				$cel_lang->add_action(__('Modify','SL_framework'), "modify_trans('".$plugin_lien."','".$domain."', '".$code."')" ) ; 
				if (($isEmailAuthor) && (strlen($nameTranslator)>3) && (strpos($info, $nameTranslator)>0)) {
					if (strpos($domain,"SL_framework")!==false) {
						$cel_lang->add_action(__('Send to the author of the framework','SL_framework'), "send_trans(\"".$plugin_lien."\",\"SL_framework\", \"".$code."\")") ; 
					} else {
						$cel_lang->add_action(__('Send to the author of the plugin','SL_framework'), "send_trans(\"".$plugin_lien."\",\"".$domain."\", \"".$code."\")") ; 
					}
				}
				$info = explode("######", $info, 2) ; 
				$cel_pour = new adminCell($info[0]) ;
				$cel_tran = new adminCell($info[1]) ;
				$table->add_line(array($cel_lang, $cel_pour, $cel_tran), $code) ; 
				
				$i++ ; 
			}
			echo $table->flush() ; 
			
			echo "<h3>".__('Add a new translation','SL_framework')."</h3>" ; 
			echo "<p>".__('You may add a new translation hereafter (Please note that it is recommended to send your translation to the author so that he would be able to add your translation to the future release of the plugin !)','SL_framework')."</p>" ; 
			echo "<SELECT id='new_translation' name='new_translation' size='1'>" ; 
			foreach ($code_locales as $c => $array) {
				$already_translated = false ; 
				foreach ($file as $f) {
					if (preg_match("@".$dom."-([a-z]{2}_[a-z]{2})\.po@i",$f, $match)) {
						if ($match[1]==$c) {
							$already_translated = true ; 
						}
					}
				}
				if (!$already_translated ) 
    				echo "<option name='$c' value='$c' id='$c'>".$array['lang-native']."</option>\n" ; 
			}
			echo "</SELECT>" ; 
			echo "<input type='submit' name='add' class='button-primary validButton' onclick='translate_add(\"".$plugin_lien."\",\"".$domain."\");return false;' value='".__('Add','SL_framework')."' />" ; 
				
			$x = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
			echo "<img id='wait_translation_add' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ;     		
		}
		
		
		/** ====================================================================================================================================================
		* Callback function for adding a new translation
		* 
		* @access private
		* @return void
		*/

		function translate_add () {
			echo "<hr/>" ;
			require('translation.inc.php') ;
			// We sanitize the language
			$code = preg_replace("/[^a-zA-Z_]/","",$_POST['idLink']) ; 
			$domain = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['domain']) ; 
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$native = $code_locales[$code]['lang-native'] ; 
			
			$plugin_lien = $plugin ; 

			if (strpos($domain,"SL_framework")!==false) {
				$plugin = explode("/",str_replace(basename( __FILE__),"",plugin_basename(__FILE__))); 
				$plugin = $plugin[0] ; 
				$path = WP_PLUGIN_DIR."/".$plugin ; 
			}

			echo "<h3>".sprintf(__('Adding a new translation for this language: %s','SL_framework'),"$native ($code)")."</h3>" ; 
			
			// Create the table
			$table = new adminTable() ;
			$table->title (array(__('Sentence to translate','SL_framework'), __('Translation','SL_framework'))) ; 
			$table->removeFooter() ;
			if (strpos($domain,"SL_framework")!==false) {
				$content_pot = file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework.pot") ;
			} else {
				$content_pot = file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain .".pot") ;
			}
			$i=0 ; 
			foreach ($content_pot as $ligne) {
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne), $match)) {
					$cel1 = new adminCell(htmlentities($match[1])) ;
					$cel2 = new adminCell("<input id='trad".$i."' type='text' name='trad".$i."' value='' style='width:100%' />") ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$i++ ; 
				}
			}
			echo $table->flush() ;
			
			$options = get_option('SL_framework_options');
			echo "<p>".__('Your name:','SL_framework')." <input id='nameAuthor' type='text' name='nameAuthor' value='".$options['nameTranslator']."'/> ".__('(if your name/pseudo is already in the list, there is no need to fill this input)','SL_framework')."</p>" ; 
			echo "<p>".__('Your email or your website:','SL_framework')." <input id='emailAuthor' type='text' name='emailAuthor' value='".$options['emailTranslator']."'/></p>" ; 
			
			echo "<input type='submit' name='create' class='button-primary validButton' onclick='translate_create(\"".$plugin_lien."\",\"".$domain."\",\"".$code."\", $i);return false;' value='".__('Create the translation files','SL_framework')."' />" ; 
			$x = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
			echo "<img id='wait_translation_create' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ; 

			//Die in order to avoid the 0 character to be printed at the end
			die() ; 
		}

		/** ====================================================================================================================================================
		* Callback function for modifying a translation
		* 
		* @access private
		* @return void
		*/

		function translate_modify () {
			echo "<hr/>" ;
			require('translation.inc.php') ;
			// We sanitize the language
			$lang = preg_replace("/[^a-zA-Z_]/","",$_POST['lang']) ; 
			$domain = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['domain']) ; 
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$native = $code_locales[$lang]['lang-native'] ; 
			
			$plugin_lien = $plugin ; 
			
			if (strpos($domain,"SL_framework")!==false) {
				$plugin = explode("/",str_replace(basename( __FILE__),"",plugin_basename(__FILE__))); 
				$plugin = $plugin[0] ; 
				$path = WP_PLUGIN_DIR."/".$plugin ; 
			}	
			
			echo "<h3>".sprintf(__('Modifying the translation for this language: %s','SL_framework'), "$native ($lang)")."</h3>" ; 
			
			// Create the table
			$table = new adminTable() ;
			$table->title (array(__('Sentence to translate','SL_framework'), __('Translation','SL_framework'))) ; 
			$table->removeFooter() ;
			if (strpos($domain,"SL_framework")!==false) {
				$content_pot = file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework.pot") ;
				$content_po = file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po") ;
			} else {
				$content_pot = file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain .".pot") ;
				$content_po = file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po") ;			
			}
			$i=0 ; 
			$a_verifier_next = false ; 
			foreach ($content_pot as $ligne) {
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne), $match)) {
					$cel1 = new adminCell(htmlentities($match[1])) ;
					$value = "" ; 
					// We search the former translation
					$a_verifier_next = false ; 
					foreach ($content_po as $ligne_po) {
						if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match2)) {
							if ($match[1]==$match2[1]) {
								$a_verifier_next = true ; 
							}
						}
						if ($a_verifier_next) {
							if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match2)) {
								$value = $match2[1] ; 
								$a_verifier_next = false ; 
							}
						}
					}
					// Convert in UTF8 if needed
					if (!seems_utf8($value)) {
						$value = utf8_encode($value) ; 
					}
					
					$cel2 = new adminCell("<input id='trad".$i."' type='text' name='trad".$i."' value='".$value."' style='width:100%' />") ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$i++ ; 
				}
			}
			echo $table->flush() ;
			
			$options = get_option('SL_framework_options');
			echo "<p>".__('Your name:','SL_framework')." <input id='nameAuthor' type='text' name='nameAuthor' value='".$options['nameTranslator']."'/></p>" ; 
			echo "<p>".__('Your email or your website:','SL_framework')." <input id='emailAuthor' type='text' name='emailAuthor' value='".$options['emailTranslator']."'/></p>" ; 
			
			echo "<input type='submit' name='create' class='button-primary validButton' onclick='translate_save_after_modification(\"".$plugin_lien."\",\"".$domain."\",\"".$lang."\", $i);return false;' value='".__('Modify the translation files','SL_framework')."' />" ; 
			$x = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
			echo "<img id='wait_translation_modify' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ; 

			//Die in order to avoid the 0 character to be printed at the end
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Get the relevant info on a po file
		* 
		* $param array $content_po array of line of the file .po
		* $param array $content_pot array of line of the file .pot
		* @access private
		* @return void
		*/
		function get_info($content_po, $content_pot) {
			// We search in the pot file to check if all sentences are translated
			$count = 0 ; 
			$all_count = 0 ; 
			$suivant_a_verifier = false ; 
			foreach ($content_pot as $ligne_pot) {
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_pot), $match)) {
					$all_count ++ ; 
					foreach ($content_po as $ligne_po) {
						if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match2)) {
							if ($match[1]==$match2[1]) {
								$suivant_a_verifier = true ; 
							}
						}
						if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match2)) {
							if (($suivant_a_verifier) && (trim($match2[1])!="")) {
								$count ++ ; 
							}
							$suivant_a_verifier = false ; 
						}
						
					}
				}
			}	
			
			$translators = "" ; 
			foreach ($content_po as $ligne_po) {
				if (preg_match("/Last-Translator: (.*) \<(.*)\>/", trim($ligne_po), $match)) {
					if ($translators != "")
						$translators .= ", " ; 
					$translators .= trim($match[1]) ; 
					if (trim($match[2])!="") {
						$mail = trim($match[2]) ; 
						if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#", $mail))
							$translators .= " &lt;<a href='mailto:$mail'>".$mail."</a>&gt;" ; 
						elseif (preg_match("#http://[a-z0-9._/-]+\.[a-z]{2,4}+([a-zA-Z0-9._/-=&?!]+)*#i", $mail))
							$translators .= " (<a href='$mail'>his website</a>)" ; 
						else 
							$translators .= " &lt;".htmlentities($mail)."&gt;" ; 
					}
				}
			}

			$return = sprintf(__("%s sentences have been translated (i.e. %s).",'SL_framework'), "<b>$count/$all_count</b>", "<b>".(floor($count/$all_count*1000)/10)."%</b>") ;  
			if ($translators != "")
				$return .= "######$translators" ; 
			return $return ; 
		}

		/** ====================================================================================================================================================
		* Send the translation to the author of the plugin
		* 
		* @access private
		* @return void
		*/
		function send_translation() {
			require('translation.inc.php') ;
			// We sanitize the language
			$lang = preg_replace("/[^a-zA-Z_]/","",$_POST['lang']) ; 
			$domain = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['domain']) ; 
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$native = $code_locales[$lang]['lang-native'] ; 
			
			$plugin_lien = $plugin ; 
			
			if (strpos($domain,"SL_framework")!==false) {
				$plugin = explode("/",str_replace(basename( __FILE__),"",plugin_basename(__FILE__))); 
				$plugin = $plugin[0] ; 
				$path = WP_PLUGIN_DIR."/".$plugin ; 
			}
			
			$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
			if (strpos($domain,"SL_framework")!==false) {
				$to = $info_file['Framework_Email'] ; 
			} else {
				$to = $info_file['Email'] ; 
			}
			if (strpos($domain,"SL_framework")!==false) {
				$subject = "[Framework] New translation (".$lang.")" ; 
				$info = explode("######",translationSL::get_info(file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po"), file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework.pot")),2) ; 
			} else {
				$subject = "[".ucfirst($plugin)."] New translation (".$lang.")" ; 
				$info = explode("######",translationSL::get_info(file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po"), file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain.".pot")),2) ; 
			}
			
			$message = "" ; 
			$message .= "<p>"."Dear sirs,"."</p><p>&nbsp;</p>" ; 
			$message .= "<p>"."Here is attached a new translation ($native)"."</p><p>&nbsp;</p>" ; 
			$message .= "<p>".strip_tags($info[0])."</p>" ; 
			$message .= "<p>"."Translators: ".$info[1]."</p><p>&nbsp;</p>" ; 
			$message .= "<p>"."Best regards,"."</p><p>&nbsp;</p>" ; 
			
			
			$message .= "<p>"."* Accounts *</p>" ; 
			$message .= "<p>"."**************************************** </p>" ; 
			$admin = get_userdata(1);
			$message .= "<p>"."Admin User Name: " . $admin->display_name ."</p>" ;
			$message .= "<p>"."Admin User Login: " . $admin->user_login."</p>" ;
			$message .= "<p>"."Admin User Mail: " . $admin->user_email."</p>" ;
			$current_user = wp_get_current_user();
			$message .= "<p>"."Logged User Name: " . $current_user->display_name ."</p>" ;
			$message .= "<p>"."Logged User Login: " . $current_user->user_login."</p>" ;
			$message .= "<p>"."Logged User Mail: " . $current_user->user_email."</p>" ;
			
			
			$headers= "MIME-Version: 1.0\n" .
					"Content-Type: text/html; charset=\"" .
					get_option('blog_charset') . "\"\n";
					
			if (strpos($domain,"SL_framework")!==false) {
				$attachments = array(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po",WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".mo");
			} else {
				$attachments = array(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po",WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".mo");
			}
			// send the email
			if (wp_mail( $to, $subject, $message, $headers, $attachments )) {
				echo "<div class='updated  fade'>" ; 
				echo "<p>".sprintf(__("The translation %s have been sent", 'SL_framework'), "$lang ($native)")."</p>" ; 
				echo "</div>" ; 
			} else {
				echo "<div class='error  fade'>" ; 
				echo "<p>".__("An error occured sending the email.", 'SL_framework')."</p><p>".__("Make sure that your wordpress is able to send email.", 'SL_framework')."</p>" ; 
				echo "</div>" ; 			
			}

			//Die in order to avoid the 0 character to be printed at the end
			die() ;
		}

		/** ====================================================================================================================================================
		* Callback function for create a new translation
		* 
		* @access private
		* @return void
		*/

		function translate_create () {
			require('translation.inc.php') ;

			$domain = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['domain']) ; 
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$lang = preg_replace("/[^a-zA-Z_]/","",$_POST['lang']) ; 
			$name = preg_replace("/[^a-zA-Z0-9_.-]/","",$_POST['name']) ; 
			$email = preg_replace("/[^:\/a-z0-9@A-Z_.-=&?!]/","",$_POST['email']) ; 
			
			$plugin_lien = $plugin ; 
			
			if (strpos($domain,"SL_framework")!==false) {
				$plugin = explode("/",str_replace(basename( __FILE__),"",plugin_basename(__FILE__))); 
				$plugin = $plugin[0] ; 
				$path = WP_PLUGIN_DIR."/".$plugin ; 
			}
			
			$trad = $_POST['idLink']; 
			
			$options = get_option('SL_framework_options');
			$options['nameTranslator'] = $name ; 
			$options['emailTranslator'] = $email ; 
			update_option('SL_framework_options', $options);
			
			// We generate a new PO file
			$old_po = array() ; 
			$delete = false ; 
			if (strpos($domain,"SL_framework")!==false) {
				if (is_file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po")) {
					$old_po = file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po") ; 
					unlink(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po") ; 
					unlink(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".mo") ; 
					$delete = true ; 
				}
			} else {
				if (is_file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po")) {
					$old_po = file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po") ; 
					unlink(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po") ; 
					unlink(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".mo") ; 
					$delete = true ; 
				}
			}
			
			if (strpos($domain,"SL_framework")!==false) {
				$handle = @fopen(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".po", "wb");
			} else {
				$handle = @fopen(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".po", "wb");
			}
			$content = "" ; 
			$content .= "msgid \"\"\n" ; 
			$content .= "msgstr \"\"\n" ; 
			$content .= "\"Generated: SL Framework (http://www.sedlex.fr)\\n\"\n";
			$content .= "\"Project-Id-Version: \\n\"\n";
			$content .= "\"Report-Msgid-Bugs-To: \\n\"\n";
			$content .= "\"POT-Creation-Date: \\n\"\n";
			$content .= "\"PO-Revision-Date: ".date("c")."\\n\"\n";
			if (trim($name)!="") {
				$found = false ; 
				foreach ($old_po as $ligne_po) {
					if (preg_match("/Last-Translator: (.*) \<(.*)\>/", trim($ligne_po), $match)) {
						if ($match[1]==$name){
							$found = true ; 
						}
					}
				}	
				if (!$found)
					$content .= "\"Last-Translator: ".$name." <".$email.">\\n\"\n";
			}
			foreach ($old_po as $ligne_po) {
				if (preg_match("/Last-Translator: (.*) \<(.*)\>/", trim($ligne_po), $match)) {
					$content .= "\"Last-Translator: ".$match[1]." <".$match[2].">\\n\"\n";
				}
			}			
			$content .= "\"Language-Team: \\n\"\n";
			$content .= "\"MIME-Version: 1.0\\n\"\n";
			$content .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
			$content .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
			$plurals = "" ; 
			foreach($countries as $code => $array) {
				if (array_search($lang, $array)!==false) {
					$plurals = $csp_l10n_plurals[$code] ; 
				}
			}
			$content .= "\"Plural-Forms: ".$plurals."\\n\"\n";
			$content .= "\"X-Poedit-Language: ".$code_locales[$lang]['lang']."\\n\"\n";
			$content .= "\"X-Poedit-Country: ".$code_locales[$lang]['country']."\\n\"\n";
			$content .= "\"X-Poedit-SourceCharset: utf-8\\n\"\n";
			$content .= "\"X-Poedit-KeywordsList: __;\\n\"\n";
			$content .= "\"X-Poedit-Basepath: \\n\"\n";
			$content .= "\"X-Poedit-Bookmarks: \\n\"\n";
			$content .= "\"X-Poedit-SearchPath-0: .\\n\"\n";
			$content .= "\"X-Textdomain-Support: yes\\n\"\n\n" ; 
			fwrite($handle, $content);
			if (strpos($domain,"SL_framework")!==false) {
				$content_pot = file(WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework.pot") ;
			} else {
				$content_pot = file(WP_PLUGIN_DIR."/".$plugin."/lang/".$domain .".pot") ;
			}
			$i=0 ; 
			
			$hash = array() ; 
			
			foreach ($content_pot as $ligne) {
				if (preg_match("/^#(.*)$/", trim($ligne), $match)) {
					$content .= $match[0]."\n" ; 
				}
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne), $match)) {
					fwrite($handle,'msgid "'.$match[1].'"'."\n" ); 
					
					$trad[$i] = stripslashes($trad[$i]) ; 
					$to_store = htmlspecialchars(htmlspecialchars_decode($trad[$i], ENT_QUOTES), ENT_QUOTES) ; 
					$to_store = str_replace("&gt;", ">", $to_store) ; 
					$to_store = str_replace("&lt;", "<", $to_store) ; 
					fwrite($handle,'msgstr "'.$to_store.'"'."\n\n") ;  
					if ($trad[$i]!="") {
						$hash[] = array('msgid' => $match[1], 'msgstr' => htmlspecialchars(htmlspecialchars_decode($trad[$i], ENT_QUOTES), ENT_QUOTES) ) ; 
					}
					$i++ ; 
				}
			}
			fclose($handle);	
			
			translationSL::summary_translations() ; 
			
			// We convert into a new MO file
			if (strpos($domain,"SL_framework")!==false) {
				translationSL::phpmo_write_mo_file($hash,WP_PLUGIN_DIR."/".$plugin."/core/lang/SL_framework-".$lang.".mo") ; 
			} else {
				translationSL::phpmo_write_mo_file($hash,WP_PLUGIN_DIR."/".$plugin."/lang/".$domain ."-".$lang.".mo") ; 
			}
			echo "<div class='updated  fade'>" ; 
			if ($delete==true) {
				if (strpos($domain,"SL_framework")!==false) {
					echo "<p>".sprintf(__("%s file has been updated for %s", 'SL_framework'), "<code>SL_framework-".$lang.".po</code>", $code_locales[$lang]['lang-native'])."</p>" ; 
					echo "<p>".sprintf(__("%s file has been updated from this file", 'SL_framework'), "<code>SL_framework-".$lang.".mo</code>")."</p>" ; 
				} else {
					echo "<p>".sprintf(__("%s file has been updated for %s", 'SL_framework'), "<code>".$domain ."-".$lang.".po</code>", $code_locales[$lang]['lang-native'])."</p>" ; 
					echo "<p>".sprintf(__("%s file has been updated from this file", 'SL_framework'), "<code>".$domain ."-".$lang.".mo</code>")."</p>" ; 					
				}
			} else {
				if (strpos($domain,"SL_framework")!==false) {
					echo "<p>".sprintf(__("%s file has been created with a new translation for %s", 'SL_framework'), "<code>SL_framework-".$lang.".po</code>", $code_locales[$lang]['lang-native'])."</p>" ; 
					echo "<p>".sprintf(__("%s file has been created from this file", 'SL_framework'), "<code>SL_framework-".$lang.".mo</code>")."</p>" ; 
				} else {
					echo "<p>".sprintf(__("%s file has been created with a new translation for %s", 'SL_framework'), "<code>".$domain ."-".$lang.".po</code>", $code_locales[$lang]['lang-native'])."</p>" ; 
					echo "<p>".sprintf(__("%s file has been created from this file", 'SL_framework'), "<code>".$domain ."-".$lang.".mo</code>")."</p>" ; 								
				}
			}
			// We propose to send the translation
			
			$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
			$isEmailAuthor = false ; 
			if (strpos($domain,"SL_framework")!==false) {
				if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Framework_Email'])) {
					$isEmailAuthor = true ; 
				}				
			} else {
				if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Email'])) {
					$isEmailAuthor = true ; 
				}
			}
			if ($isEmailAuthor) {
				if (strpos($domain,"SL_framework")!==false) {
					$url_to_send  ="<a href='#' onclick='send_trans(\"".$plugin."\",\"SL_framework\", \"".$lang."\")'>" ; 
					$url_to_send2  ="</a>" ; 
					echo "<p><img src='".WP_PLUGIN_URL."/".$plugin."/core/img/info.png'/>".sprintf(__("If you do not want to loose your translations on the next upgrading of this plugin, it is recommended to send the translation files to the author by clicking %s here %s !", 'SL_framework'), $url_to_send, $url_to_send2)."</p>";
				} else {
					$url_to_send  ="<a href='#' onclick='send_trans(\"".$plugin."\",\"".$domain."\", \"".$lang."\")'>" ; 
					$url_to_send2  ="</a>" ; 
					echo "<p><img src='".WP_PLUGIN_URL."/".$plugin."/core/img/info.png'/>".sprintf(__("If you do not want to loose your translations on the next upgrading of this plugin, it is recommended to send the translation files to the author by clicking %s here %s !", 'SL_framework'), $url_to_send, $url_to_send2)."</p>";
				}
			} else {
				echo "<p><img src='".WP_PLUGIN_URL."/".$plugin."/core/img/warning.png'/>".__("If you do not want to loose your translations on the next upgrading of this plugin, please save them on your hard disk before upgrading and then restore them after the upgrade !", 'SL_framework')."</p>";
			}
			
			echo "</div>" ; 
			//Die in order to avoid the 0 character to be printed at the end
			die() ;
		}

		/** ====================================================================================================================================================
		* Write a GNU gettext style machine object
		* 	byte
		*		+-----------------------------------------------+
		*	0  	| magic number = 0x950412de              	|
		*		|                                          		|
		*	4  	| file format revision = 0                	|
		*		|                                         		|
		*	8	| number of strings                        	|  == N
		*		|                                          		|
		*	12  	| offset of table with original strings    	|  == O
		*		|                                          		|
		*	16  	| offset of table with translation strings 	|  == T
		*		|                                          		|
		*	20  	| size of hashing table                   	|  == S
		*		|                                          		|
		*	24  	| offset of hashing table                  	|  == H
		*		|                                          		|
		*		.                                          		.
		*		.    (possibly more entries later)         	.
		*		.                                         		.
		*		|                                          		|
		*	O  	| length & offset 0th string 		 ----------------.
		*	O + 8  | length & offset 1st string  		------------------.
		*		...                                    	...   				| |
		*   O + ((N-1)*8)| length & offset (N-1)th string          	|  		| |
		*		|                                          		|  		| |
		*	T  	| length & offset 0th translation 			 ---------------.
		*	T + 8  	| length & offset 1st translation 			 -----------------.
		*		...                                    			...   		| |      | |
		*   T + ((N-1)*8)| length & offset (N-1)th translation     	|  		| |      | |
		*		|                                         		| 		| |      | |
		*	H  	| start hash table                         	|  		| |      | |
		*		...                                   			...   		| |      | |
		*     H + S * 4 | end hash table                           		|  		| |      | |
		*		|                                         		|  		| |      | |
		*		| NUL terminated 0th string 	         <----------------' |      | |
		*		|                                          		|   		  |       | |
		*		| NUL terminated 1st string  	        <------------------'       | |
		*		|                                         		|      			| |
		*		...                                   			 ...       		| |
		*		|                                         		|     			| |
		*		| NUL terminated 0th translation 			 <--------------' |
		*		|                                          		|        			  |
		*		| NUL terminated 1st translation  			<-----------------'
		*		|                                          		|
		*		 ...                                    			...
		*		|                                          		|
		*		+-----------------------------------------------+
		* @access private
		* @param array $hash the hash of the po file
		* @param string $out path to the mo file
		* @return void
		*/
		function phpmo_write_mo_file($hash, $out) {
			// sort by msgid
			ksort($hash, SORT_STRING);
			// our mo file data
			$mo = '';
			// header data
			$offsets = array ();
			$ids = '';
			$strings = '';

			foreach ($hash as $entry) {
				$offsets[] = array (strlen($ids), strlen($entry['msgid']), strlen($strings), strlen($entry['msgstr']));
				$ids .= $entry['msgid']. "\x00";
				$strings .= $entry['msgstr'] . "\x00";
			}

			// keys start after the header (7 words) + index tables ($#hash * 4 words)
			$key_start = 7 * 4 + sizeof($hash) * 4 * 4;
			// values start right after the keys
			$value_start = $key_start +strlen($ids);
			// first all key offsets, then all value offsets
			$key_offsets = array ();
			$value_offsets = array ();
			// calculate
			foreach ($offsets as $v) {
				list ($o1, $l1, $o2, $l2) = $v;
				$key_offsets[] = $l1;
				$key_offsets[] = $o1 + $key_start;
				$value_offsets[] = $l2;
				$value_offsets[] = $o2 + $value_start;
			}
			$offsets = array_merge($key_offsets, $value_offsets);

			// write header
			$mo .= pack('Iiiiiii', 0x950412de, 		// magic number
			0, 							// version
			count($hash), 					// number of entries in the catalog
			7 * 4, 						// key index offset
			7 * 4 + count($hash) * 8, 			// value index offset,
			0, 							// hashtable size (unused, thus 0)
			$key_start 						// hashtable offset
			);
			
			// offsets
			foreach ($offsets as $offset)
				$mo .= pack('i', $offset);
			// ids
			$mo .= $ids;
			// strings
			$mo .= $strings;

			file_put_contents($out, $mo);
		}


		
	}
}

?>