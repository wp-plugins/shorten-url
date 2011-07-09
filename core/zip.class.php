<?php
/*
Core SedLex Plugin
VersionInclude : 2.1
*/ 

/** ====================================================================================================================================================
* Zip class 
* 
* @return void
*/
if (!class_exists("Zip")) {
	class Zip extends ZipArchive {
	   
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