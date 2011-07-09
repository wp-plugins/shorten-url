<?php
/*
Core SedLex Plugin
VersionInclude : 2.1
*/ 

/** ====================================================================================================================================================
* Utils class 
* 
* @return void
*/
if (!class_exists("Utils")) {
	class Utils {
		/** ====================================================================================================================================================
		* Utils pour connaitre la taille d'un repertoire
		* 
		* @return void
		*/
		
		static function dirSize($path , $recursive=TRUE){
			$result = 0;
			if(!is_dir($path) || !is_readable($path)) {
				return 0;
			}
			$fd = dir($path);
			while($file = $fd->read()){
				if(($file != ".") && ($file != "..")){
					if(@is_dir($path.'/'.$file)) {
						$result += $recursive?Utils::dirSize($path.'/'.$file):0;
					} else {
						$result += filesize($path.'/'.$file);
					}
				}
			}
			$fd->close();
			return $result;
		}
		
		/** ====================================================================================================================================================
		* Test if argument is really an integer (even if string)
		* 
		* @return boolean
		*/
		
		static function is_really_int($int){
			if(is_numeric($int) === TRUE){
				// It's a number, but it has to be an integer
				if((int)$int == $int){
					return TRUE;
				// It's a number, but not an integer, so we fail
				}else{
					return FALSE;
				}
			// Not a number
			}else{
				return FALSE;
			}
		}
		
		/** ====================================================================================================================================================
		* Randomize a string
		* 
		* @return string
		*/
		static function rand_str($length, $chars) {
			// Length of character list
			$chars_length = (strlen($chars) - 1);
			// Start our string
			$string = $chars{rand(0, $chars_length)};
			// Generate random string
			for ($i = 1; $i < $length; $i = strlen($string)) {
				// Grab a random character from our list
				$r = $chars{rand(0, $chars_length)};
				$string .=  $r;
			}
			// Return the string
			return $string;
		}
		
		/** ====================================================================================================================================================
		* From a string, create an simple identifier
		* 
		* @return string
		*/
		static public function create_identifier($text) {		
			// Pas d'espace
			$n = str_replace(" ", "_", strip_tags($text));
			// L'identifiant ne doit contenir que des caracteres alpha-numérique et des underscores...
			$n = preg_replace("#[^A-Za-z0-9_]#", "", $n);
			// l'identifiant doit commencer par un caractere "alpha"
			$n = preg_replace("#^[^A-Za-z]*?([A-Za-z])#", "$1", $n);
			return $n;
		}
		
		/** ====================================================================================================================================================
		* Utils pour convertir en KB, MB, GB
		* 
		* @return string
		*/
		
		static function byteSize($bytes)  {
			$size = $bytes / 1024;
			if($size < 1024) {
				$size = number_format($size, 2);
				$size .= ' KB';
			} else {
				if($size / 1024 < 1024)  {
					$size = number_format($size / 1024, 2);
					$size .= ' MB';
				} else if ($size / 1024 / 1024 < 1024)  {
					$size = number_format($size / 1024 / 1024, 2);
					$size .= ' GB';
				} 
			}
			return $size;
		} 	
		
		
 
 
		/** ====================================================================================================================================================
		* Trier un tableau selon la n_ieme colonne
		* 
		* @return string
		*/

		function multicolumn_sort($data,$num){
 			$col_uniq = array() ; 
 			
			// List As Columns
  			foreach ($data as $row) {
    			$ligne = $row[$num] ;
    			$cnt = 0 ; 
    			foreach ($row as $c) {
    				if ($cnt!=$num) {
    					$ligne .= ",".$row[$cnt] ; 
    				}
    				$cnt ++ ; 

    			}
    			$col_uniq[] = $ligne ; 

    		}
    		
    		// We sort
    		asort($col_uniq) ; 
    		$result = array() ; 
    		foreach ($col_uniq as $l) {
    			$result[] = explode(",",$l) ; 
    		}
    		
  			return $result;
		} 
	} 
}

?>