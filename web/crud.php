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
$BASE_INCLUDES = "";
$TABLENAME = array();
for($i = 0 ; $i < count($tables) ; $i++)
{
    $TABLENAME[] = $tables[$i];
}

function url(){
  return sprintf(
    "%s://%s",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME']
  );
}

chdir('../');

exec('php console generate:admin ' . $_POST['db_name'] . ' ' . implode(" ", $TABLENAME));

header("Location: ".url()."/cruds.php");


