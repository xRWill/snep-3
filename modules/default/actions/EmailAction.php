<?php

/**
 * Ação que envia email quando executada.
 *
 * @see PBX_Rule
 * @see PBX_Rule_Action
 *
 * @category  Snep
 * @package   PBX_Rule_Action
 * @copyright Copyright (c) 2009 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class EmailAction extends PBX_Rule_Action {

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
     * @return Nome da Ação
     */
    public function getName() {
        return $this->i18n->translate("Send Email");

    }

    /**
     * Retorna o numero da versão da classe.
     * @return Versão da classe
     */
    public function getVersion() {
        return Zend_Registry::get('snep_version');
    }

    /**
     * Retorna uma breve descrição de funcionamento da ação.
     * @return Descrição de funcionamento ou objetivo
     */
    public function getDesc() {
        return $this->i18n->translate("Send an email when executed");
    }

    /**
     * Devolve um XML com as configurações requeridas pela ação
     * @return String XML
     */
    public function getConfig() {
        $i18n = $this->i18n;
        $to = (isset($this->config['to'])) ? "<value>{$this->config['to']}</value>" : "";
        $message = (isset($this->config['message'])) ? "<value>{$this->config['message']}</value>" : "";
        $subject = (isset($this->config['subject'])) ? "<value>{$this->config['subject']}</value>" : "";
        $info = (isset($this->config['info'])) ? "<value>{$this->config['info']}</value>" : "";

        $subj = $i18n->translate("Subject");
        $dst = $i18n->translate("Address");
        $dstdesc = $i18n->translate("E-mail address. Split with ',' to use more than one email address.");
        $msg = $i18n->translate("Message");
        //$information = $i18n->translate("display information of source and destination of the call in the body of the email?");

        return <<<XML
<params>
    <string>
        <id>subject</id>
        <label>$subj</label>
        <description></description>
        $subject
    </string>
    <email>
        <id>to</id>
        <label>$dst</label>
        <description>$dstdesc</description>
        $to
    </email>
    <text>
        <id>message</id>
        <label>$msg</label>
        $message
    </text>
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
        $config = Zend_Registry::get('config');
        $i18n = $this->i18n;

        $view = new Zend_View();
        $view->setScriptPath(APPLICATION_PATH . '/modules/default/views/scripts/route');

        $view->content = $this->config['message'];
        $view->call = array(
          'source' => $request->getOriginalCallerid(),
          'destination' => $request->destino,
          'calldate' => date('Y-m-d H:i:s')
        );

        $msg_content = $view->render('email_message.phtml');

        $message = array(
          "message" => $msg_content,
          "from" => $config->system->mail,
          "to" => $this->config['to'],
          "subject" => $this->config['subject']
        );


        $log->info("Sending email to [" . $this->config['to'] . "] from [{$config->system->mail}]");

        $mail = Snep_Sendmail::sendEmail($message);
    }

}
