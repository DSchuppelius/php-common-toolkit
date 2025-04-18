<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TifFile.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\ConfiguredHelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\Files;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileSystem\FileExistsException;
use ERRORToolkit\Exceptions\FileSystem\FileInvalidException;
use ERRORToolkit\Exceptions\FileSystem\FileNotFoundException;
use Exception;

class TifFile extends ConfiguredHelperAbstract {
    protected const CONFIG_FILE = __DIR__ . '/../../../../config/tiff_executables.json';

    private const FILE_EXTENSION_PATTERN = "/\.tif{1,2}$/i";

    /**
     * Repariert eine TIFF-Datei, indem sie in ein JPEG-Bild konvertiert wird.
     *
     * @param string $file Der Pfad zur TIFF-Datei.
     * @param bool $forceRepair Gibt an, ob die Reparatur erzwungen werden soll.
     * @return string Der reparierte Dateiname.
     * @throws Exception Wenn ein Fehler bei der Reparatur auftritt.
     */
    public static function repair(string $file, bool $forceRepair = false): string {
        $mimeType = File::mimeType($file);

        if ($mimeType === 'image/jpeg' && preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            $newFilename = preg_replace(self::FILE_EXTENSION_PATTERN, ".jpg", $file);

            $command = self::getConfiguredCommand("convert", ["[OUTPUT]" => escapeshellarg($newFilename), "[INPUT]" => escapeshellarg($file)]);
            if (empty($command)) {
                self::logError("ImageMagick wurde nicht konfiguriert oder ist nicht installiert.");
                throw new Exception("ImageMagick wurde nicht konfiguriert oder ist nicht installiert.");
            }

            File::rename($file, $newFilename);

            if (Shell::executeShellCommand($command)) {
                self::logInfo("TIFF-Datei erfolgreich von JPEG repariert: $newFilename");
            } else {
                self::logError("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
                throw new Exception("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
            }

            File::delete($newFilename);

            return $file;
        } elseif ($mimeType === 'image/tiff' && !preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            $newFilename = preg_replace("/\.[^.]+$/", ".tif", $file);
            File::rename($file, $newFilename);
            return self::repair($newFilename);
        } elseif ($mimeType === 'image/tiff' && preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            self::logInfo("Die Datei ist bereits im TIFF-Format: $file");
            if ($forceRepair) {
                self::logNotice("Erzwinge Reparatur der TIFF-Datei: $file");
                $newFilename = preg_replace(self::FILE_EXTENSION_PATTERN, ".original.tif", $file);

                $command = self::getConfiguredCommand("convert-monochrome", ["[OUTPUT]" => escapeshellarg($newFilename), "[INPUT]" => escapeshellarg($file)]);
                if (empty($command)) {
                    self::logError("ImageMagick wurde nicht konfiguriert oder ist nicht installiert.");
                    throw new Exception("ImageMagick wurde nicht konfiguriert oder ist nicht installiert.");
                }

                File::rename($file, $newFilename);

                self::logNotice("Erstelle monochrome Kopie der TIFF-Datei: $newFilename");
                if (Shell::executeShellCommand($command)) {
                    self::logInfo("TIFF-Datei erfolgreich repariert: $newFilename");
                } else {
                    self::logError("Fehler bei der Reparatur von TIFF: $newFilename");
                    throw new Exception("Fehler bei der Reparatur von TIFF: $newFilename");
                }

                File::delete($newFilename);

                return $file;
            }
        } else {
            self::logError("Die Datei ist nicht im TIFF-Format: $file");
            throw new Exception("Die Datei ist nicht im TIFF-Format: $file");
        }

        return $file;
    }

    /**
     * Konvertiert eine TIFF-Datei in eine PDF-Datei.
     *
     * @param string $tiffFile Der Pfad zur TIFF-Datei.
     * @param string|null $pdfFile Der Pfad zur Ausgabedatei (optional).
     * @param bool $compressed Gibt an, ob die PDF-Datei komprimiert werden soll.
     * @param bool $deleteSourceFile Gibt an, ob die Quell-TIFF-Datei gelöscht werden soll.
     * @throws FileExistsException Wenn die Ausgabedatei bereits existiert.
     * @throws FileNotFoundException Wenn die Quell-TIFF-Datei nicht gefunden wird.
     * @throws FileInvalidException Wenn die Quell-TIFF-Datei ungültig ist.
     * @throws Exception Wenn ein Fehler bei der Konvertierung auftritt.
     */
    public static function convertToPdf(string $tiffFile, ?string $pdfFile = null, bool $compressed = true, bool $deleteSourceFile = true): void {
        if (!File::exists($tiffFile)) {
            self::logError("Die Datei existiert nicht: $tiffFile");
            throw new FileNotFoundException("Die Datei existiert nicht: $tiffFile");
        } elseif (!is_null($pdfFile) && File::exists($pdfFile)) {
            self::logError("Die Datei existiert bereits: $pdfFile");
            throw new FileExistsException("Die Datei existiert bereits: $pdfFile");
        } elseif (!self::isValid($tiffFile)) {
            try {
                $tiffFile = self::repair($tiffFile);  // Reparierter Dateiname wird zurückgegeben
            } catch (Exception $e) {
                self::logError("Die Datei ist nicht gültig: $tiffFile");
                throw new FileInvalidException("Die Datei ist nicht gültig: $tiffFile");
            }
        }

        if (is_null($pdfFile)) {
            $pdfFile = preg_replace(self::FILE_EXTENSION_PATTERN, ".pdf", $tiffFile);
        }

        if (File::exists($pdfFile)) {
            self::logError("Die Datei existiert bereits: $pdfFile");
            throw new FileExistsException("Die Datei existiert bereits: $pdfFile");
        }

        $commandName = $compressed ? "tiff2pdf-compressed" : "tiff2pdf";
        $command = self::getConfiguredCommand($commandName, ["[INPUT]" => escapeshellarg($tiffFile), "[OUTPUT]" => escapeshellarg($pdfFile)]);

        if (empty($command)) {
            self::logError("tiff2pdf wurde nicht konfiguriert oder ist nicht installiert.");
            throw new Exception("tiff2pdf wurde nicht konfiguriert oder ist nicht installiert.");
        }

        File::wait4Ready($tiffFile);
        Shell::executeShellCommand($command);

        if (PdfFile::isValid($pdfFile)) {
            self::logInfo("TIFF-Datei erfolgreich in PDF umgewandelt: $tiffFile");
        } elseif ($compressed) {
            self::logWarning("Probleme bei der Umwandlung von TIFF in PDF: $tiffFile. Versuche erneute Konvertierung ohne Kompression.");
            File::delete($pdfFile);
            TifFile::repair($tiffFile, true);
            self::convertToPdf($tiffFile, $pdfFile, false, false);

            if (PdfFile::isValid($pdfFile)) {
                self::logInfo("TIFF-Datei erfolgreich ohne Kompression in PDF umgewandelt: $tiffFile");
            } else {
                self::logError("Erneuter Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
                File::delete($pdfFile);
                throw new Exception("Erneuter Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
            }
        } else {
            self::logError("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
            File::delete($pdfFile);
            throw new Exception("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
        }

        if ($deleteSourceFile) {
            File::delete($tiffFile);
        }
    }

    /**
     * Führt mehrere TIFF-Dateien zu einer einzigen zusammen.
     *
     * @param array $tiffFiles Die Pfade zu den TIFF-Dateien, die zusammengeführt werden sollen.
     * @param string $mergedFile Der Pfad zur Ausgabedatei.
     * @param bool $deleteSourceFiles Gibt an, ob die Quell-TIFF-Dateien nach dem Zusammenführen gelöscht werden sollen.
     * @throws FileExistsException Wenn die Ausgabedatei bereits existiert.
     * @throws FileNotFoundException Wenn eine der Quell-TIFF-Dateien nicht gefunden wird.
     * @throws Exception Wenn ein Fehler beim Zusammenführen auftritt.
     */
    public static function merge(array $tiffFiles, string $mergedFile, bool $deleteSourceFiles = true): void {
        if (File::exists($mergedFile)) {
            self::logError("Die Datei existiert bereits: $mergedFile");
            throw new FileExistsException("Die Datei existiert bereits: $mergedFile");
        } elseif (!Files::exists($tiffFiles)) {
            self::logError("Die Dateien existieren nicht: " . implode(", ", $tiffFiles));
            throw new FileNotFoundException("Die Dateien existieren nicht: " . implode(", ", $tiffFiles));
        }

        $command = self::getConfiguredCommand("tiffcp", ["[INPUT]" => implode(" ", array_map('escapeshellarg', $tiffFiles)), "[OUTPUT]" => escapeshellarg($mergedFile)]);

        if (empty($command)) {
            self::logError("tiffcp wurde nicht konfiguriert oder ist nicht installiert.");
            throw new Exception("tiffcp wurde nicht konfiguriert oder ist nicht installiert.");
        }

        Shell::executeShellCommand($command);

        self::logInfo("TIFF-Dateien erfolgreich zusammengeführt: $mergedFile");

        if ($deleteSourceFiles) {
            Files::delete($tiffFiles);
        }
    }

    /**
     * Überprüft, ob die TIFF-Datei gültig ist.
     *
     * @param string $file Der Pfad zur TIFF-Datei.
     * @return bool True, wenn die TIFF-Datei gültig ist, andernfalls false.
     * @throws FileNotFoundException Wenn die Datei nicht gefunden wird.
     * @throws Exception Wenn ein Fehler bei der Validierung auftritt.
     */
    public static function isValid(string $file): bool {
        if (!File::exists($file)) {
            self::logError("Datei existiert nicht: $file");
            throw new FileNotFoundException("Datei existiert nicht: $file");
        }

        if (preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            $command = self::getConfiguredCommand("tiffinfo", ["[INPUT]" => escapeshellarg($file)]);
            $output = [];

            if (empty($command)) {
                self::logError("tiffinfo wurde nicht konfiguriert oder ist nicht installiert.");
                throw new Exception("tiffinfo wurde nicht konfiguriert oder ist nicht installiert.");
            } elseif (Shell::executeShellCommand($command, $output)) {
                if (str_contains(strtolower(implode($output)), "not a tiff")) {
                    self::logWarning("TIFF-Datei ist ungültig: $file");
                    return false;
                }
                self::logInfo("TIFF-Datei ist gültig: $file");
                return true;
            } else {
                self::logWarning("TIFF-Datei ist ungültig: $file");
                return false;
            }
        }
        self::logWarning("Datei ist keine TIFF-Datei: $file");
        return false;
    }
}