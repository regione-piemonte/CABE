<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
		
	class CSIDefaultAccessLevelPlugin extends BaseApplicationPlugin {

		
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {

			$this->description = _t('Menage CSI Default Access Level behavior');
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

			$pa_params['access_level'] = ($user->getUserID() == null || empty($user)) ? __CA_ACL_EDIT_DELETE_ACCESS__ : $this::checkAccessLevel($user->getUserGroups(), $table_num, $pn_id, $access_level);

			return $pa_params;
		}


		public static function checkAccessLevel($groups, $tableNum, $pnId, $accessLevel){
			
			// solo sugli oggetti
			if($tableNum == 57){
				
					// se l'utente non ha gruppi l'accesso è zero
				if(empty($groups)){
					$accessLevel = __CA_ACL_NO_ACCESS__;
				}else{
					// se non c'è un permesso specificato per i gruppi dell'utente, l'accesso è 0

					$va_group_ids = array_keys($groups);
					$o_db = new Db();
					$qr_res = $o_db->query("
						SELECT max(access) a 
						FROM ca_acl
						WHERE
							table_num = ? AND row_id = ? AND group_id in (?)  AND user_id IS NULL
							
					", (int)$tableNum, (int)$pnId, $va_group_ids);

					if ($qr_res->nextRow()) {

						if(!$qr_res->get('a')){

							// se non ci sono record nella CA_ACL non è una scheda completa, dagli l'accesso di default, altrimenti no

							$qr_res = $o_db->query("
								SELECT *
								FROM ca_acl
								WHERE
									table_num = ? AND row_id = ?
									
							", (int)$tableNum, (int)$pnId);

							if ($qr_res->nextRow()) {
								$accessLevel = __CA_ACL_NO_ACCESS__;
							}else{

							}

							
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