#!/usr/bin/php -q
<?php
/**
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
 *
 */

 // Display errors control
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

require_once "Bootstrap-script.php";
new Bootstrap();

require_once "Snep/Config.php";
require_once "Snep/Logger.php";
require_once "Zend/Console/Getopt.php";
ob_implicit_flush(true);

$config = Snep_Config::getConfig();
$log = Snep_Logger::getInstance();

Zend_Registry::set("db", Snep_Db::getInstance());
$db = Zend_Registry::get('db');
$log = Zend_Registry::get('log');

if($argc < 3) {
    $log->err("ERROR: This script wait at least 2 parameters: context, extension");
    exit(1);
}

$i18n = Zend_Registry::get("i18n");
$msg = array(
  'context' => $argv[1],
  'exten'=> $argv[2],
  'newmsg'=> $argv[3],
  'msgid' => $argv[3] - 1,
  'oldmesg' => $argv[4]);

$select = $db->select()->from('voicemail_users')->where('mailbox = ?', $msg['exten']);
$mailbox = $db->query($select)->fetch();
if(isset($mailbox['email'])){
  $myFile = "/var/spool/asterisk/voicemail/default/{$mailbox['mailbox']}/INBOX/msg000" . $msg['msgid'];
  $myAudio =  $myFile . ".wav";
  $myAudioText =  $myFile . ".txt";
  $msg_data = new Zend_Config_Ini($myAudioText, 'message');

  // $mail->addAttachment($at);
  $log->info('Received new voicemail message to:' . $msg['exten'] .  ' ID:' . $msg['newmsg'] . ' Email:' . $mailbox['email']);
  $view = new Zend_View();
  $view->setScriptPath(APPLICATION_PATH . '/modules/default/views/scripts/voicemail');
  $view->translate = $i18n;
  $view->content = $i18n->translate("You received a new Call Message from ") . "<b>" . $msg_data->callerid . "</b>";
  $view->call = array(
    'source' => $msg_data->callerid,
    'mailbox' => $msg_data->origmailbox,
    'destination' => $msg['exten'],
    'duration' => $msg_data->duration,
    'calldate' => $msg_data->origdate
  );
  $msg_content = $view->render('new_message.phtml');

  $mail_msg = array(
    'message' => $msg_content,
    'subject' => $i18n->translate("A new voicemail message"),
    'to' => $mailbox['email'],
    'from' => $config->system->mail,
    'attachment' => $myAudio
  );
  $mail = Snep_Sendmail::sendEmail($mail_msg);
  if($mail){
    unlink($myAudio);
    unlink($myAudioText);
  }

}else{
  $log->info("No mailbox configured for:" . $msg['exten']);
}
