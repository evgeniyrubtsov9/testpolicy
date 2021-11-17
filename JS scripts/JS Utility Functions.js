import { localDatetime, loggedInUser, type} from "./JS Customer Functions.js"; // import necessary functions and variables from main.js
import { ajaxLogResult, ajaxGetLogData, ajaxClearLogfile, ajaxDownloadLogFile, ajaxUploadProductDocuments, ajaxUpdateProductSetup, ajaxUpdateProductTariff,
        ajaxSetProductPremiumPartOptions } from "./JS Ajax Functions.js";
var link = document.location.href;
link = link.split('/');
link = link.slice(-1).toString(); // get current link name by dividing on array and taking the last element https://testpolicy/src/auth => auth
const currentLink = link;
export { currentLink };
/**
 * @param {any} object - object to check for null 
 * @returns {Boolean} true, if object is not null, undefined or empty; false - if object is null, undefined or empty
 */
export function isNullSafe(object){ 
    return object != undefined && object != null && object != "null" && object != "" && object != "undefined";
}
/**
 * @param {String} name - name to check for special characters 
 * @returns {Boolean} true, if name matches regular expression; false - if does not match
 */
export function validateName(name){
    ajaxLogResult(currentLink, localDatetime, 'VALIDATION', loggedInUser, 'validateName param: <b>'+name+'</b>');
    if(isNullSafe(name)){
        const regExp = /^[a-zA-Z]+$/; // only alphabetic characters: small, large (capitals); Additionally preventing spaces in the first name
        var result = regExp.test(String(name.trim())) ? true : false;
        ajaxLogResult(currentLink, localDatetime, 'VALIDATION',loggedInUser, 'validateName returned: <b>'+result+'</b>');
        return result;
    }
}
/**
 * @param {String} email - email to check for validity 
 * @returns {Boolean} true, if name matches regular expression; false - if does not match
 */
export function validateEmail(email) {
    ajaxLogResult(currentLink, localDatetime, 'VALIDATION',loggedInUser, 'validateEmail param: <b>'+email+'</b>');
    if(isNullSafe(email)){
        const regExp = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // simple regexp validation for the email format: anystring@anystring.anystring; Additionally preventing matching multiple '@' signs
        var result = regExp.test(String(email.trim()).toLowerCase()) ? true : false;
        ajaxLogResult(currentLink, localDatetime, 'VALIDATION', loggedInUser, 'validateEmail returned: <b>'+result+'</b>');
        return result;
    }
    return false;
}
/**
 * @param {String} customerFullId - full customer id in format 'customer_id'
 * @returns {String} id - customer id retrieved from customerFullId
 */
 export function retrieveCustomerId(customerFullId){
    if(isNullSafe(customerFullId)){
        var customerId = customerFullId.split('_');
        customerId = customerId[1];
        return customerId;
    }
}
function setInputIDforTarriff(object, tariffName){
    var inputId = $(object).attr('id')
    if(!isNullSafe(inputId)){ // move forward only if the input element has not received ID previosly
        if(tariffName == 'tableMaxAge'){
            var rowIndex = $(object).closest("tr").index();
            if(rowIndex == 0 || rowIndex == 2) $(object).attr('id', tariffName+'_value_'+rowIndex+'_first')
            if(rowIndex == 1 || rowIndex == 3) $(object).attr('id', tariffName+'_value_'+rowIndex+'_second')
            return; 
        }
        if(tariffName == 'tableBMI'){
            var rowIndex = $(object).closest("tr").index(); // row number
            var columnIndex = $(object).closest("td").index();
            var inputNumberInRow = $(object).index();
            if(rowIndex >= 0) $(object).attr('id', tariffName+'_rangeStart_'+rowIndex)
            if(rowIndex >= 0 && columnIndex == 0 && inputNumberInRow == 1) $(object).attr('id', tariffName+'_rangeEnd_'+rowIndex)
            if(rowIndex >= 0 && columnIndex == 1 && inputNumberInRow == 0) $(object).attr('id', tariffName+'_value_'+rowIndex)
            return;
        }
        if(tariffName == 'tableBaseRates'){
            var rowIndex = $(object).closest("tr").index();
            if(rowIndex >= 0) $(object).attr('id', tariffName+'_value_'+rowIndex)
            return;
        }
        if(tariffName == 'tableSumInsured'){
            var rowIndex = $(object).closest("tr").index();
            var columnIndex = $(object).closest("td").index();
            var inputNumberInRow = $(object).index();
            if(columnIndex == 0) $(object).attr('id', tariffName+(inputNumberInRow == 1 ? '_rangeStart_' : '_rangeEnd_')+(rowIndex == 3 ? '2' : '1')) 
            function returnCoverName(number){
                var transform = number%3;
                if(transform == 0) return 'first';
                if(transform == 1) return 'second';
                if(transform == 2) return 'third';
            }
            if(inputNumberInRow == 0) $(object).attr('id', tariffName+(rowIndex >=0 && rowIndex <= 2 ? '_value_1_'+returnCoverName(rowIndex) : '_value_2_'+returnCoverName(rowIndex)))
            return;
        }
    }
}
/**
 * 
 * @param {String} password - string to check for strength
 * @returns {String} 'strong', 'good' or 'weak' according to the password stength score
 */
