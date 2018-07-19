<?php

/*
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

require_once "includes/ParseDown.php";

/**
 * Controller for users
 * 
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2018 Opens Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 * 
 */
class DocsController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
     public function init() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        // Add dashboard button
        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
                                              Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
                                              Zend_Controller_Front::getInstance()->getRequest()->getActionName());

        $this->profiles = Snep_Profiles_Manager::getAll();
    }

    /**
     * indexAction - List all users
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Documentation")));

        if ($this->_request->getPost()) {

            $data = $this->_request->getParams();
            unset($data["controller"]);
            unset($data["action"]);
            unset($data["module"]);

            foreach ($data as $key => $value) {
                $Parsedown = new Parsedown();
                $html = file_get_contents('/var/www/html/snep/docs/'. strtoupper($key) .'.md');
                $Parsedown = new Parsedown();
                $this->view->doc = $Parsedown->text($html); 
            }
            
        }

    }

}