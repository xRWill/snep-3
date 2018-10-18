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
 * Classe to manager a Sound Files and MOH Sound Files.
 *
 * @see Snep_SoundFiles_Manager
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti <rafael@opens.com.br>
 *
 */
class Snep_SoundFiles_Manager {

    // --------------------------------------------------------------------------------------------
    // Functions for Sound Files
    // --------------------------------------------------------------------------------------------
    /**
     * Get a sound informations
     * @param <string> $file
     * @param <string> $language
     * @return <array> $sound
     */
    public function get($file,$lang = false) {
        $this->lang = Zend_Registry::get('config')->system->language;
        if (!$lang) {
            $lang = $this->lang;
        }

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.arquivo = ?", $file)
                ->where("sounds.language = ?", $lang);

        try {
            $stmt = $db->query($select);
            $sound = $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }

        return $sound;
    }


    /**
     * Add a sound file
     * @param array $file
     */
    public function add($file) {

        $db = Zend_Registry::get('db');

        $insert_data = array('arquivo' => $file['arquivo'],
            'descricao' => $file['descricao'],
            'data' => new Zend_Db_Expr('NOW()'),
            'language' => $file['language'],
            'tipo' => $file['tipo']);

        $db->insert('sounds', $insert_data);

        return $db->lastInsertId();
    }



    /**
     * Update a sound file register
     * @param Array $data
     */
    public function edit($file) {
        $this->lang = Zend_Registry::get('config')->system->language;

        $db = Zend_Registry::get('db');

        $update_data = array('descricao' => $file['description'],
            'tipo' => 'AST');

        $db->update("sounds", $update_data, "arquivo = '{$file['arquivo']}' and language = '{$this->lang}'");
    }

