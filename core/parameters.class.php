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
if (!class_exists("parametersSedLex")) {
	class parametersSedLex {
		
		var $output ; 
		var $maj ; 
		var $modified ; 
		var $warning ; 
		var $error ; 
		var $hastobeclosed ;
		var $obj ; 
		
		/** ====================================================================================================================================================
		* Constructor
		* 
		* @return void
		*/
		function parametersSedLex($obj, $tab) {
			$this->output = "<div class=wrap><form method='post' action='".$_SERVER["REQUEST_URI"]."#".$tab."'>" ; 
			$this->maj = false ; 
			$this->modified = false ;
			$this->error = false ; 
			$this->warning = false ; 
			$this->hastobeclosed = false ; 
			$this->obj = $obj ; 
		}
		/** ====================================================================================================================================================
		* add title in the screen
		* 
		* @return void
		*/
		function add_title($title)  {
			if ($this->hastobeclosed) {
				$this->output .= "</table>" ; 
				$this->hastobeclosed = false ; 
			}
			$this->output .= "<hr/><p>".$title."</p>" ; 
		}
		
		/** ====================================================================================================================================================
		* print the output
		* 
		* @return void
		*/
		function flush()  {
			if ($this->hastobeclosed) {
				$this->output .= "</table>" ; 
				$this->hastobeclosed = false ; 
			}
			ob_start();
			?>
					<hr/>
					<div class="submit">
						<input type="submit" name="submitOptions" class='button-primary validButton' value="<?php echo __('Update', $this->pluginID) ?>" />
					</div>
				</form>
			</div>
			<?php

			
			// If the parameter have been modified, we say it !
			
			if (($this->error) && ($this->maj)) {
			?>
			<div class="error fade">
				<p><?php echo __('Some parameters have not been updated due to errors (see below) !', $this->pluginID) ?></p>
			</div>
			<?php
			} else if (($this->warning) && ($this->maj)) {
			?>
			<div class="updated  fade">
				<p><?php echo __('Parameters have been updated (but with some warnings) !', $this->pluginID) ?></p>
			</div>
			<?php
			} else if (($this->modified) && ($this->maj)) {
			?>
			<div class="updated  fade">
				<p><?php echo __('Parameters have been updated successfully !', $this->pluginID) ?></p>
			</div>
			<?php
			} 
			
			$this->output .= ob_get_contents();
			ob_end_clean();
			echo $this->output ; 
		}

		/** ====================================================================================================================================================
		* add parameter in the screen
		* 
		* @return void
		*/
		function add_comment($param)  {
			ob_start();
			?>
				<tr valign="top">
					<th scope="row"></th>
					<td><p style="color:#666666"><?php echo $param ; ?><p></td>
				</tr>
			<?php	
			$this->output .= ob_get_contents();
			ob_end_clean();
		}
		
		/** ====================================================================================================================================================
		* add parameter in the screen
		* 
		* @return void
		*/
		function add_param($param, $name, $forbid="", $allow="")  {
			global $_POST ; 
			ob_start();
			if (!$this->hastobeclosed) {
				?>
				<table class="form-table">
				<?php
				$this->hastobeclosed = true ; 
			}
			
			// On trouve le type du parametre
			//---------------------------------------
			$type = "string" ; 
			if (is_bool($this->obj->get_default_option($param))) $type = "boolean" ; 
			if (is_int($this->obj->get_default_option($param))) $type = "int" ; 
			if (is_array($this->obj->get_default_option($param))) $type = "list" ; 
			// C'est un text si dans le texte par defaut, il y a une Žtoile
			if (is_string($this->obj->get_default_option($param))) {
				if (str_replace("*","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "text" ; 
			}
			
			// On met a jour la variable
			//---------------------------------------
			$problem_e = "" ; 
			$problem_w = "" ; 
			if (isset($_POST['submitOptions'])) {
				$this->maj = true ; 
				// Est ce que c'est un boolean
				if ($type=="boolean") {
					if ($_POST[$param]) {
						$this->obj->set_param($param, true) ; 
						$this->modified = true ; 
					} else {
						$this->obj->set_param($param, false) ; 
						$this->modified = true ; 
					}
				} 
				
				// Est ce que c'est bien un int
				if ($type=="int") {
					if (Utils::is_really_int($_POST[$param])) {
						$this->obj->set_param($param, (int)$_POST[$param]) ; 
						$this->modified = true ; 
					} else {
						$problem_e .= "<p>Error: the submitted value is not an integer and thus, the parameter has not been updated!</p>" ; 
						$this->error = true ; 
					}
				} 
				
				// Est ce que c'est bien un string
				if (($type=="string")||($type=="text")) {
					$tmp = $_POST[$param] ; 
					if ($forbid!="") {
						$tmp = preg_replace($forbid, '', $_POST[$param]) ; 
					} 

					if ($tmp!=$_POST[$param]) {
						$problem_w .= "<p>Warning: some characters have been removed because they are not allowed here (".$forbid.")!</p>" ; 
						$this->warning = true ; 
					}
					
					if (($allow!="")&&(!preg_match($allow, $_POST[$param]))) {
						$problem_e .= "<p>Error: the submitted string does not match the constrains (".$allow.")!</p>" ; 
						$this->error = true ; 
					} else {
						$this->obj->set_param($param, stripslashes($tmp)) ; 
						$this->modified = true ; 
					}
					$_POST[$param] = $tmp  ; 
				} 
				
				// Est ce que c'est bien une liste
				if ($type=="list") {
					$selected = $_POST[$param] ; 
					$array = $this->obj->get_default_option($param) ; 
					for ($i=0 ; $i<count($array) ; $i++) {
						$array[$i] = str_replace("*","",$array[$i]) ;
						// On met une Žtoile si c'est celui qui est selectionnŽ par dŽfaut
						if ($selected == Utils::create_identifier($array[$i])) {
							$array[$i] = '*'.$array[$i] ; 
						}
					}
					$array2 = $this->obj->get_param($param) ; 
					if ($array2 != $array) {
						$this->obj->set_param($param, $array) ; 
						$this->modified = true ; 
					}
				} 
			}
			
			// On construit le tableau
			//---------------------------------------
			if ($type=="boolean") {
			?>
				<tr valign="top">
					<th scope="row"><label for='<?php echo $param ; ?>'><?php echo $name ; ?></label></th>
					<td><input name='<?php echo $param ; ?>' id='<?php echo $param ; ?>' type='checkbox' <?php if ($this->obj->get_param($param)) {echo "checked" ; } ?> >	
					</td>
				</tr>
			<?php
			}
			
			if ($type=="int") {
			?>
				<tr valign="top">
					<th scope="row"><label for='<?php echo $param ; ?>'><?php echo $name ; ?></label></th>
					<td><input name='<?php echo $param ; ?>' id='<?php echo $param ; ?>' type='text' value='<?php echo $this->obj->get_param($param); ?>' size='<?php echo (strlen($this->obj->get_param($param).'')+1) ; ?>'> (integer) 
					</td>
				</tr>
				<?php
				if ($problem_e!="") {	
				?>
				<tr valign="top">
					<td colspan="2">
						<div class="errorSedLex"><?php echo $problem_e ; ?></div>
					</td>
				</tr>
				<?php
				}
				if ($problem_w!="") {	
				?>
				<tr valign="top">
					<td colspan="2">
						<div class="warningSedLex"><?php echo $problem_w ; ?></div>
					</td>
				</tr>
				<?php
				}
				?>	
			<?php
			}
			
			if ($type=="string") {
			?>
				<tr valign="top">
					<th scope="row"><label for='<?php echo $param ; ?>'><?php echo $name ; ?></label></th>
					<td><input name='<?php echo $param ; ?>' id='<?php echo $param ; ?>' type='text' value='<?php echo htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8"); ?>' size='<?php echo (strlen($this->obj->get_param($param).'')+1) ; ?>'>
					</td>
				</tr>
				<?php
				if ($problem_e!="") {	
				?>
				<tr valign="top">
					<td colspan="2">
						<div class="errorSedLex"><?php echo $problem_e ; ?></div>
					</td>
				</tr>
				<?php
				}
				if ($problem_w!="") {	
				?>
				<tr valign="top">
					<td colspan="2">
						<div class="warningSedLex"><?php echo $problem_w ; ?></div>
					</td>
				</tr>
				<?php
				}
				?>	
			<?php
			}
			
			if ($type=="text") {
				$num = count(explode("\n", $this->obj->get_param($param))) + 1 ; 
			?>
				<tr valign="top">
					<th scope="row"><label for='<?php echo $param ; ?>'><?php echo $name ; ?></label></th>
					<td>
					<textarea name='<?php echo $param ; ?>' id='<?php echo $param ; ?>' rows="<?php echo $num ; ?>" cols="70"><?php echo htmlentities(str_replace("*","",$this->obj->get_param($param)), ENT_QUOTES, "UTF-8"); ?></textarea>
					</td>
				</tr>
				<?php
				if ($problem_e!="") {	
				?>
				<tr valign="top">
					<td colspan="2">
						<div class="errorSedLex"><?php echo $problem_e ; ?></div>
					</td>
				</tr>
				<?php
				}
				if ($problem_w!="") {	
				?>
				<tr valign="top">
					<td colspan="2">
						<div class="warningSedLex"><?php echo $problem_w ; ?></div>
					</td>
				</tr>
				<?php
				}
				?>	
			<?php
			}
			
			if ($type=="list") {
			?>
				<tr valign="top">
					<th scope="row"><label for='<?php echo $param ; ?>'><?php echo $name ; ?></label></th>
					<td>
						<select name='<?php echo $param ; ?>' id='<?php echo $param ; ?>'>
					<?php 
					$array = $this->obj->get_param($param);
					foreach ($array as $a) {
						$selected = "" ; 
						if (str_replace("*", "", $a) != $a) {
							$selected = "selected" ; 
						}
					?>
							<option value="<?php echo Utils::create_identifier($a) ; ?>" <?php echo $selected ; ?>><?php echo str_replace("*", "", $a) ; ?></option>
					<?php
					}
					?>
						</select>
					</td>
				</tr>
			<?php
			}

			$this->output .= ob_get_contents();
			ob_end_clean();
		}
	}
}

?>