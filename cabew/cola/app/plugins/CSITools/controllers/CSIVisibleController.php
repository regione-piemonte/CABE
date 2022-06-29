<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0

class CSIVisibleController extends ActionController {
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
        	$response = $this->updateValue($ids, true);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }

    public function Unset(){
        header('Content-Type: application/json');
        if(!$this->user_allowed_to_write){ return; }
        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : array();

        if(!empty($ids)){
        	$response = $this->updateValue($ids, false);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }


    public function SetRecursive(){
        header('Content-Type: application/json');
        if(!$this->user_allowed_to_write){ return; }

        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : array();

        if(!empty($ids)){
            $response = $this->updateValue($ids, true, true);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }

    public function UnsetRecursive(){
        header('Content-Type: application/json');
        if(!$this->user_allowed_to_write){ return; }
        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : array();

        if(!empty($ids)){
            $response = $this->updateValue($ids, false, true);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }


    private function updateValue($ids, $value, $recursive = false){
    	
        $response = array('message' => '', 'status' => false, 'refresh' => false);

        foreach($ids as $id){
            $object = new ca_objects($id);
            if($object->checkACLAccessForUser($this->user) > __CA_BUNDLE_ACCESS_READONLY__){
                $object->set('access', $value);
                $object->setMode(ACCESS_WRITE);
                $object->update();
                $object->setMode(ACCESS_READ);
            }

            if($recursive){
                $childrendIDs = $object->getHierarchyChildren(null, array('idsOnly' => true, 'sort' => 'ca_objects.rank'));
                if(!empty($childrendIDs)){
                    $this->updateValue($childrendIDs, $value, $recursive);
                }
                
            }
        }

        $status = ($value) ? 'visibili' : 'invisibili';

        $response = array('message' => 'Le schede selezionate sono state impostate come: <br /><b>' . $status . '</b>' , 'status' => true, 'refresh' => true);

        return $response;
    }

    public static function getStatusIcon($object){
        return ($object->get('access')) ? 'fa fa-eye' : null;
    }
}
?>