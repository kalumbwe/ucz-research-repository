<?php
require_once __DIR__ . '/../../app/includes/auth.php';
logout_admin();
redirect('/admin/login.php');
