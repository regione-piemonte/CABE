<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
class CSIConsistencyController extends ActionController {
	protected $o_db;
    protected $user;
    protected $user_groups;
    protected $is_readonly;
    protected $user_enabled;
    protected $user_allowed_to_write;
    protected $calculateConsistencyQuery;
    protected $updateConsistencyQuery;
    protected $allowedTypeIDs;


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

            $qr_result = $this->o_db->query("select item_id, idno from ca_list_items i where list_id = (select list_id from ca_lists where list_code ='object_types') and parent_id is not null and idno in ('complesso_fondi', 'fondo', 'subfondo', 'super_fondo', 'livello') and deleted = 0");

            while($qr_result->nextRow()) {
                $this->allowedTypeIDs[] = $qr_result->get('item_id');
            }
            

            $this->user_allowed_to_write = ($this->user_enabled && !$this->is_readonly);

    }
	
	public function CalculateConsistency(){

        if(!$this->user_allowed_to_write){ return; }

        header('Content-Type: application/json');
        $response = array('message' => 'Parametri non validi', 'status' => false, 'refresh' => false);

        $this->calculateConsistencyQuery = "select concat(group_concat(label separator ', ')) as consistency from
            (select concat( totali.eti,\": \",count(totali.object_id)) label
            from 
            (
            select o.object_id, lb.name_singular eti from
            ca_objects o, ca_list_items l,
            ca_list_item_labels lb
            where  o.type_id = l.item_id
            and l.item_id = lb.item_id
            and o.object_id not in
            (select a.row_id from
            ca_attributes as a, ca_metadata_elements as m
            where m.element_id = a.element_id
            and m.element_code= 'livello'
            and a.table_num = 57
            )
            and o.object_id in
            ({OBJECT_IDS})
            union
            select a.row_id as object_id, v.value_longtext1 as eti
            from ca_attribute_values as v, ca_attributes as a, ca_metadata_elements as m
            where v.attribute_id = a.attribute_id
            and v.element_id = m.element_id
            and m.element_code= 'livello'
            and a.table_num = 57
            and a.row_id in ({OBJECT_IDS})
            ) totali
            group by totali.eti ) tot";

        $this->updateConsistencyQuery = "update ca_attribute_values as v, ca_attributes as a, ca_metadata_elements as m
            set v.value_longtext1 = \"{CALCULATED_CONSISTENCY}\"
            where v.attribute_id = a.attribute_id
            and v.element_id = m.element_id
            and m.element_code= 'consistenza_calcolata'
            and a.table_num = 57
            and a.a.row_id = {OBJECT_ID}";


        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : null;
        $objectId = (!empty($ids)) ? $ids[0] : null;

        if(!empty($objectId)){

            $object = new ca_objects($objectId);
            $typeId = $object->get('type_id');

            if(in_array($typeId, $this->allowedTypeIDs)){

                $children = $this->_getBranchIDs($objectId);

                if(empty($children)){
                    $response = array('message' => 'Il nodo selezionato non contiene figli, impossibile calcolare la consistenza', 'status' => false, 'refresh' => false);
                }else{

                    $calculateConsistencyQuery = str_replace("{OBJECT_IDS}", implode(", ", $children), $this->calculateConsistencyQuery);

                    //var_dump($calculateConsistencyQuery);
                    $consistencyElementId = ca_metadata_elements::getElementID('consistenza_calcolata');

                    $qr_result = $this->o_db->query($calculateConsistencyQuery);

                    $qr_result->nextRow();
                    $consistency = $qr_result->get('consistency');

                    $consistencyValues = array('locale_id' => '');
                    $consistencyValues[$consistencyElementId] = $consistency;
                
                    
                    $object->setMode(ACCESS_WRITE);
                    $object->setAsChanged('_ca_attribute_' . $consistencyElementId);
                    $object->replaceAttribute($consistencyValues, $consistencyElementId);
                    $object->update();

                    $response = array('message' => 'Consistenza calcolata: ' . $consistency . '. Il valore è stato salvato nel campo Consistenza calcolata della sezione DATI PRINCIPALI', 'status' => true, 'refresh' => true);
                   
                }
             }else{
                $response = array('message' => 'Non è possibile calcolare la consistenza per il tipo di nodo selezionato', 'status' => false, 'refresh' => false);
            }
        }
       
        $this->view->setVar('response', $response);
        $this->render('response_json.php');
    }

    private function _getBranchIDs($objectId, $list = array()){

        $object = new ca_objects($objectId);

        $childrenIDs = $object->getHierarchyChildren(null, array('idsOnly' => true));

        $list = array_merge($list, $childrenIDs);

        foreach($childrenIDs as $childId){
            $list = $this->_getBranchIDs($childId, $list);
        }

        return $list;
    }


    public static function getStatusIcon($object){
        return null;
    }
}
?>