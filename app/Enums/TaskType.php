<?php

namespace App\Enums;

enum TaskType: string
{
    case SWEEP_OUT = 'Sweep Out';
    case RECLEAN_LIKE_F = 'Reclean Like F';
    case RECLEAN_1 = 'Reclean #1';
    case PRESSURE_WASH_EXTERIOR = 'Pressure Wash Exterior';
    case RECLEAN_3 = 'Reclean #3';
    case BRUSH_MACHINE = 'Brush Machine';
    case RECLEAN_2 = 'Reclean #2';
    case CLEAN_ROUGH = 'Clean Rough';
    case CLEAN_ROUGH_2 = 'Clean Rough #2';
    case CLEAN_ROUGH_3 = 'Clean Rough #3';
    case CLEAN_ROUGH_4 = 'Clean Rough #4';
    case CLEAN_ROUGH_5 = 'Clean Rough #5';
    case CLEAN_ROUGH_6 = 'Clean Rough #6';
    case CLEAN_ROUGH_7 = 'Clean Rough #7';
    case CLEAN_ROUGH_8 = 'Clean Rough #8';
    case CLEAN_ROUGH_9 = 'Clean Rough #9';
    case CLEAN_ROUGH_10 = 'Clean Rough #10';
    case CT_CLEAN = 'Ct Clean';
    case FINAL_CLEAN = 'Final Clean';
    case FLOOR_MACHINE = 'Floor Machine';
    case FLOOR_MACHINE_2 = 'Floor Machine #2';
    case FLOOR_MACHINE_3 = 'Floor Machine #3';
    case FLOOR_MACHINE_4 = 'Floor Machine #4';
    case FLOOR_MACHINE_5 = 'Floor Machine #5';
    case FLOOR_MACHINE_6 = 'Floor Machine #6';
    case FLOOR_MACHINE_7 = 'Floor Machine #7';
    case FLOOR_MACHINE_8 = 'Floor Machine #8';
    case FLOOR_MACHINE_9 = 'Floor Machine #9';
    case FLOOR_MACHINE_10 = 'Floor Machine #10';
    case FRAME_SWEEP = 'Frame Sweep';
    case GARAGE_CLEAN_UP = 'Garage Clean Up';
    case POST_DRYWALL_SWEEP = 'Post Drywall Sweep';
    case PRE_CARPET = 'Pre-Carpet';
    case PRESSURE_WASH = 'Pressure Wash';
    case PRESSURE_WASH_2 = 'Pressure Wash #2';
    case PRESSURE_WASH_3 = 'Pressure Wash #3';
    case PRESSURE_WASH_4 = 'Pressure Wash #4';
    case PRESSURE_WASH_5 = 'Pressure Wash #5';
    case PRESSURE_WASH_6 = 'Pressure Wash #6';
    case PRESSURE_WASH_7 = 'Pressure Wash #7';
    case PRESSURE_WASH_8 = 'Pressure Wash #8';
    case PRESSURE_WASH_9 = 'Pressure Wash #9';
    case PRESSURE_WASH_10 = 'Pressure Wash #10';
    case RECLEAN_4 = 'Reclean #4';
    case RECLEAN_5 = 'Reclean #5';
    case RECLEAN_6 = 'Reclean #6';
    case RECLEAN_7 = 'Reclean #7';
    case RECLEAN_8 = 'Reclean #8';
    case RECLEAN_9 = 'Reclean #9';
    case RECLEAN_10 = 'Reclean #10';
    case RECLEAN_11 = 'Reclean #11';
    case RECLEAN_12 = 'Reclean #12';
    case RECLEAN_13 = 'Reclean #13';
    case RECLEAN_14 = 'Reclean #14';
    case RECLEAN_15 = 'Reclean #15';
    case RECLEAN_16 = 'Reclean #16';
    case RECLEAN_17 = 'Reclean #17';
    case RECLEAN_18 = 'Reclean #18';
    case RECLEAN_19 = 'Reclean #19';
    case RECLEAN_20 = 'Reclean #20';
    case RECLEAN_MODELOS = 'Reclean Modelos';
    case RECLEAN_SPEC = 'Reclean Spec';
    case ROUGH_WINDOWS = 'Rough Windows';
    case STEAM_CARPET = 'Steam Carpet';
    case PW_FASCIA = 'Pw Fascia';
    case YARD_CLEAN = 'Yard Clean';
    case WEEKLY_SPECS = 'Weekly Specs';
    case COMMON_AREAS = 'Common Areas';
    case CORRIDORS = 'Corridors';
    case STORAGE_ROOM = 'Storage Room';
    case EXTRA_CLEAN_REQ = 'Extra Clean Req';
    case FINAL_WINDOWS = 'Final Windows';
    case ONLY_SOFFIT = 'Only Soffit';
    case STAIR_TOWERS = 'Stair Towers';
    case PARKING_LOT = 'Parking Lot';
    case LOBBY = 'Lobby';
    case ELEVATOR = 'Elevator';
    case PUMP_AND_TRASH_ROOM = 'Pump And Trash Room';
    case PW_EXTERIOR_ALL_FLAT_WORK = 'Pw Exterior + All Flat Work';
    case PW_PARKING_LOT = 'Pw Parking Lot';
    case WARRANTY_CLEAN = 'Warranty Clean';
    case QA_CLEAN = 'QA Clean';
    case RECLEAN_MODELO = 'Reclean Modelo';
    case TBD = 'TBD';

    public static function all(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }
}
