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

$app->match('/file_storage_item/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'component', 
		'base_url', 
		'path', 
		'type', 
		'size', 
		'name', 
		'upload_ip', 
		'created_at', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(255)', 
		'varchar(1024)', 
		'varchar(1024)', 
		'varchar(255)', 
		'int(11)', 
		'varchar(255)', 
		'varchar(15)', 
		'int(11)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `file_storage_item`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `file_storage_item`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/file_storage_item/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . file_storage_item . " WHERE ".$idfldname." = ?";
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



$app->match('/file_storage_item', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'component', 
		'base_url', 
		'path', 
		'type', 
		'size', 
		'name', 
		'upload_ip', 
		'created_at', 

    );

    $primary_key = "id";	

    return $app['twig']->render('file_storage_item/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('file_storage_item_list');



$app->match('/file_storage_item/create', function () use ($app) {
    
    $initial_data = array(
		'component' => '', 
		'base_url' => '', 
		'path' => '', 
		'type' => '', 
		'size' => '', 
		'name' => '', 
		'upload_ip' => '', 
		'created_at' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('component', 'text', array('required' => true));
	$form = $form->add('base_url', 'text', array('required' => true));
	$form = $form->add('path', 'text', array('required' => true));
	$form = $form->add('type', 'text', array('required' => false));
	$form = $form->add('size', 'text', array('required' => false));
	$form = $form->add('name', 'text', array('required' => false));
	$form = $form->add('upload_ip', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `file_storage_item` (`component`, `base_url`, `path`, `type`, `size`, `name`, `upload_ip`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['component'], $data['base_url'], $data['path'], $data['type'], $data['size'], $data['name'], $data['upload_ip'], 1490680861));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'file_storage_item created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('file_storage_item_list'));

        }
    }

    return $app['twig']->render('file_storage_item/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('file_storage_item_create');



$app->match('/file_storage_item/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `file_storage_item` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('file_storage_item_list'));
    }

    
    $initial_data = array(
		'component' => $row_sql['component'], 
		'base_url' => $row_sql['base_url'], 
		'path' => $row_sql['path'], 
		'type' => $row_sql['type'], 
		'size' => $row_sql['size'], 
		'name' => $row_sql['name'], 
		'upload_ip' => $row_sql['upload_ip'], 
		'created_at' => $row_sql['created_at'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('component', 'text', array('required' => true));
	$form = $form->add('base_url', 'text', array('required' => true));
	$form = $form->add('path', 'text', array('required' => true));
	$form = $form->add('type', 'text', array('required' => false));
	$form = $form->add('size', 'text', array('required' => false));
	$form = $form->add('name', 'text', array('required' => false));
	$form = $form->add('upload_ip', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `file_storage_item` SET `component` = ?, `base_url` = ?, `path` = ?, `type` = ?, `size` = ?, `name` = ?, `upload_ip` = ?, `created_at` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['component'], $data['base_url'], $data['path'], $data['type'], $data['size'], $data['name'], $data['upload_ip'], $data['created_at'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'file_storage_item edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('file_storage_item_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('file_storage_item/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('file_storage_item_edit');



$app->match('/file_storage_item/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `file_storage_item` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `file_storage_item` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'file_storage_item deleted!',
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

    return $app->redirect($app['url_generator']->generate('file_storage_item_list'));

})
->bind('file_storage_item_delete');






