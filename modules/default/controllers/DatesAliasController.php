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
 * Dates Alias Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2017 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class DatesAliasController extends Zend_Controller_Action {


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
     * indexAction
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Dates Alias")));

        $aliases = PBX_DatesAliases::getInstance();
        $expressions = $aliases->getAll();
        $expressionsList = $aliases->getAllList();
        
        foreach ($expressions as $key => $expression) {
            $expressions[$key]['cont'] = 0;
            foreach ($expressionsList as $x => $date) {
                if($expression['id'] == $date['dateid']){
                    $expressions[$key]['cont']++;
                }
            }
        }
        $this->view->aliases = $expressions;

    }



    /**
     * AddAction - Add expression alias
     * @throws PBX_Exception_BadArg
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Dates Alias"),
                    $this->view->translate("Add")));

        $locale = Snep_Locale::getInstance()->getLocale();
        $this->view->datepicker_locale =  Snep_Locale::getDatePickerLocale($locale) ;

        $this->view->dates = array();
        $this->view->action = 'add';
        $this->renderScript('dates-alias/addedit.phtml');

        if ($this->getRequest()->isPost()) {

            $aliasesPersistency = PBX_DatesAliases::getInstance();
            //Zend_Debug::Dump($_POST);exit;
            try {
                $id = PBX_DatesAliases::add($_POST);

                //audit
                Snep_Audit_Manager::SaveLog("Added", 'date_alias', $id, $this->view->translate("Date Alias") . " {$id} " . $_POST['name']);
                $this->_redirect($this->getRequest()->getControllerName());
            } catch (Exception $ex) {
                $this->view->error_message = $ex->getMessage();
                $this->renderScript('error/sneperror.phtml');
            }
        }
    }

    /**
     * editAction - Edit expression alias
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Dates Alias"),
                    $this->view->translate("Edit")));

        $id = (int) $this->getRequest()->getParam('id');

        $locale = Snep_Locale::getInstance()->getLocale();
        $this->view->datepicker_locale =  Snep_Locale::getDatePickerLocale($locale) ;

        $aliasesPersistency = PBX_DatesAliases::getInstance();

        $alias = $aliasesPersistency->get($id);
        $this->view->alias = $alias;
        $this->view->id = $id;

        //$this->view->expressions = $alias['expressions'];

        $this->view->action = 'edit';
        $this->renderScript('dates-alias/addedit.phtml');

        if ($this->getRequest()->isPost()) {
          try {
              PBX_DatesAliases::update($_POST);

              //audit
              Snep_Audit_Manager::SaveLog("Updated", 'date_alias', $id, $this->view->translate("Date Alias") . " {$id} " . $_POST['name']);
              $this->_redirect($this->getRequest()->getControllerName());
          } catch (Exception $ex) {
              $this->view->error_message = $ex->getMessage();
              $this->renderScript('error/sneperror.phtml');
          }



        }

    }

    /**
     * removeAction - Delete expression alias
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Dates Alias"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        // check if dates_alias is used in any route
        $routes =  PBX_DatesAliases::getValidation($id);
        if (count($routes) > 0) {

            $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this dates alias: ") . "<br />";
            foreach ($routes as $route) {
                $this->view->error_message .= $route['id'] . " - " . $route['desc'] . "<br />\n";
            }

            $this->renderScript('error/sneperror.phtml');
        }else{

            $this->view->id = $id;
            $this->view->remove_title = $this->view->translate('Delete Dates Alias.');
            $this->view->remove_message = $this->view->translate('The date alias will be deleted. After that, you have no way get it back.');
            $this->view->remove_form = 'dates-alias';
            $this->renderScript('remove/remove.phtml');

            if ($this->_request->getPost()) {

                $result = PBX_DatesAliases::get($id);
                PBX_DatesAliases::delete($_POST['id']);

                //audit
                Snep_Audit_Manager::SaveLog("Deleted", 'date_alias', $id, $this->view->translate("Date Alias") . " {$id} " . $result[0]['name']);
                $this->_redirect($this->getRequest()->getControllerName());

            }
        }

    }

}
