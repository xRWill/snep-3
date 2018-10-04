<?php
/**
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
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
 * Enviar para Fila
 *
 * Ação que envia a ligação para uma fila de atendimento.
 *
 * @category  Snep
 * @package   PBX_Rule_Action
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class Queue extends PBX_Rule_Action {

    /**
     * @var Internacionalização
     */
    private $i18n;

    /**
     * Construtor
     * @param array $config configurações da ação
     */
    public function __construct() {
        $this->i18n = Zend_Registry::get("i18n");
    }

    /**
     * Retorna o nome da Ação. Geralmente o nome da classe.
     *
     * @return Name da Ação
     */
    public function getName() {
        return $this->i18n->translate("Send to Queue");
    }

    /**
     * Retorna o numero da versão da classe.
     *
     * @return Versão da classe
     */
    public function getVersion() {
        return SNEP_VERSION;
    }

    /**
     * Seta as configurações da ação.
     *
     * @param array $config configurações da ação
     */
    public function setConfig($config) {
        $this->config = $config;
    }

    /**
     * Retorna uma breve descrição de funcionamento da ação.
     * @return Descrição de funcionamento ou objetivo
     */
    public function getDesc() {
        return $this->i18n->translate("Send call to an answering queue");
    }

    /**
     * Devolve um XML com as configurações requeridas pela ação
     * @return String XML
     */
    public function getConfig() {
        $i18n = $this->i18n;
        $queue = (isset($this->config['queue']))?"<value>{$this->config['queue']}</value>":"";
        $timeout = (isset($this->config['timeout']))?"<value>{$this->config['timeout']}</value>":"";
        $options = (isset($this->config['options']))?"<value>{$this->config['options']}</value>":"";

        return <<<XML
<params>
    <queue>
        <id>queue</id>
        <label>{$i18n->translate("Queue")}</label>
        $queue
    </queue>
    <options>
	<id>options</id>
        <default>t</default>
	$options
	<label>{$i18n->translate("Queue Options")}</label>
    </options>

    <int>
        <id>timeout</id>
        <default>180</default>
        <label>{$i18n->translate("Timeout")}</label>
        <size>4</size>
        <unit>{$i18n->translate("in seconds")}</unit>
        $timeout
    </int>
</params>
XML;
    }

    /**
     * Executa a ação. É chamado dentro de uma instancia usando AGI.
     *
     * @param Asterisk_AGI $asterisk
     * @param Asterisk_AGI_Request $request
     */
    public function execute($asterisk, $request) {
        $log = Zend_Registry::get('log');
        $calleridname = $asterisk->get_variable("CALLERID(name)");
        $calleridnum = $asterisk->get_variable("CALLERID(num)");
        if ($calleridname['data'] === "" || $calleridname['data'] === "unknown"){
           $asterisk->set_variable("CALLERID(name)",$calleridnum['data']);
        }
        $asterisk->answer();
        $queue_name = $this->config['queue'];
        $queue_options = $this->config['options'];
        $queue_timeout = $this->config['timeout'];
        $asterisk->exec("UserEvent", array("QueueData", "queue: $queue_name", "options: $queue_options", "timeout: $queue_timeout"));
        $result = $asterisk->exec('Queue', array($this->config['queue'],$this->config['options'],'','',$this->config['timeout']));
        if($result['result'] == -1) {
            throw new PBX_Rule_Action_Exception_StopExecution();
        }
	$blind = $asterisk->get_variable("BLINDTRANSFER");
	$asterisk->verbose($blind);
	if($blind['result'] == 1) {
	  $log->debug("Resultado da variavel BLINDTRANFER preenchida, ligacao transferida");
	    throw new PBX_Rule_Action_Exception_StopExecution();
	}
    }
}
