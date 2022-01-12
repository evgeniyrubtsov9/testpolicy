<?php
if(!isset($_SESSION['loggedIn'])) {
    header('Location: auth');
    exit();
}
$policySerial = $_GET['policySerial'];
if($policySerial != null){
    require('fpdf.php');
    $sql = "select distinct p.name, p.commercial_description, date_format(pol.start_date, '%d.%m.%Y') start_date, date_format(pol.end_date, '%d.%m.%Y') end_date, 
    (select concat(name, ' ', surname, '-', flex_text_1) from customer where id = pol.customer_serial) customer_data, pol.calculation_steps, 
    pol.total_premium, pol.currency, pod.policyholder_bmi bmi, pod.policyholder_extreme_sports_yn sport, 
    (select name from smoker_status where id = pod.policyholder_smoker_status_code) smoker from policy_object_details pod, customer c, 
    product p, policy pol where pol.id = ? and pod.policy_serial = ?";
    $sqlGetPolicyDetails = $connection->prepare($sql);
    $sqlGetPolicyDetails->bind_param('ss', $policySerial, $policySerial);
    $sqlGetPolicyDetails->execute();
    $sqlResult = $sqlGetPolicyDetails->get_result();
    while($row = $sqlResult->fetch_assoc()){ 
        $productName = $row['name'];
        $productDescription = $row['commercial_description'];
        $policyStartDate = $row['start_date'];
        $policyEndDate = $row['end_date'];
        $customerData = $row['customer_data'];
        $customerNameSurname = preg_split('~-~', $customerData)[0];
        $customerId = preg_split('~-~', $customerData)[1];
        $calculationSteps = $row['calculation_steps'];
        $totalPremium = $row['total_premium'] . ' '. $row['currency'];
        $bmi = $row['bmi'];
        $sport = $row['sport'];
        $smoker = $row['smoker'];
    }
    $calculationSteps = str_replace('<b>', '', $calculationSteps);
    $calculationSteps = str_replace('</b>', '', $calculationSteps);
    $calculationSteps = str_replace('<br>', "\n", $calculationSteps);
    $pdf=new FPDF();
    $pdf->SetAuthor('TestPolicy'); //set document properties
    $pdf->SetTitle('Policy Document ' .$policySerial);
    $pdf->SetFont('Helvetica','B',20); //set font for the entire document
    $pdf->SetTextColor(0, 0, 0);
    $pdf->AddPage('P'); //set up a page
    $pdf->SetDisplayMode(real, 'default'); //display the title with a border around it
    $pdf->SetXY(50,20);
    $pdf->SetDrawColor(50,60,100);
    $pdf->Cell(100,10, $productDescription, 0, 0, 'C', 0);
    //Set x and y position for the main text, reduce font size and write content
    $pdf->SetXY (10,50);
    $pdf->SetFontSize(14);
    $pdf->Write(5, "Customer: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$customerNameSurname \n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Customer National ID: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$customerId \n\n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Policy No: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$policySerial \n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Start Date: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$policyStartDate \n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "End Date: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$policyEndDate \n\n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "BMI: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$bmi \n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Extreme Sports: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$sport \n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Smoker status: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$smoker \n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Premium Calculation: \n\n");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$calculationSteps \n\n");
    $pdf->SetFont('Helvetica','B');
    $pdf->Write(5, "Total Premium: ");
    $pdf->SetFont('Helvetica','I');
    $pdf->Write(5, "$totalPremium \n");
    $policyDocumentName = "TestPolicy_$policySerial.pdf";
    $content = $pdf->Output($policyDocumentName, 'S'); // Save the document in the database (flag S)
    $sql = "insert into policy_document(policy_serial, content, name, type, size) values('$policySerial', '".addslashes($content)."', '$policyDocumentName', '-', '-')";
    $connection->query($sql);
}
?>
