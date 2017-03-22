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

function url(){
  return sprintf(
    "%s://%s",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME']
  );
}

$replace = "//Begin Configuration
\$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
            'dbs.options' => array(
            'db' => array(
                    'driver'   => 'pdo_mysql',
                    'dbname'   => '".$_POST['db_name']."',
                    'host'     => '127.0.0.1',
                    'user'     => '".$_POST['user_name']."',
                    'password' => '".$_POST['password']."',
                    'charset'  => 'utf8',
            ),
    )
));

\$app['asset_path'] = '".url()."/resources';
//End Configuration";
$startPoint = '//Begin Configuration';
$endPoint = '//End Configuration';
$search = '#('.preg_quote($startPoint).')(.*)('.preg_quote($endPoint).')#si';

$_db_config = file_get_contents(__DIR__.'/../src/app.php');
$_db_config = preg_replace($search,$replace, $_db_config,1);

$fp = fopen(__DIR__."/../src/app.php", "w+");

fwrite($fp, $_db_config);
fclose($fp);
			



