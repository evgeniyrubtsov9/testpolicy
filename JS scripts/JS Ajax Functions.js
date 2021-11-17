/** 
 * Author: Jevgenijs Rubcovs LUDF
 * Library: JS scripts, JS Ajax Functions.js
 * Version: 1.0
 * Description: Library to store single Ajax functions - GET/POST requests
 */
import { localDatetime, loggedInUser, type} from "./JS Customer Functions.js"; // import necessary functions and variables from main.js
import { isNullSafe, currentLink } from "./JS Utility Functions.js";
/**
 * 
 * @param {String} key - key for the function to understand what action to perform (add or update)
 * @param {String} name - customer name
 * @param {String} surname - customer surname
 * @param {String} email - customer email
 * @param {String} address - customer address
 * @param {String} dateOfBirth - customer date of birth
 * @param {String} countryCode - customer country code 
 * @param {String} genderCode - customer gender code 
 * @param {String} statusCode - customer status code 
 * @param {String} user - user name, who is responsible for the action
 * @param {String} flexText1 - customer flex text 1 
 * @param {String} customerId - customer id 
 */
export function ajaxAddOrUpdateCustomer(key, name, surname, email, address, dateOfBirth, countryCode, genderCode, statusCode, user, flexText1, customerId){
    var processName = 'ADD-OR-UPDATE CUSTOMER'
    ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'Invoking customer add/update AJAX function... key: '+key);
    // var params = "Params ::" + "Name :: " +name + " | Surname :: " + surname + " | Email :: " + email + " | Address :: " + address
    //                             + " | Date of Birth :: " + dateOfBirth + " | Country Code :: " + countryCode + " | Gender Code :: " + genderCode + " | Status Code :: " + statusCode 
    //                             + " | Created By :: " + user + " | Flex Text 1 :: " + flexText1 + " | customer id :: " + customerId;
    // ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'ajaxAddOrUpdateCustomer params = ' + params);
    var createdByUser = isNullSafe(user) ? user : "System";
    if(!isNullSafe(name)) alert('ERROR. Customer first name cannot be empty');
    if(key != 'add' && key != 'update') {
        ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'ReturnMsg: <b>incorrect KEY used</b>');
        return;
    } else if(key == 'add' || key == 'update'){
        // when adding a customer - use createdByUser for created_by column in DB; for update customer - use global variable loggedInUser for changed_by field 
        var responsibleUser = key == 'add' ? createdByUser : loggedInUser;
        var actionValue = key == 'add' ? 'addCustomer' : 'updateCustomer';
        ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'Responsible user: <b>'+responsibleUser+'</b> | action: <b>'+actionValue+'</b>');
        $.ajax({
            url: currentLink, method: 'POST',
            data: {
                addOrUpdate: actionValue,
                name: name,
                surname: surname,
                email: email,
                address: address,
                date_of_birth: dateOfBirth,
                country_code: countryCode,
                gender: genderCode,
                status_code: statusCode,
                user: responsibleUser, 
                flex_text_1: flexText1,
                customer_id: customerId
            },
            success: function(response){
                console.log(response);
                if(response.length > 0){
                    if(response.indexOf('success') >= 0) ajaxLogResult(currentLink, localDatetime, type, loggedInUser, 'ReturnMsg: SUCCESS');
                    else ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'ReturnMsg: Response received, however action: <b>'+actionValue+'</b> failed');
                }
            },
            dataType: 'text'
        }).done(function() { 
            if(actionValue == 'addCustomer'){
                //ajaxLoadOrUpdateCustomersList();
                window.location = 'index';
            }else {
                $('#dataTable').DataTable().destroy(); // destroy data table to build it again, later
                $('#dataTable').DataTable({ // use data table plugin to create 'beautiful' table with the search option
                    paging : true, // pagination off
                    ordering : false, // ordering off
                });
            }

        })
    }
}
/**
 * @param {String} customerId - customer ID in database 
 * Function sends ajax POST request to index.php, $_POST[key] = removeCustomer
 */
export function ajaxRemoveCustomer(customerId){
    var processName = 'CUSTOMER REMOVE'
    ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'Invoking customer remove AJAX function...');
    $.ajax({
        url: currentLink,method: 'POST', data: {
            removeCustomer: 1,
            customerId: customerId
        },
        success: function(response){
            if(response.indexOf('success') >= 0) ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'ReturnMsg: <b>SUCCESS</b>');
            else ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'ReturnMsg: <b>FAIL</b>');
        },
        dataType: 'text',
    })//.done(function(){ ajaxLogResult(currentLink, localDatetime, processName, loggedInUser, 'ajaxRemoveCustomer end'); })
};
    
