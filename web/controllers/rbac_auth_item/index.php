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

$app->match('/rbac_auth_item/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'name', 
		'type', 
		'description', 
		'rule_name', 
		'data', 
		'created_at', 
		'updated_at', 

    );
    
    $table_columns_type = array(
		'varchar(64)', 
		'int(11)', 
		'text', 
		'varchar(64)', 
		'text', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `rbac_auth_item`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `rbac_auth_item`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/rbac_auth_item/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . rbac_auth_item . " WHERE ".$idfldname." = ?";
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



$app->match('/rbac_auth_item', function () use ($app) {
    
	$table_columns = array(
		'name', 
		'type', 
		'description', 
		'rule_name', 
		'data', 
		'created_at', 
		'updated_at', 

    );

    $primary_key = "name";	

    return $app['twig']->render('rbac_auth_item/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('rbac_auth_item_list');



$app->match('/rbac_auth_item/create', function () use ($app) {
    
    $initial_data = array(
		'name' => '', 
		'type' => '', 
		'description' => '', 
		'rule_name' => '', 
		'data' => '', 
		'created_at' => '', 
		'updated_at' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('name', 'text', array('required' => true));
	$form = $form->add('type', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => false));
	$form = $form->add('rule_name', 'text', array('required' => false));
	$form = $form->add('data', 'textarea', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `rbac_auth_item` (`name`, `type`, `description`, `rule_name`, `data`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['name'], $data['type'], $data['description'], $data['rule_name'], $data['data'], 1490609833, 1490609833));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'rbac_auth_item created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('rbac_auth_item_list'));

        }
    }

    return $app['twig']->render('rbac_auth_item/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('rbac_auth_item_create');



$app->match('/rbac_auth_item/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `rbac_auth_item` WHERE `name` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('rbac_auth_item_list'));
    }

    
    $initial_data = array(
		'name' => $row_sql['name'], 
		'type' => $row_sql['type'], 
		'description' => $row_sql['description'], 
		'rule_name' => $row_sql['rule_name'], 
		'data' => $row_sql['data'], 
		'created_at' => $row_sql['created_at'], 
		'updated_at' => $row_sql['updated_at'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('name', 'text', array('required' => true));
	$form = $form->add('type', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => false));
	$form = $form->add('rule_name', 'text', array('required' => false));
	$form = $form->add('data', 'textarea', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `rbac_auth_item` SET `name` = ?, `type` = ?, `description` = ?, `rule_name` = ?, `data` = ?, `created_at` = ?, `updated_at` = ? WHERE `name` = ?";
            $app['db']->executeUpdate($update_query, array($data['name'], $data['type'], $data['description'], $data['rule_name'], $data['data'], $data['created_at'], 1490609833, $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'rbac_auth_item edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('rbac_auth_item_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('rbac_auth_item/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('rbac_auth_item_edit');



$app->match('/rbac_auth_item/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `rbac_auth_item` WHERE `name` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `rbac_auth_item` WHERE `name` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'rbac_auth_item deleted!',
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

    return $app->redirect($app['url_generator']->generate('rbac_auth_item_list'));

})
->bind('rbac_auth_item_delete');






