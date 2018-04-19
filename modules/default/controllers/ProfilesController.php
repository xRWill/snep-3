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
 * Controller of profiles
 * 
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 * 
 */
class ProfilesController extends Zend_Controller_Action {

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

    }

    /**
     * indexAction - List all profiles
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Profiles")));

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("profiles"), array("id", "name", "created", "updated"));

        $stmt = $db->query($select);
        $data = $stmt->fetchAll(); 

        
        $this->view->profiles = $data;
        
    }

    /**
     *  AddAction - Add profile
     */
    public function addAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Profiles"),
                    $this->view->translate("Add")));


        $usersProfiles = array();

        // Mount List users with not Profiles
        $idlast = (int)Snep_Profiles_Manager::lastId() +1;  
        $userNoprofile = Snep_Profiles_Manager::getUsersnotProfile($idlast);

        // Retira da lista de usuarios, caso o mesmo possua permissao individual
        foreach ($userNoprofile as $data) {
   
            $existPermission = Snep_Permission_Manager::existPermission($data['id']);

            if ($existPermission == false) {
                if (!isset($usersProfiles[$data['name']])) {
                    $usersAll[$data['nome']] = "{$data['nome']}" . " - (" . $data['name'] . ")";
                }
            }
        }
        
        $this->view->usersAll = $usersAll;

        //Define the action and load form
        $this->view->action = "add" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );
        
        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            
            $newId = Snep_Profiles_Manager::getName($dados['name']);

            if (count($newId) > 1) {
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }else{

                Snep_Profiles_Manager::add($dados);
                $lastId = Snep_Profiles_Manager::lastId();
                $dados['id'] = $lastId;

                if (isset($dados['duallistbox_profile'])) {

                    foreach ($dados['duallistbox_profile'] as $key => $box) {
                        $data['id'] = $lastId;
                        $data['box'] = $box;
                        $this->view->users = Snep_Users_Manager::addProfileByName($data);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'profiles', $dados['id'], $this->view->translate("Profile") . " {$dados['id']} " . $dados['name']);
                    

                $this->_redirect($this->getRequest()->getControllerName() . "/permission/id/$lastId?action=add");
            }
        }

    }

    /**
     * editAction - Edit profiles
     */
    public function editAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Profiles"),
                    $this->view->translate("Edit")));

        $id = $this->_request->getParam('id');
        $profile = Snep_Profiles_Manager::get($id);
        $this->view->profile = $profile;

        $usersProfiles = array();

        foreach (Snep_Profiles_Manager::getUsersProfiles($id) as $data) {
            $usersProfiles[$data['name']] = "{$data['name']}";
        }

        $usersAll = array();
        $userNoprofile = Snep_Profiles_Manager::getUsersnotProfile($id);

        foreach ($userNoprofile as $data) {

            // Retira da lista de usuarios, caso o mesmo possua permissao individual
            $existPermission = Snep_Permission_Manager::existPermission($data['id']);

            if ($existPermission == false) {
                if (!isset($usersProfiles[$data['name']])) {
                    $usersAll[$data['nome']] = "{$data['nome']}" . " - (" . $data['name'] . ")";
                }
            }
        }
        
        $this->view->usersAll = $usersAll;
        $this->view->userProfiles = $usersProfiles;
        //Define the action and load form
        $this->view->action = "edit" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();

            $newId = Snep_Profiles_Manager::getName($dados['name']);

            if (count($newId) > 1 && $profile['name'] != $dados['name']) {
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }else{

                $dados['created'] = $profile['created'];

                Snep_Profiles_Manager::edit($dados);

                $data = array();
                $data['id'] = $dados['id'];

                foreach($usersProfiles as $key => $userProfile){
                    Snep_Users_Manager::removeProfileByName($key);
                }
              
                if (isset($dados['duallistbox_profile'])) {

                    foreach ($dados['duallistbox_profile'] as $key => $box) {
                        $data['box'] = $box;
                        $this->view->users = Snep_Users_Manager::addProfileByName($data);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Updated", 'profiles', $data['id'], $this->view->translate("Profile") . " {$data['id']} " . $dados['name']);
                   
                $this->_redirect($this->getRequest()->getControllerName());
            }

        }

    }

    /**
     * removeAction - Remove a profile
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Profiles"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');
        $this->view->id = $id;
        $this->view->remove_title = $this->view->translate('Delete Profile.'); 
        $this->view->remove_message = $this->view->translate('The profile will be deleted. After that, you have no way get it back.'); 
        $this->view->remove_form = 'profiles'; 
        $this->renderScript('remove/remove.phtml');

        if ($this->_request->getPost()) {
        
            $members = Snep_Profiles_Manager::getUsersProfiles($_POST['id']);
            $profile = Snep_Profiles_Manager::get($_POST['id']);

            // migra membros para o profile default
            foreach ($members as $item => $member) {
                Snep_Profiles_Manager::migration($member);
            }

            Snep_Profiles_Manager::removePermission($_POST['id']);
            Snep_Profiles_Manager::remove($_POST['id']);

            //audit
            Snep_Audit_Manager::SaveLog("Deleted", 'profiles', $_POST['id'], $this->view->translate("Profile") . " {$_POST['id']} " . $profile['name']);
                   
            $this->_redirect($this->getRequest()->getControllerName());
        }
    }

    /**
     *  permissionAction - Add permissions to profile
     */
    public function permissionAction() {
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Profile"),
                    $this->view->translate("Permission")));

        // verifica action add. Caso sim envia em seguida para permissões
        $actionAdd = false;
        if (isset($_GET["action"])) {
            $actionAdd = true;
        }

        $id = $this->_request->getParam('id');

        $profile = Snep_Profiles_Manager::get($id);
        $this->view->profile_name = $profile['name'];
        

        $currentResources = Snep_Permission_Manager::getAllPermissions($id);

        $modules = Snep_Permission_Manager::getAll();

        $resources = array();
        $selected = array();

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

                        // verifica se modulo possui opcao de escrita para montar label
                        if (substr($resource, -5) == 'write') {
                            $resources[$resource] = $this->view->translate($label) . " - ". $this->view->translate('write');
                        } else {
                            $resources[$resource] = $this->view->translate($label) . " - ". $this->view->translate('read');
                        }

                        if (array_search($resource, $currentResources) !== FALSE)
                            $selected[$resource] = $label;
                    }
                }
            }
        }
        
        $questionValid = new Zend_Validate_InArray(array('Yes'));
        $questionValid->setMessage('Yes is required!');

        $this->view->objSelectBox = "permissions";

        foreach ($selected as $key => $item) {

            if (substr($key, -5) == 'write') {
                $label = $this->view->translate($item) . " - ". $this->view->translate('write') ;
            } else {
                $label = $this->view->translate($item) . " - ". $this->view->translate('read') ;
            }
            $selected[$key] = $label;
        }

        // verifica itens na edicao
        foreach ($resources as $cont => $resource) {
            foreach ($selected as $key => $item) {
                if ($cont == $key) {
                    unset($resources[$cont]);
                }
            }
        }
        
        $this->view->resources = $resources;
        $this->view->selected = $selected;

        // After Post
        if ($this->getRequest()->getPost()) {
            
            $dados = $this->_request->getParams();

            $update = array();
            $permissions = Snep_Permission_Manager::getPermissions($id);

            foreach ($resources as $key => $value) {

                if (isset($dados['duallistbox_permission'])) {
                    if (array_search($key, $dados['duallistbox_permission']) !== FALSE) {
                        $update[$key] = true;
                    } else {
                        $update[$key] = false;
                    }
                }
            }
            
            // Remove permission
            Snep_Permission_Manager::removePermissionProfile($id);

            foreach($dados['duallistbox_permission'] as $key => $permission){
                Snep_Permission_Manager::addPermissionOld($permission, $id);     
            }

            $this->_redirect($this->getRequest()->getControllerName());
            
        }

    }

}

?>