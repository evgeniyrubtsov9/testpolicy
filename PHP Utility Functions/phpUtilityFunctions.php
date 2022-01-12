<?php
   /** 
    * Author: Jevgenijs Rubcovs LUDF
    * Library: Php Utility/Helper functions
    * Version: 1.0
    * Description: Library to store helper/utility functions in PHP
    */
    include_once($_SESSION['path'].'\src\database.php');
    include_once($_SESSION['path'].'\PHP Product Functions\phpProductFunctions.php');
    //$user = getLoggedInUsername($connection);
    /**
     * @param $connection - database mysqli connection
     * @param $logType - name/type of the log 
     * @param $username - username 
     * @param $logdata - string to add into the database (log_data); varchar(10000)
     */
    function scriptLog($connection, $logType, $username, $logdata){
        $datetimeAndLogData; $datetime; $logdata; $conditionPassed = false;
        // if $logData contains */* (using these symbols to store datime & logData for logging Ajax data/requests), separate datetime and log data
        if(!empty($logdata) && strpos($logdata, '*/*') == true) { 
            $datetimeAndLogData = explode('*/*', $logdata);
            $datetime = $datetimeAndLogData[0];
            $logdata = $datetimeAndLogData[1];
            $conditionPassed = true;
        } else if (!empty($logdata) && !strpos($logdata, '*/*')) { // if $logData does not contain */*, no need to separate datime and log data
            $logdata = strlen($logdata) <= 10000 ? $logdata : substr($logdata, 10000); 
            $datetime = date('Y-m-d H:i:s', (time() - (60*60)));
            $conditionPassed = true;
        }
        if($conditionPassed){
            $sql = 'insert into log (datetime, name, username, log_data) values (?,?,?,?)';
            $qryCreateLog = $connection->prepare($sql);
            $qryCreateLog->bind_param('ssss', $datetime, $logType, $username, $logdata);
            $qryCreateLog->execute();
        }
    }

    /**
     * @param $connection - database mysqli connection 
     * @param $logType - log type name (could be any)
     * @param $username - user name related to the log (could be any)
     * @param $logdata - main log data
     * Function creates a log via scriptLog() function and exits with success msg
     */
    function scriptLogAjaxRequestResult($connection, $logType, $username, $logdata) {
        scriptLog($connection, $logType, $username, $logdata);
        exit(getReturnMessage('success')); // need to add validation for non success cases !!!!
    }
    /**
     * @param $data - Object to pull into console 
     * @param $returnMsg - message to put before result
     * @return echo - $data and $returnMsg concatination
    */
    function debug_to_console($returnMsg, $data) {
        $output = $data;
        //$returnMsg = strval($returnMsg);
        if (is_array($output)){ // 
            $output = implode(',', $output);
        }
        echo "<script>console.log('".$returnMsg.' '.$output."');</script>";
    }
    /**
     * @param $code - gender code (F,M,N/A);
     * @return {String} full name of gender 
    */
    function returnGenderByCode($code){
        if(!empty($code)){
            $code = strval($code);
            if($code == 'M') return 'Male';
            else if($code == 'F') return 'Female';
            else return 'N/A';
        } else return 'N/A';
    }

    function generateSecurityCode() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < 6; $i++) { 
            $index = rand(0, strlen($characters) - 1);
            $result .= $characters[$index];
        }
        return $result;
    }
    function verifyIfUserIsLoggedIn(){
        if(!isset($_SESSION['loggedIn'])){ // If user is already logged in (session variable 'loggedIn' is set up), return the user into the system without asking credentials
            header('Location: auth');      // otherwise return to auth.php for user to provide credentials at first
            exit();
        }
    }

    function validateFilesizeBytes($filesize, $maxsize){
        return ($filesize > 0 && $filesize <= $maxsize) ? true : false;
    }

    function checkUploadedFilesNotEmpty(){
        return $_FILES['gtc']['name'] != '' || $_FILES['ipid']['name'] != '' || $_FILES['logo']['name'] != '' ? true : false;
    }

    function validateExtensionByType($type, $extension){
        $ext = strtolower($extension);
        switch($type){
            case 'file': if($ext == 'pdf' || $ext == 'doc' || $ext == 'docx') return true; else return false;
            case 'image': if($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg') return true; else return false;
            default: return false;
        }
    }

    function verifyAdministrator(){
        if(!isset($_SESSION['admin']) && !isset($_GET['current'])){
            header('Location: index');
            exit();
        }
    }

    function getLoggedInUsername($connection){
        return isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
    }

    function validateProductSetup($connection, $productName, $productCommercialDescription, $productValidFrom){
        $processName = 'PRODUCT VALIDATION';
        $name = explode(' ', $productName);
        $description = explode(' ', $productCommercialDescription);
        if(strlen($productName) == 0 
        || strlen($productName) > 255 
        || strlen($productCommercialDescription) == 0 
        || strlen($productCommercialDescription > 255)){
            scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('prodNameLength'));
            exit(getReturnMessage('prodNameLength'));
        }
        foreach($name as $namePart){
            if(!ctype_alnum($namePart)){
                scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('prodNameLD'));
                exit(getReturnMessage('prodNameLD'));
            }
        }
        foreach($name as $descriptionPart){
            if(!ctype_alnum($descriptionPart)){
                scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('prodNameLD'));
                exit(getReturnMessage('prodNameLD'));
            }
        }
        if($productValidFrom == '' || empty($productValidFrom)){
            scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('prodValidFm'));
            exit(getReturnMessage('prodValidFm'));
        }
    }
    function invokeProductFunctions($connection){
        if(checkUploadedFilesNotEmpty()) uploadProductDocumentsToDatabase($connection);
        updateProductSetup($connection);
    }
    function invokeUtilityFunctions($connection){
        if(isset($_POST['logResult'])) {
            $datetime = $connection->real_escape_string($_POST['datetime']);
            $logType = $connection->real_escape_string($_POST['action']);
            $username = $connection->real_escape_string($_POST['username']);
            $logData = $connection->real_escape_string($_POST['logData']);
            $separator = "*/*"; // the */* symbols are used to later separate datetime and log data (used when logging js/jquery functions)
            $datetimeAndLogData = $datetime . $separator . $logData; 
            scriptLogAjaxRequestResult($connection, $logType, $username, $datetimeAndLogData);
        }
        if(isset($_GET['setProductPremiumPartOptions'])){
            $sqlRelations = $connection->query("select premium_part name, relation_code code from product_premium_part");
            $result = "";
            if($sqlRelations->num_rows > 0){
                while($row = $sqlRelations->fetch_assoc()){
                    $result .= str_replace(" ", "", $row['name']).'='.$row['code'].',';
                }
                exit($result);
            }
            exit(getReturnMessage('dbError'));
        }

    }

    /**
     * @param {String} $key - getCountryList
     * @param {mysqli} $connection - database mysqli connection 
     * Function exits with the result of countries list + countries code
     */
    function retrieveInfoFromDatabase($key, $connection){
        $processName = 'DATA RETRIEVE BY KEY : '.$key;
        //scriptLog($connection, $processName, getLoggedInUsername($connection), "Retrieving data...");
        if(!empty($key)){
            if($key == 'getCountryList'){
                if(isset($_GET['getCountryList'])){
                    $sqlCountries = $connection->query("select name, code from country");
                    if($sqlCountries->num_rows > 0){
                        while ($row = $sqlCountries->fetch_assoc()) 
                            $returnCountries .= $row['name'] .' '. $row['code']. ","; // result will contain countries names and code values           
                        //scriptLog($connection, $processName, getLoggedInUsername($connection), "ReturnMsg: ".getReturnMessage('success'));
                        exit($returnCountries);
                    } else {
                        //scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('countryListFail'));
                        exit(getReturnMessage('countryListFail'));
                    }
                }
            } 
            if($key == 'getLogData'){
                if(isset($_GET['getLogData'])){
                    $sqlLog = $connection->query("select date_format(datetime, '%d-%m-%Y %H:%i:%s') as datetime, name, username, log_data from log");
                    if($sqlLog->num_rows > 0){
                        while($rowLog = $sqlLog->fetch_assoc()){
                            $returnLogData .= "<p>" . $rowLog['datetime'] .      // fetching data into returnLogData
                                            ' :: [' . $rowLog['name'] . ']' . 
                                            ' :: [' . $rowLog['username'] . '] :: ' . 
                                                      $rowLog['log_data'] . "</p>";
                        }
                        //scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('success'));
                        exit($returnLogData);
                    } else {
                        //scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: '.getReturnMessage('getLogFail'));
                        exit(getReturnMessage('getLogFail'));
                    }
                }
            }
            if($key == 'getLogDataToDownload'){
                if(isset($_GET['getLogDataToDownload'])){
                    $sqlLog = $connection->query("select datetime, name, username, log_data from log");
                    if($sqlLog->num_rows > 0){
                        while($rowLog = $sqlLog->fetch_assoc()){
                            $resultedLogData = str_replace('<b>', '', $rowLog['log_data']);
                            $resultedLogData = str_replace('</b>', '', $resultedLogData);
                            $returnLogData .= $rowLog['datetime'] .      // fetching data into returnLogData
                                            ' :: [' . $rowLog['name'] . ']' . 
                                            ' :: [' . $rowLog['username'] . '] :: ' . 
                                            $resultedLogData . "\n";
                        }
                    } 
                    $file = 'scriptLog.txt';
                    $handle = fopen($file, "w");
                    fwrite($handle, $returnLogData); // open a new file and put the data inside 
                    fclose($handle);
                    header('Content-Description: Script Log text file download');
                    header('Content-Type: application/octet-stream'); // content type "application/octet-stream" is a binary file
                    header('Content-Disposition: attachment'); // attachment means it will be downloaded and saved locally, but not on the web page 
                    header('Content-Length: ' . filesize($file)); // size of the message body in bytes
                    readfile($file); // read the file and write it to the output buffer
                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Downloaded the log file. ReturnMsg: '.getReturnMessage('success'));
                    exit(); // no need to exit with anything, since AJAX GET request will get everything what PHP answered
                }
            }
        }
    }
    /**
     * @param $connection - mysqli database connection
     * Function gets customer list from database and exits with ready table rows and table data. In case customers not found in database (sql returned 0 rows), 
     * function exits with appropriate error message
     */
    function loadOrUpdateCustomersList($connection){
        $sqlCustomers = ' select *, 
                            cust.name as customerName, 
                            cust.id as customerId,
                            country.name as countryName, 
                            country.code as countryCode,
                            customer_status.name as customerStatus, 
                            customer_status.code as custStatusCode
                            from customer cust 
                                join country on cust.country_code = country.code 
                                join customer_status on customer_status.code = cust.status_code
                                order by customerId desc';
        $result = $connection->query($sqlCustomers);
        $exitResult = '';
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $gender = returnGenderByCode($row['gender']);
                $dateOfBirth = $row['date_of_birth'] != '0000-00-00' ? $row['date_of_birth'] : null; // 0000-00-00 means that date was not selected when creating the customer
                $exitResult .= 
                    '<tr>
                        <td>'.$row['customerId'].'</td>,
                        <td id="customer_'.$row['customerId'].'">'.$row['customerName'].'</td>
                        <td>'.$row['surname'].'</td>
                        <td>'.$row['email'].'</td>
                        <td>'.$row['address'].'</td>
                        <td>'.$dateOfBirth.'</td>
                        <td id="tdCountryName" value="'.$row['countryCode'].'">'.$row['countryName'].'</td>
                        <td id="tdGender" value="'.$row['gender'].'">'.$gender.'</td>
                        <td id="tdCustomerStatus" value="'.$row['custStatusCode'].'">'.$row['customerStatus'].'</td>
                        <td>'.$row['flex_text_1'].'</td>
                        <td>
                            <a id="add" class="add" title="Add"><span class="glyphicon glyphicon-plus"></span></a>
                            <a id="update" class="update" title="update"><span class="glyphicon glyphicon-ok"></span></a>
                            <a id="editCustomer_'.$row['customerId'].'" class="edit" title="Edit" data-toggle="tooltip"><span class="glyphicon glyphicon-pencil"></span></a>
                            <a class="delete" title="Delete" data-toggle="tooltip"><span class="glyphicon glyphicon-remove-sign"></span></a>
                        </td>
                    </tr>';
            }
            exit($exitResult); // exit with the string of resulted table rows
        } else exit(getReturnMessage('emptyList'));
        
    }
    /**
     * 
     */
    function uploadProductDocumentsToDatabase($connection){
        $processName = 'PROD-DOCS UPLOAD';
        scriptLog($connection, $processName, getLoggedInUsername($connection), 'User uploading product documents...');
        $productDocumentName;
        if($_FILES['gtc']['name'] != '') $productDocumentName = 'gtc';
        if($_FILES['ipid']['name'] != '') $productDocumentName = 'ipid';
        if($_FILES['logo']['name'] != '') $productDocumentName = 'logo';
        $filesize = $_FILES[$productDocumentName]['size']; 
        $filename = $_FILES[$productDocumentName]['name'];
        $fileTemporaryName = $_FILES[$productDocumentName]['tmp_name'];
        $fileType = $_FILES[$productDocumentName]['type'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION); // get the extension from file using built-in php function
        if($productDocumentName == 'logo'){
            if(!validateExtensionByType('image', $ext)){
                scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: <b>'. getReturnMessage('imgExt').'</b>');
                exit(getReturnMessage('imgExt'));
            }
        }else {
            if(!validateExtensionByType('file', $ext)) {
                scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: <b>'. getReturnMessage('fileExt').'</b>');
                exit(getReturnMessage('fileExt'));
            }
        }
        if(!validateFilesizeBytes($filesize, 25000000)) { // validate if filesize is <= 25 MB
            scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: <b>'. getReturnMessage('fileSize').'</b>');
            exit(getReturnMessage('fileSize'));
        }
        $fp = fopen($fileTemporaryName, 'r');
        $content = fread($fp, filesize($fileTemporaryName));
        $content = addslashes($content);
        fclose($fp);
        $filename = $productDocumentName .'.'. $ext; // always save file with the same name (either 'gtc', or 'ipid', or 'logo' + extension)
        $sqlCheckIfFileAlreadyExists = $connection->query("select name from files_data where name like '".$productDocumentName . "%'");
        //$sql;
        $updated = false;
        if($sqlCheckIfFileAlreadyExists->num_rows > 0) { // update current file if it was already present in DB
            $sql = $connection->query("update files_data set name='".$filename."', type='".$fileType."', size='".$filesize."', content='".$content."' where name like '".$productDocumentName . "%'");
            $updated = true;
        }
        else {
            $sqlAddFile = "insert into files_data (name, type, size, content) values ('$filename', '$fileType', '$filesize', '$content')";
            $sql = $connection->query($sqlAddFile);
        }   
        if($sql && $updated) {
            scriptLog($connection, $processName, getLoggedInUsername($connection), 'User successfully UPDATED: <b>'.$filename.'</b>. Original name: <b>'.$_FILES[$productDocumentName]['name'].'</b>');
            exit('Updated: '.$filename);
        } 
        else if($sql && !$updated) {
            scriptLog($connection, $processName, getLoggedInUsername($connection), 'User successfully UPLOADED: <b>'.$filename.'</b>. Original name: <b>'.$_FILES[$productDocumentName]['name'].'</b>');
            exit('Uploaded: '.$filename);
        }
        scriptLog($connection, $processName, getLoggedInUsername($connection), 'ReturnMsg: <b>'.getReturnMessage('dbError').'</b>'); 
        exit(getReturnMessage('dbError'));
    }
    function partiallyHideEmail($email){
        $em   = explode("@",$email);
        $name = implode('@', array_slice($em, 0, count($em)-1));
        $len  = floor(strlen($name)/2);
        return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);   
    }

    /**
     * @param $keyValue - key; string param which is used to find an appropriate message
     * @return $value - value connected to the key. Returns nothing in case of no match by key
     */
    function getReturnMessage($keyValue){
        $returnMessages = array( 
            'success' => 'Event has been finished with success', 
            'addCustomerFail' => 'ERROR SQL received customer creation parameters, but execution failed',
            'userNotExist' => 'ERROR User does not exist. Contact Administrator',
            'credentialsFail' => 'Incorrect credentials',
            'removeCustomerFail' => 'ERROR. Fail to remove customer (id): ',
            'countryListFail' => 'Function failed to get country list',
            'getLogFail' => 'GETLOG_ERROR Function failed to get log data',
            'downloadLogFail' => 'DOWNLOADLOG_ERROR Function failed to download log file',
            'custListEmpty' => 'Xls file created, but customers list is empty',
            'updCustumerFail' => 'ERROR Function failed to update customer',
            'keyFail' => 'ERROR Incorrect key was passed to the function',
            'emptyList' => 'ERROR Function failed to find customers in the database',
            'userBlocked' => 'Too many login attempts or your account is blocked. Please contact Administrator',
            'securCodeActive' => 'AC-ERROR Security code was already sent. It\'s active for 30 minutes. Check email: ',
            'noUserEmail' => 'ERROR User exists, but email is not found. Please contact Administrator',
            'incorrectSecurityCode' => 'ERROR Incorrect security code was provided',
            'pwNotChanged' => 'ERROR Password was not changed. Please contact Adminsitrator',

            'imgExt' => 'ERROR Incorrect image extension. Server allows only PNG, JPG, JPEG!',
            'fileExt' => 'ERROR Incorrect file extension. Server allows only PDF, DOC, DOCX!',
            'fileSize' => 'ERROR File size is greater than 25 MB or the file is empty!',
            
            'dbError' => 'ERROR Database error. Contact Administrator!',
            'prodNameLength' => 'ERROR Product name/description length issue!',
            'prodNameLD' => 'ERROR Product name/description should contain only letters/digits!',
            'prodValidFm' => 'ERROR Product valid from date is not set!',
            'nonNumeric' => 'ERROR Not numeric value: ',
            'valueLengthFail' => 'ERROR Value length is too big: ',
            'bmiRange' => 'ERROR Range start must be less than range end!',
            'ageRange' => 'ERROR Minimal age must be less than maximal',
            'minAge' => 'ERROR Customer should be at least 1 years old',
            'maxAge' => 'ERROR Customer should be under 100 or 100 years old'
        );
        if(!empty($keyValue))
            foreach($returnMessages as $key=>$value)
                if($keyValue == $key) 
                    return $value;
    }

    if(isset($_GET['userSelected'])){
        $username = $_GET['userSelected'];
        $_SESSION['selectedUsername'] = $_GET['userSelected'];
        $sqlGetUserDetails = $connection->query("select email, full_name, (select role from user_roles where code = role_code) role from user where username = '$username'");
        if($sqlGetUserDetails->num_rows > 0){
            $row = $sqlGetUserDetails->fetch_assoc();
            $role = $row['role'];
            $fullname = $row['full_name'];
            $email = $row['email'];
            exit($fullname.':'.$username.':'.$role.':'.$email);
        } else {
            exit(getReturnMessage('dbError'));
        }
    }

    if(isset($_POST['user_form'])){
        // retrieve variables and do some validations 1) for unique username 2) for unique email
        $processName = 'USER PROFILE ADD OR UPDATE';
        $selectedUser = $_SESSION['selectedUsername'];
        $username = $_POST['username'] != '' ? trim($_POST['username']) : $_POST['username'];
        $fullname = $_POST['fullname'];
        $email = $_POST['email'] != '' ? trim($_POST['email']) : $_POST['email'];
        $role = $_POST['role'];
        if(!empty($role)){
            if($role == 'Administrator') $role = '0';
            if($role == 'Subagent') $role = '1';
            if($role == 'Blocked') $role = '4';
        } else $role = '0';
        if($username == '') exit('ERROR Please fill all mandatory fields');
        $sqlCheckUniqueUsername = $connection->query("select username from user where username = '$username'");
        if($sqlCheckUniqueUsername->num_rows > 0){
            $row = $sqlCheckUniqueUsername->fetch_assoc();
            if($row['username'] != $selectedUser) {
                scriptLog($connection, $processName, getLoggedInUsername($connection), 'ERROR Please provide unique username');
                exit('ERROR Please provide unique username');
            }
        }
        if(!empty($email)){
            $sqlCheckUniqueEmail = $connection->query("select username from user where email = '$email'");
            if($sqlCheckUniqueEmail->num_rows > 0){
                $row = $sqlCheckUniqueEmail->fetch_assoc();
                if($row['username'] != $selectedUser){
                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'ERROR Please provide unqie email');
                    exit('ERROR Please provide unqie email');
                }
            }
        }
        $addNewUserFlag = $_POST['addNew'];
        // ---------------------------------------------------------------------------------------
        if(trim($addNewUserFlag) == 'addNew'){ // add user profile
            $processName = "ADD NEW USER";
            $sqlAddNewUser = $connection->prepare("insert into user (username, password, full_name, role_code, created, created_by) values (?,?,?,?,now(),?)");
            $password = sha1(trim($_POST['password']));
            $sqlAddNewUser->bind_param('sssss', $username, $password, $fullname, $role, getLoggedInUsername($connection));
            if($sqlAddNewUser->execute()){
                exit('success');
            }
        }else { // update user profile
            $processName = "USER PROFILE UPDATE";
            $changePasswordFlag = $_POST['changePasswordFlag'];
            if($changePasswordFlag == 'false'){
                $sqlUpdateUser = $connection->prepare("update user set username = ?, full_name = ?, email = ?, role_code = ? where username = ?");
                $sqlUpdateUser->bind_param('sssss', $username, $fullname, $email, $role, $selectedUser);
            }else {
                $password = sha1($_POST['password']);
                $sqlUpdateUser = $connection->prepare("update user set password = ?, username = ?, full_name =?, email = ?, role_code = ? where username = ?");
                $sqlUpdateUser->bind_param('ssssss', $password, $username, $fullname, $email, $role, $selectedUser);
            }
            if($sqlUpdateUser->execute()){
                if(trim($selectedUser) == trim(getLoggedInUsername($connection))){
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Updated personal profile");
                } else {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Administrator updated <b>$username</b> profile");
                }
                exit(getReturnMessage('success'));
            }else {
                scriptLog($connection, $processName, getLoggedInUsername($connection), getReturnMessage('dbError'));
                exit(getReturnMessage('dbError'));
            }
        }
    }
?>

