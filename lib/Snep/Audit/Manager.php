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
 * Class to manager a Users.
 *
 * @see Snep_Audit_Manager
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2018 OpenS Tecnologia
 * @author    Desenvolvimento <devops@opens.com.br>
 *
 */
class Snep_Audit_Manager
{

    public function __construct()
    {

    }

    /**
    * getAll - Get all logs by Category
    * @param <array> $initial_date, $final_date, $Category
    * @return <array>
    */
    public function getAll($initial_date, $final_date, $category){
      $db = Zend_Registry::get('db');
      $select = $db->select()->from('logs_users')
      ->where('datetime >= ?', $initial_date)
      ->where('datetime <= ?', $final_date);

      if($category != "all"){
        $select = $select->where('`table` = ?', $category);
      }
      $select = $select->order('datetime DESC');

      return $db->query($select)->fetchAll();

    }

    /**
     * saveLog - Inserts data in the database
     * @param <string> $acao
     * @param <string> $idAction
     * @param <int> $tipo
     * @return boolean
     */
    function saveLog($action, $table, $registerid, $description) {
      
      $db = Zend_Registry::get("db");

      $ip = $_SERVER['REMOTE_ADDR'];
      $auth = Zend_Auth::getInstance();
      $username = $auth->getIdentity();

      $insert_data = array(
          'datetime' => date('Y-m-d H:i:s'),
          'ip' => $ip,
          'user' => $username,
          'action' => $action,
          'description' => $description,
          'table' => $table,
          'registerid' => $registerid
        );

      $db->insert('logs_users', $insert_data);
    }

    /**
     * addLog - Inserts data in the database
     * @param <string> $acao
     * @param <string> $idAction
     * @param <int> $tipo
     * @return boolean
     */
    function addLog($action, $data) {
      
      $db = Zend_Registry::get("db");

      $ip = $_SERVER['REMOTE_ADDR'];
      $auth = Zend_Auth::getInstance();
      $username = $auth->getIdentity();

      if(!isset($data['table'])){
        $data['table'] = 'unknown';
      }

      if(!isset($data['registerid'])){
        $data['registerid'] = 'unknown';
      }

      if(!isset($data['description'])){
        $data['description'] = "Register {$data['registerid']} at table {$data['table']} was $action";
      }

      $insert_data = array(
          'datetime' => date('Y-m-d H:i:s'),
          'ip' => $ip,
          'user' => $username,
          'action' => $action,
          'description' => $data['description'],
          'table' => $data['table'],
          'registerid' => $data['registerid']
        );

      $db->insert('logs_users', $insert_data);
    }

}
