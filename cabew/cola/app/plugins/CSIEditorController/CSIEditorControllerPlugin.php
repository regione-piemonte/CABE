<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0

require_once(__CA_LIB_DIR__."/Logging/KLogger/KLogger.php");
		
	class CSIEditorControllerPlugin extends BaseApplicationPlugin {
		protected $o_log;

		public function __construct($ps_plugin_path) {
			$this->description = _t('Menage CSI editing functions');
			$this->o_log = new KLogger(__CA_APP_DIR__ . '/log/cola.log', KLogger::DEBUG);
			parent::__construct();
		}

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

		public function hookSaveItem($pa_params) {

			if ($pa_params['id'] > 0) {
				$pt_subject = $pa_params['instance'];
				$pb_was_insert = $pa_params['is_insert'];
				
				if (strcmp($pa_params['table_name'], 'ca_objects') === 0) {
					$this->_afterSaveObject($pt_subject, $pb_was_insert);

					$o_db = new Db();
					$o_db->query("UPDATE ca_objects set idno = " . $pt_subject->get('object_id') . ", idno_sort = " . $pt_subject->get('object_id') ." where object_id = " . $pt_subject->get('object_id'));
					$o_db->query("DELETE from ca_acl where group_id is null and table_num = 57 and row_id = " . $pt_subject->get('object_id'));
				}
			}
			return $pa_params;
		}


		public function hookDuplicateItem($pa_params){
			
			if ($pa_params['id'] > 0) {
				$pt_subject = $pa_params['duplicate'];
				
				if (strcmp($pa_params['table_name'], 'ca_objects') === 0) {
					$user = $this->getRequest()->user;

					// Se l'utente non è amministratore salvo l'accesso come il gruppo
					if (!$user->canDoAction('is_administrator')) {
							
						$user_id = $user->getUserID();
						$user_groups = $user->getUserGroups();

						$va_groups_to_set = array();
						foreach ($user_groups as $vs_key => $vs_val) {
							$va_groups_to_set[$vs_key] = __CA_ACL_EDIT_DELETE_ACCESS__;
						}
						$pt_subject->setACLUserGroups($va_groups_to_set);
					}

					/** CSI AF 30/01/2019S
					aggiunta del messaggio di notifica per evitare il bug del AGGIUNGI > NEXT TO subito dopo aver duplicato */

					$notifications = new NotificationManager($this->getRequest());
                    $notifications->addNotification(_t("Remember to save before proceeding with other operations"), __NOTIFICATION_TYPE_ERROR__);

                    /** CSI AF 30/01/2019E */
				}	
			}

			return true;
		}

		
		protected function _afterSaveObject($pt_subject, $pb_was_insert) {
			$i_val = $pt_subject->getAppConfig()->get('set_access_user_groups_for_' . $pt_subject->tableName());

			if ((int)$pt_subject->getAppConfig()->get('set_access_user_groups_for_' . $pt_subject->tableName()) == 0){
				return true;
			}	

			$user = $this->getRequest()->user;

			// Se l'utente non è amministratore salvo l'accesso come il gruppo
			if (!$user->canDoAction('is_administrator')) {
				$user_id = $user->getUserID();
				$user_groups = $user->getUserGroups();

				$va_groups_to_set = array();
				foreach ($user_groups as $vs_key => $vs_val) {
					$va_groups_to_set[$vs_key] = __CA_ACL_EDIT_DELETE_ACCESS__;
				}
				$pt_subject->setACLUserGroups($va_groups_to_set);

				if ($pt_subject->numErrors()) return false;
			}
			return true;
		}		
		
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}
?>