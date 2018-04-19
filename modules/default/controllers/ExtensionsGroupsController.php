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
 * Extension Group Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ExtensionsGroupsController extends Zend_Controller_Action {

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
     * indexAction - List all Extensions groups
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Extensions Groups")));

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('groups' => 'core_groups'),
                       array('groups.id','groups.name','count(peer_groups.group_id) as qt_peers'))
                ->joinLeft(array('peer_groups'=>'core_peer_groups'),
                           'groups.id = peer_groups.group_id',array())
                ->group(array('id'));

        $stmt = $db->query($select);
        $data = $stmt->fetchAll();

        $this->view->extensionsgroups = $data;

    }

    /**
     * addAction - Adds a group and their extensions in the database
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Extensions Group"),
                    $this->view->translate("Add")));

        // Mount array with extensions and your groups
        $allExtensions = Snep_ExtensionsGroups_Manager::getExtensionsAll();
        $extensions = array() ;
        foreach ($allExtensions as $key => $value) {
            if ( ! array_key_exists($value['peer_id'],$extensions)) {
                $extensions[$value['peer_id']] = array('group_name' => $value['group_name'],
                'name' => $value['name']) ;
            } else {
                $extensions[$value['peer_id']]['group_name'] .= ", " . $value['group_name'];
            }
        }
        $groupExtensions = array() ;
        foreach ($extensions as $key => $value) {
            array_push($groupExtensions,array('peer_id' => $key,
                'name' => $value['name'],
                'group_name' => $value['group_name']));
        }

        //Define the action and load form
        $this->view->action = "add" ;
        $this->view->noGroupExtensions = $groupExtensions;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        // After Post
        if ($this->getRequest()->getPost()) {

            $dados = $this->_request->getParams();
            $newId = Snep_ExtensionsGroups_Manager::getName($dados['name']);
            $form_isValid = true;

            if (count($newId) > 1) {
                $form_isValid = false;
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }

            if ($form_isValid) {
                $group = array( 'name' => $dados['name']);
                $groupId = Snep_ExtensionsGroups_Manager::addGroup($group);

                if ($dados['duallistbox_group'] && $group) {

                    foreach ($dados['duallistbox_group'] as $id => $peer_id) {

                        $extensionsGroup = array('group_id' => $groupId,
                                                 'peer_id' => $peer_id
                                                 );

                        Snep_ExtensionsGroups_Manager::addExtensionsGroup($extensionsGroup);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'core_groups', $groupId, $this->view->translate("Extensions Group") . " {$groupId} " . $dados['name']);

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }

    }

    /**
     * editAction - Edit extensions groups
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Extensions Group"),
                    $this->view->translate("Edit")));

        $id = $this->_request->getParam('id');

        $group = Snep_ExtensionsGroups_Manager::get($id);

        // Extensions for this group;
        $groupExtensions = Snep_ExtensionsGroups_Manager::getExtensionsGroup($id) ;
        // Extensions not in this group
        $noGroupExtensions = Snep_ExtensionsGroups_Manager::getExtensionsNoGroup($id) ;

        // Remove itens in noGroupExtensions that in groupExtensions
        foreach ($groupExtensions as $key => $value) {
            $groups = "" ;
            foreach ($noGroupExtensions as $no_key => $no_value) {
                if ($no_value['peer_id'] ===  $value['peer_id']) {
                    $groups .= ", ".$no_value['group_name'] ;
                    unset($noGroupExtensions[$no_key]);
                }
            }
            $groupExtensions[$key]['group_name'] = " ( ".substr($groups,1)." )" ;
        }
        // Adjust peer and yoor groups in only array key
        $extensions = array();
        foreach ($noGroupExtensions as $key => $value) {
            if ( ! array_key_exists($value['peer_id'],$extensions)) {
                $extensions[$value['peer_id']] = array('group_name' => $value['group_name'],
                'name' => $value['name']) ;
            } else {
                $extensions[$value['peer_id']]['group_name'] .= ", " . $value['group_name'];
            }
        }
        unset($noGroupExtensions);
        $noGroupExtensions = array() ;
        foreach ($extensions as $key => $value) {
            array_push($noGroupExtensions,array('peer_id' => $key,
                'name' => $value['name'],
                'group_name' => $value['group_name']));
        }

        //Define the action and load form
        $this->view->action = "edit" ;
        $this->view->group = $group;
        $this->view->noGroupExtensions = $noGroupExtensions;
        $this->view->groupExtensions = $groupExtensions;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        if ($this->_request->getPost()) {

            $form_isValid = true;

            $dados = $this->_request->getParams();

            $newId = Snep_ExtensionsGroups_Manager::getName($dados['name']);

            if (count($newId) > 1 && $dados['name'] != $group['name']) {
                $form_isValid = false;
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }

            if($form_isValid){

                // Update core_groups
                $group = array('id' => $dados['id'], 'name' => $dados['name']);
                $result = Snep_ExtensionsGroups_Manager::editGroup($group);

                // Update core_peer_gropups
                if ($result) {
                    $old_members = $groupExtensions ;
                    $new_members = array();
                    foreach($dados['duallistbox_group'] as $key => $val ){
                        $new_members[$val] = "" ;
                    };

                    Snep_ExtensionsGroups_Manager::updateExtensionsGroup($id, $old_members,$new_members);

                }

                //audit
                Snep_Audit_Manager::SaveLog("Updated", 'core_groups', $dados['id'], $this->view->translate("Extensions Group") . " {$dados['id']} " . $dados['name']);
                $this->_redirect($this->getRequest()->getControllerName());
            }
        }

    }

    /**
     * removeAction - Remove a Extensions Group
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Extensions Group"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        //checks if the group is used in the rule
        $regras = Snep_ExtensionsGroups_Manager::getValidation($id);

        if (count($regras) > 0) {

            $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this extensions group: ") . "<br />";
            foreach ($regras as $regra) {
                $this->view->error_message .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }

            $this->renderScript('error/sneperror.phtml');
        } else {

            $extensions = Snep_ExtensionsGroups_Manager::getExtensionsOnlyGroup($id);
            if (count($extensions) > 0) {
                $this->_redirect($this->getRequest()->getControllerName() . '/migration/id/' . $id);
            } else {
                $this->view->message = $this->view->translate("The extension group will be deleted. Are you sure?.");
            }
            $this->view->id = $id;
            $this->view->remove_title = $this->view->translate('Delete Extension Group.');
            $this->view->remove_message = $this->view->translate('The extension group will be deleted. After that, you have no way get it back.');
            $this->view->remove_form = 'extensions-groups'; 
            $this->renderScript('remove/remove.phtml');

            if ($this->_request->getPost()) {
                $id = $_POST['id'];
                $extensions_all  = Snep_ExtensionsGroups_Manager::getExtensionsGroup($id);
                foreach($extensions_all as $key => $value){
                    Snep_ExtensionsGroups_Manager::deleteGroupExtensions(array('peer_id' => $value,  'group_id' => $id));
                }
                $dados = Snep_ExtensionsGroups_Manager::get($id);
                Snep_ExtensionsGroups_Manager::delete($id);

                //audit
                Snep_Audit_Manager::SaveLog("Deleted", 'core_groups', $id, $this->view->translate("Extensions Group") . " {$id} " . $dados['name']);
                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
    }

    /**
     * migrationAction - Migrate extensions to other Extensions Group
     */
    public function migrationAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Extensions Group"),
                    $this->view->translate("Migrate")));

        $id = $this->_request->getParam('id');

        $allGroups = Snep_ExtensionsGroups_Manager::getAll();
        foreach ($allGroups as $key => $value) {
            if ($value['id'] === $id) {
                unset($allGroups[$key]);
            }
        }

        if (isset($allGroups)) {

            $this->view->groups = $allGroups;

        } else {

            $this->view->error_message = "This is the only group and it has extensions associated. You can migrate these extensions to a new group.";
            $this->renderScript('error/sneperror.phtml');
        }

        $this->view->id = $id;

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            $extensions_all  = Snep_ExtensionsGroups_Manager::getExtensionsGroup($dados['group_id']);
            $extensions_uniq = Snep_ExtensionsGroups_Manager::getExtensionsOnlyGroup($dados['group_id']);

            foreach($extensions_uniq as $key => $value){
                Snep_ExtensionsGroups_Manager::addExtensionsGroup(array('peer_id' => $value, 'group_id' => $dados['new_group']));
            }
            foreach($extensions_all as $key => $value){
                Snep_ExtensionsGroups_Manager::deleteGroupExtensions(array('peer_id' => $value,  'group_id' => $dados['group_id']));
            }

            $dados = Snep_ExtensionsGroups_Manager::get($id);
            Snep_ExtensionsGroups_Manager::delete($dados['group_id']);
            
            //audit
            Snep_Audit_Manager::SaveLog("Deleted", 'core_groups', $id, $this->view->translate("Extensions Group") . " {$id} " . $dados['name']);
            $this->_redirect($this->getRequest()->getControllerName());
        }

    }

}
