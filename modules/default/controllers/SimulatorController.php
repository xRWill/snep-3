<?php
/*
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
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
 * Route Rules Simulator.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class SimulatorController extends Zend_Controller_Action {


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
     * @return type
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routes"),
                    $this->view->translate("Simulator")));

        $trunks = array();
        foreach (PBX_Trunks::getAll() as $value) {
            $trunks[$value->getId()] = $value->getId() . " - " . $value->getName();
        }

        $this->view->trunks = $trunks;
        if ($this->_request->getPost()) {


            $formData = $this->_request->getParams();

            $extension = isset($formData['dst']) && $formData['dst'] != "" ? $formData['dst'] : 's';
            $srcType = isset($formData['srcType']) ? $formData['srcType'] : NULL;
            $trunk = isset($formData['trunk']) ? $formData['trunk'] : NULL;
            $caller = isset($formData['caller']) && $formData['caller'] != "" ? $formData['caller'] : "unknown";
            $datetime = isset($formData['ruleDay']) ? $formData['ruleDay'] : NULL;
            if($datetime){
              $time = explode(' ',$datetime)[1];
              $date = explode(' ',$datetime)[0];
              $day = explode(' ',$datetime)[2];
            }

            // $time = isset($formData['time']) ? $formData['time'] : NULL;
            // $date = ($formData['week'] == 'current') ? NULL : $formData['week'];

            $dialplan = new PBX_Dialplan_Verbose();

            if ($srcType == "exten") {
                try {
                    $srcObj = PBX_Usuarios::get($caller);
                } catch (PBX_Exception_NotFound $ex) {
                    $this->view->error_message = $this->view->translate($ex->getMessage());
                    $this->renderScript('error/sneperror.phtml');
                    return;
                }
                $channel = $srcObj->getInterface()->getCanal();
            } else if ($srcType == "trunk") {
                $srcObj = PBX_Trunks::get($trunk);
                $channel = $srcObj->getInterface()->getCanal();
            } else {
                $srcObj = null;
                $channel = "unknown";
            }

            $request = new PBX_Asterisk_AGI_Request(array(
                "agi_callerid" => $caller,
                "agi_extension" => $extension,
                "agi_channel" => $channel));

            $request->setSrcObj($srcObj);
            $dialplan->setRequest($request);

            if ($time) {
                $dialplan->setTime($time);
            }
            
            if($day){
                $dialplan->setDay($day);
            }
            if($date){
                $dialplan->setDate($date);
            }

            try {
                $dialplan->parse();
            } catch (PBX_Exception_NotFound $ex) {
                $this->view->error_message = $this->view->translate("No rule found.");
                $this->renderScript('error/sneperror.phtml');
                return;
            }

            if (count($dialplan->getMatches()) > 0) {
                $firstTorun = false;
                foreach ($dialplan->getMatches() as $index => $rule) {
                    
                    $state = "outdated";
                    if ($dialplan->getLastRule()) {                      
                        if(!$firstTorun){
                            $firstTorun = true;  
                            $state = "torun";
                        }else{
                            $state = "ignored";
                        }   
                    } 
                    
                    if (!$rule->isActive()) {
                        $state = "inactive";
                    }                     

                    $actions = array();

                    foreach ($rule->getAcoes() as $action) {
                        $config = $action->getConfigArray();
                        if ($action instanceof CCustos) {
                            $actions[] = $this->view->translate("Define Tag to ") . $config['ccustos'];
                        } else if ($action instanceof DiscarTronco) {
                            $tronco = PBX_Trunks::get($config['tronco']);
                            $actions[] = $this->view->translate("Dial through Trunk ") . $tronco->getName();
                        } else if ($action instanceof DiscarRamal) {
                            if (isset($config['ramal']) && $config['ramal'] != "") {
                                $peer = $config['ramal'];
                            } else {
                                $peer = $extension;
                            }

                            try {
                                $ramal = PBX_Usuarios::get($peer);
                                $actions[] = $this->view->translate("Dial to extension %s", $ramal->getCallerid());
                            } catch (PBX_Exception_NotFound $ex) {
                                $actions[] = "<strong style='color:red'>" . $this->view->translate("Failure on trial to dial extension %: non existent extension", $extension) . "</strong>";
                            }
                        } else if ($action instanceof Queue) {
                            $actions[] = $this->view->translate("Direct to queue %s", $config['queue']);
                        } else if ($action instanceof Cadeado) {
                            $actions[] = $this->view->translate("Request password");
                        } else if ($action instanceof Context) {
                            $actions[] = $this->view->translate("Redirect to context %s", $config['context']);
                        }
                    }

                    $srcs = array();
                    foreach ($rule->getSrcList() as $src) {
                        $srcs[] = trim(implode(":", $src), ':');
                    }
                    $srcs = implode(",", $srcs);
                    $dsts = array();
                    foreach ($rule->getDstList() as $dst) {
                        $dsts[] = trim(implode(":", $dst), ':');
                    }
                    $dsts = implode(",", $dsts);
                    
                    $result[$index] = array(
                        "id" => $rule->getId(),
                        "state" => $state,
                        "caller" => $srcs,
                        "dst" => $dsts,
                        "desc" => $rule->getDesc(),
                        "valid" => join(";", $rule->getValidTimeList()),
                        "days" => $rule->getValidWeekDays(),
                        "actions" => $actions);
                }
                
                if($date == NULL){
                    $date = date("Y-m-d");
                }else{
                    $table = array('mon'=> $this->view->translate('Monday'),
                               'tue'=> $this->view->translate('Tuesday'),
                               'wed'=> $this->view->translate('Wednesday'),
                               'thu'=> $this->view->translate('Thursday'),
                               'fri'=> $this->view->translate('Friday'),
                               'sat'=> $this->view->translate('Saturday'),
                               'sun'=> $this->view->translate('Sunday'));
                    
                    $date = strtr($date, $table);
                }
                                
                $time = $dialplan->getLastExecutionTime();
                
                $input = array(
                  "caller" => $caller,
                  "dst" => $extension,
                  "time" => $time,
                  "date" => $date,
                  "day" => $day
                );
                
                // validate days in week
                foreach ($result as $key => $value) {
                    
                    $dayofweek = date('D', strtotime($date));
                    $date = strtolower($dayofweek);             
                    $valid = explode("-",$value["valid"]);
                    
                    if(($time >= $valid[0] && $time <= $valid[1]) && $value["state"] == "torun"){
                        if(!in_array($date, $value["days"])){
                            $result[$key]["state"] = "outdated";
                        }                        
                    }else{
                        if($value["state"] == "torun"){
                            $result[$key]["state"] = "outdated";
                        }
                    }                    

                    $table = array('mon'=> $this->view->translate('Monday'),
                               'tue'=> $this->view->translate('Tuesday'),
                               'wed'=> $this->view->translate('Wednesday'),
                               'thu'=> $this->view->translate('Thursday'),
                               'fri'=> $this->view->translate('Friday'),
                               'sat'=> $this->view->translate('Saturday'),
                               'sun'=> $this->view->translate('Sunday'));

                    foreach ($value["days"] as $x => $day) {
                        $result[$key]["days"][$x] = $table[$day];
                    }
                }
                
                $this->view->input = $input;
                $this->view->result = $result;
            }
        }
    }

}
