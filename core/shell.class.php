<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the execution of multiples shell command with ajax
*/
if (!class_exists("shellSL")) {
	class shellSL {
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @return shellSL the shellSL object
		*/
		function shellSL() {
			
		}
		
		/** ====================================================================================================================================================
		* Add a command line in the buffer of command
		* 
		* @param string $folder the path of the temp folder (by default /tmp)
		* @return boolean
		*/

		public function change_temp_folder($folder) {

		}

		/** ====================================================================================================================================================
		* Add a command line in the buffer of command
		* 
		* @return void
		*/

		public function add_command() {

		}


		/** ====================================================================================================================================================
		* Begin the execution of the buffer
		* 
		* @return void
		*/
		
		private function begin_exec() {
			
		}
		
		/** ====================================================================================================================================================
		* Continue the execution of the buffer
		* 
		* 
		* @return string the 
		*/
		
		private function continue_exec() {
			
		}
		
		/** ====================================================================================================================================================
		* Tell whether all the command of the buffer has been executed
		* 
		* @return boolean true if finished
		*/
		
		private function is_finished() {
			
		}
	}
}

?>