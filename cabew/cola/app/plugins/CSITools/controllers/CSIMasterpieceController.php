<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
define('CSI_MASTERPIECE_YES', 2023);
define('CSI_MASTERPIECE_NO', 2024);

class CSIMasterpieceController extends ActionController {
	protected $o_db;
    protected $user;
    protected $user_groups;
    protected $is_readonly;
    protected $user_enabled;
    protected $user_allowed_to_write;

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
    }
	
	public function Set(){
        header('Content-Type: application/json');
        if(!$this->user_allowed_to_write){ return; }

        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : array();

        if(!empty($ids)){
        	$response = $this->updateValue($ids, CSI_MASTERPIECE_YES);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }

    public function Unset(){
        header('Content-Type: application/json');
        if(!$this->user_allowed_to_write){ return; }
        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : array();

        if(!empty($ids)){
        	$response = $this->updateValue($ids, CSI_MASTERPIECE_NO);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }


    private function updateValue($ids, $value){
    	$qr_result = $this->o_db->query("select * from ca_metadata_elements where element_code  ='masterpiece'");
        if ($qr_result->nextRow()) {
            $element_id = $qr_result->get('element_id');

            foreach ($ids as $object_id) {
                $object = new ca_objects($object_id);
                if($object->checkACLAccessForUser($this->user) > __CA_BUNDLE_ACCESS_READONLY__){

                    // cancellazione
                    $qDeleteValues = "delete  from ca_attribute_values
                        where element_id = ". $element_id ."
                        and attribute_id = (
                        select attribute_id from
                        ca_attributes
                        where element_id = " . $element_id ."
                        and table_num = 57
                        and row_id = " . $object_id .")";

                    $qr_result = $this->o_db->query($qDeleteValues);

                    $qDeleteAttributes = "delete from ca_attributes where element_id = " . $element_id . " and table_num = 57 and row_id = " . $object_id .";";
                    $qr_result = $this->o_db->query($qDeleteAttributes);

                    // inserimento
                    $qInsertAttributes = "insert into ca_attributes (element_id, locale_id, table_num, row_id) values (" . $element_id . ", 1, 57, ". $object_id .");";


                    $qr_result = $this->o_db->query($qInsertAttributes);

                    $qSelectAttributeId = "select * from ca_attributes where element_id = " . $element_id ." and table_num = 57 and row_id = " . $object_id .";";

                    $qr_result = $this->o_db->query($qSelectAttributeId);

                    if ($qr_result->nextRow()) {
                        $attribute_id = $qr_result->get('attribute_id');

                        $qInsertValues = "insert into ca_attribute_values (attribute_id, element_id, value_longtext1, item_id, source_info) values (" . $attribute_id . ", " . $element_id .", ". $value .", ". $value .", '');";

                         $qr_result = $this->o_db->query($qInsertValues);

                         $response = array('message' => 'Aggiornamento effettuato', 'status' => true, 'refresh' => true);

                    }else{
                       $response = array('message' => 'error', 'status' => false, 'refresh' => false);
                    }
                }
            }

        }else{
            $response = array('message' => 'error', 'status' => false, 'refresh' => false);
        }

        return $response;
    }

    public static function getStatusIcon($object){
        return ($object->get('ca_objects.masterpiece') == CSI_MASTERPIECE_YES) ? 'fa fa-star' : null;
    }
}
?>