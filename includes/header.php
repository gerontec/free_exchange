<?php
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
require_once __DIR__ . '/lang.php';
$current_user = getCurrentUser();

// Aktuelle Börse ermitteln
$current_exchange = 'storage'; // default
if (strpos($_SERVER['REQUEST_URI'], 'gun_') !== false || 
    strpos($_SERVER['REQUEST_URI'], 'waffen') !== false) {
    $current_exchange = 'guns';
}
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Börse' ?></title>
    <meta name="description" content="<?= $metaDescription ?? 'Online Exchange' ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Börsen-Switcher Banner -->
    <div class="bg-primary text-white py-2">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group" role="group">
                    <a href="index.php" class="btn btn-sm <?= $current_exchange === 'storage' ? 'btn-light' : 'btn-outline-light' ?>">
                        <i class="bi bi-building"></i> <?= t('exchange_storage') ?>
                    </a>
                    <a href="gun_index.php" class="btn btn-sm <?= $current_exchange === 'guns' ? 'btn-light' : 'btn-outline-light' ?>">
                        <i class="bi bi-crosshair"></i> <?= t('exchange_guns') ?>
                    </a>
                </div>
                <small class="text-white-50"><?= t('exchange_select') ?></small>
            </div>
        </div>
    </div>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark <?= $current_exchange === 'guns' ? 'bg-danger' : 'bg-dark' ?> sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $current_exchange === 'guns' ? 'gun_index.php' : 'index.php' ?>">
                <i class="bi bi-<?= $current_exchange === 'guns' ? 'crosshair' : 'building' ?>"></i> 
                <?= t($current_exchange === 'guns' ? 'exchange_guns' : 'exchange_storage') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($current_exchange === 'guns'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="gun_index.php"><i class="bi bi-search"></i> <?= t('gun_offers') ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gun_nachfragen.php"><i class="bi bi-person-raised-hand"></i> <?= t('gun_requests') ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gun_angebot_erstellen.php"><i class="bi bi-plus-circle"></i> <?= t('gun_sell') ?></a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="bi bi-search"></i> <?= t('nav_offers') ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="nachfragen.php"><i class="bi bi-person-raised-hand"></i> <?= t('nav_requests') ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="angebot_erstellen.php"><i class="bi bi-plus-circle"></i> <?= t('nav_create_offer') ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Language Switcher -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?= current_lang() === 'de' ? '🇩🇪 DE' : '🇬🇧 EN' ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=de">🇩🇪 Deutsch</a></li>
                            <li><a class="dropdown-item" href="?lang=en">🇬🇧 English</a></li>
                        </ul>
                    </li>
                    
                    <?php if ($current_user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if ($current_user['avatar_url']): ?>
                                    <img src="<?= htmlspecialchars($current_user['avatar_url']) ?>" 
                                         class="rounded-circle me-2" 
                                         width="32" height="32" alt="Avatar">
                                <?php endif; ?>
                                <?= htmlspecialchars($current_user['name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($current_exchange === 'guns'): ?>
                                    <li><a class="dropdown-item" href="gun_meine_angebote.php">
                                        <i class="bi bi-crosshair"></i> <?= t('gun_my_offers') ?>
                                    </a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="meine_angebote.php">
                                        <i class="bi bi-house-door"></i> <?= t('nav_my_offers') ?>
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> <?= t('nav_logout') ?>
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> <?= t('nav_login') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="container my-4">