/**
 * Function with no params. Function sends GET request to index.php to get the logs and appends the response to the appropriate DIV (scriptlog) in case of successful request
 */
export function ajaxGetLogData(){
    //ajaxLogResult(currentLink, localDatetime, type, loggedInUser, 'ajaxGetLogData start');
    $('#log > .loadingSymbol').toggle();
    $.ajax({
        url: currentLink, method: 'GET', data: { getLogData: 1 }, 
        success: function(response){
            if(response.indexOf('GETLOG_ERROR') < 0) { // if response does not contain 'GETLOG_ERROR'
                $('#log').empty();
                $('#log').append(response);
                //ajaxLogResult(currentLink, localDatetime, type, loggedInUser, 'ajaxGetLogData the log received and loaded into ScriptLog');
            }else ajaxLogResult(currentLink, localDatetime, 'GET LOG-DATA', loggedInUser, 'AJAX function received the response, but the log was not received. Response: <b>'+response+'</b>');
        },
        dataType: 'text'
    }).done(function(){ 
        //ajaxLogResult(currentLink, localDatetime, type, loggedInUser, 'ajaxGetLogData end'); 
        $('#log > .loadingSymbol').toggle();
    });
}
/**
 * Function with no params. Sends GET request to index.php to get log file. 
 * Success execution triggers click event and log file starts downloading
 */
 export function ajaxDownloadLogFile(){
    $('#downloadLog > .loadingSymbol').toggle();
    $.ajax({
        url: currentLink, method: 'GET', data: { getLogDataToDownload: 1 }, dataType: 'text',
        success: function(response){
            if(response.indexOf('DOWNLOADLOG_ERROR') < 0) { // if response does not contain 'DOWNLOADLOG_ERROR'
                var link = document.createElement('a'); // create link with txt file from response and download the file
                link.setAttribute('download', 'scriptLog_' + localDatetime);
                link.setAttribute('href', 'data: text/plain; charset=utf-8,' + encodeURIComponent(response));
                link.click(); 
                ajaxLogResult(currentLink, localDatetime, 'DOWNLOAD LOG-FILE', loggedInUser, 'ReturnMsg: <b>SUCCESS</b>');
            } 
            else ajaxLogResult(currentLink, localDatetime, 'DOWNLOAD LOG-FILE', loggedInUser, 'ReturnMsg: <b>FAIL</b>. Response: ' + response);
        }
    }).done(function() { $('#downloadLog > .loadingSymbol').toggle(); });
}
/**
 * 
 * @param {String} datetime - datetime of the log
 * @param {String} name - name of the log (any, i.e. 'FUNCTION')
 * @param {String} username - user related to the given log
 * @param {String} logData - basic log data
 */
export function ajaxLogResult(link, datetime, name, username, logData){
    $.ajax({
        url: link, method: 'POST', data: {
            logResult: 1,
            datetime: datetime,
            action: name,
            username: username,
            logData: logData
        }, dataType: 'text',
        success: function(response){
            if(response.indexOf('success') >= 0){
                if(logData.indexOf('error') >=0 || logData.indexOf('ERROR') >=0)
                    ajaxLogResult(currentLink, localDatetime, 'DATA-LOGGING', loggedInUser, 'ReturnMsg: <b>ERROR FOUND</b>. Check the action log: ' + logData);
            }
            else {
                ajaxLogResult(currentLink, localDatetime, 'DATA-LOGGING', loggedInUser, 'ReturnMsg: <b>FAILED TO SAVE FOLLOWING LOG IN DB</b>: ' + logData);
                console.log('ATTENTION! ERROR ajaxLogResult(). Action log not logged. Action log: ' + logData)
            }
        }
    })
}
/**
 * Function sends POST request to index.php with clearLogFile variable and appends the latest log data using ajaxGetLogData() in case of successfull php response
 */
export function ajaxClearLogfile(){
    $.ajax({
        url: currentLink, method: 'POST', data: { clearLogfile: 1 }, 
        success: function(response){
            if(response.indexOf('success') >= 0) { 
                ajaxLogResult(currentLink, localDatetime, 'CLEAR LOG', loggedInUser, 'ReturnMsg: <b>SUCCESS</b>'); 
                ajaxGetLogData(); // load the log table from database after clearing the log
            }else ajaxLogResult(currentLink, localDatetime, 'CLEAR LOG', loggedInUser, 'ReturnMsg: <b>RESPONSE RECEIVED, BUT ACTION FAILED: </b>'+response);
        },
        dateType: 'text'
    })
}
/**
 * Function sends GET request to index.php and updates customer's table with the latest records in case of successful php response
 */
