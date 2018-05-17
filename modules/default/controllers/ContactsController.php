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
include ("includes/functions.php");
/**
 * Contacts Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ContactsController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
    public function init() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        // Load groups and marks the already registered contact group
        $this->allGroups = Snep_ContactGroups_Manager::getAll();

        // States
        $this->states = Snep_Contacts_Manager::getStates();

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    /**
     * List all Contacts
     */
    public function indexAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contacts")));
        
        $config = Zend_Registry::get('config');
        $lineNumber = $config->ambiente->linelimit;

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("n" => "contacts_names"), array("id as ide", "name as nome", "id_city", "id_state", "cep"))
                ->join(array("g" => "contacts_group"), 'n.group = g.id')
                ->join(array("p" => "contacts_phone"), 'n.id = p.contact_id')
                ->joinLeft(array("s" => "core_cnl_state"), 'n.id_state = s.id', "name as state")
                ->joinLeft(array("c" => "core_cnl_city"), 'n.id_city = c.id', "name as city")
                ->group("n.id");
        
        $stmt = $db->query($select);
        $contacts = $stmt->fetchAll();        

        $formatter = new Formata();
        
        $this->view->formatter = $formatter;
        $this->view->contacts = $contacts;       
    }

    /**
     *  Add Contact
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contacts"),
                    $this->view->translate("Add")));

        $this->view->contact_id = Snep_Contacts_Manager::getLastId();

        foreach ($this->allGroups as $key=>$val) {
            $this->allGroups[$key]['selected'] = "" ;
        }

        $this->allGroups['0']['selected']='selected';
        $this->view->contact_groups = $this->allGroups;
        $this->view->states = $this->states;
        $this->view->action = 'add';

        $this->renderScript('contacts/addedit.phtml');


        // After POST
        if ($this->_request->getPost()) {
            
            $dados = $_POST;    
            $dados['id'] = Snep_Contacts_Manager::getLastId();

            $form_isValid = true;

            if (empty($dados['group'])) {
                $this->view->error_message = $this->view->translate('No group selected');
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }

            if (Snep_Contacts_Manager::get($dados['id'])) {
                $this->view->error_message = $this->view->translate('Code already exists');
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }

            if ($form_isValid) {    

                $rm = array(".","-");
                $dados['zipcode'] = str_replace($rm, "", $dados['zipcode']);

                if($dados['state'] == "0"){
                    $dados['state'] = null;
                    $dados['city'] = null;
                }

                Snep_Contacts_Manager::add($dados);
                if($dados['phonebox']["0"] != ""){
                    foreach ($dados["phonebox"] as $key => $phone) {
                        Snep_Contacts_Manager::addNumber($dados['id'], $phone);
                    }
                }

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'contacts_names', $dados['id'], $this->view->translate("Contact") . " {$dados['id']} " . $_POST['name']);

                $this->_redirect($this->getRequest()->getControllerName());
            }
        } 

    }

    /**
     * Edit Contact
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Contacts"),
                    $this->view->translate("Edit")));

        $id = $this->_request->getParam('id');

        $contact = Snep_Contacts_Manager::get($id);


        // Load phones of the contact
        $phones = Snep_Contacts_Manager::getPhone($id);
        $this->view->phones = $phones;
        foreach ($phones as $phone) {
            $contact['phone'][] = $phone['phone'];
        }

        // Load groups and marks the already registered contact group
        foreach($this->allGroups as $key => $group){

            if ( $group['id'] === $contact['group']) {
                $this->allGroups[$key]['selected'] = 'selected' ;
            } else {
                $this->allGroups[$key]['selected'] = "" ;
            }
        }
        $this->view->contact_groups = $this->allGroups;

        // Load states and cities and marks the already registered contact state / city
        if($contact['id_state']){
            
            $allstates = $allcitys = "" ;

            foreach($this->states as $state){
                $allstates .= ($contact["id_state"] == $state['id']) ? "<option value='".$state['id'] . "' selected >".$state['name']." </option>\n": "<option value='".$state['id'] . "'>".$state['name']." </option>\n";
            }

            $citys = Snep_Contacts_Manager::getCity($contact['id_state']);
            foreach($citys as $key => $city){
                $allcitys .= ($contact["id_city"] == $city['id']) ? "<option value='".$citys[$key]['id'] . "' selected >".$citys[$key]['name']." </option>\n": "<option value='".$citys[$key]['id'] . "'>".$citys[$key]['name']." </option>\n";
            }

            $this->view->states = $allstates;
            $this->view->citys = $allcitys;
            
        } else {

            $this->view->states = $this->states;
            $this->view->null = true;
        }
        
        $formatter = new Formata();        
        $this->view->zipcode = (($contact['cep'] != "") ?  $formatter->fmt_cep($contact['cep']) : null );
        $this->view->contact = $contact;
        $this->view->action = 'edit';
        $this->view->contact_id = $contact['id'];
        $this->renderScript('contacts/addedit.phtml');

        if ($this->_request->getPost()) {
        
            $_POST['id'] = $id;
            $dados = $this->_request->getParams();

            if($dados['state'] == "0"){
                $dados['state'] = null;
                $dados['city'] = null;
            }
            
            $rm = array(".","-");
            $dados['zipcode'] = str_replace($rm, "", $dados['zipcode']);
            Snep_Contacts_Manager::edit($dados);
            Snep_Contacts_Manager::removePhone($id);

            $numbers = explode(",", $_POST['phoneValue']);
            foreach ($_POST["phonebox"] as $key => $phone) {
                if($phone != ""){
                    Snep_Contacts_Manager::addNumber($id, $phone);
                }
            }

            //audit
            Snep_Audit_Manager::SaveLog("Updated", 'contacts_names', $id, $this->view->translate("Contact") . " {$id} " . $_POST['name']);

            $this->_redirect($this->getRequest()->getControllerName());
            
        } 
    }

    /**
     * Remove a Contact
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contacts"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        $this->view->id = $id;
        $this->view->remove_title = $this->view->translate('Delete Contact.'); 
        $this->view->remove_message = $this->view->translate('The contact will be deleted. After that, you have no way get it back.'); 
        $this->view->remove_form = 'contacts'; 
        $this->renderScript('remove/remove.phtml');

        if ($this->_request->getPost()) {

            $contact = Snep_Contacts_Manager::get($_POST['id']);
            Snep_Contacts_Manager::removePhone($_POST['id']);
            Snep_Contacts_Manager::remove($_POST['id']);

            //audit
            Snep_Audit_Manager::SaveLog("Deleted", 'contacts_names', $_POST['id'], $this->view->translate("Contact") . " {$_POST['id']} " . $contact['name']);

            $this->_redirect($this->getRequest()->getControllerName());
        }
    }



    /**
     * multiRemoveAction - Remove various contacts
     */
    public function multiremoveAction() {

        if ($this->_request->getPost()) {

            if ($_POST['group'] == 'all') {
                $groups = Snep_ContactGroups_Manager::getAll();
            } else {
                $groups = Snep_ContactGroups_Manager::get($_POST['group']);
            }

            foreach ($groups as $group) {
                
                $contact = Snep_Contacts_Manager::getMember($group['id']);
                foreach ($contact as $key => $id) {
                    Snep_Contacts_Manager::removePhone($id['id']);
                }

                Snep_Contacts_Manager::removeByGroupId($group['id']);
            }

            $this->_redirect($this->getRequest()->getControllerName());
        } else {

            $this->view->message = $this->view->translate('Select a contact group to remove your contacts.');
            $_contactGroups = Snep_ContactGroups_Manager::getAll();
            $contactGroups = array('all' => $this->view->translate('All Groups'));
            foreach ($_contactGroups as $contactGroup) {
                $contactGroups[$contactGroup['id']] = $contactGroup['name'];
            }

            $this->view->groups = $contactGroups;
            $this->renderScript("contacts/select-multi-remove.phtml");
        }
    }

    /**
     * Import contacts from CSV file
     */
    public function importAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Contacts"),
                    $this->view->translate("Import CSV")));
                
        $this->view->message = $this->view->translate("The file must be separated by commas. The columns can be associated in the next screen.");

        if($this->_request->getPost()){

            $arrResult = array();

            $handle = fopen($_FILES['file']['tmp_name'],"r");
            $csv = array();

            if( $handle ) {
                while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
                    $csv[] = $data;
                }
                fclose($handle);
            }
            
            $validateAccent = array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/");
            $contCol = 0;
            foreach($csv as $key => $value){
                $csv[$key] = preg_replace($validateAccent, explode(" ", "a A e E i I o O u U n N"), $value);             
                // max number the coluns
                if(count($value) > $contCol){
                    $contCol = count($value);
                }
            }
            
            $standard_fields = array("discard" => $this->view->translate("Discard"),
                "name" => $this->view->translate("Name"),
                "address" => $this->view->translate("Address"),
                "zipcode" => $this->view->translate("Zip Code"),
                "phone" => $this->view->translate("Phone"));

            $fields = "";
            foreach($standard_fields as $key => $col){
                $fields .= '<option value="'.$key.'">'.$col.'</option>\n'; 
            }
            
            $_groups = Snep_ContactGroups_Manager::getAll();
            foreach ($_groups as $group) {
                $groups[$group['id']] = $group['name'];
            }

            if (!count($_groups) > 0) {
                $this->view->error_message = $this->view->translate('There is no contacts group registered.');
                $this->renderScript('error/sneperror.phtml');
            }
            
            $_SESSION['csvData'] = $csv;           
            $this->view->contCol = $contCol;                
            $this->view->csvData = $csv;
            $this->view->fields = $fields;
            $this->view->groups = $groups;
                
        }
        
    }

    /**
     * Process a csv file
     */
    public function csvprocessAction() {
        
        if ($this->getRequest()->isPost()) {
            
            // Verifies that has name and phone number (Mandatory data)
            $colName = false;
            $colPhone = false;
            foreach($_POST['colun'] as $i => $name){
                
                if($name == 'name'){
                    $colName = true;
                }

                if($name == 'phone'){
                    $colPhone = true;
                }
            }

            if($colName == false || $colPhone == false){

                $this->view->error_message = $this->view->translate("Name and phone number are required");
                $this->renderScript('error/sneperror.phtml');
            }else{

                $cont = 0;
                foreach($_SESSION['csvData'] as $k => $key){
                    foreach($key as $keyValue => $value){
                        foreach($_POST['colun'] as $keyCol => $colName){
                            if($keyValue == $keyCol){
                                $contData[$cont][$colName] = $value;
                            }
                        }
                    }
                    $cont++;
                }

                $cont = 0;
                                
                foreach($contData as $ind => $data){

                        $erro = false;
                        $contact[$cont]['phone'] = $data['phone'];
                        $contact[$cont]['name']  = $data["name"];
                        (isset($data['address'])) ? $contact[$cont]['address'] = $data['address'] : $contact[$cont]['address'] = "";
                        (isset($data['zipcode'])) ? $contact[$cont]['zipcode'] = $data['zipcode'] : $contact[$cont]['zipcode'] = "";
                        $contact[$cont]['group'] = $_POST['group'];
                        
                        // Verifies that phone contains only numbers
                        if(!is_numeric($data['phone'])){
                            $dataInvalid[$cont] = $contact[$cont];
                            $dataInvalid[$cont]['error'] = $this->view->translate('Phone invalid'); 
                            $erro = true;
                        }

                        // Verifies that zipcode contains only numbers
                        if(!is_numeric($data['zipcode']) && $data['zipcode'] != ""){
                            $dataInvalid[$cont] = $contact[$cont];
                            $dataInvalid[$cont]['error'] = $this->view->translate('Zipcode invalid');
                            $erro = true;
                        }

                        if($erro){
                            unset($contact[$cont]);
                        }

                        $cont++;
                        
                }
                
                // Send erro message
                if($dataInvalid){
                    
                    $msg = "";
                    foreach($dataInvalid as $x => $invalid){
                        $msg .= $this->view->translate(" The contact "). $invalid['name'] . $this->view->translate(" has the following error ") . $invalid['error'] . '<br>';   
                    }
                    
                    $this->view->error_message = $msg;
                    $this->renderScript('error/sneperror.phtml');
                
                }else{
                    foreach($contact as $$key => $data){

                        //Add contact
                        $last_id = Snep_Contacts_Manager::add($data);
                    
                        //Add phone
                        Snep_Contacts_Manager::addNumber($last_id, $data['phone']);        
                    }
                    // destroy csv data in session
                    unset($_SESSION['csvData']);

                    $this->_redirect($this->getRequest()->getControllerName());
                }
                   
            }
            
        }
    }

}
