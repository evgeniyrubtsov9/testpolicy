<?php
    $path = 'D:\openserver\domains\testpolicy';
    include_once($path . '\PHP Utility Functions\phpUtilityFunctions.php');
    include_once($path . '\PHP CRUD functions\phpCrudFunctions.php');
    include_once('database.php'); // no need for a long path, since database.php is in the same folder as index.php
    session_start();
    verifyIfUserIsLoggedIn();
    invokeUtilityFunctions($connection);
    invokeProductTariffFunctions($connection); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Test Policy</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="/styles/index_page.css">
    <link rel="stylesheet" href="/styles/product_tariff_params.css">
    <link rel="stylesheet" href="/styles/auth_page.css">
</head>
<body style='background-color: white;'>
    <?php include_once('common/navbar.php'); ?>
    <div class='container-lg'>
        <legend><strong>LIFE</strong> product: <a id='productSetup' href='product'>Tariff Parameters</a></legend>
        <div class='row' style='padding-left: 15%;'>
            <div class="col-sm-5" id='BaseRates'>
                <table id='tableBaseRates' class='table table-bordered'>
                    <label>Base rates tariff</label>
                    <p>Base rates for each cover of the product</p>
                    <div class="loadingSymbol"></div>
                    <span id='tableBaseRatesMsg'></span>
                    <thead>
                        <tr>
                            <th>Premium Part</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "select premium_part, value from tariff_base_rates";
                            $sqlTariffBaseRates = $connection->query($sql);
                            if($sqlTariffBaseRates->num_rows > 0){
                                while($row = $sqlTariffBaseRates->fetch_assoc()){
                                    echo '<tr>
                                            <td style="width: 200px;">'.$row['premium_part'].'</td>
                                            <td><input class="form-control" type="number" value="'.$row['value'].'"></input></td>
                                        </tr>';
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class='col-sm-5' id='MaxAge'>
                <table id='tableMaxAge'class='table table-bordered'>
                    <label>Max Age tariff</label>
                    <p>Maximal & Minimal age for the given policy option</p>
                    <div class="loadingSymbol"></div>
                    <span id='tableMaxAgeMsg'></span>
                    <thead>
                        <tr>
                            <th>Option</th>
                            <th>Parameter</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "select option, parameter_first, parameter_second, value_first, value_second from tariff_age";
                            $sqlTariffMaxAge = $connection->query($sql);
                            if($sqlTariffMaxAge->num_rows > 0){
                                $arrayWithValues = array();
                                while($row = $sqlTariffMaxAge->fetch_assoc())
                                    array_push($arrayWithValues, $row['option'], $row['parameter_first'], $row['value_first'], $row['parameter_second'], $row['value_second']);
                                for($index = 0; $index < sizeof($arrayWithValues); $index++){
                                    echo "
                                    <tr>
                                        <td rowspan='2' style='vertical-align: middle'>".$arrayWithValues[$index]."</td>
                                        <td>".$arrayWithValues[++$index]."</td>
                                        <td><input class='form-control' value='".$arrayWithValues[++$index]."' type='number'></input></td>
                                    </tr>
                                    <tr>
                                        <td>".$arrayWithValues[++$index]."</td>
                                        <td><input class='form-control' value='".$arrayWithValues[++$index]."' type='number'></input></td>
                                    </tr>
                                    ";
                                }
                            }?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class='row' style='padding-left: 15%;'>
            <div class="col-sm-5" id='SumInsured'>
                <table id='tableSumInsured' class='table table-bordered'>
                    <label>Sum Insured tariff</label>
                    <p>Amount agreed on <b>Sum Insured</b> during purchasing the policy will be the maximum amount the customer receive for a <b>valid</b> insurance case</p>
                    <div class="loadingSymbol"></div> 
                    <span id='tableSumInsuredMsg'> </span>
                    <thead>
                        <tr>
                            <th>Age</th>
                            <th>Premium Part</th>
                            <th>Sum Insured</th>
                        </tr>
                    </thead>
                    <tbody>             
                        <?php 
                            $sql = 'select age_range_start, age_range_end,
                            cover_first, cover_second, cover_third, 
                            value_cover_first, value_cover_second, value_cover_third
                            from tariff_sum_insured';
                            $sqlTariffSumInsured = $connection->query($sql);
                            if($sqlTariffSumInsured->num_rows > 0){
                                $arrayWithValues = array();
                                while($row = $sqlTariffSumInsured->fetch_assoc())
                                    array_push($arrayWithValues, $row['age_range_start'],   $row['age_range_end'], 
                                    $row['cover_first'], $row['value_cover_first'],                        
                                    $row['cover_second'], $row['value_cover_second'],
                                    $row['cover_third'], $row['value_cover_third']
                                );
                            }
                            for($index = 0; $index < sizeof($arrayWithValues); $index++){
                                echo '
                                    <tr>
                                        <td style="vertical-align: middle; text-align: center;" rowspan="3">Number between<br>
                                            <input type="number" class="form-control" value="'.$arrayWithValues[$index].'"></input> - 
                                            <input type="number" class="form-control" value="'.$arrayWithValues[++$index].'"></input>
                                        </td>
                                        <td style="vertical-align: middle; text-align: center;">'.$arrayWithValues[++$index].'</td>
                                        <td><input type="number" class="form-control" value="'.$arrayWithValues[++$index].'"></td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle; text-align: center;">'.$arrayWithValues[++$index].'</td>
                                        <td><input type="number" class="form-control" value="'.$arrayWithValues[++$index].'"></td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle; text-align: center;">'.$arrayWithValues[++$index].'</td>
                                        <td><input type="number" class="form-control" value="'.$arrayWithValues[++$index].'"></td>
                                    </tr>';
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class='col-sm-5' id='BMI'>
                <table id='tableBMI' class='table table-bordered' >
                    <label>Policy Parameters: BMI tariff</label>
                    <p>Base rates for policyholders' different body mass indexes</p>
                    <div class="loadingSymbol"></div>
                    <span id='tableBMIMsg'></span>           
                    <thead>
                        <tr>
                            <th>Body Mass Index</th>
                            <th>Multiplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "select range_start rangeStart, range_end rangeEnd, value from tariff_bmi";
                            $sqlTariffBMI = $connection->query($sql);
                            if($sqlTariffBMI->num_rows > 0){
                                $arrayWithValues = array();
                                while($row = $sqlTariffBMI->fetch_assoc())
                                    array_push($arrayWithValues, $row['rangeStart'], $row['rangeEnd'], $row['value']);
                                for($index = 0; $index < sizeof($arrayWithValues); $index++){
                                    echo '<tr>
                                            <td>
                                                Number between
                                                <input class="form-control" type="number" value='.$arrayWithValues[$index].'></input> - 
                                                <input class="form-control" type="number" value='.$arrayWithValues[++$index].'></input>
                                            </td>
                                            <td>
                                                <input class="form-control" type="number" value='.$arrayWithValues[++$index].'
                                            </td>
                                        </tr>';
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class='col-sm-5' id='PolicyParams'>
                <table id='tablePolicyParams' class='table table-bordered' >
                    <label>Policy parameters: Cancer, Extreme sports, Smoker status tariff</label>
                    <div class="loadingSymbol"></div>
                    <span id='tablePolicyParamsMsg'></span>      
                    <p></p>     
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Parameter</th>
                            <th>Multiplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style='vertical-align: middle;' rowspan="2">Cancer</td>
                            <td>Yes</td>
                            <td><input class='form-control' type='number' value='1.5'></input></td>
                        </tr>
                        <tr style='border-bottom: double;'>
                            <td>No</td>
                            <td><input class='form-control' type='number' value='1'></input></td>
                        </tr>


                        <tr>
                            <td style='vertical-align: middle;' rowspan="2">Extreme sports</td>
                            <td>Yes</td>
                            <td><input class='form-control' type='number' value='1.75'></input></td>
                        </tr>
                        <tr style='border-bottom: double;'>
                            <td>No</td>
                            <td><input class='form-control' type='number' value='1'></input></td>
                        </tr>


                        <tr>
                            <td style='vertical-align: middle;' rowspan="4">Smoker status</td>
                            <td>Never</td>
                            <td><input class='form-control' type='number' value='1'></input></td>
                        </tr>
                        <tr>
                            <td>Not now</td>
                            <td><input class='form-control' type='number' value='1.2'></input></td>
                        </tr>
                        <tr>
                            <td>Less than 40 cigs a day</td>
                            <td><input class='form-control' type='number' value='1.5'></input></td>
                        </tr>
                        <tr>
                            <td>More than 40 cigs a day</td>
                            <td><input class='form-control' type='number' value='1.75'></input></td>
                        </tr>
                    <tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include_once('common/footer.php'); ?>
    <script type="module" src="/JS scripts/JS Customer Functions.js"></script>
    <script type="module" src="/JS scripts/JS Ajax Functions.js"></script>
    <script type="module" src="/JS scripts/JS Product Functions.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
</body>
</html>
