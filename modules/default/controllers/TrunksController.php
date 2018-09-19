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
require_once "includes/AsteriskInfo.php";

/**
 * Trunk Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class TrunksController extends Zend_Controller_Action {

  /**
  * @var Zend_Form
  */
  protected $boardData;

  /**
  * preDispatch
  */
  public function preDispatch() {

    // Test Asterisk connection
    try {
      $astinfo = new AsteriskInfo();
      // Read Khomp links
      try {
        $data = $astinfo->status_asterisk("khomp links show concise", "", True) ;
      } catch (Exception $e) {
        $this->view->error_message = $this->view->translate("Socket connection to the server is not available at the moment.");
        $this->renderScript('error/sneperror.phtml');;
      }
    } catch (Exception $e) {
      $this->view->error_message =  $this->view->translate("Error! Failed to connect to server Asterisk.");
      $this->renderScript('error/sneperror.phtml');
    }

  }

  public function init() {
    $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
    $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;

    // Add dashboard button
    $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $this->view->key = Snep_Dashboard_Manager::getKey(Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
    Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
    Zend_Controller_Front::getInstance()->getRequest()->getActionName());

    // Informações de placas khomp
    try {
      $khomp_info = new PBX_Khomp_Info();
      $khomp_boards = array();
      if ($khomp_info->hasWorkingBoards()) {
        foreach ($khomp_info->boardInfo() as $board) {
          if (!preg_match("/FXS/", $board['model'])) {
            $khomp_boards["b" . $board['id']] = "{$board['id']} - " . $this->view->translate("Board") . " {$board['model']}";
            $id = "b" . $board['id'];
            if (preg_match("/E1/", $board['model'])) {
              for ($i = 0; $i < $board['links']; $i++)
              $khomp_boards["b" . $board['id'] . "l$i"] = $board['model'] . " - " . $this->view->translate("Link") . " $i";
            } else {
              for ($i = 0; $i < $board['channels']; $i++)
              $khomp_boards["b" . $board['id'] . "c$i"] = $board['model'] . " - " . $this->view->translate("Channel") . " $i";
            }
          }
        }
        $this->khompBoards = $khomp_boards;
      }
    } catch (Exception $e) {

    }
  }

  /**
  * indexAction
  */
  public function indexAction() {

    $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Trunks")));

    $db = Zend_Registry::get('db');
    $select = "SELECT t.id, t.callerid, t.name, t.technology, t.trunktype, t.time_chargeby, t.time_total,t.disabled,
    (SELECT th.used FROM time_history AS th WHERE th.owner = t.id AND th.owner_type='T' ORDER BY th.changed DESC limit 1) as used,
    (SELECT th.changed FROM time_history AS th WHERE th.owner = t.id AND th.owner_type='T' ORDER BY th.changed DESC limit 1) as changed FROM trunks as t ";

    $datasql = $db->query($select);
    $trunks = $datasql->fetchAll();

    foreach ($trunks as $id => $val) {
      
      $trunks[$id]['saldo'] = null;

      if (!is_null($val['time_total'] )) {
        $call = $val['changed'];
        $callYear = substr($call, 0, 4);
        $callMonth = substr($call, 5, 2);
        $callDay = substr($call, 8, 2);

        $sale = 0;
        $val['time_total'] = $val['time_total']*60; // converter minutos for seconds
        
        switch ($val['time_chargeby']) {
          case 'Y':
          if ($callYear == date('Y')) {
            $sale = $val['time_total'] - $val['used'];
            if ($val['used'] >= $val['time_total']) {
              $sale = 0;
            }
          } else {
            $sale = $val['time_total'];
          }
          break;
          case 'M':
            $sale = $val['time_total'] - $val['used'];
            if ($val['used'] >= $val['time_total']) {
              $sale = 0;
            }
          break;
          case 'D':
          if ($callYear == date('Y') && $callMonth == date('m') && $callDay == date('d')) {

            $sale = $val['time_total'] - $val['used'];
          } else {
            $sale = $val['time_total'];
          }

          break;
        }

        if($sale < 0){
          $trunks[$id]['saldo'] = 0;
        }else{
          $trunks[$id]['saldo'] = round($sale/60); // converter seconds for minutos
        }
      }
    }

    $this->view->trunks = $trunks;

  }

  /**
  * addAction - Add trunk
  * @return type
  * @throws ErrorException
  * @throws Exception
  */
  public function addAction() {

    $this->view->breadcrumb = $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Trunks"),$this->view->translate("Add")));

    // Mont codec's list and sets the default codec for each option
    $codecsDefault = array("alaw","ilbc","g729","gsm","h264","h263","h263p","ulaw");
    $codec1 = "";$codec2 = "";$codec3 = "";

    foreach($codecsDefault as $key => $value){
      $codec1 .= '<option value="'.$value.'"'.($value==="alaw" ? " selected " : "").'>'.$value.'</option>\n';
      $codec2 .= '<option value="'.$value.'"'.($value==="ulaw" ? " selected " : "").'>'.$value.'</option>\n';
      $codec3 .= '<option value="'.$value.'"'.($value==="gsm"  ? " selected " : "").'>'.$value.'</option>\n';
    } // END foreach

    $this->view->codec1 = $codec1;
    $this->view->codec2 = $codec2;
    $this->view->codec3 = $codec3;

    $this->view->dtmf_rfc2833 = 'checked' ;

    $this->view->nat_no = 'checked';

    $this->view->type_friend = 'checked';

    // Informações de placas khomp
    $boards = "";
    if (isset($this->khompBoards)) {
      foreach($this->khompBoards as $key => $value){

        $boards .= '<option value="'.$key.'">'.$value.'</option>\n';
      }
    }
    $this->view->boards = $boards;

    if (class_exists("Telcos_Manager")) {
      $this->view->telcos = Telcos_Manager::getAll();
    }else{
      $this->view->telcos = array();
    }

    //Define the action and load form
    $this->view->action = "add" ;
    $this->view->techType = "sip";
    $this->view->sip = 'selected' ;
    $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

    //After POSt
    if ($this->getRequest()->isPost()) {

      $form_isValid = true;

      // Trunk name validation
      $newId = Snep_Trunks_Manager::getName($_POST['callerid']);

      if (count($newId) > 1) {
        $form_isValid = false;
        $message = $this->view->translate("Name already exists.");
        $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
      }

      if ($form_isValid) {

        // Mount array whith trunk data
        $trunk_data = $this->preparePost();
        if(isset($_POST['trunk_disabled'])){
          $trunk_data['trunk']["disabled"] = true;
        }

        $db = Snep_Db::getInstance();
        $db->beginTransaction();
        try {

          $db->insert("trunks", $trunk_data['trunk']);
          $id = $db->lastInsertId();

          if($trunk_data['trunk']['trunktype'] == "I") {
            $trunk_data['ip']["name"] = $trunk_data['trunk']["name"];
            $db->insert("peers", $trunk_data['ip']);
          }
          $db->commit();

        } catch (Exception $ex) {
          $db->rollBack();
          throw $ex;
        }
        
        // audit
        Snep_Audit_Manager::SaveLog("Added", 'trunks', $id, $this->view->translate("Trunk") . " {$id} ". $_POST['callerid']);
        
        if(!isset($_POST['trunk_disabled'])){
          Snep_InterfaceConf::loadConfFromDb();
        }
        
        $this->_redirect("trunks");
      }
    }

  }

  /**
  * enableAction - Enable trunk
  * @return type
  * @throws ErrorException
  */
  public function enableAction() {

    $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Trunks"),$this->view->translate("Enable")));

    $exten = $this->_request->getParam("id");

    $this->view->id = $exten;
    $this->view->remove_title = $this->view->translate('Enabled Trunk.');
    $this->view->remove_message = $this->view->translate('Are you sure you want to activate the trunk?');
    $this->view->remove_form = 'trunks';
    $this->renderScript('remove/enable.phtml');

    if ($this->_request->getPost()) {

      Snep_Audit_Manager::SaveLog("Enabled", 'trunks', $exten, $this->view->translate("Trunk") ." ". $exten);
      Snep_Trunks_Manager::enable($exten);
      Snep_InterfaceConf::loadConfFromDb();
      $this->_redirect("trunks");
    }
  }

  /**
  * editAction - Edit trunk
  * @return type
  * @throws ErrorException
  * @throws Exception
  */
  public function editAction() {

    $this->view->breadcrumb = $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
      $this->view->translate("Trunks"),
      $this->view->translate("Edit trunk")));

      $idTrunk = mysql_escape_string($this->getRequest()->getParam("trunk"));

      $db = Snep_Db::getInstance();
      $trunk = $db->query("select * from trunks where id='$idTrunk'")->fetch();
      $trunk['qualify_value'] = "";
      if (class_exists("Telcos_Manager")) {
        $this->view->telcos = Telcos_Manager::getAll();
      }else{
        $this->view->telcos = array();
      }

      if ($trunk['trunktype'] == "I") {
        $ip_info = $db->query("select * from peers where name='{$trunk['name']}'")->fetch();
        $this->view->infoTrunk = $ip_info;

        $type = $ip_info["type"];
        $label = "type_".$type;
        $this->view->$label = "checked";

        $qualify = $ip_info["qualify"];
        $label = "qualify_".$qualify;
        $this->view->$label = "checked";

        if ( $ip_info['qualify'] != 'yes' && $ip_info['qualify'] != 'no' ) {
          $trunk['qualify_value'] = $ip_info['qualify'] ;
          $this->view->qualify_specify = "checked";
        }

        $array_nat = explode(",",$ip_info['nat']);
        foreach($array_nat as $key => $val) {
          $label = "nat_".$val;
          $this->view->$label = "checked";
        }

      }

      // Trunk technology
      $technologyTrunk = strtolower($trunk['technology']);

      $this->view->sip     = ($technologyTrunk === "sip" ? "selected" : "");
      $this->view->iax2    = ($technologyTrunk === "iax2" ? "selected" : "");
      $this->view->virtual = ($technologyTrunk === "virtual" ? "selected" : "");
      $this->view->khomp = ($technologyTrunk === "khomp" ? "selected" : "");
      $this->view->snepsip = ($technologyTrunk === "snepsip" ? "selected" : "");
      $this->view->snepiax2 = ($technologyTrunk === "snepiax2" ? "selected" : "");
      $this->view->techType   = $technologyTrunk; //"selected";
      $this->view->technology = $technologyTrunk;

      $this->view->dtmf_dial = ($trunk['dtmf_dial'] == '0') ? "" : "checked" ;
      $this->view->reverse_auth = ($trunk['reverse_auth'] == '0') ? "" : "checked" ;
      $this->view->map_extensions = ($trunk['map_extensions'] == '0') ? "" : "checked" ;
      $this->view->tempo = ($trunk['time_total'] > 0) ? "checked" : "" ;

      $this->view->name = $trunk['name'];

      $dialmethod = $trunk["dialmethod"];
      $label = "dialmethod_".$dialmethod;
      $this->view->$label = "checked";

      $dtmf = $trunk["dtmfmode"];
      $label = "dtmf_".$dtmf;
      $this->view->$label = "checked";

      $time_chargeby = $trunk['time_chargeby'];
      $label = "chargeby_".$time_chargeby;
      $this->view->$label = "checked";

      $trunk['identifier'] = $trunk['username'];

      $codecsDefault = array("ulaw","alaw","ilbc","g729","gsm","h264","h263","h263p");
      $codecs = explode(";", $trunk['allow']);

      $codec1 = "";
      $codec2 = "";
      $codec3 = "";
      foreach($codecsDefault as $key => $value){

        $codec1 .= ($value == $codecs[0]) ? '<option value="'.$value.'" selected>'.$value.'</option>\n' : '<option value="'.$value.'">'.$value.'</option>\n';
        $codec2 .= ($value == $codecs[1]) ? '<option value="'.$value.'" selected>'.$value.'</option>\n' : '<option value="'.$value.'">'.$value.'</option>\n';
        $codec3 .= ($value == $codecs[2]) ? '<option value="'.$value.'" selected>'.$value.'</option>\n' : '<option value="'.$value.'">'.$value.'</option>\n';

      }

      $this->view->codec1 = $codec1;
      $this->view->codec2 = $codec2;
      $this->view->codec3 = $codec3;

      // Khomp boards
      $boards = "";
      if (isset($this->khompBoards)){
        foreach($this->khompBoards as $key => $value){
          $boards .= ("KHOMP/".$key == $trunk['channel']) ? '<option value="'.$key.'" selected>'.$value.'</option>\n' : '<option value="'.$key.'">'.$value.'</option>\n';
        }
      }

      if($trunk['disabled']){
        $this->view->trunk_disabled = "checked";
      }

      $this->view->boards = $boards;
      $this->view->trunk = $trunk;

      //Define the action and load form
      $this->view->action = "edit" ;
      $this->view->disabled = "disabled" ;
      $this->renderScript( $this->getRequest()->getControllerName().'/addedit.phtml' );

      //After POST
      if ($this->getRequest()->isPost()) {

        $form_isValid = true;

        $newId = Snep_Trunks_Manager::getName($_POST['callerid']);

        if (count($newId) > 1 && $_POST['callerid'] != $trunk['callerid']) {
          $form_isValid = false;
          $message = $this->view->translate("Name already exists.");
          $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
        }

        if ($form_isValid) {

          $trunk_data = $this->preparePost();

          $db = Snep_Db::getInstance();
          $db->beginTransaction();
          try {
            $db->update("trunks", $trunk_data['trunk'], "id='$idTrunk'");
            if ($trunk_data['trunk']['trunktype'] === "I") {
              $db->update("peers", $trunk_data['ip'], "name='{$trunk_data['trunk']['name']}' and peer_type='T'");
            }
            $db->commit();

          } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
          }
          //audit
          Snep_Audit_Manager::SaveLog("Updated", 'trunks', $idTrunk, $this->view->translate("Trunk") . " {$idTrunk} ". $_POST['callerid']);
          
          if(!isset($_POST['trunk_disabled'])){
            Snep_InterfaceConf::loadConfFromDb();
          }
          
          $this->_redirect("trunks");
        }
      }
    }

    /**
    * removeAction - Remove trunk
    */
    public function removeAction() {

      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Trunks"), $this->view->translate("Delete")));

      $id = $this->_request->getParam("id");
      $name = $this->_request->getParam("name");

      try {
        $astinfo = new AsteriskInfo();
      } catch (Exception $e) {
        $this->view->error_message = $this->view->translate("Socket connection to the server is not available at the moment.");
        $this->renderScript('error/sneperror.phtml');
        return;
      }
      if (!$data = $astinfo->status_asterisk("khomp links show concise", "", True)) {
        $this->view->error_message = $this->view->translate("Socket connection to the server is not available at the moment.");
        $this->renderScript('error/sneperror.phtml');
      }

      $regras = Snep_Trunks_Manager::getValidation($id);
      $rules_query = Snep_Trunks_Manager::getRules($id);

      foreach ($rules_query as $rule) {
        if (!in_array($rule, $regras)) {
          $regras[] = $rule;
        }
      }

      if (count($regras) > 0) {

        $this->view->error_message = $this->view->translate("Cannot remove. The following routes are using this trunk: ") . "<br />";
        foreach ($regras as $regra) {
          $this->view->error_message .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
        }
        $this->renderScript('error/sneperror.phtml');
      } else {

        $this->view->id = $id;
        $this->view->name = $name;
        $this->view->remove_title = $this->view->translate('Delete Trunk.');
        $this->view->remove_message = $this->view->translate('The trunk will be deleted. After that, you have no way get it back.');
        $this->view->remove_form = 'trunks';
        $this->renderScript('remove/remove.phtml');

        if ($this->_request->getPost()) {

          //audit
          $loguser = Snep_Trunks_Manager::get($id);
          Snep_Audit_Manager::SaveLog("Deleted", 'trunks', $id, $this->view->translate("Trunk") . " {$id} ". $loguser['callerid']);     

          Snep_Trunks_Manager::remove($_POST['id']);
          Snep_Trunks_Manager::removePeers($_POST['name']);

          Snep_InterfaceConf::loadConfFromDb();
          $this->_redirect("trunks");
        }
      }
    }


    /**
    * preparePost
    * @param <string> $post
    * @return type
    */
    protected function preparePost($post = null) {

      $post = $post === null ? $_POST : $post;
      $tech = $post['technology'];
      $trunktype = $post['technology'] = strtoupper($tech);
      $ip_trunks = array("sip", "iax2", "snepsip", "snepiax2");

      // Only allowed fields for trunks table
      $trunk_fields = array(
        "callerid","type","username","secret","host","dtmfmode","reverse_auth","domain","insecure","map_extensions","dtmf_dial","dtmf_dial_number",
        "time_total","time_chargeby","time_initial_date","dialmethod","trunktype","context","name","allow","id_regex","channel","technology");

      // Only allowed fields for peers table
      $ip_fields = array(
        "name","callerid","context","secret","type","allow","defaultuser","dtmfmode","fromdomain",
        "fromuser","canal","host","peer_type","trunk","qualify","nat","call-limit","port");

      // Get las trunk id, because trunk.id is autoinccrement and trunk.name not is
      $sql = "SELECT name FROM trunks ORDER BY CAST(name as DECIMAL) DESC LIMIT 1";
      $row = Snep_Db::getInstance()->query($sql)->fetch();

      if ($this->view->action == "add") {
        $trunk_data = array(
          "name" => trim($row['name'] + 1),
          "context" => "default",
          "trunktype" => (in_array($tech, $ip_trunks) ? "I" : "T"),
          "type" => $trunktype,
        );
      } else {
        $trunk_data = array("trunktype" => (in_array($tech, $ip_trunks) ? "I" : "T"),
        "type" => $trunktype,
      );
    }

    foreach ($post as $section_name => $section) {
      $trunk_data[$section_name] = $section;
    }

    $trunk_data['dtmf_dial'] = ($post['dtmf_dial'] === "dtmf_dial" ? true : false) ;
    $trunk_data['dtmf_dial_number'] = ($trunk_data['dtmf_dial'] ? $trunk_data['dtmf_dial_number'] : "");

    $trunk_data['map_extensions'] = ($post['map_extensions'] === "map_extensions" ? true : false) ;

    $trunk_data['reverse_auth'] = ($post['reverse_auth'] === "reverse_auth" ? true : false) ;

    $trunk_data['time_total'] = ($post['tempo'] === "tempo" ? $trunk_data['time_total'] : NULL);
    $trunk_data['time_chargeby'] = ($post['tempo'] === "tempo" ? $trunk_data['time_chargeby'] : "");
    $trunk_data['time_initial_date'] = ($post['tempo'] === "tempo" ? $trunk_data['time_initial_date'] : "");

    // check type Qualify, (yes|no|specify)
    if ($trunk_data['qualify'] === 'specify') {
      $trunk_data['qualify'] = trim($trunk_data['qualify_value']);
    }

    // codecs
    $trunk_data['allow'] = trim(sprintf("%s;%s;%s", $trunk_data['codec'], $trunk_data['codec1'], $trunk_data['codec2']), ";");

    if ($trunktype == "SIP" || $trunktype == "IAX2") {

      $trunk_data['dialmethod'] = strtoupper($trunk_data['dialmethod']);

      if ($trunk_data['dialmethod'] == 'NOAUTH') {
        $trunk_data['channel'] = $trunktype . "/@" . $trunk_data['host'];
      } else {
        $trunk_data['channel'] = $trunktype . "/" . $trunk_data['username'];
      }

      $trunk_data['id_regex'] = $trunktype . "/" . $trunk_data['username'];

    } else if ($trunktype === "SNEPSIP" || $trunktype === "SNEPIAX2") {

      $trunk_data['peer_type'] = $trunktype == "SNEPSIP" ? "peer" : "friend";
      $trunk_data['username'] = $trunktype == "SNEPSIP" ? $trunk_data['host'] : $trunk_data['identifier'];
      $trunk_data['channel'] = $trunk_data['id_regex'] = substr($trunktype, 4) . "/" . $trunk_data['username'];
      $trunk_data['qualify'] = 'yes' ;


    } else if ($trunktype == "KHOMP") {

      $khomp_board = $trunk_data['board'];
      $trunk_data['channel'] = 'KHOMP/' . $khomp_board;
      $b = substr($khomp_board, 1, 1);
      if (substr($khomp_board, 2, 1) == 'c') {
        $config = array(
          "board" => $b,
          "channel" => substr($khomp_board, 3)
        );
      } else if (substr($khomp_board, 2, 1) == 'l') {
        $config = array(
          "board" => $b,
          "link" => substr($khomp_board, 3)
        );
      } else {
        $config = array(
          "board" => $b
        );
      }
      $trunk = new PBX_Asterisk_Interface_KHOMP($config);
      $trunk_data['id_regex'] = $trunk->getIncomingChannel();
    } else { // VIRTUAL
      $trunk_data['id_regex'] = $trunk_data['id_regex'] == "" ? $trunk_data['channel'] : $trunk_data['id_regex'];
    }

    // Filter data and fields to allowed types
    $ip_data = array(
      "canal" => $trunk_data['channel'],
      "type" => $trunk_data['peer_type'],
    );
    foreach ($trunk_data as $field => $value) {

      if ($field === 'username') {
        $ip_data['defaultuser'] = $value ;
      }

      if (in_array($field, $ip_fields) && $field != "type") {
        $ip_data[$field] = $value;
      }

      if (!in_array($field, $trunk_fields)) {
        unset($trunk_data[$field]);
      }
    }

    $ip_data["peer_type"] = "T";
    $nat_types = array('no','comedia','force_rport','auto_comedia','auto_force_rport');
    $nat = "" ;
    foreach ($nat_types as $key => $val) {
      if (isset($post['nat_'.$val])) {
        if ($nat === "") {
          $nat = $val ;
        } else {
          $nat .= ','.$val ;
        }
      }
    }
    if ($nat === "") {
      $nat = 'no';
    }
    $ip_data['nat'] = $nat ;

    $trunk_data['telco'] = $post['telco'];

    return array("trunk" => $trunk_data, "ip" => $ip_data);
  }

}
