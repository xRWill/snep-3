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

require_once "AsteriskInfo.php";
require_once "functions.php";
require_once "AMI.php";

// Connect to database
$setup = parse_ini_file("setup.conf");
$res = "mysql:host=".$setup['db.host'].";dbname=".$setup['db.dbname'];

try {
    $conn = new PDO($res,$setup["db.username"],$setup["db.password"]);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}


// Get Extensions Sip from database - table: peers
$data = $conn->query("SELECT id,canal,callerid,name,peer_type,disabled from peers where peer_type = 'R'");

// Popula peers com itens faltantes do array
$peers = array();
foreach ($data as $peer) {
    array_push($peers, $peer);
}

foreach ($peers as $key => $val) {
    $ami = new AMI ();
    $peers[$key]['ip'] = "N.D.";
    $peers[$key]['latencia'] = "N.D.";
    
    //Get information about peer

    $res_peer = $ami->get_sippeer($val['name']);

    if ($res_peer) {
        $peers[$key]['latencia'] = isset($res_peer['status']) ? $res_peer['status'] : "N.D.";
        if (isset($res_peer['contact'])) {
            $sub = substr($res_peer['contact'], strpos($res_peer['contact'],"@")+strlen("@"),strlen($res_peer['contact']));
            $ip = substr($sub,0,strpos($sub,":"));
            if ( $ip === "" ) {
                $ip = substr($sub,0,strpos($sub,";"));
            }
            $peers[$key]['ip'] = $ip;
        }
    }
}

foreach ($peers as $key => $val) {

    if (trim($peers[$key]['ip']) === "") {
        $peers[$key]['ip'] = "N.D.";
    }
}
$peers_sip = $peers ;


$out = array_values($peers);
$ret = json_encode($out);
echo $ret ;
