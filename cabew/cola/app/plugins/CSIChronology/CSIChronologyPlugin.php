<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
require_once (__CA_LIB_DIR__ . "/Logging/KLogger/KLogger.php");

class CSIChronologyPlugin extends BaseApplicationPlugin
{
	protected $o_log;
	protected $o_db;

	protected $cron_element_id;
	protected $data_search_element_id;

	protected $cron_values;
	protected $data_search_values;
	protected $cron_attributes;

	public function __construct($ps_plugin_path)
	{
		$this->description = _t('Menage CSI chronology data format');
		$this->o_log = new KLogger(__CA_APP_DIR__ . '/log/cola.log', KLogger::DEBUG);
		$this->o_db = new Db("", NULL, false);

		
		parent::__construct();
	}

	/**
	 * Override checkStatus() to return true - the historyMenu plugin always initializes ok
	 */
	public function checkStatus()
	{
		return array(
			'description' => $this->getDescription() ,
			'errors' => array() ,
			'warnings' => array() ,
			'available' => true
		);
	}

	/**
	 * Get plugin user actions
	 */
	public static function getRoleActionList()
	{
		return array();
	}

	public function hookSaveItem($pa_params)
	{
		if ($pa_params['id'] > 0)
		{
			$pt_subject = $pa_params['instance'];

			if (strcmp($pa_params['table_name'], 'ca_objects') === 0)
			{
				$objectId = $pt_subject->get('object_id');
				$this->cron_attributes = $this->getCronAttributes($objectId);

				if(!empty($this->cron_attributes)){

					$object = new ca_objects($objectId);
					$vb_we_set_transaction = true;
					if (!$object->inTransaction()) {
						$object->setTransaction(new Transaction($object->getDb()));
						$vb_we_set_transaction = true;
					}
					$this->cron_element_id = ca_metadata_elements::getElementID('cron_new');
					$this->data_search_element_id = ca_metadata_elements::getElementID('data_search');
					$this->data_search_attribute_id = $this->getDataSearchAttributeId($objectId);
					$this->data_search_values = array('locale_id' => '');

					$this->cron_values = array();
					
					$this->updateChronologyFields($object);
					
					if($object->numErrors() > 0) {
						foreach($object->getErrors() as $vs_error) {
							Debug::msg("[CSIChronology update()] there was an error while updating the record: ".$vs_error);
						}
						if ($vb_we_set_transaction) { $object->removeTransaction(false); }	
					}

					if ($vb_we_set_transaction) { $object->removeTransaction(true); }
				}
				
			}
		}

		return true;

	}

