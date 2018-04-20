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
 * Record Report Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 Opens Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ExportDataController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
    public function init() {

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    /**
     * indexAction
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Reports"),$this->view->translate("Export Data Table")));

        $tables = array('users' => $this->view->translate("Users"),
                        'peers' => $this->view->translate("Extensions"),
                        'ccustos' => $this->view->translate("Tags"),
                        'trunks' => $this->view->translate("Trunks"),
                        'queues' => $this->view->translate("Queues"));
       
        $this->view->tables = $tables;

        $this->view->users = array('id' => $this->view->translate("Code"), 'name' => $this->view->translate("Name"), 'email' => "Email", 'created' => $this->view->translate("Create Date"), 'updated' => $this->view->translate("Update Date"));
        $this->view->peers = array('name' => $this->view->translate("Extension"), 'callerid' => $this->view->translate("Name"), 'secret' => $this->view->translate("Password"), 'dtmfmode' => $this->view->translate("DTMF Mode"), 'allow' => "Codec", 'canal' => $this->view->translate("Channel"), 'nat' => "Codec", 'directmedia' => "Directmedia");
        $this->view->ccustos = array('codigo' => $this->view->translate("Code"), 'tipo' => $this->view->translate("Type"), 'nome' => $this->view->translate("Name"), 'descricao' => $this->view->translate("Description"));
        $this->view->queues = array('id' => $this->view->translate("Code"), 'name' => $this->view->translate("Name"), 'musiconhold' => $this->view->translate("Music on hold"));
        $this->view->trunks = array('id' => $this->view->translate("Code"), 'callerid' => $this->view->translate("Name"), 'dtmfmode' => $this->view->translate("DTMF Mode"), 'host' => $this->view->translate("Host"), 'username' => $this->view->translate("Username"), 'secret' => $this->view->translate("Password"), 'allow' => "Codec", 'type' => $this->view->translate("Type"), 'channel' => $this->view->translate("Channel"), 'domain' => $this->view->translate("Domain"));
   
        if ($this->_request->getPost()) {
            $this->exportAction();            
        }

    }

    /**
     * exportAction - Export contacts for CSV file.
     */
    public function exportAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Reports"),$this->view->translate("Export Data Table")));

        $formData = $this->_request->getPost();
            
        if ($this->_request->getParam('download')) {
            
            $table = $_SESSION['exportData']['table'];
            $db = Zend_Registry::get('db');

            $select = "SELECT " . $_SESSION['exportData']['coluns'] . " FROM " . $table . " ORDER BY " . $_SESSION['exportData']['order'];
            $stmt = $db->query($select);
            $values = $stmt->fetchAll();           

            // Varre array verificando se existe ; ou ,
            foreach($values as $key => $array){
                foreach($array as $colum => $value){
                    $res[$key][$colum] = str_replace(";", " ", $value);
                    $res[$key][$colum] = str_replace(",", " ", $value);
                }
            }

            $reportData['data'] = $res;
            $reportData['cols'] = explode(',', $_SESSION['exportData']['coluns']);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();
                
                $csv = new Snep_Csv();
                $csvData = $csv->generate($reportData['data'], $reportData['cols']);

                $dateNow = new Zend_Date();
                $fileName = $this->view->translate($table) . '_csv_' . $dateNow->toString("dd-MM-yyyy_hh'h'mm'm'") . '.csv';

                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                echo $csvData;

            } else {
                $this->view->error = $this->view->translate("No records found.");
                $this->renderScript('error/sneperror.phtml');
            }
        } else {
            
            // Selected columns
            $fields = "" ;
            foreach($formData['coluns'][$formData['group']] as $key => $value){
                $fields .= $key.",";
            }
            
            $ie = new Snep_CsvIE();
            $_SESSION['exportData']['table'] = $formData['group'];
            $_SESSION['exportData']['coluns'] = substr($fields, 0,-1);
            $_SESSION['exportData']['order'] = $formData['orderby'][$formData['group']];

            $this->view->form = $ie->exportResult();
            $this->view->title = "Export";
            $this->render('export');
        }

   }

}
