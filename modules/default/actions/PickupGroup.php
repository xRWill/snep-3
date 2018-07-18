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
 * Set PickupGroup
 *
 * @category  Snep
 * @package   PBX_Rule_Action
 * @copyright Copyright (c) 2018 OpenS Tecnologia
 * @author    Tiago Zimmermann
 */
class PickupGroup extends PBX_Rule_Action {

  /**
   * @var Zend_Translate
   */
  private $i18n;

  /**
  * Construtor
  * @param array $config configurações da ação
  */
  public function __construct() {
    $this->i18n = Zend_Registry::get("Zend_Translate");
  }

  /**
  * Retorna o nome da Ação. Geralmente o nome da classe.
  *
  * @return Name da Ação
  */
  public function getName() {
    return $this->i18n->translate("Define Pickup Group");
  }

  /**
  * @return string
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

    if(!isset($config['pickupgroup']) ) {
      throw new PBX_Exception_BadArg("PickupGroup is required");
    }

    $this->config = $config;
  }

    /**
     * Retorna uma breve descrição de funcionamento da ação.
     * @return Descrição de funcionamento ou objetivo
     */
    public function getDesc() {
        return $this->i18n->translate("Define Pickup Group");
    }

  /**
   * Devolve um XML com as configurações requeridas pela ação
   * @return String XML
   */
  public function getConfig() {
    
    $pickupGroup = (isset($this->config['pickupgroup']))?"<value>{$this->config['pickupgroup']}</value>":"";
    return <<<XML
<params>
    <pickupgroup>
        <id>pickupgroup</id>
        $pickupGroup
    </pickupgroup>
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

    $pickupGroup  = $this->config['pickupgroup'];

    $log->info("Definindo Grupo de Captura ".$pickupGroup.".");
    $return = $asterisk->exec('Set', '__PICKUPMARK='.$pickupGroup);
        
  }
}
