<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class allows to compare two folder to find differences
*/
if (!class_exists("foldDiff")) {
	class foldDiff {
		
		var $folder = array() ; 
		var $rep1 = "" ; 
		var $rep2 = "" ; 
		
		/** ====================================================================================================================================================
		* Constructor
		* 
		* @access private
		* @return void
		*/
		function foldDiff() {
			$this->folder = array() ; 
		}
		
		/** ====================================================================================================================================================
		* Compute differences between the two folders
		* 
		* @param string $path1 the path of the first folder
		* @param string $path2 the path of the second folder
		* @param integer $niveau the level of recursion
		* @param boolean $racine true if it the the first level
		* @return void
		*/
		
		function diff( $path1, $path2 , $racine=true){
			
			$path1 = str_replace("//","/",$path1) ;
			$path2 = str_replace("//","/",$path2) ;

			if ($racine) {
				$this->rep1 = $path1."/" ; 
				$this->rep2 = $path2."/" ; 
			}
			
			
			// On liste les fichiers qui existe dans le $path1 mais pas dans le $path2
			if (is_dir($path1)) {
				$d1 = @opendir( $path1 );		
				while(($file = readdir( $d1 )) !== false){ 
					if (!preg_match("@^\..*@",$file)) {
						if ((!is_file($path2."/".$file))&&(!is_dir($path2."/".$file))) {
							if (is_file($path1."/".$file)) {
								if (!$this->isBinary($path1."/".$file)) {
									$this->folder[] = array($file,2,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								} else {
									$this->folder[] = array($file,2,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								}
							} else {
								$this->folder[] = array($file."/",2,"directory", str_replace($this->rep2,"",$path2."/".$file."/")) ; 
								//Recursive
								$this->diff($path1."/".$file, $path2."/".$file, false) ;  
							}
						}
					}
				}
				closedir( $d1 ); 
			}
			
			
			// On liste les fichiers qui existe dans le $path2 mais pas dans le $path1
			if (is_dir($path2)) {
				$d2 = @opendir( $path2 );
				while(($file = readdir( $d2 )) !== false){ 
					if (!preg_match("@^\..*@",$file)) {
						if ((!is_file($path1."/".$file))&&(!is_dir($path1."/".$file))) {
							if (is_file($path2."/".$file)) {
								if (!$this->isBinary($path2."/".$file)) {
									 $this->folder[] = array($file,1,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								} else {
									 $this->folder[] = array($file,1,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								}
							} else {
								 $this->folder[] = array($file."/",1,"directory", str_replace($this->rep2,"",$path2."/".$file."/")) ; 
								//Recursive
								$this->diff($path1."/".$file, $path2."/".$file, false) ;  
							}
						} else {
							if (is_file($path2."/".$file)) {
								// on regarde si les fichiers sont identiques
								if (md5_file($path2."/".$file)==md5_file($path1."/".$file)) {
									if (!$this->isBinary($path2."/".$file)) {
										 $this->folder[] = array($file,0,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									} else {
										 $this->folder[] = array($file,0,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									}
								} else {
									if (!$this->isBinary($path2."/".$file)) {
										 $this->folder[] = array($file,3,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									} else {
										 $this->folder[] = array($file,3,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									}
								}
							} else {
								 $this->folder[] = array($file."/",0,"directory", str_replace($this->rep2,"",$path2."/".$file."/")) ; 
								//Reccursive
								$this->diff($path1."/".$file, $path2."/".$file, false) ;  
							}
						}
					}
				}
				closedir( $d2 ); 
			}
			
			return $this->folder ; 
			
		}
		
		/** ====================================================================================================================================================
		* Display the difference
		* 
		* @param boolean $withTick display ticks 
		* @param bollean $closeNotModifiedFolders close folders if their contents have not been modified
		* @return void
		*/
		
		function render($closeNotModifiedFolders=true, $withTick=false) {
			
			// On affiche les repertoires
			$rep_current = Utils::multicolumn_sort($this->folder, 3) ; 
			$prev_fold = "" ; 
			$reduire = "<script>\r\n" ; 
			$hasmodif = array() ; 
			$foldlist = array() ; 
			$niveau = 1 ; 
			
			if ($withTick) {
				echo "<p><input class='button-secondary action' onClick='allTick(true)' value='".__('Select all', 'SL_framework')."'>"  ; 
				echo "&nbsp; <input class='button-secondary action' onClick='allTick(false)' value='".__('Un-select all', 'SL_framework')."'></p>"  ; 
				echo "<script>\r\n";
				echo "function allTick(val) {\r\n" ;
				echo "     jQuery('.toDelete').attr('checked', val);\r\n" ; 
				echo "     jQuery('.toDeleteFolder').attr('checked', val);\r\n" ; 
				echo "     jQuery('.toPut').attr('checked', val);\r\n" ; 
				echo "     jQuery('.toPutFolder').attr('checked', val);\r\n" ; 
				echo "     jQuery('.toModify').attr('checked', val);\r\n" ; 
				echo "		return false ; " ; 
				echo "}\r\n" ; 
				echo "</script>\r\n" ; 
			}
				
			foreach ($rep_current as $rc) {
				$color = "" ; 
				$binary = "" ;
				$loupe = "" ; 
				$plus="<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/vide-8.png' />\n" ; 
				$icone = "<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/default.png'/>\n" ; 
				
				$tick = "<span style='width:30px;display:block;float:left;'>&nbsp;</span>" ; 
				
				if (($rc[1]==1)) {
					$color = "color:red;text-decoration:line-through;" ; 
					$tick = "<span style='width:30px;display:block;float:left;'><input class='toDelete' type='checkbox' name='toDelete' value='".$rc[3]."' checked /></span>" ; 
					if ($rc[2]=="directory") 
						$tick = "<span style='width:30px;display:block;float:left;'><input class='toDeleteFolder' type='checkbox' name='toDeleteFolder' value='".$rc[3]."' checked /></span>" ; 
				}
				if (($rc[1]==2)) {
					$color = "color:green;" ; 
					$tick = "<span style='width:30px;display:block;float:left;'><input class='toPut' type='checkbox' name='toPut' value='".$rc[3]."' checked ></span>" ; 
					if ($rc[2]=="directory") 
						$tick = "<span style='width:30px;display:block;float:left;'><input class='toPutFolder' type='checkbox' name='toPutFolder' value='".$rc[3]."' checked /></span>" ; 
				}
				if (($rc[1]==3)) {
					$color = "color:blue;" ; 
					$tick = "<span style='width:30px;display:block;float:left;'><input class='toModify' type='checkbox' name='toModify' value='".$rc[3]."' checked ></span>" ; 
				}
				
				if ($rc[2]=="binary_file") {
					$binary = "*" ; 
					$icone = "<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/binary.png'/>\n" ; 
				}
				
				if (preg_match("/\.php$/i", $rc[0]))
					$icone = "<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/php.png'/>\n" ; 
				if (preg_match("/\.(gif|png|jpg|jpeg)$/i", $rc[0])) 
					$icone = "<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/img.png'/>\n" ; 

				if ($rc[2]=="directory") {
					$plus =  "<a href='#' onclick='folderToggle(\"".md5($rc[3])."\") ; return false ; '>\n" ; 
					$plus .= "<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/minus-8.png' id='minus_".md5($rc[3])."' />\n" ; 
					$plus .= "<img style='display:none;border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/plus-8.png' id='plus_".md5($rc[3])."' />\n" ; 
					$plus .=  "</a>\n" ; 
					$icone = "<img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/folder.png'/>\n" ;  ; 
				}
				
				if ((($rc[1]==3)||($rc[1]==2)||($rc[1]==1))&&($rc[2]=="text_file")) {
					$loupe =  "<a href='#' onclick='diffToggle(\"".md5($rc[3])."\") ; return false ; '>\n" ; 
					$loupe .= " <img style='border:0px' src='".WP_PLUGIN_URL.'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."img/loupe.png'/>\n"  ; 
					$loupe .=  "</a>\n" ; 
				}
				
				$old_niv = $niveau  ; 
				$niveau = substr_count(str_replace("/#", "", $rc[3]."#"), "/") ; 
				if ($old_niv>$niveau) {
					for ($i=0 ; $i<$old_niv-$niveau ; $i++) {
						echo "</div>\n" ; 
						if (!$hasmodif[$niveau+$i]) { 
							$reduire .= "folderToggle(\"".md5($listfolder[$niveau+$i])."\") ; \r\n"; 
						}
						$hasmodif[$niveau+$i] = false ; 
					}
				}
				if ($old_niv<$niveau) {
					echo "<div id='folder_".md5($prevfolder)."'>\n" ; 
					$hasmodif[$niveau] = false ;
					$listfolder[$old_niv] = $prevfolder ;
				}
				$prevfolder = $rc[3] ; 
				
				if (!$withTick)
					$tick = "" ; 
				
				echo "<p style='padding:0px;margin:0px;'>".$tick."<span style='padding:0px;margin:0px;padding-left:".(20*$niveau)."px;'>".$plus.$icone."<span style='".$color."'>".$rc[0].$binary."</span>".$loupe."</span></p>\n" ; 
				
				if ( ($rc[1]==3) || ($rc[1]==2) || ($rc[1]==1) ) {
					for ($i=0 ; $i<=$niveau ; $i++) {
						$hasmodif[$i] = true ;
					}
				}
				
				if ((($rc[1]==3)||($rc[1]==2)||($rc[1]==1))&&($rc[2]=="text_file")) {
					echo "<div id='diff_".md5($rc[3])."' style='display:none;padding:0px;margin:0px;padding-left:".(20*$niveau+30)."px;'>\n" ; 
					$text1 = @file_get_contents($this->rep1.$rc[3]) ; 
					$text2 = @file_get_contents($this->rep2.$rc[3]) ; 
					
					$textdiff = new textDiff() ; 
					$textdiff->diff($text2, $text1) ; 
					echo $textdiff->show_only_difference() ; 
					
					echo "</div>\n" ; 
				}
			}				
			
			$reduire .= "</script>\r\n" ; 
			if ($withTick) {
				echo "<p><input class='button-secondary action' onClick='allTick(true)' value='".__('Select all', 'SL_framework')."'>"  ; 
				echo "&nbsp; <input class='button-secondary action' onClick='allTick(false)' value='".__('Un-select all', 'SL_framework')."'></p>"  ; 
			}
			if ($closeNotModifiedFolders) {
				echo $reduire ; 
			}
		}

		/** ====================================================================================================================================================
		* Test if a file is binary
		* 
		* @param string $file path to the file to test
		* @access private
		* @return void
		*/
		
		function isBinary($file) {
			if (file_exists($file)) {
				if (!is_file($file)) return 0;
				if (preg_match("/\.(gif|png|jpg|jpeg)$/i", trim($file))) return 1 ; 

				$fh = fopen($file, "r");
				$blk = fread($fh, 512);
				fclose($fh);
				clearstatcache();

				return (0 or substr_count($blk, "^ -~", "^\r\n")/512 > 0.3	or substr_count($blk, "\x00") > 0);
			}
			return 0;
		} 	

	}
}

?>