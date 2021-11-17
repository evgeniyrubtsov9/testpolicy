<div class="container-lg" id="customerTable">
    <div class="table-responsive">
        <div class="table-title">
            <div class="row">
                <div class="col-sm-8">
                    <h2>Customer</h2>
                    <span id='customersAmount'><?php 
                        $customersAmount = $connection->query('select count(*) customersAmount from customer');
                        if($customersAmount->num_rows > 0){
                            while($row=$customersAmount->fetch_assoc()) {
                                echo $row['customersAmount'];
                                break;
                            }
                        }
                    ?> customers</span>
                </div>
                <div class="col-sm-4">
                    <button type="button" class="add-new">Add New</button>
                    <button type="button" id='btnExportCustomersToXls'>Export</button>
                </div>
            </div>
        </div>
        <div class="loadingSymbol" style='display: block; text-align: center'></div>
        <table class="table table-bordered" id='dataTable' style='display: none;'>
            <thead>
                <tr>
                    <th>Serial</th>
                    <th>Name</th>
                    <th>Surname</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Date of Birth</th>
                    <th>Country</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Flex Text 1</th>
                    <th id='thAction'>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php // sql to get customers and their countries and statuses                      
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
                    $customersAmount;
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $customersAmount = $row['customersAmount'];
                            $gender = returnGenderByCode($row['gender']);
                            $dateOfBirth = $row['date_of_birth'] != '0000-00-00' ? $row['date_of_birth'] : null; // 0000-00-00 means that date was not selected when creating the customer
                            echo '<tr>'; // Retrieve table data out of the sql result...
                            echo    '<td>'.$row['customerId'].'</td>', // href="policy?serial='.$row['policySerial'].'"
                                    '<td id="customer_'.$row['customerId'].'">'.$row['customerName'].'</td>', 
                                    '<td id="tdSurname">'.$row['surname'].'</td>',
                                    '<td id="tdEmail">'.$row['email'].'</td>',
                                    '<td id="tdAddress">'.$row['address'].'</td>',
                                    '<td id="tdDateOfBirth">'.$dateOfBirth.'</td>',
                                    '<td id="tdCountryName" value="'.$row['countryCode'].'">'.$row['countryName'].'</td>',
                                    '<td id="tdGender" value="'.$row['gender'].'">'.$gender.'</td>',
                                    '<td id="tdCustomerStatus" value="'.$row['custStatusCode'].'">'.$row['customerStatus'].'</td>',
                                    '<td>'.$row['flex_text_1'].'</td>';
                            // Actions Add/Edit/Remove:
                            echo    '<td>
                                        <a id="add" class="add"><span class="glyphicon glyphicon-plus"></span></a>
                                        <a id="update" class="update"><span class="glyphicon glyphicon-ok"></span></a>
                                        <a id="editCustomer_'.$row['customerId'].'" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <a class="delete"><span class="glyphicon glyphicon-remove-sign"></span></a>
                                    </td>
                                </tr>';
                       }
                    } else scriptLog($connection, 'TYPE=PHP INFO', $user, 'Customers not found in database'); 
                  ?>
            </tbody>
        </table>
    </div>
</div>