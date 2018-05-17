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
require_once 'Snep/Inspector.php';

/**
 * Sound Files Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class SoundFilesController extends Zend_Controller_Action {

    public function init() {

        $this->lang = Zend_Registry::get('config')->system->language;
        $this->path_sound = Zend_Registry::get('config')->system->path->asterisk->sounds;

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName();
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;
    }

    /**
     * indexAction - List all sound files
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files")));

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from("sounds")
                ->where("tipo = 'AST'")
                ->where("language = '".$this->lang."'")
                ->order('arquivo');

        $stmt = $db->query($select);
        $files = $stmt->fetchAll();
        
        if(!empty($files)){
            // Mount file list and verify if exists file in directory
            foreach ($files as $id => $file) {
                $info = Snep_SoundFiles_Manager::verifySoundFiles($file['arquivo']);
                $_files[] = array_merge($file, $info);
            }
        }

        $this->view->files = $_files;


    }

    /**
     *  addAction - Add Sound File
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Add")));

        //Define the action and load form
        $this->view->action = "add" ;
        $this->renderScript( 'sound-files/addedit.phtml' );

        if ($this->_request->getPost()) {

            // Form data
            $dados = $this->_request->getParams();
            $description = $dados['description'];
            $gsmConvert = $dados['gsm'];
            $type = 'AST';

            // File Upload data
            $originalName = Snep_SoundFiles_Manager::parseName($_FILES['inputFile']['name']) ;
            $uploadName = $_FILES['inputFile']['tmp_name'];

            $language =  ($this->lang === "en" ? $language = "" : $language = $this->lang) ;

            // Define files paths
            $arq_dst = $this->path_sound . "/" . $language . "/" . $originalName;
            $arq_tmp = $this->path_sound . "/" . $language . "/tmp/" . $originalName;
            $arq_bkp = $this->path_sound . "/" . $language . "/backup/" . $originalName;

            // Verify file consistence
            $error = false ;
            // Verify if file exists into db table
            if (Snep_SoundFiles_Manager::get($originalName)) {
                $this->view->error_message .= $this->view->translate("File already exists in database for this language.")."<br />";
                $error = true;
            }
            // Verify if extension is valid
            if ( !Snep_SoundFiles_Manager::checkType(pathinfo($originalName,PATHINFO_EXTENSION)) ) {
                $this->view->error_message .= $this->view->translate("File extesion is invalid.")."<br />" ;
                $error = true;
            }

            if(!file_exists('/usr/bin/sox') && !file_exists('/usr/local/bin/sox')){
              $this->view->error_message .= "<br />".$this->view->translate("The 'sox' package is not installed!") ."<br />" ;
              $error = true;
            }
            // Verify if file exists into  directory
            if (file_exists($arq_dst)) {
                $this->view->error_message .= "<br />".$this->view->translate("The file %s already exists in directory.", $arq_dst) ."<br />" ;
                $error = true;
            }
            if (!$error) {

                if (!move_uploaded_file($uploadName, $arq_tmp)) {
                    $this->view->error_message = $this->view->translate('Unable to upload file.');
                    $this->renderScript('error/sneperror.phtml');
                } else {

                    // Move from tmp to dst and/or Convert to GSM
                    if ($dados['gsm']) {
                        $fileNe = $path_sound  . '/' . $language . '/' . basename( $arq_dst, '.wav') . '.gsm';

                        exec("sox " . $arq_tmp. " -r 8000 {". $fileNe . "}", $result);

                        $originalName = basename($originalName, '.wav') . ".gsm";
                    } else {
                        exec("sox " . $arq_tmp . " -r 8000 -c 1 -e signed-integer -b 16 " . $arq_dst, $result);
                    }
                    if (!empty($result)) {
                        $this->view->error_message = $result;
                        $this->renderScript('error/sneperror.phtml');
                    } else {

                        // If upload ok, create table register and log
                        if (file_exists($arq_dst) || file_exists($fileNe)) {
                            Snep_SoundFiles_Manager::add(array('arquivo' => $originalName,
                                'descricao' => $description,
                                'language' => $this->lang,
                                'tipo' => 'AST'));

                            //audit
                            Snep_Audit_Manager::SaveLog("Added", 'sounds', $id, $this->view->translate("Sound") . " {$id} " . $originalName);

                            $this->_redirect($this->getRequest()->getControllerName());
                        } else {
                            $this->view->error_message = $this->view->translate('Invalid format');
                            $this->renderScript('error/sneperror.phtml');
                        }
                    } // end : empty(result)
                } // end : move_uploaded_file()
            } else {
                $this->renderScript('error/sneperror.phtml');
            }
        } // end POST

    }

    /**
     * editAction - Edit a sound file
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Edit")));

        $arquivo = $this->_request->getParam("arquivo");

        $sound = Snep_SoundFiles_Manager::get($arquivo);
        $arq_orig = $sound['arquivo'];

        $this->view->sound = $sound;

        //Define the action and load form
        $this->view->action = "edit" ;
        $this->renderScript('sound-files/addedit.phtml' );

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            $description = $dados['description'];
            $gsmConvert = $dados['gsm'];
            $type = 'AST';

            // File Upload data
            $originalName = Snep_SoundFiles_Manager::parseName($_FILES['inputFile']['name']) ;
            $uploadName = $_FILES['inputFile']['tmp_name'];

            $language =  ($this->lang === "en" ? $language = "" : $language = $this->lang) ;

            $arq_tmp  = $this->path_sound . "/" . $language . "/tmp/" . $originalName;
            $arq_dst  = $this->path_sound . "/" . $language . "/" . $arq_orig;
            $arq_bkp  = $this->path_sound . "/" . $language . "/backup/" . $arq_orig;
            $error = false ;

            // If change file, verify
            if ($uploadName) {

                if ( !Snep_SoundFiles_Manager::checkType(pathinfo($originalName,PATHINFO_EXTENSION)) ) {
                    $this->view->error_message = $this->view->translate("File extesion is invalid.");
                    $this->renderScript('error/sneperror.phtml');
                } else {

                    // Move file and convert
                    if (move_uploaded_file($uploadName, $arq_tmp)) {
                        $this->view->error_message = $this->view->translate('Unable to upload file.');
                        $this->renderScript('error/sneperror.phtml');

                        // Create backup
                        exec("cp " . $arq_dst   . " " . $arq_bkp,$result);

                        // Move from tmp to dst and/or Convert to GSM
                        if ($gsmConvert) {
                            $fileNe = $path_sound . '/' . $language . '/' . basename( $arq_dst, '.wav') . '.gsm';
                            exec("sox " . $arq_tmp. " -r 8000 {". $fileNe . "}", $result);
                            $originalName = basename($originalName, '.wav') . ".gsm";
                        } else {
                            exec("sox " . $arq_tmp . " -r 8000 -c 1 -e signed-integer -b 16 " . $arq_dst, $result);
                        }

                        if (!empty($result)) {
                            $this->view->error_message = $result;
                            $this->renderScript('error/sneperror.phtml');
                        }
                    } // end move_uploaded_file()
                } // end : check extension file
            } // end: upload new file

            // Change register file

            Snep_SoundFiles_Manager::edit($dados);
            
            //audit
            Snep_Audit_Manager::SaveLog("Updated", 'sounds', $id, $this->view->translate("Sound") ." ". $dados["arquivo"]);
            
            $this->_redirect($this->getRequest()->getControllerName());
        }

    }

    /**
     * removeAction - Remove a Sound File
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Delete")));

        $file = $this->_request->getParam('arquivo');

        $this->view->id = $file;
        $this->view->remove_title = $this->view->translate('Delete Sound Files.');
        $this->view->remove_message = $this->view->translate('The sound files will be deleted. After that, you have no way get it back.');
        $this->view->remove_form = 'sound-files';
        $this->renderScript('remove/remove.phtml');

        if ($this->_request->getPost()) {
            $id = $_POST['id'];
            $file = Snep_SoundFiles_Manager::remove($id);

            if ($file) {

                $result = Snep_SoundFiles_Manager::verifySoundFiles($id, true);

                if ($result['fullpath']) {
                    try {
                        //audit
                        Snep_Audit_Manager::SaveLog("Deleted", 'sounds', $id, $this->view->translate("Sound") . " {$id} " . $result['fullpath']);
                        exec("rm -f {$result['fullpath']} ");

                    } catch (Exception $e) {
                        throw new ErrorException($this->view->translate("Unable to remove file"));
                    }
                }

            } else {
                $this->view->error_message = $this->view->translate("File not found.");
                $this->renderScript('error/sneperror.phtml');
            }

            $this->_redirect($this->getRequest()->getControllerName());
        }
    }

    /**
     * restoreAction
     * @throws ErrorException
     */
    public function restoreAction() {

        $file = $this->_request->getParam("arquivo");

        if ($file) {
            $result = Snep_SoundFiles_Manager::verifySoundFiles($file, true);

            if ($result['fullpath'] && $result['backuppath']) {
                try {
                    exec("mv {$result['backuppath']}  {$result['fullpath']} ");
                } catch (Exception $e) {
                    throw new ErrorException($this->view->translate("Unable to restore file"));
                }
            }
        }

        $this->_redirect($this->getRequest()->getControllerName());
    }


    /**
     * synchronizeAction - Synchronize sounds file
     */
    public function synchronizeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Synchronization of sound files ")));

        // Arquivos de som cadastrados no banco
        $soundDb = Snep_SoundFiles_Manager::getSounds();

        $list_db = array();
        foreach ($soundDb as $sound) {
            $list_db[] = $sound['arquivo'];
        }

        // Lista de arquivos de som da pasta sounds
        $language =  $this->lang ;
        $pasteSounds = $this->path_sound .'/'.$language.'/';
        $listSounds = new DirectoryIterator($pasteSounds);

        $list_sounds = array();
        foreach ($listSounds as $fileInfo) {
            if($fileInfo->isDot())
                continue;
            $list_sounds[] = ($fileInfo->getFilename());
        }

        // Arquivos contidos no banco alem dos existentes no diretorio
        $fileNotExist = array_diff($list_db, $list_sounds);
        // Arquivos contidos no diretorio além dos cadastrados no banco
        $fileDirectory = array_diff($list_sounds, $list_db);

        $array_directory = array();
        foreach ($fileDirectory as $number => $file) {

            if (substr(strtolower($file), -3) === 'gsm' || substr(strtolower($file), -3) === 'wav' || substr(strtolower($file), -3) === 'mp3') {
                $array_directory[] = $file;
            }
        }

        //retira dados duplicados do array
        $fileNotExist = array_unique($fileNotExist);
        $array_directory = array_unique($array_directory);


        $bool = false;
        if ($fileNotExist) {
            $bool = true;
        }
        if ($array_directory) {
            $bool = true;
        }

        if ($bool == true) {

            $this->view->submit = true;
            $this->view->msgclass = 'failure';

            $message = "";
            foreach ($fileNotExist as $key => $file) {
                $message .= $file . "<br />";
            }

            $this->view->messagedb = $this->view->translate("The following files do not exist in the directory and will be excluded from the database.") . "<br />";
            $this->view->dadosdb = $message;

            $messagefile = "";
            foreach ($array_directory as $number => $archive) {
                $messagefile .= $archive . "<br />";
            }
            $this->view->messagedir = $this->view->translate("The following files were found in the directory and will be registered in the database.") . "<br />";
            $this->view->dadosdir = $messagefile;

        } else {
            $this->view->message = $this->view->translate("Your files are synchronized");
            $this->view->msgclass = 'sucess';
        }

        // After Post
        if ($this->_request->isPost()) {



            foreach ($fileNotExist as $key => $file) {
                Snep_SoundFiles_Manager::remove($file);

            }
            foreach ($array_directory as $cont => $archive) {
                Snep_SoundFiles_Manager::addSounds($archive);
            }
            $this->_redirect($this->getRequest()->getControllerName());
        }

    }

}
