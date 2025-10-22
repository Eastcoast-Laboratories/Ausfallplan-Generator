<?php
/**
 * Authenticated Layout - For logged-in users
 * 
 * Features:
 * - Sidebar navigation (collapses to hamburger on mobile)
 * - User menu with avatar (top right)
 * - Language switcher (DE/EN flag)
 * - Responsive design (hamburger menu <600px)
 *
 * @var \App\View\AppView $this
 */

$user = $this->request->getAttribute('identity');
$currentLang = $this->request->getSession()->read('Config.language', 'de');
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title', 'Ausfallplan-Generator') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['normalize.min', 'milligram.min', 'authenticated']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            min-height: 100vh;
            background: #f5f7fa;
        }
        
        /* Sidebar Navigation */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar.collapsed {
            transform: translateX(-250px);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: #1a252f;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-logo {
            font-size: 1.4rem;
            font-weight: bold;
            color: #3498db;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.2s;
            gap: 0.75rem;
        }
        
        .sidebar-nav-item:hover {
            background: #34495e;
        }
        
        .sidebar-nav-item.active {
            background: #3498db;
            border-left: 4px solid #2980b9;
        }
        
        .sidebar-nav-icon {
            font-size: 1.2rem;
        }
        
        /* Main Content Area */
        .main-wrapper {
            flex: 1;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }
        
        .main-wrapper.expanded {
            margin-left: 0;
        }
        
        /* Top Bar */
        .topbar {
            background: white;
            border-bottom: 1px solid #e1e8ed;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        /* Language Switcher */
        .language-switcher {
            position: relative;
        }
        
        .language-flag {
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0.25rem;
            border: 2px solid transparent;
            border-radius: 4px;
            transition: border-color 0.2s;
        }
        
        .language-flag:hover {
            border-color: #3498db;
        }
        
        .language-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 0.25rem); /* Small visual gap */
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 120px;
            z-index: 1000;
        }
        
        /* Create invisible bridge between flag and dropdown */
        .language-dropdown::before {
            content: '';
            position: absolute;
            top: -0.5rem; /* Cover the gap */
            left: 0;
            right: 0;
            height: 0.5rem;
            background: transparent;
        }
        
        /* Keep dropdown visible when hovering over switcher */
        .language-switcher:hover .language-dropdown {
            display: block;
        }
        
        .language-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .language-option:hover {
            background: #f5f7fa;
        }
        
        .language-option.active {
            background: #e3f2fd;
            font-weight: 600;
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        
        .user-avatar:hover {
            border-color: #3498db;
        }
        
        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            overflow: hidden;
        }
        
        .user-menu:hover .user-dropdown {
            display: block;
        }
        
        .user-dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #e1e8ed;
            background: #f8f9fa;
        }
        
        .user-dropdown-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .user-dropdown-email {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .user-dropdown-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #2c3e50;
            transition: background 0.2s;
        }
        
        .user-dropdown-item:hover {
            background: #f5f7fa;
        }
        
        .user-dropdown-item.logout {
            color: #e74c3c;
            border-top: 1px solid #e1e8ed;
        }
        
        .user-dropdown-item.logout:hover {
            background: #fee;
        }
        
        /* Content */
        .content {
            padding: 2rem;
            max-width: 1400px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 600px) {
            .sidebar {
                transform: translateX(-250px);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-wrapper {
                margin-left: 0;
            }
            
            .hamburger {
                display: block;
            }
            
            .content {
                padding: 1rem;
            }
        }
        
        /* Overlay for mobile menu */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= $this->Url->build('/') ?>" class="sidebar-logo">
                <span>🌟</span>
                <span><?= __('Ausfallplan') ?></span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>" class="sidebar-nav-item <?= $this->request->getParam('controller') === 'Dashboard' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">📊</span>
                <span><?= __('Dashboard') ?></span>
            </a>
            
            <a href="<?= $this->Url->build(['controller' => 'Children', 'action' => 'index']) ?>" class="sidebar-nav-item <?= $this->request->getParam('controller') === 'Children' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">👶</span>
                <span><?= __('Children') ?></span>
            </a>
            
            <a href="<?= $this->Url->build(['controller' => 'SiblingGroups', 'action' => 'index']) ?>" class="sidebar-nav-item <?= $this->request->getParam('controller') === 'SiblingGroups' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">👨‍👩‍👧</span>
                <span><?= __('Sibling Groups') ?></span>
            </a>
            
            <a href="<?= $this->Url->build(['controller' => 'Schedules', 'action' => 'index']) ?>" class="sidebar-nav-item <?= $this->request->getParam('controller') === 'Schedules' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">📅</span>
                <span><?= __('Schedules') ?></span>
            </a>
            
            <a href="<?= $this->Url->build(['controller' => 'Waitlist', 'action' => 'index']) ?>" class="sidebar-nav-item <?= $this->request->getParam('controller') === 'Waitlist' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">📋</span>
                <span><?= __('Waitlist') ?></span>
            </a>
            
            <a href="<?= $this->Url->build(['controller' => 'Reports', 'action' => 'index']) ?>" class="sidebar-nav-item <?= $this->request->getParam('controller') === 'Reports' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">📈</span>
                <span><?= __('Reports') ?></span>
            </a>
        </nav>
    </aside>
    
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                    ☰
                </button>
                <h1 class="topbar-title"><?= $this->fetch('title', __('Dashboard')) ?></h1>
            </div>
            
            <div class="topbar-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <div class="language-flag" title="<?= __('Change Language') ?>">
                        <?= $currentLang === 'de' ? '🇩🇪' : '🇬🇧' ?>
                    </div>
                    <div class="language-dropdown">
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'changeLanguage', 'de']) ?>" class="language-option <?= $currentLang === 'de' ? 'active' : '' ?>">
                            <span>🇩🇪</span>
                            <span>Deutsch</span>
                        </a>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'changeLanguage', 'en']) ?>" class="language-option <?= $currentLang === 'en' ? 'active' : '' ?>">
                            <span>🇬🇧</span>
                            <span>English</span>
                        </a>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="user-menu">
                    <div class="user-avatar" title="<?= h($user->email ?? '') ?>">
                        <?= strtoupper(substr($user->email ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-dropdown-header">
                            <div class="user-dropdown-name"><?= h($user->email ?? __('User')) ?></div>
                            <div class="user-dropdown-email"><?= h($user->role ?? 'viewer') ?></div>
                        </div>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'profile']) ?>" class="user-dropdown-item">
                            <span>⚙️</span>
                            <span><?= __('Settings') ?></span>
                        </a>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'account']) ?>" class="user-dropdown-item">
                            <span>👤</span>
                            <span><?= __('My Account') ?></span>
                        </a>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout']) ?>" class="user-dropdown-item logout">
                            <span>🚪</span>
                            <span><?= __('Logout') ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Flash Messages -->
        <?= $this->Flash->render() ?>
        
        <!-- Page Content -->
        <main class="content">
            <?= $this->fetch('content') ?>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const mainWrapper = document.getElementById('mainWrapper');
        
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
        
        // Close mobile menu on link click
        document.querySelectorAll('.sidebar-nav-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 600) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                }
            });
        });
    </script>
    
    <?= $this->fetch('script') ?>
</body>
</html>
