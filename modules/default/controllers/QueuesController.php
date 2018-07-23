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
 * Queues Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class QueuesController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */

    public function init() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        $this->language = Zend_Registry::get('config')->system->language;
        $this->path_sounds = Zend_Registry::get('config')->system->path->asterisk->sounds."/".$this->language ;


        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array_keys($sections->toArray());
        $this->section = array();
        foreach ($_section as $value) {
            $this->section[$value] = $value;
        }
        $this->strategies = array('ringall' => $this->view->translate('For all agents available (ringall)'),
                        'roundrobin' => $this->view->translate('Search for a available agent (roundrobin)'),
                        'leastrecent' => $this->view->translate('For the agent idle for the most time (leastrecent)'),
                        'random' => $this->view->translate('Randomly (random)'),
                        'fewestcalls' => $this->view->translate('For the agent that answerd less calls (fewestcalls)'),
                        'rrmemory' => $this->view->translate('Equally (rrmemory)'));

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    /**
     * indexAction - List all Queues
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Queues")));

        $db = Zend_Registry::get('db');

        $select = "SELECT `queues`.*,COALESCE(COUNT(uniqueid),0) as members FROM `queues` left join `queue_members` on queues.name = queue_members.queue_name group by queues.name";

        $stmt = $db->query($select);
        $queues = $stmt->fetchAll();
        
        $this->view->queues = $queues;

    }

    /**
     *  AddAction - Add Queue
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Queues"),
                    $this->view->translate("Add Queues")));


        $this->view->sounds = Snep_SoundFiles_Manager::getSounds(true);

        // Music on Hold available
        $musiconhold = "";
        foreach($this->section as $key => $session){
            $musiconhold .= "<option value='".$key . "'>".$session." </option>\n";
        }
        $this->view->musiconhold = $musiconhold;

        // Queue Stratgies available
        $strategy = "";
        foreach($this->strategies as $key => $strateg){
            $strategy .=  "<option value='".$key . "'>".$strateg." </option>\n";

        }
        $this->view->strategy = $strategy;
        $this->view->ringinuseFalse = "checked";

        //Define the action and others and load form
        $this->view->action = "add" ;
        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        // After POST
        if ($this->_request->getPost()) {

            $dados = array('name' => $_POST['name'],
                'musiconhold' => $_POST['musiconhold'],
                'announce' => $_POST['announce'],
                'context' => $_POST['context'],
                'timeout' => $_POST['timeout'],
                'queue_youarenext' => $_POST['queue_youarenext'],
                'queue_thereare' => $_POST['queue_thereare'],
                'queue_callswaiting' => $_POST['queue_callswaiting'],
                'queue_thankyou' => $_POST['queue_thankyou'],
                'announce_frequency' => $_POST['announce_frequency'],
                'retry' => $_POST['retry'],
                'wrapuptime' => $_POST['wrapuptime'],
                'maxlen' => $_POST['maxlen'],
                'servicelevel' => $_POST['servicelevel'],
                'strategy' => $_POST['strategy'],
                'joinempty' => $_POST['joinempty'],
                'leavewhenempty' => $_POST['leavewhenempty'],
                'reportholdtime' => $_POST['reportholdtime'],
                'memberdelay' => $_POST['memberdelay'],
                'weight' => $_POST['weight'],
                'ringinuse' => $_POST['ringinuse'],
            );

            $form_isValid = true;

            $newId = Snep_Queues_Manager::getName($_POST['name']);

            if (count($newId) > 1) {
                $form_isValid = false;
                $message = $this->view->translate("Name already exists.");
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }

            if ($form_isValid) {

                $id = Snep_Queues_Manager::add($dados);

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'queues', $id, $this->view->translate("Queues") . " " . $_POST['name']);
                
                $this->_redirect($this->getRequest()->getControllerName());
            }
        }

    }

    /**
     * editAction - Edit Queues
     */
    public function editAction() {

        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam("id");

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Queues"),
                    $this->view->translate("Edit")));

        $queue = Snep_Queues_Manager::get($id);

        $this->view->queue = $queue;

        $this->view->sounds = Snep_SoundFiles_Manager::getSounds(true);

        // Music On Hold available x registered
        $musiconhold = "";
        foreach($this->section as $key => $session){
            $musiconhold .= ($key == $queue['musiconhold']) ? "<option value='".$key . "' selected >".$session." </option>\n": "<option value='".$key . "'>".$session." </option>\n";
        }

        $this->view->musiconhold = $musiconhold;

        // Queue strategy available x registered
        $strategy = "";
        foreach($this->strategies as $key => $strateg){
            $strategy .= ($key == $queue['strategy']) ? "<option value='".$key . "' selected >".$strateg." </option>\n": "<option value='".$key . "'>".$strateg." </option>\n";
        }

        $this->view->strategy = $strategy;

        // Others queue definitions
        if($queue['joinempty'] == "no"){
          $this->view->joinempty_no = "checked";
        }elseif($queue['joinempty'] == "yes"){
          $this->view->joinempty_yes = "checked";
        }else{
          $this->view->joinempty_strict = "checked";
        }

        if($queue['leavewhenempty'] == "1"){
            $this->view->leavewhenemptyTrue = "checked";
        }else{
            $this->view->leavewhenemptyFalse = "checked";
        }

        if($queue['ringinuse'] == "1"){
            $this->view->ringinuseTrue = "checked";
        }else{
            $this->view->ringinuseFalse = "checked";
        }

        if($queue['reportholdtime'] == "1"){
            $this->view->reportholdtimeTrue = "checked";
        }else{
            $this->view->reportholdtimeFalse = "checked";
        }

        //Define the action and load form
        $this->view->action = "edit" ;
        $this->view->disabled = "disabled";

        $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

        // After POST
        if ($this->_request->getPost()) {

            $dados = array('name' => $queue['name'],
                'musiconhold' => $_POST['musiconhold'],
                'announce' => $_POST['announce'],
                'context' => $_POST['context'],
                'timeout' => $_POST['timeout'],
                'queue_youarenext' => $_POST['queue_youarenext'],
                'queue_thereare' => $_POST['queue_thereare'],
                'queue_callswaiting' => $_POST['queue_callswaiting'],
                'queue_thankyou' => $_POST['queue_thankyou'],
                'announce_frequency' => $_POST['announce_frequency'],
                'retry' => $_POST['retry'],
                'wrapuptime' => $_POST['wrapuptime'],
                'maxlen' => $_POST['maxlen'],
                'servicelevel' => $_POST['servicelevel'],
                'strategy' => $_POST['strategy'],
                'joinempty' => $_POST['joinempty'],
                'leavewhenempty' => $_POST['leavewhenempty'],
                'reportholdtime' => $_POST['reportholdtime'],
                'memberdelay' => $_POST['memberdelay'],
                'weight' => $_POST['weight'],
                'ringinuse' => $_POST['ringinuse'],
            );


            Snep_Queues_Manager::edit($dados);
            
            //audit
            Snep_Audit_Manager::SaveLog("Updated", 'queues', $dados['id'], $this->view->translate("Queue") . " " . $queue['name']);
            
            $this->_redirect($this->getRequest()->getControllerName());

        }

    }

    /**
     * removeAction - Remove a queue
     */
    public function removeAction() {

         $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Queue"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        // check if the queues is used in the rule or have members
        $exten_members = Snep_Queues_Manager::getValidationPeers($id);
        $agent_members = Snep_Queues_Manager::getValidationAgent($id);
        $info = Snep_Queues_Manager::get($id);

        if (count($exten_members) > 0 || count($agent_members) > 0) {
            $msg = $this->view->translate("The following members make use of this queue, remove before deleting:") . "<br />\n";

            if (count($exten_members) > 0) {

                foreach ($exten_members as $membros) {
                    $member = explode("/", $membros['membername']);
                    $member = $member[1];
                    $msg .= $this->view->translate("Extension:") . $member . "<br/>\n";
                }
            }

            if (count($agent_members) > 0) {

                foreach ($agent_members as $member_agent) {
                    $msg .= $this->view->translate("Agent:") . $member_agent['agent_id'] . "<br/>\n";
                }
            }
            $error = true;
            $this->view->error_message = $msg;
            $this->renderScript('error/sneperror.phtml');
        }

        $regras = Snep_Queues_Manager::getValidation($id);
        if (count($regras) > 0) {
            $error = true;
            $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this queues: ") . "<br />";
            foreach ($regras as $regra) {

                $this->view->error_message .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }
            $this->renderScript('error/sneperror.phtml');

        } elseif(!$error) {

            $this->view->id = $id;
            $this->view->name = $info['id'];
            $this->view->remove_title = $this->view->translate('Delete Queue.');
            $this->view->remove_message = $this->view->translate('The queue will be deleted. After that, you have no way get it back.');
            $this->view->remove_form = 'queues';
            $this->renderScript('remove/remove.phtml');

            if ($this->_request->getPost()) {
                
                $queue = Snep_Queues_Manager::get($_POST['id']);
                
                Snep_Queues_Manager::removeUserPermission($_POST['name']);
                Snep_Queues_Manager::removeQueuePeers($_POST['id']);
                Snep_Queues_Manager::remove($_POST['id']);
                Snep_Queues_Manager::removeQueues($_POST['id']);

                 
                

                //audit
                Snep_Audit_Manager::SaveLog("Deleted", 'queues', $id, $this->view->translate("Queues") . " " . $queue['name']);

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
    }

    /**
     * membersAction - Set member queue
     */
    public function membersAction() {

        $queue = $this->_request->getParam("id");

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Queues"),
                    $this->view->translate("Members")));

        $members = Snep_Queues_Manager::getMembers($queue);
        $mem = array();
        foreach ($members as $m) {
            $mem[$m['interface']] = $m['interface'];
        }

        $_allMembers = Snep_Queues_Manager::getAllMembers();
        $notMem = array();
        foreach ($_allMembers as $row) {
            $cd = explode(";", $row['canal']);
            foreach ($cd as $canal) {
                if (strlen($canal) > 0) {
                    if (!array_key_exists($canal, $mem)) {
                        $notMem[$canal] = $row['callerid'] . " ($canal)";
                    }
                }
            }
        }

        $this->view->notMembers = $notMem;
        $this->view->members = $mem;

        if ($this->_request->getPost()) {

            Snep_Queues_Manager::removeAllMembers($queue);

            if (isset($_POST['duallistbox_group'])) {

                foreach ($_POST['duallistbox_group'] as $add) {
                    Snep_Queues_Manager::insertMember($queue, $add);
                }
            }

            $this->_redirect($this->getRequest()->getControllerName() . '/');
        }
    }

}
