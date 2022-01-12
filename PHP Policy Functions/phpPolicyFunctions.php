<?php 
    include_once($_SESSION['path'] . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($_SESSION['path'] . '\PHP Auth Functions\phpAuthFunctions.php');

    function invokePolicyFunctions($connection){
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
            $processName = "POLICY SAVE";
            $policyAction = $_POST['policy_action'];
            if($policyAction != 'save' && $policyAction != 'calculate' && $policyAction != 'cancel' && $policyAction != 'activate') exit('ERROR uknown policy action: ' .$policyAction);
            $policySerial = $_POST['policy_serial'];
            if($policySerial == '') exit('ERROR Policy serial was not found');
            $sqlCheckPolicyStatus = $connection->query("select status from policy where id = $policySerial and status='Canceled'");
            if($sqlCheckPolicyStatus->num_rows > 0) exit('ERROR Policy is not canceled');

            $endDate = $_POST['end_date'] != '' ? $_POST['end_date'] : null;
            $startDate = $_POST['start_date'] != '' ? $_POST['start_date'] : null;
            $status = $_POST['status'];
            $cancelRegDate = $_POST['cancel_reg_date'] != '' ? $_POST['cancel_reg_date'] : null;
            $effectiveCancelRegDate = $_POST['effective_cancel_date'] != '' ? $_POST['effective_cancel_date'] : null;
            $terminationCause = $_POST['termination_cause'];
            $customerSerial = $_POST['customer_serial'];
            $productOption = $_POST['product_option'] == 'forAdults' ? 'Product for young or adults' : 'Product for the elderly';

            //$customerAge = $_POST['customer_age_at_outset'];
            $customerHeight = $_POST['customer_height'] != '' ? $_POST['customer_height'] : null;
            $customerWeight = $_POST['customer_weight'] != '' ? $_POST['customer_weight'] : null;
            //$customerBMI = $_POST['customer_bmi'];
            $customerCancerParam = $_POST['customer_cancer_param'];
            $customerXtremeSportsParam = $_POST['customer_xtreme_sport_param'];
            $customerSmokerStatusParam = $_POST['customer_smoker_status'];
            $smokerParamNameInTariff;
            if($customerSmokerStatusParam == '0') $smokerParamNameInTariff = 'first';
            if($customerSmokerStatusParam == '1') $smokerParamNameInTariff = 'second';
            if($customerSmokerStatusParam == '2') $smokerParamNameInTariff = 'third';
            if($customerSmokerStatusParam == '3') $smokerParamNameInTariff = 'fourth';
            $coversSelected = array();
            if($_POST['death_cover'] == 'selected') array_push($coversSelected, 'Death'); else array_push($coversSelected, null);
            if($_POST['accidental_death_cover'] == 'selected') array_push($coversSelected, 'Accidental Death'); else array_push($coversSelected, null);
            if($_POST['accident_cover'] == 'selected') array_push($coversSelected, 'Accident'); else array_push($coversSelected, null);

            if($policyAction == 'save'){ // invoke policy action Save to save all the input data into DB
                $sqlUpdatePolicyRecord = $connection->prepare("update policy set start_date = ?, end_date = ?, cancel_reg_date = ?, effective_reg_date = ?, termination_cause = ?,
                    customer_serial = ?, calculated = 0, first_cover = ?, second_cover = ?, third_cover = ?, product_option = ?, last_updated=now() where id = ?");
                $sqlUpdatePolicyRecord->bind_param('sssssssssss', $startDate, $endDate, $cancelRegDate, $effectiveCancelRegDate, $terminationCause, $customerSerial, 
                    $coversSelected[0], $coversSelected[1], $coversSelected[2], $productOption, $policySerial);
                $sqlUpdatePolicyObjectRecord = $connection->prepare('update policy_object_details set policyholder_height_cm=?, 
                            policyholder_weight_kg=?, policyholder_cancer_yn = ?, policyholder_extreme_sports_yn=?, policyholder_smoker_status_code=? where policy_serial = ?');
                $sqlUpdatePolicyObjectRecord->bind_param('ssssss', $customerHeight, $customerWeight, $customerCancerParam, $customerXtremeSportsParam, $customerSmokerStatusParam, $policySerial);
                if($sqlUpdatePolicyRecord->execute() && $sqlUpdatePolicyObjectRecord->execute()) {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>".getReturnMessage('success')."</b>");
                    exit(getReturnMessage('success'));
                }
                exit(getReturnMessage('dbError'));
            }
            else if($policyAction == 'calculate'){ // action calculate
                $processName = "POLICY CALCULATE";
                
                // get customer birth date at first
                $sqlBirthdateAndStatus = $connection->query("select date_of_birth, (select name from customer_status where code = status_code) status from customer where id = $customerSerial");
                $sqlResult = $sqlBirthdateAndStatus->fetch_assoc();
                $customerBirthDate = $sqlResult['date_of_birth'];
                $customerStatus = $sqlResult['status'];
                if($customerBirthDate == '0000-00-00') {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Please populate date of birth for the policyholder</b>");
                    exit('ERROR Please populate date of birth for the policyholder'); // valid 1
                }
                else $customerAge = strval(date_diff(date_create($customerBirthDate), date_create('now'))->y); // customer age !!!
                $sqlAgeRestrictions = $connection->query("select value_first minAge, value_second maxAge from tariff_age where option = '$productOption'");
                if($sqlResult = $sqlAgeRestrictions->fetch_assoc()){
                    $minAge = $sqlResult['minAge'];
                    $maxAge = $sqlResult['maxAge'];
                }
                if(!($minAge <= $customerAge && $customerAge <= $maxAge)){
                    exit('ERROR Product option: "'.$productOption.'" not allowed for the given customer age: '.$customerAge);
                } else { // save age in policy_object_details
                    $sqlSaveCustomerAgeOnPolicy = $connection->prepare("update policy_object_details set policyholder_age_at_outset = ? where policy_serial = ?");
                    $sqlSaveCustomerAgeOnPolicy->bind_param('ss', $customerAge, $policySerial);
                    $sqlSaveCustomerAgeOnPolicy->execute();
                }
                // important to save after product option validation (above)
                $policyWasSaved = false;
                $sqlUpdatePolicyRecord = $connection->prepare("update policy set start_date = ?, end_date = ?, cancel_reg_date = ?, effective_reg_date = ?, termination_cause = ?,
                    customer_serial = ?, calculated = 1, first_cover = ?, second_cover = ?, third_cover = ?, product_option = ?, last_updated=now() where id = ?");
                $sqlUpdatePolicyRecord->bind_param('sssssssssss', $startDate, $endDate, $cancelRegDate, $effectiveCancelRegDate, $terminationCause, $customerSerial, 
                    $coversSelected[0], $coversSelected[1], $coversSelected[2], $productOption, $policySerial);
                $sqlUpdatePolicyObjectRecord = $connection->prepare('update policy_object_details set policyholder_height_cm=?, 
                    policyholder_weight_kg=?, policyholder_cancer_yn = ?, policyholder_extreme_sports_yn=?, policyholder_smoker_status_code=? where policy_serial = ?');
                $sqlUpdatePolicyObjectRecord->bind_param('ssssss', $customerHeight, $customerWeight, $customerCancerParam, $customerXtremeSportsParam, $customerSmokerStatusParam, 
                    $policySerial);
                if($sqlUpdatePolicyRecord->execute() && $sqlUpdatePolicyObjectRecord->execute()) $policyWasSaved = true;

                if(trim($customerStatus) == 'Blacklisted') {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Selected policyholder holds the status of the Blacklisted</b>");
                    exit('ERROR Selected policyholder holds the status of the Blacklisted'); // valid 2
                }
                if($endDate == '' || $startDate == '') {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Selected policyholder holds the status of the Blacklisted</b>");
                    exit('ERROR Please populate policy Start / End dates'); // valid 3
                } else { // calculate policy interval in month with two decimals
                    $startDate = new DateTime($startDate);
                    $endDate = new DateTime($endDate);
                    $interval = $startDate->diff($endDate);
                    $countMonthsBetween = ($interval->y * 12) + $interval->m;
                    $countMonthsBetween += number_format($interval->d / 30, 2);
                }
                if(trim($customerCancerParam) == 'yes'){
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Customer has cancer. The Policy cannot be issued</b>");
                    exit('ERROR Customer has cancer. The Policy cannot be issued.'); // valid 4
                } 
                // valid 5
                $coversListIsEmpty = empty(array_filter($coversSelected, function ($a) { return $a !== null; }));
                if($coversListIsEmpty){
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Please select at least one insurance cover</b>");
                    exit('ERROR Please select at least one insurance cover'); 
                }
                // mandatory covers check .. valid 6
                $sqlMandatoryCovers = $connection->query("select premium_part name from product_premium_part where relation_code = 1");
                $mandatoryCovers = array();
                while($sqlResult = $sqlMandatoryCovers->fetch_assoc()) array_push($mandatoryCovers, $sqlResult['name']);
                // searching for the same values with the array_intersect(), then checking if the same values number is equal to the mandatory cover number 
                $mandatoryCoversAreSelected = strval(count(array_intersect($mandatoryCovers, $coversSelected)) === count($mandatoryCovers));
                if(!$mandatoryCoversAreSelected) {
                    exit('ERROR Please select all mandatory covers according to the product setup');
                } 
                if($customerHeight == null || $customerWeight == null) { // valid 7
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Please input customer height and weight</b>");
                    exit('ERROR Please input customer height and weight'); 
                } else {
                    $customerHeight = $customerHeight / 100;
                    $customerBMI = number_format($customerWeight / ($customerHeight*$customerHeight), 2);
                    $sqlSaveBMI = $connection->prepare("update policy_object_details set policyholder_bmi = ? where policy_serial = ?");
                    $sqlSaveBMI->bind_param('ss', $customerBMI, $policySerial);
                    $sqlSaveBMI->execute();
                }
                // getting product multipliers START
                $sqlParamSport = $customerXtremeSportsParam == 'yes' ? 'value_first' : 'value_second'; // sql column name according to the selected Extreme sport param on policy 
                if($customerSmokerStatusParam == '0') $sqlParamSmoker = 'value_first'; // sql column name according to the selected Smoker status param on policy 
                if($customerSmokerStatusParam == '1') $sqlParamSmoker = 'value_second';
                if($customerSmokerStatusParam == '2') $sqlParamSmoker = 'value_third';
                if($customerSmokerStatusParam == '3') $sqlParamSmoker = 'value_fourth';

                $sqlGetBMImultiplier = $connection->query("select value multiplier from tariff_bmi where range_start <= $customerBMI and $customerBMI <= range_end");
                $sqlGetXtremeSportMultiplier = $connection->query("select $sqlParamSport multiplier from tariff_sport_smoker where name = 'Extreme sports'");
                $sqlGetSmokerMultiplier = $connection->query("select $sqlParamSmoker multiplier from tariff_sport_smoker where name = 'Smoker status'");
                
                $bmiMultiplier = $sqlGetBMImultiplier->fetch_assoc()['multiplier'];
                $extremeSportsMultiplier = $sqlGetXtremeSportMultiplier->fetch_assoc()['multiplier'];
                $smokerMultiplier = $sqlGetSmokerMultiplier->fetch_assoc()['multiplier'];
                // getting product multipliers END
                $coverAndPrice = array();
                foreach($coversSelected as $cover){
                    if($cover != null){
                        $sqlGetCoverValue = $connection->query("select premium_part name, value price from tariff_base_rates where premium_part = '$cover'");
                        $sqlResult = $sqlGetCoverValue->fetch_assoc();
                        array_push($coverAndPrice, $sqlResult['name'], $sqlResult['price']);
                    }

                }
                // calculation steps
                $newLineSymbol = "<br>";
                $totalPremiumCalculationSteps = "BMI multiplier: <b>" . $bmiMultiplier."</b>" . $newLineSymbol;
                $totalPremiumCalculationSteps .= "Extreme sports multiplier: <b>" . $extremeSportsMultiplier."</b>"  . $newLineSymbol;
                $totalPremiumCalculationSteps .= "Smoker multiplier: <b>" . $smokerMultiplier ."</b>" . $newLineSymbol;
                $totalPremiumCalculationSteps .= $newLineSymbol;
                // START multiplying according to the BMI tariff and sport & smoker tariff 
                $sum = 0;
                $totalPremium = 0;
                for($index = 0; $index < sizeof($coverAndPrice); $index++){ 
                    if($index % 2 != 0) { // prices
                        $indexCopy = $index;
                        $totalPremiumCalculationSteps .= 
                            $coverAndPrice[--$indexCopy] . ' (cover) = ' . $coverAndPrice[$index] . " * " . $bmiMultiplier . " * " . $extremeSportsMultiplier . " * ". $smokerMultiplier . "\n";
                        $sum += $coverAndPrice[$index] * $bmiMultiplier * $extremeSportsMultiplier * $smokerMultiplier;
                        $totalPremiumCalculationSteps .= " = <b>".number_format($sum, 2)."</b> (monthly)" . $newLineSymbol;
                        $totalPremium += $sum;
                        $sum = 0;
                    }
                }
                $totalPremium *= $countMonthsBetween; 
                $totalPremiumCalculationSteps .= $newLineSymbol."Policy Period is <b>$countMonthsBetween</b> month(s)";
                // END multiplying according to the BMI tariff and sport & smoker tariff start 
                $sqlSaveTotalPremiumCalc = $connection->query("update policy set total_premium = '$totalPremium', calculation_steps = '$totalPremiumCalculationSteps' where id = $policySerial");
                scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>".getReturnMessage('success')."</b>");
                scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b>. Total Premium = ".$totalPremium);
                scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b>. Total Premium Calculation: ".$newLineSymbol.$totalPremiumCalculationSteps);
                $sqlUpdatePolicyRecord = $connection->prepare("update policy set calculated = 1 where id = ?");
                $sqlUpdatePolicyRecord->bind_param('s', $policySerial);
                if($sqlUpdatePolicyRecord->execute()){
                    exit(getReturnMessage('success'));
                }
                exit('Severe issue!'.getReturnMessage('dbError'));

            }else if($policyAction == 'cancel'){ // action cancel
                $processName = 'POLICY CANCEL';
                // saving policy details at first
                $policyWasSaved = false;
                $sqlUpdatePolicyRecord = $connection->prepare("update policy set start_date = ?, end_date = ?, cancel_reg_date = ?, effective_reg_date = ?, termination_cause = ?,
                    customer_serial = ?, calculated = 0, first_cover = ?, second_cover = ?, third_cover = ?, product_option = ?, last_updated=now() where id = ?");
                $sqlUpdatePolicyRecord->bind_param('sssssssssss', $startDate, $endDate, $cancelRegDate, $effectiveCancelRegDate, $terminationCause, $customerSerial, 
                    $coversSelected[0], $coversSelected[1], $coversSelected[2], $productOption, $policySerial);
                $sqlUpdatePolicyObjectRecord = $connection->prepare('update policy_object_details set policyholder_height_cm=?, 
                    policyholder_weight_kg=?, policyholder_cancer_yn = ?, policyholder_extreme_sports_yn=?, policyholder_smoker_status_code=? where policy_serial = ?');
                $sqlUpdatePolicyObjectRecord->bind_param('ssssss', $customerHeight, $customerWeight, $customerCancerParam, $customerXtremeSportsParam, $customerSmokerStatusParam, 
                    $policySerial);
                if($sqlUpdatePolicyRecord->execute() && $sqlUpdatePolicyObjectRecord->execute()) $policyWasSaved = true;
                //
                if($policyWasSaved){
                    if($cancelRegDate == '' || $effectiveCancelRegDate == '' || $terminationCause == '') {
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Please populate policy Cancel dates and Termination cause</b>");
                        exit('ERROR Please populate policy Cancel dates and Termination cause'); // valid 1
                    }
                    if(trim($endDate) != trim($effectiveCancelRegDate)) {
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Policy effective cancel date is not equal policy end date</b>");
                        exit('ERROR Policy effective cancel date is not equal policy end date'); // valid 2
                    }
                    $sqlValidatePolicyStatusActive = $connection->query("select status from policy where id = $policySerial and status = 'Active'");
                    if($sqlValidatePolicyStatusActive->num_rows > 0) {
                        $sqlCancelPolicy = $connection->prepare("update policy set status = 'Canceled', cancel_reg_date = ?, effective_reg_date = ?, 
                        termination_cause = ?  where id = ?");
                        $sqlCancelPolicy->bind_param('ssss', $cancelRegDate, $effectiveCancelRegDate, $terminationCause, $policySerial);
                        if($sqlCancelPolicy->execute()) {
                            // here need to send appropriate policy doc from policy_document + product logo
                            
                            // subject: TestPolicy CANCELLATION – [polises ID]
                            // body:
                            // Dear [polises apdrošināšanas ņēmēja vārds],
                            // Your policy № [polises ID] was cancelled.
                            // Cancellation reason: [polises anulēšanas iemesls]
                            // Respectfully yours,
                            // TestPolicy

                            exit('success');
                        }
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: ".getReturnMessage('dbError'));
                        exit(getReturnMessage('dbError'));
                    }else {
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: <b>Policy is not active</b>");
                        exit('ERROR Policy is not active');
                    }
                }
                scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: Severe issue! ".getReturnMessage('dbError'));
                exit('Severe issue!'.getReturnMessage('dbError'));
            }else if($policyAction == 'activate'){ // action activate
                $sqlValidatePolicyInStatusNew = $connection->query("select id from policy where id = $policySerial and status = 'New'");
                if($sqlValidatePolicyInStatusNew->num_rows == 0) {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: Policy must have status of 'New'");
                    exit('ERROR Policy must have status of "New"'); // valid 1
                }
                $sqlCheckIfPolicyCalculated = $connection->query("select calculated from policy where id = $policySerial and calculated = 1");
                if($sqlCheckIfPolicyCalculated->num_rows == 0) {
                    scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned: Policy is not calculated");
                    exit('ERROR Policy is not calculated'); // valid 2
                }
                else {
                    $_GET['policySerial'] = trim($policySerial); 
                    require('createPolicyDocument.php'); // create policy document
                    $result = sendPolicyDocumentToUserEmail($connection, 'activate', $policySerial, $startDate, $endDate); // send policy document + logo + gtc to policyholder 
                    $sqlActivatePolicy = $connection->query("update policy set status = 'Active' where id = $policySerial");
                    if($result){
                        $sqlPolicyHolderEmail = $connection->query("select (select email from customer where id = customer_serial) email from policy");
                        $email = $sqlPolicyHolderEmail->fetch_assoc()['email'];
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned SUCCESS. Policy was activated");
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy Document was created and sent to policyholder email: <b>$email</b>");
                    } else {
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy action: <b>$policyAction</b> returned SUCCESS. Policy was activated'");
                        scriptLog($connection, $processName, getLoggedInUsername($connection), "Policy Document was NOT sent to policyholder - empty email");
                    }
                }
            }
        }
    }
?>