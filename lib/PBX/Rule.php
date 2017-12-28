<?php

/**
*  This file is part of SNEP.
*
*  SNEP is free software: you can redistribute it and/or modify
*  it under the terms of the GNU Lesser General Public License as
*  published by the Free Software Foundation, either version 3 of
*  the License, or (at your option) any later version.
*
*  SNEP is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU Lesser General Public License for more details.
*
*  You should have received a copy of the GNU Lesser General Public License
*  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
*/

/**
* Regra de Negócio
*
* Classe que implementa as regra de negócio do snep. Estas são responsáveis
* por executar as ações com a ligação através da interface de comunicação
* com o asterisk.
*
* Suas capacidades variam de acordo com as ações que são executadas.
*
* Dentre as capacidades padrão está a gravação. Implementada de forma simples
* ela grava os arquivos no /tmp do sistema usando o unix timestamp como nome de
* arquivo.
*
* @category  Snep
* @package   Snep_Rule
* @copyright Copyright (c) 2010 OpenS Tecnologia
* @author Henrique Grolli Bassotto
*/
class PBX_Rule {

  /**
  * Array contendo os objetos que executam as ações da regra de negócio.
  * As classes aqui devem extender a classe PBX_Rule_Action
  * @var array de Actions
  */
  private $acoes = array();

  /**
  * Define se a regra deve ser considerada ativada ou desativada.
  * @var <boolean> ativa
  */
  private $active = true;

  /**
  * Interface de comunicação com o asterisk
  * @var Asterisk_AGI asterisk
  */
  private $asterisk = null;

  /**
  * Descrição para usuários da regra de negócio.
  * @var <string> descrição
  */
  private $desc = "";

  /**
  * Lista de destinos aos quais essa regra espera.
  * @var <array> dst Destinos com que essa regra trabalha
  */
  private $dst = array();

  /**
  * ID para controle de regras persistidas em banco de dados.
  * @var <int> id da regra.
  */
  private $id = -1;

  /**
  * Define se a regra vai ou não gravar a ligação.
  * @var <boolean> isRecording
  */
  private $isRecording = false;

  /**
  * Prioridade com a qual deve ser trada essa regra em relação a outras
  * regras na hora de processar o plano de discagem.
  * @var <int> prio Prioridade de execução da regra
  */
  private $priority = 0;

  /**
  * Tipo de regra
  * @var <string> Poderá ser incoming, outgoing ou others
  */
  private $typeRule = "others";

  /**
  * Define qual será a aplicação que efetuará a gravação da ligação.
  * @var <array> recordApp
  */
  private $recordApp;

  /**
  * Requisição de ligação a qual essa regra deve usar para executar suas
  * ações.
  * @var Asterisk_AGI_Request requisição
  */
  private $request = null;

  /**
  * Lista de origens as quais essa regra está apta a operar.
  * @var <array> src Origens com que essa regra trabalha
  */
  private $src = array();

  /**
  * Array com range de horários, formato:
  * hh:ss-hh:ss
  *
  * início-fim, aceita também horários invertidos ex: 18:00-08:00.
  *
  * Exemplo para casar horário comercial:
  * 08:00-12:00
  * 13:30-18:00
  *
  * @var <string|array> Array com Range de horários
  */
  private $validade = array();

  /**
  * Ex:
  * mon
  * tue
  * wed
  * thu
  * fri
  *
  * @var <string|array> Array com dias da semana em que a regra é válida
  */
  private $validWeekDays = array("sun", "mon", "tue", "wed", "thu", "fri", "sat");

  /**
  * Instance of PBX_Rule_Plugin_Broker
  * @var PBX_Rule_Plugin_Broker
  */
  protected $plugins = null;

  /**
  * __construct - Construtor do objeto.
  * Inicia alguns atributos mais complexos.
  */
  public function __construct() {
    $recordFilename = "/tmp/" . time() . ".wav";
    $this->setRecordApp('MixMonitor', array($recordFilename, "b"));
    $this->plugins = new PBX_Rule_Plugin_Broker();
    $this->plugins->setRule($this);
  }

  /**
  * registerPlugin - Register a plugin
  * @param  PBX_Rule_Plugin $plugin
  * @param  <int> $stackIndex Optional; stack index for plugin
  * @return PBX_Rule
  */
  public function registerPlugin(PBX_Rule_Plugin $plugin, $stackIndex = null) {
    $this->plugins->registerPlugin($plugin, $stackIndex);
    return $this;
  }

