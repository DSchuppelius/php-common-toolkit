<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : UnitConversionHelper.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace CommonToolkit\Helper\Data;

use CommonToolkit\Enums\{AccelerationUnit, AngleUnit, AreaUnit, DataSizeUnit, ElectricCapacitanceUnit, ElectricCurrentUnit, ElectricResistanceUnit, ElectricVoltageUnit, EnergyUnit, ForceUnit, FrequencyUnit, IlluminanceUnit, LengthUnit, MagneticFluxDensityUnit, PowerUnit, PressureUnit, SpeedUnit, TimeUnit, TorqueUnit, ViscosityUnit, VolumeUnit, WeightUnit};
use ERRORToolkit\Traits\ErrorLog;

/**
 * Hilfsklasse für Einheitsumrechnungen.
 *
 * Unterstützt:
 *  - Zeit            (TimeUnit)
 *  - Gewicht         (WeightUnit)
 *  - Volumen         (VolumeUnit)
 *  - Länge           (LengthUnit)
 *  - Fläche          (AreaUnit)
 *  - Geschwindigkeit (SpeedUnit)
 *  - Druck           (PressureUnit)
 *  - Energie         (EnergyUnit)
 *  - Leistung        (PowerUnit)
 *  - Winkel          (AngleUnit)
 *  - Datenmenge      (DataSizeUnit)
 *  - Kraft            (ForceUnit)
 *  - Frequenz         (FrequencyUnit)
 *  - Beschleunigung   (AccelerationUnit)
 *  - Elektr. Spannung (ElectricVoltageUnit)
 *  - Elektr. Strom         (ElectricCurrentUnit)
 *  - Elektr. Widerstand    (ElectricResistanceUnit)
 *  - Elektr. Kapazität     (ElectricCapacitanceUnit)
 *  - Drehmoment            (TorqueUnit)
 *  - Dynamische Viskosität (ViscosityUnit)
 *  - Beleuchtungsstärke    (IlluminanceUnit)
 *  - Magn. Flussdichte     (MagneticFluxDensityUnit)
 */
class UnitConversionHelper {
    use ErrorLog;

    /**
     * Rechnet einen Zeitwert von einer Einheit in eine andere um.
     *
     * @param float    $value Eingangswert
     * @param TimeUnit $from  Quelleinheit
     * @param TimeUnit $to    Zieleinheit
     * @return float          Umgerechneter Wert
     */
    public static function convertTime(float $value, TimeUnit $from, TimeUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toSeconds() / $to->toSeconds();
    }

    /**
     * Rechnet einen Gewichtswert von einer Einheit in eine andere um.
     *
     * @param float      $value Eingangswert
     * @param WeightUnit $from  Quelleinheit
     * @param WeightUnit $to    Zieleinheit
     * @return float            Umgerechneter Wert
     */
    public static function convertWeight(float $value, WeightUnit $from, WeightUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toGrams() / $to->toGrams();
    }

    /**
     * Rechnet einen Volumenwert von einer Einheit in eine andere um.
     *
     * @param float      $value Eingangswert
     * @param VolumeUnit $from  Quelleinheit
     * @param VolumeUnit $to    Zieleinheit
     * @return float            Umgerechneter Wert
     */
    public static function convertVolume(float $value, VolumeUnit $from, VolumeUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toMilliliters() / $to->toMilliliters();
    }

    /**
     * Rechnet einen Längenwert von einer Einheit in eine andere um.
     *
     * @param float      $value Eingangswert
     * @param LengthUnit $from  Quelleinheit
     * @param LengthUnit $to    Zieleinheit
     * @return float            Umgerechneter Wert
     */
    public static function convertLength(float $value, LengthUnit $from, LengthUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toMillimeters() / $to->toMillimeters();
    }

    /**
     * Rechnet einen Flächenwert von einer Einheit in eine andere um.
     *
     * @param float    $value Eingangswert
     * @param AreaUnit $from  Quelleinheit
     * @param AreaUnit $to    Zieleinheit
     * @return float          Umgerechneter Wert
     */
    public static function convertArea(float $value, AreaUnit $from, AreaUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toSquareMillimeters() / $to->toSquareMillimeters();
    }

