<?php
/*
 * Created on   : Fri Dec 27 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : SwiftMessageParser.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Parsers;

use CommonToolkit\Entities\Common\Banking\Swift\ApplicationHeader;
use CommonToolkit\Entities\Common\Banking\Swift\BasicHeader;
use CommonToolkit\Entities\Common\Banking\Swift\Message;
use CommonToolkit\Entities\Common\Banking\Swift\Trailer;
use CommonToolkit\Entities\Common\Banking\Swift\UserHeader;
use RuntimeException;

/**
 * Parser für SWIFT FIN Messages mit Envelope (5-Block-Struktur)
 * 
 * Unterstützt beide Formate:
 * 1. Vollständige SWIFT-Nachrichten mit {1:}...{5:} Blöcken
 * 2. Raw MT-Daten ohne Envelope (nur Text-Block Inhalt)
 */
final class SwiftMessageParser {
    /**
     * Prüft ob der Input ein SWIFT Envelope hat
     */
    public static function hasEnvelope(string $input): bool {
        $trimmed = trim($input);
        return str_starts_with($trimmed, '{1:');
    }

    /**
     * Parst eine vollständige SWIFT-Nachricht mit Envelope
     * 
     * @param string $input Die vollständige SWIFT-Nachricht
     * @return Message Das geparste Message-Objekt
     * @throws RuntimeException Bei ungültigem Format
     */
    public static function parse(string $input): Message {
        if (!self::hasEnvelope($input)) {
            throw new RuntimeException("Keine SWIFT-Envelope gefunden. Verwenden Sie extractTextBlock() für Raw-MT-Daten.");
        }

        $blocks = self::extractBlocks($input);

        if (!isset($blocks[1])) {
            throw new RuntimeException("Basic Header (Block 1) fehlt");
        }

        if (!isset($blocks[2])) {
            throw new RuntimeException("Application Header (Block 2) fehlt");
        }

        if (!isset($blocks[4])) {
            throw new RuntimeException("Text Block (Block 4) fehlt");
        }

        $basicHeader = BasicHeader::parse($blocks[1]);
        $applicationHeader = ApplicationHeader::parse($blocks[2]);
        $userHeader = isset($blocks[3]) ? UserHeader::parse($blocks[3]) : null;
        $textBlock = self::normalizeTextBlock($blocks[4]);
        $trailer = isset($blocks[5]) ? Trailer::parse($blocks[5]) : null;

        return new Message(
            basicHeader: $basicHeader,
            applicationHeader: $applicationHeader,
            textBlock: $textBlock,
            userHeader: $userHeader,
            trailer: $trailer
        );
    }

    /**
     * Parst mehrere SWIFT-Nachrichten aus einem Input
     * 
     * @param string $input Der Input mit einer oder mehreren SWIFT-Nachrichten
     * @return array<Message> Array von geparsten Messages
     */
    public static function parseMultiple(string $input): array {
        $messages = [];

        // Finde alle Positionen von {1: (Start einer neuen Nachricht)
        $offset = 0;
        while (($pos = strpos($input, '{1:', $offset)) !== false) {
            // Finde das Ende dieser Nachricht (nächstes {1: oder Ende)
            $nextPos = strpos($input, '{1:', $pos + 3);

            if ($nextPos === false) {
                // Letzte Nachricht
                $messageContent = substr($input, $pos);
            } else {
                $messageContent = substr($input, $pos, $nextPos - $pos);
            }

            try {
                $messages[] = self::parse($messageContent);
            } catch (RuntimeException $e) {
                // Ungültige Nachricht überspringen oder loggen
            }

            $offset = $pos + 3;
        }

        return $messages;
    }

    /**
     * Extrahiert nur den Text-Block aus einer SWIFT-Nachricht oder gibt Raw-Daten zurück
     * 
     * Diese Methode kann sowohl SWIFT-Nachrichten mit Envelope als auch
     * Raw-MT-Daten ohne Envelope verarbeiten.
     * 
     * @param string $input Die SWIFT-Nachricht oder Raw-MT-Daten
     * @return string Der Text-Block Inhalt
     */
    public static function extractTextBlock(string $input): string {
        if (!self::hasEnvelope($input)) {
            // Kein Envelope - Input ist bereits der Text-Block
            return trim($input);
        }

        $blocks = self::extractBlocks($input);

        if (!isset($blocks[4])) {
            throw new RuntimeException("Text Block (Block 4) nicht gefunden");
        }

        return self::normalizeTextBlock($blocks[4]);
    }