export function ajaxLoadOrUpdateCustomersList(){
    $.ajax({
        url: currentLink, method: 'GET', data: { loadOrUpdateCustomersList: 1}, dateType: 'text',
        success: function(response){
            if(response.indexOf('ERROR') < 0) {
                $('#dataTable tbody').empty();
                $('#dataTable tbody').append(response);
                ajaxLogResult(currentLink, localDatetime, 'UPDATE CUSTOMER LIST', loggedInUser, 'ReturnMsg: <b>SUCCESS</b>'); 
            } else ajaxLogResult(currentLink, localDatetime, 'UPDATE CUSTOMER LIST', loggedInUser, 'ReturnMsg: <b>RESPONSE RECEIVED, BUT CONTAINS ERROR: </b>'+response); 
        }
    })
}
/**
 * @param {String} username - user name
 * @param {String} password - user password
 * Function sends POST request to auth.php wuth changePassword, username, password variables in the request and applies appropriate html based on the php response
 */
export function ajaxChangePassword(username, password){
    $.ajax({
        url: currentLink, method: 'POST', data: { changePassword: 1, username: username, password: password},
        dataType: 'text',
        success: function(response){
            $('.loadingSymbol').toggle()
            if(response.indexOf('ERROR') < 0) {
                $('#pwChanged').css({'display' : 'block', 'color' : 'green'});
                $('#newPasswordSubmit').toggle();
            }
            else $('#pwChanged').html(response.replace('ERROR', '')).css({'display' : 'block', 'color' : 'red'});
            $('#newPassword, #newPasswordConfirm').attr('disabled', true);
        }
    })
}
/**
 * @param {String} securityCode - email security code 
 * @param {String} username - user name
 * Function send GET request to auth.php with verifySecurityCode, code, username variables in the request and applies appropriate html based on php response
 */
export function ajaxVerifySecurityCode(securityCode, username){
    if(isNullSafe(securityCode) && securityCode.length == 6){
        $('.loadingSymbol').toggle();
        $.ajax({ 
            url: currentLink, method: 'GET', data: { verifySecurityCode: 1, code: securityCode, username: username},
            success: function(response){
                if(response.indexOf('ERROR') < 0){
                    $('#new_password, #restorePasswordSubmitSecurityCode, input.securityCode, #restorePasswordMsg, #restore_password > a').toggle();
                    $('#restorePasswordUsername').attr('disabled', true);
                } else {
                    $('#restorePasswordMsg').html(response.replace('ERROR', ''));
                }
            },
            dataType: 'text'
        }).done(function(){$('.loadingSymbol').toggle();})
    }else $('#restorePasswordMsg').html('Please provide 6 symbol security code').css({'display' : 'block'});
}
/**
 * @param {String} username - user name
 * Function sends GET request to auth.php with restorePassword, username variables in the request and applies appropriate html based on php response 
 */
export function ajaxVerifyUsername(username){
    if(isNullSafe(username)){
        $('.loadingSymbol').toggle();
        $.ajax({ 
            url: currentLink,
            method: 'GET',
            data: { 
                restorePassword: 1,
                username: username
            },
            success: function(response){
                if(response.indexOf('AC-ERROR') >= 0){
                    $('.securityCode, #restorePasswordSubmitUsername, #restorePasswordSubmitSecurityCode').toggle()
                    $('#restorePasswordMsg').html(response.replace('AC-ERROR', ''));
                    return;
                } else if(response.indexOf('ERROR') < 0){
                    $('.securityCode').html('Security code sent to: ' + response).css({'display' : 'block'})
                    $('#restorePasswordUsername').attr('disabled', true)
                    $('#restorePasswordSubmitSecurityCode, #restorePasswordMsg, #restorePasswordSubmitUsername').toggle()
                } else {
                    $('#restorePasswordMsg').html(response.replace('ERROR', ''));
                }
            },
            dataType: 'text'
        }).done(function(){$('.loadingSymbol').toggle();})
    }
}
/**
 * @param {String} username - user name
 * @param {String} password - user password
 * Function send POST request to auth.php with login, username, password variables in the request. 
 * Function applies appropriate html based on the php response 
 */
export function ajaxAuthenticateUser(username, password){
    if(!isNullSafe(username) || !isNullSafe(password)) $('#auth_response').html("ERROR. Username and/or password is empty");
    else {
        $('.loadingSymbol').toggle()
        $.ajax({
            url: currentLink, method: 'POST', dataType: 'text',
            data: { // variables which will be sent by Ajax and later catched by PHP...
                login: 1,
                usernamePHP: username, 
                passwordPHP: password 
            },
            success: function(response){
                console.log(response);
                if(response.indexOf('success') >= 0){
                    $('#auth_response').html('Signing in...').css({'color' : 'green'})
                    window.location = currentLink; // redirect into the system in case of successful response
                } 
                else {
                    $('#auth_response').html(response.replace('ERROR', '')).css({'color' : 'red'});
                    $('.loadingSymbol').toggle()
                }
            }
        })
    }
    $('#auth_form').on('submit', function(e){ e.preventDefault() }); // Prevent page reloading, which makes input fields blank (useful when incorrect credentials)
}

