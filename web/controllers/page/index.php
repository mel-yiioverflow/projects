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

$app->match('/page/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'slug', 
		'title', 
		'body', 
		'view', 
		'status', 
		'created_at', 
		'updated_at', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(2048)', 
		'varchar(512)', 
		'text', 
		'varchar(255)', 
		'smallint(6)', 
		'int(11)', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `page`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `page`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/page/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . page . " WHERE ".$idfldname." = ?";
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



$app->match('/page', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'slug', 
		'title', 
		'body', 
		'view', 
		'status', 
		'created_at', 
		'updated_at', 

    );

    $primary_key = "id";	

    return $app['twig']->render('page/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('page_list');



$app->match('/page/create', function () use ($app) {
    
    $initial_data = array(
		'slug' => '', 
		'title' => '', 
		'body' => '', 
		'view' => '', 
		'status' => '', 
		'created_at' => '', 
		'updated_at' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('slug', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => true));
	$form = $form->add('body', 'textarea', array('required' => true));
	$form = $form->add('view', 'text', array('required' => false));
	$form = $form->add('status', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `page` (`slug`, `title`, `body`, `view`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['slug'], $data['title'], $data['body'], $data['view'], $data['status'], 1490204423, 1490204423));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'page created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('page_list'));

        }
    }

    return $app['twig']->render('page/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('page_create');



$app->match('/page/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `page` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('page_list'));
    }

    
    $initial_data = array(
		'slug' => $row_sql['slug'], 
		'title' => $row_sql['title'], 
		'body' => $row_sql['body'], 
		'view' => $row_sql['view'], 
		'status' => $row_sql['status'], 
		'created_at' => $row_sql['created_at'], 
		'updated_at' => $row_sql['updated_at'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('slug', 'text', array('required' => true));
	$form = $form->add('title', 'text', array('required' => true));
	$form = $form->add('body', 'textarea', array('required' => true));
	$form = $form->add('view', 'text', array('required' => false));
	$form = $form->add('status', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `page` SET `slug` = ?, `title` = ?, `body` = ?, `view` = ?, `status` = ?, `created_at` = ?, `updated_at` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['slug'], $data['title'], $data['body'], $data['view'], $data['status'], $data['created_at'], 1490204423, $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'page edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('page_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('page/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('page_edit');



$app->match('/page/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `page` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `page` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'page deleted!',
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

    return $app->redirect($app['url_generator']->generate('page_list'));

})
->bind('page_delete');






