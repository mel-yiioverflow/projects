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

$app->match('/widget_menu/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'key', 
		'title', 
		'items', 
		'status', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(32)', 
		'varchar(255)', 
		'text', 
		'smallint(6)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `widget_menu`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `widget_menu`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/widget_menu/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . widget_menu . " WHERE ".$idfldname." = ?";
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



$app->match('/widget_menu', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'key', 
		'title', 
		'items', 
		'status', 

    );

    $primary_key = "id";	

    return $app['twig']->render('widget_menu/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('widget_menu_list');



$app->match('/widget_menu/create', function () use ($app) {
    
    $initial_data = array(
		'key' => '', 
		'title' => '', 
		'items' => '', 
		'status' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('key', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => true));
	$form = $form->add('items', 'textarea', array('required' => true));
	$form = $form->add('status', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `widget_menu` (`key`, `title`, `items`, `status`) VALUES (?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['key'], $data['title'], $data['items'], $data['status']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'widget_menu created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('widget_menu_list'));

        }
    }

    return $app['twig']->render('widget_menu/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('widget_menu_create');



$app->match('/widget_menu/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `widget_menu` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('widget_menu_list'));
    }

    
    $initial_data = array(
		'key' => $row_sql['key'], 
		'title' => $row_sql['title'], 
		'items' => $row_sql['items'], 
		'status' => $row_sql['status'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('key', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => true));
	$form = $form->add('items', 'textarea', array('required' => true));
	$form = $form->add('status', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `widget_menu` SET `key` = ?, `title` = ?, `items` = ?, `status` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['key'], $data['title'], $data['items'], $data['status'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'widget_menu edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('widget_menu_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('widget_menu/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('widget_menu_edit');



$app->match('/widget_menu/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `widget_menu` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `widget_menu` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'widget_menu deleted!',
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

    return $app->redirect($app['url_generator']->generate('widget_menu_list'));

})
->bind('widget_menu_delete');






