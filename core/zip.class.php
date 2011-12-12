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
* <code>$z = new SL_Zip;<br/>$z -> addFile("/www/test/File.txt");<br/>$z -> addDir("/www/test/Folder");<br/>$z -> removePath("/www/test/") ; <br/>$z -> addPath("/newroot/") ; <br/>$z -> createZip("/pathToZip/backup.zip",1048576);</code>
*/
if (!class_exists("SL_Zip")) {
	class SL_Zip {
		var $filelist = array();
		var $starttime =0 ; 
		var $removepath = "" ; 
		var $addpath = "" ; 
		
		function SL_Zip() {
			$this->starttime = microtime(true) ; 
			if (!@function_exists('gzcompress')) {
				die(sprintf(__('Error: %s function is not found', 'SL_framework'), "<code>gzcompress()</code>"));
			}
		}
	 
		/** ====================================================================================================================================================
		* Add files to the archive
		* 
		* @param string $filename the path of the file to add
		* @return void
		*/
		
		function addFile($filename) {
			if(is_file($filename)) {
				$this->filelist[] = str_replace('\\', '/', $filename);
			} else {
				// Nothing
			}
		}
		
		
		/** ====================================================================================================================================================
		* Add directory to the archive (reccursively)
		* 
		* @param string $dirname the path of the folder to add
		* @return void
		*/
		
		function addDir($dirname) {
			if ($handle = opendir($dirname)) { 
				while (false !== ($filename = readdir($handle))) { 
					if ($filename != "." && $filename != "..")  {
						if (is_file($dirname . '/' . $filename)) {
							$this->addFile($dirname . '/' . $filename);
						} 
						if (is_dir($dirname . '/' . $filename)) {
							$this->addDir($dirname . '/' . $filename);
						}
					}
				} 
				closedir($handle); 
			} else {
				//Nothing
			}
		}
		
		/** ====================================================================================================================================================
		* Remove a part of the path in the archive
		* 
		* @param string $remove the path to be remove
		* @return void
		*/
		
		function removePath($remove) {
			$this->removePath = $remove ; 
		}	
		
		/** ====================================================================================================================================================
		* Remove a part of the path in the archive
		* 
		* @param string $remove the path to be remove
		* @return void
		*/
		
		function addPath($add) {
			$this->addPath = $add ; 
		}	
		
		/** ====================================================================================================================================================
		* Tells whether a zip file is being created or not
		* 
		* @param $path the path in which the zip should be created
		* @return array the 'step' could be 'in progress' (a process is still running), 'nothing' (no zip is being zipped) or 'to be completed' (and the 'name_zip' will be the name of the zip file being zipped)
		*/
		
		function is_inProgress($path) {
			if (is_file($path."/in_progress")) {
				$timestart = @file_get_contents($path."/in_progress")  ;
				$timeprocess = time() - (int)$timestart ; 
				// We ensure that the process has not been started a too long time ago
				if ($timeprocess<200) {
					return array("step"=>"in progress") ; 
				}
				
			} 
			
			// We search for a tmp file
			$files = scandir($path) ;
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
		* @return array with the name of the file (or false if the max eceution time has been exceeded)
		*/
		
		function createZip($splitfilename, $chunk_size=1000000000000000, $maxExecutionTime=0) {
			$zipfile_comment = "Compressed/Splitted by the SL framework (SedLex)";
			
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
			
			// We check that no process is running
			if (is_file(dirname($splitfilename)."/in_progress")) {
				$timestart = @file_get_contents(dirname($splitfilename)."/in_progress")  ;
				$timeprocess = time() - (int)$timestart ; 
				// We ensure that the process has not been started a too long time ago
				if ($timeprocess<200) {
					return array('finished'=>false, 'error' => sprintf(__("An other process is still running (it runs for %s seconds)", "SL_framework"), $timeprocess)) ; 
				}
			}
			// We create a file with the time inside to indicate that this process is doing something
			@file_put_contents(dirname($splitfilename)."/in_progress", time()) ; 
				
			// We look if the .tmp file exists, if so, it means that we have to restart the zip process where it stopped
			if (is_file($splitfilename.".tmp")) {
				// We retrieve the process
				$content = @file_get_contents($splitfilename.".tmp") ; 
				@unlink($splitfilename.".tmp") ; 
				list($data_segments_len, $nbentry, $pathToReturn, $split_offset, $old_offset, $disk_number, $this->filelist, $this->addpath, $this->removepath) = unserialize($content) ; 
			}
			if (!is_file($splitfilename.".data_segment.tmp")) {
				@file_put_contents($splitfilename.".data_segment.tmp" ,$split_signature) ; 
			}
				
			// We create the zip file
			foreach($this->filelist as $k => $filename) {
			
				// We check that the time is not exceeded
				$nowtime = microtime(true) ; 
				if ($maxExecutionTime!=0) {
					if ($nowtime - $this->starttime > $maxExecutionTime) {
						// We remove the file already inserted in the zip
						$this->filelist =  array_slice($this->filelist,$k);
						// We save the content on the disk
						@file_put_contents($splitfilename.".tmp" ,serialize(array($data_segments_len, $nbentry, $pathToReturn, $split_offset, $old_offset, $disk_number, $this->filelist, $this->addpath, $this->removepath))) ; 
						@file_put_contents($splitfilename.".data_segment.tmp" ,$data_segments, FILE_APPEND) ; 
						@file_put_contents($splitfilename.".file_headers.tmp" ,$file_headers, FILE_APPEND) ; 
						
						// we inform that the process is finished
						@unlink(dirname($splitfilename)."/in_progress") ; 
						
						return  array('finished'=>false, 'nb_to_finished' => count($this->filelist), 'nb_finished' => ($nbentry)) ; 
					}
				}
				
				
				$nbentry ++ ; 
								
				//Get the data
				$filedata = file_get_contents($filename);
				
				//Compressing data
				$c_data   = gzcompress($filedata);
				$compressed_filedata    = substr(substr($c_data, 0, strlen($c_data) - 4), 2); // fix crc bug
								
				// Get the time
				clearstatcache();
				$filetime = filectime($filename);
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
				$local_file_header_signature = "\x50\x4b\x03\x04";//4 bytes  (0x04034b50) local_file_header_signature
				$version_needed_to_extract = "\x14\x00";  //2 bytes version_needed_to_extract
				$general_purpose_bit_flag = "\x00\x00";  //2 bytes general_purpose_bit_flag
				$compression_method = "\x08\x00";  //2 bytes compression_method
				$crc_32 = pack('V', crc32($filedata)); //  4 bytes crc_32
				$compressed_size = pack('V', strlen($compressed_filedata));// 4 bytes compressed_size
				$uncompressed_size = pack('V', strlen($filedata));//4 bytes uncompressed_size
				$filename_length = pack('v', strlen(str_replace("//", "/", $this->addPath.str_replace($this->removePath, "", $filename))));// 2 bytes filename_length
				$extra_field_length = pack('v', 0);  //2 bytes extra_field_length
				
				$local_file_header = $local_file_header_signature . $version_needed_to_extract . $general_purpose_bit_flag .$compression_method .$last_mod_file_time .$last_mod_file_date .$crc_32 .$compressed_size .$uncompressed_size .$filename_length .$extra_field_length . str_replace("//", "/", $this->addPath.str_replace($this->removePath, "", $filename));
								
				//Set Data Descriptor
				$data_descriptor =  $crc_32.$compressed_size . $uncompressed_size;          //4+4+4 bytes
								
				//Set Data Segment
				$data_segments .=     $local_file_header . $compressed_filedata . $data_descriptor; 
				$data_segments_len += strlen($local_file_header . $compressed_filedata . $data_descriptor) ; 
				
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
			
				$file_headers .= $central_file_header_signature . $version_made_by . $version_needed_to_extract . $general_purpose_bit_flag . $compression_method . $last_mod_file_time . $last_mod_file_date . $crc_32 .$compressed_size .$uncompressed_size .$filename_length .$extra_field_length . $file_comment_length .  $disk_number_start . $internal_file_attributes . $external_file_attributes . $relative_offset_local_header . str_replace("//", "/", $this->addPath.str_replace($this->removePath, "", $filename));
				
			}
			
			// We complete the tmp files with current content
			@file_put_contents($splitfilename.".data_segment.tmp" ,$data_segments, FILE_APPEND) ; 
			@file_put_contents($splitfilename.".file_headers.tmp" ,$file_headers, FILE_APPEND) ; 
						
			// We retrieve the file header
			$file_headers = @file_get_contents($splitfilename.".file_headers.tmp") ; 
			@unlink($splitfilename.".file_headers.tmp") ; 
			
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
			@file_put_contents($splitfilename.".data_segment.tmp" , $file_headers. $endCentralDirectory, FILE_APPEND) ; 
			
			// We split the zip file
			$fp = fopen($splitfilename.".data_segment.tmp", 'rb') ; 
			if ($fp) {
				$j = 0 ; 
				for ($i = 0; $i < strlen($split_signature) + $data_segments_len + strlen($file_headers.$endCentralDirectory) ; $i += $chunk_size) {
					
					$j++ ; 
					$out = fread($fp, $chunk_size) ; 
					
					// Select the correct name of the file
					if( $i+$chunk_size < strlen($split_signature) + $data_segments_len + strlen($file_headers.$endCentralDirectory) ) {
						$sfilename = basename ($splitfilename,".zip"); 
						$path = str_replace(basename ($splitfilename), "", $splitfilename) ; 
						$sfilename = $path . $sfilename . ".z" . sprintf("%02d",$j);
					} else {
						$sfilename = $splitfilename;
					}
					
					$pathToReturn[] = $sfilename ; 
					@file_put_contents($sfilename, $out);
				}
				@unlink($splitfilename.".data_segment.tmp") ; 
				// we inform that the process is finished
				@unlink(dirname($splitfilename)."/in_progress") ; 
				fclose($fp) ; 
				return array('finished'=>true, 'nb_files'=>$nbentry , 'path'=>$pathToReturn) ; 
			} else {
				// we inform that the process is finished
				@unlink(dirname($splitfilename)."/in_progress") ; 
				@unlink($splitfilename.".data_segment.tmp") ; 
				return array('finished'=>true, 'error' => 'Cannot open the file') ; 
			}
		}
	} 
}


?>