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
 * Class contain report functions
 *
 * @see Snep_Reports
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2015 Opens Tecnologia
 * @author    Opens Tecnologia <desenvolvimentol@opens.com.br>
 * 
 */
class Snep_Reports {

    public function __construct() {
        
    }

    /**
     * createpages - Method get number page init/end
     * @param <int> $page
     * @param <int> $line_limit
     * @param <int> $cont
     * @return <array> $pagesvalue
     */
    public function createPages($page,$line_limit,$cont = null){
        // page = 1 e line_limit = 50;

        $numbpagenext = $page;
        $numbpageprev = $page;
        // verifica número da página da seleção anterior e posterior. Ex: page = 7, $pagenext = 11, pageprev = 4 
        
        if($numbpagenext == ceil($cont / $line_limit)){
            $numbpagenext = ceil($cont / $line_limit) -1;
        }else{
            while($numbpagenext % 5 != 0){
                $numbpagenext++;
            }
        }
        
        if($numbpageprev % 5 != 0){
            if($numbpageprev < 5){
                $numbpageprev = 1;
            }else{
                while($numbpageprev % 5 != 0){
                    $numbpageprev--;
                }
            }
        }else{
            $numbpageprev = $numbpageprev -5;
        }

        return $pagesValue = array("pagenext" => $numbpagenext+1, "pageprev" => $numbpageprev);

    }


    /**
     * fmt_date - Format date dd/mm/yyyy hh:mm for yyyy-mm-dd hh:mm:ss
     * @param <string> $initDay
     * @param <string> $endDay
     * @return <array> $date
     */
    public function fmt_date($initDay,$endDay){

        $init_day = explode(" ", $initDay);
        $final_day = explode(" ", $endDay);

        $formated_init_day = new Zend_Date($init_day[0]);
        $formated_init_day = $formated_init_day->toString('yyyy-MM-dd');
        $formated_init_time = $init_day[1];
        $formated_final_day = new Zend_Date($final_day[0]);
        $formated_final_day = $formated_final_day->toString('yyyy-MM-dd');
        $formated_final_time = $final_day[1];

        $date['start_date'] = "$formated_init_day";
        $date['end_date'] = "$formated_final_day";
        $date['start_hour'] = "$formated_init_time:00";
        $date['end_hour'] = "$formated_final_time:59";
        
        return $date;
            
    }

}