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
 * Faz o controle em banco dos Alias para expressões regulares.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author Henrique Grolli Bassotto
 */
class PBX_DatesAliases {

    private static $instance;

    protected function __construct() {

    }

    protected function __clone() {

    }

    /**
     * Retorna instancia dessa classe
     * @return PBX_ExpressionAliases
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * getAll - List expression Aliases
     * @return <array> $aliases
     */
    public function getAll() {
        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("date_alias"), array("id","name"));

        $stmt = $db->query($select);
        $dates = $stmt->fetchAll();

        return $dates;
    }

    /**
     * getAllList - List expression Aliases
     * @return <array> $aliases
     */
    public function getAllList() {
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("date_alias_list"));

        $stmt = $db->query($select);
        $dates = $stmt->fetchAll();

        return $dates;
    }

    /**
     * get
     * @param <int> $id
     * @return <array>
     * @throws PBX_Exception_BadArg
     */
    public function get($id) {
        if (!isset($id)) {
            throw new PBX_Exception_BadArg("Id must be numerical but it is: $id");
        }

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
     * add - add date alias
     * @param <array> $dates array(name => String Name, id => id, timerange => array(), date => array())
     * @throws Exception
     */
    public function add($dates) {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();
        $insert = $db->insert("date_alias", array("name" => $dates['name']));
        $id = $db->lastInsertId();

        foreach ($dates['date'] as $key => $value) {
          $data = array("date" => $value, "timerange" => $dates['timerange'][$key], "dateid" => $id);
          $db->insert("date_alias_list", $data);
        }

        try {
            $db->commit();
            return $id;
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * update - update date alias
     * @param <array> $dates array(name => String Name, id => id, timerange => array(), date => array())
     * @throws Exception
     */
    public function update($dates) {
        $id = $dates['id'];

        $db = Zend_Registry::get('db');
        $db->beginTransaction();
        $db->update("date_alias", array("name" => $dates['name']), "id='$id'");
        $db->delete("date_alias_list", "dateid=$id");

        foreach ($dates['date'] as $key => $value) {
          $data = array("date" => $value, "timerange" => $dates['timerange'][$key], "dateid" => $id);
          $db->insert("date_alias_list", $data);
        }

        try {
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * delete - Remove expression alias
     * @param <int> $id
     */
    public function delete($id) {
        $db = Zend_Registry::get('db');

        $db->delete("date_alias_list", "dateid=$id");
        $db->delete("date_alias", "id='$id'");

    }


    /**
      * getValidation - get route that are using this dates_alias
      * @param <string> $id
      */
    public function getValidation($id){
      $db = Zend_Registry::get("db");
      $select = $db->select()
                  ->from('regras_negocio')
                  ->where("FIND_IN_SET($id, regras_negocio.dates_alias)");
      return $db->query($select)->fetchAll();

    }

}
