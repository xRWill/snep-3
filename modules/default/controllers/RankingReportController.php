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
 * Ranking Report Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class RankingReportController extends Zend_Controller_Action {

    /**
     * @var Zend_Form
     */
    private $form;

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
                    $this->view->translate("Call Rankings")));

        $config = Zend_Registry::get('config');

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

        $dateForm = explode(" - ", $formData["period"]);
        $param = Snep_Reports::fmt_date($dateForm[0],$dateForm[1]);

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                            $this->view->translate("Reports"),
                            $this->view->translate("Ranking"),
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

        $param['type'] = $formData['type'];
        $param['showsource'] = $formData['showsource'];
        $param['showdestiny'] = $formData['showdestiny'];
        $out_type = $formData['out_type'];

        $replace = false;
        if(isset($formData['replace'])){
          $param['replace'] = true;
          $replace = true;
        }

        $service = 'RankingReport';
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

                if($data->status == 'empty'){
                    $this->view->error_message = $this->view->translate("No entries found");
                    $this->renderScript('error/sneperror.phtml');
                }else{

                    $this->view->rank = $data->quantity;
                    $this->view->totals = (array) $data->totals;

                    $this->view->type = $data->type;

                    if($out_type == 'lst'){
                        $this->renderScript('ranking-report/view.phtml');
                    }else{
                        $this->csvAction();
                    }
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

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Reports"),
                    $this->view->translate("Ranking"),
                    $this->view->translate("Export CSV")));


        $ranking = (array)$this->view->rank;
        $this->view->quant = count($ranking);

        // input hidden
        $rank = htmlentities(json_encode($ranking));
        $this->view->ranking = $rank;

        if (isset($_POST['submit'])) {

            $ranking = json_decode($_POST['ranking']);

            $header = array($this->view->translate('Source'),
                            $this->view->translate('Destiny'),
                            $this->view->translate('Q. Answered'),
                            $this->view->translate('Q. No Answer'),
                            $this->view->translate('Q. Total'),
                            $this->view->translate('T. Answered'),
                            $this->view->translate('T. No Answer'),
                            $this->view->translate('T. Total'));

            $output = implode(",", $header) . "\n";

            foreach($ranking as $source => $data){
                foreach($data as $destiny => $values){

                $values = (array)$values;
                $output .= $source.','.$destiny.','.$values['QA'].','.$values['QN'].','.$values['QT'].','.$values['TA'].','.$values['TN'].','.$values['TT'];
                $output .= "\n";

                }
            }

            if ($output) {

                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();


                $csvData = $output;

                $dateNow = new Zend_Date();
                $fileName = $this->view->translate('ranking').'_csv_' . $dateNow->toString("dd-MM-yyyy_hh'h'mm'm'") . '.csv';

                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                echo $csvData;

            } else {
                $this->view->error = $this->view->translate("No records found.");
                $this->renderScript('error/sneperror.phtml');
            }
        } else {

            $this->view->title = "Export";
            $this->render('export');
        }

    }

}
