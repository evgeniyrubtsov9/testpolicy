import { ajaxFindCustomerBySerial, ajaxUpdateCustomerOnCurrentPolicy, ajaxLogResult, ajaxSendPolicyFormData } from "./JS Ajax Functions.js";
import { loggedInUser } from "./JS Customer Functions.js";
import { isNullSafe, dateToFormatDayMonthYear } from "./JS Utility Functions.js";
$(document).ready(function() {
    $('#policies').DataTable({ // use data table plugin to create 'beautiful' table with the search option
        paging : true, // pagination off
        ordering : false, // ordering off
    })
    $('#addNewPolicy').on('click', function(){window.location='createNewPolicy'})
    $('#policyTermCause').focus(function () {
        $(this).animate({ height: "300px"}, 200);
    }).focusout(function () {
        $(this).animate({ height: "25px", width: "250px" }, 200);
    });
    $(function() {
        $("#customerDialog").dialog({
            autoOpen : false, resizable: false, closeText: '', height: 275, width: 425 // initialize the dialog (and uses some common options)
        });
        $("#customerSearch").click(function() {// next add the onclick handler
            $("#customerDialog").dialog("open");
        });
    });
    $('#customerDialog > input').on('keyup', function(){
        var serial = $('#customerDialog > input').val();
        $('#customerDialogMsg').html('')
        ajaxFindCustomerBySerial(serial);
    })
    $('#customerDialog > button').on('click', function(){
        var customerSerial = $('#customerDialog > input').val();
        var policySerial = $('#policySerial').html()
        console.log('Set customer : ' + customerSerial + ' for policy: ' + policySerial)
        ajaxUpdateCustomerOnCurrentPolicy(customerSerial)
    })
    if($('#status').html() == 'Canceled' || $('#status').html() == 'Active') {
        $(document).find('input, select').attr('readonly', 'readonly');
        $(document).find('textarea').attr('readonly', 'readonly');
    }
    $('#policySave, #policyCalculate, #policyCancel, #policyActivate').on('click', function() {
        var action = $(this).attr('id').replace('policy', '').toLowerCase()
        $('#formPolicy').prepend('<input type="hidden" name="policy_action" value="'+action+'"/>');
        $('#formPolicy').submit();
    })
    $('#formPolicy').submit(function(event) {
        event.preventDefault()
        var policyForm = new FormData(this)
        var action
        policyForm.forEach((value, key) => {
            console.log(key+'='+value)
            if(key == 'policy_action') {
                action = value;
                ajaxLogResult('policyView', new Date().toLocaleString('sv-SE'), 'POLICY ' + action.toUpperCase(), loggedInUser, 'Initiated action: <b>'+value+'</b>. Policy № <b>' + $('#policySerial').html() + '</b>...')
            }
            if((key == 'cancel_reg_date' || key == 'effective_cancel_date' || key == 'end_date' || key == 'start_date') && isNullSafe(value)) value = dateToFormatDayMonthYear(new Date(value))
            function returnTextValueByCode(value){
                if(value == 0) value += ' (Never)'
                if(value == 1) value += ' (Not now)'
                if(value == 2) value += ' (Less than 40 cigarettes a day)'
                if(value == 3) value += ' (More than 40 cigarettes a day)'
                return value
            }
            if(key == 'customer_smoker_status') value = returnTextValueByCode(value)
            key = key.replaceAll('_', ' ')
            if(key.indexOf('policy form') < 0) 
                ajaxLogResult('policyView', new Date().toLocaleString('sv-SE'), 'POLICY '+action.toUpperCase(), loggedInUser, "<b>"+(key[0].toUpperCase() + key.slice(1)) + ': </b>' + value)
        });
        ajaxSendPolicyFormData(policyForm)
    })

})