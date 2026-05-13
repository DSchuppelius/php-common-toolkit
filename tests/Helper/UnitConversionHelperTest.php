<?php
/*
 * Created on   : Tue May 13 2025
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : UnitConversionHelperTest.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

use CommonToolkit\Enums\{AccelerationUnit, AngleUnit, AreaUnit, DataSizeUnit, ElectricCapacitanceUnit, ElectricCurrentUnit, ElectricResistanceUnit, ElectricVoltageUnit, EnergyUnit, ForceUnit, FrequencyUnit, IlluminanceUnit, LengthUnit, MagneticFluxDensityUnit, PowerUnit, PressureUnit, SpeedUnit, TimeUnit, TorqueUnit, ViscosityUnit, VolumeUnit, WeightUnit};
use CommonToolkit\Helper\Data\UnitConversionHelper;
use PHPUnit\Framework\TestCase;

final class UnitConversionHelperTest extends TestCase {

    // -------------------------------------------------------------------------
    // Zeit
    // -------------------------------------------------------------------------

    public function testConvertTimeMinutesToSeconds(): void {
        $this->assertEqualsWithDelta(120.0, UnitConversionHelper::convertTime(2, TimeUnit::MINUTE, TimeUnit::SECOND), 1e-9);
    }

    public function testConvertTimeHoursToMinutes(): void {
        $this->assertEqualsWithDelta(90.0, UnitConversionHelper::convertTime(1.5, TimeUnit::HOUR, TimeUnit::MINUTE), 1e-9);
    }

    public function testConvertTimeDaysToHours(): void {
        $this->assertEqualsWithDelta(48.0, UnitConversionHelper::convertTime(2, TimeUnit::DAY, TimeUnit::HOUR), 1e-9);
    }

    public function testConvertTimeWeekToDays(): void {
        $this->assertEqualsWithDelta(7.0, UnitConversionHelper::convertTime(1, TimeUnit::WEEK, TimeUnit::DAY), 1e-9);
    }

    public function testConvertTimeSameUnit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertTime(5, TimeUnit::HOUR, TimeUnit::HOUR), 1e-9);
    }

    public function testConvertTimeSecondsToMilliseconds(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertTime(1, TimeUnit::SECOND, TimeUnit::MILLISECOND), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Gewicht
    // -------------------------------------------------------------------------

    public function testConvertWeightKgToGram(): void {
        $this->assertEqualsWithDelta(2500.0, UnitConversionHelper::convertWeight(2.5, WeightUnit::KILOGRAM, WeightUnit::GRAM), 1e-6);
    }

    public function testConvertWeightGramToMilligram(): void {
        $this->assertEqualsWithDelta(500.0, UnitConversionHelper::convertWeight(0.5, WeightUnit::GRAM, WeightUnit::MILLIGRAM), 1e-6);
    }

    public function testConvertWeightPoundToKg(): void {
        $this->assertEqualsWithDelta(0.45359237, UnitConversionHelper::convertWeight(1, WeightUnit::POUND, WeightUnit::KILOGRAM), 1e-6);
    }

    public function testConvertWeightTonToKg(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertWeight(1, WeightUnit::METRIC_TON, WeightUnit::KILOGRAM), 1e-6);
    }

    public function testConvertWeightSameUnit(): void {
        $this->assertEqualsWithDelta(42.0, UnitConversionHelper::convertWeight(42, WeightUnit::GRAM, WeightUnit::GRAM), 1e-6);
    }

    // -------------------------------------------------------------------------
    // Volumen
    // -------------------------------------------------------------------------

    public function testConvertVolumeLiterToMilliliter(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertVolume(1, VolumeUnit::LITER, VolumeUnit::MILLILITER), 1e-6);
    }

    public function testConvertVolumeMilliliterToCentiliter(): void {
        $this->assertEqualsWithDelta(25.0, UnitConversionHelper::convertVolume(250, VolumeUnit::MILLILITER, VolumeUnit::CENTILITER), 1e-6);
    }

    public function testConvertVolumeGallonToLiter(): void {
        $this->assertEqualsWithDelta(3.785411784, UnitConversionHelper::convertVolume(1, VolumeUnit::US_GALLON, VolumeUnit::LITER), 1e-6);
    }

    public function testConvertVolumeImpGallonToLiter(): void {
        $this->assertEqualsWithDelta(4.54609, UnitConversionHelper::convertVolume(1, VolumeUnit::IMP_GALLON, VolumeUnit::LITER), 1e-4);
    }

    public function testConvertVolumeSameUnit(): void {
        $this->assertEqualsWithDelta(3.0, UnitConversionHelper::convertVolume(3, VolumeUnit::LITER, VolumeUnit::LITER), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Länge
    // -------------------------------------------------------------------------

    public function testConvertLengthMeterToCentimeter(): void {
        $this->assertEqualsWithDelta(150.0, UnitConversionHelper::convertLength(1.5, LengthUnit::METER, LengthUnit::CENTIMETER), 1e-9);
    }

    public function testConvertLengthKilometerToMeter(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertLength(1, LengthUnit::KILOMETER, LengthUnit::METER), 1e-9);
    }

    public function testConvertLengthMileToKilometer(): void {
        $this->assertEqualsWithDelta(1.609344, UnitConversionHelper::convertLength(1, LengthUnit::MILE, LengthUnit::KILOMETER), 1e-6);
    }

    public function testConvertLengthInchToCentimeter(): void {
        $this->assertEqualsWithDelta(2.54, UnitConversionHelper::convertLength(1, LengthUnit::INCH, LengthUnit::CENTIMETER), 1e-9);
    }

    public function testConvertLengthFootToMeter(): void {
        $this->assertEqualsWithDelta(0.3048, UnitConversionHelper::convertLength(1, LengthUnit::FOOT, LengthUnit::METER), 1e-9);
    }

    public function testConvertLengthSameUnit(): void {
        $this->assertEqualsWithDelta(7.0, UnitConversionHelper::convertLength(7, LengthUnit::METER, LengthUnit::METER), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Fläche
    // -------------------------------------------------------------------------

    public function testConvertAreaSquareMeterToSquareCentimeter(): void {
        $this->assertEqualsWithDelta(10000.0, UnitConversionHelper::convertArea(1, AreaUnit::SQUARE_METER, AreaUnit::SQUARE_CENTIMETER), 1e-6);
    }

    public function testConvertAreaHectareToSquareMeter(): void {
        $this->assertEqualsWithDelta(10000.0, UnitConversionHelper::convertArea(1, AreaUnit::HECTARE, AreaUnit::SQUARE_METER), 1e-4);
    }

    public function testConvertAreaAcreToHectare(): void {
        $this->assertEqualsWithDelta(0.404685642, UnitConversionHelper::convertArea(1, AreaUnit::ACRE, AreaUnit::HECTARE), 1e-6);
    }

    public function testConvertAreaSquareKilometerToHectare(): void {
        $this->assertEqualsWithDelta(100.0, UnitConversionHelper::convertArea(1, AreaUnit::SQUARE_KILOMETER, AreaUnit::HECTARE), 1e-6);
    }

    public function testConvertAreaSameUnit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertArea(5, AreaUnit::HECTARE, AreaUnit::HECTARE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Geschwindigkeit
    // -------------------------------------------------------------------------

    public function testConvertSpeedKmhToMs(): void {
        $this->assertEqualsWithDelta(100.0 / 3.6, UnitConversionHelper::convertSpeed(100, SpeedUnit::KILOMETER_PER_HOUR, SpeedUnit::METER_PER_SECOND), 1e-6);
    }

    public function testConvertSpeedMsToKmh(): void {
        $this->assertEqualsWithDelta(36.0, UnitConversionHelper::convertSpeed(10, SpeedUnit::METER_PER_SECOND, SpeedUnit::KILOMETER_PER_HOUR), 1e-6);
    }

    public function testConvertSpeedMphToKmh(): void {
        $this->assertEqualsWithDelta(1.60934, UnitConversionHelper::convertSpeed(1, SpeedUnit::MILE_PER_HOUR, SpeedUnit::KILOMETER_PER_HOUR), 1e-4);
    }

    public function testConvertSpeedKnotToKmh(): void {
        $this->assertEqualsWithDelta(1.852, UnitConversionHelper::convertSpeed(1, SpeedUnit::KNOT, SpeedUnit::KILOMETER_PER_HOUR), 1e-4);
    }

    public function testConvertSpeedSameUnit(): void {
        $this->assertEqualsWithDelta(120.0, UnitConversionHelper::convertSpeed(120, SpeedUnit::KILOMETER_PER_HOUR, SpeedUnit::KILOMETER_PER_HOUR), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Format
    // -------------------------------------------------------------------------

    public function testFormat(): void {
        $result = UnitConversionHelper::format(3.1415926, LengthUnit::METER, 2);
        $this->assertEquals('3.14 m', $result);
    }

    public function testFormatTimeUnit(): void {
        $result = UnitConversionHelper::format(60.0, TimeUnit::SECOND, 0);
        $this->assertEquals('60 s', $result);
    }

    // -------------------------------------------------------------------------
    // Druck
    // -------------------------------------------------------------------------

    public function testConvertPressureBarToPascal(): void {
        $this->assertEqualsWithDelta(100_000.0, UnitConversionHelper::convertPressure(1, PressureUnit::BAR, PressureUnit::PASCAL), 1e-6);
    }

    public function testConvertPressureAtmToBar(): void {
        $this->assertEqualsWithDelta(1.01325, UnitConversionHelper::convertPressure(1, PressureUnit::ATMOSPHERE, PressureUnit::BAR), 1e-6);
    }

    public function testConvertPressurePsiToBar(): void {
        $this->assertEqualsWithDelta(0.0689476, UnitConversionHelper::convertPressure(1, PressureUnit::PSI, PressureUnit::BAR), 1e-5);
    }

    public function testConvertPressureHPaToMbar(): void {
        $this->assertEqualsWithDelta(1013.25, UnitConversionHelper::convertPressure(1013.25, PressureUnit::HECTOPASCAL, PressureUnit::MILLIBAR), 1e-4);
    }

    public function testConvertPressureSameUnit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertPressure(5, PressureUnit::BAR, PressureUnit::BAR), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Energie
    // -------------------------------------------------------------------------

    public function testConvertEnergyKilojouleToJoule(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertEnergy(1, EnergyUnit::KILOJOULE, EnergyUnit::JOULE), 1e-6);
    }

    public function testConvertEnergyKwhToMj(): void {
        $this->assertEqualsWithDelta(3.6, UnitConversionHelper::convertEnergy(1, EnergyUnit::KILOWATT_HOUR, EnergyUnit::MEGAJOULE), 1e-6);
    }

    public function testConvertEnergyKcalToKj(): void {
        $this->assertEqualsWithDelta(4.184, UnitConversionHelper::convertEnergy(1, EnergyUnit::KILOCALORIE, EnergyUnit::KILOJOULE), 1e-4);
    }

    public function testConvertEnergyBtuToJoule(): void {
        $this->assertEqualsWithDelta(1055.055, UnitConversionHelper::convertEnergy(1, EnergyUnit::BTU, EnergyUnit::JOULE), 1e-2);
    }

    public function testConvertEnergySameUnit(): void {
        $this->assertEqualsWithDelta(2.5, UnitConversionHelper::convertEnergy(2.5, EnergyUnit::KILOWATT_HOUR, EnergyUnit::KILOWATT_HOUR), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Leistung
    // -------------------------------------------------------------------------

    public function testConvertPowerKwToWatt(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertPower(1, PowerUnit::KILOWATT, PowerUnit::WATT), 1e-6);
    }

    public function testConvertPowerPsToKw(): void {
        $this->assertEqualsWithDelta(0.73549875, UnitConversionHelper::convertPower(1, PowerUnit::METRIC_HP, PowerUnit::KILOWATT), 1e-6);
    }

    public function testConvertPowerHpToKw(): void {
        $this->assertEqualsWithDelta(0.74570, UnitConversionHelper::convertPower(1, PowerUnit::MECHANICAL_HP, PowerUnit::KILOWATT), 1e-4);
    }

    public function testConvertPowerSameUnit(): void {
        $this->assertEqualsWithDelta(150.0, UnitConversionHelper::convertPower(150, PowerUnit::METRIC_HP, PowerUnit::METRIC_HP), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Winkel
    // -------------------------------------------------------------------------

    public function testConvertAngleDegreeToRadian(): void {
        $this->assertEqualsWithDelta(M_PI, UnitConversionHelper::convertAngle(180, AngleUnit::DEGREE, AngleUnit::RADIAN), 1e-9);
    }

    public function testConvertAngleRadianToDegree(): void {
        $this->assertEqualsWithDelta(180.0, UnitConversionHelper::convertAngle(M_PI, AngleUnit::RADIAN, AngleUnit::DEGREE), 1e-9);
    }

    public function testConvertAngleDegreeToGradian(): void {
        $this->assertEqualsWithDelta(400.0, UnitConversionHelper::convertAngle(360, AngleUnit::DEGREE, AngleUnit::GRADIAN), 1e-9);
    }

    public function testConvertAngleDegreeToArcminute(): void {
        $this->assertEqualsWithDelta(60.0, UnitConversionHelper::convertAngle(1, AngleUnit::DEGREE, AngleUnit::ARCMINUTE), 1e-9);
    }

    public function testConvertAngleSameUnit(): void {
        $this->assertEqualsWithDelta(90.0, UnitConversionHelper::convertAngle(90, AngleUnit::DEGREE, AngleUnit::DEGREE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Datenmenge
    // -------------------------------------------------------------------------

    public function testConvertDataSizeByteToKilobyte(): void {
        $this->assertEqualsWithDelta(1.0, UnitConversionHelper::convertDataSize(1000, DataSizeUnit::BYTE, DataSizeUnit::KILOBYTE), 1e-9);
    }

    public function testConvertDataSizeMegabyteToKibibyte(): void {
        // 1 MB (SI) = 1.000.000 B = 1000000 / 1024 KiB ≈ 976.5625 KiB
        $this->assertEqualsWithDelta(976.5625, UnitConversionHelper::convertDataSize(1, DataSizeUnit::MEGABYTE, DataSizeUnit::KIBIBYTE), 1e-6);
    }

    public function testConvertDataSizeGibibyteToGigabyte(): void {
        // 1 GiB = 1024³ B = 1.073.741.824 B → in GB (SI): 1.073741824
        $this->assertEqualsWithDelta(1.073741824, UnitConversionHelper::convertDataSize(1, DataSizeUnit::GIBIBYTE, DataSizeUnit::GIGABYTE), 1e-9);
    }

    public function testConvertDataSizeBitToByte(): void {
        $this->assertEqualsWithDelta(1.0, UnitConversionHelper::convertDataSize(8, DataSizeUnit::BIT, DataSizeUnit::BYTE), 1e-9);
    }

    public function testConvertDataSizeSameUnit(): void {
        $this->assertEqualsWithDelta(4.0, UnitConversionHelper::convertDataSize(4, DataSizeUnit::GIGABYTE, DataSizeUnit::GIGABYTE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Kraft
    // -------------------------------------------------------------------------

    public function testConvertForceKilonewtonToNewton(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertForce(1, ForceUnit::KILONEWTON, ForceUnit::NEWTON), 1e-6);
    }

    public function testConvertForceKgfToNewton(): void {
        $this->assertEqualsWithDelta(9.80665, UnitConversionHelper::convertForce(1, ForceUnit::KILOGRAM_FORCE, ForceUnit::NEWTON), 1e-6);
    }

    public function testConvertForcePoundForceToNewton(): void {
        $this->assertEqualsWithDelta(4.448221, UnitConversionHelper::convertForce(1, ForceUnit::POUND_FORCE, ForceUnit::NEWTON), 1e-4);
    }

    public function testConvertForceDynToNewton(): void {
        $this->assertEqualsWithDelta(0.00001, UnitConversionHelper::convertForce(1, ForceUnit::DYN, ForceUnit::NEWTON), 1e-9);
    }

    public function testConvertForceSameUnit(): void {
        $this->assertEqualsWithDelta(9.81, UnitConversionHelper::convertForce(9.81, ForceUnit::NEWTON, ForceUnit::NEWTON), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Frequenz
    // -------------------------------------------------------------------------

    public function testConvertFrequencyKilohertzToHertz(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::KILOHERTZ, FrequencyUnit::HERTZ), 1e-9);
    }

    public function testConvertFrequencyMegahertzToKilohertz(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::MEGAHERTZ, FrequencyUnit::KILOHERTZ), 1e-9);
    }

    public function testConvertFrequencyRpmToHertz(): void {
        $this->assertEqualsWithDelta(1.0 / 60.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::RPM, FrequencyUnit::HERTZ), 1e-9);
    }

    public function testConvertFrequencyHertzToRpm(): void {
        $this->assertEqualsWithDelta(60.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::HERTZ, FrequencyUnit::RPM), 1e-9);
    }

    public function testConvertFrequencySameUnit(): void {
        $this->assertEqualsWithDelta(2400.0, UnitConversionHelper::convertFrequency(2400, FrequencyUnit::MEGAHERTZ, FrequencyUnit::MEGAHERTZ), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Beschleunigung
    // -------------------------------------------------------------------------

    public function testConvertAccelerationGravityToMs2(): void {
        $this->assertEqualsWithDelta(9.80665, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::STANDARD_GRAVITY, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-6);
    }

    public function testConvertAccelerationMs2ToGravity(): void {
        $this->assertEqualsWithDelta(1.0 / 9.80665, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::METER_PER_SECOND_SQUARED, AccelerationUnit::STANDARD_GRAVITY), 1e-9);
    }

    public function testConvertAccelerationGalToMs2(): void {
        $this->assertEqualsWithDelta(0.01, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::GAL, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-9);
    }

    public function testConvertAccelerationFootPerSecondSquaredToMs2(): void {
        $this->assertEqualsWithDelta(0.3048, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::FOOT_PER_SECOND_SQUARED, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-9);
    }

    public function testConvertAccelerationSameUnit(): void {
        $this->assertEqualsWithDelta(9.81, UnitConversionHelper::convertAcceleration(9.81, AccelerationUnit::METER_PER_SECOND_SQUARED, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrische Spannung
    // -------------------------------------------------------------------------

    public function testConvertVoltageKilovoltToVolt(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricVoltage(1, ElectricVoltageUnit::KILOVOLT, ElectricVoltageUnit::VOLT), 1e-9);
    }

    public function testConvertVoltageMillivoltToVolt(): void {
        $this->assertEqualsWithDelta(0.012, UnitConversionHelper::convertElectricVoltage(12, ElectricVoltageUnit::MILLIVOLT, ElectricVoltageUnit::VOLT), 1e-9);
    }

    public function testConvertVoltageSameUnit(): void {
        $this->assertEqualsWithDelta(230.0, UnitConversionHelper::convertElectricVoltage(230, ElectricVoltageUnit::VOLT, ElectricVoltageUnit::VOLT), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrischer Strom
    // -------------------------------------------------------------------------

    public function testConvertCurrentAmpereToMilliampere(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricCurrent(1, ElectricCurrentUnit::AMPERE, ElectricCurrentUnit::MILLIAMPERE), 1e-9);
    }

    public function testConvertCurrentMilliampereToMicroampere(): void {
        $this->assertEqualsWithDelta(500.0, UnitConversionHelper::convertElectricCurrent(0.5, ElectricCurrentUnit::MILLIAMPERE, ElectricCurrentUnit::MICROAMPERE), 1e-9);
    }

    public function testConvertCurrentKiloampereToAmpere(): void {
        $this->assertEqualsWithDelta(3000.0, UnitConversionHelper::convertElectricCurrent(3, ElectricCurrentUnit::KILOAMPERE, ElectricCurrentUnit::AMPERE), 1e-9);
    }

    public function testConvertCurrentSameUnit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertElectricCurrent(5, ElectricCurrentUnit::AMPERE, ElectricCurrentUnit::AMPERE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrischer Widerstand
    // -------------------------------------------------------------------------

    public function testConvertResistanceKilohmToOhm(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricResistance(1, ElectricResistanceUnit::KILOOHM, ElectricResistanceUnit::OHM), 1e-9);
    }

    public function testConvertResistanceMegahmToKilohm(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricResistance(1, ElectricResistanceUnit::MEGAOHM, ElectricResistanceUnit::KILOOHM), 1e-9);
    }

    public function testConvertResistanceMilliohmToOhm(): void {
        $this->assertEqualsWithDelta(0.047, UnitConversionHelper::convertElectricResistance(47, ElectricResistanceUnit::MILLIOHM, ElectricResistanceUnit::OHM), 1e-9);
    }

    public function testConvertResistanceSameUnit(): void {
        $this->assertEqualsWithDelta(470.0, UnitConversionHelper::convertElectricResistance(470, ElectricResistanceUnit::OHM, ElectricResistanceUnit::OHM), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrische Kapazität
    // -------------------------------------------------------------------------

    public function testConvertCapacitanceMicrofaradToNanofarad(): void {
        $this->assertEqualsWithDelta(100.0, UnitConversionHelper::convertElectricCapacitance(0.1, ElectricCapacitanceUnit::MICROFARAD, ElectricCapacitanceUnit::NANOFARAD), 1e-6);
    }

    public function testConvertCapacitancePicofaradToMicrofarad(): void {
        $this->assertEqualsWithDelta(0.001, UnitConversionHelper::convertElectricCapacitance(1000, ElectricCapacitanceUnit::PICOFARAD, ElectricCapacitanceUnit::MICROFARAD), 1e-9);
    }

    public function testConvertCapacitanceSameUnit(): void {
        $this->assertEqualsWithDelta(10.0, UnitConversionHelper::convertElectricCapacitance(10, ElectricCapacitanceUnit::NANOFARAD, ElectricCapacitanceUnit::NANOFARAD), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Drehmoment
    // -------------------------------------------------------------------------

    public function testConvertTorqueNewtonMeterToPoundForceFoot(): void {
        $this->assertEqualsWithDelta(0.7375621, UnitConversionHelper::convertTorque(1, TorqueUnit::NEWTON_METER, TorqueUnit::POUND_FORCE_FOOT), 1e-5);
    }

    public function testConvertTorqueKilogramForceMeterToNewtonMeter(): void {
        $this->assertEqualsWithDelta(9.80665, UnitConversionHelper::convertTorque(1, TorqueUnit::KILOGRAM_FORCE_METER, TorqueUnit::NEWTON_METER), 1e-6);
    }

    public function testConvertTorqueKilonewtonMeterToNewtonMeter(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertTorque(1, TorqueUnit::KILONEWTON_METER, TorqueUnit::NEWTON_METER), 1e-9);
    }

    public function testConvertTorqueSameUnit(): void {
        $this->assertEqualsWithDelta(350.0, UnitConversionHelper::convertTorque(350, TorqueUnit::NEWTON_METER, TorqueUnit::NEWTON_METER), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Dynamische Viskosität
    // -------------------------------------------------------------------------

    public function testConvertViscosityCentipoiseToPascalSecond(): void {
        $this->assertEqualsWithDelta(0.001, UnitConversionHelper::convertViscosity(1, ViscosityUnit::CENTIPOISE, ViscosityUnit::PASCAL_SECOND), 1e-9);
    }

    public function testConvertViscosityPoiseToPascalSecond(): void {
        $this->assertEqualsWithDelta(0.1, UnitConversionHelper::convertViscosity(1, ViscosityUnit::POISE, ViscosityUnit::PASCAL_SECOND), 1e-9);
    }

    public function testConvertViscositySameUnit(): void {
        $this->assertEqualsWithDelta(1.0, UnitConversionHelper::convertViscosity(1, ViscosityUnit::PASCAL_SECOND, ViscosityUnit::PASCAL_SECOND), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Beleuchtungsstärke
    // -------------------------------------------------------------------------

    public function testConvertIlluminanceFootCandleToLux(): void {
        $this->assertEqualsWithDelta(10.7639, UnitConversionHelper::convertIlluminance(1, IlluminanceUnit::FOOT_CANDLE, IlluminanceUnit::LUX), 1e-3);
    }

    public function testConvertIlluminancePhotToLux(): void {
        $this->assertEqualsWithDelta(10_000.0, UnitConversionHelper::convertIlluminance(1, IlluminanceUnit::PHOT, IlluminanceUnit::LUX), 1e-6);
    }

    public function testConvertIlluminanceKiloluxToLux(): void {
        $this->assertEqualsWithDelta(100_000.0, UnitConversionHelper::convertIlluminance(100, IlluminanceUnit::KILOLUX, IlluminanceUnit::LUX), 1e-6);
    }

    public function testConvertIlluminanceSameUnit(): void {
        $this->assertEqualsWithDelta(500.0, UnitConversionHelper::convertIlluminance(500, IlluminanceUnit::LUX, IlluminanceUnit::LUX), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Magnetische Flussdichte
    // -------------------------------------------------------------------------

    public function testConvertMagneticFluxGaussToTesla(): void {
        $this->assertEqualsWithDelta(0.0001, UnitConversionHelper::convertMagneticFluxDensity(1, MagneticFluxDensityUnit::GAUSS, MagneticFluxDensityUnit::TESLA), 1e-9);
    }

    public function testConvertMagneticFluxTeslaToMillitesla(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertMagneticFluxDensity(1, MagneticFluxDensityUnit::TESLA, MagneticFluxDensityUnit::MILLITESLA), 1e-9);
    }

    public function testConvertMagneticFluxMicroteslaToNanotesla(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertMagneticFluxDensity(1, MagneticFluxDensityUnit::MICROTESLA, MagneticFluxDensityUnit::NANOTESLA), 1e-6);
    }

    public function testConvertMagneticFluxSameUnit(): void {
        $this->assertEqualsWithDelta(50.0, UnitConversionHelper::convertMagneticFluxDensity(50, MagneticFluxDensityUnit::MICROTESLA, MagneticFluxDensityUnit::MICROTESLA), 1e-9);
    }
}
