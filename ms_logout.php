<?php
    session_start();
    session_destroy();
    header("Location: ms_login.php");
    exit();
?>