<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
class CSICounterController extends ActionController {

      protected $o_db;
      protected $is_user_enabled;

	public function __construct(&$po_request, &$po_response, $pa_view_paths = NULL) {
            parent::__construct($po_request, $po_response, $pa_view_paths);

            $user = $this->getRequest()->user;
            $this->is_user_enabled = $user->canDoAction('can_use_csi_archive_manager');
    }
	
	public function Count(){

        if($this->is_user_enabled){
            header('Content-Type: application/json');
            $response = array('message' => '', 'status' => false);

            $ids = (isset($_POST['ids'])) ? $_POST['ids'] : array();

            if(!empty($ids)){
                $query = 'select sum(IFNULL(av.value_integer1,0)) as total from
                    ca_metadata_elements m, ca_attributes a, ca_attribute_values av
                    where m.element_id = a.element_id and a.attribute_id = av.attribute_id
                    and m.element_code=\'qnt_calcolata\'
                    and a.table_num = 57
                    and a.row_id in ('. join(', ', $ids) .')';

                $this->o_db = new Db("", NULL, false);
                $qr_result = $this->o_db->query($query);
                $qr_result->nextRow();

                $total = (int)$qr_result->get('total');

                $response = array('message' => 'Totale: ' . $total, 'status' => true, 'refresh' => false);
            }

            $this->view->setVar('response', $response);
            $this->render('response_json.php');
        }
    }

    public static function getStatusIcon($object){
        return null;
    }

}
?>