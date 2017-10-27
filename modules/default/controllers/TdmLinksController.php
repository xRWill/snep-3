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
* TDM Links Controller
*
* @category  Snep
* @package   Snep
* @copyright Copyright (c) 2014 OpenS Tecnologia
* @author    Opens Tecnologia <desenvolvimento@opens.com.br>
*/
class TdmLinksController extends Zend_Controller_Action {

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
    * indexAction - List links khomp
    * @return type
    * @throws ErrorException
    */
    public function indexAction() {

      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
        $this->view->translate("Status"),
        $this->view->translate("TDM boards"),
        $this->view->translate("Show links / channels")));
        $this->view->boards = array();

        try {
          $astinfo = new AsteriskInfo();
        } catch (Exception $e) {
          $this->view->error_message =  $this->view->translate("Error! Failed to connect to server Asterisk.");
          $this->renderScript('error/sneperror.phtml');
          return;
        }

        if (!$data = $astinfo->status_asterisk("khomp links show concise", "", True)) {
          throw new ErrorException($this->view->translate("Socket connection to the server is not available at the moment."));
        }

        $lines = explode("\n", $data);
        $error_message = array();
        if (trim(substr($lines['1'], 10, 16)) === "Error" || strpos($lines['1'], "such command") > 0) {
          $error_message['khomp'] = $this->view->translate("No Khomp board installed.");
        }else{
          $links = array();
          $boards = array();
          $lst = '';
          while (list($key, $val) = each($lines)) {

            if (substr($val, 0, 1) === "B" && substr($val, 3, 1) === "L") {

              if (substr($val, 0, 3) != $lst) {

                $board = substr($val, 0, 3);
                $boards[$board] = $board;
                $lnk = substr($val, 3, 3);
                $status = trim(substr($val, strpos($val, ":") + 1));
                $links[$board][$lnk] = $khomp_signal[$status];
                $lst = $board;
              }
            }
          }

          $this->view->boards = $boards;
        }


        if (!$data = $astinfo->status_asterisk("dahdi show status", "", True)) {
          throw new ErrorException($this->view->translate("Socket connection to the server is not available at the moment."));
        }

        $lines = explode("\n", $data);

        if (preg_match("/no dahdi found/i",$lines[1]) || preg_match("/no such command/i",$lines[1])) {
          $error_message['dahdi'] = $this->view->translate("No Dahdi board installed.");
        }else{
          $links = array();
          $boards = array();
          $lst = '';
        }

        if(count($error_message) > 0 && count($this->view->boards) < 1){
          $this->view->error_message = $error_message;
          $this->renderScript('tdm-links/sneperror.phtml');
        }

        $boards = "";
        if ($this->_request->getPost()) {

          $dados = $this->_request->getParams();
          if (!isset($dados['boards'])) {
            $this->view->error_message = $this->view->translate("No boards selected.");
            $this->renderScript('error/sneperror.phtml');
          } else {
            foreach($dados['boards'] as $key => $value){
              $boards[] = $key;
            }
            $boards = implode(",", $boards);
            $this->_redirect($this->getRequest()->getControllerName() . '/view/id/' . $dados['view'] . ',' . $dados['status'] . ',' . $boards);
          }

        }
      }

      /**
      * viewAction - view links khomp
      * @throws ErrorException
      */
      public function viewAction() {

        $data = $this->_request->getParam('id');

        $placas = explode(',', $data);

        $tiporel = $placas[0];
        $statusk = $placas[1];

        unset($placas[0]);
        unset($placas[1]);

        $config = Zend_Registry::get('config');

        $khomp_signal = array("kesOk (sync)" => $this->view->translate('Activated'),
        "kesOk" => $this->view->translate('Activated'),
        "kes{SignalLost} (sync)" => $this->view->translate('Signal Lost'),
        "kes{SignalLost},sync" => $this->view->translate('Signal Lost'),
        "kes{SignalLost}" => $this->view->translate('Signal Lost'),
        "[ksigInactive]" => $this->view->translate('Deactivated'),
        "NoLinksAvailable" => $this->view->translate('No Link Available'),
        "ringing" => $this->view->translate('Ringing'),
        "ongoing" => $this->view->translate('On going'),
        "unused" => $this->view->translate('Unused'),
        "dialing" => $this->view->translate('Dialing'),
        "kcsFree" => $this->view->translate('Channel Free'),
        "kcsFail" => $this->view->translate('Channel Fail'),
        "kcsIncoming" => $this->view->translate('Incoming Call'),
        "kcsOutgoing" => $this->view->translate('Outgoing Call'),
        "kecsFree" => $this->view->translate('Free'),
        "kecsBusy" => $this->view->translate('Busy'),
        "kecsOutgoing" => $this->view->translate('Outgoing'),
        "kecsIncoming" => $this->view->translate('Incoming'),
        "kecsLocked" => $this->view->translate('Locked'),
        "kecs{SignalLost}" => $this->view->translate('Signal Lost'),
        "kecs{Busy}" => $this->view->translate('Fail'),
        "kecs{Busy,Locked,RemoteLock}" => $this->view->translate('Busy Outgoing'),
        "kecs{Busy,Outgoing}" => $this->view->translate('Busy Outgoing'),
        "kecs{Busy,Incoming}" => $this->view->translate('Busy Incoming'),
        "kgsmIdle" => $this->view->translate('Free'),
        "kgsmCallInProgress" => $this->view->translate('Busy'),
        "kgsmSMSInProgress" => $this->view->translate('Sending SMS'),
        "kgsmNetworkError" => $this->view->translate('Communication Error'),
        "kfxsOnHook" => $this->view->translate('Free'),
        "kfxsOffHook" => $this->view->translate('Busy'),
        "offhook" => $this->view->translate('Busy'),
        "kfxsRinging" => $this->view->translate('Ringing'),
        "kfxsFail" => $this->view->translate('Fail'),
        "kfxsDisabled" => $this->view->translate('Disabled'),
        "kfxsEnable" => $this->view->translate('Enabled'),
        "reserved" => $this->view->translate('Reserved'),
        "ring" => $this->view->translate('Ringing'),
        "[Unreacheable]" => $this->view->translate('Unreachable'),
        "kgsmModemError" => $this->view->translate('Modem Error'),
        "kgsmNotReady" => $this->view->translate('Initializing')
      );

      $status_sintetico_khomp = array("unused" => $this->view->translate('Unused'),
      "ongoing" => $this->view->translate('On Going'),
      "ringing" => $this->view->translate('Ringing'),
      "dialing" => $this->view->translate('Dialing'),
      "reserved" => $this->view->translate('Reserved'),
      "offhook" => $this->view->translate('Busy'),
      "ring" => $this->view->translate('Ringing'),
      "prering" => $this->view->translate('Ringing'),
      "none" => $this->view->translate('None'),
      "down" => $this->view->translate('Hanging Up'));

      $status_canais_khomp = array($this->view->translate("Unused") => "#83C46D",
      $this->view->translate('On Going') => "#F66",
      $this->view->translate('Ringing') => "#FFDF6F",
      $this->view->translate('Dialing') => "#FFDF6F",
      $this->view->translate('Reserved') => "#FFDF6F",
      $this->view->translate('Busy') => "#F66");

      $linksKhomp = array("0" => "B00", "1" => "B01", "3" => "B02", "4" => "B03",
      "5" => "B04", "6" => "B05", "7" => "B06", "8" => "B07");

      require_once "includes/AsteriskInfo.php";
      ;
      try {
        $astinfo = new AsteriskInfo();
        // Read Khomp links
        try {
          $data = $astinfo->status_asterisk("khomp summary concise", "", True) ;
        } catch (Exception $e) {
          $this->view->error_message = $this->view->translate("Socket connection to the server is not available at the moment.");
          $this->renderScript('error/sneperror.phtml');;
        }
      } catch (Exception $e) {
        $this->view->error_message =  $this->view->translate("Error! Failed to connect to server Asterisk.");
        $this->renderScript('error/sneperror.phtml');
      }

      #Monta lista de Placas para exibir no Header da view
      $sumary = explode("\n", $data);
      $boards = array();

      foreach ($sumary as $id => $iface) {

        $t_board = explode(";", $iface);

        if (preg_match("/^[0-9][0-9]$/",substr($t_board[0],4,2)) ) {

          $t_board_id = substr($t_board[0], 4, 2);
          $t_board_name = $t_board[1] ;
          $t_board_serial = $t_board[2] ;
          $t_board_ip = $t_board[5] ;

          $boards[$t_board_id] = $t_board_name . " ( " . $t_board_serial . " ) - " . $t_board_ip ;
        }

      }

      if (!$data = $astinfo->status_asterisk("khomp links show concise", "", True)) {
        throw new ErrorException($this->view->translate("Socket connection to the server is not available at the moment."));
      }

      $lines = explode("\n", $data);
      $links = array();

      while (list($key, $val) = each($lines)) {

        if (substr($val, 0, 1) === "B" && substr($val, 3, 1) === "L") {
          $s = substr($val, 0, 3);

          if (in_array($s, $placas)) {
            $board = substr($val, 0, 3);
            $lnk = substr($val, 3, 3);
            $status = trim(substr($val, strpos($val, ":") + 1));
            $links[$board][$lnk] = $khomp_signal[$status];
          }
        }
      }

      // Informacoes dos Canais de Cada Links
      // ------------------------------------
      $link = "";
      $cntSemUso = 0;
      $cntEmCurso = 0;
      $cntChamando = 0;
      $cntReservado = 0;
      $gsm=false;
      foreach ($links as $key => $val) {

        if ($link != substr($key, 1)) {

          $link = (int) substr($key, 1);

          $data = $astinfo->status_asterisk("khomp channels show concise $link", "", True);
        } else {

          continue;
        }

        $lines = explode("\n", $data);

        while (list($chave, $valor) = each($lines)) {

          //if (substr($valor, 0, 1) === "B" && substr($valor, 3, 1) === "C") {
          if (substr($valor, 4, 1) === "B" && substr($valor, 7, 1) === "C") {

            $linha = explode(":", $valor);
            $st_ast = $khomp_signal[$linha[1]];
            $st_placa = $khomp_signal[$linha[2]];
            $st_canal = $khomp_signal[$linha[3]];

            if (isset($sintetic[substr($valor, 0, 3)][$linha[1]])) {
              $sintetic[substr($valor, 0, 3)][$linha[1]] += 1;
            } else {
              $sintetic[substr($valor, 0, 3)][$linha[1]] = 1;
            }

            if (isset($sintetic[substr($valor, 4, 3)][$linha[1]])) {
              $sintetic[substr($valor, 4, 3)][$linha[1]] += 1;
            } else {
              $sintetic[substr($valor, 4, 3)][$linha[1]] = 1;
            }

            $l = "$linha[0]:$st_ast:$st_placa:$st_canal";

            if (strpos($valor, "kgsm")) {

              $st_sinal = $linha[4];
              $st_opera = $linha[5];
              $st_gsm = true;

            } else {
              $st_sinal = '';
              $st_opera = '';
              $st_gsm = false;
            }

            //$board = substr($l, 0, 3);
            $board = substr($l, 4, 3);
            //$channel = substr($l, 3, 3);
            $channel = substr($l, 7, 4);
            $status = explode(":", $l);

            if ($status[3] != "kecs{Busy,Locked,LocalFail}") {

              $channels[$key][$channel]['asterisk'] = $status[1];
              $channels[$key][$channel]['k_call'] = $status[2];
              $channels[$key][$channel]['k_channel'] = $status[3];

              $channels[$key][$channel]['k_signal'] = $st_sinal;
              $channels[$key][$channel]['k_opera'] = $st_opera;
              $channels[$key][$channel]['k_gsm'] = $st_gsm;
            }
          }
        }
      }

      $this->view->linksKhomp = $linksKhomp;
      $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array($this->view->translate("Status"), $this->view->translate("Khomp Links")));

      $this->view->dados = $links;
      $this->view->canais = $channels;
      $this->view->status_canais = $status_canais_khomp;
      $this->view->status_sintetic = $status_sintetico_khomp;
      $this->view->cols = $links;
      $this->view->status = $statusk;
      $this->view->tiporel = $tiporel;
      $this->view->sintetic = $sintetic;
      $this->view->sumary = $boards;

    }

  }
