<?php
/**
 * Imprint Page
 */

$this->disableAutoLayout();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impressum - FairNestPlan</title>
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
         <img src="<?= $this->Url->build('/img/fairnestplan_logo.png') ?>" alt="FairNestPlan" width="140">
        <div>
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">Login</a>
        </div>
    </nav>

    <div class="content">
        <h1>Impressum</h1>
        
        <h2>Angaben gemäß § 5 TMG</h2>
        <p>
            Ruben Barkow-Kuder<br>
            Knickweg 16<br>
            24114 Kiel<br>
            Deutschland
        </p>
        
        <h2>Kontakt</h2>
        <p>
            Telefon: Kiel-53687223<br>
            E-Mail: fairnestplan-kontakt@it.z11.de
        </p>
        
        <h2>Umsatzsteuer-ID</h2>
        <p>
            Steuernummer: 19 222 22158<br>
            Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:<br>
            DE235640206
        </p>
        
        <h2>Redaktionell verantwortlich</h2>
        <p>
            Ruben Barkow-Kuder<br>
            Knickweg 16<br>
            24114 Kiel<br>
            Deutschland
        </p>
        
        <h2>Haftungsausschluss</h2>
        <h3>Haftung für Inhalte</h3>
        <p>
            Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. 
            Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen.
        </p>
        
        <h3>Haftung für Links</h3>
        <p>
            Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. 
            Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.
        </p>
        
        <h3>Urheberrecht</h3>
        <p>
            Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. 
            Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.
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