  /**
  * unregisterPlugin - Unregister a plugin
  * @param  string|PBX_Rule_Plugin $plugin Plugin class or object to unregister
  * @return PBX_Rule
  */
  public function unregisterPlugin($plugin) {
    $this->plugins->unregisterPlugin($plugin);
    return $this;
  }

  /**
  * hasPlugin - Is a particular plugin registered?
  * @param  <string> $class
  * @return <boolean>
  */
  public function hasPlugin($class) {
    return $this->plugins->hasPlugin($class);
  }

  /**
  * getPlugin - Retrieve a plugin or plugins by class
  * @param  <string> $class
  * @return false|PBX_Rule_Plugin|array
  */
  public function getPlugin($class) {
    return $this->plugins->getPlugin($class);
  }

  /**
  * getPlugins - Retrieve all plugins
  * @return <array>
  */
  public function getPlugins() {
    return $this->plugins->getPlugins();
  }

  /**
  * addAcao - Adiciona Actions a fila de execução da regra.
  * @param PBX_Rule_Action $acao - Ação a ser adicionada a fila de execução
  */
  public function addAcao(PBX_Rule_Action $acao) {
    $this->addAction($acao);
  }

  /**
  * addAction - Adiciona Actions a fila de execução da regra.
  * @param PBX_Rule_Action $action - Ação a ser adicionada a fila de execução
  */
  public function addAction(PBX_Rule_Action $action) {
    $action->setRule($this);
    $this->acoes[] = $action;
  }

  /**
  * addDst - Adiciona elemento a lista de Destinos da regra
  * Tipos válidos:
  *  R  - Ramal, aceita numero do ramal
  *  RX - Expressão Regular Asterisk
  *  X  - Qualquer Numero
  *  G  - Grupo de Destino
  *  CG - Grupo de contatos
  *
  * @param <array> $item array com tipo e valor do destino
  */
  public function addDst($item) {
    if (is_array($item) && isset($item['type']) && isset($item['value'])) {
      $this->dst[] = $item;
    } else {
      throw new PBX_Exception_BadArg("Invalid argument for adding destination in the rule {$this->getId()}: {$this->getDesc()})");
    }
  }

  /**
  * addSrc - Adiciona elemento a lista de origens
  * Tipos válidos:
  *  R  - Ramal, aceita numero do ramal
  *  T  - Tronco, id do tronco
  *  RX - Expressão Regular Asterisk
  *  X  - Qualquer Numero
  *  CG - Grupo de contatos
  *
  * @param <array> $item array com o tipo e valor da origem
  */
  public function addSrc($item) {
    if (is_array($item) && isset($item['type']) && isset($item['value'])) {
      $this->src[] = $item;
    } else {
      throw new PBX_Exception_BadArg("Invalid argument for adding source in the rule {$this->getId()}: {$this->getDesc()})");
    }
  }

  /**
   * addValidDates- Add a new Date item into Dates List array
   * @param <string> $time
   */
  public function addValidDates($date) {
      $this->dates[] = $date;
  }

  /**
  * addValidTime - Adiciona tempo na lista de tempos.
  * @param <string> $time
  */
  public function addValidTime($time) {
    $this->validade[] = $time;
  }

  /**
  * addWeekDay - Adiciona dia da semana na lista de dias válidos.
  * Formato: Dia em inglês abreviado em 3 letras:
  * sun
  * mon
  * tue
  * wed
  * thu
  * fri
  * sat
  *
  * @param <string> $weekDay
  */
  public function addWeekDay($weekDay) {
    $weekDay = strtolower($weekDay);

    if (!in_array($weekDay, array("sun", "mon", "tue", "wed", "thu", "fri", "sat"))) {
      throw new InvalidArgumentException("Weekday invalid");
    }

    if (!in_array($weekDay, $this->validWeekDays)) {
      $this->validWeekDays[] = $weekDay;
    }
  }

