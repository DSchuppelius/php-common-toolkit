<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : CamtValidator.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\Common\Banking\CamtType;
use CommonToolkit\Enums\Common\Banking\CamtVersion;
use CommonToolkit\Helper\FileSystem\File;
use DOMDocument;
use LibXMLError;
use RuntimeException;

/**
 * CAMT-Validator für XSD-Schema-Validierung.
 * 
 * Validiert CAMT.052, CAMT.053 und CAMT.054 XML-Dokumente
 * gegen die entsprechenden ISO 20022 XSD-Schemas.
 * 
 * @package CommonToolkit\Helper\Data
 */
class CamtValidator {
    /**
     * Pfad zum XSD-Verzeichnis
     */
    private const XSD_BASE_PATH = __DIR__ . '/../../../data/xsd/camt/';

    /**
     * Mapping von CAMT-Typ und Version zu XSD-Datei
     */
    private const XSD_FILES = [
        'camt.052' => [
            '02' => 'camt.052.001.02.xsd',
            '06' => 'camt.052.001.06.xsd',
            '08' => 'camt.052.001.08.xsd',
            '10' => 'camt.052.001.10.xsd',
            '12' => 'camt.052.001.12.xsd',
        ],
        'camt.053' => [
            '02' => 'camt.053.001.02.xsd',
            '04' => 'camt.053.001.04.xsd',
            '08' => 'camt.053.001.08.xsd',
            '10' => 'camt.053.001.10.xsd',
            '12' => 'camt.053.001.12.xsd',
        ],
    ];

    /**
     * Österreichische CAMT.053 Schemas
     */
    private const AUSTRIAN_XSD_FILES = [
        'camt.053' => [
            '02.003' => 'ISO.camt.053.001.02.austrian.003.xsd',
            '02.004' => 'ISO.camt.053.001.02.austrian.004.Korrigendum.xsd',
        ],
    ];

