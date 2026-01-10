<?php
/*
 * Created on   : Sat Jan 10 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : XsdValidationResult.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\XML;

use UnitEnum;

/**
 * Generic XSD validation result.
 * 
 * Provides a standardized structure for XSD schema validation results
 * that can be used by all format-specific validators.
 * 
 * @package CommonToolkit\FinancialFormats\Contracts\Abstracts
 */
class XsdValidationResult {
    /**
     * @param bool $valid Whether validation was successful
     * @param array<string> $errors List of errors (empty on success)
     * @param UnitEnum|null $type The detected document type
     * @param string|null $version The detected document version
     * @param string|null $xsdFile The XSD file used for validation
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors,
        public readonly ?UnitEnum $type = null,
        public readonly ?string $version = null,
        public readonly ?string $xsdFile = null
    ) {
    }

    /**
     * Returns true if validation was successful.
     */
    public function isValid(): bool {
        return $this->valid;
    }

    /**
     * Returns the errors.
     * 
     * @return array<string>
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Returns the errors as string.
     */
    public function getErrorsAsString(): string {
        return implode("\n", $this->errors);
    }

    /**
     * Returns the first error.
     */
    public function getFirstError(): ?string {
        return $this->errors[0] ?? null;
    }

    /**
     * Returns the number of errors.
     */
    public function countErrors(): int {
        return count($this->errors);
    }

    /**
     * Returns the document type.
     */
    public function getType(): ?UnitEnum {
        return $this->type;
    }

    /**
     * Returns the document type value as string.
     */
    public function getTypeValue(): ?string {
        return $this->type?->value ?? null;
    }

    /**
     * Returns the document version.
     */
    public function getVersion(): ?string {
        return $this->version;
    }

    /**
     * Returns the XSD file used for validation.
     */
    public function getXsdFile(): ?string {
        return $this->xsdFile;
    }
}