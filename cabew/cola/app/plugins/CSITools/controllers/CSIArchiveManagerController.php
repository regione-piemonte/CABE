<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0
class CSIArchiveManagerController extends ActionController {
        protected $user;
        protected $is_readonly;
        protected $pluginPath;
        protected $pluginLocalPath;
        protected $opo_config;
        protected $icons;
        protected $action;
        protected $icon_codes;

        public function __construct(&$po_request, &$po_response, $pa_view_paths = NULL) {
             
	       parent::__construct($po_request, $po_response, $pa_view_paths);
            $this->user = $po_request->user;
            $this->is_readonly = false;
            $this->pluginPath = __CA_URL_ROOT__ . '/app/plugins/CSITools';
            $this->pluginLocalPath = __CA_BASE_DIR__ . '/app/plugins/CSITools';
            $this->opo_config = Configuration::load(__CA_APP_DIR__ . '/plugins/CSITools/conf/csitools.conf');
            $this->icons = $this->opo_config->get('icons');
            $this->actions = $this->loadActions();

            $user_groups = $this->user->getUserGroups();
            if (!empty($user_groups)) {

                $this->is_readonly = false;
                foreach($user_groups as $group){
                    if($group['code'] == READONLY_GROUP_CODE){
                        $this->is_readonly = true;
                        break;
                    }
                }
            }

            foreach($this->actions as $group => $controllers){
                foreach($controllers as $controller => $data){
                    $className = $controllers[$controller]['class'] . '.php';
                    require_once($className);
                }
            }

            $this->icon_codes = $this->loadIconCodes();

            $this->view->setVar('is_readonly', $this->is_readonly);
            $this->view->setVar('plugin_path', $this->pluginPath);
        }

        private function loadIconCodes(){
            $codes = array();
            $o_data = new Db();
            $qr_result = $o_data->query("select item_id, idno from ca_list_items i where list_id = (select list_id from ca_lists where list_code ='object_types')");

            while($qr_result->nextRow()) {
                $codes[$qr_result->get('item_id')] = $qr_result->get('idno');

            }

            return $codes;
        }

