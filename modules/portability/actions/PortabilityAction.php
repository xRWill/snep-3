<?php

/**
 * Efetua consulta do número no sistema de portabilidade da OpenS.
 * É retornado o código da operadora.
 *
 * @see PBX_Rule
 * @see PBX_Rule_Action
 *
 * @category  Snep
 * @package   PBX_Rule_Action
 * @copyright Copyright (c) 2015 OpenS Tecnologia
 * @author    Tiago Zimmermann <tiago.zimmermann@opens.com.br>
 */
class PortabilityAction extends PBX_Rule_Action {

    /**
     * @var Internacionalização
     */
    private $i18n;
    private $destino;

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
     * @return Nome da Ação
     */
    public function getName() {
        return $this->i18n->translate("Portability");
    }

    /**
     * Retorna o numero da versão da classe.
     *
     * @return Versão da classe
     */
    public function getVersion() {
        return "1.0";
    }

    /**
     * Retorna uma breve descrição de funcionamento da ação.
     * @return Descrição de funcionamento ou objetivo
     */
    public function getDesc() {
        return $this->i18n->translate("Envia o número a ser consultado");
    }

    /**
     * Devolve um XML com as configurações requeridas pela ação
     * @return String XML
     */
    public function getConfig() {

        $i18n = $this->i18n;

        $prefix = (isset($this->config['prefix']))?"<value>{$this->config['prefix']}</value>":"";
        $type = (isset($this->config['type']))?"<value>{$this->config['type']}</value>":"";
        $type_desc = $i18n->translate("Invalid number handling");
        $type_audio_label = $i18n->translate("Play warning audio");
        $type_audio_label_2 = $i18n->translate("Insert prefix to handling");
        $prefix_label = $i18n->translate("Prefix to handling invalid numbers");
        $prefix_desc = $i18n->translate("The value must have 5 digits. You need create a Route rule to handling this Destination.");

        return <<<XML
<params>
    <radio>
        <id>type</id>
        <label>$type_desc</label>
        <default>audio</default>
        $type
        <option>
            <label>$type_audio_label</label>
            <value>audio</value>
        </option>
        <option>
            <label>$type_audio_label_2</label>
            <value>prefix</value>
        </option>
    </radio>
    <string>
        <id>prefix</id>
        <label>$prefix_label</label>
        <description>$prefix_desc</description>
        <size>5</size>
        $prefix
    </string>

</params>
XML;
    }

    /**
     * Executa a ação.
     * @param Asterisk_AGI $asterisk
     * @param PBX_Asterisk_AGI_Request $request
     */
    public function execute($asterisk, $request) {

        $log = Zend_Registry::get('log');
        $db  = Zend_Registry::get('db');

        $configuration = Snep_Register_Manager::get();
        if(!$configuration){
            $log->info("To use this service you must register your Snep");
        }

        $url = "https://api.opens.com.br/api/v1/portability/consult";
        $api_key = $configuration['api_key'];
        $client_key = $configuration['client_key'];
        $device_uuid = $configuration['uuid'];
        $this->destino = $request->destino;

        //config
        $type = $this->config['type'];
        $prefix = $this->config['prefix'];

        $service_url = $url."?client_key=".$client_key."&api_key=".$api_key."&number=".$this->destino."&device_uuid=".$device_uuid;

        $http = curl_init($service_url);

        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($http, CURLOPT_TIMEOUT,6);
	      curl_setopt($http, CURLOPT_CONNECTTIMEOUT, 6);
        $status = curl_getinfo($http, CURLINFO_HTTP_CODE);

        curl_setopt($http, CURLOPT_RETURNTRANSFER,1);

        $http_response = curl_exec($http);
        $httpcode = curl_getinfo($http, CURLINFO_HTTP_CODE);

        curl_close($http);

        $select = "SELECT * FROM `portability_cache` WHERE phone like '%{$this->destino}'";
        $stmt = $db->query($select);
        $cache = $stmt->fetch();

        if(strlen($cache['phone']) > 3){
            //exists
            $insert = false;
            $log->info("Portabilidade -> Número ".$this->destino." já existente no cache");
        }else{
            $log->info("Portabilidade -> Número ".$cache['phone']." não existente no cache");
            $insert = true;
        }

        switch ($httpcode) {
            case 200:
                $log->info("Portabilidade -> Encontrado operadora -> ".$http_response);

                if($insert){
                    //insert cache
                    $log->info("Portabilidade -> Inserindo número ".$http_response." no cache");
                    $insert_data = array('phone' => $http_response);
                    $db->insert('portability_cache', $insert_data);
                }else{
                    $log->info("Portabilidade -> Atualizando número ".$http_response." no cache");
                    $update_data = array('phone' => $http_response);
                    $db->update("portability_cache", $update_data, "phone like '%{$this->destino}'");
                }
                $asterisk->exec_goto('default',$http_response,1);
                break;
            case 401:
                $log->info("Portabilidade -> Consulta não autorizada");
                $asterisk->stream_file('portabilityError');
                $asterisk->hangup();
                break;
            case 402:
                $log->info("Portabilidade -> Central sem créditos disponíveis");
                $asterisk->stream_file('portabilityError');
                $asterisk->hangup();
                break;
            case 404:
                if($type == 'audio'){
                    $log->info("Portabilidade -> Número não encontrado na base da Portabilidade");
                    $asterisk->stream_file('portabilityError');
                    $asterisk->hangup();
                }else{
                    $rest = substr($http_response,0,5);
                    $number = substr($http_response,5);
                    $log->info("Portabilidade -> Número não encontrado -> ".$http_response);
                    $log->info("Portabilidade -> Rescrevendo número com valor do prefixo informado na ação -> ".$prefix.$number);
                    $asterisk->exec_goto('default',$prefix.$number,1);
                }
                break;
            default:
                $log->info("Portabilidade -> Houve algum erro durante o processo");
                if(strlen($cache['phone']) > 3){
                    $log->info("Portabilidade -> Completando chamada pelo número salvo no cache");
                    $asterisk->exec_goto('default',$cache['phone'],1);
                }else{
                    if($type == 'audio'){
                        $log->info("Portabilidade -> Número não encontrado na base da Portabilidade");
                        $asterisk->stream_file('portabilityError');
                        $asterisk->hangup();
                    }else{
                        $rest = substr($http_response,0,5);
                        $number = substr($http_response,5);
                        $log->info("Portabilidade -> Número não encontrado -> ".$http_response);
                        $log->info("Portabilidade -> Rescrevendo número com valor do prefixo informado na ação -> ".$prefix.$number);
                        $asterisk->exec_goto('default',$prefix.$number,1);
                    }
                }
                break;
        }

    }


}
