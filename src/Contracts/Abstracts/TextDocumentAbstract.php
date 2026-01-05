<?php
/*
 * Created on   : Sun Jan 05 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TextDocumentAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Contracts\Abstracts;

use CommonToolkit\Helper\Data\StringHelper;
use ERRORToolkit\Traits\ErrorLog;

/**
 * Abstrakte Basisklasse für textbasierte Dokumente.
 * Verwaltet Encoding, BOM (Byte Order Mark) und Zeilenumbrüche.
 *
 * @package CommonToolkit\Contracts\Abstracts
 */
abstract class TextDocumentAbstract {
    use ErrorLog;

    /** Standard-Encoding für Textdokumente */
    public const DEFAULT_ENCODING = 'UTF-8';

    /** Zeilenumbruch-Konstanten */
    public const LINE_ENDING_LF = "\n";         // Unix/Linux/macOS
    public const LINE_ENDING_CRLF = "\r\n";     // Windows
    public const LINE_ENDING_CR = "\r";         // Altes macOS (vor OS X)

    /**
     * Die Zeichenkodierung des Dokuments.
     */
    protected string $encoding = self::DEFAULT_ENCODING;

    /**
     * Ob ein BOM (Byte Order Mark) beim Export geschrieben werden soll.
     */
    protected bool $withBom = false;

    /**
     * Der Zeilenumbruch-Stil für den Export.
     */
    protected string $lineEnding = self::LINE_ENDING_LF;

    /**
     * Gibt die Zeichenkodierung des Dokuments zurück.
     *
     * @return string Die Zeichenkodierung (z.B. 'UTF-8', 'ISO-8859-1')
     */
    public function getEncoding(): string {
        return $this->encoding;
    }

    /**
     * Setzt die Zeichenkodierung des Dokuments.
     *
     * @param string $encoding Die Zeichenkodierung (z.B. 'UTF-8', 'ISO-8859-1')
     * @return static Fluent interface
     */
    public function setEncoding(string $encoding): static {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Gibt zurück, ob ein BOM beim Export geschrieben werden soll.
     *
     * @return bool
     */
    public function hasBom(): bool {
        return $this->withBom;
    }

    /**
     * Aktiviert das Schreiben eines BOM beim Export.
     *
     * @return static Fluent interface
     */
    public function withBom(): static {
        $this->withBom = true;
        return $this;
    }

    /**
     * Deaktiviert das Schreiben eines BOM beim Export.
     *
     * @return static Fluent interface
     */
    public function withoutBom(): static {
        $this->withBom = false;
        return $this;
    }

    /**
     * Setzt den BOM-Status.
     *
     * @param bool $withBom Ob ein BOM geschrieben werden soll
     * @return static Fluent interface
     */
    public function setBom(bool $withBom): static {
        $this->withBom = $withBom;
        return $this;
    }

    /**
     * Gibt den Zeilenumbruch-Stil zurück.
     *
     * @return string Der Zeilenumbruch (\n, \r\n oder \r)
     */
    public function getLineEnding(): string {
        return $this->lineEnding;
    }

    /**
     * Setzt den Zeilenumbruch-Stil.
     *
     * @param string $lineEnding Der Zeilenumbruch (\n, \r\n oder \r)
     * @return static Fluent interface
     */
    public function setLineEnding(string $lineEnding): static {
        $this->lineEnding = $lineEnding;
        return $this;
    }

    /**
     * Setzt den Zeilenumbruch auf Unix-Stil (LF).
     *
     * @return static Fluent interface
     */
    public function withUnixLineEnding(): static {
        $this->lineEnding = self::LINE_ENDING_LF;
        return $this;
    }

    /**
     * Setzt den Zeilenumbruch auf Windows-Stil (CRLF).
     *
     * @return static Fluent interface
     */
    public function withWindowsLineEnding(): static {
        $this->lineEnding = self::LINE_ENDING_CRLF;
        return $this;
    }

    /**
     * Gibt das BOM für das aktuelle Encoding zurück, falls aktiviert.
     *
     * @return string Das BOM oder ein leerer String
     */
    public function getBomBytes(): string {
        if (!$this->withBom) {
            return '';
        }

        $bom = StringHelper::getBomForEncoding($this->encoding);
        return $bom ?? '';
    }

    /**
     * Prüft, ob das aktuelle Encoding ein BOM unterstützt.
     *
     * @return bool True wenn ein BOM für dieses Encoding existiert
     */
    public function supportsBom(): bool {
        return StringHelper::getBomForEncoding($this->encoding) !== null;
    }

    /**
     * Konvertiert den Inhalt zum Ziel-Encoding und fügt optional ein BOM hinzu.
     *
     * @param string $content Der zu kodierende Inhalt
     * @param string|null $targetEncoding Das Ziel-Encoding (null = aktuelles Encoding)
     * @param bool|null $withBom Ob ein BOM hinzugefügt werden soll (null = aktuelle Einstellung)
     * @return string Der kodierte Inhalt mit optionalem BOM
     */
    protected function encodeContent(string $content, ?string $targetEncoding = null, ?bool $withBom = null): string {
        $encoding = $targetEncoding ?? $this->encoding;
        $addBom = $withBom ?? $this->withBom;

        // Encoding-Konvertierung falls nötig
        if ($encoding !== self::DEFAULT_ENCODING) {
            $converted = mb_convert_encoding($content, $encoding, self::DEFAULT_ENCODING);
            if ($converted !== false) {
                $content = $converted;
            } else {
                $this->logWarning("Konvertierung zu $encoding fehlgeschlagen, nutze Original");
            }
        }

        // BOM hinzufügen falls gewünscht
        if ($addBom) {
            $bom = StringHelper::getBomForEncoding($encoding);
            if ($bom !== null) {
                $content = $bom . $content;
            }
        }

        return $content;
    }

    /**
     * Normalisiert Zeilenumbrüche im Inhalt zum konfigurierten Stil.
     *
     * @param string $content Der Inhalt mit gemischten Zeilenumbrüchen
     * @return string Der Inhalt mit normalisierten Zeilenumbrüchen
     */
    protected function normalizeLineEndings(string $content): string {
        // Erst alle Varianten zu LF normalisieren
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Dann zum Ziel-Stil konvertieren
        if ($this->lineEnding !== "\n") {
            $content = str_replace("\n", $this->lineEnding, $content);
        }

        return $content;
    }
}
