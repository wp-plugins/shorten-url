<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 
/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enable the creation of form to manage the parameter of your plugin 
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
		* Constructor of the object
		* 
		* @see  pluginSedLex::get_param
		* @see  pluginSedLex::set_param
		* @param class $obj a reference to the object containing the parameter (usually, you need to provide "$this")
		* @param string $tab if you want to activate a tabulation after the submission of the form
		* @return parametersSedLex the form class to manage parameter/options of your plugin
		*/
		
		function parametersSedLex($obj, $tab="") {
			$this->output = "<div class='wrap parameters'><form method='post' action='".$_SERVER["REQUEST_URI"]."#".$tab."'>\n" ; 
			$this->maj = false ; 
			$this->modified = false ;
			$this->error = false ; 
			$this->warning = false ; 
			$this->hastobeclosed = false ; 
			$this->obj = $obj ; 
		}
		
		/** ====================================================================================================================================================
		* Add title in the form
		* 
		* @param string $title the title to add
		* @return void
		*/
		function add_title($title)  {
			if ($this->hastobeclosed) {
				$this->output .= "</table>" ; 
				$this->hastobeclosed = false ; 
			}
			$this->output .= "<hr/>\n<h4>".$title."</h4>\n" ; 
		}

		/** ====================================================================================================================================================
		* Add a comment in the form
		* 
		* @param string $comment the comment to add 
		* @return void
		*/
		function add_comment($comment)  {
			ob_start();
			?>
				<tr valign="top">
					<th scope="row"></th>
					<td><p class='comments'><?php echo $comment ; ?><p></td>
				</tr>
			<?php	
			$this->output .= ob_get_contents();
			ob_end_clean();
		}
		

		/** ====================================================================================================================================================
		* Add a textarea, input, checkbox, etc. in the form to enable the modification of parameter of the plugin
		* 	
		* Please note that the default value of the parameter (defined in the  <code>get_default_option</code> function) will define the type of input form. If the default  value is a: <br/>&nbsp; &nbsp; &nbsp; - string, the input form will be an input text <br/>&nbsp; &nbsp; &nbsp; - integer, the input form will be an input text accepting only integer <br/>&nbsp; &nbsp; &nbsp; - string beggining with a '*', the input form will be a textarea <br/>&nbsp; &nbsp; &nbsp; - boolean, the input form will be a checkbox 
		*
		* @param string $param the name of the parameter/option as defined in your plugin and especially in the <code>get_default_option</code> of your plugin
		* @param string $name the displayed name of the parameter in the form
		* @param string $forbid regexp which will delete some characters in the submitted string (only a warning is raised) : For instance <code>$forbid = "/[^a-zA-Z0-9]/"</code> will remove all the non alphanumeric value
		* @param string $allow regexp which will verify that the submitted string will respect this rexexp, if not, the submitted value is not saved  and an erreor is raised : For instance, <code>$allow = "/^[a-zA-Z]/"</code> require that the submitted string begin with a nalpha character
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
			// C'est un text si dans le texte par defaut, il y a une etoile
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
						$problem_e .= "<p>".__('Error: the submitted value is not an integer and thus, the parameter has not been updated!', 'SL_framework')."</p>\n" ; 
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
						$problem_w .= "<p>".__('Warning: some characters have been removed because they are not allowed here', 'SL_framework')." (".$forbid.")!</p>\n" ; 
						$this->warning = true ; 
					}
					
					if (($allow!="")&&(!preg_match($allow, $_POST[$param]))) {
						$problem_e .= "<p>".__('Error: the submitted string does not match the constrains', 'SL_framework')." (".$allow.")!</p>\n" ; 
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
						// On met une Žtoile si c'est celui qui est selectionne par defaut
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
		
		/** ====================================================================================================================================================
		* Print the form with parameters
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
						<input type="submit" name="submitOptions" class='button-primary validButton' value="<?php echo __('Update', 'SL_framework') ?>" />
					</div>
				</form>
			</div>
			<?php

			
			// If the parameter have been modified, we say it !
			
			if (($this->error) && ($this->maj)) {
			?>
			<div class="error fade">
				<p><?php echo __('Some parameters have not been updated due to errors (see below) !', 'SL_framework') ?></p>
			</div>
			<?php
			} else if (($this->warning) && ($this->maj)) {
			?>
			<div class="updated  fade">
				<p><?php echo __('Parameters have been updated (but with some warnings) !', 'SL_framework') ?></p>
			</div>
			<?php
			} else if (($this->modified) && ($this->maj)) {
			?>
			<div class="updated  fade">
				<p><?php echo __('Parameters have been updated successfully !', 'SL_framework') ?></p>
			</div>
			<?php
			} 
			
			$this->output .= ob_get_contents();
			ob_end_clean();
			echo $this->output ; 
		}
	}
}

?>