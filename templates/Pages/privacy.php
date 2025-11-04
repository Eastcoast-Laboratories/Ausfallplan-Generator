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
    <title>Datenschutzerklärung - FairNestPlan</title>
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
        <a href="<?= $this->Url->build('/') ?>" class="logo">🌟 FairNestPlan</a>
        <div>
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">Login</a>
        </div>
    </nav>

    <div class="content">
        <h1>Datenschutzerklärung</h1>
        
        <h2>1. Datenschutz auf einen Blick</h2>
        <h3>Allgemeine Hinweise</h3>
        <p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie unsere Website besuchen.</p>
        
        <h2>2. Verantwortlicher</h2>
        <p>Verantwortlicher für die Datenverarbeitung auf dieser Webseite ist:</p>
        <p>
            Ruben Barkow-Kuder<br>
            Knickweg 16<br>
            24114 Kiel<br>
            Deutschland<br>
            E-Mail: fairnestplan-kontakt@it.z11.de<br>
            Tel: Kiel-53687223
        </p>
        
        <h2>3. Erhebung personenbezogener Daten</h2>
        <p>Bei der Nutzung unserer Webseite erheben wir folgende Daten:</p>
        <ul>
            <li>Bestandsdaten (E-Mail-Adresse bei Registrierung)</li>
            <li>Organisationsinformationen</li>
            <li>Kinderdaten (Name, optional: Geschlecht, Adresse, Geburtsdatum) für Planungszwecke</li>
            <li>Nutzungsdaten (z.B. besuchte Webseiten, Interesse an Inhalten, Zugriffszeiten)</li>
            <li>Meta-/Kommunikationsdaten (z.B. Geräte-Informationen, IP-Adressen)</li>
        </ul>
        
        <h2>4. Zweck der Datenverarbeitung</h2>
        <p>Wir verarbeiten die Daten der Nutzer nur für die folgenden Zwecke:</p>
        <ul>
            <li>Zurverfügungstellung der Webseite, ihrer Funktionen und Inhalte</li>
            <li>Verwaltung Ihres Accounts</li>
            <li>Erstellung und Verwaltung von Ausfallplänen</li>
            <li>Beantwortung von Kontaktanfragen und Kommunikation mit Nutzern</li>
            <li>Sicherheitsmaßnahmen</li>
        </ul>
        
        <h2>5. Ihre Rechte</h2>
        <p>Sie haben folgende Rechte hinsichtlich Ihrer bei uns gespeicherten personenbezogenen Daten:</p>
        <ul>
            <li>Recht auf Auskunft</li>
            <li>Recht auf Berichtigung oder Löschung</li>
            <li>Recht auf Einschränkung der Verarbeitung</li>
            <li>Recht auf Widerspruch gegen die Verarbeitung</li>
            <li>Recht auf Datenübertragbarkeit</li>
        </ul>
        <p>Sie haben außerdem das Recht, sich bei einer Datenschutz-Aufsichtsbehörde über die Verarbeitung Ihrer personenbezogenen Daten durch uns zu beschweren.</p>
        
        <h2>6. Datenübermittlung an Dritte</h2>
        <p>Eine Übermittlung Ihrer Daten an Dritte zu anderen als den im Folgenden aufgeführten Zwecken findet nicht statt. Wir geben Ihre persönlichen Daten nur an Dritte weiter, wenn:</p>
        <ul>
            <li>Sie Ihre ausdrückliche Einwilligung dazu erteilt haben,</li>
            <li>die Verarbeitung zur Abwicklung eines Vertrags mit Ihnen erforderlich ist,</li>
            <li>die Verarbeitung zur Erfüllung einer rechtlichen Verpflichtung erforderlich ist,</li>
            <li>die Verarbeitung zur Wahrung berechtigter Interessen erforderlich ist und kein Grund zur Annahme besteht, dass Sie ein überwiegendes schutzwürdiges Interesse an der Nichtweitergabe Ihrer Daten haben.</li>
        </ul>
        
        <h2>7. Hosting</h2>
        <p>Wir hosten die Inhalte unserer Website bei folgendem Anbieter:</p>
        <p>Hetzner Online AG</p>
        <p>Die Server befinden sich in Deutschland und entsprechen den DSGVO-Anforderungen.</p>
        
        <h2>8. Kontakt zum Datenschutz</h2>
        <p>Wenn Sie Fragen zum Datenschutz haben, schreiben Sie uns bitte eine E-Mail an:</p>
        <p>
            E-Mail: fairnestplan-kontakt@it.z11.de<br>
            Telefon: Kiel-53687223
        </p>
        
        <p><small>Stand: <?= date('d.m.Y') ?></small></p>
    </div>

    <footer style="padding: 2rem; text-align: center; background: #f8f9fa; margin-top: 4rem;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> FairNestPlan. Alle Rechte vorbehalten.</p>
            <p>
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'imprint']) ?>">Impressum</a> | 
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'privacy']) ?>">Datenschutz</a> | 
                <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'terms']) ?>">AGB</a>
            </p>
        </div>
    </footer>
</body>
</html>
