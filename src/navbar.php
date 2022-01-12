<?php 
    if(!isset($_SESSION['loggedIn'])){ // If user is already logged in (session variable 'loggedIn' is set up), return the user into the system without asking credentials
        header('Location: auth');      // otherwise return to auth.php for user to provide credentials at first
        exit();
    }
?>
<div class='container' style='min-width: 100%;'>
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="index">TestPolicy</a>
            </div>
            <ul class="nav navbar-nav">
                <li><a href="policy">Policy</a></li>
                <li><a href='index'>Customer</a></li>
                <li><a href='product'>Product</a></li>
                <?php if(isset($_SESSION['admin'])) echo "<li><a href='user'>User</a></li>"; ?>
                <?php if(isset($_SESSION['admin'])) echo "<li><a href='scriptlog'>Script Log</a></li>"; ?>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a id='loggedInUser'>
                    <?php echo getLoggedInUsername($connection);?></a></li>
                <li><a href="logout">Logout</a></li>
            </ul>
        </div>
    </nav>
</div>
<div id="userModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <span id="description">Update user profile</span>
            </div>
            <div class="modal-body">
            <span id='outputMsg' style="color: red;"></span>
                <form id="user_form">
                    <input type="hidden" name="user_form"/>
                    <input type="hidden" id='selectedUser' name="selectedUser"/>
                    <input type="hidden" id='changePasswordFlag' name="changePasswordFlag"/>
                    <input type="hidden" id='addNew' name="addNew"/>
                    <table>
                        <tr>
                            <td>Full name</td>
                            <td><input id="fullname" name="fullname" class="form-control" style="width: 300px;" type="text"/></td>
                        </tr>
                        <tr>
                            <td>Username</td>
                            <td><input id="username" name="username"class="form-control" type="text"/></td>
                        </tr>
                        <tr>
                            <td>Role</td>
                                <td>
                                    <select name = "role" id="role" class="form-control">
                                        <option value="Administrator">Administrator</option>
                                        <option value="Subagent">Subagent</option>
                                        <option value="Blocked">Blocked</option>
                                    </select>
                                </td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><input name="email" id="email" class="form-control" type="text"/></td>
                        </tr>
                        <tr>
                            <td></label><input id = 'change_password' name="change_password" type="checkbox"/> Change Password</td>
                        </tr>
                        <tr>
                            <td>Password</td>
                            <td><input id = 'password'  name="password" type="password" class="form-control" type="text"/></td>
                        </tr>
                        <tr>
                            <td>Confirm</td>
                            <td><input id = 'password_confirm' name="password_confirm"type="password" class="form-control" /></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><button style="background-color: gold;" id="update_user" type="submit" class="form-control">Update profile</button></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>