        private function loadRoots(){
            $objects = array();

            $o_data = new Db();
            $qr_result = $o_data->query("
                SELECT *
                FROM ca_objects
                WHERE parent_id is null and deleted = 0 order by rank
             ");

            while($qr_result->nextRow()) {
                $object = new ca_objects($qr_result->get('object_id'));
                if($object->checkACLAccessForUser($this->user)){
			         $objects[] = $this->mapNode($object);
                }
            }

            return $objects;
        }

        public function GetTree(){
            return (empty($this->request->getParameter('id', pInteger))) ? $this->GetRoots() : $this->GetChildren();
        }

        public function GetRoots(){
            header('Content-Type: application/json');
            $response = $this->loadRoots();
            $this->view->setVar('response', $response);
            $this->render('response_json.php');
        }

        private function loadChildren($parentId){
            $response = array();
            if(!empty($parentId)){
                $parent = new ca_objects($parentId);

                if(!empty($parent)){
                    $children = $parent->getHierarchyChildren(null, array('idsOnly' => true, 'sort' => 'ca_objects.rank'));
                    if(!empty($children)){
                        foreach($children as $objectId){
                            $object = new ca_objects($objectId);
                            $response[] = $this->mapNode($object);
                        }
                    }
                }
            }

            return $response;
        }

         private function mapNode($object){
            $node = new stdClass();
            $node->id = $object->get('object_id');
            $node->text = '<strong>'. $node->id . '</strong> | ' .$object->get('ca_object_labels.name');
            $node->type = $object->get('type_id');
            $node->icon = $this->icons[$this->icon_codes[$node->type]];
            $children = $object->getHierarchyChildCountsForIDs(array($node->id));
            $node->children = reset($children) > 0;
            $node = $this->mapNodeActionData($node, $object);
            return $node;
        }

        private function mapNodeActionData($node, $object){
            foreach($this->actions as $group => $controllers){
                foreach($controllers as $controller => $data){
                    $controllerClass = $controllers[$controller]['class'];
                    $statusIcon = $controllerClass::getStatusIcon($object);

                    if(!empty($statusIcon)){
                        $node->text .= '<i class="csitools-status-icon ' . $statusIcon . '"></i>';
                    }
                }
            }

            return $node;

        }

        public function GetChildren(){
            $parentId = $this->request->getParameter('id', pInteger);
            $response = $this->loadChildren($parentId);

            header('Content-Type: application/json');
            $this->view->setVar('response', $response);
            $this->render('response_json.php');
        }


        public function CheckScope(){
            header('Content-Type: application/json');

            $scope = $this->request->getParameter('scope', pString);
            $scopes  = explode('|', $scope);
            $ids = $this->request->getParameter('ids', pArray);

            $status = self::checkScopeByIDs($ids, $scopes);

            $response = array('status' => $status);
            $this->view->setVar('response', $response);
            $this->render('response_json.php');
        }

        public static function checkScopeByIDs($ids, $scopes){
            $qScope = "select v.value_longtext1 as scope
                from ca_attributes a, ca_attribute_values v, ca_metadata_elements m
                where m.element_code = 'tipo_ambito'
                and a.attribute_id = v.attribute_id
                and m.element_id = a.element_id
                and a.row_id = {OBJECT_ID}
                and a.table_num = 57";

            //var_dump($scopes, $ids);

            $status = true;
            
            if(!empty($ids)){

                foreach($ids as $id){
                    $object = new ca_objects($id);

                    $o_data = new Db();
                    $qCurrentScope = str_replace("{OBJECT_ID}", $id, $qScope);

                    //var_dump($qCurrentScope);
                    $qr_result = $o_data->query($qCurrentScope);

                    if($qr_result->nextRow()){
                        $scope = $qr_result->get('scope');
                        //var_dump($scope);

                        if(!in_array($scope, $scopes)){
                            $status = false;
                            break;
                        }

                    }else{
                        $status = false;
                        break;
                    }
                }
            }

            return $status;
        }


        private function loadActions(){
            $conf = file_get_contents($this->pluginLocalPath . '/conf/actions.conf');
            $conf = json_decode($conf);
            $actions = array();

            foreach($conf as $group){

                $actions[$group->groupName] = array();

                foreach($group->controllers as $controller){

                    if($controller->enabled){

                        $actions[$group->groupName][$controller->controllerName]['class'] = $controller->controllerName . 'Controller';
                        $actions[$group->groupName][$controller->controllerName]['defaultChangeSelectionHandler'] = $controller->defaultChangeSelectionHandler;
                        $actions[$group->groupName][$controller->controllerName]['allowZeroSelection'] = $controller->allowZeroSelection;
                        $actions[$group->groupName][$controller->controllerName]['allowMultipleSelection'] = $controller->allowMultipleSelection;
                        $actions[$group->groupName][$controller->controllerName]['scope'] = $controller->scope;

                        foreach($controller->actions as $action){

                            if($action->enabled){
                                $actionData = new stdClass();
                                $actionData->action = $action->action;
                                $actionData->label = $action->label;
                                $actionData->icon = $action->icon;
                                $actionData->behavior = $action->behavior;
                                $actionData->requireParameters = $action->requireParameters;
                                $actionData->scope = $action->scope;
                                $actionData->path = __CA_URL_ROOT__ . '/service.php/CSITools/' . $controller->controllerName . '/' . $action->action;
                                $js = $this->pluginPath . '/js/' . $controller->controllerName . '.js';
                                $actions[$group->groupName][$controller->controllerName]['js'] = $js;
                                $actions[$group->groupName][$controller->controllerName]['actions'][] = $actionData;
                            }
                        }
                    }
                }
            }

            return $actions;
        }

        private function getHTMLActions(){
            $groups = $this->actions;

            foreach ($groups as $group => $controllers) {
                foreach($controllers as $controller){
                    $output .= '<script src="'. $controller['js'] .'" />';
                }
            }


            foreach ($groups as $group => $controllers) {

                $output .= '<div id="CSIToolsActionGroup'. $group . '" class="CSIToolsActionGroup">';
                foreach($controllers as $controllerName => $controller){

                    $output .= '<div id="CSIToolsActionController'. $controllerName . '" class="CSIToolsActionController" data-controller="'.$controllerName.'"  data-default-change-selection-handler="'.$controller['defaultChangeSelectionHandler'].'" data-allow-zero-selection="'. $controller['allowZeroSelection'] .'" data-allow-multiple-selection="'. $controller['allowMultipleSelection'] .'"  data-scope="' . $controller['scope'] . '">';
                    foreach($controller['actions'] as $action){
                    
                        $output .= '<div id="CSIToolsActionController'. $controllerName . '-'. $action->action .'" class="csitools-action" data-action="' . $action->action .'" data-action-path="'.$action->path.'"  data-behavior="'.$action->behavior.'" data-require-parameters="'. $action->requireParameters .'" data-scope="'.$action->scope.'"><i class="fa '. $action->icon .'"></i><span>'. $action->label .'</span></div>';

                    }
                    $output .= '</div>';
                }

                $output .= '</div>';
            }

            return $output;
        }

        public function Index($pa_values = NULL, $pa_options = NULL) {
            AssetLoadManager::register('panel');
            AssetLoadManager::register('csitools-treejs');

            $HTMLActions = $this->getHTMLActions();
            $this->view->setVar('HTMLActions', $HTMLActions);

            $this->render('tree_html.php');
            $this->render('actions_html.php');
        }

}
?>