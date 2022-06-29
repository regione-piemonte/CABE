<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
class CSISortController extends ActionController {
	protected $o_db;
    protected $user;
    protected $user_groups;
    protected $is_readonly;
    protected $user_enabled;
    protected $user_allowed_to_write;
    protected $sorting_query;

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

            $this->sorting_query = 'select t1.object_id, GROUP_CONCAT(t1.name SEPARATOR \'; \') as titolo, t1.idno,t1.parent_id, t1.tipo_scheda, t1.rank,t1.deleted, cron.data_cron from (select o.object_id, l.name, o.idno, o.parent_id, il.name_singular as tipo_scheda, o.rank, o.deleted from ca_objects o, ca_object_labels l, ca_list_items i, ca_list_item_labels il where o.object_id = l.object_id and o.type_id = i.item_id and i.item_id = il.item_id and o.parent_id = {OBJECT_ID} and l.is_preferred=1 and o.deleted=0) as t1 left join (select a.row_id, v.value_decimal1 as data_cron from ca_attributes a, ca_attribute_values v, ca_metadata_elements m where a.attribute_id = v.attribute_id and a.element_id = m.element_id and m.element_code = \'data_search\') as cron on t1.object_id = cron.row_id group by t1.object_id order by {SORTING_FIELDS}';
    }
	
	public function Sort(){

        if(!$this->user_allowed_to_write){ return; }

        header('Content-Type: application/json');
        $response = array('message' => '', 'status' => false, 'refresh' => false);

        $params = (isset($_POST['params'])) ? json_decode($_POST['params']) : null;
        $objectId = (!empty($params)) ? $params->ids[0] : null;


        if(!empty($objectId)){

            $sortingFields = $_POST['sorting_field'];
            $sortingModes = $_POST['sorting_mode'];

            if(!empty($sortingFields)){

                $sortingParams = array();

                foreach($sortingFields as $key => $value){
                    if(!empty($value)){
                        if(!isset($sortingParams[$value])){
                            $sortingParams[$value] = $value . " " . $sortingModes[$key];
                        } 
                    }
                }

                $sortingParams = implode(", ", $sortingParams);

                $counter = $this->_executeBranchSort($objectId, $sortingParams);

                if($counter > 0){
                     $response = array('message' => $counter . ' schede riordinate.', 'status' => true, 'refresh' => true);
                }else{
                    $response = array('message' => 'la scheda selezionata non contiene figli', 'status' => false, 'refresh' => false);
                }
            }else{
                $response = array('message' => 'parametri di ordinamento non validi', 'status' => false, 'refresh' => false);
            }
           
        }else{
            $response = array('message' => 'selezione non valida', 'status' => false, 'refresh' => false);
        }

       

        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }

    public function ConfigurationInterface(){
        if(!$this->user_allowed_to_write){ return; }

        $this->render('configuration_csisort_html.php');
    }


    private function _executeBranchSort($branchParentId, $sortingFields, $counter = 0){

        $currentQuery = str_replace("{OBJECT_ID}", $branchParentId, $this->sorting_query);
        $currentQuery = str_replace("{SORTING_FIELDS}", $sortingFields, $currentQuery);

        $qr_result = $this->o_db->query($currentQuery);

        $rankIndex = 1;

        $branch = array();

        while($qr_result->nextRow()) {

            $counter++;

            $objectId = $qr_result->get('object_id');
            $this->o_db->query('update ca_objects set rank =' . $rankIndex . ' where object_id = ' . $objectId);

            $counter = $this->_executeBranchSort($objectId, $sortingFields, $counter);

            $rankIndex++;
        }

        return $counter;
    
    }

    public static function getStatusIcon($object){
        return null;
    }
}
?>