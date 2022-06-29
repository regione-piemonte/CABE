<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
class CSIAssignmentController extends ActionController {
	protected $o_db;
    protected $user;
    protected $user_groups;
    protected $is_readonly;
    protected $user_enabled;
    protected $user_allowed_to_write;
    protected $assignmentElementId;

	public function __construct(&$po_request, &$po_response, $pa_view_paths = NULL) {
            parent::__construct($po_request, $po_response, $pa_view_paths);
            $this->o_db = new Db("", NULL, false);
            $this->user = $po_request->user;

            $this->user_groups = $this->user->getUserGroups();
            $this->is_readonly = false;
            $this->user_enabled = $this->user->canDoAction('can_use_csi_archive_manager');

            if (!empty($this->user_groups)) {
                foreach($this->user_groups as $group){
                    if($group['code'] == READONLY_GROUP_CODE){
                        $this->is_readonly = true;
                        break;
                    }
                }
            }

            $this->user_allowed_to_write = ($this->user_enabled && !$this->is_readonly);

    }
	
	public function Assign(){

        if(!$this->user_allowed_to_write){ return; }

        header('Content-Type: application/json');
        $response = array('message' => '', 'status' => false, 'refresh' => false);

        $params = (isset($_POST['params'])) ? json_decode($_POST['params']) : null;
        $prefix = (isset($_POST['prefix'])) ? $_POST['prefix'] : null;
        $objectId = (!empty($params)) ? $params->ids[0] : null;

        if(!empty($objectId)){

            $node = new ca_objects($objectId);
            $childrendIDs = $node->getHierarchyChildren(null, array('idsOnly' => true, 'sort' => 'ca_objects.rank'));

            if(!empty($childrendIDs)) {
                $this->assignmentElementId = ca_metadata_elements::getElementID('num_def_numero');
                $counter = $this->_assignRecursive($objectId, $prefix, $this->assignmentElementId);
                $response = array('message' => 'Segnatura definitiva assegnata correttamente a ' . $counter . ' schede', 'status' => true, 'refresh' => true);
            }else{
                $response = array('message' => 'la scheda selezionata non contiene figli', 'status' => false, 'refresh' => false);
            }
            
        }else{
            $response = array('message' => 'selezione non valida', 'status' => false, 'refresh' => false);
        }


        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }

    private function _assignRecursive($nodeId, $prefix, $elementId, $counter = 0){

        $node = new ca_objects($nodeId);
        $childrendIDs = $node->getHierarchyChildren(null, array('idsOnly' => true, 'sort' => 'ca_objects.rank'));

        if(empty($childrendIDs)){
            $counter++;
            $assignmentValue = $prefix . str_pad($counter, 5, "0", STR_PAD_LEFT);
            $this->_updateAssignment($node, $elementId, $assignmentValue);
        }else{
            foreach ($childrendIDs as $childId) {
                $counter = $this->_assignRecursive($childId, $prefix, $elementId, $counter);
            }
        }

        return $counter;
    }

    public function ConfigurationInterface(){
        if(!$this->user_allowed_to_write){ return; }

        $params = $_POST['ids'];
        $objectId = (isset( $_POST['ids'])) ?  $_POST['ids'][0] : null;

        if(!empty($objectId)){

            $object = new ca_objects($objectId);
            $childrenIDs = $object->getHierarchyChildren(null, array('idsOnly' => true));

            $singleLevel = true;

            foreach($childrenIDs as $childId){
                $child = new ca_objects($childId);
                $childrenOfChild = $child->getHierarchyChildren(null, array('idsOnly' => true));
                if(!empty($childrenOfChild)){
                    $singleLevel = false;
                    break;
                }
            }

            $this->view->setVar('singleLevel', $singleLevel);

            $this->render('configuration_csiassignment_html.php');
        }
    }

    private function _updateAssignment($object, $elementId, $assignment){

        $values = array('locale_id' => '');
        $values[$elementId] = $assignment;

        $object->setMode(ACCESS_WRITE);
        $object->setAsChanged('_ca_attribute_' . $elementId);
        $object->replaceAttribute($values, $elementId);
        $object->update(array('dontSetHierarchicalIndexing' => true));
    }


    public static function getStatusIcon($object){
        return null;
    }
}
?>