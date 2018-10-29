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

/**
 * Ranking Report Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class RankingReportController extends Zend_Controller_Action
{

    /**
     * @var Zend_Form
     */
    private $form;

    /**
     * Initial settings of the class
     */
    public function init()
    {

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
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

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Call Rankings")));

        $config = Zend_Registry::get('config');

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

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $filter = $this->_request->getParams();

        $dateForm = explode(" - ", $filter["period"]);
        $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Reports"), $this->view->translate("Ranking"), $dateForm[0] . ' - ' . $dateForm[1]));

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

        $filter['type'] = $filter['type'];
        $filter['showsource'] = $filter['showsource'];
        $filter['showdestiny'] = $filter['showdestiny'];
        $out_type = $filter['out_type'];

        $replace = false;
        if (isset($filter['replace'])) {
            $filter['replace'] = true;
            $replace = true;
        }

        $data = $this->getData($filter);

        $this->view->rank = $data['quantity'];
        $this->view->totals = (array) $data['totals'];
        $this->view->type = $data['type'];
        $this->renderScript('ranking-report/view.phtml');

    }

    public function getData($filter)
    {

        $dateForm = explode(" - ", $filter["period"]);

        $date = Snep_Reports::fmt_date($dateForm[0], $dateForm[1]);
        $start_date = $date['start_date'] . " " . $date['start_hour'];
        $end_date = $date['end_date'] . " " . $date['end_hour'];

        $showsource = $filter['showsource'];
        $showdestiny = $filter['showdestiny'];
        $rankType = $filter['type'];

        // Check Bon
        $db = Zend_registry::get('db');
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $user = Snep_Users_Manager::getName($username);
        $format = new Formata;

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
                $where_binds = " AND (src NOT IN (" . $where_binds . ") AND dst NOT IN (" . $where_binds . "))";
            } else {
                $where_binds = " AND (src IN (" . $where_binds . ") OR dst IN (" . $where_binds . "))";
            }
        }

        $prefix_inout = $config->ambiente->prefix_inout;

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

        $condDstExp = $where_exceptions = "";

        $where_exceptions .= " AND ( locate('ZOMBIE',channel) = 0 ) ";

        $select = "SELECT cdr.src, cdr.dst, cdr.disposition, cdr.duration, cdr.billsec, cdr.userfield, cdr.uniqueid ";
        $select .= " FROM cdr JOIN peers on cdr.src = peers.name WHERE";
        $select .= " ( calldate >= '$start_date' AND calldate <= '$end_date')";
        $select .= isset($where_binds) ? $where_binds : '';
        $select .= isset($where_prefix) ? $where_prefix : '';
        $select .= isset($where_exceptions) ? $where_exceptions : '';
        $select .= " ORDER BY calldate,userfield,cdr.amaflags";

        $userfield = "XXXXXXXXX";
        $flag_ini = true;
        $dados = array();

        foreach ($db->query($select) as $row) {
            /* userfield equals */
            if ($userfield != $row['userfield']) {
                if ($flag_ini) {
                    $result[$row['uniqueid']] = $row;
                    $userfield = $row['userfield'];
                    $flag_ini = false;
                    continue;
                }
            } else {
                $result[$row['uniqueid']] = $row;
                continue;
            }

            foreach ($result as $val) {

                $src = $val['src'];

                $dst = $val['dst'];

                if (!isset($dados[$src][$dst])) {
                    $dados[$src][$dst]["QA"] = 0;
                    $dados[$src][$dst]["QN"] = 0;
                    $dados[$src][$dst]["QT"] = 0;
                    $dados[$src][$dst]["TA"] = 0;
                    $dados[$src][$dst]["TN"] = 0;
                    $dados[$src][$dst]["TT"] = 0;
                    $totais_q[$src] = 0;
                    $totais_t[$src] = 0;
                }

                switch ($val['disposition']) {
                    case "ANSWERED":
                        $dados[$src][$dst]["QA"]++;
                        $dados[$src][$dst]["TA"] += $val['duration'];
                        break;
                    default:
                        $dados[$src][$dst]["QN"]++;
                        $dados[$src][$dst]["TN"] += $val['duration'];
                        break;
                }

                (isset($dados[$src][$dst]["QT"])) ? $dados[$src][$dst]["QT"]++ : $dados[$src][$dst]["QT"] = 0;
                (isset($dados[$src][$dst]["TT"])) ? $dados[$src][$dst]["TT"] += $val['duration'] : $dados[$src][$dst]["TT"] = 0;
                $totais_q[$src]++;
                $totais_t[$src] += $val['duration'];
            }
            unset($result);
            $result[$row['uniqueid']] = $row;
            $userfield = $row['userfield'];
        }

        // no entries
        if (empty($dados)) {
            $this->view->error_message = $this->view->translate("No entries found.");
            $this->view->error_title = $this->view->translate("Alert");
            $this->renderScript('error/sneperror.phtml');
        } else {

            /* possible last call */
            foreach ($result as $val) {
                $src = $val['src'];
                $dst = $val['dst'];
                if (!isset($dados[$src][$dst])) {
                    $dados[$src][$dst]["QA"] = 0;
                    $dados[$src][$dst]["QN"] = 0;
                    $dados[$src][$dst]["QT"] = 0;
                    $dados[$src][$dst]["TA"] = 0;
                    $dados[$src][$dst]["TN"] = 0;
                    $dados[$src][$dst]["TT"] = 0;
                    $totais_q[$src] = 0;
                    $totais_t[$src] = 0;
                }
                switch ($val['disposition']) {
                    case "ANSWERED":

                        $dados[$src][$dst]["QA"]++;
                        $dados[$src][$dst]["TA"] += $val['duration'];
                        break;
                    default:
                        $dados[$src][$dst]["QN"]++;
                        $dados[$src][$dst]["TN"] += $val['duration'];
                        break;
                }

                (isset($dados[$src][$dst]["QT"])) ? $dados[$src][$dst]["QT"]++ : $dados[$src][$dst]["QT"] = 0;
                (isset($dados[$src][$dst]["TT"])) ? $dados[$src][$dst]["TT"] += $val['duration'] : $dados[$src][$dst]["TT"] = 0;
                $totais_q[$src]++;
                $totais_t[$src] += $val['duration'];
            }

            arsort($totais_q);
            arsort($totais_t);

            $array = array_chunk($totais_q, $showsource, true);
            $array_rank = $array[0];

            // ordered quantity by destination
            foreach ($array_rank as $phone => $tot) {
                foreach ($dados as $key => $values) {

                    if ($phone == $key) {

                        foreach ($values as $destiny => $value) {

                            if ($rankType == "num") {
                                $arr[$phone][$destiny] = $value['QT'];
                                arsort($arr[$phone]);
                            } else {
                                $arr[$phone][$destiny] = $value['TT'];
                                arsort($arr[$phone]);
                            }
                        }
                    }
                }

                $arr = array_chunk($arr[$phone], $showdestiny, true);
                $value_array[$phone] = $arr[0];

            }

            // ordered array as quantity
            foreach ($value_array as $phone => $value) {

                // Totals
                if (!isset($totals[$phone])) {
                    $totals[$phone] = 0;
                }

                // Totals
                if (!isset($rank_totals[$phone])) {
                    $rank_totals[$phone] = 0;
                }

                foreach ($dados as $key => $dado) {
                    if ($phone == $key) {
                        foreach ($value as $destiny => $x) {
                            foreach ($dado as $dst => $val) {
                                if ($destiny == $dst) {
                                    $rank_final[$phone][$destiny] = $val;

                                    if ($rankType == "num") {
                                        $totals[$phone] += $val['QT'];
                                        $rank_totals[$phone] += $val['QT'];
                                    } else {
                                        $totals[$phone] += $val['TT'];
                                        $rank_totals[$phone] += $val['TT'];
                                    }

                                }
                            }
                        }
                    }
                }
            }

            // ordered array associative
            array_multisort($totals, $rank_final);

            //format hh:mm:ss
            foreach ($rank_final as $key => $dado) {
                foreach ($dado as $x => $value) {

                    if (isset($value['TA'])) {
                        $rank_final[$key][$x]['TA'] = $format->fmt_segundos(array("a" => $value['TA'], "b" => 'hms'));
                    } else {
                        $rank_final[$key][$x]['TA'] = "00:00:00";
                    }

                    if (isset($param['TN'])) {
                        $rank_final[$key][$x]['TN'] = $format->fmt_segundos(array("a" => $value['TN'], "b" => 'hms'));
                    } else {
                        $rank_final[$key][$x]['TN'] = "00:00:00";
                    }

                    if (isset($value['TT'])) {
                        $rank_final[$key][$x]['TT'] = $format->fmt_segundos(array("a" => $value['TT'], "b" => 'hms'));
                    } else {
                        $rank_final[$key][$x]['TT'] = "00:00:00";
                    }
                }
            }

            // ordered key and value
            arsort($rank_totals);
            $cont = 0;
            $rankfinal = array_reverse($rank_final);

            if ($rankType != "num") {
                foreach ($rank_totals as $key => $value) {
                    $rank_totals[$key] = $format->fmt_segundos(array("a" => $value, "b" => 'hms'));
                }
            }

            foreach ($rank_totals as $x => $value) {

                $rank[$x] = $rankfinal[$cont];
                $cont++;
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

                foreach ($rank as $n => $numbers) {

                    if ($contacts[$n]) {

                        $rank[$contacts[$n] . ' ->' . $n] = $rank[$n];
                        unset($rank[$n]);
                    } else {
                        $val = $rank[$n];
                        unset($rank[$n]);
                        $rank[$n . ' ->' . $n] = $val;
                    }
                }

                foreach ($rank as $n => $numbers) {
                    foreach ($numbers as $dst => $value) {
                        if ($contacts[$dst]) {
                            $rank[$n][$contacts[$dst]] = $rank[$n][$dst];
                            unset($rank[$n][$dst]);
                        } else {
                            $val = $rank[$n][$dst];
                            unset($rank[$n][$dst]);
                            $rank[$n][$dst] = $val;

                        }
                    }
                }
            }
            return array("quantity" => $rank, "type" => $rankType, "totals" => $rank_totals);

        }

    }

}
