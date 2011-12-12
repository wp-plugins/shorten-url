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
		
		var $login ; 
		var $mdp ;
		var $host ;
		var $port ;
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $host the host of the svn repository (for instance svn.wp-plugins.org)
		* @param interger $port the port of the webdav repository
		* @param string $login your login
		* @param string $mdp your password
		* @return svnAdmin the box object
		*/
		
		function svnAdmin($host, $port=80, $login='', $mdp='') {
			$this->login = $login ; 
			$this->mdp = $mdp ; 
			$this->port = $port ; 
			$this->host = $host ; 
			$this->user_agent = "SVN PHP client v1.0 (SL Framework for Wordpress)" ; 
		}
		
		/** ====================================================================================================================================================
		* List the files and folder on the repository
		* 
		* @param string $base the relative path of the folder to be looked into (from the repository)
		* @param boolean $rec true if the listing should be reccursive (useful if you want the list of an entire repository with sub-folders)
		* @param boolean $credentials true if the repository requires credentials to list files
		* @return array 'isOk' => whether the request is successful, 'list' => the list of files and folders, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		*/	
		
		function listFilesInRepository($base, $rec=true, $credentials=false) {

			$result = $this->sendSVNRequest($this->host, $base, "PROPFIND", "<?xml version=\"1.0\"?><D:propfind xmlns:D=\"DAV:\"><D:allprop/></D:propfind>", array("Depth: 1") ) ; 		
			
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$doc = $this->xmlContentParse($result['content']) ;
				$ls=array();
				
				//baseline-relative-path
				//resourcetype
				$i=-1;
				foreach($doc->getElementsByTagNameNS ( 'DAV:' , 'response' ) as $response) {
					$i++;
					// We skip the first entry because the first entry is the folder itself
					if ($i===0) {
						continue;
					}
					
					// We get the name of the file/folder
					$res=array();
					$res['href'] = urldecode($response->getElementsByTagNameNS ( 'DAV:' , 'href' )->item(0)->textContent);
					
					// We check if the entry is a folder
					if($response->getElementsByTagNameNS ( 'DAV:' , 'collection')->length) 
						$res['folder']=true;
					
					// We add to the array
					$ls[] = $res ; 
					
					// reccursive
					if ($rec) {
						if ($res['folder']) {
							$rec_files = $this->listFilesInRepository($res['href'], $rec, $credentials)  ; 
							$ls = array_merge($ls, $rec_files['list']) ; 
						}
					}
				}
				return array("isOK" => true, "list" => $ls, "raw_result" => $result);
			} else {
				return array("isOK" => false, "raw_result" => $result) ;						
			}
		}

		/** ====================================================================================================================================================
		* Get the activity collection folder (required to put/delete file in the repo)
		* 
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'activity_folder' => the activity folder, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		*/			
		
		function getActivityFolder($base, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $base, "OPTIONS", "<?xml version=\"1.0\" encoding=\"utf-8\"?><D:options xmlns:D=\"DAV:\"><D:activity-collection-set/></D:options>", array(), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$xml = $this->xmlContentParse($result['content']) ;
				$activity = $xml->getElementsByTagNameNS ( 'DAV:' , 'href' )->item(0)->textContent ;
				return array("isOK" => true, "activity_folder" => $activity, "raw_result" => $result) ;
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}
		
		/** ====================================================================================================================================================
		* Get VCC (Version Controlled Resource)
		* 
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'vcc' => the version controlled folder, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		*/			
		
		function getVCC($base, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $base, "PROPFIND", "<?xml version=\"1.0\" encoding=\"utf-8\"?><propfind xmlns=\"DAV:\"><prop><version-controlled-configuration xmlns=\"DAV:\"/></prop></propfind>", array("Depth: 0"), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$xml = $this->xmlContentParse($result['content']) ;
				$vcc = $xml->getElementsByTagNameNS ( 'DAV:' , 'version-controlled-configuration' )->item(0)->textContent ;
				return array("isOK" => true, "vcc" => $vcc, "raw_result" => $result) ;
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}
		
		/** ====================================================================================================================================================
		* Get Repository Revision
		* 
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'revision' => the revision number, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		*/			
		
		function getRevision($base, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $base, "PROPFIND", "<?xml version=\"1.0\" encoding=\"utf-8\"?><propfind xmlns=\"DAV:\"><prop><version-name xmlns=\"DAV:\"/></prop></propfind>", array("Depth: 0"), $credentials) ; 		
			
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$xml = $this->xmlContentParse($result['content']) ;
				$rev = $xml->getElementsByTagNameNS ( 'DAV:' , 'version-name' )->item(0)->textContent ;
				return array("isOK" => true, "revision" => $rev, "raw_result" => $result) ;
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}
		
		/** ====================================================================================================================================================
		* Create an activity
		* 
		* @param string $activity_n_uuid it is the activity folder concatenated with an random UUID (composed with hexadecimal digit xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getActivityFolder
		*/			
		
		function createActivity($activity_n_uuid, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $activity_n_uuid, "MKACTIVITY", "", array(), $credentials) ; 	
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				return array("isOK" => true, "raw_result" => $result) ;
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}
		
		/** ====================================================================================================================================================
		* Get the commit comment URL
		* 
		* @param string $vcc the VCC of the repository
		* @param string $activity_n_uuid it is the activity folder concatenated with the random UUID (composed with hexadecimal digit xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'url' the comment url, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getVCC
		* @see svnAdmin::getActivityFolder
		*/			
		
		function getCommitCommentURL($vcc, $activity_n_uuid, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $vcc, "CHECKOUT", "<?xml version=\"1.0\" encoding=\"utf-8\"?><D:checkout xmlns:D=\"DAV:\"><D:activity-set><D:href>".$activity_n_uuid."</D:href></D:activity-set><D:apply-to-version/></D:checkout>", array(), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$url = str_replace("http://".$this->host, "", $result["header"]["Location"]) ; 
				return array("isOK" => true, "url" => $url, "raw_result" => $result) ;  
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}

		/** ====================================================================================================================================================
		* Set a commit comment
		* 
		* @param string $comment the comment to be added
		* @param string $comment_url the comment url
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful,'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getCommitCommentURL
		*/			 
		
		function setCommitComment($comment, $comment_url, $credentials=false) {
		
			$replacements = array('&lt;', '&gt;');
			$entities = array('<', '>');
			$comment = str_replace($entities, $replacements, $comment);
			
			$result = $this->sendSVNRequest($this->host, $comment_url, "PROPPATCH", "<?xml version=\"1.0\" encoding=\"utf-8\"?><D:propertyupdate xmlns:D=\"DAV:\" xmlns:V=\"http://subversion.tigris.org/xmlns/dav/\" xmlns:C=\"http://subversion.tigris.org/xmlns/custom/\" xmlns:S=\"http://subversion.tigris.org/xmlns/svn/\"><D:set><D:prop><S:log >".$comment."</S:log></D:prop></D:set></D:propertyupdate>", array(), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				return array("isOK" => true, "raw_result" => $result) ; 
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}		
		
		/** ====================================================================================================================================================
		* Get the version folder
		* 
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'version_folder' => the version folder,'raw_result' the request and the respond in an array (useful for debugging purpose)
		*/			
		
		function getVersionFolder($base, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $base, "PROPFIND", "<?xml version=\"1.0\" encoding=\"utf-8\"?><propfind xmlns=\"DAV:\"><prop><checked-in xmlns=\"DAV:\"/></prop></propfind>", array("Depth: 0"), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$xml = $this->xmlContentParse($result['content']) ;
				$vf = $xml->getElementsByTagNameNS ( 'DAV:' , 'checked-in' )->item(0)->firstChild->textContent ;
				return array("version_folder" => $vf, "isOK" => true, "raw_result" => $result) ; 
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}	
		
		/** ====================================================================================================================================================
		* Get the folder to put files or delete files
		* 
		* @param string $version the version folder
		* @param string $activity_n_uuid it is the activity folder concatenated with the random UUID (composed with hexadecimal digit xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'put_folder' the put url, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getVersionFolder
		* @see svnAdmin::getActivityFolder
		*/			
		
		function getPutFolder($version, $activity_n_uuid, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $version, "CHECKOUT", "<?xml version=\"1.0\" encoding=\"utf-8\"?><D:checkout xmlns:D=\"DAV:\"><D:activity-set><D:href>".$activity_n_uuid."</D:href></D:activity-set></D:checkout>", array(), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				$url = str_replace("http://".$this->host, "", $result["header"]["Location"]) ; 
				return array("put_folder" => $url, "isOK" => true, "raw_result" => $result) ; 
			} else {
				return array("isOK" => false, "raw_result" => $result) ;			
			}
		}
		
		/** ====================================================================================================================================================
		* Put a file in the repo
		* 
		* @param string $put_folder_n_file the put folder concatenated with the file name
		* @param string $file the complete url of the file on your disk
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'svn_header' the svn header sent (useful for debugging), 'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getPutFolder
		*/			
		
		function putFile($put_folder_n_file, $file, $credentials=false) {
			$file = file_get_contents($file) ; 
			
			$top_header = "SVN".chr(0) ; // Version 0 of the SVN diff protocol (see https://svn.apache.org/repos/asf/subversion/trunk/notes/svndiff)
			
			$maxsize = 102400 ; // Si un fichier est plus grand, alors ca bug !! il faut alors le découper en plusieurs "window"
			$content = "" ; 
			$numwin = 0 ; 
			$info = array() ; 
			$info[] = array("HEADER ".$this->asc2bin("SVN".chr(0))) ; 
			while (strlen($file)!=0) {
				$numwin ++ ; 
				// Taille de la window
				$lenfile = min($maxsize, strlen($file) ) ; 
				
				// Instructions
				$instructions = "" ; 
				$instructions .= chr(bindec("10"."000000")) ; 		// Copy from the new file
				$instructions .= $this->getChr($lenfile) ; 			// the length to be copied (i.e. here size of the new file)
				
				// header
				$header = $this->getChr(0) ; 				// Source offset 0
				$header .= $this->getChr(0) ; 				// Source length 0
				$header .= $this->getChr($lenfile) ; 			// Target length (i.e. the size of the file)
				$header .= $this->getChr(strlen($instructions)) ; 	// Number of instructions bytes
				$header .= $this->getChr($lenfile) ; 			// New data length
				
				$info[] = array("SOURCE OFFSET ".$this->asc2bin($this->getChr(0))." - SOURCE LENGTH ".$this->asc2bin($this->getChr(0) )." - TARGET LENGTH : ".$this->asc2bin($this->getChr($lenfile)) ." - INSTRUCTION LENGTH : ".$this->asc2bin($this->getChr(strlen($instructions)))." - NEW DATA LENGTH : ".$this->asc2bin($this->getChr($lenfile))." - INSTRUCTIONS : ".$this->asc2bin($instructions)) ; 
				
				$content .= $header.$instructions.substr($file, 0, $lenfile) ;
				//maj du file
				$file = substr($file, $lenfile) ; 
			}
			//http://websvn.cyberspectrum.de/wsvn/tl_svn/trunk/system/modules/svnupdate/SubVersionMessageDiff.php
						
			$result = $this->sendSVNRequest($this->host, $put_folder_n_file, "PUT", $top_header.$content, array("Content-Type: application/vnd.svn-svndiff"), $credentials) ; 		
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 
				return array("isOK" => true, "raw_result" => $result, "svn_header" => $info ) ;	
			} else {
				return array("isOK" => false, "raw_result" => $result, "svn_header" => $info ) ;	
			}
		} 
		
		/** ====================================================================================================================================================
		* Put folder in the repository
		* 
		* @param string $put_folder_n_folder the put folder concatenated with the folder to add 
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful,'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getPutFolder	
		*/			
		
		function putFolder($put_folder_n_folder, $credentials=true) {
			$result = $this->sendSVNRequest($this->host, $put_folder_n_folder, "MKCOL", "", array(), $credentials) ; 		
			
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 				
				return array("isOK" => true, "raw_result" => $result) ;	
			} else {
				return array("isOK" => false, "raw_result" => $result) ;	
			}		
			
		}
		
		/** ====================================================================================================================================================
		* Put folder in the repository
		* 
		* @param string $put_folder_n_filefolder the put folder concatenated with the folder/file to add 
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful,'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getPutFolder	
		*/			
		
		function deleteFileFolder($put_folder_n_filefolder, $credentials=true) {
			$result = $this->sendSVNRequest($this->host, $put_folder_n_filefolder, "DELETE", "", array(), $credentials) ; 		
			
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 				
				return array("isOK" => true, "raw_result" => $result) ;	
			} else {
				return array("isOK" => false, "raw_result" => $result) ;	
			}		
		}
				
		/** ====================================================================================================================================================
		* Merge the commit ... thus all the change will be taken in account
		* 
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param string $activity_n_uuid it is the activity folder concatenated with the random UUID (composed with hexadecimal digit xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'commit_info' the commit information, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getActivityFolder
		*/			
		
		function merge($base, $activity_n_uuid, $credentials=false) {
			$result = $this->sendSVNRequest($this->host, $base, "MERGE", "<?xml version=\"1.0\" encoding=\"utf-8\"?><D:merge xmlns:D=\"DAV:\"><D:source><D:href>".$activity_n_uuid."</D:href></D:source><D:no-auto-merge/><D:no-checkout/><D:prop><D:checked-in/><D:version-name/><D:resourcetype/><D:creationdate/><D:creator-displayname/></D:prop></D:merge>", array(), $credentials) ; 		
			
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 				
				preg_match("/version-name>([^<]*)<([^>]*)version-name/", $result['content'], $rev) ;
				preg_match("/creator-displayname>([^<]*)<([^>]*)creator-displayname/", $result['content'], $author) ;
				return array("isOK" => true, "commit_info" => "revision n&deg;".$rev[1]." (".$author[1].")", "raw_result" => $result) ;	
			} else {
				return array("isOK" => false, "raw_result" => $result) ;	
			}		
		}
		
				
		/** ====================================================================================================================================================
		* Get a single file of the repository
		* 
		* @param string $base_file the relative path of the file to get (for instance /yourplugin/trunk/file1.txt)
		* @param string $store the local path to store the file retrieved (for instance /home/foo/yourplugin/file1.txt)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'siez' the size in bytes of the retrieved file
		*/			
		
		function getFile($base_file, $store, $credentials=true) {
			
			$replacements = array('%20');
			$entities = array(' ');
			$base_file = str_replace($entities, $replacements, $base_file);

			$content = @file_get_contents("http://".$this->host.$base_file) ; 
			if ($content!==false) {
				@mkdir(dirname($store), 0777, true) ; 
				@file_put_contents($store, $content) ; 
				return array("size" => strlen($content) , "isOK" => true ) ; 
			} else {
				return array("isOK" => false) ;			
			}
		}
		
		/** ====================================================================================================================================================
		* Get all files of the repository
		* 
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param string $vcc the VCC 
		* @param string $rev the revision number 
		* @param string $store the local path to store the file retrieved (for instance /home/foo/yourplugin/)
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'info' the list of retrieved files/folder, 'raw_result' the request and the respond in an array (useful for debugging purpose)
		* @see svnAdmin::getVCC
		* @see svnAdmin::getRevision		
		*/			
		
		function getAllFiles($base, $vcc, $rev, $store, $credentials=true) {
			$result = $this->sendSVNRequest($this->host, $vcc, "REPORT", "<?xml version=\"1.0\" encoding=\"utf-8\"?><S:update-report send-all=\"true\" xmlns:S=\"svn:\" xmlns:D=\"DAV:\"><S:src-path>http://".$this->host.":".$this->port.$base."</S:src-path><S:target-revision>".$rev."</S:target-revision><S:depth>infinity</S:depth><S:entry rev=\"".$rev."\" depth=\"infinity\"  start-empty=\"true\"></S:entry></S:update-report>", array(), $credentials) ; 		
			$is_err = false ; 
			if (substr($result['header']['Return-Code-HTTP'], 0, 1)=="2") { 		
				$xml = $this->xmlContentParse($result['content']) ;
				$info = array() ; 
				
				// On s'occupe des folders
				$dir = $xml->getElementsByTagNameNS ( 'svn:' , 'add-directory' );
				foreach ($dir as $d) {
					$url = $d->getElementsByTagNameNS ( 'DAV:' , 'checked-in' )->item(0)->firstChild->textContent ; 
					
					$tmp = explode($base, $url, 2) ; 
					if (count($tmp)==2) {
						$url = $tmp[1] ; 
					}
					// On crée les folders
					if (@mkdir($store.$url, 0755, true)) {
						$info[] = array("url"=>$url, "ok"=>true, "folder"=>true)  ; 
					} else {
						$info[] = array("url"=>$url, "ok"=>false, "folder"=>true)  ; 
						$is_err = true ; 
						break ; 
					}
				}
				if (!$is_err) {
					// On s'occupe des fichiers
					$file = $xml->getElementsByTagNameNS ( 'svn:' , 'add-file' );
					foreach ($file as $f) {
						$url = $f->getElementsByTagNameNS ( 'DAV:' , 'checked-in' )->item(0)->firstChild->textContent ; 
						
						// on recupere le contenu du fichier
						$content = $f->getElementsByTagNameNS ( 'svn:' , 'txdelta' )->item(0)->textContent ; 
						$content = base64_decode($content) ; 
						$true_content = "" ; 
						// On supprime l'entete SVN binaire (voir https://svn.apache.org/repos/asf/subversion/trunk/notes/svndiff)
						$offset = 4 ; // on passe le PHP\0
						// Window 
						while (strlen($content)!=$offset) {
							$val = $this->popInt($content, $offset) ; 			// Read source view offset
							$val = $this->popInt($content, $val['new_offset']) ; 		// Read source view length
							$val = $this->popInt($content, $val['new_offset']) ; 		// Read target view length
							$val = $this->popInt($content, $val['new_offset']) ; 		// Read instructions length
							$insl = $val['int'] ;
							$val = $this->popInt($content, $val['new_offset']) ; 		// New data length
																	// After we have the instruction ($insl the number of bytes)
							$data_length = $val['int'] ;
							$debut_text = $val['new_offset']+$insl ; 
							if ($data_length==0)
								break ; 
							$true_content .= substr($content, $debut_text, $data_length) ; 
							$offset = $debut_text+$data_length ;
						}
						
						
						
						
						$tmp = explode($base, $url, 2) ; 
						if (count($tmp)==2) {
							$url = $tmp[1] ; 
						}
						
						$replacements = array(' ');
						$entities = array('%20');
						$url = str_replace($entities, $replacements, $url);
						
						// On crée les  fichiers en cache
						if (@file_put_contents($store.$url, $true_content)!==false) {
							$info[] = array("url"=>$url, "ok"=>true, "folder"=>false , "size"=>strlen($content) )  ; 
						} else {
							$info[] = array("url"=>$url, "ok"=>false, "folder"=>false, "size" =>strlen($content) )  ; 
							$is_err = true ; 
							break ; 
						}
					}
				}
				if (!$is_err) {
					return array("isOK" => true, "info" => $info, "raw_result" => $result) ;	
				} else {
					return array("isOK" => false, "info" => $info, "raw_result" => $result) ;	
				}
			} else {
				return array("isOK" => false, "raw_result" => $result) ;	
			}	
			
		}
		
		/** ====================================================================================================================================================
		* Prepare the commit
		*
		* @param string $base the relative path of the folder (for instance /yourplugin/trunk/)
		* @param string $comment the comment for the commit
		* @param boolean $credentials true if the repository requires credentials 
		* @return array 'isOk' => whether the request is successful, 'step' indicated the step which fails, 'putFolder'  the put folder , 'activityFolder' the acivity folder, 'uuid' the random uuid used for this commit,  'raw_result' the request and the respond in an array (useful for debugging purpose)
		*/			
		
		function prepareCommit($base, $comment, $credentials=false) {
			$activity = $this->getActivityFolder($base, $credentials) ; 
			if ($activity['isOK']) {
				$vcc = $this->getVCC($base, $credentials) ; 
				if ($vcc['isOK']) {
					// We generate a new random UUID
					$chars = md5(rand(1, 1000).rand(1, 1000).rand(1, 1000).rand(1, 1000).rand(1, 1000).rand(1, 1000).rand(1, 1000).rand(1, 1000).rand(1, 1000));
					$uuid  = substr($chars,0,8) . '-';
					$uuid .= substr($chars,8,4) . '-';
					$uuid .= substr($chars,12,4) . '-';
					$uuid .= substr($chars,16,4) . '-';
					$uuid .= substr($chars,20,12) ;
					$act = $this->createActivity($activity['activity_folder'].$uuid , $credentials) ; 
					if ($act['isOK']) {					
						$url_comment = $this->getCommitCommentURL($vcc['vcc'], $activity['activity_folder'].$uuid , $credentials) ; 
						if ($url_comment['isOK']) {
							$res = $this->setCommitComment($comment, $url_comment['url'], true) ; 
							if ($res['isOK']) {
								$versionFolder = $this->getVersionFolder($base, $credentials) ; 
								if ($versionFolder['isOK']) {
									
									$urlDepot = $this->getPutFolder($versionFolder['version_folder'], $activity['activity_folder'].$uuid , $credentials) ; 
									if ($urlDepot['isOK']) {
										
										return array("putFolder" => $urlDepot['put_folder'], 'activityFolder' => $activity['activity_folder'] , 'uuid' => $uuid , "isOK" => true ) ; 
									
									} else {
										return array("isOK" => false, 'step' => 'GETTING THE URL TO PUT', 'raw_result' => $urlDepot['raw_result']) ; 
									}
								} else {
									return array("isOK" => false, 'step' => 'GETTING THE VERSION FOLDER', 'raw_result' => $versionFolder['raw_result']) ; 
								}
							} else {
								return array("isOK" => false, 'step' => 'SETTING COMMIT COMMENT', 'raw_result' => $res['raw_result']) ; 
							}
						} else {
							return array("isOK" => false, 'step' => 'GETTING THE COMMENT URL', 'raw_result' => $url_comment['raw_result']) ; 
						}						
					} else {
						return array("isOK" => false, 'step' => 'CREATING ACTIVITY/VCC', 'raw_result' => $act['raw_result']) ; 
					}
				} else {
					return array("isOK" => false, 'step' => 'GETTING VCC', 'raw_result' => $vcc['raw_result']) ; 
				}	
			} else {
				return array("isOK" => false, 'step' => 'GETTING THE ACTIVITY FOLDER', 'raw_result' => $activity['raw_result']) ; 
			}
		}
				
		/** ====================================================================================================================================================
		* Send a SVN request
		* 
		* @param string $host the host (for instance svn.wp-plugins.org)
		* @param string $relative_uri the base url (for instance /yourplugin/trunk/)
		* @param string $type the type of request (e.g. GET, PROPFIND, PROPPATCH, etc.)
		* @param string $content the content of the http request
		* @param array $additional_headers if addiotionnal header are required (for instance array('Content-Type: text/plain'))
		* @param boolean $credentials true if the repository requires credentials 
		* @return array the response and the content sent
		*/			
		
		function sendSVNRequest($host, $relative_uri, $type, $content, $additional_headers=array(), $credentials=false) {
			
			$replacements = array('%20');
			$entities = array(' ');
			$relative_uri = str_replace($entities, $replacements, $relative_uri);

			$header = $type." ".$relative_uri." HTTP/1.1\r\n" ;
			$header .= "Host: ".$host."\r\n" ;
			$header .= "User-Agent: ".$this->user_agent."\r\n" ;
			$noContentType = false ; 
			foreach ($additional_headers as $h) {
				if (strpos($h, "Content-Type") !== false)
					$noContentType = true ;
			}
			if (!$noContentType) 
				$header .= "Content-Type: text/xml\r\n" ;
			foreach ($additional_headers as $h) {
				$header .= $h."\r\n" ;
			}
			if ($credentials) 
				$header .= "Authorization: Basic " . base64_encode($this->login . ":" . $this->mdp) ."\r\n" ;
			$header .= "Content-Length: ".strlen($content)."\r\n" ;
			$header .= "\r\n";
			
			
			$fp = fsockopen($host, $this->port, $errno, $errstr, 30);
			if (!$fp) {
    			return "$errstr ($errno)";
			} else {
				fwrite($fp, $header.$content);
				$result = "" ; 
				while (!feof($fp)) {
					$result .= fgets($fp, 128);
				}
				fclose($fp);
				return array_merge(array("sent"=> array('header' => $header, 'content' => $content) ), $this->httpMessageParse($result) ) ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Parse HTTP response message (support of chuncked encoding)
		*
		* @param string $message the HTTP message
		* @return array the parsed response
		* @access private
		*/			
		
		function httpMessageParse($message) {
			$tmp = explode("\r\n\r\n", $message, 2) ; 
			$header = $tmp[0] ; 
			$content = $tmp[1] ;
			$lh = explode("\r\n", $header) ; 
			$rh = array() ; 
			foreach ($lh as $l) {
				$p = explode (":", $l, 2) ;
				if (count($p)==2) {
					$rh = array_merge($rh, array(trim($p[0]) => trim($p[1]) )) ; 
				}
			}
			$pl = explode(" ", $lh[0], 3) ; 
			if (count($pl)==3) {
				$rh = array_merge($rh, array("Return-Code-HTTP" => trim($pl[1]) )) ; 
			} else {
				$rh = array_merge($rh, array("Return-Code-HTTP" => "999" )) ; 
			}
			
			// We convert the chuncked content if needed
			//----------
			
			if (strpos(strtolower($header), "transfer-encoding: chunked")) {
				$in = $content ;
				$content = '' ; 
				
				$lines = explode("\r\n", $in) ; 
				$num = 0 ; 
				foreach ($lines as $l) {
					$num ++ ; 
					// La premiere ligne donne une longueur...
					if ($num==1) {
						$length = hexdec($l) ;
						$cur_len = 0 ; 
						$part_content = "" ; 
					} else {
						if ($cur_len != $length) {
							if ($part_content != '') {
								$part_content .= "\r\n" ;
								$cur_len += 2 ; 
							}
							$part_content .= $l ;
							$cur_len += strlen($l) ; 
						} else {
							$length = hexdec($l) ;
							$cur_len = 0 ; 
							$content .= $part_content ;
							$part_content = "" ; 
						}
					}
					
					
				}
			}
			
			return array("header" => $rh, "content" => $content) ; 
		}

		/** ====================================================================================================================================================
		*  Convert plain XML text into DOM document
		* 
		* @param string $content the plain XML text
		* @return DOMdocument the parsed XML
		* @access private
		*/			
		
		function xmlContentParse($content) {
			$doc = new DOMDocument();
			$doc->loadXML($content, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING);
			//$doc->loadXML(trim($content));
			return $doc ; 
		}
		
		/** ====================================================================================================================================================
		* Print the raw response in order to render it into a human readeable message
		* 
		* @param string $raw the raw message to render
		* @return string the readeable message
		* @access private
		*/			
		
		function printRawResult($raw) {
			if ($raw['header']['Return-Code-HTTP']=='401'){
				return "<span style='color:#CC0000'>".__("Your credentials do not seem to be correct. Please check them!", "SL_framework")."</span>" ; 
			}
			if ($raw['header']['Return-Code-HTTP']=='500'){
				if (strpos($raw['content'], "previous representation is currently being written")===false) {
					//return "<span style='color:#CC0000'>".__("This file have not been written in the repository due to server problem. Nevertheless, you should retry as it often works better with a second try !", "SL_framework")."</span>" ; 
				}
			}
			return "<span style='color:#CC0000'>".nl2br(str_replace(" ", "&nbsp;", htmlentities(print_r($raw, true))))."</span>" ;  
		}
		
		
		/** ====================================================================================================================================================
		* Extract from a string one single byte
		* 
		* @param string $string the string from which the byte should be extracted
		* @param integer $offset the offset from which the byte should be extracted from the string
		* @return array 'byte' is the extracted byte, "new_offset' is the new offset (i.e. the offset+1)
		* @access private
		*/
		
		function pop($string, $offset){
                        $res=substr($string, $offset, 1);
			return array('byte'=>$res, 'new_offset'=>$offset+1) ;
		}
		
		/** ====================================================================================================================================================
		* Extract integers that are encoded using a variable-length format (see https://svn.apache.org/repos/asf/subversion/trunk/notes/svndiff)
		* 
		* @param string $string the string from which the integer should be extracted
		* @param integer $offset the offset from which the integer should be extracted from the string
		* @return array 'int' is the extracted integer, "new_offset' is the new offset 
		* @access private
		*/

		function popInt($string, $offset) {
			$n=0;
			while(true){
				$res = $this->pop($string, $offset) ;
				$c = $res['byte'] ; 
				$offset =  $res['new_offset'] ; 
				$c=ord($c);
				$n = (($n << 7)) | ($c & 0x7f);
				if (!($c & 0x80))
					break;
			}
			return array('int'=>$n, 'new_offset'=>$offset) ;
		}
		
		/** ====================================================================================================================================================
		* Convert a integer in a variable-length format (see https://svn.apache.org/repos/asf/subversion/trunk/notes/svndiff)
		* 
		* @param integer $int the integer to convert
		* @return string the bytes representing the integer in a variable-length format 
		* @access private
		*/

		function getChr($int) {
			$bin = decbin($int) ; 
			$result = "" ; 
			$c = true ; 
			$iteration = "0" ; 
			while ($c) {
				if (strlen($bin)>7) {
					// On part de la fin ! car le premier on mets 0 puis 1
					$les7DerniersCaracteres = substr($bin, strlen($bin)-7,7) ;
					$lesAutresCaracteres = substr($bin, 0, strlen($bin)-7) ;
					$result = chr(bindec($iteration . $les7DerniersCaracteres)) . $result ; 
					$iteration = "1" ; 
					$bin = $lesAutresCaracteres ; 
				} else {
					$tmp = chr(bindec($iteration . substr("0000000",0,7 - strlen($bin)) . $bin)) ; 
					$result = $tmp . $result ;
					$c = false ; 
				}
			}
			return $result ; 
		}
		
		/** ====================================================================================================================================================
		* Convert a byte-string into binary represention (string)
		* 
		* @param string $ascii the byte-string to convert
		* @return string the binary representation (8-blocks with space)
		* @access private
		*/
		
		function asc2bin ($ascii) {
			while ( strlen($ascii) > 0 ){
				$byte = "";
				$i = 0;
				$byte = substr($ascii, 0, 1);
				while ( $byte!= chr($i) ) { $i++; }
				$byte = base_convert($i, 10, 2);
				$byte = str_repeat("0", (8 - strlen($byte)) ) . $byte; /* This is an endian (architexture) specific line, you may need to alter it. */
				$ascii = substr($ascii, 1);
				$binary .= $byte." ";
			}
			return $binary;
		} 
		
	}
}

?>