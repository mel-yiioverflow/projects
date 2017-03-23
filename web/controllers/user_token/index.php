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

$app->match('/user_token/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'user_id', 
		'type', 
		'token', 
		'expire_at', 
		'created_at', 
		'updated_at', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'varchar(255)', 
		'varchar(40)', 
		'int(11)', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `user_token`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `user_token`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){
            if($table_columns[$i] == 'created_at' || $table_columns[$i] == 'updated_at')
            {
                $row_sql[$table_columns[$i]] = date('d/m/Y',$row_sql[$table_columns[$i]]);
            }
			if($table_columns[$i] == 'user_id'){
			    $findexternal_sql = 'SELECT `email` FROM `user` WHERE `id` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['email'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
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
$app->match('/user_token/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . user_token . " WHERE ".$idfldname." = ?";
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



$app->match('/user_token', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'user_id', 
		'type', 
		'token', 
		'expire_at', 
		'created_at', 
		'updated_at', 

    );

    $primary_key = "id";	

    return $app['twig']->render('user_token/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('user_token_list');



$app->match('/user_token/create', function () use ($app) {
    
    $initial_data = array(
		'user_id' => '', 
		'type' => '', 
		'token' => '', 
		'expire_at' => '', 
		'created_at' => '', 
		'updated_at' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `id`, `email` FROM `user`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['id']] = $findexternal_row['email'];
	}
	if(count($options) > 0){
	    $form = $form->add('user_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('user_id', 'text', array('required' => true));
	}



	$form = $form->add('type', 'text', array('required' => true));
	$form = $form->add('token', 'text', array('required' => true));
	$form = $form->add('expire_at', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `user_token` (`user_id`, `type`, `token`, `expire_at`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['user_id'], $data['type'], $data['token'], $data['expire_at'], 1490204423, 1490204423));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user_token created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_token_list'));

        }
    }

    return $app['twig']->render('user_token/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('user_token_create');



$app->match('/user_token/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user_token` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('user_token_list'));
    }

    
    $initial_data = array(
		'user_id' => $row_sql['user_id'], 
		'type' => $row_sql['type'], 
		'token' => $row_sql['token'], 
		'expire_at' => $row_sql['expire_at'], 
		'created_at' => $row_sql['created_at'], 
		'updated_at' => $row_sql['updated_at'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `id`, `email` FROM `user`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['id']] = $findexternal_row['email'];
	}
	if(count($options) > 0){
	    $form = $form->add('user_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('user_id', 'text', array('required' => true));
	}


	$form = $form->add('type', 'text', array('required' => true));
	$form = $form->add('token', 'text', array('required' => true));
	$form = $form->add('expire_at', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `user_token` SET `user_id` = ?, `type` = ?, `token` = ?, `expire_at` = ?, `created_at` = ?, `updated_at` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['user_id'], $data['type'], $data['token'], $data['expire_at'], $data['created_at'], 1490204423, $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user_token edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_token_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('user_token/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('user_token_edit');



$app->match('/user_token/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user_token` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `user_token` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'user_token deleted!',
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

    return $app->redirect($app['url_generator']->generate('user_token_list'));

})
->bind('user_token_delete');






