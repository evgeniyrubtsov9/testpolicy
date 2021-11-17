<?php 
   /** 
    * Author: Jevgenijs Rubcovs LUDF
    * Library: PHP Authentication functions
    * Version: 1.0
    * Description: Library to store authentication functions in PHP
    */
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    include_once($path . '\PHP Utility Functions\phpUtilityFunctions.php');
    require $path . '\vendor\autoload.php';
    /**
     * @param $connection - database mysqli connection 
     * @param $username - user name
     * @param $password - user password
     * Function redirects the user to index.php in case of the correct username and password validation.
     * Function creates new login attempts records in login_attempts db in case of incorrect password provided for the given username
     */
    function authenticateUserAndRedirect($connection, $username, $password){
        $processName = 'AUTHENTICATION';
        scriptLog($connection, $processName, $username, 'User is attempting to log into the system');
        $sqlCheckIfUserExists = $connection->query("select * from user where username = '".$username."'"); // check if user exists in the system and later output appropriate message
        $userFound = $sqlCheckIfUserExists->num_rows > 0 ? "TRUE" : "FALSE"; // if more then 1 row ==> user exists in the system
        if($userFound == 'TRUE'){               // sql returns user role name and login attempts number
            $sqlLoginAttempts = $connection->prepare('SELECT (SELECT ur.role 
                                                                FROM user u, user_roles ur 
                                                                WHERE u.role_code = ur.code AND u.username = ?
                                                             ) as userRole, 
                                                             (SELECT lg.attempts_to_login attempts 
                                                                FROM login_attempts lg 
                                                                WHERE lg.username = ?
                                                             ) as attempts
                                                        FROM user u 
                                                        WHERE u.username = ?');
            $sqlLoginAttempts->bind_param('sss', $username, $username, $username);
            $sqlLoginAttempts->execute();
            $resultLoginAttempts = $sqlLoginAttempts->get_result();
            $attemptsToLogin;
            $userRole;
            $recordNotFound = true;
            if($resultLoginAttempts->num_rows > 0){ // if record is found in login_attempts table, take 'attempts' and 'user role'
                while($row = $resultLoginAttempts->fetch_assoc()) {
                    if($row['attempts'] != null){ // login attempts could be null, meaning the user has no unsuccessfull login attempts records in database
                        $recordNotFound = false;
                        $attemptsToLogin = $row['attempts'];
                    }
                    $userRole = $row['userRole']; // but user role will be always retrieved from the same sql
                }
            } // exit with an appropriate error code if user status is blocked or user has previously provided incorrect password for 3 times
            if($attemptsToLogin == 3 || $userRole == "Blocked") exit(getReturnMessage('userBlocked'));
            if($userRole == "Administrator") $_SESSION['admin'] = 1; // set the session variable 'admin' if the logged in user is with the Administrator role 
            $qryUser = $connection->prepare("select username from user where username = ? and password = ?");
            $qryUser->bind_param('ss', $username, $password);
            $qryUser->execute();
            $result = $qryUser->get_result();
            if ($row = $result->fetch_assoc()) {
                $_SESSION['loggedIn'] = 1; // set loggedIn and username session variables to use it later on pages; return success message (= user is verified and could be redirected)
                $_SESSION['username'] = $username;
                scriptLog($connection, $processName, $username, 'User authenticated. ReturnMsg: <b>' . getReturnMessage('success').'</b>');
                $sqlClearLoginAttempts = $connection->query("delete from login_attempts where username = '" . $_SESSION['username']."'"); // empty record in db 
                exit(getReturnMessage('success'));
            } else { // create/update login attempts number for the current user or return error that credentials are incorrect in case login attempts are not equal to 3
                if($recordNotFound) $sqlAddLoginAttempts = $connection->query("insert into login_attempts (username, attempts_to_login) values ('".$username."', 1)"); 
                else if($attemptsToLogin < 3) { // update attempts with a new value += 1 
                    $sqlUpdateLoginAttempts = $connection->query("update login_attempts set attempts_to_login = ".++$attemptsToLogin." where username = '".$username."'");
                    if($attemptsToLogin == 3) { // change user status to blocked when 3 attempts were reached
                        $sqlBlockUser = $connection->query("update user set role_code = 4 where username = '".$username."'"); // set user role 'Blocked' by role code '4'
                        scriptLog($connection, $processName, $username, 'ReturnMsg: ' . getReturnMessage('userBlocked'));
                        exit(getReturnMessage('userBlocked'));
                    }
                }
                scriptLog($connection, $processName, $username, 'ReturnMsg: ' . getReturnMessage('credentialsFail')); // exit with error message that credentials are incorrect
                exit(getReturnMessage('credentialsFail'));
            }
        } else { // Exit with error message that user does not exist 
            scriptLog($connection, $processName, $username, 'ReturnMsg: ' . getReturnMessage('userNotExist'));
            exit(getReturnMessage('userNotExist'));
        }
    }
    /**
     * @param $connection - mysqli database connection
     * @param $username - user name
     * Function checks the user for existance, generates password reset security code and sends email with the code to the user email address 
     */
    function createSecurityCodeAndSendEmailToUser($connection, $username){
        $processName = 'PASSWORD RESTORE';
        criptLog($connection, $processName, $username, 'User initiated password restore, username: ' . $username);
        $sqlCheckUserExist = $connection->query("select email from user where username = '" .$username."'");
        if($sqlCheckUserExist->num_rows > 0){
            while($row = $sqlCheckUserExist->fetch_assoc()){
                $sqlCheckUserActiveSecurityCode = $connection->query("select username from password_reset where username = '".$username."'");
                if($sqlCheckUserActiveSecurityCode->num_rows > 0) exit(getReturnMessage('securCodeActive') . $row['email']); // exit with appropriat error msg if exists Active Security code
                $email = $row['email'];
                if($email == null) exit(getReturnMessage('noUserEmail')); // Exist with appropriate error message if email is not found for the user in database
                $securityCode = generateSecurityCode(); 
                $sqlCreateSecurityCode = $connection->prepare("insert into password_reset (timestamp, username, security_code, email) values ('".time()."', '".$username."', ?, ?)");
                $sqlCreateSecurityCode->bind_param('ss', sha1($securityCode), $email); // encrypt securityCode with sha1 
                if($sqlCreateSecurityCode->execute()){
                    $mail = new PHPMailer(true); //Passing `true` enables PHPMailer exceptions
                    $mail->isHTML(true);  //Set email format to HTML
                    $mail->isSMTP(); // use SMTP protocol to send email
                    $mail->Host = 'smtp.gmail.com'; // using gmail stmp server
                    $mail->SMTPAuth = true; 
                    $mail->Port = 587;
                    $mail->Username = 'ludf.kvalifikacijasdarbs@gmail.com';
                    $mail->Password = 'Italian1009';
                    $mail->setFrom('ludf.kvalifikacijasdarbs@gmail.com', 'TestPolicy');
                    $mail->addAddress($email);
                    $mail->Subject = 'TestPolicy Password Restore';        
                    $mail->Body    = 'Your security code: ' . $securityCode . '. It will be active for <b>30 minutes</b>!<br>TestPolicy';
                    $mail->AltBody = 'Your security code: ' . $securityCode . '. It will be active for 30 minutes!'; // body in plain text for non-HTML mail clients
                    $result = $mail->send();
                    if($result) { // mail function returns boolean. Exit with user email address if mail was accepted for delivery (true). *Does not mean email was sent
                        scriptLog($connection, $processName, $username, 'Email was sent to: <b>'.$email.'</b>. Password restore security code: <b>'.$securityCode.'</b>');
                        exit($email);
                    } 
                    else {
                        scriptLog($connection, $processName, $username, 'ReturnMsg: '.getReturnMessage('userNotExist'));
                        exit(getReturnMessage('userNotExist'));
                    }
                }
                exit('error'); // need to handle
            }
        } 
        scriptLog($connection, '$processName', $username, 'ReturnMsg: '.getReturnMessage('userNotExist'));
        exit(getReturnMessage('userNotExist')); // Exit with error message that user does not exist 
    }
    /**
     * @param $connection - mysqli db connection
     * @param $username - user name
     * @param $secutiyCode - security code for password reset, which sent to user's email
     * Function verified the security code by checking the number of records with the givern user and security code
     */
    function verifySecurityCode($connection, $username, $securityCode){
        $processName = 'SEC-CODE VERIFICATION';
        scriptLog($connection,  $processName, $username, 'Verifying username: <b>'.$username. ', Security Code: </b>' .$securityCode.'</b>');
        // sql returns number of records for given user and security code taking the last 30 (1800 seconds) minutes starting from Now
        $sqlVerifyCode = $connection->prepare("select count(*) recordsFound from password_reset where username = ? and security_code = ? and timestamp + 1800 >= unix_timestamp()");
        $sqlVerifyCode->bind_param('ss', $username, sha1($securityCode));
        if($sqlVerifyCode->execute()){
            $result = $sqlVerifyCode->get_result();
            if($row = $result->fetch_assoc()){
                if($row['recordsFound'] > 0) {
                    scriptLog($connection, $processName, $username, 'User: '.$username.' was verified by email security code: '.$securityCode);
                    exit(getReturnMessage('success'));
                } else {
                    scriptLog($connection, $processName, $username, 'User: '.$username.' failed verification: '.getReturnMessage('incorrectSecurityCode'));
                    exit(getReturnMessage('incorrectSecurityCode'));
                }
            }
        }
        scriptLog($connection, $processName, $username, 'ReturnMsg: '.getReturnMessage('dbError'));
        exit(getReturnMessage('dbError'));  // need to handle
    }
    /**
     * @param $connection - mysqli db connection
     * @param $username - user name
     * @param $newPassword - user's new password
     * Function update the password for the given user and removes security code record from the password_reset table
     */
    function changeUserPassword($connection, $username, $newPassword){
        $processName = 'PASSWORD CHANGE';
        scriptLog($connection, $processName, $username, 'changeUserPassword start, username: '.$username. ', New password: ' .$newPassword);
        $sqlUpdatePassword = $connection->prepare("update user set password = ? where username = ?");
        $sqlUpdatePassword->bind_param('ss', sha1($newPassword), $username);
        if($sqlUpdatePassword->execute()){
            $sqlDeleteSecurityCodeRecord = $connection->query("delete from password_reset where username = '".$username."'");
            scriptLog($connection, $processName, $username, 'user changed the password');
            exit(getReturnMessage('success'));
        }
        scriptLog($connection, $processName, $username, getReturnMessage('dbError').' '.getReturnMessage('pwNotChanged'));
        exit(getReturnMessage('pwNotChanged'));
    }
    /**
     * @param $connection - mysqli database connection
     * Function calls all authentication function
     */
    function invokeAuthenticationFunctions($connection){
        $processName = 'AUTHENTICATION';
        $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User not authenticated yet';
        if(isset($_POST['login'])){
            $username = $connection->real_escape_string($_POST['usernamePHP']);
            $password = sha1($connection->real_escape_string($_POST['passwordPHP'])); // sha1 is used to encrypt user password
            scriptLog($connection, $processName, $user, 'attempting to log into the system');
            authenticateUserAndRedirect($connection, $username, $password);
        }
        if(isset($_GET['restorePassword'])){
            $username = $connection->real_escape_string($_GET['username']);
            createSecurityCodeAndSendEmailToUser($connection, $username);
        }
        if(isset($_GET['verifySecurityCode'])){
            $securityCode = $connection->real_escape_string($_GET['code']);
            $username = $connection->real_escape_string($_GET['username']); 
            verifySecurityCode($connection, $username, $securityCode);
        }
        if(isset($_POST['changePassword'])){
            $username = $connection->real_escape_string($_POST['username']);
            $password = $connection->real_escape_string($_POST['password']);
            changeUserPassword($connection, $username, $password);
        }
    }
?>


