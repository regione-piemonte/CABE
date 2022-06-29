<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
 	$t_object 			= $this->getVar('t_subject');
	$vn_object_id 		= $this->getVar('subject_id');
	$vn_above_id 		= $this->getVar('above_id');
	$vn_after_id 		= $this->getVar('after_id');

	$vb_can_edit	 	= $t_object->isSaveable($this->request);
	$vb_can_delete		= $t_object->isDeletable($this->request);

	$vs_rel_table		= $this->getVar('rel_table');
	$vn_rel_type_id		= $this->getVar('rel_type_id');
	$vn_rel_id			= $this->getVar('rel_id');
	
	$vn_collection_id	= $this->request->getParameter('collection_id', pInteger);
	
	if ($vb_can_edit) {
		$va_cancel_parameters = ($vn_object_id ? array('object_id' => $vn_object_id, 'collection_id' => $vn_collection_id) : array('type_id' => $t_object->getTypeID(), 'collection_id' => $vn_collection_id));
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'ObjectEditorForm').' '.
			($this->getVar('show_save_and_return') ? caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save and return"), 'ObjectEditorForm', array('isSaveAndReturn' => true)) : '').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'editor/objects', 'ObjectEditor', 'Edit/'.$this->request->getActionExtra(), $va_cancel_parameters),
			'', 
			((intval($vn_object_id) > 0) && $vb_can_delete) ? caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), 'form-button deleteButton', 'editor/objects', 'ObjectEditor', 'Delete/'.$this->request->getActionExtra(), array('object_id' => $vn_object_id)) : ''
		);
	}
?>
	<div class="sectionBox">
<?php
//LM 23/03/18S PARTE RELATIVA AL MENU' LATERALE - FOGLIOLINA
// $this->setVar('request', $this->opo_request);
// $this->setVar('user', $this->opo_request->user);
			AssetLoadManager::register('treejs');
			$this->setVar('user', $this->opo_request->user);
			$this->setVar('current_id', $vn_object_id);
			print $this->render("widget_object_hierarchy_html.php", true);
//LM 23/03/18E PARTE RELATIVA AL MENU' LATERALE - FOGLIOLINA
					
			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/object_id/'.$vn_object_id, 'ObjectEditorForm', null, 'POST', 'multipart/form-data');
		
			$va_bundle_list = array();
			$va_form_elements = $t_object->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'ObjectEditorForm',
									'forceHidden' => array('lot_id')
								), $va_bundle_list);
			
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) { print $vs_control_box; }
?>
			<input type='hidden' name='object_id' value='<?php print $vn_object_id; ?>'/>
			<input type='hidden' name='collection_id' value='<?php print $vn_collection_id; ?>'/>
			<input type='hidden' name='above_id' value='<?php print $vn_above_id; ?>'/>
			<input id='isSaveAndReturn' type='hidden' name='is_save_and_return' value='0'/>
			<input type='hidden' name='rel_table' value='<?php print $vs_rel_table; ?>'/>
			<input type='hidden' name='rel_type_id' value='<?php print $vn_rel_type_id; ?>'/>
			<input type='hidden' name='rel_id' value='<?php print $vn_rel_id; ?>'/>
			<input type='hidden' name='after_id' value='<?php print $vn_after_id; ?>'/>
<?php
			if($this->request->getParameter('rel', pInteger)) {
?>
				<input type='hidden' name='rel' value='1'/>
<?php
			}
?>
		</form>
	</div>
	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caSetupEditorScreenOverlays($this->request, $t_object, $va_bundle_list); ?>
<!-- LM 23/03/18S PARTE RELATIVA AL MENU' LATERALE - FOGLIOLINA -->
<!-- Archiui -->
<?php
	$container = array();
	$type_id = $t_object->get('type_id');
	$tsk = array(290 => 1752, 289 => 1753, 288 => 1754, 286 => 1755, 2565 => 2570, 287 => 1756 );
	foreach ($va_bundle_list as $screen) {
		if ($screen['bundle'] == 'ca_attribute_tsk') {
			$container = $screen;
			break;
		}
	}
?>
<!--<script type="text/javascript">
	jQuery(document).ready(function($) {
		var container = <?php echo json_encode($container) ?>;
		jQuery('.bundleLabel div#' + container['id'] + '_attribute_195').ready(function (e) {
			var options = jQuery('.bundleLabel div#' + container['id'] + '_attribute_195').find('.attributeListItem .formLabel select option');
			options.each(function(index, option) {
				if (option.value == '<?php echo $tsk[$type_id]; ?>') {
					jQuery(option).prop('selected', 'selected');
				}
			});

		});
	});
</script>-->
<!-- LM 23/03/18E PARTE RELATIVA AL MENU' LATERALE - FOGLIOLINA -->
