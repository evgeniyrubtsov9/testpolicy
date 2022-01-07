<?php
    session_start();
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($_SESSION['path'] . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    
    verifyIfUserIsLoggedIn();
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
    else if(isset($_GET['setNewCustomer'])){
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
        $policyAction = $_POST['policy_action'];
        if($policyAction != 'save' && $policyAction != 'calculate' && $policyAction != 'cancel' && $policyAction != 'activate') exit('ERROR uknown policy action: ' .$policyAction);
        $policySerial = $_POST['policy_serial'];
        if($policySerial == '') exit('ERROR Policy serial was not found');
        $sqlCheckPolicyStatus = $connection->query("select status from policy where id = $policySerial and status='Canceled'");
        if($sqlCheckPolicyStatus->num_rows > 0) exit('ERROR Policy is canceled');

        $endDate = $_POST['end_date'] != '' ? $_POST['end_date'] : null;
        $startDate = $_POST['start_date'] != '' ? $_POST['start_date'] : null;
        $status = $_POST['status'];
        $cancelRegDate = $_POST['cancel_reg_date'] != '' ? $_POST['cancel_reg_date'] : null;
        $effectiveCancelRegDate = $_POST['effective_cancel_date'] != '' ? $_POST['effective_cancel_date'] : null;
        $terminationCause = $_POST['termination_cause'];
        $customerSerial = $_POST['customer_serial'];
        $productOption = $_POST['product_option'] == 'forAdults' ? 'Product for young or adults' : 'Product for the elderly';

        $customerAge = $_POST['customer_age_at_outset'];
        $customerHeight = $_POST['customer_height'] != '' ? $_POST['customer_height'] : null;
        $customerWeight = $_POST['customer_weight'] != '' ? $_POST['customer_weight'] : null;
        $customerBMI = $_POST['customer_bmi'];
        $customerCancerParam = $_POST['customer_cancer_param'];
        $customerXtremeSportsParam = $_POST['customer_xtreme_sport_param'];
        $customerSmokerStatusParam = $_POST['customer_smoker_status'];


        // $sqlCheckIfDifferentCustomer = $connection->prepare("select customer_serial from policy where id = ? and customer_serial = ?");
        // $sqlCheckIfDifferentCustomer->bind_param('ss', $policySerial, $customerSerial);
        // if($sqlCheckIfDifferentCustomer->execute()){
        //     $result = $sqlCheckIfDifferentCustomer->get_result();
        //     if($result->num_rows == 0){
        //         $sqlClearPolicyObjectForNewCustomer = $connection->prepare("update policy_object_details set policyholder_age_at_outset=null, policyholder_height_cm=null, 
        //             policyholder_weight_kg=null, policyholder_bmi=null, policyholder_cancer_yn ='no', policyholder_extreme_sports_yn='no', 
        //             policyholder_smoker_status_code=0 where policy_serial = ?");
        //             $sqlClearPolicyObjectForNewCustomer->bind_param('s', $policySerial);
        //             if($sqlClearPolicyObjectForNewCustomer->execute()){
        //                 //exit('success');
        //             }
        //     }
        // }
        $optionalCovers = array();
        if($_POST['death_cover'] == 'selected') array_push($optionalCovers, 'Death'); else array_push($optionalCovers, null);
        if($_POST['accidental_death_cover'] == 'selected') array_push($optionalCovers, 'Accidental Death'); else array_push($optionalCovers, null);
        if($_POST['accident_cover'] == 'selected') array_push($optionalCovers, 'Accident'); else array_push($optionalCovers, null);

        if($policyAction == 'save'){ // invoke policy action Save to save all the input data into DB
            $sqlUpdatePolicyRecord = $connection->prepare("update policy set start_date = ?, end_date = ?, cancel_reg_date = ?, effective_reg_date = ?, termination_cause = ?,
                customer_serial = ?, calculated = 0, first_cover = ?, second_cover = ?, third_cover = ?, product_option = ?, last_updated=now() where id = ?");
            $sqlUpdatePolicyRecord->bind_param('sssssssssss', $startDate, $endDate, $cancelRegDate, $effectiveCancelRegDate, $terminationCause, $customerSerial, 
                $optionalCovers[0], $optionalCovers[1], $optionalCovers[2], $productOption, $policySerial);
            $sqlUpdatePolicyObjectRecord = $connection->prepare('update policy_object_details set policyholder_height_cm=?, 
                         policyholder_weight_kg=?, policyholder_cancer_yn = ?, policyholder_extreme_sports_yn=?, policyholder_smoker_status_code=? where policy_serial = ?');
            $sqlUpdatePolicyObjectRecord->bind_param('ssssss', $customerHeight, $customerWeight, $customerCancerParam, $customerXtremeSportsParam, $customerSmokerStatusParam, $policySerial);
            if($sqlUpdatePolicyRecord->execute() && $sqlUpdatePolicyObjectRecord->execute()){
                exit(getReturnMessage('success'));
            }
            exit(getReturnMessage('dbError'));
        }
        else if($policyAction == 'calculate'){ // action calculate
            if($endDate == '' || $startDate == '') exit('ERROR Please populate policy Start / End dates');
            $sqlUpdatePolicyRecord = $connection->prepare("update policy set calculated = 1 where id = ?");
            $sqlUpdatePolicyRecord->bind_param('s', $policySerial);
            if($sqlUpdatePolicyRecord->execute()){
                exit(getReturnMessage('success'));
            }
            exit(getReturnMessage('dbError'));
        }else if($policyAction == 'cancel'){ // action cancel
            if($cancelRegDate == '' || $effectiveCancelRegDate == '' || $terminationCause == '') exit('ERROR Please populate policy Cancel dates and Termination cause');
            $sqlCancelPolicy = $connection->prepare("update policy set status = 'Canceled', cancel_reg_date = ?, effective_reg_date = ?, 
                termination_cause = ?  where id = ?");
            $sqlCancelPolicy->bind_param('ssss', $cancelRegDate, $effectiveCancelRegDate, $terminationCause, $policySerial);
            if($sqlCancelPolicy->execute()) exit('success');
            exit(getReturnMessage('dbError'));
        }else if($policyAction == 'activate'){ // action activate
            $sqlCheckIfPolicyCalculated = $connection->query("select calculated from policy where id = $policySerial and calculated = 1");
            if($sqlCheckIfPolicyCalculated->num_rows == 0) exit('ERROR Policy is not calculated');
        }
        
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
    <link rel="stylesheet" href="/styles/auth_page.css">
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/policy_view.css">
</head>
<body style='background-color: white;'>
    <?php include_once('navbar.php'); ?>
    <div class='container-lg'>
        <?php if(isset($_GET['serial'])) 
            $policySerial = $_GET['serial'];
            $sqlGetPolicy = $connection->prepare("
            SELECT status policyStatus, calculated, product_option, c.name, c.surname, ifnull(nullif(c.email, ''), '<small>Not specified</small>') email, 
            ifnull(nullif(c.address,''), '<small>Not specified</small>') address, ifnull(nullif(date_format(c.date_of_birth, '%d-%m-%Y'), '00-00-0000'), '<small style=\"color:red;\">Not specified</small>') date_of_birth, 
            ( SELECT cs.name FROM customer_status cs, customer cust WHERE cs.code = cust.status_code AND cust.id = c.id ) AS status , 
            p.id AS policySerial, p.customer_serial, DATE_FORMAT(p.created, '%d-%m-%Y') created, DATE_FORMAT(p.last_updated, '%d-%m-%Y %h:%i:%s') last_updated, p.total_premium, p.currency, p.product_name, DATE_FORMAT(p.start_date, '%d-%m-%Y') start_date, 
            DATE_FORMAT(p.end_date, '%Y-%m-%d') end_date, DATE_FORMAT(p.start_date, '%Y-%m-%d') start_date, DATE_FORMAT( p.cancel_reg_date, '%Y-%m-%d' ) cancel_reg_date, 
            DATE_FORMAT( p.effective_reg_date, '%Y-%m-%d' ) effective_reg_date, termination_cause FROM policy p, customer c where p.id = ? and c.id = p.customer_serial");
            $sqlGetPolicy->bind_param('s', $policySerial);
            if($sqlGetPolicy->execute()){
                $result = $sqlGetPolicy->get_result();
                if($result->num_rows > 0){
                    while($rowPolicy = $result->fetch_assoc()){
                        if(strpos($rowPolicy['date_of_birth'], 'Not specified') == false) 
                            $age = ' ('.date_diff(new DateTime($rowPolicy['date_of_birth']), date_create('now'))->y.' yo) ';
                        echo "
                            <form id='formPolicy'>
                                <input type='hidden' name='policy_form'>
                                <input type='hidden' name='policy_serial' value='".$rowPolicy['policySerial']."'>
                                <div class='row'>
                                    <div class='col-sm-8' style='display: flex; justify-content: center;'>
                                        <span id='policyReturnMsg'></span>
                                    </div>
                                </div>
                                <div class='row' style='display: flex; justify-content: center;'>
                                    <div class='col-sm-3' style='border: 1px dotted black; margin: 0 15px 15px 0'>
                                        <label>Policy Details</label>
                                        <hr>
                                        <table class='table bordless'>
                                            <tr><td>Serial:</td><td id='policySerial'>".$rowPolicy['policySerial']."</td></tr>
                                            <tr><td>Created:</td><td>".$rowPolicy['created']."</td></tr>
                                            <tr><td>Last Updated:</td><td>".$rowPolicy['last_updated']."</td></tr>
                                            <tr><td>Currency:</td><td>".$rowPolicy['currency']."</td></tr>
                                            <tr><td>Product name:</td><td>".$rowPolicy['product_name']."</td></tr>
                                            <tr><td>Start Date:</td><td><input name='start_date' type='date' style='width: 160px' class='form-control' value='".$rowPolicy['start_date']."'></input></td></tr>
                                            <tr><td>End Date:</td><td><input name='end_date' type='date' style='width: 160px' class='form-control' value='".$rowPolicy['end_date']."'></input></td></tr>
                                            <tr><td>Was calculated after last save</td><td>".($rowPolicy['policyStatus'] != 'Canceled' ? ($rowPolicy['calculated'] == 0 ? '<b style="color: red;">No</b>' : 'Yes') : '<b style="color: red;">Policy in canceled</b>')."</td></tr>
                                        </table>
                                    </div>
                                    <div class='col-sm-3' style='border: 1px dotted black; margin: 0 15px 15px 0'>
                                        <label>Policy Details</label>
                                        <hr>
                                        <table class='table bordless'>
                                            <tr><td>Terms & Conditions:</td><td>";
                                        $sql = $connection->query('select name from files_data where name like "gtc%"');
                                        if($sql->num_rows > 0) echo ' <a href="downloadProductDoc?name=gtc">Download</a>';
                                        else echo ' <a style="display:none;" href="downloadProductDoc?name=gtc">Download</a></td></tr>';
                                        echo "
                                            <tr><td>Policy Document:</td><td>download link here</td></tr>
                                            <tr><td>Status:</td><td id='status'>";
                                                if($rowPolicy['policyStatus'] == 'New') echo "New";
                                                if($rowPolicy['policyStatus'] == 'Active') echo "Active";
                                                if($rowPolicy['policyStatus'] == 'Canceled') echo "Canceled";
                                        echo "</td></tr>
                                            <tr><td>Payment method:</td><td>Cash</td></tr>
                                            <tr><td>Cancel Reg. Date: </td><td><input name='cancel_reg_date' type='date' style='width: 160px' class='form-control' value='".$rowPolicy['cancel_reg_date']."'></td></tr>
                                            <tr><td>Effective Cancel Date:</td><td><input name='effective_cancel_date' type='date' style='width: 160px' class='form-control' value='".$rowPolicy['effective_reg_date']."'></input></td></tr>
                                            <tr><td>Termination cause:</td><td><textarea name='termination_cause' class='form-control' id='policyTermCause' style='padding: 0; position: absolute; z-index: 5;'>".$rowPolicy['termination_cause']."</textarea></td></tr>
                                            <tr><td>Total Premium:</td><td style='font-weight: bold;'>".number_format($rowPolicy['total_premium'],2, '.', '').' '.$rowPolicy['currency']."</td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div class='row' style='display: flex; justify-content: center;'>
                                    <div class='col-sm-3' style='border: 1px dotted black; margin: 0 15px 15px 0'>
                                        <label>Policyholder</label>
                                        <hr>
                                        <table class='table bordless'>
                                            <div id='customerDialog' style='display: none;' title='Find customer by serial'>
                                                <input type='number' class='form-control'></input>
                                                <span id='customerDialogSpanRes'></span><br><br>
                                                <button type='button' style='display: none;'>Select</button>
                                                <span id='customerDialogMsg'></span>
                                            </div>
                                            <button type='button' id='customerSearch'>Search</button>
                                            <tr><td>Customer No:</td><td><input name='customer_serial' class='form-control' style='width: 75px;' id='custSerial' readonly value='".$rowPolicy['customer_serial']."'></input></td></tr>
                                            <tr><td>Name:</td><td id='custName'>".$rowPolicy['name']."</td></tr>
                                            <tr><td>Surname:</td><td id='custSurname'>".$rowPolicy['surname']."</td></tr>
                                            <tr><td>Email:</td><td id='custEmail'>".$rowPolicy['email']."</td></tr>
                                            <tr><td>Address:</td><td id='custAddress'>".$rowPolicy['address']."</td></tr>
                                            <tr><td>Date of birth:</td><td id='custBirthdate'>".$rowPolicy['date_of_birth'].$age."</td></tr>
                                            <tr><td>Status:</td><td id='custStatus'>".($rowPolicy['status'] == 'Blacklisted' ? '<b style="color: red;">Blacklisted</b>' : $rowPolicy['status'])."</td></tr>
                                        </table>
                                    </div>
                                    <div class='col-sm-3' style='border: 1px dotted black; margin: 0 15px 15px 0'>
                                        <label>Premium calculation steps</label>
                                        <hr>
                                        ...
                                    </div>
                                </div>";
                                $sqlGetCustomerDetails = $connection->prepare("select policyholder_age_at_outset age, policyholder_height_cm height, policyholder_weight_kg weight, 
                                policyholder_bmi bmi, policyholder_cancer_yn cancer, policyholder_extreme_sports_yn sport, policyholder_smoker_status_code smokerStatus 
                                from policy_object_details where policy_serial = ?");
                                $sqlGetCustomerDetails->bind_param('s', $policySerial);
                                if($sqlGetCustomerDetails->execute()){
                                    $result = $sqlGetCustomerDetails->get_result();
                                    if($result->num_rows > 0){
                                        $row = $result->fetch_assoc();
                                        //if($rowPolicy['calculated'] == '1'){
                                            echo "
                                            <div class='row' style='display: flex; justify-content: center;'>
                                                <div class='col-sm-3' style='border: 1px dotted black; margin: 0 15px 15px 0'>
                                                    <label>Object Overview</label>
                                                    <hr>";
                                                    
                                                    echo "
                                                    <table class='table bordless'>
                                                        <tr><td>Age at outset:</td><td><input readonly name='customer_age_at_outset' value='".$row['age']."' style='width: 50px;' class='form-control' type='number'></input></td></tr>
                                                        <tr><td>Height (cm):</td><td><input onKeyPress='if(this.value.length==3) return false;' name='customer_height' value='".$row['height']."' style='width: 50px;' class='form-control' type='number'></input></td></tr>
                                                        <tr><td>Weight (kg): </td><td><input onKeyPress='if(this.value.length==3) return false;' name='customer_weight' value='".$row['weight']."' style='width: 50px;' class='form-control' type='number'></input></td></tr>
                                                        <tr><td>BMI: </td><td><input readonly name='customer_bmi' value='".($row['bmi'] != '' ? number_format($row['bmi'],2,'.','') : $row['bmi'])."' style='width: 70px;' class='form-control' type='number'></input></td></tr>
                                                        <tr><td>Cancer:</td><td>
                                                            <select name='customer_cancer_param' id='cancerSelect' style='width: 100px;'class='form-control'>";
                                                                if($row['cancer'] == 'yes') echo "<option selected value='yes'>Yes</option><option value='no'>No</option>";
                                                                else echo "<option value='yes'>Yes</option><option selected value='no'>No</option>";
                                                    echo "</select></td></tr>
                                                        <tr><td>Extreme Sports:</td><td>
                                                            <select name='customer_xtreme_sport_param' id='xtremeSportsSelect' style='width: 100px;' class='form-control'>";
                                                            if($row['sport'] == 'yes') echo "<option selected value='yes'>Yes</option><option value='no'>No</option>";
                                                            else echo "<option value='yes'>Yes</option><option selected value='no'>No</option>";
                                                    echo "</select></td></tr>
                                                        <tr><td>Smoker status:</td><td>
                                                            <select name='customer_smoker_status' class='form-control' style='width: 100px;'>";
                                                                if($row['smokerStatus'] == 0) 
                                                                    echo "<option value='0' selected>Never</option>
                                                                          <option value='1'>Not now</option>
                                                                          <option value='2'>Less than 40 cigs a day</option>
                                                                          <option value='3'>More than 40 cigs a day</option>";
                                                                if($row['smokerStatus'] == 1) 
                                                                    echo "<option value='0'>Never</option>
                                                                          <option selected value='1'>Not now</option>
                                                                          <option value='2'>Less than 40 cigs a day</option>
                                                                          <option value='3'>More than 40 cigs a day</option>";
                                                                if($row['smokerStatus'] == 2) 
                                                                     echo "<option value='0'>Never</option>
                                                                        <option value='1'>Not now</option>
                                                                        <option selected value='2'>Less than 40 cigs a day</option>
                                                                        <option value='3'>More than 40 cigs a day</option>";
                                                                if($row['smokerStatus'] == 3) 
                                                                    echo "<option value='0'>Never</option>
                                                                        <option value='1'>Not now</option>
                                                                        <option value='2'>Less than 40 cigs a day</option>
                                                                        <option selected value='3'>More than 40 cigs a day</option>";
                                                                
                                                    echo "</select>
                                                        </td></tr>
                                                    </table>
                                                </div>";

                                                // $sqlCovers = $connection->query("select distinct ppp.premium_part cover from product_premium_part ppp, policy p
                                                //     where (ppp.premium_part = p.first_cover or premium_part = p.second_cover or premium_part = p.third_cover) and ppp.relation_code = 1 and p.id = $policySerial");
                                                $sqlCovers = $connection->query("select first_cover, second_cover, third_cover from policy where id = $policySerial");
                                                $arrayWithValues = array();
                                                if($sqlCovers->num_rows > 0){
                                                    while($row = $sqlCovers->fetch_assoc()){
                                                        array_push($arrayWithValues, $row['cover']);
                                                        if($row['first_cover'] == 'Death') array_push($arrayWithValues, $row['first_cover']);
                                                        if($row['second_cover'] == 'Accidental Death') array_push($arrayWithValues, $row['second_cover']);
                                                        if($row['third_cover'] == 'Accident') array_push($arrayWithValues, $row['third_cover']);
                                                    }
                                                }
                                               // print_r($arrayWithValues);
                                                $id = $rowPolicy['product_option'] == 'Product for the elderly' ? 2 : 1;
                                                $sql = $connection->query("select value_cover_first value1, value_cover_second value2, value_cover_third value3 from tariff_sum_insured where id=$id");
                                                if($sql->num_rows > 0) {
                                                    $row = $sql->fetch_assoc();
                                                    $deathSumInsured = $row['value1'];
                                                    $accidentalDeathSumInsured = $row['value2'];
                                                    $accidentSumInsured = $row['value3'];
                                                }
                                                $sql = $connection->query("select value from tariff_base_rates order by premium_part desc");
                                                if($sql->num_rows > 0){
                                                    $prices = array();
                                                    while($row = $sql->fetch_assoc())
                                                        array_push($prices, $row['value']);
                                                }
                                                echo "
                                                <div class='col-sm-3' style='border: 1px dotted black; margin: 0 15px 15px 0'>
                                                    <label>Cover Details</label>
                                                    <hr>
                                                    <table class='table bordless'>
                                                        <tr>
                                                            <th>Cover</th>
                                                            <th>Sum Insured</th>
                                                            <th>Price Monthly</th>
                                                        </tr>
                                                        <tr>";
                                                        if(in_array('Death', $arrayWithValues)) echo "<td><input name='death_cover' value='selected' checked type='checkbox'>Death</td>";
                                                        else echo "<td><input name='death_cover' value='selected' type='checkbox'>Death</td>";
                                                        echo "
                                                            <td>$deathSumInsured</td>
                                                            <td>".$prices[0]."</td></tr>
                                                        <tr>";
                                                        if(in_array('Accidental Death', $arrayWithValues)) echo "<td><input name='accidental_death_cover' value='selected' checked type='checkbox'>Accidental Death</td>";
                                                        else echo "<td><input name='accidental_death_cover' type='checkbox' value='selected'>Accidental Death</td>";
                                                        echo "
                                                            <td>$accidentalDeathSumInsured</td>
                                                            <td>".$prices[1]."</td></tr>
                                                        </tr>
                                                        <tr>";
                                                        if(in_array('Accident', $arrayWithValues)) echo "<td><input name='accident_cover' value='selected' checked type='checkbox'>Accident</td>";
                                                        else echo "<td><input name='accident_cover' value='selected' type='checkbox'>Accident</td>";
                                                        echo "
                                                            <td>$accidentSumInsured</td>
                                                            <td>".$prices[2]."</td></tr>
                                                        </tr>
                                                    </table>
                                                    <span id='productOption'>
                                                        <select name='product_option' class='form-control' style='width: 220px;'>";
                                                            if($rowPolicy['product_option'] == 'Product for the elderly'){
                                                                echo '<option value="forAdults">Product for young or adults</option>
                                                                      <option selected value="forElderly">Product for the elderly</option>
                                                                ';
                                                            } else if($rowPolicy['product_option'] == 'Product for young or adults'){
                                                                echo '<option selected value="forAdults">Product for young or adults</option>
                                                                      <option value="forElderly">Product for the elderly</option>
                                                                ';
                                                            } else echo '<option value="forAdults">Product for young or adults</option>
                                                            <option value="forElderly">Product for the elderly</option>
                                                            ';
                                                        echo "
                                                        </select>
                                                    </span>
                                                </div>
                                            </div>
                                            ";
                                        //}
                                    }
                                }
                                echo "
                                <div class='row' style='display: flex; justify-content: center;'> 
                                    <div class='col-sm-3'>
                                        <div class='loadingSymbol'></div>
                                        <button type='button' id='policySave'>Save</button>
                                        <button type='button' id='policyCalculate'>Calculate</button>
                                        <button type='button' id='policyCancel'>Cancel</button>
                                        <button type='button' id='policyActivate'>Activate</button>
                                    </div>
                                </div>
                            </form>
                        ";
                    }
                }else echo 'error';
            }else echo 'error ';

        ?>
    </div>
    <?php include_once('footer.php'); ?>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <script type="module" src="/JS scripts/JS Policy Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
