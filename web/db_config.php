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


$dbTables = array();

$mysqli = new mysqli('127.0.0.1', $_POST['user_name'], $_POST['password'], $_POST['db_name']);

$getTablesQuery = "SHOW TABLES";
$result = $mysqli->query($getTablesQuery);

while($getTablesResult = $result->fetch_row())
        {
            $rows[] = $getTablesResult;
            
        }        
        foreach ($rows as $key => $r1)
        {
            foreach ($r1 as $key => $r2)
            {
                $dbTables[] = $r2;
            }
            
        
        }
        
$result->free();
$mysqli->close();
?>

<!-- bootstrap 3.0.2 -->
<link href="/resources/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<!-- font Awesome -->
<link href="/resources/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<!-- Ionicons -->
<link href="/resources/css/ionicons.min.css" rel="stylesheet" type="text/css" />
<!-- Morris chart -->
<link href="/resources/css/morris/morris.css" rel="stylesheet" type="text/css" />
<!-- jvectormap -->
<link href="/resources/css/jvectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />
<!-- DATA TABLES -->
<link href="/resources/css/datatables/dataTables.bootstrap.css" rel="stylesheet" type="text/css" />    
<!-- bootstrap wysihtml5 - text editor -->
<link href="/resources/css/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css" rel="stylesheet" type="text/css" />
<!-- Theme style -->
<link href="/resources/css/AdminLTE.css" rel="stylesheet" type="text/css" />

<div class="row">
    <div class="row" style="height: 200px;"></div>
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="row table table-bordered" style="border-spacing: 10px; border-color: #0073b7; background-color: #9cc2cb;">
                    <form action="crud.php" method="post">
                        <?php 
                        for($i = 0 ; $i < count($dbTables) ; $i++)
                        {
                        ?>
                        <div class="row" style="height: 30px;">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <input type="checkbox" class="checkbox" name="check_box[]" value="<?php echo $dbTables[$i] ?>" />
                                </div>
                                <div class="col-md-6">
                                    <?php echo $dbTables[$i] ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="row" style="height: 30px;">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <input type="hidden" name="user_name" value="<?php echo $_POST['user_name'] ?>" />
                                    <input type="hidden" name="password" value="<?php echo $_POST['password'] ?>" />
                                    <input type="hidden" name="db_name" value="<?php echo $_POST['db_name'] ?>" />
                                </div>
                                <div class="col-md-6">
                                    <input type="submit" class="btn btn-primary" name="submit" value="Submit" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row"></div>
    </div>
</div>


			



