<?php
require_once '../config/config.php';

requireLogin();

logout();

redirect('index.php');