  /**
  * astrule2regex - Coverte expressões regulares do asterisk para o padrão posix
  * @param <string> #astrule - expressão regular do asterisk
  * @return regra em expressão regular padrão posix
  */
  private function astrule2regex($astrule) {
    $astrule = str_replace("*", "\*", $astrule);
    $astrule = str_replace("|", "", $astrule);
    if (preg_match_all("#\[[^]]*\]#", $astrule, $brackets)) {
      $brackets = $brackets[0];
      foreach ($brackets as $key => $value) {
        $new_bracket = "[";
        for ($i = 1; $i < strlen($value) - 1; $i++) {
          $char = (substr($value, $i, 1) !== false) ? substr($value, $i, 1) : -1;
          $charnext = (substr($value, $i + 1, 1) !== false) ? substr($value, $i + 1, 1) : -1;
          if ($char != "-" && $charnext != "-" && $i < (strlen($value) - 2)) {
            $new_bracket = $new_bracket . $char . "|";
          } else {
            $new_bracket = $new_bracket . $char;
          }
        }
        $lists[$key] = $new_bracket . "]";
      }
      $astrule = str_replace($brackets, $lists, $astrule);
    }
    $sub = array("_", "X", "Z", "N", ".", "!");
    $exp = array("", "[0-9]", "[1-9]", "[2-9]", "[[0-9]|.*]", ".*");
    $rule = str_replace($sub, $exp, $astrule);
    return "^" . $rule . "\$";
  }

  /**
  * checkExpr - Checa se uma origem/destino casa com um numero
  * @param <string> $type Tipo de origem/destino
  * @param <string> $expr Expressão do tipo, se houver
  * @param <string> $value Valor a ser confrontado com a expressão
  * @return <boolean> Resultado da checagem
  */
  private function checkExpr($type, $expr, $value) {
    switch ($type) {
      case 'RX': // Expressão Regular
      return preg_match("/{$this->astrule2regex($expr)}/", $value);
      break;
      case 'G':
      if ($this->request->getSrcObj() instanceof Snep_Usuario) {
        //return PBX_Usuarios::hasGroupInheritance($expr, $this->request->getSrcObj()->getGroup())
        return PBX_Usuarios::hasExtenGroup($expr,$value);
      } else {
        return false;
      }
      break;
      case 'R': // Vinda de um Ramal específico
      return $value == $expr ? true : false;
      break;
      case 'S': // Sem destino - Válido somente para destinos (duh!).
      return $value == 's' ? true : false;
      break;
      case 'T': // Troncos
      $log = Snep_Logger::getInstance();
      if (($this->request->getSrcObj() instanceof Snep_Trunk) && $this->request->getSrcObj()->getId() == $expr) {
        return true;
      } else {
        return false;
      }
      break;
      case 'X': // Qualquer origem/destino
      return true;
      break;
      case 'CG':
      $db = Zend_Registry::get('db');
      $select = $db->select()
      ->from(array("n" => "contacts_names"))
      ->join(array("p" => "contacts_phone"), 'n.id = p.contact_id')
      ->where("`group` = '$expr'")
      ->where("`phone` = '$value'");

      $stmt = $db->query($select);
      $groups = $stmt->fetchAll();

      if (count($groups) > 0) {
        return true;
      } else {
        return false;
      }
      break;
      case "AL":
      $aliases = PBX_ExpressionAliases::getInstance();

      $expression = $aliases->get((int) $expr);

      $found = false;
      foreach ($expression["expressions"] as $expr_value) {
        if (preg_match("/{$this->astrule2regex($expr_value)}/", $value)) {
          $found = true;
          break;
        }
      }

      return $found;
      break;
      default:
      throw new PBX_Exception_BadArg("Type of expression invalid '$type' to check source/destiny. Verify the route {$this->parsingRuleId}");
    }
  }

  /**
  * cleanActionList - Limpa a lista de ações da regra
  */
  public function cleanActionsList() {
    $this->acoes = array();
  }

  /**
   * cleanValidDatesList - Clean the Dates list array
   */
  public function cleanValidDatesList() {
      $this->dates = array();
  }

  /**
  * cleanValidTimeList - Limpa lista de Validade
  */
  public function cleanValidTimeList() {
    $this->validade = array();
  }

  /**
  * cleanValidWeekList - Limpa lista de dias da semana em que a regra é válida.
  */
  public function cleanValidWeekList() {
    $this->validWeekDays = array();
  }

  /**
  * disable - Desativa a regra de negócio
  */
  public function disable() {
    $this->active = false;
  }

  /**
  * dontRecord - Não permite gravação da ligação na próxima execução da regra.
  */
  public function dontRecord() {
    $this->isRecording = false;
  }

