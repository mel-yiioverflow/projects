<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
                <div class="row table table-bordered" style="border-spacing: 10px; border-color: #0073b7; background-color: #c5a4a4;">
                    <form action="select_tables.php" method="post">
                        <div class="row" style="height: 30px;">
                            <div class="col-md-12 form-group">
                                <div class="col-md-6">
                                    DB Name :
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="db_name" name="db_name" />
                                </div>
                            </div>
                        </div>
                        <div class="row" style="height: 30px;">
                            <div class="col-md-12 form-group">
                                <div class="col-md-6">
                                    User :
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="user_name" name="user_name" />
                                </div>
                            </div>
                        </div>
                        <div class="row" style="height: 30px;">
                            <div class="col-md-12 form-group">
                                <div class="col-md-6">
                                    Password :
                                </div>
                                <div class="col-md-6">
                                    <input type="password" class="form-control" id="password" name="password" />
                                </div>
                            </div>
                        </div><br />
                        <div class="row" style="height: 30px;">
                            <div class="col-md-12">
                                <div class="col-md-6"></div>
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

