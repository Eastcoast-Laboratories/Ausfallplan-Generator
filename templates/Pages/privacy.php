<?php
/**
 * Privacy Policy Page
 */

$this->disableAutoLayout();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Datenschutzerklärung - Ausfallplan-Generator</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['normalize.min', 'milligram.min', 'home']) ?>
    <style>
        .content {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        h1 { color: #667eea; }
        h2 { margin-top: 2rem; }
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
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="header-nav">
        <a href="<?= $this->Url->build('/') ?>" class="logo">🌟 Ausfallplan-Generator</a>
        <div>
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">Login</a>
        </div>
    </nav>

    <div class="content">
        <h1>Datenschutzerklärung</h1>
        
        <h2>1. Datenschutz auf einen Blick</h2>
        <h3>Allgemeine Hinweise</h3>
        <p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie unsere Website besuchen.</p>
        
        <h2>2. Datenerfassung auf unserer Website</h2>
        <h3>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</h3>
        <p>Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber:</p>
        <p>
            z11 Media GmbH<br>
            Musterstraße 123<br>
            12345 Musterstadt<br>
            E-Mail: info@z11.de
        </p>
        
        <h3>Welche Daten erfassen wir?</h3>
        <p>Wir erfassen folgende Daten:</p>
        <ul>
            <li>Kontaktdaten (E-Mail-Adresse)</li>
            <li>Organisationsinformationen</li>
            <li>Kinderdaten (Namen, Geburtsdaten) für Planungszwecke</li>
            <li>Technische Daten (Browser, IP-Adresse)</li>
        </ul>
        
        <h3>Wofür nutzen wir Ihre Daten?</h3>
        <p>Die Daten werden benötigt, um:</p>
        <ul>
            <li>Ihren Account zu verwalten</li>
            <li>Die Software-Funktionalität bereitzustellen</li>
            <li>Ausfallpläne zu erstellen und zu verwalten</li>
        </ul>
        
        <h2>3. Hosting</h2>
        <p>Wir hosten die Inhalte unserer Website bei folgend

em Anbieter:</p>
        <p>Die Server befinden sich in Deutschland und entsprechen den DSGVO-Anforderungen.</p>
        
        <h2>4. Ihre Rechte</h2>
        <p>Sie haben jederzeit das Recht:</p>
        <ul>
            <li>Auskunft über Ihre gespeicherten personenbezogenen Daten zu erhalten</li>
            <li>Berichtigung unrichtiger Daten zu verlangen</li>
            <li>Löschung Ihrer Daten zu verlangen</li>
            <li>Einschränkung der Datenverarbeitung zu verlangen</li>
            <li>Widerspruch gegen die Verarbeitung einzulegen</li>
            <li>Datenübertragbarkeit zu verlangen</li>
        </ul>
        
        <h2>5. Kontakt</h2>
        <p>Bei Fragen zum Datenschutz wenden Sie sich bitte an:</p>
        <p>
            E-Mail: datenschutz@z11.de<br>
            Telefon: +49 (0) 123 456789
        </p>
        
        <p><small>Stand: <?= date('d.m.Y') ?></small></p>
    </div>

    <footer style="padding: 2rem; text-align: center; background: #f8f9fa; margin-top: 4rem;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Ausfallplan-Generator. Alle Rechte vorbehalten.</p>
            <p>
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'imprint']) ?>">Impressum</a> | 
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'privacy']) ?>">Datenschutz</a> | 
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'terms']) ?>">AGB</a>
            </p>
        </div>
    </footer>
</body>
</html>
