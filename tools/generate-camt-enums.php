#!/usr/bin/env php
<?php
/*
 * Created on   : Sun Dec 28 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : generate-camt-enums.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

/**
 * Generiert PHP-Enums aus CAMT XSD Codelists.
 * 
 * Verwendung:
 *   php tools/generate-camt-enums.php
 *   php tools/generate-camt-enums.php --dry-run
 */

$xsdFiles = [
    __DIR__ . '/../data/xsd/camt/camt.053_codelists.xsd',
    __DIR__ . '/../data/xsd/camt/RB6.0_camt.053_codelists.xsd',
];

$outputDir = __DIR__ . '/../src/Enums/Common/Banking/Camt';

// Mapping: XSD simpleType Name => PHP Enum Name
$typeMapping = [
    'ISO_ExternalPurpose1Code' => 'TransactionPurpose',
    'ISO_ExternalBankTransactionDomain1Code' => 'TransactionDomain',
    'ISO_ExternalBankTransactionFamily1Code' => 'TransactionFamily',
    'ISO_ExternalBankTransactionSubFamily1Code' => 'TransactionSubFamily',
    'ISO_ExternalReturnReason1Code' => 'ReturnReason',
    'ISO_ExternalBalanceType1Code' => 'BalanceType',
    'ISO_ExternalBalanceSubType1Code' => 'BalanceSubType',
    'ISO_ExternalAccountIdentification1Code' => 'AccountIdentificationType',
    'ISO_ExternalClearingSystemIdentification1Code' => 'ClearingSystemIdentification',
    'ISO_ExternalFinancialInstitutionIdentification1Code' => 'FinancialInstitutionIdentification',
    'ISO_ExternalOrganisationIdentification1Code' => 'OrganisationIdentification',
    'ISO_ExternalPersonIdentification1Code' => 'PersonIdentification',
];

$dryRun = in_array('--dry-run', $argv);

echo "=== CAMT Enum Generator ===\n\n";

// Finde die neueste XSD-Datei
$xsdFile = null;
foreach ($xsdFiles as $file) {
    if (file_exists($file)) {
        $xsdFile = $file;
        break;
    }
}

if (!$xsdFile) {
    die("Keine XSD-Datei gefunden!\n");
}

echo "Verwende XSD: " . basename($xsdFile) . "\n\n";

// Parse XSD
$xml = simplexml_load_file($xsdFile);
if (!$xml) {
    die("Fehler beim Parsen der XSD-Datei!\n");
}

// Registriere Namespaces
$namespaces = $xml->getNamespaces(true);
$ns = '';
foreach (['xs', 'xd', 'xsd'] as $prefix) {
    if (isset($namespaces[$prefix])) {
        $ns = $prefix;
        break;
    }
}

if (empty($ns)) {
    // Fallback: Versuche ohne Namespace
    $simpleTypes = $xml->xpath('//simpleType[@name]');
} else {
    $xml->registerXPathNamespace($ns, $namespaces[$ns]);
    $simpleTypes = $xml->xpath("//{$ns}:simpleType[@name]");
}

echo "Gefundene simpleTypes: " . count($simpleTypes) . "\n\n";

$generatedEnums = [];

foreach ($simpleTypes as $simpleType) {
    $typeName = (string) $simpleType['name'];

    // Nur ISO_ Typen verarbeiten
    if (!isset($typeMapping[$typeName])) {
        continue;
    }

    $enumName = $typeMapping[$typeName];
    echo "Verarbeite: $typeName -> $enumName\n";

    // Finde alle enumeration-Werte
    if (!empty($ns)) {
        $simpleType->registerXPathNamespace($ns, $namespaces[$ns]);
        $enumerations = $simpleType->xpath(".//{$ns}:enumeration");
    } else {
        $enumerations = $simpleType->xpath('.//enumeration');
    }

    if (empty($enumerations)) {
        echo "  -> Keine Enumerations gefunden, überspringe.\n";
        continue;
    }

    $cases = [];
    foreach ($enumerations as $enum) {
        $value = (string) $enum['value'];

        // Extrahiere Dokumentation
        $name = $value;
        $definition = '';

        // Suche nach Info-Elementen oder Name/Definition
        $docs = $enum->xpath('.//Info[@title="Name"]');
        if (!empty($docs)) {
            $name = (string) $docs[0];
        } else {
            $docs = $enum->xpath('.//Name');
            if (!empty($docs)) {
                $name = (string) $docs[0];
            }
        }

        $docs = $enum->xpath('.//Info[@title="Definition"]');
        if (!empty($docs)) {
            $definition = (string) $docs[0];
        } else {
            $docs = $enum->xpath('.//Definition');
            if (!empty($docs)) {
                $definition = (string) $docs[0];
            }
        }

        // Bereinige Name für PHP-Konstante
        $caseName = strtoupper($value);
        // Ersetze ungültige Zeichen
        $caseName = preg_replace('/[^A-Z0-9_]/', '_', $caseName);
        // Stelle sicher, dass es nicht mit einer Zahl beginnt
        if (preg_match('/^[0-9]/', $caseName)) {
            $caseName = '_' . $caseName;
        }

        $cases[] = [
            'value' => $value,
            'caseName' => $caseName,
            'name' => trim($name),
            'definition' => trim($definition),
        ];
    }

    echo "  -> " . count($cases) . " Codes gefunden\n";

    if (!empty($cases)) {
        $generatedEnums[$enumName] = [
            'xsdType' => $typeName,
            'cases' => $cases,
        ];
    }
}

