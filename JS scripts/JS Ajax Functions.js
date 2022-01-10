/** 
 * Author: Jevgenijs Rubcovs LUDF
 * Library: JS scripts, JS Ajax Functions.js
 * Version: 1.0
 * Description: Library to store single Ajax functions - GET/POST requests
 */
import { localDatetime, loggedInUser} from "./JS Customer Functions.js"; // import necessary functions and variables from main.js
import { isNullSafe, currentLink, calculateAge, validateEmail, checkPasswordStrengthAndReturnScore } from "./JS Utility Functions.js";
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
    var createdByUser = isNullSafe(user) ? user : "System";
    if(key != 'add' && key != 'update') return;
    else if(key == 'add' || key == 'update'){
        var responsibleUser = key == 'add' ? createdByUser : loggedInUser;
        var actionValue = key == 'add' ? 'addCustomer' : 'updateCustomer';
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
            dataType: 'text',
            success: function(response){
                if(response.indexOf('ERROR') < 0) window.location = 'index'
                else $('#custReturnMsg').html(response.replace('ERROR', '')).css({'display' : 'block'})
            }
        })
    }
}
/**
 * @param {String} customerId - customer ID in database 
 * Function sends ajax POST request to index.php, $_POST[key] = removeCustomer
 */
export function ajaxRemoveCustomer(customerId){
    $.ajax({
        url: currentLink,method: 'POST', data: {
            removeCustomer: 1,
            customerId: customerId
        },
        dataType: 'text',
    })
};
/**
 * Function with no params. Function sends GET request to index.php to get the logs and appends the response to the appropriate DIV (scriptlog) in case of successful request
 */
export function ajaxGetLogData(){
    $('#log > .loadingSymbol').toggle();
    $.ajax({
        url: currentLink, method: 'GET', data: { getLogData: 1 }, 
        success: function(response){
            $('#log').empty();
            if(response.indexOf('GETLOG_ERROR') < 0) { // if response does not contain 'GETLOG_ERROR'   
                $('#log').append(response);
            }
        },
        dataType: 'text'
    }).done(function(){$('#log > .loadingSymbol').toggle()});
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
            response = response.replace('<b>', '')
            response = response.replace('</b>', '')
            if(response.indexOf('DOWNLOADLOG_ERROR') < 0) { // if response does not contain 'DOWNLOADLOG_ERROR'
                var link = document.createElement('a'); // create link with txt file from response and download the file
                link.setAttribute('download', 'scriptLog_' + localDatetime);
                link.setAttribute('href', 'data: text/plain; charset=utf-8,' + encodeURIComponent(response));
                link.click(); 
            }
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
        }, 
        dataType: 'text',
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
        success: function(response){ if(response.indexOf('success') >= 0) ajaxGetLogData(); }, // load the log table from database after clearing the log
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
            }
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
            url: currentLink, method: 'GET',
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
                } else $('#restorePasswordMsg').html(response.replace('ERROR', '')); 
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
            if(response.indexOf('ERROR') >= 0) $('#productSetupUpdateMsg').html(response.replace('ERROR', '')).css({'color' : 'red'});
            else $('#productSetupUpdateMsg').html(response.replace('success', 'Updated')).css({'color' : 'green'});
        }
    }).done(function(){ $('.col-md-4 > .loadingSymbol').css({'display' : 'none'}) }) 
}
/**
 * @param {String} parameter - tariff table input id 
 * @param {String} newValue - tariff table input's new value
 * @returns nothing in case of inappropriate tariff name found. Otherwise the function doesn't do return
 */
export function ajaxUpdateProductTariff(parameter, newValue){
    console.log('ajaxUpdateProductTariff param: ' + parameter)
    console.log('ajaxUpdateProductTariff new value: ' + newValue)
    var tariffName = parameter.split('_')[0].replace('table', '') // retrieve tariff/table name out of the parameter
    var loadingSymbolIdentifier;
    var outputMsgIdentifier;
    if(tariffName != 'BaseRates' && tariffName != 'BMI' && tariffName != 'MaxAge' && tariffName != 'SumInsured' && tariffName != 'PolicyParams'){
        alert('Tariff: ' + tariffName + ' not found!');
        return;
    } else {
        loadingSymbolIdentifier = '#'+tariffName+' > .loadingSymbol';
        $(loadingSymbolIdentifier).css({'display' : 'inline-block'})
        outputMsgIdentifier = '#table'+tariffName+'Msg';
    }
    $.ajax({
        url: currentLink, method: 'POST', // currentLink is a global variable for the convenience. Exact page name might be indicated in case of necessity
        data: { 
            updateTariff: 1, // later this variable is used to indicate the update tariff action by PHP functionality
            param: parameter, 
            value: newValue
        },
        success: function(response){
            console.log('Response: ' + response)
            newValue = newValue[0] == '.' ? newValue.replace('.', '0.') : newValue // do some formation 
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
                $(outputMsgIdentifier).html(' Data not saved.' + response.replace('ERROR', '')).css({'color' : 'red'}); // no need for the user to show 'ERROR' string in response
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
            } else alert('ajaxSetProductPremiumPartOptions ERROR') // need to handle differently
        }
    })
}

export function ajaxFindCustomerBySerial(customerSerial){
    $.ajax({
        url: currentLink, method: 'GET', data: { findCustomer: customerSerial},
        success: function(response){
            if(response){
                $('#customerDialog > button').css(response.indexOf('Customer not found') < 0 ? {'display' : 'block'} : {'display' : 'none'})
                $('#customerDialogSpanRes').html(response)
            }else alert('ajaxFindCustomerBySerial error') // need to handle properly
        }
    })
}

