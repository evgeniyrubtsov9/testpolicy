import { currentLink } from "./JS Utility Functions.js";
import { ajaxRetrieveSelectedUser, ajaxUpdateUserProfile } from "./JS Ajax Functions.js";
$(document).ready(function(){
    $('#users').DataTable({ // use data table plugin to create 'beautiful' table with the search option
        paging : true, // pagination off
        ordering : false, // ordering off
    })    
})