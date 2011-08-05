<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class helps you to zip files and directory.
* Please note that this function extended the ZipArchive class in PHP <a href="http://php.net/manual/fr/class.ziparchive.php">http://php.net/manual/fr/class.ziparchive.php</a>
*
* For instance, you can create an archive by doing that
* <code>$zip = new Zip() ; <br/> chdir("/root/of/the/archive/") ; <br/> $zip->open("/storeDirectory/theNameOfTheArchive.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) ; <br/> $zip->addDir($folder_name) ; <br/> $zip->close() ; </code>
*/

if (!class_exists("Zip")) {
	class Zip extends ZipArchive {
	   
		/** ====================================================================================================================================================
		* Add a complete directory in the archive (with files and subfolders)
		* 
		* @param string $path the path of the directory to add 
		* @return void 
		*/
		public function addDir($path) {
			$this->addEmptyDir($path);
			$nodes = glob($path . '/*');
			foreach ($nodes as $node) {
				if (is_dir($node)) {
					$this->addDir($node);
				} else if (is_file($node))  {
					$this->addFile($node);
				}
			}
		}
	} 
}

?>