	public function updateChronologyFields($object)
	{
		global $g_ui_locale_id;
		
		$cron_2 = ca_metadata_elements::getElementID('cron_2');
		$tipo_data_2 = ca_metadata_elements::getElementID('tipo_data_2');
		$motiv_2 = ca_metadata_elements::getElementID('motiv_2');
		$spec_2 = ca_metadata_elements::getElementID('spec_2');
		$note_cron_2 = ca_metadata_elements::getElementID('note_cron_2');
		$dta_topica = ca_metadata_elements::getElementID('dta_topica');
		$sec_da = ca_metadata_elements::getElementID('sec_da');
		$fraz_sec_da = ca_metadata_elements::getElementID('fraz_sec_da');
		$sec_a = ca_metadata_elements::getElementID('sec_a');
		$fraz_sec_a = ca_metadata_elements::getElementID('fraz_sec_a');
		$data_da = ca_metadata_elements::getElementID('data_da');
		$data_a = ca_metadata_elements::getElementID('data_a');
		$data_calc_da = ca_metadata_elements::getElementID('data_calc_da');
		$data_calc_a = ca_metadata_elements::getElementID('data_calc_a');
		$data_da_output = ca_metadata_elements::getElementID('data_da_output');
		$data_a_output = ca_metadata_elements::getElementID('data_a_output');
		$is_single = ca_metadata_elements::getElementID('is_single');

		$attributeList = $this->cron_attributes;

		$dataSearchDec1 = null;
		$dataSearchDec2 = null;
		$dataSearchLongtext = null;

		foreach ($attributeList as $attributeId)
		{
			$this->cron_values[$attributeId] = array('locale_id' => $g_ui_locale_id);
			$item_sec_da = $this->getItemValue($sec_da, $attributeId);
			$item_fraz_sec_da = $this->getItemValue($fraz_sec_da, $attributeId);
			$item_data_da = $this->getItemValue($data_da, $attributeId);
			$item_sec_a = $this->getItemValue($sec_a, $attributeId);
			$item_fraz_sec_a = $this->getItemValue($fraz_sec_a, $attributeId);
			$item_data_a = $this->getItemValue($data_a, $attributeId);
			$item_cron_2 = $this->getItemValue($cron_2, $attributeId);
			$item_tipo_data_2 = $this->getItemValue($tipo_data_2, $attributeId);
			$item_motiv_2 = $this->getItemValue($motiv_2, $attributeId);
			$item_spec_2 = $this->getItemValue($spec_2, $attributeId);
			$item_note_cron_2 = $this->getItemValue($note_cron_2, $attributeId);
			$item_dta_topica = $this->getItemValue($dta_topica, $attributeId);

			$this->cron_values[$attributeId][$sec_da] = $item_sec_da['longtext'];
			$this->cron_values[$attributeId][$fraz_sec_da] = $item_fraz_sec_da['longtext'];
			$this->cron_values[$attributeId][$data_da] = $item_data_da['longtext'];
			$this->cron_values[$attributeId][$sec_a] = $item_sec_a['longtext'];
			$this->cron_values[$attributeId][$fraz_sec_a] = $item_fraz_sec_a['longtext'];
			$this->cron_values[$attributeId][$data_a] = $item_data_a['longtext'];

			$this->cron_values[$attributeId][$cron_2] = $item_cron_2['longtext'];
			$this->cron_values[$attributeId][$tipo_data_2] = $item_tipo_data_2['longtext'];
			$this->cron_values[$attributeId][$motiv_2] = $item_motiv_2['longtext'];
			$this->cron_values[$attributeId][$spec_2] = $item_spec_2['longtext'];

			$this->cron_values[$attributeId][$note_cron_2] = $item_note_cron_2['longtext'];
			$this->cron_values[$attributeId][$dta_topica] = $item_dta_topica['longtext'];

			$cronValuesDa = (!empty($item_sec_da['itemId']) && !empty($item_fraz_sec_da['itemId'])) ? $this->getCronMapValues($item_sec_da['itemId'], $item_fraz_sec_da['itemId']) : null;
			$cronValuesA = (!empty($item_sec_a['itemId']) && !empty($item_fraz_sec_a['itemId'])) ? $this->getCronMapValues($item_sec_a['itemId'], $item_fraz_sec_a['itemId']) : null;

			$check_data_da = false;
			$check_data_a = false;
			$current_data_dec1 = null;
			$current_data_dec2 = null;

			if (!empty($item_data_da['longtext']))
			{
				$this->cron_values[$attributeId][$data_da_output] = $item_data_da['longtext'];
				$this->cron_values[$attributeId][$data_calc_da] = "";
				
				$check_data_da = true;

				$convertedDecs = $this->convertDecBeforeAfter($item_data_da['dec1'], $item_data_da['dec2']);

				$item_data_da['dec1'] = $convertedDecs['dec1'];
				$item_data_da['dec2'] = $convertedDecs['dec2'];

				$current_data_dec1 = ($item_data_da['dec1'] < $current_data_dec1 || empty($current_data_dec1)) ? $item_data_da['dec1'] : $current_data_dec1;
				$current_data_dec2 = ($item_data_da['dec2'] > $current_data_dec2) ? $item_data_da['dec2'] : $current_data_dec2;
			}
			else
			{
				if (!empty($cronValuesDa))
				{
					$this->cron_values[$attributeId][$data_da_output] = $cronValuesDa['longtext'];
					$this->cron_values[$attributeId][$data_calc_da] =  $cronValuesDa['longtext'];
					
					$check_data_da = true;
					
					$current_data_dec1 = ($cronValuesDa['dec1'] < $current_data_dec1 || empty($current_data_dec1)) ? $cronValuesDa['dec1'] : $current_data_dec1;
					$current_data_dec2 = ($cronValuesDa['dec2'] > $current_data_dec2) ? $cronValuesDa['dec2'] : $current_data_dec2;

				}
			}

			if (!empty($item_data_a['longtext']))
			{
				$this->cron_values[$attributeId][$data_a_output] = $item_data_a['longtext'];
				$this->cron_values[$attributeId][$data_calc_a] = "";
				
				$check_data_a = true;

				$convertedDecs = $this->convertDecBeforeAfter($item_data_a['dec1'], $item_data_a['dec2']);

				$item_data_a['dec1'] = $convertedDecs['dec1'];
				$item_data_a['dec2'] = $convertedDecs['dec2'];


				$current_data_dec1 = ($item_data_a['dec1'] < $current_data_dec1 || $current_data_dec1 == null) ? $item_data_a['dec1'] : $current_data_dec1;
				$current_data_dec2 = (!empty($item_data_a['dec2']) && $item_data_a['dec2'] > $current_data_dec2) ? $item_data_a['dec2'] : $current_data_dec2;
			}
			else
			{
				if (!empty($cronValuesA))
				{
					$this->cron_values[$attributeId][$data_a_output] = $cronValuesA['longtext'];
					$this->cron_values[$attributeId][$data_calc_a] =  $cronValuesA['longtext'];

					$check_data_a = true;
					
					$current_data_dec1 = ($cronValuesA['dec1'] < $current_data_dec1 || $current_data_dec1 == null) ? $cronValuesA['dec1'] : $current_data_dec1;
					$current_data_dec2 = ($cronValuesA['dec2'] > $current_data_dec2) ? $cronValuesA['dec2'] : $current_data_dec2;

				}
			}

			if (empty($check_data_da) && empty($check_data_a))
			{
				$this->cron_values[$attributeId][$data_calc_da] = '';
				$this->cron_values[$attributeId][$data_calc_a] = '';
				$this->cron_values[$attributeId][$is_single] = '';
				$this->cron_values[$attributeId][$data_da_output] = '';
				$this->cron_values[$attributeId][$data_a_output] = '';
			}
			else
			{
				if (empty($check_data_da))
				{
					$this->cron_values[$attributeId][$data_calc_da] = '';
					$this->cron_values[$attributeId][$data_da_output] = '';
				}

				if (empty($check_data_a))
				{
					$this->cron_values[$attributeId][$data_calc_a] = '';
					$this->cron_values[$attributeId][$data_a_output] = '';
				}

				if (!empty($check_data_da) && !empty($check_data_a))
				{
					$this->cron_values[$attributeId][$is_single] = '0';
				}
				else
				{
					$this->cron_values[$attributeId][$is_single] = '1';
				}
			}

			if (empty($item_data_da['longtext']) && (!empty($item_sec_da['itemId']) && empty($item_fraz_sec_da['itemId'])))
			{
				$notifications = new NotificationManager($this->getRequest());
				$notifications->addNotification(_t("The field 'DA: Frazione di secolo' is required if you set the field 'DA: Secolo'.") , __NOTIFICATION_TYPE_ERROR__);
			}

			if (empty($item_data_a['longtext']) && (!empty($item_sec_a['itemId']) && empty($item_fraz_sec_a['itemId'])))
			{
				$notifications = new NotificationManager($this->getRequest());
				$notifications->addNotification(_t("The field 'A: Frazione di secolo' is required if you set the field 'A: Secolo'.") , __NOTIFICATION_TYPE_ERROR__);
			}

			$dataSearchDec1 = (!empty($current_data_dec1) && ($current_data_dec1 < $dataSearchDec1 || $dataSearchDec1 == null)) ? $current_data_dec1 : $dataSearchDec1;
			$dataSearchDec2 = ($current_data_dec2 > $dataSearchDec2 || $dataSearchDec2 == null) ? $current_data_dec2 : $dataSearchDec2;
			
		}

		if (empty($dataSearchDec1) || empty($dataSearchDec2))
		{
			$this->data_search_values[$this->data_search_element_id] = '';
		}
		else
		{
			$dataSearchLongtext = $this->getDateFromDec($dataSearchDec1) . ' - ' . $this->getDateFromDec($dataSearchDec2);
			$this->data_search_values[$this->data_search_element_id] = $dataSearchLongtext;
		}

		$this->_updateValues($object);

	}

