<?php 
	$plugin_path = $this->getVar('plugin_path');

	$object_edit_url = __CA_URL_ROOT__ . '/index.php/editor/objects/ObjectEditor/Edit/object_id/';
	$object_summary_url = __CA_URL_ROOT__ . '/index.php/editor/objects/ObjectEditor/Summary/object_id/';
?>

<link type = "text/css" rel = "stylesheet" href="<?php print $plugin_path ?>/resources/dist/libs/bootstrap/css/bootstrap-grids.min.css" />
<link type = "text/css" rel = "stylesheet" href="<?php print $plugin_path ?>/resources/style.css" />
<script src="<?php print $plugin_path ?>/resources/dist/libs/bootstrap-notify.min.js" type="text/javascript"></script>
<div id="CSIToolsConfigurationInterface"></div>
<div class="container-fluid" id="cam_tree_selection">
	<div class="row ">
		<div class="col-md-10">
			Selezionati: <span class="count">0</span>
		</div>
		<div class="col-md-2">
			<button type="button" class="deselect-all fa fa-ban"></button>
		</div>
	</div>
</div>
<div class="container-fluid" id="cam_tree_container">
	<div class="row">
		<div class="col-md-12">
			<div id="cam_tree"></div>
		</div>
	</div>
</div>
<script>
    const CSITools = {};
    CSITools.editUrl = '<?php print $object_edit_url; ?>';
    CSITools.summaryUrl = '<?php print $object_summary_url; ?>';
    CSITools.rootUrl = '<?php print __CA_URL_ROOT__; ?>';
    CSITools.pluginUrl = CSITools.rootUrl + '/service.php/CSITools/';
    CSITools.controllers = {};
</script>
<script src="<?php print $plugin_path ?>/js/CSITools.js"></script>