export function checkPasswordStrengthAndReturnScore(password){
    var score = 0;
    if (!password) return score; // return score (= 0), if password is not specified in function params
    var letters = new Object();
    for (var i=0; i<password.length; i++) {// award every unique letter until 5 repetitions
        if(letters[password[i]]) letters[password[i]]++;// add letters from password to the letters object as keys and it's number of occurs as values. I.e aabc => {'a':2, 'b':1, 'c':1} 
        else letters[password[i]] = 1;
        score += 5 / letters[password[i]];
    }
    var variations = { // bonus points for mixing it up
        digits: /\d/.test(password),
        lowerCase: /[a-z]/.test(password),
        upperCase: /[A-Z]/.test(password),
        nonWords: /\W/.test(password), // Matches any character that is not a word character
    }
    var variationCount = 0;
    for (var check in variations) {
        variationCount += (variations[check] == true) ? 1 : 0; // count variations in the given password
    }
    score += (variationCount - 1) * 10; // 10 points for each variation
    if (score > 80) return "strong";
    if (score > 60) return "good";
    if (score >= 30) return "weak";
}

export function showImageBasedOnInput(input) { // function to show the image on the page after user has uploaded it
    if (input.files && input.files[0]) { // if input type file is not empty
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#productLogo').attr('src', e.target.result); // set 'src' attribute for product logo
        }
        reader.readAsDataURL(input.files[0]);
    }
}
/**
 * Function without parameters makes basic setup for the Customer. Creates 'beautiful' DataTable, etc
 */
export function invokeBasicCustomerSetupFunctions(){
    if(currentLink == 'index' || currentLink == '') $('.loadingSymbol').toggle();
    ajaxGetLogData(); 
    $('#btnDownloadlog').on('click', function(){ ajaxDownloadLogFile(); }) // download latest log data from database using custom function
    $('#dataTable').DataTable({ // use data table plugin to create 'beautiful' table with the search option
        paging : true, // pagination off
        ordering : false, // ordering off
    });
    $('#dataTable_info').toggle(); 
    $('#btnExportCustomersToXls').on('click', function(){ 
        if(confirm('Download Customers excel file?')) 
            window.location = 'exportCustomersInXls' // download the customer list in Xls by referring to the appropriate php page
    }) 
    $('#btnClearlog').on('click', function() { ajaxClearLogfile(); }) // invoke log file table truncation 
}
/**
 * Function without parameters makes basic setup for the Product. I.e, sets up IDs for input fields, sets up input fields length
 */
export function invokeBasicProductSetupFunctions(){
    ajaxSetProductPremiumPartOptions();
    $("#tableMaxAge, #tableBMI, #tableBaseRates, #tableSumInsured").find("input").on('focus', function(){ 
        var tariffTableId = $(this).closest('table').attr('id')
        setInputIDforTarriff(this, tariffTableId); // use the function to create appropriate IDs for table's input when user focused the given input
    })
    if(currentLink == 'product_tariff_params'){
        //inputs.attr('pattern', '/^-?\d+\.?\d*$/') // only digits regexp
        var path = ' > tbody > tr > td > input'
        var tableBaseRates = '#tableBaseRates' + path;
        var tableMaxAge = '#tableMaxAge' + path;
        var tableBMI = '#tableBMI' + path;
        var tableBaseRatesInputs = $(document).find(tableBaseRates);
        var tableMaxAgeInputs = $(document).find(tableMaxAge);
        var tableBMIinputs = $(document).find(tableBMI);
        tableBaseRatesInputs.each(function() { tableBaseRatesInputs.attr('onKeyPress', 'if(this.value.length==8) return false;')}) // set input max length = 8  
        tableMaxAgeInputs.each(function() { tableMaxAgeInputs.attr('onKeyPress', 'if(this.value.length==3) return false;')}) 
        tableBMIinputs.each(function() { tableBMIinputs.attr('onKeyPress', 'if(this.value.length==5) return false;')}) 
    }
}