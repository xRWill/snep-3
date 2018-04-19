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
 * Contact Groups Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ContactGroupsController extends Zend_Controller_Action {

   /**
     * Initial settings of the class
     */
     public function init() {
        
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    /**
     * List all Contact Groups
     */
    public function indexAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contact Group")));

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from("contacts_group");

        $stmt = $db->query($select);
        $contactsGroup = $stmt->fetchAll();  

        $this->view->contactgroups = $contactsGroup;

    }

    /**
     * Add a new Contact Group
     */
    public function addAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contact Group"),
                    $this->view->translate("Add")));

        $db = Zend_Registry::get('db');

        try {
            $sql = "SELECT c.id as id, c.name as name, g.name as `group` FROM contacts_names as c, contacts_group as g  WHERE (c.group = g.id) ";
            $contacts_result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            
        }
        $contact = array();
        foreach ($contacts_result as $key => $val) {
            $contact[$val['id']] = $val['name'] . " (" . $val['group'] . ")";
        }

        $this->view->noGroupContacts = $contact;
        
        //Define the action and load form
        $this->view->action = "add" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        if ($this->_request->getPost()) {
            
            $form_isValid = true;
            $dados = $this->_request->getParams();
            
            $newId = Snep_ContactGroups_Manager::getName($dados['group']);

            if (count($newId) > 1) {
                $form_isValid = false;

                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }
            
            if ($form_isValid) {
                $groupId = Snep_ContactGroups_Manager::add(array('group' => $dados['group']));

                if ($dados['duallistbox_group']) {
                    foreach ($dados['duallistbox_group'] as $id => $idContact) {
                        Snep_ContactGroups_Manager::insertContactOnGroup($groupId, $idContact);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'contacts_group', $groupId, $this->view->translate("Contacts Group") . " {$groupId} " . $dados['group']);

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
        
    }

    /**
     * Edit a Contact Group
     */
    public function editAction() {
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contact Group"),
                    $this->view->translate("Edit")));

        $id = $this->_request->getParam('id');
        $group = Snep_ContactGroups_Manager::get($id);
        
        $groupContacts = array();
        foreach (Snep_ContactGroups_Manager::getGroupContacts($id) as $contact) {
            $groupContacts[$contact['id']] = "{$contact['name']} ({$contact['group']})";
        }

        $noGroupContacts = array();
        foreach (Snep_Contacts_Manager::getAll() as $contact) {
            if (!isset($groupContacts[$contact['id']])) {
                $noGroupContacts[$contact['id']] = "{$contact['name']} ({$contact['groupName']})";
            }
        }
        
        $this->view->group = $group['name'];
        $this->view->noGroupContacts = $noGroupContacts;
        $this->view->groupContacts = $groupContacts;

        //Define the action and load form
        $this->view->action = "edit" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );
        
        if ($this->_request->getPost()) {

            $form_isValid = true;

            $dados = $this->_request->getParams();

            $newId = Snep_ContactGroups_Manager::getName($dados['group']);

            if (count($newId) > 1 && $dados['group'] != $group['name']) {
                $form_isValid = false;
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }

            if ($form_isValid) {

                $groupId = Snep_ContactGroups_Manager::edit(array('group' => $dados['group'], 'id' => $dados['id']));
                $members = Snep_Contacts_Manager::getMember($dados['id']);

                //audit
                Snep_Audit_Manager::SaveLog("Updated", 'contacts_group', $groupId, $this->view->translate("Contacts Group") . " {$groupId} " . $dados['group']);


                // remove contacts of group and insert in default group
                if ($groupContacts) {
                    foreach ($groupContacts as $id => $idContact) {

                        Snep_ContactGroups_Manager::removeContactOnGroup($id);
                    }
                }

                // add contacts
                if ($dados['duallistbox_group']) {
                    foreach ($dados['duallistbox_group'] as $id => $idContact) {

                        Snep_ContactGroups_Manager::insertContactOnGroup($dados['id'], $idContact);
                    }
                }
                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
        
    }

    /**
     * Remove a Contact Group
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contact Group"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');
        $this->view->id = $id;

        //checks if the group is used in the rule
        $regras = Snep_ContactGroups_Manager::getValidation($id);

        if (count($regras) > 0) {

            $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this contact group: ") . "<br />";
            foreach ($regras as $regra) {
                $this->view->error_message .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }

            $this->renderScript('error/sneperror.phtml');

        } else {

            $contacts = Snep_ContactGroups_Manager::getGroupContacts($id);
            
            if (count($contacts) > 0) {
                $this->_redirect('default/contact-groups/migration/id/' . $id);
            } else {
                $this->view->message = $this->view->translate("The group will be removed. After that you can't go back.");
            }

            $this->view->id = $id;
            $this->view->remove_title = $this->view->translate('Delete Contacts Group.'); 
            $this->view->remove_message = $this->view->translate('The contacts group will be deleted. After that, you have no way get it back.'); 
            $this->view->remove_form = 'contact-groups'; 
            $this->renderScript('remove/remove.phtml');

            if ($this->_request->getPost()) {
                
                $group = Snep_ContactGroups_Manager::get($_POST['id']);
                Snep_ContactGroups_Manager::remove($_POST['id']);

                //audit
                Snep_Audit_Manager::SaveLog("Deleted", 'contacts_group', $_POST['id'], $this->view->translate("Contacts Group") . " {$_POST['id']} " . $group['name']);

                $this->_redirect('default/contact-groups/');
            }

            
        }
    }

    /**
     * Migrate contacts to other Contact Group
     */
    public function migrationAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contact Group"),
                    $this->view->translate("Migrate")));

        $id = $this->_request->getParam('id');
        $this->view->id = $id;

        $_allGroups = Snep_ContactGroups_Manager::getAll();
        foreach ($_allGroups as $group) {
            if ($group['id'] != $id) {
                $allGroups[$group['id']] = $group['name'];
            }
        }

        if (isset($allGroups)) {

            $this->view->groups = $allGroups;
            
        } else {

            $this->view->error_message = "This is the only group and it has contacts associated. You can migrate these contacts to a new group.";
            $this->renderScript('error/sneperror.phtml');
        }

        $this->view->message = $this->view->translate("The excluded group has associated contacts.");

        
        if ($this->_request->getPost()) {

            if ($_POST['option'] == 'migrate') {

                $contacts = Snep_ContactGroups_Manager::getGroupContacts($_POST['id']);
                $groupId = Snep_ContactGroups_Manager::getName($_POST['group']);


                foreach ($contacts as $contact) {
                    Snep_ContactGroups_Manager::insertContactOnGroup($groupId["id"], $contact['id']);
                }

                Snep_ContactGroups_Manager::remove($_POST['id']);
            
            } elseif ($_POST['option'] == 'remove') {

                $contacts = Snep_ContactGroups_Manager::getGroupContacts($_POST['id']);

                foreach ($contacts as $contact) {
                    Snep_Contacts_Manager::removePhone($contact['id']);
                }

                Snep_Contacts_Manager::removeGroup($contact['idGroup']);
                Snep_ContactGroups_Manager::remove($_POST['id']);
            }

            //audit
            $group = Snep_ContactGroups_Manager::get($_POST['id']);
            Snep_Audit_Manager::SaveLog("Deleted", 'contacts_group', $_POST['id'], $this->view->translate("Contacts Group") . " {$_POST['id']} " . $group['name']);

            $this->_redirect($this->getRequest()->getControllerName());
        }  
    }
}