echo "\n=== Generiere PHP-Enums ===\n\n";

if (!$dryRun && !is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Verzeichnis erstellt: $outputDir\n";
}

foreach ($generatedEnums as $enumName => $data) {
    $filename = "$outputDir/$enumName.php";
    $content = generateEnumCode($enumName, $data['xsdType'], $data['cases']);

    echo "Generiere: $enumName.php (" . count($data['cases']) . " Cases)\n";

    if (!$dryRun) {
        file_put_contents($filename, $content);
        echo "  -> Geschrieben: $filename\n";
    } else {
        echo "  -> [DRY-RUN] Würde schreiben: $filename\n";
    }
}

echo "\n=== Fertig! ===\n";
echo "Generiert: " . count($generatedEnums) . " Enums\n";

/**
 * Generiert den PHP-Code für ein Enum.
 */
function generateEnumCode(string $enumName, string $xsdType, array $cases): string {
    $date = date('D M d Y');

    $casesCode = [];
    $descriptionMatches = [];
    $definitionMatches = [];

    foreach ($cases as $case) {
        // Case-Definition mit PHPDoc
        $comment = "    /**\n";
        $comment .= "     * {$case['value']} - {$case['name']}\n";
        if (!empty($case['definition'])) {
            // Kürze Definition auf max 100 Zeichen für PHPDoc
            $shortDef = mb_strlen($case['definition']) > 100
                ? mb_substr($case['definition'], 0, 97) . '...'
                : $case['definition'];
            $comment .= "     * {$shortDef}\n";
        }
        $comment .= "     */\n";
        $comment .= "    case {$case['caseName']} = '{$case['value']}';";

        $casesCode[] = $comment;

        // Für description() match
        $escapedName = str_replace("'", "\\'", $case['name']);
        $descriptionMatches[] = "            self::{$case['caseName']} => '{$escapedName}'";

        // Für definition() match (gekürzt)
        $shortDef = mb_strlen($case['definition']) > 200
            ? mb_substr($case['definition'], 0, 197) . '...'
            : $case['definition'];
        $escapedDef = str_replace("'", "\\'", $shortDef);
        $escapedDef = str_replace("\n", ' ', $escapedDef);
        $definitionMatches[] = "            self::{$case['caseName']} => '{$escapedDef}'";
    }

    $casesStr = implode("\n\n", $casesCode);
    $descriptionStr = implode(",\n", $descriptionMatches);
    $definitionStr = implode(",\n", $definitionMatches);

    return <<<PHP
<?php
/*
 * Created on   : {$date}
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : {$enumName}.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 * 
 * Auto-generated from XSD: {$xsdType}
 * Do not edit manually - regenerate with: php tools/generate-camt-enums.php
 */

declare(strict_types=1);

namespace CommonToolkit\Enums\Common\Banking\Camt;

/**
 * {$enumName} - ISO 20022 External Code List
 * 
 * Generiert aus: {$xsdType}
 * @see https://www.iso20022.org/external_code_list.page
 */
enum {$enumName}: string {
{$casesStr}

    /**
     * Gibt den Namen/Titel des Codes zurück.
     */
    public function name(): string {
        return match (\$this) {
{$descriptionStr},
        };
    }

    /**
     * Gibt die Definition/Beschreibung des Codes zurück.
     */
    public function definition(): string {
        return match (\$this) {
{$definitionStr},
        };
    }

    /**
     * Factory-Methode aus String.
     */
    public static function fromString(string \$value): ?self {
        return self::tryFrom(strtoupper(trim(\$value)));
    }

    /**
     * Prüft ob der Wert ein gültiger Code ist.
     */
    public static function isValid(string \$value): bool {
        return self::tryFrom(strtoupper(trim(\$value))) !== null;
    }
}

PHP;
}