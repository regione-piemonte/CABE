<?php
/* ----------------------------------------------------------------------
 * promemoriaTreeObjectWidget.php :
 * created by Promemoria srl (Turin - Italy) www.promemoriagroup.com
 * info@promemoriagroup.com
 * version 2.0 - 16/02/2015
 * info@promemoriagroup.com
 * This widget allow to view objects in a hierarchical structure
 *
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
require_once( __CA_LIB_DIR__ . '/BaseWidget.php' );
require_once( __CA_LIB_DIR__ . '/IWidget.php' );


class promemoriaTreeObjectWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	static $s_widget_settings = array();
	private $opo_config;

	# -------------------------------------------------------

	public function __construct( $ps_widget_path, $pa_settings ) {
		$this->title       = _t( 'Struttura gerarchica' );
		$this->description = _t( 'Displays objects in a hierarchical structure' );
		parent::__construct( $ps_widget_path, $pa_settings );

		$this->opo_config = Configuration::load( $ps_widget_path . '/conf/promemoriaTreeObjectWidget.conf' );

                //LM 02/11/17S CARICO VARIABILI PER IL PROFILO GLOBAL READER ONLY
                $this->opa_config = Configuration::load( $ps_widget_path . '../../conf/local/app.conf' );
                //LM 02/11/17E
        }
	# -------------------------------------------------------

	/**
	 * Get widget user actions
	 */
	static public function getRoleActionList() {
		return array();
	}
	# -------------------------------------------------------

	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		$vb_available = ( (bool) $this->opo_config->get( 'enabled' ) );

		/*if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("is_administrator")){
			$vb_available = false;
		}*/

		return array(
			'description' => $this->getDescription(),
			'errors'      => array(),
			'warnings'    => array(),
			'available'   => $vb_available
		);
	}
	# -------------------------------------------------------

	/**
	 *
	 */
//LM 22/12/17S
//modifiche per aggiornamento a CA 1.7
//	public function renderWidget( $ps_widget_id, $pa_settings ) {
	public function renderWidget( $ps_widget_id, &$pa_settings ) {
//LM 22/12/17E
	parent::renderWidget( $ps_widget_id, $pa_settings );

		$this->opo_view->setVar( 'request', $this->getRequest() );
		$this->opo_view->setVar( 'field', $this->opo_config->get( 'order_field' ) );
		$this->opo_view->setVar( 'user', $this->getRequest()->user);

                //LM 02/11/17S CARICO VARIABILI PER IL PROFILO GLOBAL READER ONLY
                $is_global_reader = false;
                $user_groups_id = array_keys($this->getRequest()->user->getUserGroups());
                $g_ro_user_id = $this->opa_config->get( 'global_reader_user_id' );
                $g_ro_group_id = $this->opa_config->get( 'global_reader_group_id' );

                if (($this->getRequest()->user->getUserID() == $g_ro_user_id)
                   || (array_key_exists($g_ro_group_id, $user_groups_id))
                ) {
                  $is_global_reader = true;
                }
                
                $this->opo_view->setVar( 'g_ro_user_id', $g_ro_user_id);
                $this->opo_view->setVar( 'g_ro_group_id', $g_ro_group_id);
		        $this->opo_view->setVar( 'is_global_reader', $is_global_reader);
                //LM 02/11/17E

                // CSI AF 30/07/18S
		        $o_db          = new Db( NULL, NULL, false );

				// ottengo il group_id per l'utente readonly se esiste
				$query = 'select * from ca_user_groups where code = "readonly"';
				$qr_result = $o_db->query($query);

				$readonly_group_id = null;
				while ($qr_result->nextRow()) {
				    $readonly_group_id = $qr_result->get("group_id");
				    break;
				}

				$is_readonly = in_array($readonly_group_id, $user_groups_id);

				$this->opo_view->setVar( 'readonly_group_id', $readonly_group_id);
				$this->opo_view->setVar( 'is_readonly', $is_readonly);

                // CSI AF 30/07/18E
                return $this->opo_view->render( 'main_html.php' );
	}
	# -------------------------------------------------------
}

BaseWidget::$s_widget_settings['promemoriaTreeObjectWidget'] = array();
?>
