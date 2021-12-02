import { ajaxFindCustomerBySerial, ajaxUpdateCustomerOnCurrentPolicy } from "./JS Ajax Functions.js";

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


})