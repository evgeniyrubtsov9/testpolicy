<?php
    session_start();
    if(!isset($_SESSION['loggedIn'])){ // If user is already logged in (session variable 'loggedIn' is set up), return the user into the system without asking credentials
        header('Location: auth');      // otherwise return to auth.php for user to provide credentials at first
        exit();
    }
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($_SESSION['path'] . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    invokeUtilityFunctions($connection);
    if(isset($_GET['findCustomer'])){
        $customerSerial = $_GET['findCustomer'];
        $sqlFindCustomer = $connection->query("select concat(name, ' ', surname) customer, ifnull(nullif(email, ''), 'Not specified') email,
        ifnull(DATE_FORMAT(date_of_birth, '%d %M %Y'), 'Not specified') bd from customer
        where id = $customerSerial");
        if($sqlFindCustomer->num_rows > 0){
            $row = $sqlFindCustomer->fetch_assoc();
            $age;
            if($row['bd'] != 'Not specified') $age = ' ('.date_diff(new DateTime($row['bd']), date_create('now'))->y.' yo) '; // get the age of the customer, '->y' represents Years
            $result = '<b>Name: </b>'.$row['customer'].'<br>'
            .'<b>Date of Birth: </b>'.$row['bd'].$age.'<br>'
            .'<b>Email: </b>'.$row['email'];
            exit($result);
        }else exit('Customer not found');
    }
    if(isset($_GET['setNewCustomer'])){
        $customerSerial = $_GET['setNewCustomer'];
        $sql = $connection->prepare("select id as serial, name, surname, ifnull(nullif(email, ''), '<small>Not specified</small>') email, 
        ifnull(nullif(address,''), '<small>Not specified</small>') address, ifnull(DATE_FORMAT(date_of_birth, '%d-%m-%Y'), '<small>Not specified</small>') bd, 
        (select cs.name from customer c, customer_status cs where cs.code = c.status_code and c.id=?) as status from customer where id=?");
        $sql->bind_param('ss', $customerSerial, $customerSerial);
        if($sql->execute()){
            $result = $sql->get_result();
            if($result->num_rows > 0){
                $row = $result->fetch_assoc();
                exit($row['serial'].':'.$row['name'].':'.$row['surname'].':'.$row['email'].':'.$row['address'].':'.$row['bd'].':'.$row['status']);
            }
        }
    }
    if(isset($_POST['policy_form'])){
        $customerSerial = preg_split('~ ~', $_POST['customer'])[0];
        $startDate = $_POST['start_date'] != '' ? $_POST['start_date'] : null;
        $endDate = $_POST['end_date'] != '' ? $_POST['end_date'] : null;
        $cancelRegDate = $_POST['cancel_reg_date'] != '' ? $_POST['cancel_reg_date'] : null;
        $effectiveRegDate = $_POST['effective_reg_date'] != '' ? $_POST['effective_reg_date'] : null;
        $terminationCause = $_POST['termination_cause'] != '' ? $_POST['termination_cause'] : null;
        $sqlCreatePolicy = $connection->prepare("insert into policy (customer_serial, created, created_by, currency, product_name, start_date, end_date, status,
            cancel_reg_date, effective_reg_date, termination_cause, calculated, product_option) 
            values (?, now(), ?, 'EUR', 'Life', ?, ?, 'New', ?, ?,?, '0', 'Product for young or adults')");
        $sqlCreatePolicy->bind_param('sssssss', $customerSerial, getLoggedInUsername($connection), $startDate, $endDate, $cancelRegDate, $effectiveRegDate, $terminationCause);
        if($sqlCreatePolicy->execute()){
            $policySerial = $connection->query("select id from policy where customer_serial = '$customerSerial' order by created desc limit 1");
            $policySerial = $policySerial->fetch_assoc()['id'];
            $sqlCreatePolicyObject = $connection->prepare("insert into policy_object_details (policy_serial, policyholder_cancer_yn, policyholder_extreme_sports_yn, 
            policyholder_smoker_status_code) values (?, 'no', 'no', '0')");
            $sqlCreatePolicyObject->bind_param('s', $policySerial);
            if($sqlCreatePolicyObject->execute()){
                exit('policy_created ' .$policySerial);
            }
            exit('ERROR');
        }
        exit('ERROR');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Test Policy</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/policy.css">
    <link rel="stylesheet" href="/styles/product_tariff_params.css">
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container-lg'>
        <form id = 'formPolicy'>
            <input type='hidden' name='policy_action' />
            <input type='hidden' name='customer'/>
            <input type='hidden' name='policy_form'>
            <span id='policyReturnMsg' style='color: red;'></span>
            <table class="table bordless" style='width: 450px;'>
                <div id='customerDialog' style='display: none;' title='Find customer by serial'>
                    <input type='number' class='form-control'></input><br>
                    <span id='customerDialogSpanRes'></span><br><br>
                    <button type='button' style='display: none;'>Select</button>
                    <span id='customerDialogMsg'></span>
                </div>
                <tr>
                    <td style='vertical-align: middle;'>Customer Serial</td>
                    <td><button type='button' id='customerSearch'>Search</button><br><span id='customer'></td>
                </tr>
                <tr>
                    <td style='vertical-align: middle;'>Policy Start Date</td>
                    <td><input name='start_date' class='form-control' type='Date' style='width: 250px; display: inline;'/><br></td>
                </tr>
                <tr>
                    <td style='vertical-align: middle;'>Policy End Date</td>
                    <td><input name='end_date' class='form-control' type='Date' style='width: 250px; display: inline;'/><br></td>
                </tr>
                <tr>
                    <td style='vertical-align: middle;'>Cancel Reg. Date</td>
                    <td><input name='cancel_reg_date' class='form-control' type='Date' style='width: 250px; display: inline;'/><br></td>
                </tr>
                <tr>
                    <td style='vertical-align: middle;'>Effective Cancel Date</td>
                    <td><input name='effective_cancel_date' class='form-control' type='Date' style='width: 250px; display: inline;'/><br></td>
                </tr>
                <tr>
                    <td>Termination cause:</td>
                    <td><textarea name='termination_cause' class='form-control' id='policyTermCause' style='width: 250px; height: 25px; padding: 0; position: absolute; z-index: 5;'></textarea></td>
                <tr>
                    <td></td>
                    <td><button type='button' style=''id='policyCreate'>Add</button></td>
                </tr>
            </table>
        </form>
    </div>
    <?php include_once('footer.php'); ?>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <script type="module" src="/JS scripts/JS Policy Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
