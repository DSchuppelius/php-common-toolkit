<?php
/*
 * Created on   : Sat Jan 10 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XsdValidatorAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts\XML;

use CommonToolkit\Entities\XML\XsdValidationResult;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Parsers\ExtendedDOMDocumentParser;
use RuntimeException;
use UnitEnum;

/**
 * Abstract XSD validator base class.
 * 
 * Provides common XSD schema validation functionality for all
 * format-specific validators (CAMT, Pain, etc.).
 * 
 * @package CommonToolkit\FinancialFormats\Contracts\Abstracts
 */
abstract class XsdValidatorAbstract {
    /**
     * Returns the base path to the XSD directory.
     */
    abstract protected static function getXsdBasePath(): string;

    /**
     * Returns the mapping of type and version to XSD files.
     * 
     * @return array<string, array<string, string>> Type => Version => Filename
     */
    abstract protected static function getXsdFiles(): array;

    /**
     * Detects the document type from XML content.
     */
    abstract public static function detectType(string $xmlContent): ?UnitEnum;

    /**
     * Detects the document version from XML content.
     */
    abstract public static function detectVersion(string $xmlContent): ?string;

    /**
     * Returns the type key used in the XSD mapping.
     */
    abstract protected static function getTypeKey(UnitEnum $type): ?string;

    /**
     * Validates XML content against an XSD schema.
     * 
     * @param string $xmlContent The XML content to validate
     * @param UnitEnum|null $type Optional: Document type (auto-detected if null)
     * @param string|null $version Optional: Document version (auto-detected if null)
     * @return XsdValidationResult The validation result
     */
    public static function validate(
        string $xmlContent,
        ?UnitEnum $type = null,
        ?string $version = null
    ): XsdValidationResult {
        // Auto-detect type
        if ($type === null) {
            $type = static::detectType($xmlContent);
            if ($type === null) {
                return new XsdValidationResult(
                    valid: false,
                    errors: ['Unknown document type'],
                    type: null,
                    version: null
                );
            }
        }

        // Auto-detect version
        if ($version === null) {
            $version = static::detectVersion($xmlContent);
        }

        // Determine XSD file
        $xsdFile = static::getXsdFile($type, $version);
        if ($xsdFile === null || !File::exists($xsdFile)) {
            $typeValue = $type instanceof UnitEnum ? $type->value : (string) $type;
            return new XsdValidationResult(
                valid: false,
                errors: ["No XSD file found for {$typeValue} version " . ($version ?? 'unknown')],
                type: $type,
                version: $version,
                xsdFile: $xsdFile
            );
        }

        // Parse and validate XML using ExtendedDOMDocumentParser
        try {
            $document = ExtendedDOMDocumentParser::fromString($xmlContent);
        } catch (RuntimeException $e) {
            return new XsdValidationResult(
                valid: false,
                errors: ['XML parsing failed: ' . $e->getMessage()],
                type: $type,
                version: $version,
                xsdFile: $xsdFile
            );
        }

        // Schema validation via Parser
        $result = ExtendedDOMDocumentParser::validateAgainstXsd($document, $xsdFile);

        return new XsdValidationResult(
            valid: $result['valid'],
            errors: $result['errors'],
            type: $type,
            version: $version,
            xsdFile: $xsdFile
        );
    }

    /**
     * Validates a file against an XSD schema.
     * 
     * @param string $filePath Path to the XML file
     * @param UnitEnum|null $type Optional: Document type
     * @param string|null $version Optional: Document version
     * @return XsdValidationResult The validation result
     */
    public static function validateFile(
        string $filePath,
        ?UnitEnum $type = null,
        ?string $version = null
    ): XsdValidationResult {
        try {
            $xmlContent = File::read($filePath);
        } catch (\Throwable $e) {
            return new XsdValidationResult(
                valid: false,
                errors: [$e->getMessage()],
                type: $type,
                version: $version
            );
        }

        return static::validate($xmlContent, $type, $version);
    }

    /**
     * Determines the XSD file for a document type and version.
     */
    protected static function getXsdFile(UnitEnum $type, ?string $version): ?string {
        $typeKey = static::getTypeKey($type);
        $xsdFiles = static::getXsdFiles();
        $xsdBasePath = static::getXsdBasePath();

        if ($typeKey === null || !isset($xsdFiles[$typeKey])) {
            return null;
        }

        // Try exact version
        if ($version !== null && isset($xsdFiles[$typeKey][$version])) {
            $file = $xsdBasePath . $xsdFiles[$typeKey][$version];
            if (File::exists($file)) {
                return $file;
            }
        }

        // Fallback: Use latest available version
        $versions = array_keys($xsdFiles[$typeKey]);
        rsort($versions);
        foreach ($versions as $v) {
            $file = $xsdBasePath . $xsdFiles[$typeKey][$v];
            if (File::exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Returns all available XSD files.
     * 
     * @return array<string, array<string, string>> Type => Version => Filename
     */
    public static function getAvailableSchemas(): array {
        $available = [];
        $xsdFiles = static::getXsdFiles();
        $xsdBasePath = static::getXsdBasePath();

        foreach ($xsdFiles as $type => $versions) {
            foreach ($versions as $version => $filename) {
                $file = $xsdBasePath . $filename;
                if (File::exists($file)) {
                    $available[$type][$version] = $filename;
                }
            }
        }

        return $available;
    }
}