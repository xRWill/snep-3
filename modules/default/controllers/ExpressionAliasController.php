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
 * Expression Alias Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ExpressionAliasController extends Zend_Controller_Action {


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
                    $this->view->translate("Expression Alias")));

        $aliases = PBX_ExpressionAliases::getInstance();
        $expressions = $aliases->getAll();

        $this->view->aliases = $expressions;

    }



    /**
     * AddAction - Add expression alias
     * @throws PBX_Exception_BadArg
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Expression Alias"),
                    $this->view->translate("Add")));

        $this->view->expressions = array();
        $this->view->action = 'add';
        $this->renderScript('expression-alias/addedit.phtml');

        if ($this->getRequest()->isPost()) {

            $expression = array(
                "name" => $_POST['name'],
                "expressions" => $_POST['aliasbox']);

            $aliasesPersistency = PBX_ExpressionAliases::getInstance();


            //validation
            $form_isValid = true ;
            foreach ($_POST['aliasbox'] as $key => $value) {
                $valida = Snep_ValidateExpression::execute($value);
                if (!$valida) {
                    break ;
                }
            }
            if (!$valida) {
                $this->view->error_message = $this->view->translate("Their alias has invalid character. Accents are not allowed, empty value between the keys, blanks and special characters except( # % | . - _ )");
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }
            if ($_POST["name"] == "" || $_POST["aliasbox"] == "") {
                $this->view->error_message = $this->view->translate("Required value");
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }
            if ($form_isValid) {

                try {
                    $id = $aliasesPersistency->register($expression);

                    //audit
                    Snep_Audit_Manager::SaveLog("Added", 'expr_alias', $id, $this->view->translate("Expression Alias") . " {$id} " . $_POST['name']);
                    $this->_redirect($this->getRequest()->getControllerName());
                } catch (Exception $ex) {
                    $this->view->error_message = $ex->getMessage();
                    $this->renderScript('error/sneperror.phtml');
                }

            }
        }
    }

    /**
     * editAction - Edit expression alias
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Expression Alias"),
                    $this->view->translate("Edit")));

        $id = (int) $this->getRequest()->getParam('id');

        $aliasesPersistency = PBX_ExpressionAliases::getInstance();

        $alias = $aliasesPersistency->get($id);
        $this->view->alias = $alias;
        $this->view->id = $id;
        $this->view->expressions = $alias['expressions'];

        $this->view->action = 'edit';
        $this->renderScript('expression-alias/addedit.phtml');

        if ($this->getRequest()->isPost()) {

            $expression = array(
                "id" => $id,
                "name" => $_POST['name'],
                "expressions" => $_POST['aliasbox']);

            //validation
            $form_isValid = true ;
            foreach ($_POST['aliasbox'] as $key => $value) {
                $valida = Snep_ValidateExpression::execute($value);
                if (!$valida) {
                    break ;
                }
            }
            if (!$valida) {
                $this->view->error_message = $this->view->translate("Their alias has invalid character. Accents are not allowed, empty value between the keys, blanks and special characters except( # % | . - _ )");
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }
            if ($_POST["name"] == "" || $_POST["aliasbox"] == "") {
                $this->view->error_message = $this->view->translate("Required value");
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }
            if ($form_isValid) {

                try {
                    $aliasesPersistency->update($expression);
                    //audit
                    Snep_Audit_Manager::SaveLog("Updated", 'expr_alias', $id, $this->view->translate("Expression Alias") . " {$id} " . $_POST['name']);
                    $this->_redirect($this->getRequest()->getControllerName());
                } catch (Exception $ex) {
                    $this->view->error_message = $ex->getMessage();
                    $this->renderScript('error/sneperror.phtml');
                }

            }

        }

    }

    /**
     * removeAction - Delete expression alias
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Expression Alias"),
                    $this->view->translate("Delete")));

        $id = $this->_request->getParam('id');

        //verifica se grupo é usado em alguma regra
        $regras =  Snep_ExpressionAliases_Manager::getValidation($id);
        if (count($regras) > 0) {

            $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this expression alias: ") . "<br />";
            foreach ($regras as $regra) {
                $this->view->error_message .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }

            $this->renderScript('error/sneperror.phtml');
        }else{

            $this->view->id = $id;
            $this->view->remove_title = $this->view->translate('Delete Expression Alias.');
            $this->view->remove_message = $this->view->translate('The expression alias will be deleted. After that, you have no way get it back.');
            $this->view->remove_form = 'expression-alias';
            $this->renderScript('remove/remove.phtml');

            if ($this->_request->getPost()) {

                Snep_ExpressionAliases_Manager::delete($_POST['id']);

                //audit
                Snep_Audit_Manager::SaveLog("Deleted", 'expr_alias', $id, $this->view->translate("Expression Alias") . " {$id} " . $expr['name']);
                $this->_redirect($this->getRequest()->getControllerName());

            }
        }
    }

}
