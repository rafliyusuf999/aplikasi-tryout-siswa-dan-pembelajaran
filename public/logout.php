<?php
require_once '../config/config.php';

requireLogin();

logout();

header('Location: /index.php');
exit;
