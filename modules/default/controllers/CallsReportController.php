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
* Calls report Controller
*
* @category  Snep
* @package   Snep
* @copyright Copyright (c) 2015 Opens Tecnologia
* @author    Opens Tecnologia <desenvolvimento@opens.com.br>
*/
class CallsReportController extends Zend_Controller_Action {

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
    * indexAction - Report calls
    */
    public function indexAction() {

      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
        $this->view->translate("Reports"),
        $this->view->translate("Calls")));

        $config = Zend_Registry::get('config');

        include( $config->system->path->base . "/inspectors/Permissions.php" );
        $test = new Permissions();
        $response = $test->getTests();

        $expression_aliases = Snep_ExpressionAliases_Manager::getAll();
        array_unshift($expression_aliases, array('id' => '0', 'name' => ""));
        $this->view->expression_aliases = $expression_aliases;

        // Contact groups
        $contact_groups = Snep_ContactGroups_Manager::getAll();
        array_unshift($contact_groups, array('id' => '0', 'name' => ""));
        $this->view->contact_groups = $contact_groups;

        // Contact
        $contacts = Snep_Contacts_Manager::getAll();
        array_unshift($contacts, array('id' => '0', 'name' => ""));
        $this->view->contacts = $contacts;

        // Peer groups
        $peer_groups = Snep_ExtensionsGroups_Manager::getAll() ;
        array_unshift($peer_groups, array('id' => '0', 'name' => ""));
        $this->view->groups = $peer_groups;

        // Tags
        $this->view->costs = Snep_CostCenter_Manager::getAll();

        $locale = Snep_Locale::getInstance()->getLocale();
        $this->view->datepicker_locale =  Snep_Locale::getDatePickerLocale($locale) ;

        if ($this->_request->getPost()) {
          $this->viewAction();
        }

      }

      public function viewAction(){

        $formData = $this->_request->getParams();
        $line_limit = $this->view->lineNumber;
        $locale = false;
        $type = 'preview';

        // Check Bond
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);

        // Primary select
        if(!isset($formData['page'])){

          $param = Snep_Reports::fmt_date($formData['initDay'],$formData['finalDay']);

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

            $exceptions = Snep_Binds_Manager::getBondException($user['id']);
            if(!empty($exceptions)){
              foreach($exceptions as $x => $excep){
                (isset($exceptionsAll)) ? $exceptionsAll .= $excep["exception"]."_" : $exceptionsAll = $excep["exception"]."_";
              }
              $param['exceptions'] = substr($exceptionsAll, 0,-1);
            }


          }

          // call status
          if(isset($formData['ANSWERED']))
          $param['status_answered'] = true;

          if(isset($formData['NOANSWER']))
          $param['status_noanswer'] = true;

          if(isset($formData['BUSY']))
          $param['status_busy'] = true;

          if(isset($formData['FAILED']))
          $param['status_failed'] = true;

          // time call
          if($formData['duration_init'] != "")
          $param['time_call_init'] = $formData['duration_init'];

          if($formData['duration_end'] != "")
          $param['time_call_end'] = $formData['duration_end'];

          // Contact Group Src
          if($formData['selectContactGroupSrc'] != "0"){
            $param['contactGroupSrcId'] = $formData['selectContactGroupSrc'];
          }

          // Contact Src
          if($formData['selectContactSrc'] != "0"){
            $param['contactSrcId'] = $formData['selectContactSrc'];
          }

          // Contact Group Dst
          if($formData['selectContactGroupDst'] != "0"){
            $param['contactGroupDstId'] = $formData['selectContactGroupDst'];
          }

          // Contact Dst
          if($formData['selectContactDst'] != "0"){
            $param['contactDstId'] = $formData['selectContactDst'];
          }

          // Peer groups of src and dst
          if($formData['selectSrc'] != "0"){
            $param['groupsrc'] = $formData['selectSrc'];
          }

          if($formData['selectDst'] != "0"){
            $param['groupdst'] = $formData['selectDst'];
          }

          // Peer list od src or dst
          if($formData['groupSrc'] != ""){
            $param['src'] = $formData['groupSrc'];
            $param['order_src'] = $formData['order_src'];
          }


          if($formData['groupDst'] != ""){
            $param['dst'] = $formData['groupDst'];
            $param['order_dst'] = $formData['order_dst'];
          }

          if(isset($formData['locale'])){
            $param['locale'] = true;
            $locale = true;

          }

          $record = false;
          if(isset($formData['record'])){
            $param['record'] = true;
            $record = true;

          }

          $replace = false;
          if(isset($formData['replace'])){
            $param['replace'] = true;
            $replace = true;
          }

          // cost center
          if(!empty($formData['costs_center'])){
            $param['cost_center'] = implode('_', $formData['costs_center']);
          }

          $report_type = $formData['report_type'];
          $param['report_type'] = $report_type;

          if($formData['preview'] == 'graphic'){
            $param['report_type'] = 'synthetic';
            $report_type = 'synthetic';
            $type = 'graphic';
          }

          $service = 'CallsReport';
          $url = Snep_Services::getPathService($service);

          $link = "";
          foreach($param as $key => $value){
            $link .= '&'.$key.'='.$value;
          }

          // Limit on select
          $limit = '0,' .$line_limit;

          $service_url = $url.$link.'&limit='.$limit;

          // Guarda url na sessão para paginação
          $_SESSION[$user['name']]['report_url'] = $url.$link;
          $_SESSION[$user['name']]['locale'] = $locale;
          $_SESSION[$user['name']]['record'] = $record;
          $_SESSION[$user['name']]['replace'] = $replace;

          // Pagination
          $pagesValue = Snep_Reports::createPages(1, $line_limit);
          $this->view->pageprev = $pagesValue['pageprev'];
          $this->view->pagenext = $pagesValue['pagenext'];
          $this->view->page = 1;

        }else{

          $report_type = 'analytic';
          $page = $formData['page'];

          $cont = $formData['cont'];

          $init_value = ($page -1) * $line_limit;

          // Limit on select. Ex: 40,20 -> Select data id 40 to 60
          $limit = $init_value .',' .$line_limit;

          $service_url = $_SESSION[$user['name']]['report_url'].'&limit='.$limit;

          // Pagination
          $pagesValue = Snep_Reports::createPages($page, $line_limit, $cont);
          $this->view->pageprev = $pagesValue['pageprev'];
          $this->view->pagenext = $pagesValue['pagenext'];
          $this->view->page = $page;
        }
        // events asterisk
        $this->view->translate("ANSWERED");
        $this->view->translate("NO ANSWER");
        $this->view->translate("BUSY");
        $this->view->translate("FAILED");
        if(class_exists("Billing_Manager")){
            $this->view->bill = true;
            $service_url .= "&rate=true";
        }


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

          $row = json_decode($http_response);

          if($row){

            // error
            if($row->status == 'fail'){

              if($data->message == 'errorgroup'){
                $this->view->error_message = $this->view->translate("There are no extensions in the selected group");
                $this->renderScript('error/sneperror.phtml');
              }

            }else{

              if(isset($row->select)){
                $_SESSION[$user['name']]['select'] = $row->select;
                $_SESSION[$user['name']]['selectcont'] = $row->selectcont;
              }

              if($report_type == 'analytic'){

                $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                  $this->view->translate("Reports"),
                  $this->view->translate("Calls"),
                  $this->view->translate("Analytic")));

                  $listItems = array();
                  $format = new Formata;
                  $cont = 1;

                  foreach ($row->data as $item) {

                    if($_SESSION[$user['name']]['locale'] == 'on'){

                      //Search for a city or format the telephone type
                      if (strlen($item->src) > 7 && strlen($item->dst) < 5) {
                        $item->city = Snep_Cnl::getCity($item->src);
                      } else {
                        $item->city = Snep_Cnl::getCity($item->dst);
                      }

                      $this->view->locale = true;
                    }

                    if($_SESSION[$user['name']]['record'] == 'on'){

                      $filePath = Snep_Manutencao::arquivoExiste($item->calldate, $item->userfield);
                      $item->file_name = $item->userfield . ".wav";

                      if ($filePath) {
                        $item->file_path = $filePath;
                        $item->file_name = $filePath;
                      } else {
                        $item->file_path = 'N.D.';
                      }

                      $this->view->record = true;

                    }

                    $item->id = $cont;
                    $item->nome = $item->tipo . " : " . $item->codigo . " - " . $item->nome;
                    $item->src = $format->fmt_telefone(array("a" => $item->src));
                    $item->dst = $format->fmt_telefone(array("a" => $item->dst));
                    $item->src_name = $format->fmt_telefone(array("a" => $item->src_name));
                    $item->dst_name = $format->fmt_telefone(array("a" => $item->dst_name));

                    // Times
                    $item->billsec = $format->fmt_segundos(array("a" => $item->billsec, "b" => 'hms'));
                    $item->duration = $format->fmt_segundos(array("a" => $item->duration, "b" => 'hms'));

                    if($item->disposition == 'ANSWERED'){
                      $class = "label label-success";
                    }elseif($item->disposition == 'NO ANSWER'){
                      $class = "label label-danger";
                    }elseif($item->disposition == 'BUSY'){
                      $class = "label label-warning";
                    }else{
                      $class = "label label-default";
                    }
                    $item->class = $class;
                    $item->disposition = $this->view->translate($item->disposition);
                    $listItems[$cont] = $item;
                    $cont++;
                  }
                  $this->view->replace = $replace;
                }else{

                  // Synthetic
                  $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Reports"),
                    $this->view->translate("Calls"),
                    $this->view->translate("Synthetic")));


                    // Calcule ccustos
                    $dataccustos = Snep_CostCenter_Manager::getAll();

                    foreach($row->ccustos as $key => $value){

                      foreach($dataccustos as $keycc => $ccusto){
                        if($key == $ccusto['codigo']){
                          $ccustos[$ccusto['codigo']. ' - ' .$ccusto['nome']] = $value;
                        }
                      }
                    }

                    $this->view->calldate = $row->calldate;
                    $this->view->type = $row->type;
                    $this->view->ccustos = $ccustos;
                    $this->view->totals = $row->totals;


                    if($type == 'graphic'){

                      $http = curl_init("https://www.gstatic.com/charts/loader.js");
                      $status = curl_getinfo($http, CURLINFO_HTTP_CODE);
                      curl_setopt($http, CURLOPT_RETURNTRANSFER,1);
                      $http_response = curl_exec($http);
                      $httpcode = curl_getinfo($http, CURLINFO_HTTP_CODE);
                      curl_close($http);

                      if(!$httpcode){
                        $this->view->error_message = $this->view->translate("Error generating chart. Check your connection!");
                        $this->renderScript('error/sneperror.phtml');

                      }else{
                        $this->renderScript('calls-report/graphic.phtml');
                      }

                    }

                  } // ./synthetic

                  if($type != 'graphic'){

                    if($row->quantity == 0){
                      $this->view->error_message = $this->view->translate("No entries found.");
                      $this->renderScript('error/sneperror.phtml');
                    }else{
                      $listItems["status"] = "sucess";
                      $listItems['report_type'] = $report_type;

                      $this->view->cont = $row->quantity;
                      $this->view->lineNumber = $line_limit;
                      //number pages
                      $this->view->pagination = ceil($row->quantity / $line_limit);
                      $this->view->totals = $row->totals;
                      $this->view->call_list = $listItems;
                      $this->renderScript('calls-report/view.phtml');
                    }
                  }
                }

              }else{

                $this->view->error_message = $this->view->translate("No entries found.");
                $this->renderScript('error/sneperror.phtml');

              }

              break;
              default:
              $erro = $this->view->translate("Error "). $httpcode;
              $erro .= " - $http_response - ";
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
              $this->view->translate("Calls"),
              $this->view->translate("Export CSV")));

              if ($this->_request->getParam('download')) {

                $db = Zend_registry::get('db');

                // substr -> retry limit
                $stmt = $db->query(substr($_SESSION[$user['name']]['select'], 0, -11));

                $header = array(
                  $this->view->translate('Day'),
                  $this->view->translate('Date'),
                  $this->view->translate('Source'),
                  $this->view->translate('Destiny'),
                  $this->view->translate('Call Status'),
                  $this->view->translate('Duration (seconds)'),
                  $this->view->translate('Conversation (seconds)'),
                  $this->view->translate('Tag'),
                  $this->view->translate('Userfield'),
                  $this->view->translate('Context'),
                  $this->view->translate('Amaflags'),
                  $this->view->translate('Code unique'),
                  $this->view->translate('Calldate'),
                  $this->view->translate('Channel'),
                  $this->view->translate('Code Tag'),
                  $this->view->translate('Type Tag'),
                  $this->view->translate('Name Tag'),
                  $this->view->translate('Rate')
                );

                $output = implode(";", $header) . "\n";

                while ($dado = $stmt->fetch()) {

                  $indexes = null;
                  $values = null;

                  $indexes = array_keys($dado);
                  $values .= preg_replace("/(\r|\n)+/", "", implode(";", $dado));
                  $values .= "\n";
                  $output .= $values;
                }

                if ($output) {

                  $this->_helper->layout->disableLayout();
                  $this->_helper->viewRenderer->setNoRender();


                  $csvData = $output;

                  $dateNow = new Zend_Date();
                  $fileName = $this->view->translate('calls').'_csv_' . $dateNow->toString("dd-MM-yyyy_hh'h'mm'm'") . '.csv';

                  header('Content-type: application/octet-stream');
                  header('Content-Disposition: attachment; filename="' . $fileName . '"');
                  echo $csvData;

                } else {
                  $this->view->error = $this->view->translate("No records found.");
                  $this->renderScript('error/sneperror.phtml');
                }
              } else {

                $ie = new Snep_CsvIE();
                $this->view->form = $ie->exportResultReport($_SESSION[$user['name']]['selectcont']);
                $this->view->title = "Export";
                $this->render('export');
              }

            }


          }
