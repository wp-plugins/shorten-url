<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class creates an export of the database
*/
if (!class_exists("SL_Database")) {
	class SL_Database {
		
		function SL_Database() {
			$this->starttime = microtime(true) ; 
		}
		
		/** ====================================================================================================================================================
		* Return the progression ratio
		* 
		* @param $file the sql file that is being created
		* @return string the progress nb_table_extracted/nb_table
		*/
		
		function progress($file) {
			
			if (is_file($file.".tmp")) {
				// We retrieve the process
				$content = @file_get_contents($file.".tmp") ; 
				list($list_table, $current_index, $current_offset) = unserialize($content) ;
				return $current_index."/".count($list_table) ; 
			} 
			
			return "" ; 
		}	
		
		/** ====================================================================================================================================================
		* Tells whether a database extraction is in progress
		* 
		* @param $path the path in which the database sql file should be created
		* @return array the 'step' could be 'in progress' (a process is still running), 'nothing' (no sql is being created) or 'to be completed' (and the 'name_sql' will be the name of the sql file being created) or 'error' (and the 'msg' will display the error messgae)
		*/
		
		function is_inProgress($path) {
			if (is_file($path."/sql_in_progress")) {
				$timestart = @file_get_contents($path."/sql_in_progress")  ;
				if ($timestart===FALSE) {
					return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/sql_in_progress</code>")) ; 
				}
				$timeprocess = time() - (int)$timestart ; 
				// We ensure that the process has not been started a too long time ago
				if ($timeprocess<200) {
					return array("step"=>"in progress", "for"=>$timeprocess) ; 
				} else {
					if (!Utils::rm_rec($path."/sql_in_progress")) {
						return array("step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', $this->pluginID),"<code>".$path."/sql_in_progress"."</code>")) ; 
					}
				}
			} 
			
			// We search for a sql.tmp file
			$files = @scandir($path) ;
			if ($files===FALSE) {
				return array("step"=>"error", "msg"=>sprintf(__('The folder %s cannot be opened. You should have a problem with folder permissions or security restrictions.', 'SL_framework'),"<code>".$path."</code>")) ; 
			}
			foreach ($files as $f) {
				if (preg_match("/sql[.]tmp$/i", $f)) {
					$name_file = str_replace(".sql.tmp", ".sql",$f) ; 
					return array("step"=>"to be completed", 'name_sql' => $name_file) ; 
				} 
			}
			return array("step"=>"nothing") ; 
		}	

		
		/** ====================================================================================================================================================
		* Create the sql file
		* 
		* @param string $sqlfilename the path of the sql file to create
		* @param integer $maxExecutionTime the maximum execution time in second (if this time is exceeded, the function will return false. You just have to relaunch this function to complete the zip from where it has stopped)
		* @param integer $maxAllocatedMemory the maximum memory allocated by the process (in bytes)
		* @return array with the name of the file (or 'finished' => false and if an error occured see 'error' for the error message)
		*/
		
		function createSQL($sqlfilename, $maxExecutionTime=150, $maxAllocatedMemory=4000000) {
			global $wpdb ; 
			
			$path = dirname($sqlfilename) ; 
			
			// We check that no process is running
			if (is_file($path."/sql_in_progress")) {
				$timestart = @file_get_contents($path."/sql_in_progress")  ;
				if ($timestart===FALSE) {
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/sql_in_progress</code>")) ; 
				}
				$timeprocess = time() - (int)$timestart ; 
				// We ensure that the process has not been started a too long time ago
				if ($timeprocess<200) {
					return array('finished'=>false, 'error' => sprintf(__("An other process is still running (it runs for %s seconds)", "SL_framework"), $timeprocess)) ; 
				} else {
					if (!Utils::rm_rec($path."/sql_in_progress")) {
						return array('finished'=>false, "step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', $this->pluginID),"<code>".$path."/sql_in_progress"."</code>")) ; 
					}
				}
			}
			
			// We create a file with the time inside to indicate that this process is doing something
			$r = @file_put_contents(dirname($sqlfilename)."/sql_in_progress", time()) ; 
			if ($r===FALSE) {
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$path."/sql_in_progress</code>")) ; 
			}
			
			// Default value
			$current_index = 0 ; 
			$current_offset = 0 ; 
			$max_size = 1000 ; 
			$contentOfTable = "" ; 
				
			// We look if the .tmp file exists, if so, it means that we have to restart the zip process where it stopped
			if (is_file($sqlfilename.".tmp")) {
				// We retrieve the process
				$content = @file_get_contents($sqlfilename.".tmp") ; 
				if ($content===FALSE) {
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be read. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sqlfilename.".tmp</code>")) ; 
				}
				list($list_table, $current_index, $current_offset) = unserialize($content) ; 
			}
			if (!is_file($sqlfilename.".content.tmp")) {
				$entete  = "-- -------------------------------------------------\n";
				$entete .= "-- ".DB_NAME." - ".date("d-M-Y")."\n";
				$entete .= "-- -----------------------------------------------\n";
				
				$list_table = $wpdb->get_results("show tables", ARRAY_N);
				foreach ($list_table as $table) {
					$entete .= "\n\n";
					$entete .= "-- -----------------------------\n";
					$entete .= "-- CREATE ".$table[0]."\n";
					$entete .= "-- -----------------------------\n";
					$entete .= $wpdb->get_var("show create table ".$table[0], 1).";";
				}
				$r = @file_put_contents($sqlfilename.".content.tmp" ,$entete) ; 
				if ($r===FALSE) {
					return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sqlfilename.".content.tmp</code>")) ; 
				}
			}
				
			// We create the sql file
			for($i=$current_index ; $i<count($list_table) ; $i++) {
				$table = $list_table[$i] ; 
				
				$nb_response = $max_size ;
				
				while ($nb_response==$max_size) {
					// We check that the time is not exceeded
					$nowtime = microtime(true) ; 
					if ($maxExecutionTime!=0) {
						if (($nowtime - $this->starttime > $maxExecutionTime) || ($maxAllocatedMemory<=strlen($contentOfTable))){
							// We save the content on the disk
							$r = @file_put_contents($sqlfilename.".tmp" ,serialize(array($list_table, $i, $current_offset))) ; 
							if ($r===FALSE) {
								return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sqlfilename.".tmp</code>")) ; 
							}
							$r = @file_put_contents($sqlfilename.".content.tmp" ,$contentOfTable, FILE_APPEND) ; 
							if ($r===FALSE) {
								return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sqlfilename.".content.tmp</code>")) ; 
							}
							// we inform that the process is finished
							if (!Utils::rm_rec($path."/sql_in_progress")) {
								return array('finished'=>false, "step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', $this->pluginID),"<code>".$path."/sql_in_progress"."</code>")) ; 
							}
							return  array('finished'=>false, 'nb_to_finished' => count($list_table)-($i-1), 'nb_finished' => ($i-1)) ; 
						}
					}
					// Now we retrieve the content.
					if ($current_offset==0) {
						$contentOfTable .= "\n\n";
						$contentOfTable .= "-- -----------------------------\n";
						$contentOfTable .= "-- INSERT INTO ".$table[0]."\n";
						$contentOfTable .= "-- -----------------------------\n\n";
					}
					$lignes = $wpdb->get_results("SELECT * FROM ".$table[0]." LIMIT ".$current_offset.",".$max_size, ARRAY_N);
					@mysql_free_result($wpdb->dbh) ; 
					$current_offset += $max_size ; 
					$nb_response = count($lignes) ; 
										
					foreach ( $lignes as $ligne ) {
						$contentOfTable .= "INSERT INTO ".$table[0]." VALUES(";
						for($ii=0; $ii < count($ligne); $ii++) {
							if($ii != 0) 
								$contentOfTable .=  ", ";
							//DATE, TIMESTAMP, TIMESTAMP
							$delimit = "" ; 
							if ( ($wpdb->get_col_info('type', $ii) == "string") || ($wpdb->get_col_info('type', $ii) == "blob") || ($wpdb->get_col_info('type', $ii) == "datetime") || ($wpdb->get_col_info('type', $ii) == "date") || ($wpdb->get_col_info('type', $ii) == "timestamp") || ($wpdb->get_col_info('type', $ii) == "time") || ($wpdb->get_col_info('type', $ii) == "year") )
								$delimit .=  "'";
							if ($ligne[$ii]==NULL) {
								$ligne[$ii]="NULL" ; 
							}
							$contentOfTable .= $delimit.addslashes($ligne[$ii]).$delimit;
							
				
						}
						$contentOfTable .=  ");\n";
					}
				}
				$current_offset=0 ; 
			}
			
			// We complete the tmp files with current content
			$r = @file_put_contents($sqlfilename.".content.tmp" ,$contentOfTable, FILE_APPEND) ; 
			if ($r===FALSE) {
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be modified/created. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sqlfilename.".data_segment.tmp</code>")) ; 
			}
					
			// we inform that the process is finished
			if (!Utils::rm_rec($path."/sql_in_progress")) {
				return array('finished'=>false, "step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', $this->pluginID),"<code>".$path."/sql_in_progress"."</code>")) ; 
			}
			if (!Utils::rm_rec($sqlfilename.".tmp")) {
				return array('finished'=>false, "step"=>"error", "msg"=>sprintf(__('The file %s cannot be deleted. You should have a problem with file permissions or security restrictions.', $this->pluginID),"<code>".$sqlfilename.".tmp"."</code>")) ; 
			}
			$r = @rename($sqlfilename.".content.tmp", $sqlfilename) ; 
			if ($r===FALSE) {
				return array('finished'=>false, "error"=>sprintf(__('The file %s cannot be renamed. You should have a problem with file permissions or security restrictions.', 'SL_framework'),"<code>".$sqlfilename.".content.tmp</code>")) ; 
			}
			
			return array('finished'=>true, 'nb_to_finished' => 0, 'nb_finished' => count($list_table) ) ; 
			
		}
	} 
}


?>