	private function _updateValues($object){
		if(isset($_REQUEST['form_timestamp']) && ($_REQUEST['form_timestamp'] > 0)) { $_REQUEST['form_timestamp'] = time(); }

		$object->setMode(ACCESS_WRITE);
		$object->setAsChanged('_ca_attribute_' . $this->data_search_element_id);
		
		foreach($this->cron_values as $attributeId => $values){
			$object->editAttribute($attributeId, $this->cron_element_id, $values);
			$object->update();	
		}

		$object->replaceAttribute($this->data_search_values, $this->data_search_element_id);
		$object->update();	
	}

	public function getCronAttributes($objectId)
	{
		$cron_new = ca_metadata_elements::getElementID('cron_new');
		$attributeList = array();

		if(!empty($cron_new)){
			$qr_result = $this
				->o_db
				->query("select * from ca_attributes where element_id = " . $cron_new . " and row_id = " . $objectId);

			while ($qr_result->nextRow())
			{
				$attributeList[] = $qr_result->get('attribute_id');
			}
		}

		return $attributeList;
	}

	public function getCronMapValues($item_id_secolo, $item_id_frazione)
	{
		$values = array();

		$qr_result = $this
			->o_db
			->query("select vlongtext1, dec1, dec2 from CA_MAP_SECOLO where ITEM_ID_SECOLO = '" . $item_id_secolo . "' and  ITEM_ID_FRAZ = '" . $item_id_frazione . "'");

		if ($qr_result->nextRow())
		{
			$values['longtext'] = $qr_result->get('vlongtext1');
			$values['dec1'] = (double)$qr_result->get('dec1');
			$values['dec2'] = (double)$qr_result->get('dec2');
		}

		return $values;
	}

