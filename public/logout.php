<?php
require_once '../config/config.php';

requireLogin();

logout();

setFlash('Anda telah logout.', 'info');
redirect('index.php');