  /**
  * dstClean - Limpa o array de destinos
  */
  public function dstClean() {
    $this->dst = array();
  }

  /**
  * enable - Habilita a regra de negócio.
  */
  public function enable() {
    $this->active = true;
  }

  /**
  * execute - Execute the rule
  * This method is meant to be executed exclusively within AGI context
  * @param <string> $origem
  */
  public function execute($origem) {
    $asterisk = $this->asterisk;
    $log = Zend_Registry::get('log');
    $lang = Zend_Registry::get('config');

    $to_execute = true;
    try {
      $this->plugins->startup();
    } catch (PBX_Rule_Action_Exception_StopExecution $ex) {
      $log->info("Stopping rule execution by plugin request: {$ex->getMessage()}");
      $to_execute = false;
    }

    if (count($this->acoes) == 0) {
      $log->warn("Rule does not have any actions.");
    } else {

      $isagent = false;

      if (class_exists("Agents_Agent") || class_exists("Agents_Manager")) {

        //verifica se origem é agente ou ramal
        $isagent = self::isAgent($origem);

        if ($isagent == true) {

          //Busca último evento do agente
          $db = Zend_Registry::get('db');

          $select = "SELECT event FROM `agent_availability` WHERE agent='$origem' order by date DESC limit 1; ";
          $result = $db->query($select)->fetch();
          $event = $result['event'];

          //caso seja pausa exibe mensagem
          if ($event != 1 && $event != 2 && $event != 4) {

            $log->info("User $origem have padlock enabled");
            $asterisk->exec_setlanguage($lang);
            $asterisk->stream_file('ext-disabled');
          } else {
            // Efetua ligação caso agente não esteja pausado
            $isagent = false;
          }
        }
      }

      // Se origem é ramal
      if ($isagent == false) {

        $requester = $this->request->getSrcObj();
        if ($requester instanceof Snep_Exten && $requester->isLocked()) {
          $log->info("User $requester have padlock enabled");
          $asterisk->exec_setlanguage($lang);
          $asterisk->stream_file('ext-disabled');
        } else {
          if ($this->isRecording()) {
            $recordApp = $this->getRecordApp();
            $log->info("Recording with '{$recordApp['application']}'");
            $this->asterisk->exec($recordApp['application'], $recordApp['options']);
          }

          for ($priority = 0; $priority < count($this->acoes) && $to_execute; $priority++) {
            $acao = $this->acoes[$priority];

            $log->debug(sprintf("Executing action %d-%s", $priority, get_class($acao)));
            try {
              $this->plugins->preExecute($priority);
              $acao->execute($asterisk, $this->request);
            } catch (PBX_Exception_AuthFail $ex) {
              $log->info("Stopping rule. Failed to authenticate extension $priority-" . get_class($acao) . ". Response: {$ex->getMessage()}");
              $to_execute = false;
            } catch (PBX_Rule_Action_Exception_StopExecution $ex) {
              $log->info("Stopping rule execution by action request: $priority-" . get_class($acao));
              $to_execute = false;
            } catch (PBX_Rule_Action_Exception_GoTo $goto) {
              $priority = $goto->getIndex() - 1;
              $log->info("Forwarding to action {$goto->getIndex()}.");
            } catch (Exception $ex) {
              $log->crit("Failure on execute action $priority -" . get_class($acao) . " of rule $this->id-$this");
              $log->crit($ex);
            }

            try {
              $this->plugins->postExecute($priority);
            } catch (PBX_Rule_Action_Exception_StopExecution $ex) {
              $log->info("Stopping execution by plugin request: {$ex->getMessage()}");
              $to_execute = false;
            }
          }
        }
      }
    }
    $this->plugins->shutdown();
  }

  /**
  * isAgent - Verify if src is agent
  * @param <string> $origem
  * @return <boolean>
  */
  public static function isAgent($origem) {

    $agentFile = '/etc/asterisk/snep/snep-agents.conf';
    $agents = explode("\n", file_get_contents($agentFile));
    $agentsData = array();

    foreach ($agents as $agent) {
      if (preg_match('/^agent/', $agent)) {
        $info = explode(",", substr($agent, 9));
        $agentsData[] = array('code' => $info[0], 'password' => $info[1], 'name' => $info[2]);
      }
    }
    $isagent = false;
    foreach ($agentsData as $name) {
      if ($name["code"] == $origem) {
        $isagent = true;
      } else {
        $isexten = true;
      }
    }

    if ($isagent == true) {
      return true;
    } else {
      return false;
    }
  }

