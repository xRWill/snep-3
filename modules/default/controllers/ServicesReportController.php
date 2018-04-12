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
class ServicesReportController extends Zend_Controller_Action
{

    /**
     * Initial settings of the class
     */
    public function init()
    {

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    /**
     * indexAction
     */
    public function indexAction()
    {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
            $this->view->translate("Reports"),
            $this->view->translate("Services Use")));

        $config = Zend_Registry::get('config');

        // Include Inpector class, for permission test
        include_once $config->system->path->base . "/inspectors/Permissions.php";

        // Peer groups
        $peer_groups = Snep_ExtensionsGroups_Manager::getAll();
        array_unshift($peer_groups, array('id' => '0', 'name' => ""));
        $this->view->group = $peer_groups;

        $test = new Permissions();
        $response = $test->getTests();

        $locale = Snep_Locale::getInstance()->getLocale();
        $this->view->datepicker_locale = Snep_Locale::getDatePickerLocale($locale);

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);
        if ($_SESSION[$user['name']]['period']) {
            $dateForm = explode(" - ", $_SESSION[$user['name']]['period']);
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
     * viewAction - View report services
     */
    public function viewAction()
    {

        $filter = $this->_request->getParams();

        $dateForm = explode(" - ", $filter["period"]);
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
            $this->view->translate("Reports"),
            $this->view->translate("Services Use"),
            $dateForm[0] . ' - ' . $dateForm[1]));

        $this->view->exportName = $dateForm[0] . '_' . $dateForm[1];

        // Check Bond
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);
        $_SESSION[$user['name']]['period'] = $filter["period"];

        // Binds
        if ($user['id'] != '1') {

            $binds = Snep_Binds_Manager::getBond($user['id']);

            if ($binds) {
                $clausule = $binds[0]["type"];
                $clausulepeer = '';
                foreach ($binds as $key => $value) {
                    $clausulepeer .= $value['peer_name'] . '_';
                }

                $filter['clausule'] = $clausule;
                $filter['clausulepeer'] = substr($clausulepeer, 0, -1);
            }
        }

        if (!isset($filter['serv_select'])) {
            $this->view->error_message = $this->view->translate("Select at least one service");
            $this->renderScript('error/sneperror.phtml');
            return;
        } else {
            foreach ($filter['serv_select'] as $key => $value) {
                $filter[$key] = true;
            }
        }

        if ($filter['group_select'] != "0") {
            $filter['group_select'] = $filter['group_select'];
        }

        if ($filter['exten_select'] != "") {
            $filter['exten_select'] = $filter['exten_select'];
        }

        $data = $this->getData($filter);
        $this->view->data = $data['totals'];
        $this->renderScript('services-report/view.phtml');

    }

    public function getData($filter)
    {

        $db = Zend_registry::get('db');

        $dateForm = explode(" - ", $filter["period"]);
        $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);
        $fromDay = $date['start_date'] . " " . $date['start_hour'];
        $tillDay = $date['end_date'] . " " . $date['end_hour'];

        // Binds
        if (isset($filter['clausulepeer']) && isset($filter['clausule'])) {

            $clausulepeer = explode("_", $filter['clausulepeer']);
            $where_binds = '';

            foreach ($clausulepeer as $key => $value) {
                $where_binds .= $value . ",";
            }
            $where_binds = substr($where_binds, 0, -1);

            // Not permission
            if ($filter['clausule'] == 'nobound') {

                $where_binds = " AND (peer NOT IN (" . $where_binds . "))";

            } else {

                $where_binds = " AND (peer IN (" . $where_binds . "))";
            }
        }

        /* Get peers in group */
        if ($filter['group_select'] != "0") {

            $groupsrc = $filter['group_select'];

            $origens = Snep_ExtensionsGroups_Manager::getExtensionsGroup($groupsrc);

            if (count($origens) == 0) {
                $this->view->error_message = $this->view->translate("No have extensions in group.");
                $this->view->error_title = $this->view->translate("Error");
                $this->renderScript('error/sneperror.phtml');
            } else {
                $ramalsrc = "";

                foreach ($origens as $key => $ramal) {
                    $num = $ramal['name'];
                    if (is_numeric($num)) {
                        $ramalsrc .= $num . ',';
                    }
                }
                $where_options[] = " peer in (" . trim($ramalsrc, ',') . ") ";
            }
        }

        if ($filter['exten_select'] != "") {
            $extenList = explode(',', $filter['exten_select']);
            $extens = "";

            foreach ($extenList as $key => $value) {
                $num = $value;
                if (is_numeric($num)) {
                    $extens .= $num . ',';
                }
            }
            $where_options[] = " peer in (" . trim($extens, ',') . ") ";
        }

        //Services
        $services = array();
        if (isset($filter['DND'])) {
            array_push($services, 'DND');
        }

        if (isset($filter['SIGAME'])) {
            array_push($services, 'SIGAME');
        }
        if (isset($filter['LOCK'])) {
            array_push($services, 'LOCK');
        }
        if (isset($filter['SPY'])) {
            array_push($services, 'SPY');
        }
        if (isset($filter['REDIAL'])) {
            array_push($services, 'REDIAL');
        }
        if (isset($filter['WHOAMI'])) {
            array_push($services, 'WHOAMI');
        }
        if (isset($filter['REC'])) {
            array_push($services, 'REC');
        }
        if (isset($filter['RECPLAY'])) {
            array_push($services, 'RECPLAY');
        }

        $srv = '';

        if (count($services) > 0) {

            foreach ($services as $key => $service) {

                $srv .= "'$service',";
            }
            $where_options[] = " service IN (" . substr($srv, 0, -1) . ")";
        }

        if ($where_options) {
            $where = "";
            foreach ($where_options as $key => $option) {
                $where .= ' AND (' . $option . ') ';
            }
        }

        $select = "SELECT * FROM services_log";
        $select .= " WHERE ( date >= '$fromDay' AND date <= '$tillDay') ";
        $select .= (isset($where_binds)) ? $where_binds : '';
        $select .= (isset($where)) ? $where : '';

        $stmt = $db->query($select);
        $services = $stmt->fetchAll();

        $services = array_reverse($services);
        return array("totals" => $services);

    }

}
