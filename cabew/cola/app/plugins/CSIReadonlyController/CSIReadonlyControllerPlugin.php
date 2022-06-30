<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
	class CSIReadonlyControllerPlugin extends BaseApplicationPlugin {

		
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {

			$this->description = _t('Menage CSI Read Only behavior');
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the historyMenu plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true
			);
		}
		

		// se l'utente ha gruppo readonly, vede solo ciò che può vedere in base ai suoi altri gruppi ma solo in sola lettura,
		// se invece ha solo il gruppo readonly, vede tutto ma in sola lettura
		public function hookCSIcheckACLAccessForUser($pa_params){
			

			$user = $pa_params['user'];
			$table_num = $pa_params['table_num'];
			$pn_id = $pa_params['pn_id'];
			$access_level = $pa_params['access_level'];

			$pa_params['access_level'] = $this::checkAccessLevel($user->getUserGroups(), $table_num, $pn_id, $access_level);

			return $pa_params;
		}


		public static function checkAccessLevel($groups, $tableNum, $pnId, $accessLevel){

				$hasReadonlyGroup = false;

				if($groups){
					foreach ($groups as $key => $group) {
						if($group['code'] == 'readonly'){
							$hasReadonlyGroup = true;
							break;
						}
					}
				}

				if($hasReadonlyGroup){
					if(count($groups) > 0){
						if(count($groups) == 1){
							$accessLevel = __CA_ACL_READONLY_ACCESS__;
						}else{
							if($accessLevel > 1){
								$accessLevel = __CA_ACL_READONLY_ACCESS__;
							}
						}
					}
				}

			return $accessLevel;
		}
		
		
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}
?>