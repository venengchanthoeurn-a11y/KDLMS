<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
session_unset();
session_destroy();
session_start();
setFlash('info', 'អ្នកបានចាកចេញ · You have been logged out.');
redirect(BASE_URL . '/login.php?msg=logged_out');
