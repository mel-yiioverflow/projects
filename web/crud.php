<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$tables = array();
$checkedTables = array();
$checkedTables[] = $_POST['check_box'];
foreach ($checkedTables as $key => $cTables) {
    foreach ($cTables as $key => $tab) {
        $tables[] = $tab;
    }
}