  /**
  * getAcao - Retorna a ação da regra pelo seu índice
  * @param <int> $index
  * @return PBX_Rule_Action
  */
  public function getAcao($index) {
    return $this->getAction($index);
  }

  /**
  * getAction - Retorna a ação da regra pelo seu índice
  * @param <int> $index
  * @return PBX_Rule_Action
  */
  public function getAction($index) {
    if (isset($this->acoes[$index])) {
      return $this->acoes[$index];
    } else {
      throw new PBX_Exception_NotFound("No action index $index in the rule.");
    }
  }

  /**
  * getAcoes - Retorna as ações que a regra de negócio executa
  * @return <array> acoes da regra
  */
  public function getAcoes() {
    return $this->getActions();
  }

  /**
  * getActions - Retorna as ações que a regra de negócio executa.
  * @return <array> acoes da regra
  */
  public function getActions() {
    return $this->acoes;
  }

  /**
  * getDesc - Obter a descrição da regra de negócio
  * @return <string> descrição
  */
  public function getDesc() {
    return $this->desc;
  }

  /**
  * getTypeRule - Recupera tipo de regra
  * @return <int> $type
  */
  public function getTypeRule() {
    return $this->typeRule;
  }

  /**
  * getDstList - Recupera a lista de destinos
  * @return <array> dst
  */
  public function getDstList() {
    return $this->dst;
  }

  /**
  * getId - Recupera o ID da regra de negócio
  * @return id da regra
  */
  public function getId() {
    return $this->id;
  }

  /**
  * getPriority - Prioridade que a regra está requisitando
  * @return <int> $prio
  */
  public function getPriority() {
    return $this->priority;
  }



  /**
  * getRecordApp - Retorna o nome da aplicação que será usada para gravar as ligações.
  * @return <string> recordApp
  */
  public function getRecordApp() {
    return $this->recordApp;
  }

  /**
  * getSrcList - Recupera a lista de origens
  * @return <array> src
  */
  public function getSrcList() {
    return $this->src;
  }

  /**
  * getValidDstExpr - Verifica se um destino é válido para essa regra
  * e retorna a expressão válida
  * @param <string> $dst
  * @return <null | string>
  */
  public function getValidDstExpr($dst) {
    foreach ($this->getDstList() as $thisdst) {
      if ($this->checkExpr($thisdst['type'], $thisdst['value'], $dst)) {
        return $thisdst;
      }
    }
    return null;
  }

  /**
  * getValidSrcExpr - Verifica se uma origem é válida para essa regra
  * e retorna a expressão válida
  * @param <string> $src
  * @return <null | string>
  */
  public function getValidSrcExpr($src) {
    foreach ($this->getSrcList() as $thissrc) {
      if ($this->checkExpr($thissrc['type'], $thissrc['value'], $src)) {
        return $thissrc;
      }
    }
    return null;
  }

  /**
   * getValidDatesList - Get the current Dates List on form
   * @return array string $dates
   */
  public function getValidDatesList() {
      return $this->dates;
  }

  /**
  * getValidTimeList - Pega a lista de tempos da regra
  * @return array string $time
  */
  public function getValidTimeList() {
    return $this->validade;
  }

  /**
  * getValidWeekDays - Retorna um array com os dias da semana que são válidos para essa regra
  * @return array string
  */
  public function getValidWeekDays() {
    return $this->validWeekDays;
  }

  /**
   * getValidAliasDates - Return the array with all alias dates
   * @return array string
   */
  public function getValidAliasDates() {
    $db = Zend_Registry::get('db');
    $select = $db->select()
            ->from(array("n" => "date_alias"), array("id","name"))
            ->join(array("p" => "date_alias_list"), 'n.id = p.dateid', array("date","timerange","list_id" => "id"));

    $stmt = $db->query($select);
    $dates = $stmt->fetchAll();

    return $dates;
  }

  /**
   * getValidAliasDateById - Return the array with all alias dates
   * @return array string
   */
  public function getValidAliasDateById($id) {
    $db = Zend_Registry::get('db');
    $select = $db->select()
            ->from(array("n" => "date_alias"), array("id","name"))
            ->join(array("p" => "date_alias_list"), 'n.id = p.dateid', array("date","timerange","list_id" => "id"))
            ->where("n.id = $id");

    $stmt = $db->query($select);
    $dates = $stmt->fetchAll();

    return $dates;
  }


