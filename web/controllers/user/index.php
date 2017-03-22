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

$app->match('/user/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'username', 
		'auth_key', 
		'access_token', 
		'password_hash', 
		'oauth_client', 
		'oauth_client_user_id', 
		'email', 
		'status', 
		'created_at', 
		'updated_at', 
		'logged_at', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(32)', 
		'varchar(32)', 
		'varchar(40)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'smallint(6)', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `user`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `user`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/user/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . user . " WHERE ".$idfldname." = ?";
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



$app->match('/user', function () use ($app) {
    
	$table_columns = array(
		'id', 
		'username', 
		'auth_key', 
		'access_token', 
		'password_hash', 
		'oauth_client', 
		'oauth_client_user_id', 
		'email', 
		'status', 
		'created_at', 
		'updated_at', 
		'logged_at', 

    );

    $primary_key = "id";	

    return $app['twig']->render('user/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('user_list');



$app->match('/user/create', function () use ($app) {
    
    $initial_data = array(
		'username' => '', 
		'auth_key' => '', 
		'access_token' => '', 
		'password_hash' => '', 
		'oauth_client' => '', 
		'oauth_client_user_id' => '', 
		'email' => '', 
		'status' => '', 
		'created_at' => '', 
		'updated_at' => '', 
		'logged_at' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('username', 'text', array('required' => false));
	$form = $form->add('auth_key', 'text', array('required' => true));
	$form = $form->add('access_token', 'text', array('required' => true));
	$form = $form->add('password_hash', 'text', array('required' => true));
	$form = $form->add('oauth_client', 'text', array('required' => false));
	$form = $form->add('oauth_client_user_id', 'text', array('required' => false));
	$form = $form->add('email', 'text', array('required' => true));
	$form = $form->add('status', 'text', array('required' => true));
	$form = $form->add('logged_at', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `user` (`username`, `auth_key`, `access_token`, `password_hash`, `oauth_client`, `oauth_client_user_id`, `email`, `status`, `created_at`, `updated_at`, `logged_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['username'], $data['auth_key'], $data['access_token'], $data['password_hash'], $data['oauth_client'], $data['oauth_client_user_id'], $data['email'], $data['status'], 1490178417, 1490178417, $data['logged_at']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_list'));

        }
    }

    return $app['twig']->render('user/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('user_create');



$app->match('/user/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('user_list'));
    }

    
    $initial_data = array(
		'username' => $row_sql['username'], 
		'auth_key' => $row_sql['auth_key'], 
		'access_token' => $row_sql['access_token'], 
		'password_hash' => $row_sql['password_hash'], 
		'oauth_client' => $row_sql['oauth_client'], 
		'oauth_client_user_id' => $row_sql['oauth_client_user_id'], 
		'email' => $row_sql['email'], 
		'status' => $row_sql['status'], 
		'created_at' => $row_sql['created_at'], 
		'updated_at' => $row_sql['updated_at'], 
		'logged_at' => $row_sql['logged_at'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('username', 'text', array('required' => false));
	$form = $form->add('auth_key', 'text', array('required' => true));
	$form = $form->add('access_token', 'text', array('required' => true));
	$form = $form->add('password_hash', 'text', array('required' => true));
	$form = $form->add('oauth_client', 'text', array('required' => false));
	$form = $form->add('oauth_client_user_id', 'text', array('required' => false));
	$form = $form->add('email', 'text', array('required' => true));
	$form = $form->add('status', 'text', array('required' => true));
	$form = $form->add('logged_at', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `user` SET `username` = ?, `auth_key` = ?, `access_token` = ?, `password_hash` = ?, `oauth_client` = ?, `oauth_client_user_id` = ?, `email` = ?, `status` = ?, `created_at` = ?, `updated_at` = ?, `logged_at` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['username'], $data['auth_key'], $data['access_token'], $data['password_hash'], $data['oauth_client'], $data['oauth_client_user_id'], $data['email'], $data['status'], $data['created_at'], 1490178417, $data['logged_at'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('user/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('user_edit');



$app->match('/user/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `user` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'user deleted!',
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

    return $app->redirect($app['url_generator']->generate('user_list'));

})
->bind('user_delete');






