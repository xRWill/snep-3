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
 * Class contain service functions
 *
 * @see Snep_Reports
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 Opens Tecnologia
 * @author    Opens Tecnologia <desenvolvimentol@opens.com.br>
 *
 */
class Snep_Services {

    public function __construct() {

    }

    /**
     * getPathService - Method set servuce url
     * @param <string> $service
     * @return <string> $url
     */
    public function getPathService($service){

        $config = Zend_Registry::get('config');

        if($_SERVER["SERVER_ADDR"] == '::1'){
          $server = "localhost";
        }else{
          $server = $_SERVER["SERVER_ADDR"];
        }

        $url = "http://".$server.":".$_SERVER["SERVER_PORT"]."/snep/modules/default/api/?service=".$service;
        //$url = '127.0.0.1/snep/modules/default/api/?service='.$service;

        return $url;
    }

}
