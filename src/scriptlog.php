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
    if(isset($_GET['getLogData'])) retrieveInfoFromDatabase('getLogData', $connection); // get logs from log table
    if(isset($_GET['getLogDataToDownload'])) retrieveInfoFromDatabase('getLogDataToDownload', $connection); // send log file in txt format to download
    if(isset($_POST['clearLogfile'])){
        $result = $connection->query('truncate table log');
        if($result) exit(getReturnMessage('success'));
        exit('error');
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.css"> -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="/styles/current_user.css">
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/auth_page.css">
    <title>TestPolicy</title>
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container' id="log"><div class="loadingSymbol"></div></div>
    <div id='downloadLog'>
        <div class="loadingSymbol"></div>
        <button id='btnDownloadlog'>Download</button>
        <button id='btnClearlog'>Clear Log</button>
    </div>
    <?php include_once('footer.php'); ?>

    <script type="module" src="/JS scripts/JS Customer Functions.js"></script>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script> -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>