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
if (!class_exists("adminTable")) {
	class adminTable  {
		var $nbCol ; 
		var $nbLigneAffich ; 
		var $nbLigneTotal ; 
		var $title ; 
		var $hasFooter ; 
		var $content ; 
		
		function adminTable($nbLigneTotal = 0) {	
			$this->title = array() ; 
			$this->nbLigneAffich = $nbLigneAffich ; 
			$this->nbLigneTotal = $nbLigneTotal ; 
			$this->hasFooter = true ; 
			$this->content = array() ; 
		}
		
		//-------------------------------------------------
		// Add the title
		//-------------------------------------------------
		function title($array) {
			$this->title = $array ; 
		}
		
		//-------------------------------------------------
		// Add the current num of the page
		//-------------------------------------------------
		function current_page() {
			if (isset($_GET['paged'])) {
				$page_cur = $_GET['paged'] ; 
			} else {
				$page_cur = 1 ; 
			}
			return $page_cur ; 
		}
		
		//-------------------------------------------------
		// remove the footer
		//-------------------------------------------------
		function removeFooter() {
			$this->hasFooter = false ; 
		}
		
		//-------------------------------------------------
		// Add a line in the table
		//-------------------------------------------------
		function add_line($array, $id) {
			$n = 1 ; 
			foreach ($array as $a) {
				$a->idLigne= $id ;
				$a->idCol = $n ; 
				$n++ ; 
			}
			$this->content[] = $array ; 
		}
		
		//-------------------------------------------------
		// Print the designed table
		//-------------------------------------------------
		function flush() {
			ob_start() ; 
			//
			// Est-ce que on affiche le raccourci pour se deplacer dans les entrees du tableau
			//
			if ($this->nbLigneTotal>count($this->content)) {
				$get = $_GET;
				
				$page_cur = $this->current_page() ; 
				
				$page_tot = ceil($this->nbLigneTotal/count($this->content)) ; 
			
				$page_inf = max(1,$page_cur-1) ; 
				$page_sup= min($page_tot,$page_cur+1) ; 
				
?>					<form id="posts-filter" action="<?php echo $_SERVER['PHP_SELF'] ;?>" method="get">
						<div class="tablenav top">
							<div class="tablenav-pages">
<?php
								// Variable cachee pour reconstruire completement l'URL de la page courante
								foreach ($get as $k => $v) {
?>								<input name="<?php echo $k;?>" value="<?php echo $v;?>" size="1" type="hidden"/>
<?php
								}
?>								<span class="displaying-num"><?php echo $this->nbLigneTotal ; ?> items</span>
								<a class="first-page<?php if ($page_cur == 1) {echo  ' disabled' ; } ?>" <?php if ($page_cur == 1) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the first page" href="<?php echo add_query_arg( 'paged', '1' );?>">&laquo;</a>
								<a class="prev-page<?php if ($page_cur == 1) {echo  ' disabled' ; } ?>" <?php if ($page_cur == 1) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the previous page" href="<?php echo add_query_arg( 'paged', $page_inf );?>">&lsaquo;</a>
								<span class="paging-input"><input class="current-page" title="Current page" name="paged" value="<?php echo $page_cur;?>" size="1" type="text"> of <span class="total-pages"><?php echo $page_tot;?></span></span>
								<a class="next-page<?php if ($page_cur == $page_tot) {echo  ' disabled' ; } ?>" <?php if ($page_cur == $page_tot) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the next page" href="<?php echo add_query_arg( 'paged', $page_sup );?>">&rsaquo;</a>
								<a class="last-page<?php if ($page_cur == $page_tot) {echo  ' disabled' ; } ?>" <?php if ($page_cur == $page_tot) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the last page" href="<?php echo add_query_arg( 'paged', $page_tot );?>">&raquo;</a>			
								<br class="clear">
							</div>
						</div>
					</form>
<?php
			}
			//
			// Affichage du debut du tableau
			//
?>					<table class="widefat fixed" cellspacing="0">
						<thead>
							<tr>
								<tr>
<?php
			foreach ($this->title as $name) {
?>									<th class="manage-column column-columnname" scope="col"><?php echo $name ; ?></th>
<?php
			}
?>								</tr>
							</tr>
						</thead>
<?php
			//
			// Affichage de la fin du tableau
			//
			if ($this->hasFooter) {			
?>						<tfoot>
							<tr>
								<tr>
<?php
				foreach ($this->title as $name) {
?>									<th class="manage-column column-columnname" scope="col"><?php echo $name ; ?></th>
<?php
				}
?>								</tr>
							</tr>
						</tfoot>
<?php			
			}
			//
			// Affichage des lignes
			//
?>						<tbody>
<?php
			$ligne = 0 ; 
			foreach ($this->content as $line) {
				$ligne++ ; 
				// on recupere le premier id de la ligne et on considere que c'est le meme partout
				$id = $line[0]->idLigne ; 
?>							<tr class="<?php if ($ligne%2==1) {echo  'alternate' ; } ?>" valign="top" id="ligne<?php echo $id ; ?>"> 
<?php
				foreach ($line as $cellule) {
					$cellule->flush() ; 
				}
?>							</tr> 
<?php

			}
?>						</tbody>
					</table>
<?php
			$return = ob_get_clean() ; 
			return $return ; 
		}
	} 
}

/** ====================================================================================================================================================
* Admin cell class 
* 
* @return void
*/
if (!class_exists("adminCell")) {
	class adminCell  {
		var $content ; 
		var $action ; 
		var $idLigne ;
		var $idCol ;
		
		function adminCell($content) {
			$this->content = $content ; 
			$this->action = array() ;
		}
		
		function add_action($name, $javascript_function) {
			$this->action[] = array($name, $javascript_function) ;
		}
		
		function flush() {
		
?>								<td class="column-columnname">
									<span id="cell_<?php echo $this->idLigne ?>_<?php echo $this->idCol ?>" ><?php  echo $this->content ?></span>
<?php
			if (! empty($this->action)) {
?>									<div class="row-actions">
<?php		
				$num = 0 ; 
				foreach ($this->action as $l) {
					$num ++ ;
?>										<span><a href="#" onclick="javascript: <?php echo $l[1] ;?>(<?php echo $this->idLigne ; ?>) ; return false ; " id="<?php echo $l[1] ;?>_<?php echo $this->idLigne ;?>"><?php echo $l[0] ;?></a><?php if ($num!=count($this->action)) { echo "|" ; }?></span>
<?php		
				}
?>									</div>
<?php
			}
?>								</td>
<?php
		}
	}
}

?>