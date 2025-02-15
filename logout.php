<?php
session_start();
include 'partials/conn.php';
session_start();    
unset($_SESSION["loginID"]);
unset($_SESSION["userName"]);
header("Location:setup.php");
?>