<?php 
    if(!isset($_SESSION['loggedIn'])){ // If user is already logged in (session variable 'loggedIn' is set up), return the user into the system without asking credentials
        header('Location: auth');      // otherwise return to auth.php for user to provide credentials at first
        exit();
    }
?>
<div><hr><p style='text-align: center; margin: 10px; bottom: 0; right: 45%;'>2021 Jevgenijs Rubcovs LUDF</p></div>