  /**
  * isActive - Checa se a regra gostari de estar ativa ou não
  * @return <boolean> active
  */
  public function isActive() {
    return $this->active;
  }

  /**
  * isRecording - Retorna a ordem de gravação da ligação.
  * @return <boolean> isRecording;
  */
  public function isRecording() {
    return $this->isRecording;
  }

  /**
  * isValidDst - Verifica se um destino é válido para essa regra
  * @param <string> $extension
  * @return <boolean> validity
  */
  public function isValidDst($extension) {
    foreach ($this->getDstList() as $dst) {
      if ($dst['type'] == 'G') {
        try {
          $peer = PBX_Usuarios::get($extension);
        } catch (PBX_Exception_NotFound $ex) {
          $peer = false;
        }

        if ($peer instanceof Snep_Usuario && PBX_Usuarios::hasExtenGroup($dst['value'], $extension)) {
          return true;
        }
      } else if ($this->checkExpr($dst['type'], $dst['value'], $extension)) {
        return true;
      }
    }
    return false;
  }

  /**
  * isValidSrc - Verifica se uma origem é válida para essa regra.
  * @param <string> $src
  * @return <boolean> validity
  */
  public function isValidSrc($src) {
    foreach ($this->getSrcList() as $thissrc) {
      if ($this->checkExpr($thissrc['type'], $thissrc['value'], $src))
      return true;
    }
    return false;
  }

  /**
  * isValidTime - Verifica se um tempo é válido para execução dessa regra.
  * @param <string> $time
  * @param <string> $week Dia da semana em formato 3 letras e em inglês
  * @return <boolean> validity
  */
  public function isValidTime($time = null, $week = null) {

    if ($time === null) {
      $time = date("H:i:s");
    }

    if ($week === null) {
      $week = strtolower(date("D"));
    } else {
      $week = strtolower($week);
    }

    if (in_array($week, $this->validWeekDays)) {

      foreach ($this->getValidTimeList() as $validTimeRange) {
        $validTimeRange = explode('-', $validTimeRange);
        $start = $validTimeRange[0];
        $end = $validTimeRange[1];
        if ($start > $end) {
          if ($start < $time OR $time <= $end) {
            return true;
          }
        } else {
          if ($start <= $time && $time < $end) {
            return true;
          }
        }
      }
    }
    return false;
  }

  /**
   * isValidAliasTime - Check if the date and time is valid for the rule
   * @param <string> $exec_time - exact time of rule execution
   * @param <string> $exec_date - exact date of rule execution
   * @return <boolean> validity
   */
  public function isValidAliasTime($exec_time, $exec_date) {
    $log = Snep_Logger::getInstance();
    $valid = $this->getValidDatesList();
    $count = count($valid);
    $valid_debug = serialize($valid);
    $log->debug("Current Valid Dates: $valid_debug -> $count -> $exec_date -> $exec_time");
    if(count($valid) < 0){
      $log->info("Rule Valid all days");
      return true;
    }
    foreach ($valid as $validDatesId) {
      if($validDatesId == "" || $validDatesId == 0){
        $log->info("Rule Date is Valid");
        return true;
      }else{
        $log->debug("Valid Alias Date Id: $validDatesId");
        $current = $this->getValidAliasDateById($validDatesId);
        foreach($current as $validRange){
          $validTimeRange = explode('-', $validRange['timerange']);
          $validDate = $validRange['date'];
          if(preg_match("/^[0-9][0-9]?-[0-9][0-9]?$/",$validDate)){
            $year = date('Y');
            $log->debug("Valid Date is a MM-DD format, adding year: $year");
            $validDate = $year . '-' . $validDate;
          }
          $start = $validTimeRange[0];
          $end = $validTimeRange[1];
          $debug = serialize($validTimeRange) . " | " . serialize($validRange);
          $log->debug("Valid Alias Dates: $debug");
          if($validDate == $exec_date){
            if ($start > $end) {
                if ($start < $exec_time OR $exec_time <= $end) {
                    $log->info("Rule Date is Valid");
                    return true;
                }
            } else {
                if ($start <= $exec_time && $exec_time < $end) {
                    $log->info("Rule Date is Valid");
                    return true;
                }
            }
          }
        }
      }

    }
    $log->info("Rule Date is invalid");
    return false;
  }

