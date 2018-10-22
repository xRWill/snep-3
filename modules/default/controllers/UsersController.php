<?php

/*
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Controller for users
 * 
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 * 
 */
class UsersController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
     public function init() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        // Add dashboard button
        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
                                              Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
                                              Zend_Controller_Front::getInstance()->getRequest()->getActionName());

        $this->profiles = Snep_Profiles_Manager::getAll();
    }

    /**
     * indexAction - List all users
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Users")));

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("n" => "users"), array("id as ide", "name as nome", "email", "created", "updated"))
                ->join(array("g" => "profiles"), 'n.profile_id = g.id', "name");

        $stmt = $db->query($select);
        $data = $stmt->fetchAll();

        $this->view->users = $data;

    }

    /**
     *  addAction - Add User
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Users"),
                    $this->view->translate("Add")));


        $this->view->profiles = $this->profiles;
        $this->view->queues = Snep_Queues_Manager::getQueueAll();

        //Define the action and load form
        $this->view->action = "add" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();

            $name_exist = Snep_Users_Manager::getName($dados['name']);

            if (count($name_exist) > 1) {

                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));

            }else{

                $id = Snep_Users_Manager::add($dados);

                if(isset($dados['queues'])){

                    foreach($dados['queues'] as $x => $queue){
                        Snep_Users_Manager::addQueuesPermission($id, $queue);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'users', $id, $this->view->translate("User") . " {$id} " . $dados['name']);   

                $this->_redirect($this->getRequest()->getControllerName());

            }

        }

    }

    /**
     * editAction - Edit users
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Users"),
                    $this->view->translate("Edit")));

        $id = $this->_request->getParam('id');
        $user = Snep_Users_Manager::get($id);
        $queues = Snep_Queues_Manager::getQueueAll();
        $queuesPermission = Snep_Users_Manager::getQueuesPermission($id);

        if($id == 1){
            $this->view->disabled = 'disabled';
        }

        if(!empty($queuesPermission)){
            foreach($queues as $q => $queue){
                foreach($queuesPermission as $p => $queueP){
                   if($queue['id'] == $queueP["queue_id"]){
                    $queues[$q]['selected'] = true;
                   }
                }
            }
        }

        // Mount the view
        $this->view->profiles = $this->profiles;
        $this->view->user = $user;

        $this->view->queues = $queues;

        //Define the action and load form
        $this->view->action = "edit" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );


        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();

            $dados['created'] = $user['created'];

            if (strlen($dados['password']) != 32) {
                $dados['password'] = md5($dados['password']);
            }

            // if change profile, remove permissions
            if ($user['profile_id'] != $dados['profile_id']) {
                Snep_Users_Manager::removePermission($id);
            }

            Snep_Users_Manager::edit($dados);
            Snep_Users_Manager::removeQueuesPermission($id);
            // add queue permission
            if(isset($dados['queues'])){
                foreach($dados['queues'] as $x => $queue){
                    Snep_Users_Manager::addQueuesPermission($id, $queue);
                }
            }

            //audit
            Snep_Audit_Manager::SaveLog("Updated", 'users', $id, $this->view->translate("User") . " {$id} " . $dados['name']); 
            
            $this->_redirect($this->getRequest()->getControllerName());

        }

    }

    /**
     * Remove a user
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Users"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        $this->view->id = $id;
        $this->view->remove_title = $this->view->translate('Delete User.');
        $this->view->remove_message = $this->view->translate('The user will be deleted. After that, you have no way get it back.');
        $this->view->remove_form = 'users';
        $this->renderScript('remove/remove.phtml');

        if ($this->_request->getPost()) {

            $user = Snep_Users_Manager::get($id);
            Snep_Users_Manager::removeRecovery($id);
            Snep_Users_Manager::removePermission($id);
            Snep_Users_Manager::removeQueuesPermission($id);
            Snep_Binds_Manager::removeBond($id);
            Snep_Users_Manager::remove($id);

            //audit
            Snep_Audit_Manager::SaveLog("Deleted", 'users', $id, $this->view->translate("User") . " {$id} " . $user['name']); 
            $this->_redirect($this->getRequest()->getControllerName());
        }
    }

    /**
     *  Edit permission
     */
    public function permissionAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Users"),
                    $this->view->translate("Permission")));

        $id = $this->_request->getParam('id');
        if ($id == null) {
            $id = $_POST['user'];
        }

        $profile = Snep_Permission_Manager::getIdProfile($id);
        $idProfile = (int) $profile["profile_id"];

        //Permissões do grupo
        $currentResourcesGroup = Snep_Permission_Manager::getAllPermissions($idProfile);

        //Permissões individuais do usuário
        $currentResourcesUsers = Snep_Permission_Manager::getAllPermissionsUser($id);

        // Modulos do sistema
        $modules = Snep_Permission_Manager::getAll();

        $resources = array();
        foreach ($modules as $moduleKey => $module) {
            foreach ($module as $controllerKey => $controller) {

                if (isset($controller["write"])) {
                    $controller["write"] = $controller["read"];
                }

                // Modulos de erro e login/logout não são inseridos no banco
                $valid = true;
                if ($controllerKey == 'error' || $controllerKey == "auth" || $controllerKey == "installer") {
                    $valid = false;
                }

                if ($valid == true) {
                    foreach ($controller as $actionKey => $action) {
                        $resource = $moduleKey . '_' . $controllerKey . '_' . $actionKey;
                        $label = Snep_Modules::$modules[$moduleKey]->getName() . " - " . $action;

                        if (substr($label, 0, 7) == 'Default') {
                            $label = substr_replace($label, '', 0, 10);
                        }

                        // verifica se arquivo possui opcao de escrita para montar label
                        if (substr($resource, -5) == 'write') {
                            $resources[$resource] = $this->view->translate($label) . " - " . $this->view->translate('write');
                        } else {
                            $resources[$resource] = $this->view->translate($label) . " - " . $this->view->translate('read');
                        }
                    }
                }
            }
        }
        

        $permissionsGroup = array_intersect_key($currentResourcesGroup, $resources);

        // usuário nao possui permissão individual
        if (empty($currentResourcesUsers)) {
            $this->view->user = false;
        } else {
            $permissionUser = array();

            foreach ($currentResourcesUsers as $key => $resourceUser) {
                $permissionUser[$key] = substr($resourceUser, 0, -1);
            }

            $permissionsUser = array_intersect_key($permissionUser, $resources);
            $this->view->user = true;
        }

        //verifica permissões do grupo do usuário
        $modulesAll = array();
        $cont = 0;

        // Caso possua dados de permissão do usuário, as permissões passam
        // a ser da tabela users_permission
        foreach ($resources as $key => $res) {

            $modulesAll[$cont]['id_permission'] = $key;
            $modulesAll[$cont]['name'] = $res;
            foreach ($permissionsGroup as $item => $permissionGroup) {
                if ($key == $item) {
                    $modulesAll[$cont]['group'] = true;
                }
            }
            if (!empty($permissionsUser)) {
                foreach ($permissionsUser as $item_ => $userPermission) {

                    if ($key == $item_) {
                        $modulesAll[$cont]['user'] = true;
                        foreach ($currentResourcesUsers as $try => $allow) {
                            if ($item_ == $try) {
                                $modulesAll[$cont]['allow'] = substr($allow, -1);
                            }
                        }
                    }
                }
            }
            $cont++;
        }

        $this->view->modules = $modulesAll;
        $this->view->id = $id;

        if ($this->_request->isPost()) {
            
            $dados = array();
            $dados['id'] = $_POST['user'];

            unset($_POST['user']);
            foreach ($_POST as $key => $permission_id) {
                $dados['permission_id'][$key] = $key;
            }

            $deleted = array_diff($currentResourcesGroup, $dados['permission_id']);
            $added = $dados['permission_id'];

            if (!empty($permissionUser)) {
                $deletedusers = array_diff($permissionUser, $dados['permission_id']);
                $deleted = array_merge($deleted, $deletedusers);
            }

            // Caso se exclua todas as permissões
            if (!array_key_exists('permission_id', $dados)) {
                $deleted = array_merge($currentResourcesGroup, $permissionUser);
            }

            // retirar permissão do usuário por id
            if (!empty($deleted)) {
                Snep_Permission_Manager::removePermissionUser($dados['id'], $deleted);
            }

            //adicionar permissão ao usuário por id
            if (!empty($added)) {
                Snep_Permission_Manager::addPermissionUser($dados['id'], $added);
            }

            $this->_redirect("/" . $this->getRequest()->getControllerName() . "/");
        }
    }

    /**
     *  Edit bond
     */
    public function bondAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Users"),
                    $this->view->translate("Bond")));

        $id = $this->_request->getParam('id');
        $user = Snep_Users_Manager::get($id);

        $this->view->user_name = $user['name'];
        $this->view->id = $id;

        $allPeers = Snep_Extensions_Manager::getAll();

        $exceptions = Snep_Binds_Manager::getBondException($id);
        if(!empty($exceptions)){
            foreach($exceptions as $x => $excep){
                (isset($exceptionsAll)) ? $exceptionsAll .= $excep["exception"]."," : $exceptionsAll = $excep["exception"].",";
            }
            $exceptions = substr($exceptionsAll, 0,-1);
            $this->view->exceptions = $exceptions;
        }

        $usersBond = Snep_Binds_Manager::getBond($id);
        if($usersBond){

            // Type bond
            if($usersBond[0]["type"] == 'bound'){
                $this->view->typeBond = 'checked';
            }else{
                $this->view->typeNobond = 'checked';
            }

            // Array bond
            foreach($usersBond as $key => $user){
                $selectedUsers[]['name'] = $user["peer_name"];
                foreach($allPeers as $x => $peer){
                    if($user['peer_name'] == $peer['exten']){
                        unset($allPeers[$x]);
                    }
                }
            }

            $this->view->selected = $selectedUsers;
            $this->view->peers = $allPeers;

        }else{

            $this->view->peers = $allPeers;
            $this->view->typeNobond = 'checked';

        }

        if ($this->_request->isPost()) {

            $data = $_POST;

            // removes bond extension
            Snep_Binds_Manager::removeBond($data['id']);

            //add bond extension
            if(isset($data['duallistbox_bond'])){
                foreach($data['duallistbox_bond'] as $key => $peer){

                    Snep_Binds_Manager::addBond($data['id'],$data['bound'],$peer);

                }
            }

            Snep_Binds_Manager::removeBondException($data['id']);
            // add bond exceptions
            if($data["exceptions"] != ""){
                $exceptions = explode(",", $data["exceptions"]);

                foreach($exceptions as $x => $exception){
                    if($exception != ""){
                        Snep_Binds_Manager::addBondException($data['id'],$exception);
                    }
                }
            }

            $this->_redirect("/" . $this->getRequest()->getControllerName() . "/");
        }
    }

}
