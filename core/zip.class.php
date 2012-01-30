<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

require_once (ABSPATH . 'wp-admin/includes/class-pclzip.php');

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class creates zip file (multipart if needed)
* It requires the gzcompress function. Otherwise, a fatal error will be raised
* For instance : 
* <code>$z = new SL_Zip;<br/>$z -> addFile("/www/test/File.txt","/www/test/","/newroot/");<br/>$z -> addDir("/www/test/Folder","/www/test/","/newroot/") ; <br/>$z -> createZip("/pathToZip/backup.zip",1048576);</code>
*/
if (!class_exists("SL_Zip")) {
	class SL_Zip {
		var $filelist = array();
		var $starttime =0 ; 
		
		function SL_Zip() {
			$this->starttime = microtime(true) ; 
			if (!@function_exists('gzcompress')) {
				die(sprintf(__('Error: %s function is not found', 'SL_framework'), "<code>gzcompress()</code>"));
			}
		}
		
		/** ====================================================================================================================================================
		* Return the progression ratio
		* 
		* @param $file the zip file that is being created
		* @return string the progress nb_file_included/nb_file
		*/
		
		function progress($file) {
			
			if (is_file($file.".tmp")) {
				// We retrieve the process
				$content = @file_get_contents($file.".tmp") ; 
				list($data_segments_len, $nbentry, $pathToReturn, $split_offset, $old_offset, $disk_number, $filelist) = unserialize($content) ; 
				return $nbentry."/".(count($filelist)+$nbentry) ; 
			} 
			
			return "" ; 
		}	
		
		/** ====================================================================================================================================================
		* Add files to the archive
		* 
		* @param string $filename the path of the file to add
		* @param string $remove the part of the path to remove
		* @param string $add the part of the path to add
		* @return void
		*/
		
		function addFile($filename, $remove="", $add="") {
			if(is_file($filename)) {
				$this->filelist[] = array(str_replace('\\', '/', $filename), $remove, $add) ;
			} else {
				// Nothing
			}
		}
		
		
		/** ====================================================================================================================================================
		* Add directory to the archive (reccursively)
		* 
		* @param string $dirname the path of the folder to add
		* @param string $remove the part of the path to remove
		* @param string $add the part of the path to add
		* @param array $exclu a list of folder that are no to be included in the zip file
		* @return void
		*/
		
		function addDir($dirname, $remove="", $add="", $exclu=array()) {
			if ($handle = opendir($dirname)) { 
				while (false !== ($filename = readdir($handle))) { 
					// We check if exclu
					$exclu_folder = false ; 
					foreach($exclu as $e) {
						$path = str_replace("//", "/", $dirname . '/' . $filename) ; 
						if (($e==$path)||($e==$path."/")) {
							$exclu_folder=true ; 
						}
					}
					// On recursive
					if ($filename != "." && $filename != ".." && !$exclu_folder)  {
						if (is_file($dirname . '/' . $filename)) {
							$this->addFile($dirname . '/' . $filename, $remove, $add);
						} 
						if (is_dir($dirname . '/' . $filename)) {
							$this->addDir($dirname . '/' . $filename, $remove, $add, $exclu);
						}
					}
				} 
				closedir($handle); 
			} else {
				//Nothing
			}
		}
		
		/** ====================================================================================================================================================
		* Tells whether a zip file is being created or not
		* 
		* @param $path the path in which the zip should be created
		* @return array the 'step' could be 'in progress' (a process is still running), 'nothing' (no zip is being zipped) or 'to be completed' (and the 'name_zip' will be the name of the zip file being zipped) or 'error' (and the 'msg' will display the error messgae)
		*/
		
		function is_inProgress($path) {
			if (is_file($path."/in_progress")) {
				$timestart = @file_get_contents($path."/in_progress")  ;
				if ($timestart===FALSE) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
				}
				$timeprocess = time() - (int)$timestart ; 
				// We ensure that the process has not been started a too long time ago
				if ($timeprocess<200) {
					return array("step"=>"in progress", "for"=>$timeprocess) ; 
				} else {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
				}
			} 
			
			// We search for a tmp file
			$files = @scandir($path) ;
			if ($files===FALSE) {
				return array("step"=>"error", "msg"=>sprintf(__('The folder %s cannot be opened. You should have a problem with folder permissions or security restrictions.', 'SL_framework'),"<code>".$path."</code>")) ; 
			}
			foreach ($files as $f) {
				if (preg_match("/zip[.]tmp$/i", $f)) {
					$name_file = str_replace(".zip.tmp", ".zip",$f) ; 
					return array("step"=>"to be completed", 'name_zip' => $name_file) ; 
				} 
			}
			return array("step"=>"nothing") ; 
		}	
		
		/** ====================================================================================================================================================
		* Create the archive and split it if necessary
		* 
		* @param string $splitfilename the path of the zip file to create
		* @param integer $chunk_size the maximum size of the archive
		* @param integer $maxExecutionTime the maximum execution time in second (if this time is exceeded, the function will return false. You just have to relaunch this function to complete the zip from where it has stopped)
		* @param integer $maxExecutionTime the maximum memory allocated by the process (in bytes)
		* @return array with the name of the file (or 'finished' => false if an error occured see 'error' for the error message)
		*/
		
		function createZip($splitfilename, $chunk_size=1000000000000000, $maxExecutionTime=150, $maxAllocatedMemory=4000000) {
			
			// Init variables
			//---------------------
			
			$zipfile_comment = "Compressed/Splitted by the SL framework (SedLex)";
			
			$path = str_replace(basename ($splitfilename), "", $splitfilename) ; 
			
			if ($chunk_size!=1000000000000000)
				$splitted = true;
			else
				$splitted = false;
			
			$pathToReturn = array() ; 
			
			$split_offset = 4;
			$old_offset = $split_offset;
			$disk_number = 1 ; 
			$split_signature = "\x50\x4b\x07\x08";
			$nbentry = 0 ; 
			$file_headers = "" ; 
			$data_segments = "" ; 
			$data_segments_len = 0 ; 
			
			//  We check whether a process is running
			//----------------------------------------------

			if (is_file(dirname($splitfilename)."/in_progress")) {
				$timestart = @file_get_contents(dirname($splitfilename)."/in_progress")  ;
				
				// We cannot read the lock file
				if ($timestart===FALSE) {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".dirname($splitfilename)."/in_progress</code>")) ; 
				}
				$timeprocess = time() - (int)$timestart ; 
				// We ensure that the process has not been started a too long time ago
				if ($timeprocess<200) {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
					return array('finished'=>false, 'error' => sprintf(__("An other process is still running (it runs for %s seconds)", "SL_framework"), $timeprocess)) ; 
				} else {
					// We create a file with the time inside to indicate that this process is doing something
					$r = @file_put_contents(dirname($splitfilename)."/in_progress", time()) ; 
				}
			}
			
			
			//  We create a lock file
			//----------------------------------------------

			$r = @file_put_contents(dirname($splitfilename)."/in_progress", time()) ; 
			if ($r===FALSE) {
				if (!Utils::rm_rec($path."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
				}
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".dirname($splitfilename)."/in_progress</code>")) ; 
			}
					
			
			//  We retrieve old saved param
			//      if the .tmp file exists, it means that we have to restart the zip process where it stopped
			//----------------------------------------------

			if (is_file($splitfilename.".tmp")) {
				// We retrieve the process
				$content = @file_get_contents($splitfilename.".tmp") ; 
				
				if ($content===FALSE) {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".tmp</code>")) ; 
				}
				
				list($data_segments_len, $nbentry, $pathToReturn, $split_offset, $old_offset, $disk_number, $this->filelist) = unserialize($content) ; 
			}
			
			if (!is_file($splitfilename.".data_segment.tmp")) {
				$r = @file_put_contents($splitfilename.".data_segment.tmp" ,$split_signature) ; 
				if ($r===FALSE) {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
				}
			}
				
			
			//  The creation of the zip begin
			//----------------------------------------------

			foreach($this->filelist as $k => $filename_array) {
				$add_t = $filename_array[2] ; 
				$remove_t = $filename_array[1] ; 
				$filename = $filename_array[0] ; 
				
				//  If the time limit / memory limit exceed, we save into temp files
				//----------------------------------------------
				
				$nowtime = microtime(true) ; 
				if ($maxExecutionTime!=0) {
					if (($nowtime - $this->starttime > $maxExecutionTime) || ($maxAllocatedMemory<=strlen($data_segments))){
						// We remove the file already inserted in the zip
						$this->filelist =  array_slice($this->filelist,$k);
						// We save the content on the disk
						
						$r = @file_put_contents($splitfilename.".tmp" ,serialize(array($data_segments_len, $nbentry, $pathToReturn, $split_offset, $old_offset, $disk_number, $this->filelist))) ; 
						if ($r===FALSE) {
							if (!Utils::rm_rec($path."/in_progress")) {
								return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
							}
							return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".tmp</code>")) ; 
						}
						$r = @file_put_contents($splitfilename.".data_segment.tmp" ,$data_segments, FILE_APPEND) ; 
						if ($r===FALSE) {
							if (!Utils::rm_rec($path."/in_progress")) {
								return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
							}
							return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
						}
						$r = @file_put_contents($splitfilename.".file_headers.tmp" ,$file_headers, FILE_APPEND) ; 
						if ($r===FALSE) {
							if (!Utils::rm_rec($path."/in_progress")) {
								return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
							}
							return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".file_headers.tmp</code>")) ; 
						}
						// we inform that the process is finished
						if (!Utils::rm_rec($path."/in_progress")) {
							return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
						}

						return  array('finished'=>false, 'nb_to_finished' => count($this->filelist), 'nb_finished' => ($nbentry)) ; 
					}
				}
				
				
				//  Check if the file to be inserted in the zip file still exists
				//----------------------------------------------

				if (!is_file($filename)) {
					continue ; 
				}
				
				//  Compress
				//----------------------------------------------

				$nbentry ++ ; 
								
				//Get the data
				$filedata = @file_get_contents($filename);
				if ($filedata===FALSE) {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$filename."</code>")) ; 
				}
				
				//Compressing data
				$c_data = @gzcompress($filedata);
				if ($c_data===FALSE) {
					if (!Utils::rm_rec($path."/in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
					}
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be compressed.', 'SL_framework'),"<code>".$filename."</code>")) ; 
				}
				$compressed_filedata = substr(substr($c_data, 0, strlen($c_data) - 4), 2); // fix crc bug
								
				// Get the time
				clearstatcache();
				$filetime = @filectime($filename);
				if ($filetime == 0) { 
					$timearray = getdate() ;
				} else { 
					$timearray = getdate($filetime) ; 
				}
				if ($timearray['year'] < 1980) {
					$timearray['year']    = 1980;
					$timearray['mon']     = 1;
					$timearray['mday']    = 1;
					$timearray['hours']   = 0;
					$timearray['minutes'] = 0;
					$timearray['seconds'] = 0;
				} 
				$dostime = (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
				$dtime    = dechex($dostime);
				$hexdtime = '\x' . $dtime[6] . $dtime[7] . '\x' . $dtime[4] . $dtime[5];
				$hexddate = '\x' . $dtime[2] . $dtime[3]. '\x' . $dtime[0] . $dtime[1];
				eval('$hexdtime = "' . $hexdtime . '";');
				eval('$hexddate = "' . $hexddate . '";');
				$last_mod_file_time = $hexdtime;
				$last_mod_file_date = $hexddate;
							
				//Set Local File Header
				$newfilename = str_replace("//", "/", $add_t.str_replace($remove_t, "", $filename)) ; 
				if (substr($newfilename, 0, 1)=="/") {
					$newfilename = substr($newfilename, 1) ; 
				}
				
				$local_file_header_signature = "\x50\x4b\x03\x04";//4 bytes  (0x04034b50) local_file_header_signature
				$version_needed_to_extract = "\x14\x00";  //2 bytes version_needed_to_extract
				$general_purpose_bit_flag = "\x00\x00";  //2 bytes general_purpose_bit_flag
				$compression_method = "\x08\x00";  //2 bytes compression_method
				$crc_32 = pack('V', crc32($filedata)); //  4 bytes crc_32
				$compressed_size = pack('V', strlen($compressed_filedata));// 4 bytes compressed_size
				$uncompressed_size = pack('V', strlen($filedata));//4 bytes uncompressed_size
				$filename_length = pack('v', strlen($newfilename));// 2 bytes filename_length
				$extra_field_length = pack('v', 0);  //2 bytes extra_field_length
				
				$local_file_header = $local_file_header_signature . $version_needed_to_extract . $general_purpose_bit_flag .$compression_method .$last_mod_file_time .$last_mod_file_date .$crc_32 .$compressed_size .$uncompressed_size .$filename_length .$extra_field_length . $newfilename;
								
				//Set Data Descriptor
				$data_descriptor =  $crc_32.$compressed_size . $uncompressed_size;          //4+4+4 bytes
								
				//Set Data Segment
				$data_segments .=     $local_file_header . $compressed_filedata . $data_descriptor; 
				$data_segments_len += strlen($local_file_header)+strlen($compressed_filedata) +strlen( $data_descriptor ); 
				
				//Set File Header
				$new_offset        		= strlen( $split_signature ) + $data_segments_len ;
				$central_file_header_signature  = "\x50\x4b\x01\x02";//4 bytes  (0x02014b50)
				$version_made_by                = pack('v', 0);  //2 bytes
				$file_comment_length            = pack('v', 0);  //2 bytes
				$disk_number_start              = pack('v', $disk_number - 1); //2 bytes
				$internal_file_attributes       = pack('v', 0); //2 bytes
				$external_file_attributes       = pack('V', 32); //4 bytes
				$relative_offset_local_header   = pack('V', $old_offset); //4 bytes
							
				if($splitted) {
					$disk_number = ceil($new_offset/$chunk_size);
					$old_offset = $new_offset - ($chunk_size * ($disk_number-1));
				} else {
					$old_offset = $new_offset;
				}
			
				$file_headers .= $central_file_header_signature . $version_made_by . $version_needed_to_extract . $general_purpose_bit_flag . $compression_method . $last_mod_file_time . $last_mod_file_date . $crc_32 .$compressed_size .$uncompressed_size .$filename_length .$extra_field_length . $file_comment_length .  $disk_number_start . $internal_file_attributes . $external_file_attributes . $relative_offset_local_header . $newfilename;
			}
			
			//  Finalization
			//----------------------------------------------
		
			$r = @file_put_contents($splitfilename.".data_segment.tmp" ,$data_segments, FILE_APPEND) ; 
			if ($r===FALSE) {
				if (!Utils::rm_rec($path."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
				}
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
			}
			$r = @file_put_contents($splitfilename.".file_headers.tmp" ,$file_headers, FILE_APPEND) ; 
			if ($r===FALSE) {
				if (!Utils::rm_rec($path."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
				}
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".file_headers.tmp</code>")) ; 
			}			
			
			// We retrieve the file header
			$file_headers = @file_get_contents($splitfilename.".file_headers.tmp") ; 
			if ($file_headers===FALSE) {
				if (!Utils::rm_rec($path."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
				}
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".file_headers.tmp</code>")) ; 
			}
			if (!Utils::rm_rec($path."/file_headers.tmp")) {
				return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/file_headers.tmp</code>")) ; 
			}
			
			// We finalize
			if($splitted) {
				$data_len = strlen($split_signature) + $data_segments_len + strlen($file_headers);
				$last_chunk_len = $data_len - floor($data_len / $chunk_size) * $chunk_size;
				$old_offset = $last_chunk_len - strlen($file_headers);
			}
	
			$end_central_dir_signature    = "\x50\x4b\x05\x06";//4 bytes  (0x06054b50)
			$number_this_disk             = pack('v', $disk_number - 1);//2 bytes
			$number_disk_start              = pack('v', $disk_number - 1);//  2 bytes
			$total_number_entries          = pack('v', $nbentry);//2 bytes
			$total_number_entries_central = pack('v', $nbentry);//2 bytes
			$size_central_directory         = pack('V', strlen($file_headers));  //4 bytes
			$offset_start_central         = pack('V', $old_offset); //4 bytes     
			$zipfile_comment_length       = pack('v', strlen($zipfile_comment));//2 bytes
			$endCentralDirectory  = $end_central_dir_signature . $number_this_disk . $number_disk_start . $total_number_entries . $total_number_entries_central . $size_central_directory . $offset_start_central . $zipfile_comment_length . $zipfile_comment; 
		
			// We complete the data segments file
			$r = @file_put_contents($splitfilename.".data_segment.tmp" , $file_headers. $endCentralDirectory, FILE_APPEND) ; 
			if ($r===FALSE) {
				if (!Utils::rm_rec($path."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
				}
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
			}
			
			// We split the zip file
			$taille = filesize($splitfilename.".data_segment.tmp") ; 
			$fp = @fopen($splitfilename.".data_segment.tmp", 'rb') ; 
			if ($fp) {
				$j = 0 ; 
				while(!feof($fp)) {
					$j++ ; 
					$out = @fread($fp, $chunk_size) ; 
					if ($out===FALSE) {
						if (!Utils::rm_rec($path."/in_progress")) {
							return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
						}
						return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
					}
					// Select the correct name of the file
					if( !feof($fp) ) {
						$sfilename = basename ($splitfilename,".zip"); 
						$sfilename = $path . $sfilename . ".z" . sprintf("%02d",$j) ;
					} else {
						$sfilename = $splitfilename ;
					}
					
					$pathToReturn[] = $sfilename ; 
					$r = @file_put_contents($sfilename, $out);
					if ($r===FALSE) {
						if (!Utils::rm_rec($path."/in_progress")) {
							return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/in_progress</code>")) ; 
						}
						return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sfilename."</code>")) ; 
					}
				}
				if (!Utils::rm_rec($splitfilename.".data_segment.tmp")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
				}
				if (!Utils::rm_rec($splitfilename.".tmp")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".tmp</code>")) ; 
				}
				// we inform that the process is finished
				if (!Utils::rm_rec(dirname($splitfilename)."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".dirname($splitfilename)."/in_progress</code>")) ; 
				}
				@fclose($fp) ; 
				return array('finished'=>true, 'nb_finished'=>$nbentry,'nb_to_finished'=>0, 'nb_files'=>$nbentry , 'path'=>$pathToReturn) ; 
			} else {
				// we inform that the process is finished
				if (!Utils::rm_rec(dirname($splitfilename)."/in_progress")) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".dirname($splitfilename)."/in_progress</code>")) ; 
				}
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$splitfilename.".data_segment.tmp</code>")) ; 
			}
		}
	} 
}


?>