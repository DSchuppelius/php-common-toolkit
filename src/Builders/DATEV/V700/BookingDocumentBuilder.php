<?php
/*
 * Created on   : Sat Dec 14 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : BookingDocumentBuilder.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Builders\DATEV\V700;

use CommonToolkit\Builders\CSVDocumentBuilder;
use CommonToolkit\Entities\DATEV\{Document as DatevDocument, MetaHeaderLine};
use CommonToolkit\Entities\DATEV\Header\V700\{MetaHeaderDefinition, BookingBatchHeaderDefinition, BookingBatchHeaderLine};
use CommonToolkit\Entities\DATEV\V700\BookingDataLine;
use CommonToolkit\Enums\DATEV\V700\{MetaHeaderField, BookingBatchHeaderField};
use ERRORToolkit\Traits\ErrorLog;
use RuntimeException;
use DateTimeImmutable;

/**
 * Builder für DATEV BookingBatch-Dokumente (V700).
 * Erstellt komplette DATEV-Export-Dateien mit MetaHeader, FieldHeader und Buchungsdaten.
 */
final class BookingDocumentBuilder extends CSVDocumentBuilder {
    use ErrorLog;

    private ?MetaHeaderLine $metaHeader = null;
    private ?BookingBatchHeaderLine $fieldHeader = null;
    /** @var BookingDataLine[] */
    private array $bookingLines = [];

    public function __construct(string $delimiter = DatevDocument::DEFAULT_DELIMITER, string $enclosure = '"') {
        parent::__construct($delimiter, $enclosure);
    }

    /**
     * Setzt den MetaHeader mit Standard-BookingBatch-Konfiguration.
     */
    public function setMetaHeader(?MetaHeaderLine $metaHeader = null): self {
        $this->metaHeader = $metaHeader ?? $this->createDefaultMetaHeader();
        return $this;
    }

    /**
     * Setzt den FieldHeader (Spaltenbeschreibungen).
     */
    public function setFieldHeader(?BookingBatchHeaderLine $fieldHeader = null): self {
        $this->fieldHeader = $fieldHeader ?? BookingBatchHeaderLine::createDefault();
        return $this;
    }

    /**
     * Fügt eine Buchungszeile hinzu.
     */
    public function addBooking(BookingDataLine $booking): self {
        if ($this->fieldHeader) {
            // Stelle sicher, dass die Buchung den Header kennt
            $booking = new BookingDataLine(
                array_map(fn($f) => $f->getValue(), $booking->getFields()),
                $this->fieldHeader,
                $this->delimiter,
                $this->enclosure
            );
        }

        $this->bookingLines[] = $booking;
        return $this;
    }

    /**
     * Convenience-Methode zum Hinzufügen einer einfachen Buchung.
     */
    public function addSimpleBooking(
        float $amount,
        string $sollHaben,
        string $account,
        string $contraAccount,
        DateTimeImmutable|string $date,
        string $documentRef,
        string $text
    ): self {
        $booking = BookingDataLine::create(
            $amount,
            $sollHaben,
            $account,
            $contraAccount,
            $date,
            $documentRef,
            $text,
            $this->fieldHeader
        );

        return $this->addBooking($booking);
    }

    /**
     * Setzt Berater- und Mandantennummer im MetaHeader.
     */
    public function setClient(int $advisorNumber, int $clientNumber): self {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        $this->metaHeader->set(MetaHeaderField::Beraternummer, $advisorNumber);
        $this->metaHeader->set(MetaHeaderField::Mandantennummer, $clientNumber);

        return $this;
    }

    /**
     * Setzt den Zeitraum der Buchungen im MetaHeader.
     */
    public function setDateRange(DateTimeImmutable $from, DateTimeImmutable $to): self {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        $this->metaHeader->set(MetaHeaderField::DatumVon, $from->format('dmY'));
        $this->metaHeader->set(MetaHeaderField::DatumBis, $to->format('dmY'));

        return $this;
    }

    /**
     * Setzt die Beschreibung des BookingBatchs.
     */
    public function setDescription(string $description): self {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        $this->metaHeader->set(MetaHeaderField::Bezeichnung, $description);

        return $this;
    }

    /**
     * Erstellt das komplette DATEV-Dokument.
     */
    public function build(): DatevDocument {
        if (!$this->metaHeader) {
            $this->setMetaHeader();
        }

        if (!$this->fieldHeader) {
            $this->setFieldHeader();
        }

        if (empty($this->bookingLines)) {
            static::logWarning('BookingBatch ohne Buchungszeilen erstellt');
        }

        // Validierung
        $this->validate();

        return new DatevDocument(
            $this->metaHeader,
            $this->fieldHeader,
            $this->bookingLines
        );
    }

    /**
     * Validiert den Builder-Zustand vor dem Build.
     */
    private function validate(): void {
        if (!$this->metaHeader) {
            throw new RuntimeException('MetaHeader muss gesetzt sein');
        }

        if (!$this->fieldHeader) {
            throw new RuntimeException('FieldHeader muss gesetzt sein');
        }

        // Validiere MetaHeader
        $this->metaHeader->validate();

        // Validiere FieldHeader
        $this->fieldHeader->validate();

        // Validiere Buchungszeilen
        foreach ($this->bookingLines as $index => $booking) {
            try {
                $booking->validate();
            } catch (\Exception $e) {
                throw new RuntimeException("Buchungszeile $index ungültig: " . $e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Erstellt einen Standard-MetaHeader für BookingBatch.
     */
    private function createDefaultMetaHeader(): MetaHeaderLine {
        $definition = new MetaHeaderDefinition();
        $metaHeader = new MetaHeaderLine($definition);

        // Setze aktuelle Zeit als Erzeugungszeitpunkt
        $metaHeader->set(
            MetaHeaderField::ErzeugtAm,
            (new DateTimeImmutable())->format('YmdHis') . '000'
        );

        return $metaHeader;
    }

    /**
     * Liefert Statistiken über den aktuellen Builder-Zustand.
     */
    public function getStats(): array {
        return [
            'metaHeader_set' => $this->metaHeader !== null,
            'fieldHeader_set' => $this->fieldHeader !== null,
            'booking_count' => count($this->bookingLines),
            'field_count' => $this->fieldHeader?->countFields() ?? 0,
        ];
    }
}
