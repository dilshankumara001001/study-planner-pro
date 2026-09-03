<!DOCTYPE html>
<html lang="en">
<head>
    <!-- =============================================
    META TAGS
    ============================================= -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="StudyHub Pro - Advanced Student Study Planner">
    <meta name="author" content="StudyHub Team">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="📚 StudyHub Pro">
    <meta property="og:description" content="Advanced Student Study Planner & Task Manager">
    <meta property="og:type" content="website">
    
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'StudyHub Pro'; ?> - Student Planner</title>
    
    <!-- =============================================
    FONTS
    ============================================= -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- =============================================
    FONT AWESOME (Icons)
    ============================================= -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- =============================================
    CSS
    ============================================= -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    
    <!-- =============================================
    ADDITIONAL META FOR PWA (Optional)
    ============================================= -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>public/images/icon-192.png">
</head>
<body>
    <!-- =============================================
    LOADING SPINNER
    ============================================= -->
    <div id="loading-spinner" style="display:none;">
        <div class="spinner"></div>
    </div>

    <!-- =============================================
    MAIN CONTAINER
    ============================================= -->
    <div class="container">
        
        <!-- =============================================
        HEADER
        ============================================= -->
        <header class="header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="header-logo">
                    <span class="logo-icon">📚</span>
                    <span class="logo-text">Study<span>Hub</span></span>
                    <span class="logo-badge">Pro</span>
                </a>
            </div>
            
            <nav class="header-nav" id="mainNav">
                <a href="<?php echo BASE_URL; ?>pages/dashboard.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' || basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>pages/subjects.php" 
                   class="<?php echo (strpos($_SERVER['PHP_SELF'], 'subject') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Subjects
                </a>
                <a href="<?php echo BASE_URL; ?>pages/tasks.php" 
                   class="<?php echo (strpos($_SERVER['PHP_SELF'], 'task') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i> Tasks
                </a>
            </nav>
            
            <div class="header-right">
                <!-- Notifications -->
                <div class="notification-wrapper">
                    <button class="notification-btn" id="notificationToggle" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <span>Notifications</span>
                            <button id="markAllRead">Mark all read</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-empty">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Profile -->
                <div class="user-wrapper">
                    <button class="user-btn" id="userToggle" aria-label="User Menu">
                        <div class="user-avatar">
                            <?php 
                            $initial = isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U';
                            ?>
                            <span><?php echo $initial; ?></span>
                        </div>
                        <span class="user-name">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest'; ?>
                        </span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <div class="user-avatar large">
                                <span><?php echo $initial; ?></span>
                            </div>
                            <div class="user-info">
                                <strong><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest'; ?></strong>
                                <small><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'guest@email.com'; ?></small>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>profile.php">
                            <i class="fas fa-user-cog"></i> Profile Settings
                        </a>
                        <a href="<?php echo BASE_URL; ?>pages/dashboard.php">
                            <i class="fas fa-chart-pie"></i> Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <!-- Dark Mode Toggle -->
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle Theme">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </div>
        </header>
        
        <!-- =============================================
        MAIN CONTENT
        ============================================= -->
        <main class="main-content">