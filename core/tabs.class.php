<?php
/*
Core SedLex Plugin
VersionInclude : 2.1
*/ 

/** ====================================================================================================================================================
* Admin table class 
* 
* @return void
*/
if (!class_exists("adminTabs")) {
	class adminTabs  {
		var $title ; 
		var $content ; 
		
		function adminTabs() {	
			$this->title = array() ; 
			$this->content = array() ; 
		}
		
		function add_tab($title, $content) {
			$this->title[] = $title ; 
			$this->content[] = $content ; 
		}
		
		function flush() {
			ob_start() ; 
			$rnd = rand(1, 100000) ; 
?>
			<script>jQuery(function($){ $('#tabs<?php echo $rnd ; ?>').tabs(); }) ; </script>		
			<div id="tabs<?php echo $rnd ; ?>">
				<ul class="hide-if-no-js">
<?php
			for ($i=0 ; $i<count($this->title) ; $i++) {
?>					<li><a href="#tab-<? echo md5($this->title[$i]) ?>"><? echo $this->title[$i] ?></a></li>		
<?php
			}
?>				</ul>
<?php
			for ($i=0 ; $i<count($this->title) ; $i++) {
?>				<div id="tab-<? echo md5($this->title[$i]) ?>" class="blc-section">
					<?php echo $this->content[$i] ; ?>
				</div>
<?php
				
			}
?>
			</div>
<?php		return ob_get_clean() ; 
		}
	}
}
?>