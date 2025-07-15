<?php
// خروج از حساب کاربری 
session_start();
session_destroy();
header("Location: login");
exit;
?>
