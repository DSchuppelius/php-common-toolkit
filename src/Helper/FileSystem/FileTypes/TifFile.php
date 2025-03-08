<?php
/*
 * Created on   : Sun Oct 06 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : TifFileHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\FileSystem\FileTypes;

use CommonToolkit\Contracts\Abstracts\HelperAbstract;
use CommonToolkit\Helper\FileSystem\File;
use CommonToolkit\Helper\FileSystem\Files;
use CommonToolkit\Helper\Shell;
use ERRORToolkit\Exceptions\FileNotFoundException;
use Exception;

class TifFile extends HelperAbstract {
    private const FILE_EXTENSION_PATTERN = "/\.tif{1,2}$/i";

    public static function repair(string $file, bool $forceRepair = false): string {

        self::setLogger();
        $mimeType = File::mimeType($file);

        if ($mimeType !== 'image/tiff' && preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            $newFilename = preg_replace(self::FILE_EXTENSION_PATTERN, ".jpg", $file);
            File::rename($file, $newFilename);

            $command = Shell::getPlatformSpecificCommand(
                sprintf("convert %s %s", escapeshellarg($newFilename), escapeshellarg($file)),
                sprintf("magick %s %s", escapeshellarg($newFilename), escapeshellarg($file))
            );
            if (Shell::executeShellCommand($command)) {
                self::$logger->info("TIFF-Datei erfolgreich von JPEG repariert: $newFilename");
            } else {
                self::$logger->error("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
                throw new Exception("Fehler bei der Reparatur von TIFF nach JPEG: $newFilename");
            }

            File::delete($newFilename);

            return $file;
        } elseif ($mimeType === 'image/tiff' && !preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            $newFilename = preg_replace("/\.[^.]+$/", ".tif", $file);
            File::rename($file, $newFilename);
            return self::repair($newFilename);
        } elseif ($mimeType === 'image/tiff' && preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            self::$logger->info("Die Datei ist bereits im TIFF-Format: $file");
            if ($forceRepair) {
                self::$logger->notice("Erzwinge Reparatur der TIFF-Datei: $file");
                $newFilename = preg_replace(self::FILE_EXTENSION_PATTERN, ".original.tif", $file);
                File::rename($file, $newFilename);

                self::$logger->notice("Erstelle monochrome Kopie der TIFF-Datei: $newFilename");
                $command = Shell::getPlatformSpecificCommand(
                    sprintf("convert %s -monochrome %s", escapeshellarg($newFilename), escapeshellarg($file)),
                    sprintf("magick %s -monochrome %s", escapeshellarg($newFilename), escapeshellarg($file))
                );
                if (Shell::executeShellCommand($command)) {
                    self::$logger->info("TIFF-Datei erfolgreich repariert: $newFilename");
                } else {
                    self::$logger->error("Fehler bei der Reparatur von TIFF: $newFilename");
                    throw new Exception("Fehler bei der Reparatur von TIFF: $newFilename");
                }

                File::delete($newFilename);

                return $file;
            }
        } else {
            self::$logger->error("Die Datei ist nicht im TIFF-Format: $file");
            throw new Exception("Die Datei ist nicht im TIFF-Format: $file");
        }

        return $file;
    }

    public static function convertToPdf(string $tiffFile, ?string $pdfFile = null, bool $compressed = true, bool $deleteSourceFile = true): void {
        self::setLogger();
        if (!File::exists($tiffFile)) {
            self::$logger->error("Die Datei existiert nicht: $tiffFile");
            throw new FileNotFoundException("Die Datei existiert nicht: $tiffFile");
        } elseif (!is_null($pdfFile) && File::exists($pdfFile)) {
            self::$logger->error("Die Datei existiert bereits: $pdfFile");
            throw new Exception("Die Datei existiert bereits: $pdfFile");
        } elseif (!self::isValid($tiffFile)) {
            try {
                $tiffFile = self::repair($tiffFile);  // Reparierter Dateiname wird zurückgegeben
            } catch (Exception $e) {
                self::$logger->error("Die Datei ist nicht gültig: $tiffFile");
                throw new Exception("Die Datei ist nicht gültig: $tiffFile");
            }
        }

        if (is_null($pdfFile)) {
            $pdfFile = preg_replace(self::FILE_EXTENSION_PATTERN, ".pdf", $tiffFile);
        }

        if (File::exists($pdfFile)) {
            self::$logger->error("Die Datei existiert bereits: $pdfFile");
            throw new Exception("Die Datei existiert bereits: $pdfFile");
        }
        $command = Shell::getPlatformSpecificCommand(
            ($compressed
                ? "tiff2pdf -F -j -c common_fileconverter -a common_fileconverter -o '$pdfFile' '$tiffFile' 2>&1"
                : "tiff2pdf -F -n -c common_fileconverter -a common_fileconverter -o '$pdfFile' '$tiffFile' 2>&1"),
            ($compressed
                ? "tiff2pdf -j -c common_fileconverter -a common_fileconverter -o \"$pdfFile\" \"$tiffFile\" 2>&1"
                : "tiff2pdf -n -c common_fileconverter -a common_fileconverter -o \"$pdfFile\" \"$tiffFile\" 2>&1")
        );

        File::wait4Ready($tiffFile);
        Shell::executeShellCommand($command);

        if (PdfFile::isValid($pdfFile)) {
            self::$logger->info("TIFF-Datei erfolgreich in PDF umgewandelt: $tiffFile");
        } elseif ($compressed) {
            self::$logger->warning("Probleme bei der Umwandlung von TIFF in PDF: $tiffFile. Versuche erneute Konvertierung ohne Kompression.");
            File::delete($pdfFile);
            TifFile::repair($tiffFile, true);
            self::convertToPdf($tiffFile, $pdfFile, false, false);

            if (PdfFile::isValid($pdfFile)) {
                self::$logger->info("TIFF-Datei erfolgreich ohne Kompression in PDF umgewandelt: $tiffFile");
            } else {
                self::$logger->error("Erneuter Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
                File::delete($pdfFile);
                throw new Exception("Erneuter Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
            }
        } else {
            self::$logger->error("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
            File::delete($pdfFile);
            throw new Exception("Fehler bei der Umwandlung von TIFF in PDF: $tiffFile");
        }

        if ($deleteSourceFile) {
            File::delete($tiffFile);
        }
    }

    public static function merge(array $tiffFiles, string $mergedFile, bool $deleteSourceFiles = true): void {
        self::setLogger();

        if (File::exists($mergedFile)) {
            self::$logger->error("Die Datei existiert bereits: $mergedFile");
            throw new Exception("Die Datei existiert bereits: $mergedFile");
        } elseif (!Files::exists($tiffFiles)) {
            self::$logger->error("Die Dateien existieren nicht: " . implode(", ", $tiffFiles));
            throw new FileNotFoundException("Die Dateien existieren nicht: " . implode(", ", $tiffFiles));
        }

        $command = sprintf("tiffcp %s %s", implode(" ", array_map('escapeshellarg', $tiffFiles)), escapeshellarg($mergedFile));
        Shell::executeShellCommand($command);

        self::$logger->info("TIFF-Dateien erfolgreich zusammengeführt: $mergedFile");

        if ($deleteSourceFiles) {
            Files::delete($tiffFiles);
        }
    }

    public static function isValid(string $file): bool {
        self::setLogger();

        if (!File::exists($file)) {
            self::$logger->error("Datei existiert nicht: $file");
            throw new FileNotFoundException("Datei existiert nicht: $file");
        }

        if (preg_match(self::FILE_EXTENSION_PATTERN, $file)) {
            $command = sprintf("tiffinfo %s 2>&1", escapeshellarg($file));
            $output = [];

            if (Shell::executeShellCommand($command, $output)) {
                if (str_contains(strtolower(implode($output)), "not a tiff")) {
                    self::$logger->warning("TIFF-Datei ist ungültig: $file");
                    return false;
                }
                self::$logger->info("TIFF-Datei ist gültig: $file");
                return true;
            } else {
                self::$logger->warning("TIFF-Datei ist ungültig: $file");
                return false;
            }
        }
        self::$logger->warning("Datei ist keine TIFF-Datei: $file");
        return false;
    }
}
