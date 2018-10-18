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

/**
 * Music on Hold Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class MusicOnHoldController extends Zend_Controller_Action {

    /**
     * Initial settings of the class
     */
    public function init() {

        $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->view->key = Snep_Dashboard_Manager::getKey(
            Zend_Controller_Front::getInstance()->getRequest()->getModuleName(),
            Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
            Zend_Controller_Front::getInstance()->getRequest()->getActionName());
        $this->modes = array(
            'files' => $this->view->translate('Directory')
            // 'mp3' => $this->view->translate('MP3'),
            // 'quietmp3' => $this->view->translate('Normal'),
            // 'mp3nb' => $this->view->translate('Without buffer'),
            // 'quietmp3nb' => $this->view->translate('Without buffer quiet'),
            // 'custom' => $this->view->translate('Custom application')
            );
        $this->view->lineNumber = Zend_Registry::get('config')->ambiente->linelimit;
        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName();
        $this->view->path_base = Zend_Registry::get('config')->system->path->asterisk->moh;
    }

    /**
     * indexAction - List all Music on Hold sounds
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions")));

        Snep_SoundFiles_Manager::syncFiles('moh');

        $sections = Snep_SoundFiles_Manager::getClasses();

        // Count number of files in each section/class
        foreach ($sections as $key => $value) {
            $dir = $value['directory'];
            if (is_dir($dir)) {
                $count = 0 ;
                $scanned_directory = array_diff(scandir($dir), array('..', '.', 'backup', 'tmp'));
                foreach ($scanned_directory as $sd_key => $sd_value) {
                    if (is_dir($dir . '/' . $sd_value)) {
                        continue ;
                    } else {
                        $count ++ ;
                    }
                }
                $sections[$key]['count'] = $count;
            } else {
                $sections[$key]['count'] = 'ND' ;
            }
        }

        $this->view->modes = $this->modes;
        $this->view->sections = $sections;

    }

    /**
     *  addAction - Add Sound File
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Add")));

        $viewModes = "";
        foreach($this->modes as $key => $value){
            $viewModes .= "<option value='".$key . "'>".$value." </option>\n";

        }
        //Define the action and load form
        $this->view->modes = $viewModes ;
        $this->view->action = "add" ;
        $this->renderScript( 'music-on-hold/addedit.phtml' );

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            $classes = Snep_SoundFiles_Manager::getClasses();
            $form_isValid = true;

            if (file_exists($dados['directory'])) {
                $message = $this->view->translate('Directory already exists');
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
                $form_isValid = false;
            }

            foreach ($classes as $name => $item) {

                if ($item['name'] == $dados['name']) {
                    $message = $this->view->translate('Music on hold class already exists');
                    $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
                    $form_isValid = false;
                }

                $fullPath = $dados['base'] . $dados['directory'];
                
                if ($item['directory'] == $fullPath) {
                    $message = $this->view->translate('Directory already exists');
                    $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
                    $form_isValid = false;
                }
            }

            if ($form_isValid) {
                $dados['directory'] = $dados['base'] .'/' . $dados['directory'];
                $dados['name'] = $dados['nome'];
                Snep_SoundFiles_Manager::addClass($dados);
                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
        
    }

    /**
     * Edit Section/Class
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Edit")));

        $section = $this->_request->getParam("section");
        $data = Snep_SoundFiles_Manager::getClasse($section);

        $viewModes = "";
        foreach($this->modes as $key => $value){
            $viewModes .= ($key == $data['mode']) ? "<option value='".$key . "' selected >".$value." </option>\n": "<option value='".$key . "'>".$value." </option>\n";
        }

        $this->view->modes = $viewModes;

        $directory = explode("/", $data['directory']);
        $directoryName = array_pop($directory);

        $this->view->directoryName = $directoryName;
        $originalName = $data['name'];
        
        $this->view->file = $data;

        //Define the action and load form
        $this->view->disabled = 'disabled';
        $this->view->action = "edit" ;
        $this->renderScript( 'music-on-hold/addedit.phtml' );

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();



            $class = array(
                'name' => $dados['nome'],
                'mode' => $dados['mode'],
                'directory' => $dados['base'].'/'.$dados['folder']);

            Snep_SoundFiles_Manager::editClass($data['name'], $class);

            $this->_redirect($this->getRequest()->getControllerName());
            
        }

    }

    /**
     * Remove a Carrier
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Remove")));

        $file = $this->_request->getParam('section');

        $this->view->class = Snep_SoundFiles_Manager::getClasse($file);
        $this->view->message = $this->view->translate("You are removing a music on hold class, it has some audio files attached to it.");
        $this->view->confirmation = $this->view->translate("Delete Sound Files?");
        $this->view->id = $file;

        
        if ($this->_request->getPost()) {
            
            if ($_POST['delete']) {
                $class = Snep_SoundFiles_Manager::getClasse($_POST['id']);
                Snep_SoundFiles_Manager::removeClass($class);
            }
            $this->_redirect($this->getRequest()->getControllerName());
            
        }
        
    }

    /**
     * fileAction
     */
    public function fileAction() {

        $section = $this->_request->getParam('section');

        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" .
                $this->getRequest()->getControllerName();

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Files")));

        $class = Snep_SoundFiles_Manager::getClasse($section);
        $files = Snep_SoundFiles_Manager::getClassFiles($section);
        
        if(empty($files)){
            $this->view->error_message = $this->view->translate("You do not have registered file. <br><br> Click 'Add File' to make the first registration");
        }

        $this->view->files = $files;
        $this->view->section = $section;
    
    }

    /**
     * AddfileAction - Add file
     */
    public function addfileAction() {

        $className = $this->_request->getParam('section');

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Add File")));

        $this->view->section = $className;
        
        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            $data = $_FILES["inputFile"];

            // Converter megabytes em bytes
            $size_in_mega = ini_get('upload_max_filesize');
            $size_in_bytes = Snep_SoundFiles_Manager::converter($size_in_mega);
            
            // Information about section/class
            $class = Snep_SoundFiles_Manager::getClasse($dados['section']);
            $form_isValid = true;
            
            $invalid = array('â', 'ã', 'á', 'à', 'ẽ', 'é', 'è', 'ê', 'í', 'ì', 'ó', 'õ', 'ò', 'ú', 'ù', 'ç', " ", '@', '!');
            $valid = array('a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'o', 'u', 'u', 'c', "_", '_', '_');

            if ($data["size"] > $size_in_bytes) {
                $this->view->error_message = $this->view->translate("File larger than $size_in_mega");
                $this->renderScript('error/sneperror.phtml');
            }

            if ($data["type"] != "audio/wav" && $data["type"] != "audio/mp3") {
                $message = "Tamanho ou formato inválido";
                $this->_helper->redirector('sneperror','error',null,array('error_message'=>$message));
            }

            $originalName = str_replace($invalid, $valid, $_FILES['inputFile']['name']);
            $clean_name = strstr($originalName, '.', true);
            $validname = $clean_name . '.wav';

            $files = Snep_SoundFiles_Manager::get($originalName);

            if ($files) {
                $this->view->error_message = $this->view->translate("File already exists");
                $this->renderScript('error/sneperror.phtml');
                $form_isValid = false;
            }

            if ($form_isValid) {


                $uploadName = $data['tmp_name'];
                $arq_tmp = $class['directory'] . "/tmp/" . $originalName;
                $arq_dst = $class['directory'] . "/" . $originalName;

                exec("mv $uploadName $arq_tmp");

                if ($_POST['gsm']) {
                    $fileNe = basename($arq_dst, '.wav');
                    exec("sox $arq_tmp -r 8000 {$fileNe}.gsm");
                    $originalName = basename($originalName, '.wav') . ".gsm";
                } else {
                    exec("sox $arq_tmp -r 8000 -c 1 -e signed-integer -b 16 $arq_dst");
                }

                if (file_exists($arq_dst) || file_exists($fileNe)) {
                    Snep_SoundFiles_Manager::
                    addClassFile(array('arquivo' => $originalName,
                        'descricao' => $dados['description'],
                        'data' => new Zend_Db_Expr('NOW()'),
                        'tipo' => 'MOH',
                        'secao' => $dados['section']));
                } else {
                    $this->view->error_message = $this->view->translate("There were problems uploading the file. Please, contact your system administrator.");
                    $this->renderScript('error/sneperror.phtml');
                }
                $this->_redirect($this->getRequest()->getControllerName() . "/file/section/$className/");
            }
        }
        
    }

    /**
     * editfileAction - Edit file
     */
    public function editfileAction() {

        $fileName = $this->_request->getParam('file');
        $class = $this->_request->getParam('class');

        $this->view->file = Snep_SoundFiles_Manager::getClassFile($fileName,$class) ;

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Edit File"),
                    $fileName));


        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
 
            Snep_SoundFiles_Manager::editClassFile($dados);
    
            $this->_redirect($this->getRequest()->getControllerName(). "/file/section/".$dados['secao']);
        }

    }

    /**
     * removefileAction - Remove file
     */
    public function removefileAction() {
        
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Delete File")));

        $file = $this->_request->getParam('file');
        $class = $this->_request->getParam('class');
        $this->view->file = Snep_SoundFiles_Manager::getClassFile($file,$class) ;

        $this->view->remove_title = $this->view->translate('Delete music on hold file: '.$file); 
        $this->view->remove_message = $this->view->translate('The music on hold files will be deleted. After that, you have no way get it back.'); 
        $this->view->remove_form = 'music-on-hold'; 
        $this->view->remove_action = 'removefile'; 
        $this->renderScript($this->getRequest()->getControllerName().'/removefile.phtml');

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();

            $base_dir = Zend_Registry::get('config')->system->path->asterisk->moh; 
            if ($dados['secao'] != 'default') {
                $base_dir .= '/'.$dados['secao'] ;
            }
            $file_remove = $base_dir . '/' . $dados['arquivo'] ;
            
            if (file_exists($file_remove)) {
                exec("rm {$file_remove}");
            }
            exec("rm {$file_remove} ");

            Snep_SoundFiles_Manager::remove($dados['arquivo'], $dados['secao']);

            $this->_redirect($this->getRequest()->getControllerName() . "/file/section/".$dados['secao']);
        }
    }

}
