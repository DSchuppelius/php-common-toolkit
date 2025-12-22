# DATEV Versionsverwaltung (Dynamisches Discovery-System)

Dieses System bietet eine **vollautomatische** und **dynamische** Verwaltung für DATEV-Header und Versionen. Das revolutionäre Design erkennt neue DATEV-Versionen automatisch aus dem Dateisystem, ohne dass Code-Änderungen erforderlich sind.

## Discovery-System

### Automatische Erkennung

Das System durchsucht automatisch das Verzeichnis `src/Entities/DATEV/Header/` nach Versionsverzeichnissen im Format `VXX` (z.B. `V700`, `V800`, etc.) und erkennt:

- ✅ **MetaHeaderDefinition-Klassen** automatisch
- ✅ **Format-Header-Definitionen** automatisch
- ✅ **Unterstützte Formate** pro Version automatisch
- ✅ **Klassenvalidierung** zur Laufzeit

### Vorteile der dynamischen Architektur

- 🎯 **Zero-Config**: Neue Versionen durch einfaches Erstellen der Verzeichnisstruktur
- 🔄 **Runtime-Discovery**: Erkennung zur Laufzeit ohne Code-Rebuild
- 🛡️ **Robuste Validation**: Automatische Prüfung der Klassenkonsistenz
- 📊 **Detaillierte Diagnostik**: Umfassende Informationen über erkannte Versionen
- 🧪 **Test-freundlich**: Refresh-Mechanismus für dynamisches Testen

## Kernkomponenten

### 1. `VersionDiscovery` - Das Herzstück
```php
// Automatische Erkennung aller verfügbaren Versionen
$versions = VersionDiscovery::getAvailableVersions(); // [700, 800, ...]

// Prüfung der Version-Unterstützung  
$supported = VersionDiscovery::isVersionSupported(800); // true/false

// Format-Support prüfen
$hasFormat = VersionDiscovery::isFormatSupported(Category::Buchungsstapel, 800);
```

### 2. `HeaderRegistry` - Dynamisch erweitert
```php
// Funktioniert automatisch für alle erkannten Versionen
$metaHeader = HeaderRegistry::get(800); // Funktioniert wenn V800/ existiert
$formatDef = HeaderRegistry::getFormatDefinition(Category::Buchungsstapel, 800);

// Alle unterstützten Versionen abrufen (dynamisch)
$versions = HeaderRegistry::getSupportedVersions(); // [700, 800, ...]
```

### 3. `VersionManager` - Erweiterte Intelligenz
```php
// Dynamische Versionsübersicht
$overview = VersionManager::getVersionOverview();
// Zeigt alle erkannten Versionen mit Details

// Automatische Migration-Planung
$plan = VersionManager::getMigrationPlan(700, 800);
// Analysiert was zwischen Versionen migrierbar ist

// Umfassende Validation
$validation = VersionManager::validateAllVersions();
// Prüft Vollständigkeit aller erkannten Versionen
```

## Verwendung

### Einfache Versionsprüfung
```php
use CommonToolkit\Registries\DATEV\VersionDiscovery;

// Prüfe was verfügbar ist
$available = VersionDiscovery::getAvailableVersions();
$supported = VersionDiscovery::getSupportedVersions();

echo "Verfügbar: " . implode(', ', $available);
echo "Unterstützt: " . implode(', ', $supported);
```

### Format-Verfügbarkeit prüfen
```php
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;

// Prüfe ob Buchungsstapel in Version 800 verfügbar ist
if (VersionDiscovery::isFormatSupported(Category::Buchungsstapel, 800)) {
    echo "Buchungsstapel V800 ist verfügbar!";
}

// Alle unterstützten Formate für Version abrufen
$formats = VersionDiscovery::getSupportedFormats(800);
foreach ($formats as $format) {
    echo $format->nameValue() . "\n";
}
```

### Dynamische Header-Definition
```php
use CommonToolkit\Registries\DATEV\HeaderRegistry;

// Funktioniert automatisch für alle erkannten Versionen
try {
    $definition = HeaderRegistry::get(800); // Erkennt automatisch wenn vorhanden
    echo "Version 800 MetaHeader verfügbar!";
} catch (RuntimeException $e) {
    echo "Version 800 nicht verfügbar: " . $e->getMessage();
}
```

### Versionsdiagnose
```php
use CommonToolkit\Registries\DATEV\VersionManager;

// Detaillierte Übersicht aller Versionen
echo VersionManager::getVersionSummary();

// Validation aller erkannten Versionen
$validations = VersionManager::validateAllVersions();
foreach ($validations as $version => $result) {
    if (!$result['valid']) {
        echo "Version {$version} hat Probleme:\n";
        foreach ($result['issues'] as $issue) {
            echo "- {$issue}\n";
        }
    }
}
```

