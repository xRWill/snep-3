<?php

/**
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
 * Pickup groups Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class PickupGroupsController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
     public function init() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        // Add Dashboard button
        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
                                              Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
                                              Zend_Controller_Front::getInstance()->getRequest()->getActionName());        

        $this->extensionsAll = Snep_PickupGroups_Manager::getExtensionsAll();
    }

    /**
     * indexAction - List pickup groups
     */
    public function indexAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Pickup Groups")));

        $pickupGroups = Snep_PickupGroups_Manager::getAllMembers();
        
        $this->view->pickupgroups = $pickupGroups;
    
    }

    /**
     * addAction - Add pickup groups
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Pickup Groups"),
                    $this->view->translate("Add")));

        //All extensions 
        $groupAllExtensions = array();
        foreach ($this->extensionsAll as $data) {
            
            array_push($groupAllExtensions,array('name' => $data['name'], 
                'pickupgroup' => $data['pickupgroup'],
                'group_name' => $data['nome']));
                
        }


        $this->view->extensionsAll = $groupAllExtensions;
        
        //Define the action and load form
        $this->view->action = "add" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        // After POST
        if ($this->getRequest()->getPost()) {
            
            $form_isValid = true;
            $dados = $this->_request->getParams();

            $newId = Snep_PickupGroups_Manager::getName($dados['name']);

            if (count($newId) > 1) {
                $form_isValid = false;
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
                
            }

            if ($form_isValid) {
                
                $namegroup = array('nome' => $dados['name']);
                $groupId = Snep_PickupGroups_Manager::addGroup($namegroup);

                if ($dados['duallistbox_group'] && $groupId > 0) {

                    foreach ($dados['duallistbox_group'] as $id => $extensions) {
                        $extensionsGroup = array('pickupgroup' => $groupId,
                                                 'extensions' => $extensions
                                                 );

                        Snep_PickupGroups_Manager::addExtensionsGroup($extensionsGroup);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'grupos', $groupId, $this->view->translate("Pickup Group") . " {$groupId} " . $dados['name']);
                    
                $this->_redirect($this->getRequest()->getControllerName());
            }
        }

    }

    /**
     * editAction - Edit pickup groups
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Pickup Groups"),
                    $this->view->translate("Edit")));

        // Data about group
        $id = $this->_request->getParam('id');
        $group = Snep_PickupGroups_Manager::get($id);
        $this->view->group = $group;

        $groupExtensions = Snep_PickupGroups_Manager::getExtensionsOnlyGroup($id);

        // All extensions not pertence for this group
        $groupAllExtensions = array();
        foreach ($this->extensionsAll as $data) {
            $_ingroup = false ;
            foreach ($groupExtensions as $value) {
                if ($value['pickupgroup'] === $data['pickupgroup']) {
                    $_ingroup = true ;
                    break;
                }
            }
            if (!$_ingroup) {
                array_push($groupAllExtensions,array('name' => $data['name'], 
                    'pickupgroup' => $data['pickupgroup'],
                    'group_name' => $data['nome']));
            }    
        }

        $this->view->extensionsAll = $groupAllExtensions;
        $this->view->groupExtensions = $groupExtensions;
        
        //Define the action and load form
        $this->view->action = "edit" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        if ($this->_request->getPost()) {
            $form_isValid = true;
            $dados = $this->_request->getParams();
            $dados['id'] = $id;

            $newId = Snep_PickupGroups_Manager::getName($dados['name']);

            if (count($newId) > 1 && $dados['name'] != $group['nome']) {
                $form_isValid = false;
                $this->view->translate = $this->view->translate('Name already exists.');
            }

            if($form_isValid){
                
                Snep_PickupGroups_Manager::editGroup(array('name' => $dados['name'], 'id' => $dados['id']));

                /* Remove all extensions of group */
                if (isset($groupExtensions)) {

                    foreach ($groupExtensions as $key => $val) {
                        Snep_PickupGroups_Manager::addExtensionsGroup(array('extensions' => $val['name'], 'pickupgroup' => NULL));
                    }
                }

                /* Add pickupgroup in exten */ 
                if (isset($dados["duallistbox_group"])) {

                    foreach ($dados['duallistbox_group'] as $key => $val) {
                        $this->view->extensions = Snep_PickupGroups_Manager::addExtensionsGroup(array('extensions' => $val, 'pickupgroup' => $dados['id']));
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Updated", 'grupos', $dados['id'], $this->view->translate("Pickup Group") . " {$dados['id']} " . $dados['name']);

                $this->_redirect($this->getRequest()->getControllerName());

            }
        }
        
    }

    /**
     * removeAction - Delete pickup groups
     * @throws Zend_Controller_Action_Exception
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Pickup Group"),$this->view->translate("Delete")));

        $id = mysql_escape_string($this->getRequest()->getParam('id'));
        $this->view->id = $id;
        $rules = Snep_PickupGroups_Manager::getValidation($id);

        if (count($rules) > 0) {
            $error = true;
            $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this pickup group: ") . "<br />";
            foreach ($rules as $rule) {
                $this->view->error_message .= $rule['id'] . " - " . $rule['desc'] . "<br />\n";
            }
            $this->renderScript('error/sneperror.phtml');
        }elseif(!$error){

            $this->view->remove_title = $this->view->translate('Delete Pickup Group.'); 
            $this->view->remove_message = $this->view->translate('The pickup group will be deleted. After that, you have no way get it back.'); 
            $this->view->remove_form = 'pickup-groups'; 
            $this->renderScript('remove/remove.phtml');

            if ($this->_request->getPost()) {

                try {
                    $pickugroups = Snep_PickupGroups_Manager::get($_POST['id']);
                } catch (PBX_Exception_NotFound $ex) {
                    throw new Zend_Controller_Action_Exception('Page not found.', 404);
                }

                $dados = Snep_PickupGroups_Manager::get($_POST['id']);
                Snep_PickupGroups_Manager::delete($_POST['id']);

                //audit
                Snep_Audit_Manager::SaveLog("Deleted", 'grupos', $dados['cod_grupo'], $this->view->translate("Pickup Group") . " {$dados['cod_grupo']} " . $dados['nome']);

                $this->_redirect($this->getRequest()->getControllerName());
    
            }
        }
    }

}