export function ajaxUploadProductDocuments(formData){
    $.ajax({
        url: currentLink,
        method: 'POST',
        data: formData ,
        contentType: false,
        processData: false,
        success: function(response){
            if(response.indexOf('ERROR') >= 0) $('#product_file_upload_msg').html(response.replace('ERROR', '')).css({'display' : 'block', 'color' : 'red'})
            else  {
                $('#product_file_upload_msg').html(response).css({'display': 'block', 'color': 'green'});
                if(response.indexOf('gtc') >= 0) $('#form_gtc > p > a').css({'display' : 'inline'})
                else if(response.indexOf('ipid') >= 0) $('#form_ipid > p > a').css({'display' : 'inline'})
                else if(response.indexOf('logo') >= 0) $('#form_logo > p > a').css({'display' : 'inline'})
            }
            if(response.indexOf('image') >= 0) $('#productLogo').css({'display' : 'none'})
        }, 
    }).done(function(){ $('.loadingSymbol').toggle()}) 
}

export function ajaxUpdateProductSetup(formData){
    $('.col-md-4 > .loadingSymbol').css({'display' : 'inline-flex'})
    $.ajax({
        url: currentLink,
        data: formData,
        method: 'POST',
        contentType: false,
        processData: false,
        success: function(response){
            //console.log(response)
            if(response.indexOf('ERROR') >= 0) $('#productSetupUpdateMsg').html(response.replace('ERROR', '')).css({'color' : 'red'});
            else $('#productSetupUpdateMsg').html(response.replace('success', 'Updated')).css({'color' : 'green'});
        }
    }).done(function(){ $('.col-md-4 > .loadingSymbol').css({'display' : 'none'}) }) 
}

export function ajaxUpdateProductTariff(parameter, newValue){
    var tariffName = parameter.split('_')[0].replace('table', '') // retrieve tariff/table name out of the parameter
    var loadingSymbolIdentifier;
    var outputMsgIdentifier;
    if(tariffName != 'BaseRates' && tariffName != 'BMI' && tariffName != 'MaxAge' && tariffName != 'SumInsured'){
        alert('Tariff: ' + tariffName + ' not found!');
        return;
    } else {
        loadingSymbolIdentifier = '#'+tariffName+' > .loadingSymbol';
        $(loadingSymbolIdentifier).css({'display' : 'inline-block'})
        outputMsgIdentifier = '#table'+tariffName+'Msg';
    }
    $.ajax({
        url: currentLink, method: 'POST', 
        data: { 
            updateTariff: 1, 
            param: parameter, 
            value: newValue
        },
        success: function(response){
            //console.log(response)
            newValue = newValue[0] == '.' ? newValue.replace('.', '0.') : newValue
            var valueWasChanged = true;
            if(response.indexOf('value-was-not-changed') > 0) { 
                valueWasChanged = false; 
                $(outputMsgIdentifier).html('Value (<b>'+newValue+'</b>) remained the same.').css({'color' : '#404E67'})
                $('#'+parameter).css({'border': '1px solid #ccc', 'background-color' : 'unset'})
            } 
            if(response.indexOf('ERROR') < 0 && valueWasChanged){
                $(outputMsgIdentifier).html(' Updated with the new value: <b>' + newValue+"</b>").css({'color' : 'green'})
                $('#'+parameter).css({'border': '1px solid #ccc', 'background-color' : 'unset'})
            } else if(valueWasChanged){
                response = response.replace('.00', '')
                $(outputMsgIdentifier).html(' Data not saved.' + response.replace('ERROR', '')).css({'color' : 'red'});
                $('#'+parameter).css({'border': '1px solid red', 'background-color': 'rgba(255, 0, 0, 0.2)'})
            }
        }
    }).done(function(){$(loadingSymbolIdentifier).css({'display' : 'none'})})
}
export function ajaxSetProductPremiumPartOptions(){
    $.ajax({
        url: currentLink, method: 'GET', data: { setProductPremiumPartOptions: 1 },
        success: function(response){
            if(response.indexOf('ERROR') < 0){
                var result = response.split(',')
                for(var i=0; i<result.length; i++){
                    var id = result[i].split('=')
                    id[0] = id[0].trim() 
                    $("#select_"+id[0].trim()).val(id[1]).change();
                }
            } else {
                alert('ajaxSetProductPremiumPartOptions ERROR') // need to handle differently
            }
        }
    })
}