    /**
     * Rechnet einen Geschwindigkeitswert von einer Einheit in eine andere um.
     *
     * @param float     $value Eingangswert
     * @param SpeedUnit $from  Quelleinheit
     * @param SpeedUnit $to    Zieleinheit
     * @return float           Umgerechneter Wert
     */
    public static function convertSpeed(float $value, SpeedUnit $from, SpeedUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toMetersPerSecond() / $to->toMetersPerSecond();
    }

    /**
     * Formatiert einen umgerechneten Wert inkl. Einheitensymbol.
     *
     * @param float                                                                                              $value
     * @param TimeUnit|WeightUnit|VolumeUnit|LengthUnit|AreaUnit|SpeedUnit|PressureUnit|EnergyUnit|PowerUnit|AngleUnit|DataSizeUnit|ForceUnit|FrequencyUnit|AccelerationUnit|ElectricVoltageUnit|ElectricCurrentUnit  $unit
     * @param int                                                                                                                                                                                                      $precision
     * @return string
     */
    public static function format(
        float $value,
        TimeUnit|WeightUnit|VolumeUnit|LengthUnit|AreaUnit|SpeedUnit|PressureUnit|EnergyUnit|PowerUnit|AngleUnit|DataSizeUnit|ForceUnit|FrequencyUnit|AccelerationUnit|ElectricVoltageUnit|ElectricCurrentUnit|ElectricResistanceUnit|ElectricCapacitanceUnit|TorqueUnit|ViscosityUnit|IlluminanceUnit|MagneticFluxDensityUnit $unit,
        int $precision = 4
    ): string {
        return round($value, $precision) . ' ' . $unit->value;
    }

    /**
     * Rechnet einen Druckwert von einer Einheit in eine andere um.
     *
     * @param float        $value Eingangswert
     * @param PressureUnit $from  Quelleinheit
     * @param PressureUnit $to    Zieleinheit
     * @return float              Umgerechneter Wert
     */
    public static function convertPressure(float $value, PressureUnit $from, PressureUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toPascal() / $to->toPascal();
    }

    /**
     * Rechnet einen Energiewert von einer Einheit in eine andere um.
     *
     * @param float      $value Eingangswert
     * @param EnergyUnit $from  Quelleinheit
     * @param EnergyUnit $to    Zieleinheit
     * @return float            Umgerechneter Wert
     */
    public static function convertEnergy(float $value, EnergyUnit $from, EnergyUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toJoules() / $to->toJoules();
    }

    /**
     * Rechnet einen Leistungswert von einer Einheit in eine andere um.
     *
     * @param float     $value Eingangswert
     * @param PowerUnit $from  Quelleinheit
     * @param PowerUnit $to    Zieleinheit
     * @return float           Umgerechneter Wert
     */
    public static function convertPower(float $value, PowerUnit $from, PowerUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toWatts() / $to->toWatts();
    }

    /**
     * Rechnet einen Winkelwert von einer Einheit in eine andere um.
     *
     * @param float     $value Eingangswert
     * @param AngleUnit $from  Quelleinheit
     * @param AngleUnit $to    Zieleinheit
     * @return float           Umgerechneter Wert
     */
    public static function convertAngle(float $value, AngleUnit $from, AngleUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toDegrees() / $to->toDegrees();
    }

    /**
     * Rechnet eine Datenmenge von einer Einheit in eine andere um.
     *
     * @param float        $value Eingangswert
     * @param DataSizeUnit $from  Quelleinheit
     * @param DataSizeUnit $to    Zieleinheit
     * @return float              Umgerechneter Wert
     */
    public static function convertDataSize(float $value, DataSizeUnit $from, DataSizeUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toBits() / $to->toBits();
    }

    /**
     * Rechnet einen Kraftwert von einer Einheit in eine andere um.
     *
     * @param float     $value Eingangswert
     * @param ForceUnit $from  Quelleinheit
     * @param ForceUnit $to    Zieleinheit
     * @return float           Umgerechneter Wert
     */
    public static function convertForce(float $value, ForceUnit $from, ForceUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toNewtons() / $to->toNewtons();
    }

    /**
     * Rechnet einen Frequenzwert von einer Einheit in eine andere um.
     *
     * @param float         $value Eingangswert
     * @param FrequencyUnit $from  Quelleinheit
     * @param FrequencyUnit $to    Zieleinheit
     * @return float               Umgerechneter Wert
     */
    public static function convertFrequency(float $value, FrequencyUnit $from, FrequencyUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toHertz() / $to->toHertz();
    }

