<?php // basic code lines for logout event - unset the variable and redirect to authentication page
    session_start(); 
    unset($_SESSION['loggedIn']);
    session_destroy();
    header('Location: auth');
    exit(); // it is important to exit for the session
?>