<?php
    session_start();
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($_SESSION['path'] . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    verifyIfUserIsLoggedIn();
    invokeUtilityFunctions($connection);
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.css">
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/policy.css">
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container-lg'>
        <div class="table-responsive">
            <div class="table-title">
                <div class="row">
                    <div class="col-sm-8">
                        <h2>Policy</h2>
                        <span id='policiesAmount'><?php 
                            $policiesAmount = $connection->query('select count(*) policiesAmount from policy');
                            if($policiesAmount->num_rows > 0){
                                while($row=$policiesAmount->fetch_assoc()) {
                                    echo $row['policiesAmount'];
                                    break;
                                }
                            }else echo 0;
                        ?> policy(-ies)</span>
                    </div>
                    <div class="col-sm-4">
                        <button type="button" id="addNewPolicy">Add New</button>
                    </div>
                </div>
            </div>
            <table class="table table-bordered" id='policies'>
                <thead>
                    <tr>
                        <th>Policy Serial</th>
                        <th>Customer</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Created by</th>
                        <th>Total Premium</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $sqlGetPolicies = "select policy.id as serial, concat(name, ' ', surname) customer, policy.created_by, 
                        DATE_FORMAT(start_date, '%d %M %Y') start_date, DATE_FORMAT(end_date, '%d %M %Y') end_date, status, 
                        DATE_FORMAT(created, '%d %M %Y') created, total_premium, currency from policy join customer on policy.customer_serial = customer.id";
                        $policies = $connection->query($sqlGetPolicies);
                        if($policies->num_rows > 0){
                            while($row = $policies->fetch_assoc()){
                                echo "
                                <tr>
                                    <td><a href='policyView?serial=".$row['serial']."'>".$row['serial']."</a></td>
                                    <td>".$row['customer']."</td>
                                    <td>".$row['start_date']."</td>
                                    <td>".$row['end_date']."</td>
                                    <td>".$row['status']."</td>
                                    <td>".$row['created']."</td>
                                    <td>".$row['created_by']."</td>
                                    <td>".number_format($row['total_premium'],2, '.', '').' '.$row['currency']."</td>
                                </tr>
                                ";
                            }
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include_once('footer.php'); ?>
    <script type="module" src="/JS scripts/JS Policy Functions.js"></script>
    <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css" />
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
