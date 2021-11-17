<?php
    /**
     * File: exportCustomersXls.php
     * The page sends excel file with the customer's list to downloading 
     */
    $path = 'D:\openserver\domains\testpolicy';
    include_once($path . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($path . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    session_start();
    verifyIfUserIsLoggedIn();
    //$user = getLoggedInUsername($connection); // if session variable username is not set, use 'System' as a user name
    $fileName = "customers_" . getLoggedInUsername($connection) . "_" . date('Y-m-d H:i:s', time()) . ".xls"; // Excel file name with xls extenstion for download 
    $fields = array('Serial', 'Name', 'Surname', 'Email', 'Address', 'Date of Birth', 'Country', 'Gender', 'Status', 'Created By', 'Changed By', 'Flex Text 1'); // Column names 
    $excelData = implode("\t", array_values($fields)) . "\n"; // Display column names as first row 
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
    $query = $connection->query($sqlCustomers); 
    if($query->num_rows > 0){ // Output each row of the data 
        while($row = $query->fetch_assoc()){ // Fetch records from database 
            $dateOfBirth = $row['date_of_birth'] != '0000-00-00' ? $row['date_of_birth'] : null; // 0000-00-00 means that date was not selected when creating a customer
            $lineData = array($row['customerId'],
                              $row['customerName'], 
                              $row['surname'], 
                              $row['email'], 
                              $row['address'],  
                              $dateOfBirth, 
                              $row['countryName'], 
                              returnGenderByCode($row['gender']), 
                              $row['customerStatus'], 
                              $row['created_by'],
                              $row['changed_by'],
                              $row['flex_text_1']); 
            $excelData .= implode("\t", array_values($lineData)) . "\n"; 
        } 
    }else {
        $excelData .= 'No records found' . "\n";
        scriptLog($connection, "DOWNLOAD CUSTOMER XLS", getLoggedInUsername($connection), getReturnMessage('custListEmpty')); 
    }
    header("Content-Type: application/vnd.ms-excel"); // Headers for download xls
    header("Content-Disposition: attachment; filename=\"$fileName\""); // attachment means it will be downloaded and saved locally, but not on the web page
    scriptLog($connection, "DOWNLOAD CUSTOMER XLS", getLoggedInUsername($connection), getReturnMessage('success') . ' customers list contains at least 1 customer'); 
    exit($excelData); // exit from the session with the file
?>