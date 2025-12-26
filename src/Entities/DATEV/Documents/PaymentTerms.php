<?php
/*
 * Created on   : Sun Dec 16 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PaymentTerms.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Entities\DATEV\Documents;

use CommonToolkit\Entities\Common\CSV\ColumnWidthConfig;
use CommonToolkit\Entities\Common\CSV\HeaderLine;
use CommonToolkit\Contracts\Abstracts\DATEV\Document;
use CommonToolkit\Entities\DATEV\MetaHeaderLine;
use CommonToolkit\Enums\Common\CSV\TruncationStrategy;
use CommonToolkit\Enums\DATEV\MetaFields\Format\Category;
use CommonToolkit\Enums\DATEV\HeaderFields\V700\PaymentTermsHeaderField;
use RuntimeException;

/**
 * DATEV-Zahlungsbedingungen-Dokument.
 * Spezielle Document-Klasse für Zahlungsbedingungen-Format (Kategorie 46).
 * 
 * Die Spaltenbreiten werden automatisch basierend auf den DATEV-Spezifikationen
 * aus PaymentTermsHeaderField::getMaxLength() angewendet.
 */
final class PaymentTerms extends Document {
    public function __construct(?MetaHeaderLine $metaHeader, ?HeaderLine $header, array $rows = []) {
        parent::__construct($metaHeader, $header, $rows);
    }

    /**
     * Erstellt eine ColumnWidthConfig basierend auf den DATEV-Spezifikationen.
     * Die maximalen Feldlängen werden aus PaymentTermsHeaderField::getMaxLength() abgeleitet.
     * 
     * @param TruncationStrategy $strategy Abschneidungsstrategie (Standard: TRUNCATE für DATEV-Konformität)
     * @return ColumnWidthConfig
     */
    public static function createDatevColumnWidthConfig(TruncationStrategy $strategy = TruncationStrategy::TRUNCATE): ColumnWidthConfig {
        $config = new ColumnWidthConfig(null, $strategy);

        foreach (PaymentTermsHeaderField::ordered() as $index => $field) {
            $maxLength = $field->getMaxLength();
            if ($maxLength !== null) {
                $config->setColumnWidth($index, $maxLength);
            }
        }

        return $config;
    }

    /**
     * Liefert die DATEV-Kategorie für diese Document-Art.
     */
    public function getCategory(): Category {
        return Category::Zahlungsbedingungen;
    }

    /**
     * Gibt den DATEV-Format-Typ zurück.
     */
    public function getFormatType(): string {
        return Category::Zahlungsbedingungen->nameValue();
    }

    /**
     * Validiert Zahlungsbedingungen-spezifische Regeln.
     */
    public function validate(): void {
        parent::validate();

        $metaFields = $this->getMetaHeader()?->getFields() ?? [];
        if (count($metaFields) > 2 && (int)$metaFields[2]->getValue() !== 46) {
            throw new RuntimeException('Ungültige Kategorie für Zahlungsbedingungen-Dokument. Erwartet: 46');
        }
    }
}