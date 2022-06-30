<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
require_once(__CA_APP_DIR__ . '/plugins/CSITools/business/CSIPrint/CSIPrint.php');
require_once(__CA_APP_DIR__ . '/plugins/CSITools/controllers/CSIArchiveManagerController.php');

class CSIPrintController extends ActionController {
	protected $o_db;
    protected $user;
    protected $user_groups;
    protected $is_readonly;
    protected $user_enabled;
    protected $opo_config;

	public function __construct(&$po_request, &$po_response, $pa_view_paths = NULL) {
            parent::__construct($po_request, $po_response, $pa_view_paths);
            $this->o_db = new Db("", NULL, false);
            $this->user = $po_request->user;

            $this->user_groups = $this->user->getUserGroups();
            $this->is_readonly = false;
            $this->user_enabled = $this->user->canDoAction('can_use_csi_archive_manager');

            if (!empty($this->user_groups)) {
                foreach($this->user_groups as $group_id => $group){
                    if($group['code'] == READONLY_GROUP_CODE){
                        $this->is_readonly = true;
                        break;
                    }
                }
            }

            $this->user_allowed_to_write = ($this->user_enabled && !$this->is_readonly);

            $this->opo_config = Configuration::load(__CA_APP_DIR__ . '/plugins/CSITools/conf/csiprint.conf');

    }
	
	public function Print(){

        if(!$this->user_allowed_to_write){ return; }

        $displayCode = $this->request->getParameter('display_code', pString);
        $ids = $this->request->getParameter('object_ids', pString);
        $id = explode(',', $ids)[0];
        $metadataView = $this->_getBundleDisplayIdByCode($displayCode);

        if(!empty($id)){

            $printer = new CSIPrint($this->request, $this->opo_config);
            $file = $printer->print($id, $metadataView);

            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename='. preg_replace('/[^a-zA-Z0-9\-\._]/','', basename($file)));
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($file));

            readfile($file);
            unlink($file);
        }
    }


    public function ConfigurationInterface(){
        if(!$this->user_allowed_to_write){ return; }

        $params = $_POST['ids'];
        $objectId = (isset( $_POST['ids'])) ?  $_POST['ids'][0] : null;

        if(!empty($objectId)){

            $displayCodes = array('stampaInventario_dat', 'stampa_gen');
            $displays = array();

            foreach($displayCodes as $displayCode){

                if($displayCode != 'stampaInventario_dat' || CSIArchiveManagerController::checkScopeByIDs(array($objectId), array('A'))){

                    $displayId = $this->_getBundleDisplayIdByCode($displayCode);
                    $t_display = new ca_bundle_displays();
                    $t_display->load($displayId);
                    $displayName = $t_display->getPreferredLabels(null, false)[$displayId][1][0]['name'];
                    $displays[$displayCode] = $displayName;
                }
            }

            
            $this->view->setVar('displays', $displays);

            $this->render('configuration_csiprint_html.php');
        }
    }

    private function _getBundleDisplayIdByCode($code){
        $qr_result = $this
                ->o_db
                ->query("select display_id from ca_bundle_displays where display_code = '". $code ."'");

        $qr_result->nextRow();
        $displayId = $qr_result->get('display_id');

        return $displayId;
    }




    public static function getStatusIcon($object){
        return null;
    }
}
?>