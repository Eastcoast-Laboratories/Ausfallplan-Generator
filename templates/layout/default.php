<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

$cakeDescription = 'CakePHP: the rapid development php framework';
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= __('Ausfallplan Generator') ?>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts']) ?>
    <!-- Force reload CSS with cache busting -->
    <link rel="stylesheet" href="/css/cake.css?v=<?= time() ?>">

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
    
    <style>
        .language-switcher {
            position: fixed;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
            z-index: 1000;
        }
        .language-switcher a {
            display: block;
            width: 32px;
            height: 24px;
            border-radius: 3px;
            overflow: hidden;
            border: 2px solid #ddd;
            transition: all 0.2s;
            text-decoration: none;
        }
        .language-switcher a:hover {
            border-color: #666;
            transform: scale(1.1);
        }
        .language-switcher a.active {
            border-color: #0066cc;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }
        .language-switcher img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <div class="language-switcher">
        <a href="<?= $this->Url->build(['?' => ['lang' => 'de']]) ?>" 
           class="<?= ($this->request->getSession()->read('Config.language', 'de_DE') === 'de_DE') ? 'active' : '' ?>"
           title="Deutsch">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 5 3'%3E%3Crect width='5' height='3' fill='%23000'/%3E%3Crect width='5' height='2' y='1' fill='%23D00'/%3E%3Crect width='5' height='1' y='2' fill='%23FFCE00'/%3E%3C/svg%3E" alt="DE">
        </a>
        <a href="<?= $this->Url->build(['?' => ['lang' => 'en']]) ?>"
           class="<?= ($this->request->getSession()->read('Config.language', 'de_DE') === 'en_US') ? 'active' : '' ?>"
           title="English">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 30'%3E%3Crect width='60' height='30' fill='%23012169'/%3E%3Cpath d='M0,0 L60,30 M60,0 L0,30' stroke='%23FFF' stroke-width='6'/%3E%3Cpath d='M0,0 L60,30 M60,0 L0,30' stroke='%23C8102E' stroke-width='4'/%3E%3Cpath d='M30,0 V30 M0,15 H60' stroke='%23FFF' stroke-width='10'/%3E%3Cpath d='M30,0 V30 M0,15 H60' stroke='%23C8102E' stroke-width='6'/%3E%3C/svg%3E" alt="EN">
        </a>
    </div>
    
    <nav class="top-nav">
        <div class="top-nav-title">
            <a href="<?= $this->Url->build('/') ?>"><span><?= __('Ausfallplan') ?></span> Generator</a>
        </div>
        <div class="top-nav-links">
            <?php if ($this->request->getParam('action') !== 'login'): ?>
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>"><?= __('Login') ?></a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="main">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>
    <footer>
        <div style="text-align: center; padding: 2rem 0; border-top: 1px solid #e0e0e0; margin-top: 3rem;">
            <p style="margin: 0; font-size: 0.9rem; color: #666;">
                <?= $this->Html->link(__('Impressum'), ['controller' => 'Pages', 'action' => 'display', 'impressum'], ['style' => 'color: #666; margin: 0 1rem;']) ?>
                |
                <?= $this->Html->link(__('Datenschutzerklärung'), ['controller' => 'Pages', 'action' => 'display', 'datenschutz'], ['style' => 'color: #666; margin: 0 1rem;']) ?>
            </p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #999;">
                &copy; <?= date('Y') ?> Eastcoast Laboratories
            </p>
        </div>
    </footer>
</body>
</html>
