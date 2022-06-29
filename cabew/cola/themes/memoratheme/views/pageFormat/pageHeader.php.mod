<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/pageHeader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2017 Whirl-i-Gig
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
 
 	if(!($vs_window_title = trim(MetaTagManager::getWindowTitle()))) {
 		$va_breadcrumb = $this->getVar('nav')->getDestinationAsBreadCrumbTrail();
 		if (is_array($va_breadcrumb) && sizeof($va_breadcrumb)) {
 			$vs_window_title = array_pop($va_breadcrumb);
 		}
 	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=EDGE" />
	    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"/>

		<title><?php print $this->appconfig->get("window_title").($vs_window_title ? " : {$vs_window_title}" : ''); ?></title>

		<script type="text/javascript">window.caBasePath = '<?php print $this->request->getBaseUrlPath(); ?>';</script>
<?php
	print AssetLoadManager::getLoadHTML($this->request);
	print MetaTagManager::getHTML();
	
	if ($vs_local_css_url_path = $this->request->getUrlPathForThemeFile("css/local.css")) {
		print "<link rel='stylesheet' href='{$vs_local_css_url_path}' type='text/css' media='screen' />
";
	}
	
	//
	// Pull in JS and CSS for debug bar
	// 
	if(Debug::isEnabled()) {
		$o_debugbar_renderer = Debug::$bar->getJavascriptRenderer();
		$o_debugbar_renderer->setBaseUrl(__CA_URL_ROOT__.$o_debugbar_renderer->getBaseUrl());
		print $o_debugbar_renderer->renderHead();
	}
?>
		<script type="text/javascript">
			// initialise plugins
			jQuery(document).ready(function() {
				jQuery('ul.sf-menu').superfish(
					{
						delay: 350,
						speed: 150,
						disableHI: true,
						animation: { opacity: 'show' }
					}
				);
				
				jQuery('#caQuickSearchFormText').searchlight('<?php print caNavUrl($this->request, 'find', 'SearchObjects', 'lookup'); ?>', {showIcons: false, searchDelay: 100, minimumCharacters: 3, limitPerCategory: 3});
			

				/* CSI AF 21/09/18S
				mostro l'overlay per inibire click ripetuti quando di salva o duplica una scheda */
				 jQuery('.form-button .fa-check-circle-o').closest("a").not('#caInventaryButtom, #caSummaryDownloadOptionsFormExecuteButton').on('click', function(){
                        jQuery('#csi-overlay').css('display', 'block');
                });

                jQuery('#caDuplicateItemButton').on('click', function(){
                        jQuery('#csi-overlay').css('display', 'block');
                });
		
		if(jQuery('#ca_attribute_ObjectEditorForm_cron_new').length){
		
			jQuery('#ca_attribute_ObjectEditorForm_cron_new').parent().find('select').css('width', '248px');
			jQuery('._attribute_value_data_da').parent().find('input').css('width', '150px');
			jQuery('._attribute_value_data_a').parent().find('input').css('width', '150px');
			jQuery('._attribute_value_fraz_sec_da').parents('table').css('padding-top','50px');
			jQuery('._attribute_value_sec_a').parents('table').css('padding-bottom','50px');


			jQuery('#ca_attribute_ObjectEditorForm_cron_new').parent().after('<div id="csi-date-error" class="notification-error-box rounded"><ul class="notification-error-box"><li class="notification-error-box">Controlla la conformità dei campi data</li></ul></div>');

			csiSaveWarningBox = jQuery('#csi-date-error').hide();
				
			jQuery('.control-box-left-content').css('position', 'relative');
                        csiSaveDateOverlay = jQuery('<div class="csi-save-overlay" style="position:absolute; width:50%; height:100%; background:rgba(247,247,247,0.5);"></div>');
                        jQuery('.control-box-left-content').prepend(csiSaveDateOverlay);


                        if(jQuery('._attribute_value_fraz_sec_da').siblings("select").val()){
                                jQuery('._attribute_value_data_da').siblings("input").attr('disabled', 'disabled');
                                jQuery('._attribute_value_data_da').siblings("input").val('');
                                csiSaveWarningBox.show();
                        }

                        

                        jQuery('._attribute_value_fraz_sec_da').siblings("select").on('change', function(){
                                if(jQuery('._attribute_value_fraz_sec_da').siblings("select").val()){
                                        jQuery('._attribute_value_data_da').siblings("input").attr('disabled', 'disabled');
                                        jQuery('._attribute_value_data_da').siblings("input").val('');
                                        jQuery('.csi-save-overlay').css('display', 'none');
                                        csiSaveWarningBox.hide();
                                }else{
                                        jQuery('._attribute_value_data_da').siblings("input").removeAttr('disabled');
                                        jQuery('.csi-save-overlay').css('display', 'block');
                                        csiSaveWarningBox.show();
                                }
                        });
			fraz_sec_a = jQuery('._attribute_value_fraz_sec_a').parent().parent();
                        jQuery('._attribute_value_sec_a').parent().parent().parent().prepend(fraz_sec_a);

			data_da = jQuery('._attribute_value_data_da').parent().parent();
			jQuery('._attribute_value_fraz_sec_da').parent().parent().parent().find('td:last-child').remove();
                        jQuery('._attribute_value_fraz_sec_da').parent().parent().parent().append(data_da);
                }	

				/* CSI AF 21/09/18E */

				
			});
			
			// initialize CA Utils
			caUI.initUtils({unsavedChangesWarningMessage: '<?php _p('You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.'); ?>'});

		</script>
		<style type="text/css">
			.attributeListItem td {
				padding-right:10px;
			}

			

		</style>
		<!--[if lte IE 6]>
			<style type="text/css">
			#container {
			height: 100%;
			}
			</style>
			<![endif]-->
		<!-- super fish end menus -->
	</head>	
	<body>
		<!-- CSI AF 21/09/18S 
			Overlay di caricamento
		-->
		<div id="csi-overlay" style="position:fixed; z-index:999; background-color: rgba(0,0,0, 0.4); width: 100%; height: 100%;display:none">
			<div style="position:relative; text-align: center; width: 100%; top:50%; font-size: 2rem; color: white">Loading...</div>
		</div>
		<!-- CSI AF 21/09/18E -->
		<div align="center">