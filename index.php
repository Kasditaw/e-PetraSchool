<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\index.php

require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header("Location: modules/dashboard/index.php");
} else {
    header("Location: login.php");
}
exit;
?>