    /**
     * Remove a Sound File register
     * @param string $file
     *        string $class
     */
    public function remove($file, $class = false) {

        $this->lang = Zend_Registry::get('config')->system->language;
        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        if ($class) {
            $db->delete('sounds', "arquivo = '$file' and secao = '$class' and tipo='MOH' and language = '$this->lang'");
        } else {
            $db->delete('sounds', "arquivo = '$file' and tipo='AST' and language = '$this->lang'");
        }

        try {
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }



    // --------------------------------------------------------------------------------------------
    // Functions for Music on Hold
    // --------------------------------------------------------------------------------------------

    /**
     * Add a MOH Class
     * @param array $class
     * @return boolean
     */

    public function addClass($class) {

        $classes = self::getClasses();
        $classes[$class['name']] = $class;
        $todayDate = date("d/m/Y H:m:s");
        $header = ";----------------------------------------------------------------\n ";
        $header .= "; Arquivo: snep-musiconhold.conf - Cadastro Musicas de Espera    \n";
        $header .="; Sintaxe: [secao]                                               \n";
        $header .=";          mode=modo de leitura do(s) arquivo(s)                 \n";
        $header .=";          directory=onde estao os arquivos                      \n";
        $header .= ";          [application=aplicativo pra tocar o som] - opcional   \n";
        $header .= "; Include: em /etc/asterisk/musiconhold.conf                     \n";
        $header .= ";          include /etc/asterisk/snep/snep-musiconhold.conf      \n";
        $header .= "; Atualizado em: $todayDate                                      \n";
        $header .= "; Copyright(c) 2015 Opens Tecnologia                             \n";
        $header .= ";----------------------------------------------------------------\n";
        $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.          \n";
        $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de    \n";
        $header .= "; causar mau funcionamento do Asterisk                           \n";
        $header .= ";----------------------------------------------------------------\n\n";

        $body = '';
        foreach ($classes as $classe) {
            $body .= "[" . $classe['name'] . "] \n";
            $body .= "mode=" . $classe['mode'] . "\n";
            $body .= "directory=" . $classe['directory'] . "\n\n";
        }

        if (!file_exists($class['directory'])) {

            exec("mkdir {$class['directory']}");
            exec("mkdir {$class['directory']}/tmp");
            exec("mkdir {$class['directory']}/backup");

            file_put_contents('/etc/asterisk/snep/snep-musiconhold.conf', $header . $body);

            return true;
        }
        return false;
    }

    /**
     * Get information about a MOH Class
     * @param array $name
     * @return array $section
     */

   public function getClasse($name) {

        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array();
        foreach ($sections->toArray() as $class => $info) {

            if ($class == $name) {
                $_section['name'] = $class;
                $_section['mode'] = $info['mode'];
                $_section['directory'] = $info['directory'];
            }
        }
        return $_section;
    }

    /**
     * Get a list of MOH Classes
     * @return array
     */

    public function getClasses() {

        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array();
        foreach ($sections->toArray() as $class => $info) {
            $_section[$class]['name'] = $class;
            $_section[$class]['mode'] = $info['mode'];
            $_section[$class]['directory'] = $info['directory'];
        }
        return $_section;
    }

    /**
     * Get a MOH sound informations
     * @param <string> $file
     * @param <string> $section
     * @return <array> $sound:
     *              arquivo => string
     *              descricao => string
     *              data => string
     *              tipo => string
     *              secao => string
     *              language => string
     */
    public function getClassFile($file,$section) {
        $this->lang = Zend_Registry::get('config')->system->language;

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.arquivo = ?", $file)
                ->where("sounds.secao = ?", $section)
                ->where("sounds.tipo = 'MOH'")
                ->where("sounds.language = ?", $this->lang);

        try {
            $stmt = $db->query($select);
            $sound = $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }

        return $sound;
    }
    /**
     * Add a MOH sound
     * @param array $file
     * @return boolean
     */
    public function addClassFile($file) {

        $db = Zend_Registry::get('db');
        $this->lang = Zend_Registry::get('config')->system->language;

        $insert_data = array('arquivo' => $file['arquivo'],
            'descricao' => $file['descricao'],
            'data' => new Zend_Db_Expr('NOW()'),
            'tipo' => 'MOH',
            'language' => $this->lang,
            'secao' => $file['secao']);

        try {
            $db->insert('sounds', $insert_data);
        } catch (Exception $e) {

            return false;
        }

        return true ;
    }

    /**
     * Edit a MOH ound
     * @param array $file
     * @return boolean
     */
    public function editClassFile($file) {

        $db = Zend_Registry::get('db');
        $this->lang = Zend_Registry::get('config')->system->language;

        $insert_data = array('arquivo' => $file['arquivo'],
            'descricao' => $file['descricao'],
            'data' => new Zend_Db_Expr('NOW()'),
            'tipo' => 'MOH',
            'language' => $this->lang,
            'secao' => $file['secao']);

        try {
            $db->update('sounds', $insert_data, "arquivo='{$file['arquivo']}' and secao='{$file['secao']}' and language='{$this->lang}' and tipo='MOH'");
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get a list of sounds of thea MOH Class from table 'sounds' - don't read directory (See function: syncFiles())
     * @param string $class - Class/Section name
     * @return array of sounds:
     *              arquivo => string
     *              descricao => string
     *              data => string
     *              tipo => string
     *              secao => string
     *              language => string
     */
    public function getClassFiles($class) {

        $this->lang = Zend_Registry::get('config')->system->language;

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.secao = ?", $class)
                ->where("sounds.language = ?", $this->lang);

        try {
            $stmt = $db->query($select);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Edit a MOH Class
     * @param string $originalName $newName
     * @return boolean
     */

    public function editClass($originalName, $newClass) {

        $classes = self::getClasses();

        $directory = '';

        foreach ($classes as $class => $item) {

            if ($originalName === $item['name']) {
                $classes[$class]['name'] = $newClass['name'];
                $classes[$class]['mode'] = $newClass['mode'];
                $classes[$class]['directory'] = $newClass['directory'];
            }
        }
        $todayDate = date("d/m/Y H:m:s");
        $header =  ";----------------------------------------------------------------\n ";
        $header .= "; Arquivo: snep-musiconhold.conf - Cadastro Musicas de Espera    \n";
        $header .= "; Sintaxe: [secao]                                               \n";
        $header .= ";          mode=modo de leitura do(s) arquivo(s)                 \n";
        $header .= ";          directory=onde estao os arquivos                      \n";
        $header .= ";          [application=aplicativo pra tocar o som] - opcional   \n";
        $header .= "; Include: em /etc/asterisk/musiconhold.conf                     \n";
        $header .= ";          include /etc/asterisk/snep/snep-musiconhold.conf      \n";
        $header .= "; Atualizado em: $todayDate                             \n";
        $header .= "; Copyright(c) 2015 Opens Tecnologia                             \n";
        $header .= ";----------------------------------------------------------------\n";
        $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.          \n";
        $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de    \n";
        $header .= "; causar mau funcionamento do Asterisk                           \n";
        $header .= ";----------------------------------------------------------------\n\n";

        $body = '';

        foreach ($classes as $classe) {
            $body .= "[" . $classe['name'] . "] \n";
            $body .= "mode=" . $classe['mode'] . "\n";
            $body .= "directory=" . $classe['directory'] . "\n\n";
        }

        file_put_contents('/etc/asterisk/snep/snep-musiconhold.conf', $header . $body);

        return true;
    }

    /**
     * Remove a MOH Class and your files
     * @param string $class
     * @return boolean
     */
    public function removeClass($classRemove) {

        $classes = self::getClasses();

        $directory = '';
        foreach ($classes as $class => $item) {

            if ($classRemove['name'] == $item['name']) {

                if (file_exists($classRemove['directory'])) {
                    exec("rm -rf {$classRemove['directory']}");
                }
                unset($classes[$class]);
            }
        }
        $todayDate = date("d/m/Y H:m:s");
        $header =  ";----------------------------------------------------------------\n ";
        $header .= "; Arquivo: snep-musiconhold.conf - Cadastro Musicas de Espera    \n";
        $header .= "; Sintaxe: [secao]                                               \n";
        $header .= ";          mode=modo de leitura do(s) arquivo(s)                 \n";
        $header .= ";          directory=onde estao os arquivos                      \n";
        $header .= ";          [application=aplicativo pra tocar o som] - opcional   \n";
        $header .= "; Include: em /etc/asterisk/musiconhold.conf                     \n";
        $header .= ";          include /etc/asterisk/snep/snep-musiconhold.conf      \n";
        $header .= "; Atualizado em: $todayDate                             \n";
        $header .= "; Copyright(c) 2015 Opens Tecnologia                             \n";
        $header .= ";----------------------------------------------------------------\n";
        $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.          \n";
        $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de    \n";
        $header .= "; causar mau funcionamento do Asterisk                           \n";
        $header .= ";----------------------------------------------------------------\n\n";

        $body = '';
        foreach ($classes as $classe) {
            $body .= "[" . $classe['name'] . "] \n";
            $body .= "mode=" . $classe['mode'] . "\n";
            $body .= "directory=" . $classe['directory'] . "\n\n";
        }

        file_put_contents('/etc/asterisk/snep/snep-musiconhold.conf', $header . $body);
    }

    // --------------------------------------------------------------------------------------------
    // Functions for syncronize files with database
    // --------------------------------------------------------------------------------------------
    /**
     * Get file list MOH/AST sounds of the directory and update database
     * @param string $type : ast / moh
     */
    public function syncFiles($type='ast') {

        $db = Zend_Registry::get('db');
        $this->lang =  Zend_Registry::get('config')->system->language;
        if ($type === 'moh') {
            $this->base_dir = Zend_Registry::get('config')->system->path->asterisk->moh;
        } else {
            $this->base_dir = Zend_Registry::get('config')->system->path->asterisk->sounds;
        }

        $select = $db->select()
                ->from('sounds')
                ->where('sounds.tipo = ?',  strtoupper($type))
                ->where('language = ?', $this->lang);

        try {
            $stmt = $db->query($select);
            $sounds = $stmt->fetchAll();
        } catch (Exception $e) {
            return false;
        }
        // Remove all sounds not found in $dir
        $_sound = array();
        foreach ($sounds as $sound) {
            if ($type === "moh") {
                $dir = $sound['secao'] === 'default' ? $this->base_dir : $this->base_dir . '/' . $sound['secao'];
            } else {
                $dir = $base_dir.'/'.$lang;
            }
            if (file_exists($dir."/".$sound['arquivo'])) {
                if ($type === "moh") {
                    array_push($_sound,array($sound['secao'] => $sound['arquivo']));
                } else {
                    $_sound[$sound['language']] = $sound['arquivo'];
                }
            } else {
                self::remove($sound['arquivo'],$sound['secao']) ;
            }
        }

        // MOH - Read moh directory and mount table registrys
        // Mount directory list
        if ($type === 'moh') {
            $scanned_directory = array_diff(scandir($this->base_dir), array('..', '.', 'backup', 'tmp'));
            $sections = array("default" => $this->base_dir) ;
            foreach ($scanned_directory as $key => $value) {
                if (is_dir($this->base_dir . '/' . $value)) {
                    $sections[$value] = $this->base_dir.'/'.$value ;
                    continue ;
                }
            }

            // Read each directory and your files
            foreach ($sections as $key => $directory) {
                $scanned_directory = array_diff(scandir($directory), array('..', '.', 'backup', 'tmp'));
                foreach ($scanned_directory as $dir_key => $file) {
                    $file_found = $directory . '/' . $file;
                    if (is_file($file_found)) {
                        $flag_found = false ;
                        foreach ($_sound as $sound_key => $sound_value) {
                            if (isset($sound_value[$key]) && $sound_value[$key] === $file) {
                                $flag_found = true ;
                            }
                        }
                        // Add file in database
                        if (!$flag_found) {
                            $newfile = array('arquivo' => $file,
                                            'descricao' => $file,
                                            'data' => new Zend_Db_Expr('NOW()'),
                                            'language' => $this->lang,
                                            'tipo' => 'MOH',
                                            'secao' => $key);
                            self::addClassFile($newfile) ;
                        }
                    }

                }
            }
        } // end type = moh
        return true;
    }

    /**
     * Add a sound file of direcoty in syncronize
     * @param array $file
     */
    public function addSounds($file) {

        $db = Zend_Registry::get('db');
        $this->lang = Zend_Registry::get('config')->system->language;

        $insert_data = array('arquivo' => $file,
            'descricao' => substr($file, 0, -4),
            'data' => new Zend_Db_Expr('NOW()'),
            'language' => $this->lang,
            'tipo' => 'AST');

        try {
            $db->insert('sounds', $insert_data);
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return $e;
        }
    }


    /**
     * Get sound files
     * @param boolean $flag - true = include a null sound in array, false=not include
     * @return <array>
     */
    public function getSounds($flag=false) {

        $db = Zend_Registry::get('db');
        $this->lang = Zend_Registry::get('config')->system->language;

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.tipo = ?", 'AST')
                ->where("sounds.language = ?", $this->lang);

        $stmt = $db->query($select);
        $sounds = $stmt->fetchall();
        if ($flag) {
            array_unshift($sounds, array ( "arquivo" => "",
                "descricao" => "" ,
                "data" => "",
                "tipo" => "" ,
                "secao" => "",
                "language" => ""));
        }

        return $sounds;
    }


    // --------------------------------------------------------------------------------------------
    // Generic functions for Sound Files and MOH
    // --------------------------------------------------------------------------------------------
    /**
     * Verify file of sound in directories
     * @param <string> $name
     * @param <boolean> $full
     * @return <array> $result
     */
    public function verifySoundFiles($name, $full = false) {

        $sound_path = Zend_Registry::get('config')->system->path->asterisk->sounds;
        $web_path = Zend_Registry::get('config')->system->path->web;
        $this->lang = Zend_Registry::get('config')->system->language;
        $lang = $this->lang ;

        $result = array();

        if (file_exists($sound_path)) {
            if (file_exists($sound_path . '/' . $lang . '/'. $name)) {
                if ($full) {
                    $result['fullpath'] = $sound_path . '/' . $lang . '/'. $name;
                } else {
                    $result['fullpath'] = $web_path . '/sounds/' . $this->lang . '/'. $name;;
                }
            }
            if (file_exists($sound_path . '/' . $this->lang . '/backup/' . $name)) {
                if ($full) {
                    $result['backuppath'] = $sound_path . '/' . $lang . '/backup/' . $name;
                } else {
                    $result['backuppath'] = $web_path . '/sounds/' . $this->lang . '/backup/'. $name; ;
                }
            }
        }
        return $result;
    }

    /**
     * Verify file type
     * @param string
     * @return booolean
     */
    public function checkType($extension) {
        if(strtolower($extension) != "wav"  && strtolower($extension) != "gsm") {
            return false ;
         } else {
            return true ;
        }
    }

    /**
     * Parsing name file
     * @param <string> $name
     * @return <string> $name
     */
    public function parseName($name) {

        $invalid = array('â', 'ã', 'á', 'à', 'ẽ', 'é', 'è', 'ê', 'í', 'ì', 'ó', 'õ', 'ò', 'ú', 'ù', 'ç', " ", '@', '!');
        $valid = array('a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'o', 'u', 'u', 'c', "_", '_', '_');

        return str_replace($invalid, $valid, $name);
    }

    /**
     * Converter megabytes in bytes
     * @param <string> $size
     */
    function converter($size) {
        $bytes = "1048576";
        $result = $bytes * $size;
        return $result;
    }

}
