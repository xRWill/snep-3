<?php
/**
 *  This file is part of SNEP.
 *  Para territ�rio Brasileiro leia LICENCA_BR.txt
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
 */

$id = $_GET['id'];
$file = $_GET['file'];

if(!isset($id) && !isset($file)){
	echo "{'status':'error','message':'Esperando id como argumento!'}";
	exit(1);
}elseif(isset($id)){
	$exp_data = preg_match("/^[0-9]+_([0-9]+)_/",$id,$data);
	$exp_data = $data[1];
	$pattern = '/(20[12][0-9])([0-9][0-9])([0-9][0-9])/';
	$replacement = '$1-$2-$3';

	//clicktocall
	if($data == ""){
		$data = substr($id,0,4) . "-".substr($id,4,2)."-".substr($id,6,2);
	}

	$data = preg_replace($pattern,$replacement,$exp_data);
	if(file_exists("$id.WAV")){
		header("Location: $id.WAV");
	}elseif(file_exists("$id.wav")){
		header("Location: $id.wav");
	}elseif(file_exists("storage1/$id.WAV")){
		header("Location: storage1/$id.WAV");
	}elseif(file_exists("storage1/$id.wav")){
		header("Location: storage1/$id.wav");
	}elseif(file_exists("$data/$id.WAV")){
		header("Location: $data/$id.WAV");
	}elseif(file_exists("$data/$id.wav")){
		header("Location: $data/$id.wav");
	}elseif(file_exists("storage1/$data/$id.WAV")){
		header("Location: storage1/$data/$id.WAV");
	}elseif(file_exists("storage1/$data/$id.wav")){
		header("Location: storage1/$data/$id.wav");
	}else{
		echo "{'status':'error','message':'File $id not found','date':'$data'}";
	}

}elseif(isset($file)){
	$exp_data = preg_match("/^[0-9]+_([0-9]+)_/",$file,$data);
	$exp_data = $data[1];
	$pattern = '/(20[12][0-9])([0-9][0-9])([0-9][0-9])/';
	$replacement = '$1-$2-$3';

	//clicktocall
	if($data == ""){
		$data = substr($id,0,4) . "-".substr($id,4,2)."-".substr($id,6,2);
	}
	
	$data = preg_replace($pattern,$replacement,$exp_data);
	if(file_exists("$file")){
		header("Location: $file");
	}elseif(file_exists("storage1/$file")){
		header("Location: storage1/$file");
	}elseif(file_exists("$data/$file")){
		header("Location: $data/$file");
	}elseif(file_exists("storage1/$data/$file")){
		header("Location: storage1/$data/$file");
	}else{
		echo "{'status':'error','message':'File $file not found','date':'$data'}";
	}

}