    /**
     * Extrahiert alle Blöcke aus einer SWIFT-Nachricht
     * 
     * @param string $input Die SWIFT-Nachricht
     * @return array<int, string> Array mit Block-Nummer => Block-Inhalt
     */
    private static function extractBlocks(string $input): array {
        $blocks = [];

        // Block 1 und 2: Einfache Blöcke ohne geschachtelte {}
        for ($blockNum = 1; $blockNum <= 2; $blockNum++) {
            $pattern = '/\{' . $blockNum . ':([^}]+)\}/';
            if (preg_match($pattern, $input, $match)) {
                $blocks[$blockNum] = $match[1];
            }
        }

        // Block 3: User Header mit geschachtelten {tag:value} Paaren
        // Format: {3:{108:MUR12345}{119:STP}}
        if (preg_match('/\{3:((?:\{[^}]+\})+)\}/', $input, $match)) {
            $blocks[3] = $match[1];
        }

        // Block 4: Text-Block endet mit -}
        // Format: {4:\n:20:REF\n:25:IBAN\n-}
        if (preg_match('/\{4:(.*?)\n?-\}/s', $input, $match)) {
            $blocks[4] = $match[1];
        }

        // Block 5: Trailer mit geschachtelten {tag:value} Paaren
        // Format: {5:{CHK:123456789ABC}{TNG:}}
        if (preg_match('/\{5:((?:\{[^}]+\})+)\}/', $input, $match)) {
            $blocks[5] = $match[1];
        }

        return $blocks;
    }

    /**
     * Normalisiert den Text-Block (entfernt führende/nachfolgende Leerzeichen und Zeilenumbrüche)
     */
    private static function normalizeTextBlock(string $textBlock): string {
        // Entferne führende Zeilenumbrüche
        $textBlock = ltrim($textBlock, "\r\n");

        // Entferne nachfolgende Leerzeichen vor dem -
        $textBlock = rtrim($textBlock);

        return $textBlock;
    }

    /**
     * Validiert eine SWIFT-Nachricht
     * 
     * @param string $input Die zu validierende Nachricht
     * @return array<string, mixed> Validierungsergebnis mit 'valid' und 'errors'
     */
    public static function validate(string $input): array {
        $errors = [];
        $valid = true;

        if (!self::hasEnvelope($input)) {
            return [
                'valid' => true,
                'hasEnvelope' => false,
                'errors' => [],
                'message' => 'Raw MT-Daten ohne Envelope'
            ];
        }

        $blocks = self::extractBlocks($input);

        if (!isset($blocks[1])) {
            $errors[] = 'Basic Header (Block 1) fehlt';
            $valid = false;
        }

        if (!isset($blocks[2])) {
            $errors[] = 'Application Header (Block 2) fehlt';
            $valid = false;
        }

        if (!isset($blocks[4])) {
            $errors[] = 'Text Block (Block 4) fehlt';
            $valid = false;
        }

        // Validiere Block 1 Format
        // Format: F01GSCRUS33AXXX0000000000 (AppID + ServiceID + LT Address + optional Session/Sequence)
        if (isset($blocks[1]) && !preg_match('/^[FAL]\d{2}[A-Z0-9]{12}\d*$/', $blocks[1])) {
            $errors[] = 'Basic Header (Block 1) hat ungültiges Format';
        }

        // Validiere Block 2 Format
        if (isset($blocks[2]) && !preg_match('/^[IO]\d{3}/', $blocks[2])) {
            $errors[] = 'Application Header (Block 2) hat ungültiges Format';
        }

        return [
            'valid' => $valid && empty($errors),
            'hasEnvelope' => true,
            'errors' => $errors,
            'blocks' => array_keys($blocks)
        ];
    }
}
