/** 
 * Author: Jevgenijs Rubcovs LUDF
 * Library: JS scripts, JS Product Functions.js
 * Version: 1.0
 * Description: Library stores Product related functions
 */
import { ajaxLogResult, ajaxUpdateProductTariff, ajaxUploadProductDocuments,ajaxUpdateUserProfile, ajaxUpdateProductSetup } from "./JS Ajax Functions.js";
import { currentLink, invokeBasicProductSetupFunctions, showImageBasedOnInput } from "./JS Utility Functions.js";
$(document).ready(function() {
    invokeBasicProductSetupFunctions();
    $('#product_documents').on('click', function(){ $('#documentsModal').modal('show'); })
    $("#product_logo_file").change(function(){ // show the logo when user uploaded an image
        if($("#productLogo").is(":visible"));
        else $("#productLogo").toggle(); // toggle (show) the logo when a file is uploaded into product_logo_file
        showImageBasedOnInput(this); // show the logo on the page as an image using the previously created function
        if(document.getElementById('product_logo_file').files[0]) $("#form_logo" ).submit(); // submit form on file input ('Choose File') change & if file is not empty
    });
    $("#product_ipid_file").change(function(){ 
        if(document.getElementById('product_ipid_file').files[0]) $("#form_ipid").submit() 
    });
    // $('#user_form').submit(function(event){ // prevent page reloading and run the function on form submit 
    //     event.preventDefault();
    //     var customerForm = new FormData(this)
    //     ajaxUpdateUserProfile(customerForm);
    // })
    // $('update_user').on('click', function(){
    //     $('#user_form').submit();
    // })
    $("#product_gtc_file").change(function(){ 
        if(document.getElementById('product_gtc_file').files[0]) $("#form_gtc").submit() 
    });
    $("#form_logo, #form_gtc").submit(function(e) {
        $('.loadingSymbol').toggle();
        e.preventDefault(); // prevents page reloading on submitting
        ajaxUploadProductDocuments(new FormData(this)); 
    }); 
    $('#product_form').submit(function(event){ // prevent page reloading and run the function on form submit 
        event.preventDefault();
        var form = new FormData(this)
        ajaxUpdateProductSetup(new FormData(this));
    })
    $("#tableMaxAge, #tableBMI, #tableBaseRates, #tableSumInsured, #tablePolicyParams").find("input").focusout(function(){
        var tariffTableId = $(this).closest('table').attr('id');
        var parameter = $(this, 'input').attr('id')
        var inputValue = $(this, 'input').val()
        if(tariffTableId != 'tableMaxAge' && tariffTableId != 'tableSumInsured'){
            inputValue = (Math.round(inputValue * 100) / 100).toFixed(2);
            $(this, 'input').val(inputValue)
        }else {
            inputValue = Math.round(inputValue);
            $(this, 'input').val(inputValue)
        }
        ajaxUpdateProductTariff(parameter, inputValue)
    })
});