<?php
/**
 * Ausfallplan-Generator Landing Page
 *
 * @var \App\View\AppView $this
 */

$this->disableAutoLayout();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ausfallplan-Generator - Kita Scheduling Made Easy</title>
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
    </style>
</head>
<body>
    <div class="hero">
        <div class="container">
            <h1>🌟 Ausfallplan-Generator</h1>
            <p>Einfache und faire Planung für Kitas und Kindergärten</p>
            <p>Verwalten Sie Kinder, erstellen Sie Zeitpläne und exportieren Sie wunderschöne PDFs</p>
            <a href="#features" class="btn-primary">Mehr erfahren</a>
        </div>
    </div>

    <div id="features" class="features">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem;">Hauptfunktionen</h2>
            
            <div class="row">
                <div class="column">
                    <div class="feature-card">
                        <h3>👶 Kinderverwaltung</h3>
                        <p>Verwalten Sie Kinder mit integrativen Kindern und Geschwistergruppen. Automatische Gewichtung für faire Verteilung.</p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>📅 Automatische Verteilung</h3>
                        <p>Intelligente Algorithmen verteilen Kinder fair über Tage, respektieren Kapazitäten und Geschwistergruppen.</p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>📋 Wartelisten</h3>
                        <p>Prioritätsbasierte Wartelisten mit fairem Rotationssystem. Füllen Sie freie Plätze automatisch.</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <div class="feature-card">
                        <h3>🖨️ PDF/PNG Export</h3>
                        <p>Exportieren Sie schöne, druckfertige Pläne im PDF oder PNG Format. Perfekt für Aushänge.</p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>🌍 Mehrsprachig</h3>
                        <p>Verfügbar in Deutsch und Englisch. Einfacher Sprachwechsel für internationale Teams.</p>
                    </div>
                </div>
                <div class="column">
                    <div class="feature-card">
                        <h3>🔒 Sicher</h3>
                        <p>Rollenbasierte Zugriffskontrolle, sichere Authentifizierung und GDPR-konform.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pricing">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem;">Preispläne</h2>
            
            <div class="row">
                <div class="column">
                    <div class="price-card">
                        <h3>Test Plan</h3>
                        <div class="price">Kostenlos</div>
                        <ul style="text-align: left;">
                            <li>1 Organisation</li>
                            <li>1 aktiver Plan</li>
                            <li>Bis zu 25 Kinder</li>
                            <li>PDF Export</li>
                            <li>Community Support</li>
                        </ul>
                        <a href="#" class="btn-primary">Jetzt starten</a>
                    </div>
                </div>
                <div class="column">
                    <div class="price-card">
                        <h3>Pro</h3>
                        <div class="price">€29/Monat</div>
                        <ul style="text-align: left;">
                            <li>Unbegrenzte Pläne</li>
                            <li>Prioritäts-Warteliste</li>
                            <li>CSV Import</li>
                            <li>Custom PDF Themes</li>
                            <li>Priority Support</li>
                        </ul>
                        <a href="#" class="btn-primary">Upgrade</a>
                    </div>
                </div>
                <div class="column">
                    <div class="price-card">
                        <h3>Enterprise</h3>
                        <div class="price">Kontakt</div>
                        <ul style="text-align: left;">
                            <li>SSO/SAML Integration</li>
                            <li>SLA Vereinbarung</li>
                            <li>Audit Logs</li>
                            <li>Dedicated Support</li>
                            <li>Custom Features</li>
                        </ul>
                        <a href="#" class="btn-primary">Kontakt</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="background: #667eea; color: white; padding: 2rem; text-align: center;">
        <div class="container">
            <h3>Bereit anzufangen?</h3>
            <p>Erstellen Sie Ihren kostenlosen Account und probieren Sie es aus!</p>
            <a href="#" class="btn-primary" style="background: white; color: #667eea;">Kostenlos registrieren</a>
        </div>
    </div>

    <footer style="padding: 2rem; text-align: center; background: #f8f9fa;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Ausfallplan-Generator. Alle Rechte vorbehalten.</p>
            <p>
                <a href="#">Impressum</a> | 
                <a href="#">Datenschutz</a> | 
                <a href="#">AGB</a>
            </p>
        </div>
    </footer>
</body>
</html>
