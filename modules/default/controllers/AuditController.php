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
 * Audit Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2018 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class AuditController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
    public function init() {
      
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(Zend_Controller_Front::getInstance()->getRequest()->getModuleName(), Zend_Controller_Front::getInstance()->getRequest()->getControllerName(), Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    
    /**
     * indexAction - formData logs of system
     */
    public function indexAction() {
    
        $this->view->breadcrumb = $this->view->translate("Audit");
        $config = Zend_Registry::get('config');

        $db = Zend_Registry::get('db');
        
        $this->view->groups = array(
            "all" => $this->view->translate('All Actions'),
            "ccustos" => $this->view->translate('Tags Actions'),
            "contacts_names" => $this->view->translate('Contacts Actions'),
            "contacts_group" => $this->view->translate('Contacts Group Actions'),
            "queues" => $this->view->translate('Queues Actions'),
            "peers" => $this->view->translate('Extensions Actions'),
            "core_groups" => $this->view->translate('Extensions Group Actions'),
            "grupos" => $this->view->translate('Pickup Group Actions'),
            "users" => $this->view->translate('Users Actions'),
            "profiles" => $this->view->translate('Profiles Actions'),
            "trunks" => $this->view->translate('Trunks Actions'),
            "regras_negocio" => $this->view->translate('Routes Actions'),
            "expr_alias" => $this->view->translate('Expression Alias Actions'),
            "date_alias" => $this->view->translate('Expression Alias Dates'),
            "sounds" => $this->view->translate('Sound Files'),
        );

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);
        if ($_SESSION[$user['name']]['periodAudit']) {
            $dateForm = explode(" - ", $_SESSION[$user['name']]['periodAudit']);
            $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);
            $this->view->startDate = $dateForm[0];
            $this->view->endDate = $dateForm[1];
        } else {
            $this->view->startDate = false;
            $this->view->endDate = false;
        }
        
        if ($this->_request->getPost()) {
            $this->viewAction();
        }

    }

    /**
    * viewAction - logs of system list
    */
    public function viewAction(){
      
      $formData = $this->_request->getParams();

      $auth = Zend_Auth::getInstance();
      $username = $auth->getIdentity();
      $user = Snep_Users_Manager::getName($username);
      $_SESSION[$user['name']]['periodAudit'] = $formData["period"];

      $dateForm = explode(" - ", $formData["period"]);
      $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);
      $start_date = $date['start_date'] . " " . $date['start_hour'];
      $end_date = $date['end_date'] . " " . $date['end_hour'];

      $data = Snep_Audit_Manager::getAll($start_date, $end_date, $formData['selectAct']);

      foreach($data as $key => $value){

        switch ($value['action']) {
          case 'Added':
            $data[$key]['class'] = "label label-success";
            break;
          case 'Updated':
            $data[$key]['class'] = "label label-warning";
            break;
          case 'Deleted':
            $data[$key]['class'] = "label label-danger";
            break;
          case 'duplicate':
            $data[$key]['class'] = "label label-info";
            $data[$key]['action'] = $this->view->translate('Duplicated');
            break;
          default:
            $data[$key]['class'] = "label label-default";
            break;
        }
      }
      
      $this->view->logs = $data;
      $this->renderScript('audit/view.phtml');

    }

  }