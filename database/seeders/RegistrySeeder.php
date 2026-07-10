<?php

namespace Database\Seeders;

use App\Models\Battery;
use App\Models\Car;
use Illuminate\Database\Seeder;

/**
 * Импорт реестра: велосипеды Kugoo U5 / V3 Pro и аккумуляторы (АКБ).
 * Идемпотентно: cars — по license_plate, batteries — по vin.
 */
class RegistrySeeder extends Seeder
{
    public function run(): void
    {
        // Kugoo U5 (аккумулятор 60/45): [позывной, номер рамы]
        $u5 = [
            ['В1', 'JL20250500668'], ['В2', 'JL20250500722'], ['В3', 'JL20250500833'], ['В4', null],
            ['В5', 'JL20250500053'], ['В6', 'JL20250500416'], ['В7', 'JL20250500125'], ['В8', 'LJ20250500484'],
            ['В9', 'LJ20250500102'], ['В10', 'LJ20250500433'], ['В11', 'LJ20250500459'], ['В12', 'LJ20250500410'],
            ['В13', 'LJ20250500487'], ['В14', 'LJ20250500407'], ['В15', null], ['В16', 'LJ20250600253'],
            ['В17', 'LJ20250600145'], ['В18', null],
        ];
        // Kugoo V3 Pro (аккумулятор 60/21)
        $v3 = [
            ['В1', 'JL20240715165'], ['В2', 'JL20250222766'], ['В3', 'JL20240714458'], ['В4', 'JL20250223664'],
            ['В5', null], ['В6', null], ['В7', null], ['В8', null], ['В9', null], ['В10', null],
            ['В11', null], ['В12', null], ['В13', null], ['В14', null],
        ];

        foreach ($u5 as [$cs, $frame]) {
            Car::firstOrCreate(
                ['license_plate' => 'U5-'.$cs],
                ['brand' => 'Kugoo', 'model' => 'U5', 'frame_number' => $frame, 'comment' => 'Позывной '.$cs, 'balance' => 0],
            );
        }
        foreach ($v3 as [$cs, $frame]) {
            Car::firstOrCreate(
                ['license_plate' => 'V3Pro-'.$cs],
                ['brand' => 'Kugoo', 'model' => 'V3 Pro', 'frame_number' => $frame, 'comment' => 'Позывной '.$cs, 'balance' => 0],
            );
        }

        // АКБ для Kugoo V3 Pro (60/21): [позывной, вин]
        $bV3 = [
            ['001', 'IG-60V20.8AH-DC26-IT-PW-C-18485'], ['002', 'IG-60V20.8AH-DC26-IT-PW-C-18175'],
            ['003', 'IG-60V20.8AH-DC26-IT-PW-C-17945'], ['004', 'ZBSDA22024042635A'],
            ['005', 'ZBSDA22024041635A'], ['006', 'ZBSDA22024050635A'], ['007', 'ZBSDA22024061835A'],
            ['008', 'IG-60V20.8AH-DC26-IT-PW-C-19011'],
        ];
        foreach ($bV3 as [$cs, $vin]) {
            Battery::firstOrCreate(['vin' => $vin], ['car_model' => 'Kugoo V3 Pro', 'capacity' => '60/21', 'callsign' => $cs]);
        }

        // АКБ для Kugoo U5 (60/45): [позывной, вин]  (#9 дублирует вин #3 — firstOrCreate пропустит)
        $bV5 = [
            ['1', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0902'],
            ['2', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0852'],
            ['3', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0626'],
            ['4', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0911'],
            ['5', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0939'],
            ['6', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0536'],
            ['7', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0948'],
            ['8', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0814'],
            ['9', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0626'],
            ['10', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0648'],
            ['11', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0691'],
            ['12', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0655'],
            ['13', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0608'],
            ['14', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0645'],
            ['15', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/0600'],
            ['16', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/1365'],
            ['17', 'JS/TJ/0033/LW/MTYZ/D18B12/6045/0/20250530/1541'],
        ];
        foreach ($bV5 as [$cs, $vin]) {
            Battery::firstOrCreate(['vin' => $vin], ['car_model' => 'Kugoo U5', 'capacity' => '60/45', 'callsign' => $cs]);
        }
    }
}
