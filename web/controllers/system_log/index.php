<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/system_log/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'id', 
		'level', 
		'category', 
		'log_time', 
		'prefix', 
		'message', 

    );
    
    $table_columns_type = array(
		'bigint(20)', 
		'int(11)', 
		'varchar(255)', 
		'double', 
		'text', 
		'text', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `system_log`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `system_log`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){
            if($table_columns[$i] == 'created_at' || $table_columns[$i] == 'updated_at')
            {
                $row_sql[$table_columns[$i]] = date('d/m/Y',$row_sql[$table_columns[$i]]);
            }
		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
		}

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/system_log/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . system_log . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/system_log', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'level', 
		'category', 
		'log_time', 
		'prefix', 
		'message', 

    );

    $primary_key = "id";	

    return $app['twig']->render('system_log/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('system_log_list');



$app->match('/system_log/create', function () use ($app) {
    
    $initial_data = array(
		'level' => '', 
		'category' => '', 
		'log_time' => '', 
		'prefix' => '', 
		'message' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('level', 'text', array('required' => false));
	$form = $form->add('category', 'text', array('required' => false));
	$form = $form->add('log_time', 'text', array('required' => false));
	$form = $form->add('prefix', 'textarea', array('required' => false));
	$form = $form->add('message', 'textarea', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `system_log` (`level`, `category`, `log_time`, `prefix`, `message`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['level'], $data['category'], $data['log_time'], $data['prefix'], $data['message']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'system_log created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('system_log_list'));

        }
    }

    return $app['twig']->render('system_log/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('system_log_create');



$app->match('/system_log/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `system_log` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('system_log_list'));
    }

    
    $initial_data = array(
		'level' => $row_sql['level'], 
		'category' => $row_sql['category'], 
		'log_time' => $row_sql['log_time'], 
		'prefix' => $row_sql['prefix'], 
		'message' => $row_sql['message'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('level', 'text', array('required' => false));
	$form = $form->add('category', 'text', array('required' => false));
	$form = $form->add('log_time', 'text', array('required' => false));
	$form = $form->add('prefix', 'textarea', array('required' => false));
	$form = $form->add('message', 'textarea', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `system_log` SET `level` = ?, `category` = ?, `log_time` = ?, `prefix` = ?, `message` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['level'], $data['category'], $data['log_time'], $data['prefix'], $data['message'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'system_log edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('system_log_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('system_log/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('system_log_edit');



$app->match('/system_log/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `system_log` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `system_log` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'system_log deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('system_log_list'));

})
->bind('system_log_delete');






