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

    public function test_convert_time_minutes_to_seconds(): void {
        $this->assertEqualsWithDelta(120.0, UnitConversionHelper::convertTime(2, TimeUnit::MINUTE, TimeUnit::SECOND), 1e-9);
    }

    public function test_convert_time_hours_to_minutes(): void {
        $this->assertEqualsWithDelta(90.0, UnitConversionHelper::convertTime(1.5, TimeUnit::HOUR, TimeUnit::MINUTE), 1e-9);
    }

    public function test_convert_time_days_to_hours(): void {
        $this->assertEqualsWithDelta(48.0, UnitConversionHelper::convertTime(2, TimeUnit::DAY, TimeUnit::HOUR), 1e-9);
    }

    public function test_convert_time_week_to_days(): void {
        $this->assertEqualsWithDelta(7.0, UnitConversionHelper::convertTime(1, TimeUnit::WEEK, TimeUnit::DAY), 1e-9);
    }

    public function test_convert_time_same_unit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertTime(5, TimeUnit::HOUR, TimeUnit::HOUR), 1e-9);
    }

    public function test_convert_time_seconds_to_milliseconds(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertTime(1, TimeUnit::SECOND, TimeUnit::MILLISECOND), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Gewicht
    // -------------------------------------------------------------------------

    public function test_convert_weight_kg_to_gram(): void {
        $this->assertEqualsWithDelta(2500.0, UnitConversionHelper::convertWeight(2.5, WeightUnit::KILOGRAM, WeightUnit::GRAM), 1e-6);
    }

    public function test_convert_weight_gram_to_milligram(): void {
        $this->assertEqualsWithDelta(500.0, UnitConversionHelper::convertWeight(0.5, WeightUnit::GRAM, WeightUnit::MILLIGRAM), 1e-6);
    }

    public function test_convert_weight_pound_to_kg(): void {
        $this->assertEqualsWithDelta(0.45359237, UnitConversionHelper::convertWeight(1, WeightUnit::POUND, WeightUnit::KILOGRAM), 1e-6);
    }

    public function test_convert_weight_ton_to_kg(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertWeight(1, WeightUnit::METRIC_TON, WeightUnit::KILOGRAM), 1e-6);
    }

    public function test_convert_weight_same_unit(): void {
        $this->assertEqualsWithDelta(42.0, UnitConversionHelper::convertWeight(42, WeightUnit::GRAM, WeightUnit::GRAM), 1e-6);
    }

    // -------------------------------------------------------------------------
    // Volumen
    // -------------------------------------------------------------------------

    public function test_convert_volume_liter_to_milliliter(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertVolume(1, VolumeUnit::LITER, VolumeUnit::MILLILITER), 1e-6);
    }

    public function test_convert_volume_milliliter_to_centiliter(): void {
        $this->assertEqualsWithDelta(25.0, UnitConversionHelper::convertVolume(250, VolumeUnit::MILLILITER, VolumeUnit::CENTILITER), 1e-6);
    }

    public function test_convert_volume_gallon_to_liter(): void {
        $this->assertEqualsWithDelta(3.785411784, UnitConversionHelper::convertVolume(1, VolumeUnit::US_GALLON, VolumeUnit::LITER), 1e-6);
    }

    public function test_convert_volume_imp_gallon_to_liter(): void {
        $this->assertEqualsWithDelta(4.54609, UnitConversionHelper::convertVolume(1, VolumeUnit::IMP_GALLON, VolumeUnit::LITER), 1e-4);
    }

    public function test_convert_volume_same_unit(): void {
        $this->assertEqualsWithDelta(3.0, UnitConversionHelper::convertVolume(3, VolumeUnit::LITER, VolumeUnit::LITER), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Länge
    // -------------------------------------------------------------------------

    public function test_convert_length_meter_to_centimeter(): void {
        $this->assertEqualsWithDelta(150.0, UnitConversionHelper::convertLength(1.5, LengthUnit::METER, LengthUnit::CENTIMETER), 1e-9);
    }

    public function test_convert_length_kilometer_to_meter(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertLength(1, LengthUnit::KILOMETER, LengthUnit::METER), 1e-9);
    }

    public function test_convert_length_mile_to_kilometer(): void {
        $this->assertEqualsWithDelta(1.609344, UnitConversionHelper::convertLength(1, LengthUnit::MILE, LengthUnit::KILOMETER), 1e-6);
    }

    public function test_convert_length_inch_to_centimeter(): void {
        $this->assertEqualsWithDelta(2.54, UnitConversionHelper::convertLength(1, LengthUnit::INCH, LengthUnit::CENTIMETER), 1e-9);
    }

    public function test_convert_length_foot_to_meter(): void {
        $this->assertEqualsWithDelta(0.3048, UnitConversionHelper::convertLength(1, LengthUnit::FOOT, LengthUnit::METER), 1e-9);
    }

    public function test_convert_length_same_unit(): void {
        $this->assertEqualsWithDelta(7.0, UnitConversionHelper::convertLength(7, LengthUnit::METER, LengthUnit::METER), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Fläche
    // -------------------------------------------------------------------------

    public function test_convert_area_square_meter_to_square_centimeter(): void {
        $this->assertEqualsWithDelta(10000.0, UnitConversionHelper::convertArea(1, AreaUnit::SQUARE_METER, AreaUnit::SQUARE_CENTIMETER), 1e-6);
    }

    public function test_convert_area_hectare_to_square_meter(): void {
        $this->assertEqualsWithDelta(10000.0, UnitConversionHelper::convertArea(1, AreaUnit::HECTARE, AreaUnit::SQUARE_METER), 1e-4);
    }

    public function test_convert_area_acre_to_hectare(): void {
        $this->assertEqualsWithDelta(0.404685642, UnitConversionHelper::convertArea(1, AreaUnit::ACRE, AreaUnit::HECTARE), 1e-6);
    }

    public function test_convert_area_square_kilometer_to_hectare(): void {
        $this->assertEqualsWithDelta(100.0, UnitConversionHelper::convertArea(1, AreaUnit::SQUARE_KILOMETER, AreaUnit::HECTARE), 1e-6);
    }

    public function test_convert_area_same_unit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertArea(5, AreaUnit::HECTARE, AreaUnit::HECTARE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Geschwindigkeit
    // -------------------------------------------------------------------------

    public function test_convert_speed_kmh_to_ms(): void {
        $this->assertEqualsWithDelta(100.0 / 3.6, UnitConversionHelper::convertSpeed(100, SpeedUnit::KILOMETER_PER_HOUR, SpeedUnit::METER_PER_SECOND), 1e-6);
    }

    public function test_convert_speed_ms_to_kmh(): void {
        $this->assertEqualsWithDelta(36.0, UnitConversionHelper::convertSpeed(10, SpeedUnit::METER_PER_SECOND, SpeedUnit::KILOMETER_PER_HOUR), 1e-6);
    }

    public function test_convert_speed_mph_to_kmh(): void {
        $this->assertEqualsWithDelta(1.60934, UnitConversionHelper::convertSpeed(1, SpeedUnit::MILE_PER_HOUR, SpeedUnit::KILOMETER_PER_HOUR), 1e-4);
    }

    public function test_convert_speed_knot_to_kmh(): void {
        $this->assertEqualsWithDelta(1.852, UnitConversionHelper::convertSpeed(1, SpeedUnit::KNOT, SpeedUnit::KILOMETER_PER_HOUR), 1e-4);
    }

    public function test_convert_speed_same_unit(): void {
        $this->assertEqualsWithDelta(120.0, UnitConversionHelper::convertSpeed(120, SpeedUnit::KILOMETER_PER_HOUR, SpeedUnit::KILOMETER_PER_HOUR), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Format
    // -------------------------------------------------------------------------

    public function test_format(): void {
        $result = UnitConversionHelper::format(3.1415926, LengthUnit::METER, 2);
        $this->assertEquals('3.14 m', $result);
    }

    public function test_format_time_unit(): void {
        $result = UnitConversionHelper::format(60.0, TimeUnit::SECOND, 0);
        $this->assertEquals('60 s', $result);
    }

    // -------------------------------------------------------------------------
    // Druck
    // -------------------------------------------------------------------------

    public function test_convert_pressure_bar_to_pascal(): void {
        $this->assertEqualsWithDelta(100_000.0, UnitConversionHelper::convertPressure(1, PressureUnit::BAR, PressureUnit::PASCAL), 1e-6);
    }

    public function test_convert_pressure_atm_to_bar(): void {
        $this->assertEqualsWithDelta(1.01325, UnitConversionHelper::convertPressure(1, PressureUnit::ATMOSPHERE, PressureUnit::BAR), 1e-6);
    }

    public function test_convert_pressure_psi_to_bar(): void {
        $this->assertEqualsWithDelta(0.0689476, UnitConversionHelper::convertPressure(1, PressureUnit::PSI, PressureUnit::BAR), 1e-5);
    }

    public function test_convert_pressure_h_pa_to_mbar(): void {
        $this->assertEqualsWithDelta(1013.25, UnitConversionHelper::convertPressure(1013.25, PressureUnit::HECTOPASCAL, PressureUnit::MILLIBAR), 1e-4);
    }

    public function test_convert_pressure_same_unit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertPressure(5, PressureUnit::BAR, PressureUnit::BAR), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Energie
    // -------------------------------------------------------------------------

    public function test_convert_energy_kilojoule_to_joule(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertEnergy(1, EnergyUnit::KILOJOULE, EnergyUnit::JOULE), 1e-6);
    }

    public function test_convert_energy_kwh_to_mj(): void {
        $this->assertEqualsWithDelta(3.6, UnitConversionHelper::convertEnergy(1, EnergyUnit::KILOWATT_HOUR, EnergyUnit::MEGAJOULE), 1e-6);
    }

    public function test_convert_energy_kcal_to_kj(): void {
        $this->assertEqualsWithDelta(4.184, UnitConversionHelper::convertEnergy(1, EnergyUnit::KILOCALORIE, EnergyUnit::KILOJOULE), 1e-4);
    }

    public function test_convert_energy_btu_to_joule(): void {
        $this->assertEqualsWithDelta(1055.055, UnitConversionHelper::convertEnergy(1, EnergyUnit::BTU, EnergyUnit::JOULE), 1e-2);
    }

    public function test_convert_energy_same_unit(): void {
        $this->assertEqualsWithDelta(2.5, UnitConversionHelper::convertEnergy(2.5, EnergyUnit::KILOWATT_HOUR, EnergyUnit::KILOWATT_HOUR), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Leistung
    // -------------------------------------------------------------------------

    public function test_convert_power_kw_to_watt(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertPower(1, PowerUnit::KILOWATT, PowerUnit::WATT), 1e-6);
    }

    public function test_convert_power_ps_to_kw(): void {
        $this->assertEqualsWithDelta(0.73549875, UnitConversionHelper::convertPower(1, PowerUnit::METRIC_HP, PowerUnit::KILOWATT), 1e-6);
    }

    public function test_convert_power_hp_to_kw(): void {
        $this->assertEqualsWithDelta(0.74570, UnitConversionHelper::convertPower(1, PowerUnit::MECHANICAL_HP, PowerUnit::KILOWATT), 1e-4);
    }

    public function test_convert_power_same_unit(): void {
        $this->assertEqualsWithDelta(150.0, UnitConversionHelper::convertPower(150, PowerUnit::METRIC_HP, PowerUnit::METRIC_HP), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Winkel
    // -------------------------------------------------------------------------

    public function test_convert_angle_degree_to_radian(): void {
        $this->assertEqualsWithDelta(M_PI, UnitConversionHelper::convertAngle(180, AngleUnit::DEGREE, AngleUnit::RADIAN), 1e-9);
    }

    public function test_convert_angle_radian_to_degree(): void {
        $this->assertEqualsWithDelta(180.0, UnitConversionHelper::convertAngle(M_PI, AngleUnit::RADIAN, AngleUnit::DEGREE), 1e-9);
    }

    public function test_convert_angle_degree_to_gradian(): void {
        $this->assertEqualsWithDelta(400.0, UnitConversionHelper::convertAngle(360, AngleUnit::DEGREE, AngleUnit::GRADIAN), 1e-9);
    }

    public function test_convert_angle_degree_to_arcminute(): void {
        $this->assertEqualsWithDelta(60.0, UnitConversionHelper::convertAngle(1, AngleUnit::DEGREE, AngleUnit::ARCMINUTE), 1e-9);
    }

    public function test_convert_angle_same_unit(): void {
        $this->assertEqualsWithDelta(90.0, UnitConversionHelper::convertAngle(90, AngleUnit::DEGREE, AngleUnit::DEGREE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Datenmenge
    // -------------------------------------------------------------------------

    public function test_convert_data_size_byte_to_kilobyte(): void {
        $this->assertEqualsWithDelta(1.0, UnitConversionHelper::convertDataSize(1000, DataSizeUnit::BYTE, DataSizeUnit::KILOBYTE), 1e-9);
    }

    public function test_convert_data_size_megabyte_to_kibibyte(): void {
        // 1 MB (SI) = 1.000.000 B = 1000000 / 1024 KiB ≈ 976.5625 KiB
        $this->assertEqualsWithDelta(976.5625, UnitConversionHelper::convertDataSize(1, DataSizeUnit::MEGABYTE, DataSizeUnit::KIBIBYTE), 1e-6);
    }

    public function test_convert_data_size_gibibyte_to_gigabyte(): void {
        // 1 GiB = 1024³ B = 1.073.741.824 B → in GB (SI): 1.073741824
        $this->assertEqualsWithDelta(1.073741824, UnitConversionHelper::convertDataSize(1, DataSizeUnit::GIBIBYTE, DataSizeUnit::GIGABYTE), 1e-9);
    }

    public function test_convert_data_size_bit_to_byte(): void {
        $this->assertEqualsWithDelta(1.0, UnitConversionHelper::convertDataSize(8, DataSizeUnit::BIT, DataSizeUnit::BYTE), 1e-9);
    }

    public function test_convert_data_size_same_unit(): void {
        $this->assertEqualsWithDelta(4.0, UnitConversionHelper::convertDataSize(4, DataSizeUnit::GIGABYTE, DataSizeUnit::GIGABYTE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Kraft
    // -------------------------------------------------------------------------

    public function test_convert_force_kilonewton_to_newton(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertForce(1, ForceUnit::KILONEWTON, ForceUnit::NEWTON), 1e-6);
    }

    public function test_convert_force_kgf_to_newton(): void {
        $this->assertEqualsWithDelta(9.80665, UnitConversionHelper::convertForce(1, ForceUnit::KILOGRAM_FORCE, ForceUnit::NEWTON), 1e-6);
    }

    public function test_convert_force_pound_force_to_newton(): void {
        $this->assertEqualsWithDelta(4.448221, UnitConversionHelper::convertForce(1, ForceUnit::POUND_FORCE, ForceUnit::NEWTON), 1e-4);
    }

    public function test_convert_force_dyn_to_newton(): void {
        $this->assertEqualsWithDelta(0.00001, UnitConversionHelper::convertForce(1, ForceUnit::DYN, ForceUnit::NEWTON), 1e-9);
    }

    public function test_convert_force_same_unit(): void {
        $this->assertEqualsWithDelta(9.81, UnitConversionHelper::convertForce(9.81, ForceUnit::NEWTON, ForceUnit::NEWTON), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Frequenz
    // -------------------------------------------------------------------------

    public function test_convert_frequency_kilohertz_to_hertz(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::KILOHERTZ, FrequencyUnit::HERTZ), 1e-9);
    }

    public function test_convert_frequency_megahertz_to_kilohertz(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::MEGAHERTZ, FrequencyUnit::KILOHERTZ), 1e-9);
    }

    public function test_convert_frequency_rpm_to_hertz(): void {
        $this->assertEqualsWithDelta(1.0 / 60.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::RPM, FrequencyUnit::HERTZ), 1e-9);
    }

    public function test_convert_frequency_hertz_to_rpm(): void {
        $this->assertEqualsWithDelta(60.0, UnitConversionHelper::convertFrequency(1, FrequencyUnit::HERTZ, FrequencyUnit::RPM), 1e-9);
    }

    public function test_convert_frequency_same_unit(): void {
        $this->assertEqualsWithDelta(2400.0, UnitConversionHelper::convertFrequency(2400, FrequencyUnit::MEGAHERTZ, FrequencyUnit::MEGAHERTZ), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Beschleunigung
    // -------------------------------------------------------------------------

    public function test_convert_acceleration_gravity_to_ms2(): void {
        $this->assertEqualsWithDelta(9.80665, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::STANDARD_GRAVITY, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-6);
    }

    public function test_convert_acceleration_ms2_to_gravity(): void {
        $this->assertEqualsWithDelta(1.0 / 9.80665, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::METER_PER_SECOND_SQUARED, AccelerationUnit::STANDARD_GRAVITY), 1e-9);
    }

    public function test_convert_acceleration_gal_to_ms2(): void {
        $this->assertEqualsWithDelta(0.01, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::GAL, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-9);
    }

    public function test_convert_acceleration_foot_per_second_squared_to_ms2(): void {
        $this->assertEqualsWithDelta(0.3048, UnitConversionHelper::convertAcceleration(1, AccelerationUnit::FOOT_PER_SECOND_SQUARED, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-9);
    }

    public function test_convert_acceleration_same_unit(): void {
        $this->assertEqualsWithDelta(9.81, UnitConversionHelper::convertAcceleration(9.81, AccelerationUnit::METER_PER_SECOND_SQUARED, AccelerationUnit::METER_PER_SECOND_SQUARED), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrische Spannung
    // -------------------------------------------------------------------------

    public function test_convert_voltage_kilovolt_to_volt(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricVoltage(1, ElectricVoltageUnit::KILOVOLT, ElectricVoltageUnit::VOLT), 1e-9);
    }

    public function test_convert_voltage_millivolt_to_volt(): void {
        $this->assertEqualsWithDelta(0.012, UnitConversionHelper::convertElectricVoltage(12, ElectricVoltageUnit::MILLIVOLT, ElectricVoltageUnit::VOLT), 1e-9);
    }

    public function test_convert_voltage_same_unit(): void {
        $this->assertEqualsWithDelta(230.0, UnitConversionHelper::convertElectricVoltage(230, ElectricVoltageUnit::VOLT, ElectricVoltageUnit::VOLT), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrischer Strom
    // -------------------------------------------------------------------------

    public function test_convert_current_ampere_to_milliampere(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricCurrent(1, ElectricCurrentUnit::AMPERE, ElectricCurrentUnit::MILLIAMPERE), 1e-9);
    }

    public function test_convert_current_milliampere_to_microampere(): void {
        $this->assertEqualsWithDelta(500.0, UnitConversionHelper::convertElectricCurrent(0.5, ElectricCurrentUnit::MILLIAMPERE, ElectricCurrentUnit::MICROAMPERE), 1e-9);
    }

    public function test_convert_current_kiloampere_to_ampere(): void {
        $this->assertEqualsWithDelta(3000.0, UnitConversionHelper::convertElectricCurrent(3, ElectricCurrentUnit::KILOAMPERE, ElectricCurrentUnit::AMPERE), 1e-9);
    }

    public function test_convert_current_same_unit(): void {
        $this->assertEqualsWithDelta(5.0, UnitConversionHelper::convertElectricCurrent(5, ElectricCurrentUnit::AMPERE, ElectricCurrentUnit::AMPERE), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrischer Widerstand
    // -------------------------------------------------------------------------

    public function test_convert_resistance_kilohm_to_ohm(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricResistance(1, ElectricResistanceUnit::KILOOHM, ElectricResistanceUnit::OHM), 1e-9);
    }

    public function test_convert_resistance_megahm_to_kilohm(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertElectricResistance(1, ElectricResistanceUnit::MEGAOHM, ElectricResistanceUnit::KILOOHM), 1e-9);
    }

    public function test_convert_resistance_milliohm_to_ohm(): void {
        $this->assertEqualsWithDelta(0.047, UnitConversionHelper::convertElectricResistance(47, ElectricResistanceUnit::MILLIOHM, ElectricResistanceUnit::OHM), 1e-9);
    }

    public function test_convert_resistance_same_unit(): void {
        $this->assertEqualsWithDelta(470.0, UnitConversionHelper::convertElectricResistance(470, ElectricResistanceUnit::OHM, ElectricResistanceUnit::OHM), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Elektrische Kapazität
    // -------------------------------------------------------------------------

    public function test_convert_capacitance_microfarad_to_nanofarad(): void {
        $this->assertEqualsWithDelta(100.0, UnitConversionHelper::convertElectricCapacitance(0.1, ElectricCapacitanceUnit::MICROFARAD, ElectricCapacitanceUnit::NANOFARAD), 1e-6);
    }

    public function test_convert_capacitance_picofarad_to_microfarad(): void {
        $this->assertEqualsWithDelta(0.001, UnitConversionHelper::convertElectricCapacitance(1000, ElectricCapacitanceUnit::PICOFARAD, ElectricCapacitanceUnit::MICROFARAD), 1e-9);
    }

    public function test_convert_capacitance_same_unit(): void {
        $this->assertEqualsWithDelta(10.0, UnitConversionHelper::convertElectricCapacitance(10, ElectricCapacitanceUnit::NANOFARAD, ElectricCapacitanceUnit::NANOFARAD), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Drehmoment
    // -------------------------------------------------------------------------

    public function test_convert_torque_newton_meter_to_pound_force_foot(): void {
        $this->assertEqualsWithDelta(0.7375621, UnitConversionHelper::convertTorque(1, TorqueUnit::NEWTON_METER, TorqueUnit::POUND_FORCE_FOOT), 1e-5);
    }

    public function test_convert_torque_kilogram_force_meter_to_newton_meter(): void {
        $this->assertEqualsWithDelta(9.80665, UnitConversionHelper::convertTorque(1, TorqueUnit::KILOGRAM_FORCE_METER, TorqueUnit::NEWTON_METER), 1e-6);
    }

    public function test_convert_torque_kilonewton_meter_to_newton_meter(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertTorque(1, TorqueUnit::KILONEWTON_METER, TorqueUnit::NEWTON_METER), 1e-9);
    }

    public function test_convert_torque_same_unit(): void {
        $this->assertEqualsWithDelta(350.0, UnitConversionHelper::convertTorque(350, TorqueUnit::NEWTON_METER, TorqueUnit::NEWTON_METER), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Dynamische Viskosität
    // -------------------------------------------------------------------------

    public function test_convert_viscosity_centipoise_to_pascal_second(): void {
        $this->assertEqualsWithDelta(0.001, UnitConversionHelper::convertViscosity(1, ViscosityUnit::CENTIPOISE, ViscosityUnit::PASCAL_SECOND), 1e-9);
    }

    public function test_convert_viscosity_poise_to_pascal_second(): void {
        $this->assertEqualsWithDelta(0.1, UnitConversionHelper::convertViscosity(1, ViscosityUnit::POISE, ViscosityUnit::PASCAL_SECOND), 1e-9);
    }

    public function test_convert_viscosity_same_unit(): void {
        $this->assertEqualsWithDelta(1.0, UnitConversionHelper::convertViscosity(1, ViscosityUnit::PASCAL_SECOND, ViscosityUnit::PASCAL_SECOND), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Beleuchtungsstärke
    // -------------------------------------------------------------------------

    public function test_convert_illuminance_foot_candle_to_lux(): void {
        $this->assertEqualsWithDelta(10.7639, UnitConversionHelper::convertIlluminance(1, IlluminanceUnit::FOOT_CANDLE, IlluminanceUnit::LUX), 1e-3);
    }

    public function test_convert_illuminance_phot_to_lux(): void {
        $this->assertEqualsWithDelta(10_000.0, UnitConversionHelper::convertIlluminance(1, IlluminanceUnit::PHOT, IlluminanceUnit::LUX), 1e-6);
    }

    public function test_convert_illuminance_kilolux_to_lux(): void {
        $this->assertEqualsWithDelta(100_000.0, UnitConversionHelper::convertIlluminance(100, IlluminanceUnit::KILOLUX, IlluminanceUnit::LUX), 1e-6);
    }

    public function test_convert_illuminance_same_unit(): void {
        $this->assertEqualsWithDelta(500.0, UnitConversionHelper::convertIlluminance(500, IlluminanceUnit::LUX, IlluminanceUnit::LUX), 1e-9);
    }

    // -------------------------------------------------------------------------
    // Magnetische Flussdichte
    // -------------------------------------------------------------------------

    public function test_convert_magnetic_flux_gauss_to_tesla(): void {
        $this->assertEqualsWithDelta(0.0001, UnitConversionHelper::convertMagneticFluxDensity(1, MagneticFluxDensityUnit::GAUSS, MagneticFluxDensityUnit::TESLA), 1e-9);
    }

    public function test_convert_magnetic_flux_tesla_to_millitesla(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertMagneticFluxDensity(1, MagneticFluxDensityUnit::TESLA, MagneticFluxDensityUnit::MILLITESLA), 1e-9);
    }

    public function test_convert_magnetic_flux_microtesla_to_nanotesla(): void {
        $this->assertEqualsWithDelta(1000.0, UnitConversionHelper::convertMagneticFluxDensity(1, MagneticFluxDensityUnit::MICROTESLA, MagneticFluxDensityUnit::NANOTESLA), 1e-6);
    }

    public function test_convert_magnetic_flux_same_unit(): void {
        $this->assertEqualsWithDelta(50.0, UnitConversionHelper::convertMagneticFluxDensity(50, MagneticFluxDensityUnit::MICROTESLA, MagneticFluxDensityUnit::MICROTESLA), 1e-9);
    }
}
