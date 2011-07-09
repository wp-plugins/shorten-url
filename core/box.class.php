<?php
/*
Core SedLex Plugin
VersionInclude : 2.1
*/ 
/** ====================================================================================================================================================
* Configuration panel - Parameters Class
* 
* @return void
*/
if (!class_exists("boxAdmin")) {
	class boxAdmin {
		
		var $title ; 
		var $content ; 
		
		/** ====================================================================================================================================================
		* Constructor
		* 
		* @return void
		*/
		function boxAdmin($title, $content) {
			$this->title = $title ; 
			$this->content = $content ; 
		}
		
		
		/** ====================================================================================================================================================
		* print the output
		* 
		* @return void
		*/
		function flush()  {
			ob_start();
			?>
			<div class="metabox-holder" style="width: 100%">
				<div class="meta-box-sortables">
					<div class="postbox">
						<h3 class="hndle"><span><?php echo $this->title ; ?></span></h3>
						<div class="inside" style="padding: 5px 10px 5px 20px;">
							<?php 
								echo $this->content ; 
							?>
						</div>
					</div>
					
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}

?>