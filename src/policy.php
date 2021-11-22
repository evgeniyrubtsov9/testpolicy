<?php
    $path = 'D:\openserver\domains\testpolicy';
    include_once($path . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($path . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    session_start();
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
    <?php include_once('common/navbar.php'); ?>
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
            <table class="table table-bordered" id='dataTable'>
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
                        $sqlGetPolicies = "select id as serial, created_by,
                                            (select concat(c.name, ' ',c.surname) from customer c, policy p where c.id = p.customer_serial) as customer, 
                                            DATE_FORMAT(start_date, '%d %M %Y') start_date, DATE_FORMAT(end_date, '%d %M %Y') end_date, status, DATE_FORMAT(created, '%d %M %Y') created, total_premium, currency from policy";
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
                                    <td><a href='user?name=".$row['created_by']."'>".$row['created_by']."</a></td>
                                    <td>".$row['total_premium'].' '.$row['currency']."</td>
                                </tr>
                                ";
                            }
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include_once('common/footer.php'); ?>
    <script type="module" src="/JS scripts/JS Policy Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
