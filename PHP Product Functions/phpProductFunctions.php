<?php 
   /** 
    * Author: Jevgenijs Rubcovs LUDF
    * Library: Php Utility functions
    * Version: 1.0
    * Description: Library stores basic CRUD (create remove update delete) functions in PHP
    */
    $path = 'D:\openserver\domains\testpolicy';
    include_once($path . '\PHP Utility Functions\phpUtilityFunctions.php');
    function updateProductSetup($connection){
        if(isset($_POST['product_form'])){
            $deathCoverOptionSelected = $_POST['Death'];
            $accidentalDeathOptionSelected = $_POST['AccidentalDeath'];
            $accidentOptionSelected = $_POST['Accident'];
            $sqlUpdateCoverDeathRelation = $connection->prepare("update product_premium_part set relation_code = ? where premium_part = 'Death'");
            $sqlUpdateCoverAccidentalDeathRelation = $connection->prepare("update product_premium_part set relation_code = ? where premium_part = 'Accidental Death'");
            $sqlUpdateCoverAccidentRelation = $connection->prepare("update product_premium_part set relation_code = ? where premium_part = 'Accident'");
            $sqlUpdateCoverDeathRelation->bind_param('s', $deathCoverOptionSelected);
            $sqlUpdateCoverAccidentalDeathRelation->bind_param('s', $accidentalDeathOptionSelected);
            $sqlUpdateCoverAccidentRelation->bind_param('s', $accidentOptionSelected);
            // remove white spaces from both ends of string. Is important, bc check explode() function inside validateProductSetup() 
            $productName = $_POST['product_name'] != '' ? trim($_POST['product_name']) : $_POST['product_name']; 
            $productCommercialDescription = $_POST['product_cd'] != '' ? trim($_POST['product_cd']) : $_POST['product_cd'];
            $productValidFrom = $_POST['product_valid_from'];
            $productValidTo = $_POST['product_valid_to'];
            $productStatus = $_POST['product_status'];
            $validation = validateProductSetup($connection, $productName, $productCommercialDescription, $productValidFrom);
            $sqlUpdateProductSetup = $connection->prepare("
            update product set name=?, commercial_description=?, valid_from=?,
                valid_to=?, status=?, changed_when=localtime(), changed_by='".getLoggedInUsername($connection)."'");
            $sqlUpdateProductSetup->bind_param('sssss', $productName, $productCommercialDescription, $productValidFrom, $productValidTo, $productStatus);
            if($sqlUpdateProductSetup->execute() && $sqlUpdateCoverDeathRelation->execute() && $sqlUpdateCoverAccidentalDeathRelation->execute()
                &&  $sqlUpdateCoverAccidentRelation->execute()) exit('success');
            else exit(getReturnMessage('dbError'));
        }
    }
    function invokeProductTariffFunctions($connection){
        if(isset($_POST['updateTariff'])){
            $processName = 'TARIFF UPDATE';
            $parameter = $_POST['param'];
            if(empty($parameter)) exit(getReturnMessage('dbError'));
            $tariffChangeDetails = explode('_', $parameter); // i.e. tableBaseRates_value_0 -> [0] tableBaseRates_value_0, [1] value, [2] 0 (id in database)
            $tariffName = $tariffChangeDetails[0]; // tariff name
            if(strpos($tariffName, "table") >= 0){
                $tariffName = explode('table', $tariffName); // i.e. tableMaxAge -> [0] table, [1] MaxAge
                $tariffName = $tariffName[1]; // tariff name without 'table' in the name
                $newValue = $_POST['value'];
                switch($tariffName){
                    case 'BaseRates': {
                        if($tariffChangeDetails[2] >= 0 && $tariffChangeDetails[2] <= 2){
                            $id = $tariffChangeDetails[2];
                            $sqlCheckCurrentValue = $connection->query("select value from tariff_base_rates where value = $newValue and id = $id");
                            if($sqlCheckCurrentValue->num_rows == 0) {
                                $sqlUpdateTableBaseRates = $connection->prepare('update tariff_base_rates set value = ? where id = ?');
                                $sqlUpdateTableBaseRates->bind_param('sd', $newValue, $tariffChangeDetails[2]);
                                if($sqlUpdateTableBaseRates->execute()){
                                    $cover;
                                    if($tariffChangeDetails[2] == '0') $cover = 'Death'; 
                                    else if($tariffChangeDetails[2] == '1') $cover = 'Accidental Death';
                                    else $cover = 'Accident';
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Updated cover: <b>'.$cover.'</b> in tariff: <b>'.$tariffName.'</b> with the new value: <b>' .$newValue.'</b>');
                                    exit(getReturnMessage('success'));
                                } else {
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: '.getReturnMessage('dbError'));
                                    exit(getReturnMessage('dbError'));
                                }
                            }else {
                                scriptLog($connection, $processName, getLoggedInUsername($connection), 'User started updating: <b>'.$tariffName.'</b>, but value was not changed');
                                exit('value-was-not-changed');
                            } 
                        }else {
                            scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('dbError').'</b>');
                            exit(getReturnMessage('dbError'));
                        }
                    }
                    case 'MaxAge': {
                        if($newValue < 1) exit(getReturnMessage('minAge'));
                        else if($newValue > 100)  exit(getReturnMessage('maxAge'));
                        if($tariffChangeDetails[2] >= 0 && $tariffChangeDetails[2] <= 3 
                            && ($tariffChangeDetails[3] == 'first' || $tariffChangeDetails[3] == 'second')){
                                $id = ($tariffChangeDetails[2] == 0 || $tariffChangeDetails[2] == 1) ? 0 : 1;
                                //exit('Id: ' . $id);
                                $param = $tariffChangeDetails[3] == 'first' ? 'Minimal Age' : 'Maximal Age';
                                $validationPassed = true;
                                $oppositeParam = $param == 'Minimal Age' ? 'value_second' : 'value_first';
                                $sqlValidate = $connection->query("select $oppositeParam value from tariff_age where id=$id");
                                if($sqlValidate->num_rows > 0){
                                    $row = $sqlValidate->fetch_assoc();
                                    $oppositeValue = $row['value'];
                                    if($param == 'Minimal Age') $validationPassed = $newValue < $oppositeValue ? true : false;
                                    else $validationPassed = $newValue > $oppositeValue ? true : false;
                                }else {
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('dbError').'</b>');
                                    exit(getReturnMessage('dbError')); 
                                }
                                if(!$validationPassed) {
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('ageRange').'</b>');
                                    exit(getReturnMessage('ageRange')); 
                                }
                                $option = $id == 0 ? 'Product for young or adults' : 'Product for the elderly';
                                $sqlCheckCurrentValue = $connection->query("select value_".$tariffChangeDetails[3]." 
                                                                                from tariff_age 
                                                                                where value_".$tariffChangeDetails[3]." = $newValue
                                                                                and id = $id");
                                if($sqlCheckCurrentValue->num_rows != 0) {
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'User started updating: <b>'.$tariffName.'</b>, but value was not changed');
                                    exit('value-was-not-changed');
                                }
                                $sqlUpdateTableMaxAge = $connection->prepare('update tariff_age set value_'.$tariffChangeDetails[3].' = ? where id = ?');
                                $sqlUpdateTableMaxAge->bind_param('ss', $newValue, $id);
                                if($sqlUpdateTableMaxAge->execute()){
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Set parameter: <b>'.$param.'</b> = <b>'.$newValue.'</b> in tariff: <b>'.$tariffName.'</b> for option: <b>'.$option.'</b>');
                                    exit(getReturnMessage('success'));
                                }
                        } else { 
                            scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: '.getReturnMessage('dbError'));
                            exit(getReturnMessage('dbError')); 
                        }
                    }
                    case 'BMI': {
                        $param;
                        if($tariffChangeDetails[1] != 'value' && $newValue < 10 || $newValue > 50) {
                            scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>Impossible BMI value: '.$newValue.'</b>');
                            exit('ERROR Impossible BMI value: '.$newValue);
                        }
                        if($tariffChangeDetails[1] == 'value') $param = $tariffChangeDetails[1];
                        else if($tariffChangeDetails[1] == 'rangeStart') $param = 'range_start';
                        else if($tariffChangeDetails[1] == 'rangeEnd') $param = 'range_end';
                        else {
                            scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: '.getReturnMessage('dbError'));
                            exit(getReturnMessage('dbError')); 
                        } 
                        $id = $tariffChangeDetails[2];
                        $sqlCheckCurrentValue = $connection->query("select $param from tariff_bmi where $param=$newValue and id=$id");
                        if($sqlCheckCurrentValue->num_rows != 0) exit('value-was-not-changed');
                        if($param != 'value'){
                            $oppositValue = $param == 'range_start' ? 'range_end' : 'range_start';
                            $validationPassed = true;
                            $sqlValidate = $connection->query("select $oppositValue as value from tariff_bmi where id=$id");
                            if($sqlValidate->num_rows > 0){
                                $row = $sqlValidate->fetch_assoc();
                                $oppositeValue = $row['value'];
                                if($oppositeValue == $newValue) {
                                    scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>Ranges cannot be equal</b>');
                                    exit('ERROR Ranges cannot be equal');
                                }
                                if($param == 'range_start') $validationPassed = (double)$newValue > (double)$oppositeValue ? false : true;
                                else $validationPassed = (double)$newValue < (double)$oppositeValue ? false : true;
                            }else {
                                scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('dbError').'</b>');
                                exit(getReturnMessage('dbError')); 
                            }
                            if(!$validationPassed) {
                                scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('bmiRange').'</b>');
                                exit(getReturnMessage('bmiRange'));
                            }
                        }
                        $sqlUpdateBMItariff = $connection->query("update tariff_bmi set $param = $newValue where id = $id");
                        $sqlGetCurrentBMIrecordLevel = $connection->query("select range_start start, range_end end from tariff_bmi where id=$id");
                        $section = 'ERROR';
                        if($sqlGetCurrentBMIrecordLevel->num_rows > 0){
                            $row = $sqlGetCurrentBMIrecordLevel->fetch_assoc();
                            $section = $row['start'].' - '.$row['end'];
                        }
                        if($sqlUpdateBMItariff){
                            scriptLog($connection, $processName, getLoggedInUsername($connection), "Updated parameter: <b>$param</b> in tariff: <b>$tariffName</b> with the new value: <b>$newValue</b>.
                            BMI level: <b>($section)</b>");
                            exit(getReturnMessage('success'));
                        }
                        scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('dbError').'</b>');
                        exit(getReturnMessage('dbError')); 
                    }
                    case 'SumInsured': {
                        if($tariffChangeDetails[1] != 'value' && $newValue < 1) exit(getReturnMessage('minAge'));
                        else if($tariffChangeDetails[1] != 'value' && $newValue > 100) exit(getReturnMessage('maxAge'));
                        $id = $tariffChangeDetails[2];
                        $option = $tariffChangeDetails[1];
                        $validationPassed = true;
                        if($tariffChangeDetails[1] != 'value'){
                            $oppositeParam = $tariffChangeDetails[1] == 'rangeStart' ? 'age_range_end' : 'age_range_start';
                            $sqlValidate = $connection->query("select $oppositeParam value from tariff_sum_insured where id = $id");
                            if($sqlValidate->num_rows > 0){
                                $row = $sqlValidate->fetch_assoc();
                                $oppositeValue = $row['value'];
                                if($oppositeParam == 'age_range_end') $validationPassed = $newValue < $oppositeValue ? true : false;
                                else $validationPassed = $newValue > $oppositeValue ? true : false;
                            }
                            if(!$validationPassed) {
                                scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('ageRange').'</b>');
                                exit(getReturnMessage('ageRange')); 
                            }
                        }
                        $param = $tariffChangeDetails[1] != 'value' ? ($oppositeParam == 'age_range_end' ? 'age_range_start' : 'age_range_end') : ( 'value_cover_'.$tariffChangeDetails[3] );
                        $cover;
                        if($param == 'value_cover_first') $cover = 'Death';
                        if($param == 'value_cover_second') $cover = 'Accidental Death';
                        if($param == 'value_cover_third') $cover = 'Accident';
                        $sqlCheckCurrentValue = $connection->query("select $param from tariff_sum_insured where id = $id and $param = $newValue");
                        if($sqlCheckCurrentValue->num_rows != 0) {
                            scriptLog($connection, $processName, getLoggedInUsername($connection), 'User started updating: <b>'.$tariffName.'</b>, but value was not changed');
                            exit('value-was-not-changed');
                        }
                        $sqlUpdateTableSumInsured = $connection->prepare("update tariff_sum_insured set $param = ? where id = ?");
                        $sqlUpdateTableSumInsured->bind_param('ss', $newValue, $id);
                        if($sqlUpdateTableSumInsured->execute()){
                            $sqlGetAgeRange = $connection->query("select age_range_start start, age_range_end end from tariff_sum_insured where id=$id");
                            $ageRange;
                            if($sqlGetAgeRange->num_rows > 0){
                                $row = $sqlGetAgeRange->fetch_assoc();
                                $ageRange = $row['start'].'-'.$row['end'];
                            }
                            if($tariffChangeDetails[1] == 'value')
                                scriptLog($connection, $processName, getLoggedInUsername($connection), "Set new value = <b>$newValue</b> for cover: <b>$cover</b> in tariff: <b>$tariffName</b> (Age range: $ageRange)");
                            else scriptLog($connection, $processName, getLoggedInUsername($connection), "Updated Age <b>$option</b> = <b>$newValue</b> for tariff: <b>$tariffName</b>. (New age range: $ageRange)");
                            exit(getReturnMessage('success'));
                        }else {
                            scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update tariff: <b>'.$tariffName.'</b>. Reason: '.getReturnMessage('dbError'));
                            exit(getReturnMessage('dbError')); 
                        }
                    }
                    default: {
                        scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update: <b>'.$tariffName.'</b>. Reason: <b>ERROR tariff table not found</b>');
                        exit('ERROR tariff table not found');
                    }
                }
            }else {
                scriptLog($connection, $processName, getLoggedInUsername($connection), 'Failed to update: <b>'.$tariffName.'</b>. Reason: <b>'.getReturnMessage('dbError').'</b>');
                exit(getReturnMessage('dbError')); 
            }
        }
    }
?>