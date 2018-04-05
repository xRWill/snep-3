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
 * Services report Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ServicesReportController extends Zend_Controller_Action {


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

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                            $this->view->translate("Reports"),
                            $this->view->translate("Services Use")));


        $config = Zend_Registry::get('config');

        // Include Inpector class, for permission test
        include_once( $config->system->path->base . "/inspectors/Permissions.php" );

        // Peer groups
        $peer_groups = Snep_ExtensionsGroups_Manager::getAll() ;
        array_unshift($peer_groups, array('id' => '0', 'name' => ""));
        $this->view->group = $peer_groups;

        $test = new Permissions();
        $response = $test->getTests();

        $locale = Snep_Locale::getInstance()->getLocale();
        $this->view->datepicker_locale =  Snep_Locale::getDatePickerLocale($locale) ;

        if ($this->_request->getPost()) {

            $this->viewAction();

        }

    }

    /**
     * viewAction - View report services
     */
    public function viewAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $formData = $this->_request->getParams();
        $line_limit = Zend_Registry::get('config')->ambiente->linelimit;

        $dateForm = explode(" - ", $formData["period"]);
        $param = Snep_Reports::fmt_date($dateForm[0],$dateForm[1]);

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                            $this->view->translate("Reports"),
                            $this->view->translate("Services Use"),
                            $dateForm[0] . ' - ' . $dateForm[1]));

        // Check Bond
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);

        // Binds
        if($user['id'] != '1'){

            $binds = Snep_Binds_Manager::getBond($user['id']);

            if($binds){
                $clausule = $binds[0]["type"];
                $clausulepeer ='';
                foreach($binds as $key => $value){
                    $clausulepeer .= $value['peer_name'].'_';
                }

                $param['clausule'] = $clausule;
                $param['clausulepeer'] = substr($clausulepeer, 0,-1);
            }
        }

        if(!isset($formData['serv_select'])){
            $this->view->error_message = $this->view->translate("Select at least one service");
            $this->renderScript('error/sneperror.phtml');
            return;
        }else{
            foreach($formData['serv_select'] as $key => $value){
                $param[$key] = true;
            }
        }

        if($formData['group_select'] != "0"){
            $param['group_select'] = $formData['group_select'];
        }

        if($formData['exten_select'] != ""){
            $param['exten_select'] = $formData['exten_select'];
        }

        $service = 'ServicesReport';
        $url = Snep_Services::getPathService($service);

        $link = "";
        foreach($param as $key => $value){
            $link .= '&'.$key.'='.$value;
        }

        $service_url = $url.$link;

        $this->view->service_url = $service_url;

        $http = curl_init($service_url);
        $status = curl_getinfo($http, CURLINFO_HTTP_CODE);

        curl_setopt($http, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($http, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        $digest = Snep_Usuario::decrypt($_SESSION['http_authorization'], $_SESSION['ENCRYPTION_KEY']);
        curl_setopt($http, CURLOPT_USERPWD, "$digest");

        $http_response = curl_exec($http);
        $httpcode = curl_getinfo($http, CURLINFO_HTTP_CODE);

        curl_close($http);

        switch ($httpcode) {
            case 200:
                $data = json_decode($http_response);


                if($data->status === 'empty' || $data->status === 'fail'){
                    $this->view->error_message = $this->view->translate($data->message);
                    $this->renderScript('error/sneperror.phtml');
                }else{

                    $_SESSION[$user['name']]['service_report']['select'] = $data->select;
                    $_SESSION[$user['name']]['service_report']['selectcount'] = $data->selectcount;
                    $this->view->data = $data->totals;
                    $this->view->lineNumber = $line_limit;
                    $this->renderScript('services-report/view.phtml');
                }

            break;
            default:
                $erro = $this->view->translate("Error "). $httpcode;
                $erro .= $this->view->translate(". Please contact your system administrator");
                $this->view->error_message = $erro;
                $this->renderScript('error/sneperror.phtml');
            break;

        }
    }

    /**
     * csvAction - Export CSV
     */
    public function csvAction() {

        // Check Bond
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Reports"),
                    $this->view->translate("Services Use"),
                    $this->view->translate("Export CSV")));

        if ($this->_request->getParam('download')) {

            $db = Zend_registry::get('db');

            $stmt = $db->query($_SESSION[$user['name']]['service_report']['select']);

            $header = array($this->view->translate('Date'),
                            $this->view->translate('Peer'),
                            $this->view->translate('Service'),
                            $this->view->translate('State'),
                            $this->view->translate('Status'));

            $output = implode(",", $header) . "\n";

            while ($dado = $stmt->fetch()) {

                $indexes = null;
                $values = null;

                $indexes = array_keys($dado);
                $values .= preg_replace("/(\r|\n)+/", "", implode(",", $dado));
                $values .= "\n";
                $output .= $values;
            }

            if ($output) {

                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();


                $csvData = $output;

                $dateNow = new Zend_Date();
                $fileName = $this->view->translate('services_use').'_csv_' . $dateNow->toString("dd-MM-yyyy_hh'h'mm'm'") . '.csv';

                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                echo $csvData;

            } else {
                $this->view->error = $this->view->translate("No records found.");
                $this->renderScript('error/sneperror.phtml');
            }
        } else {

            $ie = new Snep_CsvIE();
            $this->view->form = $ie->exportResultReport($_SESSION[$user['name']]['service_report']['selectcount']);
            $this->view->title = "Export";
            $this->render('export');
        }

    }

}
