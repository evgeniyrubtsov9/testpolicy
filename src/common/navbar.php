<div class='container' style='min-width: 100%;'> <!-- navigation bar start -->
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="index">TestPolicy</a>
            </div>
            <ul class="nav navbar-nav">
                <li><a href="policy">Policy</a></li>
                <li><a href='index'>Customer</a></li>
                <li><a href='product'>Product</a></li>
                <li><a href="user">User</a></li>
                <?php if(isset($_SESSION['admin'])) echo "<li><a href='scriptlog'>Script Log</a></li>"; ?>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a id='loggedInUser' href="user?current=<?php echo urlencode(getLoggedInUsername($connection));?>">
                    <?php echo getLoggedInUsername($connection);?></a></li>
                <li><a href="logout">Logout</a></li>
            </ul>
        </div>
    </nav>
</div><!-- navigation bar end -->