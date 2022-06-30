<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
class CSICutAndPasteController extends ActionController {
	protected $o_db;
    protected $user;
    protected $user_groups;
    protected $is_readonly;
    protected $user_enabled;
    protected $user_allowed_to_write;

	public function __construct(&$po_request, &$po_response, $pa_view_paths = NULL) {
        parent::__construct($po_request, $po_response, $pa_view_paths);

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

    public function Paste(){

        if(!$this->user_allowed_to_write){ return; }

        header('Content-Type: application/json');
        $children = (isset($_POST['childrenIds'])) ? $_POST['childrenIds'] : null;
        $parentId = (isset($_POST['parentId'])) ? $_POST['parentId'] : null;

        if(!empty($children) && !empty($parentId)){

            $nodesStartingParentId = null;
            $nodesHaveDifferentParents = false;
            foreach($children as $node){
                $nodeInstance = new ca_objects($node['id']);
                $nodeParentId = $nodeInstance->get('parent_id');
                if(empty($nodesStartingParentId)){
                    $nodesStartingParentId = $nodeParentId;
                }else{
                    if($nodeParentId != $nodesStartingParentId){
                        $nodesHaveDifferentParents = true;
                        break;
                    }
                }
            }

            if($nodesHaveDifferentParents){
                $response = array('message' => 'Non è consentito spostare nodi da rami diffenti' , 'status' => false, 'refresh' => true);
            }else{

                $t_parent_instance = new ca_objects($parentId);

                foreach ($children as $node) {
                    $nodeId = $node['id'];
                    $t_instance = new ca_objects($nodeId);
                    $t_instance->setMode(ACCESS_WRITE);
                    $t_instance->set('parent_id', $parentId);
                    $t_instance->set('rank', null);

                    $t_instance->update(array('dontSetHierarchicalIndexing' => true));
                }

                $t_parent_instance->rebuildHierarchicalIndex();

                
                if ($t_instance->numErrors()) {
                    $message = join("; ", $t_instance->getErrors());
                    $response = array('message' => $message , 'status' => false, 'refresh' => true);
                }else{
                    $response = array('message' => 'Schede spostate correttamente' , 'status' => true, 'refresh' => true);
                }
            }
        }else{
            $response = array('message' => 'Operazione non implementata' , 'status' => false, 'refresh' => true);
        }

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }



    public static function getStatusIcon($object){
        return null;
    }
}
?>