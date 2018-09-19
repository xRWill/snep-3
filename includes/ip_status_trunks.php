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


require_once "AMI.php";
$ami = new AMI ();

// Connect to database
$setup = parse_ini_file("setup.conf");
$res = "mysql:host=".$setup['db.host'].";dbname=".$setup['db.dbname'];

try {
    $conn = new PDO($res,$setup["db.username"],$setup["db.password"]);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}


// Get Trunks Sip from database - table: trunks
$like = 'SIP%';
$data = $conn->query("SELECT id,channel,callerid,host,username,type, disabled from trunks where channel LIKE '".$like."'");

// Popula troncos com itens faltantes do array
$troncos = array();
foreach ($data as $tronco) {
    array_push($troncos, $tronco);
}

foreach ($troncos as $key => $val) {    
    $ami = new AMI ();
    $troncos[$key]['status'] = "N.D.";
    $troncos[$key]['latencia'] = "N.D.";
    
    //Get information about peer
    $sip_username = $val['type'] === "VIRTUAL" ? substr($val['channel'], 4): $val['username'];
    $res_peer = $ami->get_sippeer($sip_username);
    if ($res_peer) {
        if (strripos($res_peer['status'], "ok") > 0 ) {
           $troncos[$key]['latencia'] = $res_peer['status'];
       } else {
           $troncos[$key]['latencia'] = ucwords($res_peer['status']);
       }
    }
    // Get information about registry
    if ($val['type'] === "VIRTUAL") {
        $troncos[$key]['host'] = $res_peer['tohost'] ;
    }
    $res_reg = $ami->get_SIPshowregistry($troncos[$key]['host'],$sip_username);
    if ($res_reg) {
        $troncos[$key]['status'] = $res_reg['state'];
    }
}

foreach ($troncos as $key => $val) {
    unset($troncos[$key]['channel']);
    unset($troncos[$key]['username']);
    if (trim($troncos[$key]['latencia']) === "") {
        $troncos[$key]['latencia'] = "Unreachable";
    }
    if (trim($troncos[$key]['status']) === "") {
        $troncos[$key]['status'] = "N.D.";
    }
}
$troncos_sip = $troncos ;

/* -------------------------------------------------------------------------- */

// Get Trunks IAX from database - table: trunks
$like = 'IAX%';
$data = $conn->query("SELECT id,channel,callerid,host,username,type from trunks where channel LIKE '".$like."'");

// Popula troncos com itens faltantes do array
$troncos = array();
foreach ($data as $tronco) {
    array_push($troncos, $tronco);
}

$peer_list = $ami->get_IAXpeerlist() ;
$reg_list = $ami->get_IAXregistry();

foreach ($troncos as $key => $val) {
    $troncos[$key]['status'] = "N.D.";
    $troncos[$key]['latencia'] = "N.D.";

    // Get information about peer
    $iax_username = $val['type'] === "VIRTUAL" ? substr($val['channel'], 5): $val['username'];
    foreach ($peer_list as $pl_key => $pl_val) {
        if ($pl_val['objectname'] === $iax_username) {
            $troncos[$key]['latencia'] = $pl_val['status'];
        }
    }

    //Get information about registry
    
    if ($val['type'] === "VIRTUAL") {
       $troncos[$key]['host'] = $res_peer['ipaddress'] ;
    }
    $res_reg = $ami->get_IAXregistry($troncos[$key]['host'],$iax_username);
    if ($res_reg) {
        $troncos[$key]['status'] = $res_reg['state'];
    }
}

foreach ($troncos as $key => $val) {
    unset($troncos[$key]['channel']);
    unset($troncos[$key]['username']);
    if ($troncos[$key]['latencia'] === "") {
        $troncos[$key]['latencia'] = "Unreachable";
    }
    if (trim($troncos[$key]['status']) === "") {
        $troncos[$key]['status'] = "N.D.";
    }
}

$troncos_iax = $troncos ;

$troncos = array_merge($troncos_sip,$troncos_iax) ;

$out = array_values($troncos);
$ret = json_encode($out);
echo $ret ;
