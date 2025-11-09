<?php
/**
 * AGB Page (Terms of Service)
 */

$this->disableAutoLayout();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AGB - FairNestPlan</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['normalize.min', 'milligram.min', 'home']) ?>
    <style>
        .content {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }
        h1 { color: #667eea; }
        h2 { margin-top: 2rem; }
        h3 { margin-top: 1.5rem; }
        .header-nav {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-nav .logo {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="header-nav">
        <a href="<?= $this->Url->build('/') ?>" class="logo">
            <img src="<?= $this->Url->build('/img/fairnestplan_logo.png') ?>" alt="FairNestPlan" width="140">
        </a>
        <div>
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">Login</a>
        </div>
    </nav>

    <div class="content">
        <h1>Allgemeine Geschäftsbedingungen (AGB)</h1>
    
    <p>
        <strong>Allgemeine Geschäftsbedingungen von Ruben Barkow-Kuder, Eastcoast Laboratories</strong><br>
        Knickweg 16, 24114 Kiel<br>
        fairnestplan-kontakt@it.z11.de<br>
        im Folgenden als „FairNestPlan" gekennzeichnet.
    </p>
    
    <h3><?= __('1. Geltungsbereich') ?></h3>
    <p>
        FairNestPlan erbringt alle Leistungen im Bereich der Kita-Ausfallplan-Software ausschließlich auf Grundlage 
        dieser Geschäftsbedingungen. Von diesen Geschäftsbedingungen abweichende AGB des Kunden erkennen wir nicht an, 
        es sei denn, wir haben diesen ausdrücklich schriftlich zugestimmt.
    </p>
    
    <h3><?= __('2. Vertragsschluss') ?></h3>
    <p>
        Der Vertrag kommt durch Registrierung eines Benutzerkontos und Bestätigung der E-Mail-Adresse zustande. 
        FairNestPlan behält sich vor, Registrierungen ohne Angabe von Gründen abzulehnen.
    </p>
    
    <h3><?= __('3. Leistungsumfang') ?></h3>
    <p>
        FairNestPlan stellt eine webbasierte Softwarelösung zur Verwaltung von Kita-Ausfallplänen bereit. 
        Der konkrete Leistungsumfang ergibt sich aus der zum Zeitpunkt der Registrierung gültigen Leistungsbeschreibung 
        des gewählten Tarifs (Free, Pro oder Enterprise).
    </p>
    <p>
        Die Verfügbarkeit der FairNestPlan Server beträgt mindestens 99% im Jahresmittel und 96% im Monatsmittel. 
        Wartungsarbeiten werden nach Möglichkeit in nutzungsarmen Zeiten durchgeführt.
    </p>
    
    <h3><?= __('4. Nutzungsrechte und Datenschutz') ?></h3>
    <p>
        Der Kunde erhält ein zeitlich auf die Vertragslaufzeit beschränktes, nicht übertragbares Nutzungsrecht 
        an der Software. Die Weitergabe von Zugangsdaten an Dritte ist untersagt.
    </p>
    <p>
        Der Kunde ist für die Rechtmäßigkeit der von ihm eingegebenen Daten verantwortlich und stellt sicher, 
        dass alle datenschutzrechtlichen Anforderungen (DSGVO) eingehalten werden, insbesondere bei der 
        Verarbeitung personenbezogener Daten von Kindern und Erziehungsberechtigten.
    </p>
    
    <h3><?= __('5. Pflichten des Kunden') ?></h3>
    <p>Der Kunde verpflichtet sich:</p>
    <ul>
        <li>Notwendige Daten vollständig und richtig anzugeben und Änderungen unverzüglich mitzuteilen</li>
        <li>Zugangsdaten sorgfältig und geheim zu halten</li>
        <li>Regelmäßig Sicherungskopien seiner Daten zu erstellen</li>
        <li>Die Software nicht für rechtswidrige Zwecke zu nutzen</li>
        <li>Keine Inhalte hochzuladen, die gegen gesetzliche Vorschriften oder Rechte Dritter verstoßen</li>
    </ul>
    
    <h3><?= __('6. Zahlungsbedingungen') ?></h3>
    <p>
        Der Free-Tarif ist kostenlos. Für kostenpflichtige Tarife (Pro, Enterprise) sind die Entgelte 
        für die vereinbarte Vertragslaufzeit im Voraus fällig. Zahlungen erfolgen per Überweisung mit 
        einer Zahlungsfrist von 14 Tagen.
    </p>
    <p>
        Bei Zahlungsverzug kann FairNestPlan die Dienste sperren. Kommt der Kunde für zwei aufeinander 
        folgende Monate mit der Bezahlung in Verzug, kann FairNestPlan den Vertrag fristlos kündigen.
    </p>
    
    <h3><?= __('7. Haftung') ?></h3>
    <p>
        FairNestPlan haftet für Schäden nur bei Vorsatz und grober Fahrlässigkeit. Die Haftung ist beschränkt 
        auf den typischen, vorhersehbaren Schaden. Diese Beschränkung gilt nicht bei Verletzung von Leben, 
        Körper oder Gesundheit.
    </p>
    <p>
        Der Kunde ist selbst für die Sicherung seiner Daten verantwortlich. FairNestPlan übernimmt keine 
        Haftung für Datenverlust, soweit dieser durch regelmäßige Datensicherung durch den Kunden vermeidbar gewesen wäre.
    </p>
    
    <h3><?= __('8. Vertragslaufzeit und Kündigung') ?></h3>
    <p>
        Kostenlose Accounts können jederzeit ohne Einhaltung einer Frist gekündigt werden. 
        Kostenpflichtige Verträge haben eine Mindestlaufzeit gemäß der gewählten Tarifoption und 
        verlängern sich automatisch, wenn sie nicht mit einer Frist von einer Woche zum Laufzeitende 
        gekündigt werden.
    </p>
    <p>
        Die Kündigung kann über den Kundenbereich oder schriftlich per E-Mail erfolgen.
    </p>
    
    <h3><?= __('9. Widerrufsrecht für Verbraucher') ?></h3>
    <p>
        Verbraucher haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen den Vertrag zu widerrufen. 
        Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses.
    </p>
    <p>
        Um das Widerrufsrecht auszuüben, muss der Verbraucher FairNestPlan mittels einer eindeutigen Erklärung 
        (z.B. E-Mail) über den Entschluss, den Vertrag zu widerrufen, informieren.
    </p>
    <p>
        Bei Widerruf werden alle Zahlungen unverzüglich und spätestens binnen vierzehn Tagen zurückerstattet. 
        Wurde die Dienstleistung auf ausdrücklichen Wunsch bereits vor Ablauf der Widerrufsfrist begonnen, 
        ist ein angemessener Betrag für die bereits erbrachten Leistungen zu zahlen.
    </p>
    
    <h3><?= __('10. Änderung der AGB') ?></h3>
    <p>
        FairNestPlan ist berechtigt, diese AGB mit einer angemessenen Ankündigungsfrist zu ändern. 
        Widerspricht der Kunde der Änderung nicht innerhalb von 14 Tagen, gilt die Änderung als genehmigt. 
        FairNestPlan weist in der Änderungsankündigung darauf hin, dass die Änderung bei fehlendem Widerspruch 
        wirksam wird.
    </p>
    
    <h3><?= __('11. Schlussbestimmungen') ?></h3>
    <p>
        Es gilt das Recht der Bundesrepublik Deutschland unter Ausschluss des UN-Kaufrechts. 
        Gerichtsstand ist Kiel, sofern der Kunde Kaufmann, juristische Person des öffentlichen Rechts 
        oder öffentlich-rechtliches Sondervermögen ist.
    </p>
    <p>
        Sollten einzelne Bestimmungen dieser AGB unwirksam sein oder werden, bleibt die Wirksamkeit 
        der übrigen Bestimmungen hiervon unberührt.
    </p>
    
    <hr style="margin: 3rem 0; border: none; border-top: 1px solid #e0e0e0;">
    
    <p style="font-size: 0.9rem; color: #666;">
        <strong>Stand:</strong> November 2025<br>
        <strong>Eastcoast Laboratories</strong><br>
        Ruben Barkow-Kuder<br>
        Knickweg 16<br>
        24114 Kiel<br>
        E-Mail: fairnestplan-kontakt@it.z11.de
    </p>
    </div>
</body>
</html>
