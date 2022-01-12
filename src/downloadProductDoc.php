<?php 
    session_start();
    if(!isset($_SESSION['loggedIn'])) {
        header('Location: auth');
        exit();
    } else {
        require_once('database.php');
        if(isset($_GET['name'])){
            $sqlDownloadGivenFile = "select name, type, size, content from files_data where name like '" . $_GET['name'] . "%'";
            $result = $connection->query($sqlDownloadGivenFile);
            if($result->num_rows > 0){
                $row = $result->fetch_assoc();
                $name = $row['name'];
                $type = $row['type'];
                $size = $row['size'];
                $content = $row['content'];
                header("Content-length: $size");
                header("Content-type: $type");
                header("Content-Disposition: attachment; filename=$name");
                echo $content;
                mysql_close();
            }
            else header('Location: product');
        }
    }
?>

