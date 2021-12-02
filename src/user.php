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
    <link rel="stylesheet" href="/styles/index_page.css">
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container-lg'>
    <?php
        if(isset($_GET['current'])){
            $currentUser = $_GET['current'];
            echo "User wants to check/update his profile...<br> User: <input type='text' value='$currentUser'</input>";
        }
        if(isset($_GET['name'])){
            $currentUser = $_GET['name'];
            echo "User retrieved another profile...<br> User: $currentUser";
        }
    ?>
    </div>
    <?php include_once('footer.php'); ?>
    <script type="module" src="/JS scripts/JS User Functions.js"></script>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
