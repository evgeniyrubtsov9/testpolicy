/** 
 * Author: Jevgenijs Rubcovs LUDF
 * Library: JS scripts, auth.js
 * Version: 1.0
 * Description: Library to store jQuery authentication functions
 */
import { isNullSafe, checkPasswordStrengthAndReturnScore } from "./JS Utility Functions.js";
import { ajaxAuthenticateUser, ajaxChangePassword, ajaxVerifyUsername, ajaxVerifySecurityCode } from "./JS Ajax Functions.js";
$(document).ready(function() {
    $('#restorePassword').on('click', function(){
        $('#restore_password').css({'display' : 'block'})
        $('#auth_form').css({'display' : 'none'})
    })
    $("#restore_password, #new_password").submit(function(e) { e.preventDefault();}); // prevents page reloading on submitting
    $('#login').on('click', function(){ // 
        var username = $('#username').val();
        var password = $('#password').val();
        ajaxAuthenticateUser(username, password);
    });
    $('#newPasswordSubmit').on('click', function(){ // set new password for the given username when clicking on the given button
        $('.loadingSymbol').toggle();
        var username = $('input#restorePasswordUsername').val();
        var password = $('#newPassword').val()
        ajaxChangePassword(username, password);
    })
    $('#restorePasswordSubmitUsername').on('click', function(){ // check username existance in database using ajax function when clicking on the given button
        var username = $('#restorePasswordUsername').val()
        ajaxVerifyUsername(username);
    })
    $('#restorePasswordSubmitSecurityCode').on('click', function(){
        var securityCode = $('input.securityCode').val();
        var username = $('input#restorePasswordUsername').val();
        ajaxVerifySecurityCode(securityCode, username);
    })
    $('#newPassword').on('keyup', function(){ // apply appropriate css from 'if' and 'else' statements on each newPassword input change
        var passworsStrength = checkPasswordStrengthAndReturnScore($('#newPassword').val());
        if(!isNullSafe(passworsStrength) || passworsStrength == "weak") $(this).css({'border-color' : 'red'})
        else $(this).css({'border-color' : 'green'})
    })
    $('#newPasswordConfirm').on('keyup', function(){ // 
        var confirmPassword = $('#newPasswordConfirm').val();
        if(confirmPassword != $('#newPassword').val()) {
            $(this).css({'border-color' : 'red'})
            $('#newPasswordSubmit').attr('disabled', true);
        }
        else {
            $(this).css({'border-color' : 'green'})
            $('#newPasswordSubmit').attr('disabled', false);
        } 
    })
});