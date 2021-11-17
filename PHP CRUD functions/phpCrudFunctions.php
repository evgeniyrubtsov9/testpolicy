<?php
   /** 
    * Author: Jevgenijs Rubcovs LUDF
    * Library: Php Utility functions
    * Version: 1.0
    * Description: Library stores basic CRUD (create remove update delete) functions in PHP
    */
    $path = 'D:\openserver\domains\testpolicy';
    include_once($path . '\PHP Utility Functions\phpUtilityFunctions.php');
   /**
    * @param $connection - connection query-path to database,
    * @param $customerId - id of the customer to delete
    * Function exit from the session with 'success' or 'error'
    */
    function removeCustomer($connection, $loggedInUser, $customerId){
        $processName = 'CUSTOMER REMOVE';
        scriptLog($connection, $processName, $loggedInUser, "User removing customer: " . $customerId);
        if(!empty($customerId) && !($connection->connect_error)){
            $customerId = $connection->real_escape_string($_POST['customerId']);
            $sqlRemoveCustomer = $connection->prepare('delete from customer where id = ?');
            $sqlRemoveCustomer->bind_param('s', $customerId);
            $sqlRemoveCustomer->execute();
            if($sqlRemoveCustomer->execute()){
                scriptLog($connection, $processName, $loggedInUser, "ReturnMsg: " . getReturnMessage('success'));
                exit(getReturnMessage('success'));
            } else {
                scriptLog($connection, $processName, $loggedInUser, "ReturnMsg: " . getReturnMessage('removeCustomerFail'));
                exit(getReturnMessage('removeCustomerFail'));
            }
        }
    }
    /**
     * @param $key - key for the function to understand what action to perform ('add' or 'update')
     * @param $connection - mysqli database connection
     * @param $responsibleUser - username responsible for action (put into created_by or changed_by)
     * @param $name, $surname, $email, $address, $dateOfBirth, $countryCode, $gender, $status_code, $flextext1, $customerId - customer information details
     * Function perfoms exit with successful or unsuccessful message
     */
    function addOrUpdateCustomer($key, $connection, $responsibleUser, $name, $surname, $email, $address, $dateOfBirth, $countryCode, $gender, $status_code, $flextext1, $customerId){
        $processName = 'ADD-OR-UPDATE CUSTOMER';
        scriptLog($connection, $processName, $responsibleUser, "Received AJAX request... ");
        // Ugly way to get function arguments. php built-in function func_get_args() was adding a lot of unwanted loading time
        $params = array('key: ' => $key, 'responsible user: ' => $responsibleUser, 'name: ' => $name, 'surname: ' => $surname, 'email: ' => $email, 'address: ' => $address,
            'birthdate: ' => $dateOfBirth, 'countryCode: ' => $countryCode, 'gender: ' => $gender, 'status code: ' => $status_code, 'customer serial: ' => $customerId);
        $paramsToString = "";
            foreach($params as $paramsKey=>$value) {
            scriptLog($connection, $processName, $responsibleUser, '<b>'.$paramsKey.'</b>'.(($value == '') ? "[empty]" : $value));
            $paramsToString .= $value . ' :: ';
        }
        if(!$key) exit(getReturnMessage('keyFail')); // exit from session if the key is not specified
        else {
            switch($key){
                case 'add':{
                    $sql= "insert into customer (name, surname, email, address, date_of_birth, country_code, gender, status_code, created_by, flex_text_1) 
                                values (?, ?, ?, ?, str_to_date(?, '%Y-%m-%d'), ?, ?, ?, ?, ?)";
                    $sqlAddCustomer = $connection->prepare($sql);
                    // 10 bind params <=> 10 values:
                    $sqlAddCustomer->bind_param('ssssssssss', $name, $surname, $email, $address, $dateOfBirth, $countryCode, $gender, $status_code, $responsibleUser, $flextext1); 
                    if($sqlAddCustomer->execute()){
                        scriptLog($connection, $processName, $responsibleUser, "ReturnMsg: " . getReturnMessage('success'));
                        exit(getReturnMessage('success'));
                    }
                    else { 
                        scriptLog($connection, $processName, $responsibleUser, "ReturnMsg: " . getReturnMessage('addCustomerFail') . ' Params :: '. $paramsToString);
                        exit(getReturnMessage('addCustomerFail'));
                    }
                }
                case 'update':{
                    $sql = "update customer 
                                set name=?, surname=?, email=?, address=?, date_of_birth=str_to_date(?, '%Y-%m-%d'), country_code=?, gender=?, status_code=?, flex_text_1=?, changed_by=? 
                                where id=?";
                    $sqlUpdateCustomer = $connection->prepare($sql);
                    $sqlUpdateCustomer->bind_param('sssssssssss', $name, $surname, $email, $address, $dateOfBirth, $countryCode, $gender, $status_code, $flextext1, $responsibleUser, $customerId); 
                    if($sqlUpdateCustomer->execute()){
                        scriptLog($connection, $processName, $responsibleUser, "ReturnMsg: " . getReturnMessage('success'));
                        exit(getReturnMessage('success'));
                    }
                    else { 
                        scriptLog($connection, $processName, $responsibleUser, "ReturnMsg: " . getReturnMessage('updCustumerFail') . ' Params :: '. $paramsToString);
                        exit(getReturnMessage('updCustumerFail'));
                    }
                }
                default: exit(getReturnMessage('keyFail')); // return error that the key is incorrect in case the key does not match the values 'add' or 'update'
            }
        }
    }

    function invokeCustomerFunctions($connection, $user){
        $processName = 'CUSTOMER MANIPULATION';
        if(isset($_GET['getCountryList'])) retrieveInfoFromDatabase('getCountryList', $connection); // get countries list with their codes from country table
        if(isset($_GET['loadOrUpdateCustomersList'])) loadOrUpdateCustomersList($connection); // load or update customer list table
        if(isset($_POST['addOrUpdate'])) { // Edit customer & Add Customer
            $action = $connection->real_escape_string($_POST['addOrUpdate']);
            scriptLog($connection, $processName, $user, 'User initiated action: <b>'.$action.'</b>');
            $name = $connection->real_escape_string($_POST['name']); // getting and escaping special characters in POST variables 
            $surname = $connection->real_escape_string($_POST['surname']);
            $email = $connection->real_escape_string($_POST['email']);
            $address = $connection->real_escape_string($_POST['address']);
            $dateOfBirth = $connection->real_escape_string($_POST['date_of_birth']);
            $countryCode = $connection->real_escape_string($_POST['country_code']);
            $gender = $connection->real_escape_string($_POST['gender']);
            $status_code = $connection->real_escape_string($_POST['status_code']);
            $responsibleUser = $connection->real_escape_string($_POST['user']);
            $flextext1 = $connection->real_escape_string($_POST['flex_text_1']);
            $customerId = $connection->real_escape_string($_POST['customer_id']);
            $customerId = $customerId == null ? "No serial yet. New Customer" : $customerId;
            scriptLog($connection, $processName, $user, 'Customer serial: <b>'.$customerId.'</b>');
            if($action == 'addCustomer'){
                addOrUpdateCustomer('add', $connection, $user, $name,$surname,$email,$address,$dateOfBirth,$countryCode,$gender,$status_code,$flextext1,
                    null);
            } else if($action == 'updateCustomer'){ 
                addOrUpdateCustomer('update', $connection, $user, $name,$surname,$email,$address,$dateOfBirth,$countryCode,$gender,$status_code,$flextext1,$customerId);
            }
        }
        if(isset($_POST['removeCustomer'])){ // Remove Customer function. If variable removeCustomer is set up, remove the customer using the appropriate function
            $customerId = $connection->real_escape_string($_POST['customerId']);
            removeCustomer($connection, $user, $customerId);
        }
    }
?>