    /**
     * Rechnet einen Beschleunigungswert von einer Einheit in eine andere um.
     *
     * @param float            $value Eingangswert
     * @param AccelerationUnit $from  Quelleinheit
     * @param AccelerationUnit $to    Zieleinheit
     * @return float                  Umgerechneter Wert
     */
    public static function convertAcceleration(float $value, AccelerationUnit $from, AccelerationUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toMetersPerSecondSquared() / $to->toMetersPerSecondSquared();
    }

    /**
     * Rechnet eine elektrische Spannung von einer Einheit in eine andere um.
     *
     * @param float               $value Eingangswert
     * @param ElectricVoltageUnit $from  Quelleinheit
     * @param ElectricVoltageUnit $to    Zieleinheit
     * @return float                     Umgerechneter Wert
     */
    public static function convertElectricVoltage(float $value, ElectricVoltageUnit $from, ElectricVoltageUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toVolts() / $to->toVolts();
    }

    /**
     * Rechnet einen elektrischen Strom von einer Einheit in eine andere um.
     *
     * @param float               $value Eingangswert
     * @param ElectricCurrentUnit $from  Quelleinheit
     * @param ElectricCurrentUnit $to    Zieleinheit
     * @return float                     Umgerechneter Wert
     */
    public static function convertElectricCurrent(float $value, ElectricCurrentUnit $from, ElectricCurrentUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toAmperes() / $to->toAmperes();
    }

    /**
     * Rechnet einen elektrischen Widerstand von einer Einheit in eine andere um.
     *
     * @param float                  $value Eingangswert
     * @param ElectricResistanceUnit $from  Quelleinheit
     * @param ElectricResistanceUnit $to    Zieleinheit
     * @return float                        Umgerechneter Wert
     */
    public static function convertElectricResistance(float $value, ElectricResistanceUnit $from, ElectricResistanceUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toOhms() / $to->toOhms();
    }

    /**
     * Rechnet eine elektrische Kapazität von einer Einheit in eine andere um.
     *
     * @param float                   $value Eingangswert
     * @param ElectricCapacitanceUnit $from  Quelleinheit
     * @param ElectricCapacitanceUnit $to    Zieleinheit
     * @return float                         Umgerechneter Wert
     */
    public static function convertElectricCapacitance(float $value, ElectricCapacitanceUnit $from, ElectricCapacitanceUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toFarads() / $to->toFarads();
    }

    /**
     * Rechnet ein Drehmoment von einer Einheit in eine andere um.
     *
     * @param float       $value Eingangswert
     * @param TorqueUnit  $from  Quelleinheit
     * @param TorqueUnit  $to    Zieleinheit
     * @return float             Umgerechneter Wert
     */
    public static function convertTorque(float $value, TorqueUnit $from, TorqueUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toNewtonMeters() / $to->toNewtonMeters();
    }

    /**
     * Rechnet eine dynamische Viskosität von einer Einheit in eine andere um.
     *
     * @param float         $value Eingangswert
     * @param ViscosityUnit $from  Quelleinheit
     * @param ViscosityUnit $to    Zieleinheit
     * @return float               Umgerechneter Wert
     */
    public static function convertViscosity(float $value, ViscosityUnit $from, ViscosityUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toPascalSeconds() / $to->toPascalSeconds();
    }

    /**
     * Rechnet eine Beleuchtungsstärke von einer Einheit in eine andere um.
     *
     * @param float           $value Eingangswert
     * @param IlluminanceUnit $from  Quelleinheit
     * @param IlluminanceUnit $to    Zieleinheit
     * @return float                 Umgerechneter Wert
     */
    public static function convertIlluminance(float $value, IlluminanceUnit $from, IlluminanceUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toLux() / $to->toLux();
    }

    /**
     * Rechnet eine magnetische Flussdichte von einer Einheit in eine andere um.
     *
     * @param float                   $value Eingangswert
     * @param MagneticFluxDensityUnit $from  Quelleinheit
     * @param MagneticFluxDensityUnit $to    Zieleinheit
     * @return float                         Umgerechneter Wert
     */
    public static function convertMagneticFluxDensity(float $value, MagneticFluxDensityUnit $from, MagneticFluxDensityUnit $to): float {
        if ($from === $to) {
            return $value;
        }
        return $value * $from->toTesla() / $to->toTesla();
    }
}
