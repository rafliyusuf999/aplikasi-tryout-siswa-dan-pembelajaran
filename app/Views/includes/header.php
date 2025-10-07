<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'INSPIRANET OFFICIAL TO'; ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <?php if(isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