export function ajaxUpdateCustomerOnCurrentPolicy(customerSerial){
    $.ajax({
        url: currentLink, method: 'GET', data: { setNewCustomer: customerSerial},
        success: function(response){
            if(response.indexOf('ERROR') < 0){
                var customerDetails = response.split(':');
                customerDetails[0] = customerDetails[0].trim()
                $('#custSerial').val(customerDetails[0])
                $('#custName').html(customerDetails[1])
                $('#custSurname').html(customerDetails[2])
                $('#custEmail').html(customerDetails[3])
                $('#custAddress').html(customerDetails[4])
                $('#custBirthdate').html(customerDetails[5].indexOf('00-00') < 0 ? customerDetails[5] + ' (' + calculateAge(new Date(customerDetails[5].split("-").reverse().join("-"))) + ' yo)' 
                    : '<small>Not specified</small>')
                $('#custStatus').html(customerDetails[6] == 'Blacklisted' ? '<b style="color: red;">Blacklisted</b>' : customerDetails[6])
                $('#customerDialogMsg').html('Save policy in order to change the policyholder!').css({'color' : 'red', 'text-align' : 'center'})
            }else alert('ajaxUpdateCustomerOnPolicy error')
        }
    })
}
/**
 * @param {formData} formData - policy submit form data
 */
export function ajaxSendPolicyFormData(formData){
    $('.loadingSymbol').css({'display':'inline-block'})
    $.ajax({
        url: currentLink, method: 'POST', data: formData,
        contentType: false,
        processData: false,
        success: function(response){
            console.log(response)
            if(response.indexOf('ERROR') < 0){ // reload the page by redirecting the user to the same page in case of successful response from PHP functionality
                //$('#policyReturnMsg').html(response).css({'color':'green', 'font-weight' : 'bold'});
                //window.location = currentLink 
            }else {
                $('#policyReturnMsg').html('Output :: ' + response.replace('ERROR', '')).css({'color' : 'red', 'font-weight' : 'bold'});
                window.scrollTo(0,0);
            }
        }
    }).done(function(){ $('.loadingSymbol').css({'display':'none'})})
}

export function ajaxRetrieveSelectedUser(updateUserFlag, username){
    if(updateUserFlag){
        $.ajax({
            url: currentLink, method: 'GET', data: { userSelected: username }, dataType: 'text',
            success: function(response){
                if(response.indexOf('ERROR') < 0) {
                    $('#change_password').parent().css('display', 'block') 
                    var userDetails = response.split(":"); 
                    console.log('User profile details [array]: ' + userDetails)
                    $('#fullname').val(userDetails[0])
                    $('#username').val(userDetails[1])
                    $('#role').val(userDetails[2]).change()
                    console.log('Logged In User: ' + $('#loggedInUser').html().trim())
                    if(userDetails[2] == 'Administrator' && userDetails[1] == $('#loggedInUser').html().trim()) $('#role').attr('disabled', true)
                    else $('#role').attr('disabled', false)
                    if(userDetails[2] == 'Subagent' && userDetails[1] == $('#loggedInUser').html().trim()) {
                        $('#role option[value="Administrator"]').attr('disabled', true);
                        $('#role option[value="Blocked"]').attr('disabled', true);
                    }
                    $('#email').val(userDetails[3])
                    $('#outputMsg').html(null)
                    $('#update_user').html('Update profile')
                    $('#description').html('Update user profile')
                    $('#userModal').modal('show');
                }
            }
        })
    } else { // else add a new user
        $('#fullname').val(null)
        $('#username').val(null)
        $('#email').val(null)
        $('#password').val(null)
        $('#password_confirm').val(null)
        $('#outputMsg').html(null)
        $('#role').val('Administrator').change()
        $('#change_password').parent().css('display', 'none')
        $('#update_user').html('Add')
        $('#description').html('Add new User')
        $('#userModal').modal('show');
    }

}

export function ajaxUpdateUserProfile(customerForm){
    if(isNullSafe($('#email').val())){
        var email = $('#email').val()
        var validEmail = validateEmail(email);
        if(!validEmail) {
            $('#outputMsg').html('Incorrect email format!');
            return;
        }
    }
    var changePasswordFlag = $("#change_password").prop('checked');
    if(changePasswordFlag){
        var pass = $('#password').val();
        var passConfirm = $('#password_confirm').val();
        if(pass != passConfirm) {
            $('#outputMsg').html('Password not allowed')
            return;
        } else {
            var checkPasswordStrength = checkPasswordStrengthAndReturnScore(pass);
            console.log(checkPasswordStrength)
            if(checkPasswordStrength == 'weak'){
                $('#outputMsg').html('Password not allowed')
                return;
            }
        }
    }else if($('#update_user').html().trim() == 'Add'){
        var pass = $('#password').val();
        var passConfirm = $('#password_confirm').val();
        if(pass != passConfirm || !isNullSafe(pass) || !isNullSafe(passConfirm)) {
            $('#outputMsg').html('Password not allowed')
            return;
        } else {
            var checkPasswordStrength = checkPasswordStrengthAndReturnScore(pass);
            console.log(checkPasswordStrength)
            if(checkPasswordStrength == 'weak'){
                $('#outputMsg').html('Password not allowed')
                return;
            }
        }
    }
    $.ajax({
        url: currentLink, method: 'POST', data: customerForm,
        contentType: false,
        processData: false,
        success: function(response){
            console.log(response)
            if(response.indexOf('ERROR') < 0){
               window.location = currentLink
            } else {
                $('#outputMsg').html(response.replace('ERROR', ''))
            }
        }
    })
}