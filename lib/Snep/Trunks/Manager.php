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
 * Class to manager a trunks.
 *
 * @see Snep_Trunks_Manager
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Tiago Zimmermann <tiago.zimmermann@opens.com.br>
 *
 */
class Snep_Trunks_Manager {

    public function __construct() {

    }

    /**
     * Method to get all trunks
     */
    public function getData() {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("trunks", array("id", "callerid"));

        $stmt = $db->query($select);
        $trunks = $stmt->fetchAll();

        return $trunks;
    }

    /**
     * getValidation - checks if the queue is used in the rule
     * @param <int> $id
     * @return <array>
     */
    public function getValidation($id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('regras_negocio', array('id', 'desc'))
                ->where("regras_negocio.origem LIKE ?", '%T:' . $id.'%')
                ->orwhere("regras_negocio.destino LIKE ?", '%T:' . $id.'%');

        $stmt = $db->query($select);
        $regras = $stmt->fetchall();

        return $regras;
    }

    /**
     * getId
     * @param <string> $name
     * @return <array>
     */
    public function getId($name) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('trunks', array('id'))
                ->where("trunks.callerid = ?", $name);

        $stmt = $db->query($select);
        $id = $stmt->fetch();

        return $id;
    }

    /**
     * get trunk by id
     * @param <string> $id
     * @return <array>
     */
    public function get($id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('trunks')
                ->where("trunks.id = ?", $id);

        $stmt = $db->query($select);
        $id = $stmt->fetch();

        return $id;
    }


    /**
     * getRules - checks if the queue is used in the rule
     * @param <int> $id
     * @return <array>
     */
    public function getRules($id) {

        $db = Zend_Registry::get('db');

        $rules_query = "SELECT rule.id, rule.desc FROM regras_negocio as rule, regras_negocio_actions_config as rconf WHERE (rconf.regra_id = rule.id AND rconf.value = '$id' AND (rconf.key = 'tronco' OR rconf.key = 'trunk'))";
        $regras = $db->query($rules_query)->fetchAll();

        return $regras;
    }

    /**
     * Remove a trunk
     * @param <int> $id
     */
    public function remove($id) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('trunks', "id = '$id'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * Remove a trunk
     * @param <int> $id
     */
    public function removePeers($name) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('peers', "name = '$name'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * getTrunk - set array widh data of trunk
     * @param <int> $id - Trunk code
     * @return <array> $tronco - Data of trunk
     */
    function getTrunkLog($id) {

        $tronco = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT  from  trunks where id='$id'";
        $select = $db->select()
                ->from('trunks', array('id', 'name', 'callerid', 'dtmfmode', 'insecure', 'username', 'allow', 'type', 'host', 'map_extensions', 'reverse_auth', 'domain'))
                ->where('trunks.id = ?', $id);

        $stmt = $db->query($select);
        $tronco = $stmt->fetch();

        if ($tronco["type"] != "KHOMP" && $tronco["type"] != "VIRTUAL") {

            $name = $tronco["name"];

            $select = $db->select()
                    ->from('peers', array('fromuser', 'fromdomain', 'nat', 'port', 'qualify', 'type as type_peer', `call-limit as call_limit`))
                    ->where('peers.name = ?', $name);
            $stmt = $db->query($select);
            $peer = $stmt->fetch();

            foreach ($peer as $item => $info) {
                $tronco[$item] = $info;
            }
        }

        return $tronco;
    }
    
    /**
     * Method to get trunks by name
     * @param <string> $id
     * @return Array
     */
    public function getName($name) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from("trunks", array("id", "callerid"))
                ->where("trunks.callerid = ?", $name);

        $stmt = $db->query($select);
        $trunk = $stmt->fetch();

        return $trunk;
    }

    /**
     * enable - enable trunk
     * @param <int> $id
     */
    public function enable($id) {

        $db = Zend_Registry::get('db');

        $update_data = array('disabled' => false );
        $db->update("trunks", $update_data, "id = '$id'");

    }

}
