<?php
/*
 * Created on   : Sun Jul 26 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : EmailAddress.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\ValueObjects;

use CommonToolkit\Helper\Data\EmailHelper;
use ERRORToolkit\Traits\ErrorLog;
use InvalidArgumentException;

/**
 * Immutable, validierte E-Mail-Adresse.
 *
 * Die Konstruktion verwendet ausschließlich die deterministische
 * Formatprüfung ({@see EmailHelper::isEmail()}) — DNS-/MX-Prüfungen sind
 * ausdrücklich kein Konstruktorbestandteil. Die Normalisierung delegiert an
 * {@see EmailHelper::normalize()} (Kleinschreibung, getrimmt) OHNE
 * providerspezifisches Entfernen von Punkten.
 *
 * SENSIBLER WERT: Eine E-Mail-Adresse ist ein personenbezogener
 * Identifikator. Die Klasse implementiert deshalb bewusst WEDER `Stringable`
 * NOCH `JsonSerializable` — der Klarwert ist ausschließlich über den bewusst
 * aufgerufenen {@see getValue()}-Getter verfügbar; für Anzeigen gibt es
 * {@see masked()}.
 *
 * @example
 * ```php
 * $email = EmailAddress::of('Max.Mustermann@EXAMPLE.com');
 * $email->getValue();  // "max.mustermann@example.com"
 * $email->masked();    // "ma**********nn@example.com"
 * $email->getDomain(); // "example.com"
 * ```
 */
final class EmailAddress {
    use ErrorLog;

    private readonly string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    // ========================================================================
    // Konstruktion
    // ========================================================================

    /**
     * Erzeugt eine validierte E-Mail-Adresse (normalisiert, Formatprüfung).
     *
     * Die Fehlermeldung enthält bewusst nicht den Eingabewert — auch eine
     * beinahe gültige Adresse gehört nicht ins Log.
     *
     * @throws InvalidArgumentException Bei ungültigem Format.
     */
    public static function of(string $value): self {
        $email = self::tryFrom($value);
        if ($email === null) {
            self::logErrorAndThrow(InvalidArgumentException::class, 'Ungültige E-Mail-Adresse (Formatprüfung fehlgeschlagen, Länge ' . strlen(trim($value)) . ').');
        }

        return $email;
    }

    /**
     * Wie {@see of()}, liefert aber bei `null`, leerer oder ungültiger
     * Eingabe `null` statt einer Exception.
     */
    public static function tryFrom(?string $value): ?self {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = EmailHelper::normalize($value);
        if (!EmailHelper::isEmail($normalized)) {
            return null;
        }

        return new self($normalized);
    }

    // ========================================================================
    // Zugriff
    // ========================================================================

    /**
     * Klarwert (normalisiert, z.B. "max@example.com") — nur bewusst abrufen;
     * für Anzeigen {@see masked()} verwenden.
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * Local-Part vor dem "@" (normalisiert).
     */
    public function getLocalPart(): string {
        // Für eine validierte Instanz nie null; Fallback nur für PHPStan.
        return EmailHelper::extractLocalPart($this->value) ?? '';
    }

    /**
     * Domain nach dem "@" (normalisiert).
     */
    public function getDomain(): string {
        // Für eine validierte Instanz nie null; Fallback nur für PHPStan.
        return EmailHelper::extractDomain($this->value) ?? '';
    }

    /**
     * Maskierte Darstellung für Anzeigen/Logs: zeigt höchstens $showChars
     * Zeichen am Anfang und Ende des Local-Parts, die Domain bleibt sichtbar
     * (z.B. "ma**********nn@example.com"). Delegiert an
     * {@see EmailHelper::mask()}, der auch kurze Local-Parts sicher maskiert.
     */
    public function masked(int $showChars = 2): string {
        return EmailHelper::mask($this->value, $showChars);
    }

    /**
     * Gehört die Domain zu einem Wegwerf-Mail-Anbieter?
     */
    public function isDisposable(): bool {
        return EmailHelper::isDisposableEmail($this->value);
    }

    /**
     * Gehört die Domain zu einem Freemail-Anbieter (Gmail, Outlook, GMX, …)?
     */
    public function isFreeProvider(): bool {
        return EmailHelper::isFreeEmailProvider($this->value);
    }

    /**
     * Gleichheit der normalisierten Darstellung.
     */
    public function equals(self $other): bool {
        return $this->value === $other->value;
    }
}
