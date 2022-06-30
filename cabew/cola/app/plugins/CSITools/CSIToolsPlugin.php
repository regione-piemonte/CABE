<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
define('READONLY_GROUP_CODE', 'readonly');
		
class CSIToolsPlugin extends BaseApplicationPlugin {

	public function __construct($ps_plugin_path) {
		$this->description = _t('Menage CSITools');
		parent::__construct();
	}


	static public function getRoleActionList() {
	    return array(
		    'can_use_csi_archive_manager' => array(
			    'label' => _t('Can use CSI Archive Manager'),
			    'description' => _t('User can use CSI Archive Manager')
		    )
	    );
    }

    public function checkStatus() {
        return array(
            'description' => $this->getDescription(),
            'errors'      => array(),
            'warnings'    => array(),
            'available'   => true
        );
    }

    public function hookRenderMenuBar( $pa_menu_bar ) {

		$user = $this->getRequest()->user;
		$user_groups = $user->getUserGroups();
		$is_readonly = false;
		$user_enabled = $user->canDoAction('can_use_csi_archive_manager');
      

		if (!empty($user_groups)) {
			foreach($user_groups as $group){
				if($group['code'] == READONLY_GROUP_CODE){
					$is_readonly = true;
					break;
				}
			}
		}

		if(!$is_readonly && $user_enabled){
			if ( isset( $pa_menu_bar['csiTools'] ) ) {
	          
	           	$pa_menu_bar['csiTools']['navigation']['csiArchiveManager'] = array(
	               'displayName' => 'Operazioni massive',
	               "default"         => array(
	                   'module'     => 'CSITools',
	                   'controller' => 'CSIArchiveManager',
	                   'action'     => 'Index'
	               ),
	               'require'         => array()
	           	);
	       } else {
	           //Se non esiste lo creo
	           $pa_menu_bar['csiTools'] = array(
	               'displayName' => 'Utilità',
	               "default"     => array(
	                   'module'     => 'CSITools',
	                   'controller' => 'CSIArchiveManager',
	                   'action'     => 'Index'
	               ),
	               'require'     => array(),
	               'navigation'  => array(
	                   'csiArchiveManager' => array(
	                       'displayName' => 'Operazioni massive',
	                       "default"     => array(
	                           	'module'     => 'CSITools',
	                   			'controller' => 'CSIArchiveManager',
	                           	'action'     => 'Index'
	                       ),
	                       'require'     => array()
	                   )
	               )
	           );
	       }
      }
        return $pa_menu_bar;
    }
	
}

?>