    /**
     * Validiert ein CAMT-XML gegen das entsprechende XSD-Schema.
     * 
     * @param string $xmlContent Der XML-Inhalt
     * @param CamtType|null $type Optional: CAMT-Typ (wird automatisch erkannt)
     * @param CamtVersion|null $version Optional: CAMT-Version (wird automatisch erkannt)
     * @return ValidationResult Das Validierungsergebnis
     */
    public static function validate(
        string $xmlContent,
        ?CamtType $type = null,
        ?CamtVersion $version = null
    ): ValidationResult {
        // Typ automatisch erkennen
        if ($type === null) {
            $type = CamtType::fromXml($xmlContent);
            if ($type === null) {
                return new ValidationResult(
                    valid: false,
                    errors: ['Unbekannter CAMT-Dokumenttyp'],
                    type: null,
                    version: null
                );
            }
        }

        // Version automatisch erkennen
        if ($version === null) {
            $version = self::detectVersion($xmlContent);
        }

        // XSD-Datei ermitteln
        $xsdFile = self::getXsdFile($type, $version);
        if ($xsdFile === null || !file_exists($xsdFile)) {
            return new ValidationResult(
                valid: false,
                errors: ["Keine XSD-Datei gefunden für {$type->value} Version " . ($version?->value ?? 'unbekannt')],
                type: $type,
                version: $version,
                xsdFile: $xsdFile
            );
        }

        // XML validieren
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xmlContent)) {
            $errors = self::formatLibXmlErrors(libxml_get_errors());
            libxml_clear_errors();
            return new ValidationResult(
                valid: false,
                errors: array_merge(['XML-Parsing fehlgeschlagen'], $errors),
                type: $type,
                version: $version,
                xsdFile: $xsdFile
            );
        }

        // Schema-Validierung
        $valid = $dom->schemaValidate($xsdFile);
        $errors = self::formatLibXmlErrors(libxml_get_errors());
        libxml_clear_errors();

        return new ValidationResult(
            valid: $valid,
            errors: $errors,
            type: $type,
            version: $version,
            xsdFile: $xsdFile
        );
    }

    /**
     * Validiert eine CAMT-Datei gegen das entsprechende XSD-Schema.
     * 
     * @param string $filePath Pfad zur XML-Datei
     * @param CamtType|null $type Optional: CAMT-Typ
     * @param CamtVersion|null $version Optional: CAMT-Version
     * @return ValidationResult Das Validierungsergebnis
     */
    public static function validateFile(
        string $filePath,
        ?CamtType $type = null,
        ?CamtVersion $version = null
    ): ValidationResult {
        try {
            $xmlContent = File::read($filePath);
        } catch (\Throwable $e) {
            return new ValidationResult(
                valid: false,
                errors: [$e->getMessage()],
                type: $type,
                version: $version
            );
        }

        return self::validate($xmlContent, $type, $version);
    }

    /**
     * Erkennt die CAMT-Version aus dem XML-Namespace.
     */
    public static function detectVersion(string $xmlContent): ?CamtVersion {
        // Namespace-Pattern: urn:iso:std:iso:20022:tech:xsd:camt.053.001.02
        if (preg_match('/urn:iso:std:iso:20022:tech:xsd:camt\.\d{3}\.001\.(\d{2})/', $xmlContent, $matches)) {
            return CamtVersion::tryFrom($matches[1]);
        }

        // Alternativer Namespace für ältere Dokumente
        if (preg_match('/xmlns[^=]*=\s*["\'][^"\']*camt\.(\d{3})\.001\.(\d{2})[^"\']*["\']/', $xmlContent, $matches)) {
            return CamtVersion::tryFrom($matches[2]);
        }

        return null;
    }

    /**
     * Ermittelt die XSD-Datei für einen CAMT-Typ und eine Version.
     */
    private static function getXsdFile(CamtType $type, ?CamtVersion $version): ?string {
        $typeKey = match ($type) {
            CamtType::CAMT052 => 'camt.052',
            CamtType::CAMT053 => 'camt.053',
            CamtType::CAMT054 => null, // CAMT.054 nicht implementiert
        };

        if ($typeKey === null) {
            return null;
        }

        $versionKey = $version?->value ?? '02';

        // Standard-XSD suchen
        if (isset(self::XSD_FILES[$typeKey][$versionKey])) {
            $file = self::XSD_BASE_PATH . self::XSD_FILES[$typeKey][$versionKey];
            if (file_exists($file)) {
                return $file;
            }
        }

        // Fallback: Neueste verfügbare Version suchen
        if (isset(self::XSD_FILES[$typeKey])) {
            $versions = array_keys(self::XSD_FILES[$typeKey]);
            rsort($versions);
            foreach ($versions as $v) {
                $file = self::XSD_BASE_PATH . self::XSD_FILES[$typeKey][$v];
                if (file_exists($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Gibt alle verfügbaren XSD-Dateien zurück.
     * 
     * @return array<string, array<string, string>> Typ => Version => Dateiname
     */
    public static function getAvailableSchemas(): array {
        $available = [];

        foreach (self::XSD_FILES as $type => $versions) {
            foreach ($versions as $version => $filename) {
                $file = self::XSD_BASE_PATH . $filename;
                if (file_exists($file)) {
                    $available[$type][$version] = $filename;
                }
            }
        }

        foreach (self::AUSTRIAN_XSD_FILES as $type => $versions) {
            foreach ($versions as $version => $filename) {
                $file = self::XSD_BASE_PATH . $filename;
                if (file_exists($file)) {
                    $available[$type . '.austrian'][$version] = $filename;
                }
            }
        }

        return $available;
    }

    /**
     * Formatiert LibXML-Fehler zu lesbaren Strings.
     * 
     * @param array<LibXMLError> $errors
     * @return array<string>
     */
    private static function formatLibXmlErrors(array $errors): array {
        $formatted = [];

        foreach ($errors as $error) {
            $level = match ($error->level) {
                LIBXML_ERR_WARNING => 'Warnung',
                LIBXML_ERR_ERROR => 'Fehler',
                LIBXML_ERR_FATAL => 'Kritischer Fehler',
                default => 'Unbekannt'
            };

            $formatted[] = sprintf(
                '[%s] Zeile %d: %s',
                $level,
                $error->line,
                trim($error->message)
            );
        }

        return $formatted;
    }
}

/**
 * Ergebnis einer CAMT-Validierung.
 */
final class ValidationResult {
    /**
     * @param bool $valid Ob die Validierung erfolgreich war
     * @param array<string> $errors Liste der Fehler (leer bei Erfolg)
     * @param CamtType|null $type Der erkannte CAMT-Typ
     * @param CamtVersion|null $version Die erkannte CAMT-Version
     * @param string|null $xsdFile Die verwendete XSD-Datei
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors,
        public readonly ?CamtType $type,
        public readonly ?CamtVersion $version,
        public readonly ?string $xsdFile = null
    ) {
    }

    /**
     * Gibt true zurück wenn die Validierung erfolgreich war.
     */
    public function isValid(): bool {
        return $this->valid;
    }

    /**
     * Gibt die Fehler zurück.
     * 
     * @return array<string>
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Gibt die Fehler als String zurück.
     */
    public function getErrorsAsString(): string {
        return implode("\n", $this->errors);
    }

    /**
     * Gibt den ersten Fehler zurück.
     */
    public function getFirstError(): ?string {
        return $this->errors[0] ?? null;
    }

    /**
     * Gibt die Anzahl der Fehler zurück.
     */
    public function countErrors(): int {
        return count($this->errors);
    }
}
