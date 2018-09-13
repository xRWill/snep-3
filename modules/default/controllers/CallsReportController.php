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
include "includes/functions.php";
include "inspectors/Permissions.php";

/**
 * Calls report Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 Opens Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class CallsReportController extends Zend_Controller_Action
{

    /**
     * Initial settings of the class
     */
    public function init()
    {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;
        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(Zend_Controller_Front::getInstance()->getRequest()->getModuleName(), Zend_Controller_Front::getInstance()->getRequest()->getControllerName(), Zend_Controller_Front::getInstance()->getRequest()->getActionName());
    }

    /**
     * indexAction - Report calls
     */
    public function indexAction()
    {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Reports"), $this->view->translate("Calls")));

        $config = Zend_Registry::get('config');

        $test = new Permissions();
        $response = $test->getTests();

        // Contact groups
        $contact_groups = Snep_ContactGroups_Manager::getAll();
        array_unshift($contact_groups, array('id' => '0', 'name' => ""));
        $this->view->contact_groups = $contact_groups;

        // Contact
        $contacts = Snep_Contacts_Manager::getAll();
        array_unshift($contacts, array('id' => '0', 'name' => ""));
        $this->view->contacts = $contacts;

        // Peer groups
        $peer_groups = Snep_ExtensionsGroups_Manager::getAll();
        array_unshift($peer_groups, array('id' => '0', 'name' => ""));
        $this->view->groups = $peer_groups;

        // Tags
        $this->view->costs = Snep_CostCenter_Manager::getAll();

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
            $formData = $this->_request->getParams();
            ($formData['report_type'] == 'analytic') ? $this->getAnalytic($formData) : $this->getSynthetic($formData);
        }
    }

    public function getselect($filter)
    {

        $dateForm = explode(" - ", $filter["period"]);

        $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);
        $start_date = $date['start_date'] . " " . $date['start_hour'];
        $end_date = $date['end_date'] . " " . $date['end_hour'];

        // Check Bon
        $db = Zend_registry::get('db');
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);
        $_SESSION[$user['name']]['period'] = $filter["period"];

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
            $exceptions = Snep_Binds_Manager::getBondException($user['id']);
            
            if (!empty($exceptions)) {
                $where_exceptions = "";

                foreach ($exceptions as $x => $excep) {
                    $where_exceptions .= "'".$excep["exception"]."'" . ",";
                }
                $where_exceptions = substr($where_exceptions, 0, -1);
            }
        }    
        
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
                if (count($exceptions) >0) {
                    $where_binds = " AND (src IN (" . $where_exceptions . ") OR dst IN (" . $where_exceptions . "))" . " AND (src NOT IN (" . $where_binds . ") OR dst NOT IN (" . $where_binds . "))";
                } else {
                    $where_binds = " AND (src NOT IN (" . $where_binds . ") OR dst NOT IN (" . $where_binds . "))";
                }
            } else {
                if (count($exceptions) >0) {
                    $where_binds = " AND (src IN (" . $where_binds . "," . $where_exceptions . ") OR dst IN (" . $where_binds . "," . $where_exceptions . "))";
                } else {
                    $where_binds = " AND (src IN (" . $where_binds . ") OR dst IN (" . $where_binds . "))";
                }
            }
        }    

        // when no exits bind and exsts only exception special
        if (!isset($where_binds) && count($exceptions) >0) {
            $where_binds = " AND (src IN (" . $where_exceptions . ") OR dst IN (" . $where_exceptions . "))";
        }
        
        // Status call
        $where_options[0] = " disposition != 'ANSWERED'";
        $where_options[1] = " disposition != 'NO ANSWER'";
        $where_options[2] = " disposition != 'BUSY'";
        $where_options[3] = " disposition != 'FAILED'";

        if (isset($filter['ANSWERED'])) {
            unset($where_options[0]);
        }
        if (isset($filter['NOANSWER'])) {
            unset($where_options[1]);
        }
        if (isset($filter['BUSY'])) {
            unset($where_options[2]);
        }
        if (isset($filter['FAILED'])) {
            unset($where_options[3]);
        }

        
        if (isset($filter['selectContactGroupSrc']) && $filter['selectContactGroupSrc'] != "0") {
            $where_contactGroupSrc = " AND src IN (";
            $where_contactGroupSrc .= " SELECT phone FROM contacts_names cn ";
            $where_contactGroupSrc .= " INNER JOIN contacts_phone cp ON cp.contact_id = cn.id ";
            $where_contactGroupSrc .= " INNER JOIN contacts_group cg on cg.id = cn.group ";
            $where_contactGroupSrc .= " WHERE cn.group = " . $filter['selectContactGroupSrc'];
            $where_contactGroupSrc .= ") ";
        }
        if (isset($filter['selectContactSrc']) && $filter['selectContactSrc'] != "0") {
            $where_contactSrc = " AND src IN ( ";
            $where_contactSrc .= " SELECT phone FROM contacts_names cn ";
            $where_contactSrc .= " INNER JOIN contacts_phone cp ON cp.contact_id = cn.id ";
            $where_contactSrc .= " WHERE cn.id = " . $filter['selectContactSrc'];
            $where_contactSrc .= ") ";
        }
        if (isset($filter['selectContactGroupDst']) && $filter['selectContactGroupDst'] != "0") {
            $where_contactGroupDst = " AND dst IN (";
            $where_contactGroupDst .= " SELECT phone FROM contacts_names cn ";
            $where_contactGroupDst .= " INNER JOIN contacts_phone cp ON cp.contact_id = cn.id ";
            $where_contactGroupDst .= " INNER JOIN contacts_group cg on cg.id = cn.group ";
            $where_contactGroupDst .= " WHERE cn.group = " . $filter['selectContactGroupDst'];
            $where_contactGroupDst .= ") ";
        }
        if (isset($filter['selectContactDst']) && $filter['selectContactDst'] != "0") {
            $where_contactDst = " AND dst IN ( ";
            $where_contactDst .= " SELECT phone FROM contacts_names cn ";
            $where_contactDst .= " INNER JOIN contacts_phone cp ON cp.contact_id = cn.id ";
            $where_contactDst .= " WHERE cn.id = " . $filter['selectContactDst'];
            $where_contactDst .= ") ";
        }
        
        // Searches for extensions belonging to the selected source extension group
        $ramaissrc = $ramaisdst = "";
        if ($filter['selectSrc'] != "0") {
            $groupsrc = $filter['selectSrc'];
            $origens = Snep_ExtensionsGroups_Manager::getExtensionsGroup($groupsrc);
            if (count($origens) == 0) {
                $this->view->error_message = $this->view->translate("There are no extensions in the selected group");
                $this->renderScript('error/sneperror.phtml');
            } else {
                $ramalsrc = "";
                foreach ($origens as $key => $ramal) {
                    $num = $ramal['name'];
                    if (is_numeric($num)) {
                        $ramalsrc .= $num . ',';
                    }
                }
                $ramaissrc = " AND src in (" . trim($ramalsrc, ',') . ") ";
            }
        }

        if ($filter['selectDst'] != "0") {
            $groupdst = $filter['selectDst'];
            $destino = Snep_ExtensionsGroups_Manager::getExtensionsGroup($groupdst);
            if (count($destino) == 0) {
                $this->view->error_message = $this->view->translate("There are no extensions in the selected group");
                $this->renderScript('error/sneperror.phtml');
            } else {
                $ramaldst = "";
                foreach ($destino as $key => $ramal) {
                    $num = $ramal['name'];
                    if (is_numeric($num)) {
                        $ramaldst .= $num . ',';
                    }
                }
                $ramaisdst = " AND dst in (" . trim($ramaldst, ',') . ") ";
            }
        }

        // Src or dst value
        if ($filter['groupSrc'] != "") {
            $src = $filter['groupSrc'];
            if (strpos($src, ",")) {
                $where_src = '';
                $arrSrc = explode(",", $src);
                foreach ($arrSrc as $srcs) {
                    ($filter['order_src'] == 'equal') ? $where_src .= " OR (src = $srcs)" : $where_src .= " OR (src LIKE '%$srcs%')";
                }
                $where_src = " AND (" . substr($where_src, 3) . ")";
            } else {
                ($filter['order_src'] == 'equal') ? $where_src = "AND (src = $src)" : $where_src = "AND (src LIKE '%$src%')";
            }
        }

        if ($filter['groupDst'] != "") {
            $dst = $filter['groupDst'];
            if (strpos($dst, ",")) {
                $where_dst = '';
                $arrdst = explode(",", $dst);
                foreach ($arrdst as $dsts) {
                    ($filter['order_dst'] == 'equal') ? $where_dst .= " OR (dst = $dsts)" : $where_dst .= " OR (dst LIKE '%$dsts%')";
                }
                $where_dst = " AND (" . substr($where_dst, 3) . ")";
            } else {
                ($filter['order_dst'] == 'equal') ? $where_dst = "AND (dst = $dst)" : $where_dst = "AND (dst LIKE '%$dst%')";
            }
        }

        // Time call option
        ($filter['duration_init'] != "") ? $where_options[] = ' duration >= ' . $filter['duration_init'] . ' ' : null;
        ($filter['duration_end'] != "") ? $where_options[] = ' duration <= ' . $filter['duration_end'] . ' ' : null;

        // cost center
        if (!empty($filter['costs_center'])) {
            $cost_centers = $filter['costs_center'];
            if (count($cost_centers) > 0) {
                $tmp_cc = "";
                foreach ($cost_centers as $valor) {
                    $tmp_cc .= " cdr.accountcode like '" . $valor . "%'";
                    $tmp_cc .= " OR ";
                }
                $cost_centers = implode(",", $cost_centers);
                if ($tmp_cc != "") {
                    $where_cost_center .= " AND ( " . substr($tmp_cc, 0, strlen($tmp_cc) - 3) . " ) ";
                }
            } else {
                $where_cost_center = " cdr.accountcode like " . $cost_centers . "%";
            }
        }

        /* Where Prefix Login/Logout */
        $where_prefix = "";
        if (strlen($prefix_inout) > 3) {
            $cond_pio = "";
            $array_prefixo = explode(";", $prefix_inout);
            foreach ($array_prefixo as $valor) {
                $par = explode("/", $valor);
                $pio_in = $par[0];
                if (!empty($par[1])) {
                    $pio_out = $par[1];
                }
                $t_pio_in = strlen($pio_in);
                $t_pio_out = strlen($pio_out);
                $cond_pio .= " substr(dst,1,$t_pio_in) != '$pio_in' ";
                if (!$pio_out == '') {
                    $cond_pio .= " AND substr(dst,1,$t_pio_out) != '$pio_out' ";
                }
                $cond_pio .= " AND ";
            }
            if ($cond_pio != "") {
                $where_prefix .= " AND ( " . substr($cond_pio, 0, strlen($cond_pio) - 4) . " ) ";
            }
        }
        $where_prefix .= " AND ( locate('ZOMBIE',channel) = 0 ) AND dst NOT LIKE '*0%' AND src NOT LIKE '*0%' ";

        if ($where_options) {
            $where = "";
            foreach ($where_options as $key => $option) {
                $where .= ' AND (' . $option . ') ';
            }
        }

        // SQL
        $select = "SELECT date_format(cdr.calldate,'%d/%m/%Y') AS key_dia, date_format(cdr.calldate,'%d/%m/%Y %H:%i:%s') AS dia, ";
        $select .= "cdr.src, cdr.dst, cdr.disposition, cdr.duration, cdr.billsec, cdr.lastapp, cdr.accountcode,";
        $select .= "cdr.userfield, cdr.dcontext, cdr.amaflags, cdr.uniqueid, cdr.calldate, cdr.dstchannel";
        $select .= ",ccustos.codigo,ccustos.tipo,ccustos.nome";
        if (class_exists("Billing_Manager")) {
            $select .= ", bc.price FROM cdr ";
            $select .= " LEFT JOIN rated_calls bc ON bc.userfield = cdr.userfield ";
        } else {
            $select .= " FROM cdr ";
        }
        $select .= " LEFT JOIN ccustos ON accountcode = ccustos.codigo ";
        $select .= " WHERE ( calldate >= '$start_date' AND calldate <= '$end_date') ";
        $select .= (isset($where_cost_center)) ? $where_cost_center : '';
        $select .= (isset($where)) ? $where : '';
        $select .= (isset($where_contactGroupSrc)) ? $where_contactGroupSrc : '';
        $select .= (isset($where_contactSrc)) ? $where_contactSrc : '';
        $select .= (isset($where_contactGroupDst)) ? $where_contactGroupDst : '';
        $select .= (isset($where_contactDst)) ? $where_contactDst : '';
        $select .= (isset($where_src)) ? $where_src : '';
        $select .= (isset($where_dst)) ? $where_dst : '';
        $select .= (isset($ramaissrc)) ? $ramaissrc : '';
        $select .= (isset($ramaisdst)) ? $ramaisdst : '';
        $select .= (isset($where_binds)) ? $where_binds : '';
        $select .= $where_prefix;
        //$select .= " GROUP BY userfield ORDER BY calldate, userfield ";
        $select .= " ORDER BY calldate, userfield ";
        
        
        $stmt = $db->query($select);
        $cont = count($stmt);
        while ($dado = $stmt->fetch()) {
            $row[] = $dado;
        }
        
        return $row;

    }

    public function getAnalytic($filter)
    {

        $format = new Formata;
        $db = Zend_registry::get('db');
        $dateForm = explode(" - ", $filter["period"]);
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Reports"),$this->view->translate("Calls"),$this->view->translate("Analytic"),$dateForm[0] . ' - ' . $dateForm[1]));
        $this->view->exportName = $dateForm[0] . '_' . $dateForm[1];

        $row = $this->getSelect($filter);
        $locale_call = false;
        if (isset($filter['locale'])) {
            $locale_call = true;
        }

        $record = false;
        if (isset($filter['record'])) {
            $record = true;
        }

        $replace = false;
        if (isset($filter['replace'])) {
            $replace = true;
        }
        
        if ($replace) {
            $select_contacts = "SELECT `c`.id,`c`.name, `p`.`phone` FROM `contacts_names` AS `c` INNER JOIN `contacts_phone` AS `p` ON c.id = p.contact_id";
            $stmt = $db->query($select_contacts);
            $allContacts = $stmt->fetchAll();
            
            $select_peers = "SELECT name,callerid FROM `peers` WHERE peer_type = 'R'";
            $stmt = $db->query($select_peers);
            $allPeers = $stmt->fetchAll();

            foreach ($allContacts as $key => $value) {
                $contacts[$value['phone']] = $value['name'];
            }
            foreach ($allPeers as $key => $value) {
                $contacts[$value['name']] = $value['callerid'];
            }
        }
        
        foreach ($row as $key => $value) {
            
            //if (!$result_data[$value['uniqueid']]['disposition']) {
            $result_data[$value['uniqueid']]["disposition"] = $value["disposition"];  
            //}
            if ($result_data[$value['uniqueid']]['dia'] == null) {
                $result_data[$value['uniqueid']]['dia'] = $value["dia"];
            }
            if ($result_data[$value['uniqueid']]['billsec'] === null) {
                $result_data[$value['uniqueid']]['billsec'] = 0;
            }

            if($value['lastapp'] == 'Queue'){
              $result_data[$value['uniqueid']]["wasQueue"] = true;
            }

            switch ($value['disposition']) {
                case 'ANSWERED':
                    $result_data[$value['uniqueid']]['billsec'] = $result_data[$value['uniqueid']]['billsec'] + $value['billsec'];
                    $result_data[$value['uniqueid']]["disposition"] = $value["disposition"];
                    $result_data[$value['uniqueid']]["wasAttended"] = true;
                    $result_data[$value['uniqueid']]["class"] = "label label-success";
                    break;
                case 'NO ANSWER':
                    $result_data[$value['uniqueid']]["class"] = "label label-danger";
                    break;
                case 'BUSY':
                    $result_data[$value['uniqueid']]["class"] = "label label-warning";
                    break;
                default:
                    $result_data[$value['uniqueid']]["class"] = "label label-default";
                    break;
            }

            // treatment when no answer on first
            if($result_data[$value['uniqueid']]["wasAttended"]){
              $result_data[$value['uniqueid']]["disposition"] = 'ANSWERED';
              $result_data[$value['uniqueid']]["class"] = "label label-success";
            }

            if ($locale_call) {
                if (strlen($value["src"]) > 7 && strlen($value["dst"]) < 5) {
                    $result_data[$value['uniqueid']]["city"] = Snep_Cnl::getCity($value["src"]);
                } else {
                    $result_data[$value['uniqueid']]["city"] = Snep_Cnl::getCity($value["dst"]);
                }
            }

            $result_data[$value['uniqueid']]['file_path'] = false;
            if ($record) {
                $filePath = Snep_Manutencao::arquivoExiste($value['calldate'], $value['userfield']);
                $result_data[$value['uniqueid']]["file_name"] = $value['userfield'] . ".wav";

                if ($filePath) {
                    $result_data[$value['uniqueid']]['file_path'] = $filePath;
                    $result_data[$value['uniqueid']]['file_name'] = $filePath;
                    $result_data[$value['uniqueid']]['record'] = true;
                }
            }

            $result_data[$value['uniqueid']]["codigo"] = $value['codigo'];
            $result_data[$value['uniqueid']]["tipo"] = $value["tipo"];
            $result_data[$value['uniqueid']]["nome"] = $value["nome"];
            $result_data[$value['uniqueid']]["key_dia"] = $value["key_dia"];
            $result_data[$value['uniqueid']]["src"] = $value["src"];
            $result_data[$value['uniqueid']]["dst"] = $value["dst"];
            $result_data[$value['uniqueid']]["src_name"] = false;
            $result_data[$value['uniqueid']]["dst_name"] = false;

            if ($value['price'] !== "") {
                //$bill = money_format('%.2n', $value["price"]);
                $result_data[$value['uniqueid']]["price"] = $value["price"];
            }

            if ($replace) {
                if ($contacts[$value["src"]]) {
                    $result_data[$value['uniqueid']]["src_name"] = $contacts[$value["src"]];
                }
                if ($contacts[$value["dst"]]) {
                    $result_data[$value['uniqueid']]["dst_name"] = $contacts[$value["dst"]];
                }
            }

            $result_data[$value['uniqueid']]["duration"] += $value["duration"];
            $result_data[$value['uniqueid']]["accountcode"] = $value["accountcode"];
            $result_data[$value['uniqueid']]["userfield"] = $value["userfield"];
            $result_data[$value['uniqueid']]["dcontext"] = $value["dcontext"];
            $result_data[$value['uniqueid']]["amaflags"] = $value["amaflags"];
            $result_data[$value['uniqueid']]["uniqueid"] = $value["uniqueid"];
            $result_data[$value['uniqueid']]["calldate"] = $value["calldate"];
            $result_data[$value['uniqueid']]["dstchannel"] = $value["dstchannel"];

        }

        $this->view->bill = false;
        if (class_exists("Billing_Manager")) {
            $this->view->bill = true;
        }
        
        //totals
        $totals = array('totals' => 0, 'answered' => 0, 'noanswer' => 0, 'busy' => 0, 'failed' => 0, 'bill' => 0);
        $totals['totals'] = count($result_data);
        foreach ($result_data as $key => $value) {
            if($value['price']){
                $totals['bill'] += str_replace(',', '.', $value['price']);
            }
        
            switch ($value['disposition']) {
                case 'ANSWERED':
                    $totals['answered']++;
                    break;
                case 'NO ANSWER':
                    $totals['noanswer']++;
                    break;
                case 'BUSY':
                    $totals['busy']++;
                    break;
                default:
                    $totals['failed']++;
                    break;
            }
        }
        
        $this->view->totals = $totals;
        $this->view->call_list = $result_data;
        $this->view->format = $format;
        $this->view->locale = $locale_call;
        $this->view->record = $record;
        
        $this->renderScript('calls-report/analytic.phtml');

    }

    public function getSynthetic($filter)
    {

        $dateForm = explode(" - ", $filter["period"]);

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
            $this->view->translate("Reports"),
            $this->view->translate("Calls"),
            $this->view->translate("Synthetic"),
            $dateForm[0] . ' - ' . $dateForm[1]));

        $this->view->bill = false;
        if (class_exists("Billing_Manager")) {
            $this->view->bill = true;
        }

        $this->view->exportName = $dateForm[0] . '_' . $dateForm[1];

        $row = $this->getSelect($filter);

        foreach ($row as $key => $value) {
            
            
            $result_data[$value['uniqueid']]["disposition"] = $value["disposition"];  
            
            if ($result_data[$value['uniqueid']]['dia'] == null) {
                $result_data[$value['uniqueid']]['dia'] = $value["dia"];
            }
            if ($result_data[$value['uniqueid']]['billsec'] === null) {
                $result_data[$value['uniqueid']]['billsec'] = 0;
            }

            if($value['lastapp'] == 'Queue'){
                $result_data[$value['uniqueid']]["wasQueue"] = true;
            }

            switch ($value['disposition']) {
                case 'ANSWERED':
                    $result_data[$value['uniqueid']]['billsec'] = $result_data[$value['uniqueid']]['billsec'] + $value['billsec'];
                    $result_data[$value['uniqueid']]["disposition"] = $value["disposition"];
                    $result_data[$value['uniqueid']]["wasAttended"] = true;
                    break;
            }

            if ($value['price'] !== "") {
                //$bill = money_format('%.2n', $value["price"]);
                $result_data[$value['uniqueid']]["price"] = $value["price"];
            }

            // treatment when no answer on first
            if($result_data[$value['uniqueid']]["wasAttended"]){
                $result_data[$value['uniqueid']]["disposition"] = 'ANSWERED';
            }

            $result_data[$value['uniqueid']]["codigo"] = $value['codigo'];
            $result_data[$value['uniqueid']]["tipo"] = $value["tipo"];
            $result_data[$value['uniqueid']]["key_dia"] = $value["key_dia"];
            $result_data[$value['uniqueid']]["accountcode"] = $value["accountcode"];
            
        }
                
        $totals = array('answer' => 0, 'noanswer' => 0, 'busy' => 0, 'failed' => 0, 'totals' => 0, 'bill' => 0);
        $typeCall = array('incoming' => 0, 'outgoing' => 0, 'other' => 0);
        
        foreach ($result_data as $key => $value) {
            
            if (isset($ccustos[$value['accountcode']])) {
                $ccustos[$value['accountcode']]['cont']++;
            } else {
                $ccustos[$value['accountcode']] = array('name' => $value['accountcode'], 'cont' => 1);
            }

            if (!isset($calldate[$value['key_dia']])) {
                $calldate[$value['key_dia']]['totals'] = 0;
                $calldate[$value['key_dia']]['answer'] = 0;
                $calldate[$value['key_dia']]['noanswer'] = 0;
                $calldate[$value['key_dia']]['busy'] = 0;
                $calldate[$value['key_dia']]['failed'] = 0;
            }
            $calldate[$value['key_dia']]['totals']++;

            if($value['price']){
                $totals['bill'] += str_replace(',', '.', $value['price']);
            }
            
            switch ($value["disposition"]) {
                case 'ANSWERED':
                    $totals["answer"]++;
                    $calldate[$value['key_dia']]['answer']++;
                    break;
                case 'NO ANSWER':
                    $totals["noanswer"]++;
                    $calldate[$value['key_dia']]['noanswer']++;
                    break;
                case 'BUSY':
                    $totals["busy"]++;
                    $calldate[$value['key_dia']]['busy']++;
                    break;
                case 'FAILED':
                    $totals["failed"]++;
                    $calldate[$value['key_dia']]['failed']++;
                    break;
            }

            switch ($value['tipo']) {
                case 'E':
                    $typeCall["incoming"]++;
                    break;
                case 'S':
                    $typeCall["outgoing"]++;
                    break;
                default:
                    $typeCall["other"]++;
                    break;
            }
        }
        $totals["totals"] = $totals["answer"] + $totals["noanswer"] + $totals["failed"] + $totals["busy"];

        $db = Zend_registry::get('db');
        $select = "SELECT * FROM ccustos";
        $stmt = $db->query($select);
        $ccustosAll = $stmt->fetchAll();

        foreach ($ccustos as $x => $cc) {
            foreach ($ccustosAll as $t => $tag) {
                if ($x == $tag['codigo']) {
                    $ccustos[$x]['name'] = $cc['name'] . ' - ' . $tag['nome'];
                }
            }
        }
        
        $this->view->totals = $totals;
        $this->view->typeCall = $typeCall;
        $this->view->calldate = $calldate;
        $this->view->ccustos = $ccustos;

        if ($totals['totals'] == 0) {
            $this->view->error_message = $this->view->translate("No entries found.");
            $this->view->error_title = $this->view->translate("Alert");
            $this->renderScript('error/sneperror.phtml');
        } else {
            $this->renderScript('calls-report/synthetic.phtml');
        }

    }

}
