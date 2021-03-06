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

//require_once __DIR__.'/controllers/base.php';

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
    <div class="row"  style="width: 100%; height: 250px"></div>
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="col-md-12 table table-bordered" style="border-spacing: 5px;">
                    <br />
                    <div class="col-md-6">
                        <form action="settings.php" method="post">
                            <button type="submit" class="btn btn-default">Add Settings</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="cruds.php" method="post">
                            <button type="submit" class="btn btn-default">List Cruds</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </div>
    <div class="row"></div>
</div>
           