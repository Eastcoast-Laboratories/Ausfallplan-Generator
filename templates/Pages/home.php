<?php
/**
 * FairNestPlan Landing Page
 *
 * @var \App\View\AppView $this
 */

$this->disableAutoLayout();

// Check if user is logged in via request session
$identity = $this->request->getAttribute('identity');

// Get current locale
$locale = $this->request->getSession()->read('Config.language', 'de_DE');
$lang = substr($locale, 0, 2); // de or en
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('FairNestPlan - Kita Reduzierte Gruppenplanung') ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['normalize.min', 'milligram.min', 'home']) ?>
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .features {
            padding: 4rem 2rem;
        }
        .feature-card {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .pricing {
            background: #f8f9fa;
            padding: 4rem 2rem;
        }
        .price-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .price-card h3 {
            color: #667eea;
        }
        .price-card .price {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        .btn-primary {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .header-nav {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-nav .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .header-nav .nav-links a {
            margin-left: 1.5rem;
            color: #333;
            text-decoration: none;
        }
        .header-nav .nav-links a:hover {
            color: #667eea;
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        .language-switcher {
            display: inline-flex;
            gap: 0.5rem;
            align-items: center;
        }
        .language-switcher a {
            font-size: 1.5rem;
            text-decoration: none;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        .language-switcher a:hover,
        .language-switcher a.active {
            opacity: 1;
        }
        
        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 1001;
        }
        .hamburger span {
            width: 25px;
            height: 3px;
            background: #667eea;
            margin: 3px 0;
            transition: 0.3s;
            display: block;
        }
        
        /* Mobile Navigation */
        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }
            
            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 70%;
                height: 100vh;
                background: white;
                flex-direction: column;
                padding: 4rem 2rem;
                box-shadow: -2px 0 5px rgba(0,0,0,0.1);
                transition: right 0.3s ease;
                z-index: 1000;
            }
            
            .nav-links.active {
                right: 0;
            }
            
            .nav-links a {
                margin: 1rem 0 !important;
                display: block;
            }
            
            .language-switcher {
                margin: 1rem 0;
            }
            
            /* Hamburger animation when active */
            .hamburger.active span:nth-child(1) {
                transform: rotate(-45deg) translate(-5px, 6px);
            }
            .hamburger.active span:nth-child(2) {
                opacity: 0;
            }
            .hamburger.active span:nth-child(3) {
                transform: rotate(45deg) translate(-5px, -6px);
            }
        }
    </style>
</head>
<body>
    <nav class="header-nav">
        <div class="logo">🌟 FairNestPlan</div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="nav-links" id="navLinks">
            <div class="language-switcher">
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'setLanguage', '?' => ['locale' => 'de_DE']]) ?>" 
                   class="<?= $locale === 'de_DE' ? 'active' : '' ?>" title="Deutsch">🇩🇪</a>
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'setLanguage', '?' => ['locale' => 'en_US']]) ?>" 
                   class="<?= $locale === 'en_US' ? 'active' : '' ?>" title="English">🇬🇧</a>
            </div>
            <a href="#features"><?= __('Features') ?></a>
            <a href="#pricing"><?= __('Pricing') ?></a>
            <?php if ($identity): ?>
                <a href="<?= $this->Url->build(['controller' => 'Schedules', 'action' => 'index']) ?>" class="btn-secondary"><?= __('My Schedules') ?></a>
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'logout']) ?>" class="btn-primary"><?= __('Logout') ?></a>
            <?php else: ?>
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="btn-secondary"><?= __('Login') ?></a>
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'register']) ?>" class="btn-primary"><?= __('Register') ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h1>🌟 FairNestPlan</h1>
            <p><?= __('Simple and fair planning in case of absences for daycare centers and kindergartens') ?></p>
            <p><?= __('Manage children, create a list of which children come on which days with an intelligent substitute list system') ?></p>
            <div style="margin-top: 2rem;">
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'register']) ?>" class="btn-primary" style="margin-right: 1rem;"><?= __('Register for free') ?></a>
                <a href="#features" class="btn-secondary"><?= __('Learn more') ?></a>
            </div>
        </div>
    </div>

    <div id="features" class="features">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem;"><?= __('Key Features') ?></h2>
            
            <div class="row">
                <div class="column">
                    <div class="feature-card">
                        <h3>👥 <?= __('Child Management') ?></h3>
                        <p><?= __('Manage children with sibling groups, integration status and individual priorities.') ?></p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>📅 <?= __('Automatic Distribution') ?></h3>
                        <p><?= __('Intelligent algorithms distribute children fairly across days, respecting capacities and sibling groups.') ?></p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>📋 <?= __('Waitlist') ?></h3>
                        <p><?= __('If places become available, the waitlist helps with quick and fair filling.') ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <div class="feature-card">
                        <h3>🖨️ <?= __('PDF/Excel Export') ?></h3>
                        <p><?= __('Export beautiful, print-ready schedules as PDF for distribution or Excel format for editing.') ?></p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>🌍 <?= __('Multilingual') ?></h3>
                        <p><?= __('Available in German and English. Easy language switching for international teams.') ?></p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>🔒 <?= __('Secure') ?></h3>
                        <p><?= __('Role-based access control, secure authentication and GDPR compliant.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pricing">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem;"><?= __('Pricing Plans') ?></h2>
            
            <div class="row">
                <div class="column">
                    <div class="price-card">
                        <h3>Test Plan</h3>
                        <div class="price"><?= __('Free') ?></div>
                        <ul style="text-align: left;">
                            <li><?= __('1 Organization') ?></li>
                            <li><?= __('1 active schedule') ?></li>
                            <li><?= __('PDF Export') ?></li>
                            <li><?= __('Community Support') ?></li>
                        </ul>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'register']) ?>" class="btn-primary"><?= __('Get started') ?></a>
                    </div>
                </div>
                <div class="column">
                    <div class="price-card">
                        <h3>Pro</h3>
                        <div class="price">€5/Monat</div>
                        <ul style="text-align: left;">
                            <li><?= __('Unlimited organizations') ?></li>
                            <li><?= __('Unlimited schedules') ?></li>
                            <li><?= __('PDF Export') ?></li>
                            <li><?= __('Priority Support') ?></li>
                        </ul>
                        <a href="#" class="btn-primary"><?= __('Upgrade') ?></a>
                    </div>
                </div>
                <div class="column">
                    <div class="price-card">
                        <h3>Enterprise</h3>
                        <div class="price"><?= __('On request') ?></div>
                        <ul style="text-align: left;">
                            <li><?= __('SSO/SAML Integration') ?></li>
                            <li><?= __('SLA Agreement') ?></li>
                            <li><?= __('Dedicated Support') ?></li>
                            <li><?= __('Custom Features') ?></li>
                        </ul>
                        <a href="mailto:fairnestplan-kontakt@it.z11.de" class="btn-primary"><?= __('Contact') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pricing" style="background: #667eea; color: white; padding: 2rem; text-align: center;">
        <div class="container">
            <h3><?= __('Ready to get started?') ?></h3>
            <p><?= __('Create your free account and try it out!') ?></p>
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'register']) ?>" class="btn-primary" style="background: white; color: #667eea;"><?= __('Register for free') ?></a>
        </div>
    </div>

    <footer style="padding: 2rem; text-align: center; background: #f8f9fa;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> FairNestPlan. <?= __('All rights reserved.') ?></p>
            <p>
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'imprint']) ?>"><?= __('Imprint') ?></a> | 
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'privacy']) ?>"><?= __('Privacy') ?></a> | 
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'terms']) ?>"><?= __('Terms') ?></a>
            </p>
        </div>
    </footer>
    
    <script>
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            const hamburger = document.querySelector('.hamburger');
            
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        }
        
        // Close menu when clicking on a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                const navLinks = document.getElementById('navLinks');
                const hamburger = document.querySelector('.hamburger');
                
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            const navLinks = document.getElementById('navLinks');
            const hamburger = document.querySelector('.hamburger');
            
            if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) {
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    </script>
</body>
</html>