## Neue Version hinzufügen!

### Schritt 1: Verzeichnisstruktur erstellen
```bash
mkdir -p src/Entities/DATEV/Header/V800/
mkdir -p src/Enums/DATEV/V800/
```

### Schritt 2: MetaHeaderDefinition implementieren
```php
// src/Entities/DATEV/Header/V800/MetaHeaderDefinition.php
<?php
namespace CommonToolkit\Entities\DATEV\Header\V800;

use CommonToolkit\Contracts\Abstracts\DATEV\MetaHeaderDefinitionAbstract;
// ...

final class MetaHeaderDefinition extends MetaHeaderDefinitionAbstract {
    protected const VERSION = 800;
    // Implementation...
}
```

### Schritt 3: Format-Definitionen hinzufügen
```php
// src/Entities/DATEV/Header/V800/BookingBatchHeaderDefinition.php
// src/Entities/DATEV/Header/V800/DebitorsCreditorsHeaderDefinition.php
// etc.
```

### Schritt 4: Fertig!
```php
// Das System erkennt die neue Version automatisch
$versions = VersionDiscovery::getAvailableVersions(); // [700, 800]
$supported = HeaderRegistry::getSupportedVersions(); // [700, 800]

// Alle APIs funktionieren automatisch
$v800Meta = HeaderRegistry::get(800);
$v800Formats = HeaderRegistry::getSupportedFormats(800);
```

### Klassen-Mapping
Das System mappt automatisch bekannte Klassennamen auf Kategorien:

| Klassenname | Kategorie |
|-------------|-----------|
| `BookingBatchHeaderDefinition` | Buchungsstapel |
| `DebitorsCreditorsHeaderDefinition` | Debitoren/Kreditoren |
| `VariousAddressesHeaderDefinition` | Diverse Adressen |
| `GLAccountDescriptionHeaderDefinition` | Sachkontenbeschriftungen |
| `RecurringBookingsHeaderDefinition` | Wiederkehrende Buchungen |
| `PaymentTermsHeaderDefinition` | Zahlungsbedingungen |
| `NaturalStackHeaderDefinition` | Natural-Stapel |

## Erweiterte Features

### Discovery-Details abrufen
```php
$details = VersionDiscovery::getVersionDetails();
foreach ($details as $version => $info) {
    echo "Version {$version}:\n";
    echo "- Pfad: {$info['path']}\n";
    echo "- MetaHeader: " . ($info['metaHeaderClass'] ? '✅' : '❌') . "\n";
    echo "- Formate: {$info['formatCount']}\n";
}
```

### Kompatibilitäts-Matrix
```php
$matrix = VersionManager::getCompatibilityMatrix();
echo "Format-Kompatibilität:\n";
foreach ($matrix as $format => $versions) {
    echo "{$format}: ";
    foreach ($versions as $version => $supported) {
        echo $supported ? "✅{$version} " : "❌{$version} ";
    }
    echo "\n";
}
```

### Migration zwischen Versionen
```php
$migration = VersionManager::getMigrationPlan(700, 800);

echo "Migration V700 → V800:\n";
echo "Migrierbar: " . count($migration['migratable']) . " Formate\n";
echo "Verloren: " . count($migration['not_migratable']) . " Formate\n";
echo "Neu: " . count($migration['new_formats']) . " Formate\n";
```

## Testing

### Refresh-Mechanismus für Tests
```php
class MyTest extends TestCase {
    protected function setUp(): void {
        // Discovery aktualisieren für konsistente Tests
        VersionDiscovery::refresh();
        HeaderRegistry::clearCache();
    }
}
```

### Mock-Versionen für Tests
Das Discovery-System kann auch für Testzwecke verwendet werden, um temporäre Verzeichnisstrukturen zu testen.

## Best Practices

### Performance-Optimierung
- **Singleton-Pattern**: Instanzen werden automatisch wiederverwendet
- **Lazy Discovery**: Erkennung erfolgt nur bei erster Verwendung  
- **Caching**: Alle Ergebnisse werden gecacht bis zum Refresh

### Fehlerbehandlung
```php
// Robuste Fehlerbehandlung
try {
    $definition = HeaderRegistry::get($version);
} catch (RuntimeException $e) {
    // Version nicht verfügbar oder ungültig
    $this->logger->warning("Version {$version} nicht verfügbar", [
        'error' => $e->getMessage()
    ]);
}
```

### Konsistenz-Checks
```php
// Regelmäßige Validation in Production
$validations = VersionManager::validateAllVersions();
foreach ($validations as $version => $result) {
    if (!$result['valid']) {
        $this->logger->error("Version {$version} inkonsistent", $result);
    }
}
```