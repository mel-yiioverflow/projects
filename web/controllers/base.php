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


require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';


require_once __DIR__.'/article/index.php';
require_once __DIR__.'/article_attachment/index.php';
require_once __DIR__.'/article_category/index.php';
require_once __DIR__.'/assets/index.php';
require_once __DIR__.'/attendance/index.php';
require_once __DIR__.'/city/index.php';
require_once __DIR__.'/contacts/index.php';
require_once __DIR__.'/country/index.php';
require_once __DIR__.'/employee_employer/index.php';
require_once __DIR__.'/employee_profile/index.php';
require_once __DIR__.'/employee_qualification/index.php';
require_once __DIR__.'/employee_salary/index.php';
require_once __DIR__.'/employee_status/index.php';
require_once __DIR__.'/expense/index.php';
require_once __DIR__.'/expense_type/index.php';
require_once __DIR__.'/file_storage_item/index.php';
require_once __DIR__.'/i18n_source_message/index.php';
require_once __DIR__.'/key_storage_item/index.php';
require_once __DIR__.'/page/index.php';
require_once __DIR__.'/qualification/index.php';
require_once __DIR__.'/rbac_auth_item/index.php';
require_once __DIR__.'/rbac_auth_rule/index.php';
require_once __DIR__.'/state/index.php';
require_once __DIR__.'/system_db_migration/index.php';
require_once __DIR__.'/system_log/index.php';
require_once __DIR__.'/system_rbac_migration/index.php';
require_once __DIR__.'/timeline_event/index.php';
require_once __DIR__.'/user/index.php';
require_once __DIR__.'/user_profile/index.php';
require_once __DIR__.'/user_token/index.php';
require_once __DIR__.'/widget_carousel/index.php';
require_once __DIR__.'/widget_carousel_item/index.php';
require_once __DIR__.'/widget_menu/index.php';
require_once __DIR__.'/widget_text/index.php';



$app->match('/', function () use ($app) {

    return $app['twig']->render('ag_dashboard.html.twig', array());
        
})
->bind('dashboard');


$app->run();