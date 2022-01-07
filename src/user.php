<?php
    session_start();
    if(!isset($_SESSION['loggedIn'])){ // If user is already logged in (session variable 'loggedIn' is set up), return the user into the system without asking credentials
        header('Location: auth');      // otherwise return to auth.php for user to provide credentials at first
        exit();
    }
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($_SESSION['path'] . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    verifyAdministrator();
    invokeUtilityFunctions($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Test Policy</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.css">
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/current_user.css">
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container-lg'>
    <?php
            if(isset($_SESSION['admin'])){
            $currentUser = $_GET['name'];
            ?>
            <div class="table-title">
                <div class="row">
                    <div class="col-sm-11">
                        <h2>User</h2>
                        <span id='usersAmount'><?php 
                            $usersAmount = $connection->query('select count(*) usersAmount from user');
                            if($usersAmount->num_rows > 0){
                                while($row=$usersAmount->fetch_assoc()) {
                                    echo --$row['usersAmount']; // -=1 bc one user is the System user
                                    break;
                                }
                            }else echo 0;
                        ?> user(-s)</span>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" id="addNewUser">Add New</button>
                    </div>
                </div>
            </div>
            <?php
            echo "
                <div class='row'>
                <div class='col-md-3'></div>
                    <div class='col-md-6'>
                        <table class='table table-bordered' id='users'>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Created by</th>
                            </tr>
                        </thead>
                        <tbody>";
            $sqlGetUserDetails = $connection->query("select username, full_name, email, created, created_by, (select role from user_roles where code = role_code) role from user");
            if($sqlGetUserDetails->num_rows > 0){
                while($row = $sqlGetUserDetails->fetch_assoc()){
                    if($row['username'] == 'System') continue;
                    echo "
                        <tr>
                            <td><a class='username' >".$row['username']."</a></td>
                            <td>".$row['full_name']."</td>
                            <td>".$row['email']."</td>
                            <td>".$row['role']."</td>
                            <td>".date_format(date_create($row['created']), 'd-m-Y')."</td>
                            <td>".$row['created_by']."</td>
                        </tr>
                    ";
                }
            }
                    echo "     
                        </tbody>
                    </div>
                </div>
            ";
        }
    ?>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script type="module" src="/JS scripts/JS User Functions.js"></script>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
