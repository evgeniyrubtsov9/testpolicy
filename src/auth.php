<?php 
    $path = 'D:\openserver\domains\testpolicy';
    include_once($path . '\PHP Auth Functions\phpAuthFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php (src)
    session_start(); 
    if(isset($_SESSION['loggedIn'])) {
        header('Location: index');
        exit();
    }
    invokeAuthenticationFunctions($connection); // database mysql connection from database.php
    invokeUtilityFunctions($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <link rel="stylesheet" href="/styles/auth_page.css"> 
    <title>Test Policy</title>
</head>
<body>
    <div class="back">
        <div class="div-center">
            <div class="content">
                <h3>Test Policy</h3>
                <form id='auth_form'>
                    <div class="form-group">
                        <input type="text" class="form-control" id="username" placeholder="Your username">
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" placeholder="Your password">
                    </div>
                    <input type="submit" value='Sign in' id='login'>
                    <a id='restorePassword'>Forgot password?</a>
                    <p id='auth_response'></p>
                </form>
                <form id='restore_password'>
                    <span class='securityCode form-group'></span>
                    <div class="form-group">
                        <input id='restorePasswordUsername' type="text" class="form-control" placeholder="Your username">
                    </div>
                    <div class="form-group">
                        <input class='form-control securityCode' type='text' placeholder='6 symbols' maxlength='6'>
                    </div>
                    <input id='restorePasswordSubmitUsername' type='button' value='Submit'>
                    <input id='restorePasswordSubmitSecurityCode' type='button' value='Submit'>
                    <a display='block' href="index">Go back</a>
                    <span id='restorePasswordMsg'></span>
                </form>
                <form id='new_password'>
                    <div class="form-group">
                        <input id='newPassword' type="password" class="form-control" placeholder="New Password">
                    </div>
                    <div class="form-group">
                        <input id='newPasswordConfirm' type="password" class="form-control" placeholder="Confirm">
                    </div>
                    <span id='pwChanged'>Password changed</span>
                    <input id='newPasswordSubmit' type='button' value='Submit' disabled>
                    <a display='block' href="index">Go back</a>
                </form>
                <br>
                <div class="loadingSymbol"></div>
                <span id='auth_security_msg'>The system is for autherised users only.</span>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" 
            integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" 
            crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" 
            integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" 
            crossorigin="anonymous"></script>
    <script type="module" src="/JS scripts/JS Authentication Functions.js"></script>
    <script type="text/javascript" src="/JS scripts/jQuery library.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>

