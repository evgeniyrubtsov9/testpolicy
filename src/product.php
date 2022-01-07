<?php
    session_start();
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($_SESSION['path'] . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    verifyIfUserIsLoggedIn();
    invokeUtilityFunctions($connection);
    invokeProductFunctions($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Test Policy</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/product.css">
    <link rel="stylesheet" href="/styles/auth_page.css">
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container-lg'>
        <form class="form-horizontal" id='product_form' method="post">
            <input type='hidden' name='product_form'>
            <fieldset>
                <legend><strong>LIFE</strong> product: <a id='productSetup' href='product_tariff_params'>Setup</a></legend>
                <span id='productSetupUpdateMsg'></span>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="product_name">Product Premium Parts</label>  
                    <div class="col-md-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th id='premiumName'>Name</th>
                                    <th id='premiumType'>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                                $sqlRelationOptions = $connection->query("select value, code from premium_part_relation");
                                $relationOptions = "";
                                if($sqlRelationOptions->num_rows > 0){
                                    while($row = $sqlRelationOptions->fetch_assoc()){
                                        $relationOptions .= "<option value=".$row['code'].">".$row['value']."</option>";
                                    }
                                }
                                $sqlPremiumParts = $connection->query("select ppr.value value, ppp.premium_part name, 
                                ppp.description description from premium_part_relation ppr, product_premium_part ppp where ppp.relation_code = ppr.code
                                                                        order by name desc");
                                $premiumPartsAndRelations = array();
                                if($sqlPremiumParts->num_rows > 0){
                                    while($row = $sqlPremiumParts->fetch_assoc()){
                                        echo "<tr>
                                                <td style='vertical-align: middle;'>".$row['name']."</td>
                                                <td style='vertical-align: middle;'>
                                                    <select 
                                                        id='select_".str_replace(" ", "", $row['name'])."' 
                                                        name='".str_replace(" ", "", $row['name'])."'
                                                        class='form-control'>".$relationOptions."
                                                    </select></td>
                                                <td style='vertical-align: middle;'>".$row['description']."</td>
                                            </tr>";
                                    }
                                }
                            ?>
                            </tbody>
                        </table> 
                    </div>
                </div>
                <?php 
                    $sqlGetProduct = ' select name, 
                                              commercial_description cm, 
                                              valid_from vf,
                                              valid_to vt, 
                                              status prod_status, 
                                              changed_when cw, 
                                              changed_by cb
                                                    from product';
                    $result = $connection->query($sqlGetProduct);
                    if($result->num_rows > 0){
                        while($row = $result->fetch_assoc()) {
                            $productStatus = $row['status'];
                            echo 
                            '<div class="form-group">
                                <label class="col-md-4 control-label" for="product_name">Product name</label>  
                                <div class="col-md-4">
                                    <input id="product_name" name="product_name" class="form-control input-md" type="text" value='.$row['name'].'> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_cd">Commercial Description</label>  
                                <div class="col-md-4">
                                    <input id="product_cd" name="product_cd" class="form-control input-md" type="text" value="'.$row['cm'].'"> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_valid_from">Valid From</label>
                                <div class="col-md-4">
                                    <input id="product_valid_from" name="product_valid_from" class="form-control input-md" type="date" value='.date_format(date_create($row['vf']), 'Y-m-d').'> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_valid_to">Valid To</label>
                                <div class="col-md-4">
                                    <input id="product_valid_to" name="product_valid_to" class="form-control input-md" type="date" value='.date_format(date_create($row['vt']), 'Y-m-d').'> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_status">Product Status</label>
                                <div class="col-md-4">
                                    <select id="product_status" name="product_status" class="form-control">';
                                    if($row['prod_status'] == 'Active'){
                                        echo 
                                        '<option selected>Active</option>
                                         <option>Retired</option>
                                    </select>
                                </div>
                            </div>'; 
                                    } else {
                                        echo 
                                        '<option>Active</option>
                                         <option selected>Retired</option>
                                    </select>
                                </div>
                            </div>';}
                            echo ' 
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_changed_when">Changed When</label>
                                <div class="col-md-4">
                                    <input  name="product_changed_when" class="form-control" type="text" value="'.$row['cw'].'" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_changed_by">Changed By</label>
                                <div class="col-md-4">
                                    <input name="product_changed_by" class="form-control" type="text" value="'.$row['cb'].'" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <a class="col-md-4 control-label" id="product_documents">Documents*</a>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label"></label>
                                <div class="col-md-4">
                                    <button id="btn_update_product" type="submit">Update</button>
                                    <div class="loadingSymbol"></div>
                                </div>
                            </div>
            </fieldset>
        </form>';
                        }
                    }
    echo '<div id="documentsModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Product Documents</h4>
                        <div class="loadingSymbol"></div>
                        <span id="product_file_upload_msg"></span>
                    </div>
                    <div class="modal-body">
                        <form id="form_gtc" method="post" enctype="multipart/form-data"> 
                            <p><label>General Terms & Conditions</label>';
                            $sql = $connection->query('select name from files_data where name like "gtc%"');
                            if($sql->num_rows > 0) echo ' <a href="downloadProductDoc?name=gtc">Download</a>';
                            else echo ' <a style="display:none;" href="downloadProductDoc?name=gtc">Download</a>';
                      echo '<input id="product_gtc_file" type="file" name="gtc"/>
                            </p>
                        </form>
                        <!--<form id="form_ipid" method="post" enctype="multipart/form-data">
                            <p><label>Insurance Product Information Document</label>';
                            $sql = $connection->query('select name from files_data where name like "ipid%"');
                            if($sql->num_rows > 0) echo ' <a href="downloadProductDoc?name=ipid">Download</a>';
                            else echo ' <a style="display:none;" href="downloadProductDoc?name=ipid">Download</a>';
                      echo '<input id="product_ipid_file" type="file" name="ipid"/>
                            </p>
                        </form>--!>
                        <form id="form_logo" method="post" enctype="multipart/form-data">
                            <p><label>Logo</label>';
                            $sql = $connection->query('select name from files_data where name like "logo%"');
                            if($sql->num_rows > 0) echo ' <a href="downloadProductDoc?name=logo">Download</a>';
                            else echo ' <a style="display:none;" href="downloadProductDoc?name=logo">Download</a>';
                      echo '<input id="product_logo_file" type="file" name="logo"/><br>
                                <img id="productLogo" src="#" alt="logo"/>
                            </p>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>';?>
    </div>
    <?php include_once('footer.php');?>
    <script type="module" src="/JS scripts/JS Customer Functions.js"></script>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <script type="module" src="/JS scripts/JS Product Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
