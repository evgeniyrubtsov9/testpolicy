<?php
    session_start();
    if(!isset($_SESSION['loggedIn'])){ // If user is already logged in (session variable 'loggedIn' is set up), return the user into the system without asking credentials
        header('Location: auth');      // otherwise return to auth.php for user to provide credentials at first
        exit();
    }
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    //include_once($_SESSION['path'] . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once($_SESSION['path'] . '\PHP Policy Functions\phpPolicyFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    invokeUtilityFunctions($connection);
    invokePolicyFunctions($connection);

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
            DATE_FORMAT( p.effective_reg_date, '%Y-%m-%d' ) effective_reg_date, calculation_steps, termination_cause FROM policy p, customer c where p.id = ? and c.id = p.customer_serial");
            $sqlGetPolicy->bind_param('s', $policySerial);
            if($sqlGetPolicy->execute()){
                $result = $sqlGetPolicy->get_result();
                if($result->num_rows > 0){
                    while($rowPolicy = $result->fetch_assoc()){
                        if(strpos($rowPolicy['date_of_birth'], 'Not specified') == false) 
                            $age = ' ('.date_diff(new DateTime($rowPolicy['date_of_birth']), date_create('now'))->y.' yo) ';
                        echo "
                            <form id='formPolicy'>
                                <input type='hidden' name='policy_action' />
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
                                            <tr><td>Policy Document:</td><td>";
                                            $sql = $connection->query("select name from policy_document where policy_serial = '$policySerial'");
                                            if($sql->num_rows > 0) echo "<a href='downloadPolicyDoc?policySerial=$policySerial'>Download</a>";
                                            else echo "<a style='display: none;' href='downloadPolicyDoc?policySerial=$policySerial'>Download</a>";
                                        echo "</td></tr>
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
                                        <p>"
                                        .$rowPolicy['calculation_steps'].  
                                       "</p>
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