  /**
  * record - Ativa gravação da ligação na próxima execução da regra.
  */
  public function record() {
    $this->isRecording = true;
  }

  /**
  * removerAcao - Remove a ação da lista pelo índice dela.
  * Remove ação do índice específicado e reordena os índices.
  *
  * @param <int> $indice
  * @return PBX_Rule_Action|null Regra removida.
  */
  public function removerAcao($indice) {
    $nova_ordem = array();
    $removed = null;
    // Loop necessário para manter a estrutura linear organizada das ações
    foreach ($this->acoes as $i => $acao) {
      if ($i != $indice) {
        $nova_ordem[] = $acao;
      } else {
        $removed = $acao;
        $removed->setRegra(null);
      }
    }
    $this->acoes = $nova_ordem;

    return $removed;
  }

  /**
  * removeWeekDay - Remove um dia da semana da lista de dias válidos.
  * @param <string> $weekDay
  */
  public function removeWeekDay($weekDay) {
    $weekDay = strtolower($weekDay);
    $index = array_search($weekDay, $this->validWeekDays);
    if ($index !== null) {
      unset($this->validWeekDays[$index]);
    }
  }

  /**
  * setActive - Define se a regra está ativa ou não
  * @param <boolean> $active
  */
  public function setActive($active) {
    $this->active = $active;
  }

  /**
  * setAsteriskInterface - Fornece uma interface de conexão com o asterisk.
  * @param Asterisk_AGI $asterisk
  */
  public function setAsteriskInterface($asterisk) {
    $this->asterisk = $asterisk;
    $this->plugins->setAsteriskInterface($asterisk);
    if (!isset($this->request))
    $this->request = $asterisk->requestObj;
  }

  /**
  * setDesc - Define uma descrição para a regra
  * @param <string> $desc descrição
  */
  public function setDesc($desc) {
    $this->desc = $desc;
  }

  /**
  * setType - Seta o tipo da regra
  * Define o tipo para a regra.
  * @param <string> $type
  */
  public function setTypeRule($type) {
    $this->typeRule = $type;
  }

  /**
  * setDstList - Define a lista de destinos para o ramal.
  * Não há uma definição direta para que haja validação nos itens.
  * @param <array> $list
  */
  public function setDstList($list) {
    $this->dstClean();
    foreach ($list as $dst) {
      $this->addDst($dst);
    }
  }

  /**
  * setId - Seta o id da regra
  * Define um numero de identificação para a regra.
  * @param <int> $id
  */
  public function setId($id) {
    $this->id = $id;
  }

  /**
  * setPriority - Define uma prioridade para a regra
  * @param <int> $prio
  */
  public function setPriority($prio) {
    $this->priority = $prio;
  }

  /**
  * setRecordApp - Define o nome da aplicação que será executada para iniciar a gravação
  * das ligações.
  *
  * O parametro options será repassado como parametro para a aplicação de
  * gravação, pode, e será na maioria dos casos, um array contendo o nome
  * do arquivo de gravação e as flags (opções) para execução. ex:
  *
  * <code>
  * $recordApp = 'MixMonitor'
  * $options = array(
  *     "/tmp/filename.wav",
  *     "b"
  * );
  * </code>
  *
  * @param <string> $recordApp Application name
  * @param <string> $options Opções da applicação
  */
  public function setRecordApp($recordApp, $options) {
    $this->recordApp = array(
      "application" => $recordApp,
      "options" => $options
    );
  }

  /**
  * setRequest - Requisição de conexão para ser usado na execução da regra.
  * @param Asterisk_AGI_Request $request
  */
  public function setRequest($request) {
    $this->request = $request;
  }

  /**
  * setSrcList - Define a lista de origens para o ramal.
  * Não há uma definição direta para que haja validação nos itens.
  * @param <array> $list
  */
  public function setSrcList($list) {
    $this->srcClean();
    foreach ($list as $src) {
      $this->addSrc($src);
    }
  }

  /**
  * srcClean - Limpa o array de origens
  */
  public function srcClean() {
    $this->src = array();
  }

  /**
  * __toString - Retorna um string imprimivel dessa regra
  */
  public function __toString() {
    return $this->desc;
  }

}
