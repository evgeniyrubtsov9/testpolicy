/** 
 * Author: Jevgenijs Rubcovs LUDF
 * Library: JS scripts, JS Customer Functions.js
 * Version: 1.0
 * Description: Library stores Customer related functions
 */
import { validateEmail, validateName, retrieveCustomerId, isNullSafe, currentLink, invokeBasicCustomerSetupFunctions } from "./JS Utility Functions.js";
import { ajaxAddOrUpdateCustomer, ajaxRemoveCustomer, ajaxGetLogData, ajaxDownloadLogFile, ajaxLogResult, ajaxClearLogfile } from "./JS Ajax Functions.js";
export var localDatetime = new Date().toLocaleString('sv-SE'); // date in format YYYY-MM-DD h:m:s
export var loggedInUser = isNullSafe($('#loggedInUser').html()) ? $('#loggedInUser').html().trim() : 'User not authenticated yet'; // used in the ajaxLogResult
$('#dataTable').toggle()
$(document).ready(function() {
    invokeBasicCustomerSetupFunctions();
    $(".add-new").click(function(){ // ----------------------- Append a new row to the table on 'Add new' button click start -----------------------
        $('#dataTable_filter > label > input').attr("disabled", "disabled")
        $('#dataTable_length > label > select').attr("disabled", "disabled")
        $('#dataTable_paginate').toggle()
        $(this).attr("disabled", "disabled"); // disable 'Add new' button until a new row wouldn't be added
        $("#btnExportCustomersToXls").attr("disabled", "disabled");
        $(document).find('.edit, .delete').toggle(); // disable edit and delete buttons for other rows except current
        var action = $("table td:last-child").html(); // in this case last child contains 'add' button
        var rowPart1 = '<tr> \
                            <td></td> \
                            <td><input type="text" class="form-control" name="name"></td> \
                            <td><input type="text" class="form-control" name="surname"></td> \
                            <td><input type="text" class="form-control" name="email"></td> \
                            <td><input type="text" class="form-control" name="address"></td> \
                            <td><input type="date" class="form-control" name="dateOfBirth"></td> \
                            <td><select id="selectCountries" class="form-control">';
                                // select options are being added between two parts. Options are got from db using ajax
        var rowPart2 =      '</select></td> \
                            <td> \
                                <select id="selectGender" class="form-control"> \
                                    <option value="M">Male</option> \
                                    <option value="F">Female</option> \
                                    <option value="N">N/A</option> \
                                </select> \
                            </td> \
                            <td> \
                                <select id="selectStatus" class="form-control"> \
                                    <option value="1">New</option> \
                                    <option value="2">Active</option> \
                                    <option value="3">Blacklisted</option> \
                                    <option value="4">Other</option> \
                                </select> \
                            </td> \
                            <td><input type="text" class="form-control" name="flex_text_1""></td> \
                            <td>' + action + '</td> \
                        </tr>';
        $.ajax({
            url: 'index',
            method: 'GET', // no need to change or add anything, so using GET method 
            data: {
                getCountryList: 1,
            },
            success: function(response){
                var response = response.split(',');
                if(response.length > 0){
                    for(var i = 0; i < response.length-1; i++){ // response is array with countries; last two symbols are country code, i.e. Latvia LV
                        rowPart1 += // retrieving data from response accordingly
                        '<option class="form-control" \
                            value="' + response[i].slice(-2).trim() +'"\
                            name="country">' + (response[i].slice(0,-2)).trim() 
                      + '</option>'; 
                    }
                    var append = rowPart1 + rowPart2;
                    $("table").prepend(append); // add row in the beginning of the table
                    $("table tbody tr:first-child").find(".add").toggle();
                }
            },
            dataType: 'text'
        });
    });// ----------------------- Append a new row to the table on 'Add new' button click end -----------------------
	$(document).on("click", ".add", function(){ // ----------------------- Add row on add button click start -----------------------
        var firstName = $(this).parents("tr").find('input[type="text"]').eq(0).val(); 
        var surname = $(this).parents("tr").find('input[type="text"]').eq(1).val();
        var email = $(this).parents("tr").find('input[type="text"]').eq(2).val();
        var validationPassed = true;
        if(isNullSafe(firstName) && isNullSafe(surname)){
            if(!validateName(firstName) || !validateName(surname)) {
                $('#custReturnMsg').html('Customer first name or/and surname contain prohibited symbols!') 
                validationPassed = false;
            }
        } else {
            $('#custReturnMsg').html('Customer first name or/and surname is empty!');
            validationPassed = false;
        }
        if(isNullSafe(email)) {
            if(!validateEmail(email)) {
                $('#custReturnMsg').html('Incorrect email format!')
                 validationPassed = false;
            }
        }
        if(validationPassed){
            $('#custReturnMsg').css({'display' : 'none'});
            $(".add-new").removeAttr("disabled");
            $("#btnExportCustomersToXls").removeAttr("disabled");
            var createdByToDatabase = loggedInUser;
            var nameToDatabase = firstName;
            var surnameToDatabase = surname;
            var emailToDatabase = email;
            var addressToDatabase = $(this).parents("tr").find('input[type="text"]').eq(3).val();
            var datebirthToDatabase = $(this).parents("tr").find('input[type="date"]').eq(0).val();
            var countryToDatabase = $(this).parents("tr").find('select option:selected').eq(0).val();
            var genderToDatabase = $(this).parents("tr").find('select option:selected').eq(1).val();
            var statusToDatabase = $(this).parents("tr").find('select option:selected').eq(2).val();
            var flexText1 = $(this).parents("tr").find('input[type="text"]').eq(4).val();
            var inputText = $(this).parents("tr").find('input[type="text"]');
            inputText.each(function(){ $(this).parent("td").html($(this).val()); }); // Saving text of "input type='text'" and their selected options
            var inputDate = $(this).parents("tr").find('input[type="date"]').eq(0) 
                                .replaceWith(
                                    "<td>" 
                                  + $(this).parents("tr").find('input[type="date"]').eq(0).val()
                                  + "</td>");
            // ugly way, need to fix...
            var selectCountry = $(this).parents("tr").find('select').eq(0)
                                .replaceWith(
                                    "<td>" 
                                    + $(this).parents("tr").find('select option:selected').eq(0).html() 
                                    + "</td>");
            
            var selectGender = $(this).parents("tr").find('select').eq(0)
                                .replaceWith(
                                    "<td>" 
                                    + $(this).parents("tr").find('select option:selected').eq(0).html() 
                                    + "</td>");
            var selectStatus = $(this).parents("tr").find('select').eq(0)
                                .replaceWith(
                                    "<td>" 
                                    + $(this).parents("tr").find('select option:selected').eq(0).html() 
                                    + "</td>");
            ajaxAddOrUpdateCustomer('add',  nameToDatabase, 
                                            surnameToDatabase, 
                                            emailToDatabase, 
                                            addressToDatabase, 
                                            datebirthToDatabase, 
                                            countryToDatabase,
                                            genderToDatabase, 
                                            statusToDatabase,
                                            createdByToDatabase,
                                            flexText1,
                                            null);
            $(this).parents("tr").find(".add").toggle();
            $(document).find('.edit, .delete').toggle();
        } //else return;
    }); // ----------------------- Add row on add button click end -----------------------
    $(document).on("click", ".edit", function(){ // ----------------------- Edit row on edit button click start ----------------------- 
        var customerFullName = $(this).parents("tr").find("td:nth-child(1)").html() + ' ' + $(this).parents("tr").find("td:nth-child(2)").html() 
                            + " " + $(this).parents("tr").find("td:nth-child(3)").html()
        $("#btnExportCustomersToXls").attr('disabled', 'disabled'); // set attribute disabled <=> make the button inactive for clicking on it
        $(".add-new").attr('disabled', 'disabled');
        $(this).parents("tr").find(".update").toggle();
        $(document).find('.edit, .delete').toggle(); // this toggle will remove edit, delete buttons until next toggle. The logic behind: it is possible to edit only one customer at a time
        for(var nthChild = 2; nthChild <= 10; nthChild++){ // for input type Text
            if(nthChild == 6 || nthChild == 7 || nthChild == 8 || nthChild == 9) continue; // ugly way to skip inputs whose type is not 'text'
            $(this).parents("tr").find("td:nth-child(" + nthChild +")").each(function(){
                $(this).html('<input type="text" class="form-control" value="' + $(this).text() + '">');
            });
        }
        $(this).parents("tr").find("td:nth-child(6)").each(function(){ // for input type Date
            var birthdate = $(this).parents('tr').find('#tdDateOfBirth').text();
            $(this).html('<input type="date" class="form-control" value="' + $(this).text().substring(0,10).split("-").reverse().join("-") + '">');
        });
        $(this).parents("tr").find("td:nth-child(7)").each(function(){ // for input type Select country
            var currentCountry = $(this).parents('tr').find('#tdCountryName').attr('value');
            var options = "<select id='selectCountry' class='form-control'></select>";
            $(this).html(options);
            $.ajax({ // ajax to get country list from database and fetch it into select country
                url: 'index',
                method: 'GET', // no need to change or add anything, so using GET method 
                data: {
                    getCountryList: 1,
                },
                success: function(response){
                    var response = response.split(','); // split countries into array separating array values by ',' symbol
                    if(response.length > 0){
                        for(var i = 0; i < response.length-1; i++){ // response is array with countries; last two symbols are country code; (Latvia LV)
                            $('#selectCountry').append($('<option>', {
                                value: response[i].slice(-2).trim(),
                                text: (response[i].slice(0,-2)).trim()
                            }));
                        }
                    }
                },
                dataType: 'text',
            }).done(function(){$("#selectCountry").val(currentCountry).change();}) // when ajax request is done, set country option:selected to the customer's current country
        });
        $(this).parents("tr").find("td:nth-child(8)").each(function(){ // for input type Select gender
            var currentGender = $(this).parents('tr').find('#tdGender').text().slice(0,1);
            var options = "<select id='selectGender' class='form-control'> \
                              <option value='M'>Male</option> \
                              <option value='F'>Female</option> \
                              <option value='N'>N/A</option> \
                           </select>"
            $(this).html(options);
            $("#selectGender").val(currentGender).change(); // set gender select option:selected according to the customer's current gender
        });
        $(this).parents("tr").find("td:nth-child(9)").each(function(){ // for input type Select status
            var currentStatus = $(this).parents('tr').find('#tdCustomerStatus').attr("value");
            var options =   '<select id="editStatus" class="form-control"> \
                                <option value="1">New</option> \
                                <option value="2">Active</option> \
                                <option value="3">Blacklisted</option> \
                                <option value="4">Other</option> \
                            </select>';
            $(this).html(options);
            $("#editStatus").val(currentStatus).change(); // set gender select option:selected according to the customer's current status
        });
    });// ----------------------- Edit row on edit button click end -----------------------
    $(document).on('click', '.update', function(){ // ----------------------- Update customer row on update button click start -----------------------
        $(document).find('.edit, .delete').toggle(); // toggling in the whole document
        $(this).parents("tr").find(".update").toggle(); // toggling only in the current (this) table row
        $("#btnExportCustomersToXls").removeAttr('disabled'); // remove attribute disable <=> make the button active for clicking on it
        $(".add-new").removeAttr('disabled');
        var customerSerial = $(this).parents("tr").find("td:nth-child(1)").html()
        if(!isNullSafe($(this).parents("tr").find("td:nth-child(1)").html())) { alert('ERROR. Customer id not found'); return; } // stop the function if customer id is undefined/null
        else {
            //var customerId = retrieveCustomerId(customerFullId);
            var customerId = $(this).parents("tr").find("td:nth-child(1)").html();
            var firstName = $(this).parents("tr").find('input[type="text"]').eq(0).val();
            var surname = $(this).parents("tr").find('input[type="text"]').eq(1).val();
            var email = $(this).parents("tr").find('input[type="text"]').eq(2).val();
            var validationPassed = true;
            if(isNullSafe(firstName) && isNullSafe(surname)){
                if(!validateName(firstName) || !validateName(surname)) {
                    $('#custReturnMsg').html('Customer first name or/and surname contain prohibited symbols!') 
                    validationPassed = false;
                }
            } else {
                $('#custReturnMsg').html('Customer first name or/and surname is empty!');
                validationPassed = false;
            }
            if(isNullSafe(email)) {
                if(!validateEmail(email)) {
                    $('#custReturnMsg').html('Incorrect email format!')
                     validationPassed = false;
                }
            }
            if(validationPassed){
                var changedByToDatabase = loggedInUser;
                var nameToDatabase = firstName;
                var surnameToDatabase = surname;
                var emailToDatabase = email;
                var addressToDatabase = $(this).parents("tr").find('input[type="text"]').eq(3).val();
                var datebirthToDatabase = $(this).parents("tr").find('input[type="date"]').eq(0).val();
                var countryToDatabase = $(this).parents("tr").find('select option:selected').eq(0).val();
                var genderToDatabase = $(this).parents("tr").find('select option:selected').eq(1).val();
                var statusToDatabase = $(this).parents("tr").find('select option:selected').eq(2).val();
                var flexText1 = $(this).parents("tr").find('input[type="text"]').eq(4).val();
                // --- Updating input fields start
                var inputText = $(this).parents("tr").find('input[type="text"]');
                inputText.each(function(){ 
                    $(this).parent("td").html($(this).val()); // Saving text of "input type='text'" and their selected options
                }); 
                var inputDate = $(this).parents("tr").find('input[type="date"]').eq(0).replaceWith("<td>"+ $(this).parents("tr").find('input[type="date"]').eq(0).val() + "</td>");                                                                      
                $(this).parents("tr").find("td:nth-child(" + 7 + ")").each(function(){ // for input type Select country
                    $(this).html($(this).parents("tr").find('select option:selected').eq(0).html());
                });
                $(this).parents("tr").find("td:nth-child(" + 8 + ")").each(function(){ // for input type Select gender
                    $(this).html($(this).parents("tr").find('select option:selected').eq(0).html());
                });
                $(this).parents("tr").find("td:nth-child(" + 9 + ")").each(function(){ // for input type Select status
                    $(this).html($(this).parents("tr").find('select option:selected').eq(0).html());
                }); // --- Updating input fields end
                ajaxAddOrUpdateCustomer('update', nameToDatabase, 
                                                surnameToDatabase, 
                                                emailToDatabase, 
                                                addressToDatabase, 
                                                datebirthToDatabase, 
                                                countryToDatabase,
                                                genderToDatabase, 
                                                statusToDatabase,
                                                changedByToDatabase,
                                                flexText1,
                                                customerId);
            } else {
                $(this).parents("tr").find(".update").toggle(); // in case of validation not passed, do not change update, edit, delete buttons by toggling them again
                $(document).find('.edit, .delete').toggle();
            }
        }
    }); // ----------------------- Update customer row on update button click end -----------------------
    $(document).on("click", ".delete", function(){ // ----------------------- Delete row on delete button click start -----------------------
        var customerFullname = $(this).parents("tr").find("td:nth-child(1)").text() // serial
                        + ' ' + $(this).parents("tr").find("td:nth-child(2)").text() // first name
                        + ' ' + $(this).parents("tr").find("td:nth-child(3)").text() // username
        if(confirm('Remove customer: '  + customerFullname + "?")){
            var customerId = $(this).parents("tr").find("td:nth-child(2)").attr('id');
            customerId = isNullSafe(customerId) ? customerId : null;
            if(isNullSafe(customerId)) {
                var customerId = customerId.split('_'); // split on arrays using the symbol '_' (customer id format: customer_id)
                customerId = customerId[1]; // Retrieve the id number from the whole Id (customer_id)
                ajaxRemoveCustomer(customerId);
                if(!isNaN(customerId)){
                    $(this).parents("tr").remove();
                    $(".add-new").removeAttr("disabled");
                }
            } else alert('Database error. Customer ID not found');
            
        }
    });
});// ----------------------- Delete row on delete button click end -----------------------
