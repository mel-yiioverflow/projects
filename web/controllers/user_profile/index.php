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

$app->match('/user_profile/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'user_id', 
		'firstname', 
		'middlename', 
		'lastname', 
		'avatar_path', 
		'avatar_base_url', 
		'locale', 
		'gender', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(32)', 
		'smallint(1)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `user_profile`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `user_profile`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/user_profile/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . user_profile . " WHERE ".$idfldname." = ?";
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



$app->match('/user_profile', function () use ($app) {
    
	$table_columns = array(
		'user_id', 
		'firstname', 
		'middlename', 
		'lastname', 
		'avatar_path', 
		'avatar_base_url', 
		'locale', 
		'gender', 

    );

    $primary_key = "user_id";	

    return $app['twig']->render('user_profile/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('user_profile_list');



$app->match('/user_profile/create', function () use ($app) {
    
    $initial_data = array(
		'firstname' => '', 
		'middlename' => '', 
		'lastname' => '', 
		'avatar_path' => '', 
		'avatar_base_url' => '', 
		'locale' => '', 
		'gender' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('firstname', 'text', array('required' => false));
	$form = $form->add('middlename', 'text', array('required' => false));
	$form = $form->add('lastname', 'text', array('required' => false));
	$form = $form->add('avatar_path', 'text', array('required' => false));
	$form = $form->add('avatar_base_url', 'text', array('required' => false));
	$form = $form->add('locale', 'text', array('required' => true));
	$form = $form->add('gender', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `user_profile` (`firstname`, `middlename`, `lastname`, `avatar_path`, `avatar_base_url`, `locale`, `gender`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['firstname'], $data['middlename'], $data['lastname'], $data['avatar_path'], $data['avatar_base_url'], $data['locale'], $data['gender']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user_profile created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_profile_list'));

        }
    }

    return $app['twig']->render('user_profile/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('user_profile_create');



$app->match('/user_profile/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user_profile` WHERE `user_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('user_profile_list'));
    }

    
    $initial_data = array(
		'firstname' => $row_sql['firstname'], 
		'middlename' => $row_sql['middlename'], 
		'lastname' => $row_sql['lastname'], 
		'avatar_path' => $row_sql['avatar_path'], 
		'avatar_base_url' => $row_sql['avatar_base_url'], 
		'locale' => $row_sql['locale'], 
		'gender' => $row_sql['gender'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('firstname', 'text', array('required' => false));
	$form = $form->add('middlename', 'text', array('required' => false));
	$form = $form->add('lastname', 'text', array('required' => false));
	$form = $form->add('avatar_path', 'text', array('required' => false));
	$form = $form->add('avatar_base_url', 'text', array('required' => false));
	$form = $form->add('locale', 'text', array('required' => true));
	$form = $form->add('gender', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `user_profile` SET `firstname` = ?, `middlename` = ?, `lastname` = ?, `avatar_path` = ?, `avatar_base_url` = ?, `locale` = ?, `gender` = ? WHERE `user_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['firstname'], $data['middlename'], $data['lastname'], $data['avatar_path'], $data['avatar_base_url'], $data['locale'], $data['gender'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user_profile edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_profile_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('user_profile/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('user_profile_edit');



$app->match('/user_profile/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user_profile` WHERE `user_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `user_profile` WHERE `user_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'user_profile deleted!',
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

    return $app->redirect($app['url_generator']->generate('user_profile_list'));

})
->bind('user_profile_delete');






