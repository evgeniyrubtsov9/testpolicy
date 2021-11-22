$(document).ready(function() {
    $('#dataTable').DataTable({ // use data table plugin to create 'beautiful' table with the search option
        paging : true, // pagination off
        ordering : false, // ordering off
    })
    $('#addNewPolicy').on('click', function(){window.location='createNewPolicy'})
})