	public function getItemValue($elementId, $attributeId)
	{
		$itemValue = array();
		$qr_result = $this
			->o_db
			->query("select v.* from ca_attribute_values v, ca_attributes a where  v.attribute_id = a.attribute_id and a.attribute_id = " . $attributeId . " and v.element_id = " . $elementId);

		if ($qr_result->nextRow())
		{
			$itemValue['itemId'] = $qr_result->get('item_id');
			$itemValue['longtext'] = $qr_result->get('value_longtext1');
			$itemValue['dec1'] = $qr_result->get('value_decimal1');
			$itemValue['dec2'] = $qr_result->get('value_decimal2');
		}

		return $itemValue;
	}


	public function unsetAttributeValues($attributeId, $elementId)
	{

		$this
			->o_db
			->query("update ca_attribute_values set value_longtext1 = null, value_decimal1 = null, value_decimal2 = null, item_id = null where attribute_id = " . $attributeId . " and element_id = " . $elementId);

	}

	public function getDataSearchAttributeId($objectId)
	{
		$elementCodeId = ca_metadata_elements::getElementID('data_search');
		$attributeId = null;
		$qr_result = $this
			->o_db
			->query("select * from ca_attributes where element_id = " . $elementCodeId . " and row_id = " . $objectId);

		$qr_result->nextRow();
		return $qr_result->get('attribute_id');
	}

	public function getDateFromDec($dec)
	{
		$suffix = ' ad';
        $date = null;
        if (!empty($dec))
        {
                $decParts = explode('.', $dec);

                $year = $decParts[0];
                $month = substr($decParts[1], 0, 2);
                $day = substr($decParts[1], 2, 2);

                if($year < 0){
                    $year = abs($year);
                    $suffix = ' bc';
                }

                $month = ($month == "00") ? "01" : $month;
                $day = ($day == "00") ? "01" : $day;


                $date = $day . "/" . $month . "/" . $year . $suffix;
        }

		return $date;
	}

	public function isBeforeOrAfter($dec){


		$decParts = explode('.', $dec);
        $year = $decParts[0];
        $year = abs($year);

        return $year == '2000000000';
	}


	public function convertDecBeforeAfter($dec1, $dec2){

		if($this->isBeforeOrAfter($dec1)){
			$dec1 = $this->convertToDec($dec2, 'dec1');
		}

		if($this->isBeforeOrAfter($dec2)){
			$dec2 = $this->convertToDec($dec1, 'dec2');
		}

		return array('dec1' => $dec1, 'dec2' => $dec2);
	}

	public function convertToDec($dec, $type){

		$decParts = explode('.', $dec);
        $year = $decParts[0];
        $month = substr($decParts[1], 0, 2);
        $day = substr($decParts[1], 2, 2);

        $monthday = $month . $day;

        $isSpecificDate = !in_array($monthday, array('0101', '1231'));

        switch ($type) {
    		case 'dec1':
    			$monthday = ($isSpecificDate) ? $monthday : '0101';
  				$dec = $year . '.' . $monthday .'000000';
  				break;
  			case 'dec2':
  				$monthday = ($isSpecificDate) ? $monthday : '1231';
  				$dec = $year . '.' . $monthday .'235959';
  				break;
        }

        return $dec;

	}

}
?>