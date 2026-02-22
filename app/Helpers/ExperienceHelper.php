<?php

namespace App\Helpers;

use App\Models\Character;
use App\Models\CharacterPet;

class ExperienceHelper
{
    /**
     * Character XP table (levels 1-100)
     */
    private static $characterXpTable = [
        1 => 15, 2 => 304, 3 => 493, 4 => 711, 5 => 961, 6 => 1247, 7 => 1574, 8 => 1945, 9 => 2366, 10 => 2843,
        11 => 3382, 12 => 3989, 13 => 4673, 14 => 5542, 15 => 6306, 16 => 7273, 17 => 8537, 18 => 9569, 19 => 10922, 20 => 12433,
        21 => 14117, 22 => 15992, 23 => 18080, 24 => 20401, 25 => 22981, 26 => 25845, 27 => 29024, 28 => 32548, 29 => 36454, 30 => 40780,
        31 => 45569, 32 => 50867, 33 => 56725, 34 => 63201, 35 => 70354, 36 => 78254, 37 => 86973, 38 => 96593, 39 => 107202, 40 => 118899,
        41 => 131790, 42 => 145991, 43 => 161632, 44 => 178850, 45 => 197801, 46 => 218652, 47 => 241587, 48 => 266806, 49 => 294530, 50 => 325000,
        51 => 358478, 52 => 395253, 53 => 435640, 54 => 479982, 55 => 528656, 56 => 582073, 57 => 640648, 58 => 704980, 59 => 775497, 60 => 858822,
        61 => 973598, 62 => 1030523, 63 => 1132364, 64 => 1243956, 65 => 1366211, 66 => 1500266, 67 => 1646789, 68 => 1807388, 69 => 1983211, 70 => 2175702,
        71 => 3857490, 72 => 5539279, 73 => 7221067, 74 => 8902856, 75 => 10584644, 76 => 34958287, 77 => 38667739, 78 => 42377192, 79 => 46086644, 80 => 49796096,
        81 => 69149957, 82 => 73172858, 83 => 77195758, 84 => 81218659, 85 => 85241560, 86 => 118206898, 87 => 130658489, 88 => 143202210, 89 => 155837542, 90 => 168563974,
        91 => 586602629, 92 => 686931906, 93 => 811340210, 94 => 965606507, 95 => 1156896715, 96 => 4164828174, 97 => 5067207611, 98 => 6240300880, 99 => 7765322130, 100 => 9747849755
    ];

    /**
     * Pet XP table (levels 1-100)
     */
    private static $petXpTable = [
        1 => 28, 2 => 61, 3 => 99, 4 => 142, 5 => 192, 6 => 249, 7 => 315, 8 => 389, 9 => 473, 10 => 569,
        11 => 676, 12 => 798, 13 => 935, 14 => 1088, 15 => 1261, 16 => 1455, 17 => 1671, 18 => 1914, 19 => 2184, 20 => 2487,
        21 => 2823, 22 => 3198, 23 => 3616, 24 => 4080, 25 => 4596, 26 => 5196, 27 => 5805, 28 => 6510, 29 => 7291, 30 => 8156,
        31 => 9114, 32 => 10173, 33 => 11345, 34 => 12640, 35 => 14071, 36 => 15651, 37 => 17395, 38 => 19319, 39 => 21440, 40 => 23780,
        41 => 27733, 42 => 30696, 43 => 33471, 44 => 36193, 45 => 39579, 46 => 42140, 47 => 46342, 48 => 49634, 49 => 53379, 50 => 56695,
        51 => 59936, 52 => 66622, 53 => 70841, 54 => 74605, 55 => 79734, 56 => 86755, 57 => 90227, 58 => 95427, 59 => 103740, 60 => 110291,
        61 => 125307, 62 => 145705, 63 => 174070, 64 => 211985, 65 => 259748, 66 => 314393, 67 => 377280, 68 => 447571, 69 => 526381, 70 => 612222,
        71 => 705963, 72 => 806478, 73 => 912730, 74 => 1026380, 75 => 1144886, 76 => 1269847, 77 => 1402425, 78 => 1538415, 79 => 1683103, 80 => 1831845,
        81 => 2049957, 82 => 2372858, 83 => 2695758, 84 => 3018659, 85 => 3541560, 86 => 4057490, 87 => 4639279, 88 => 5221067, 89 => 5902856, 90 => 6184644,
        91 => 7297879, 92 => 8611498, 93 => 10161568, 94 => 11990650, 95 => 14148967, 96 => 65910291, 97 => 81728761, 98 => 101343664, 99 => 125666144, 100 => 155826018
    ];

    /**
     * Get level cap based on character rank
     * 
     * @param string $rank
     * @return int
     */
    private static function getLevelCap(string $rank): int
    {
        $rankMap = [
            'Chunin'                => 2,
            'Tensai Chunin'         => 3,
            'Jounin'                => 4,
            'Tensai Jounin'         => 5,
            'Special Jounin'        => 6,
            'Tensai Special Jounin' => 7,
            'Ninja Tutor'           => 8,
            'Senior Ninja Tutor'    => 9,
        ];

        $rankId = $rankMap[$rank] ?? 1;

        if ($rankId < 2) return 20; // Genin
        if ($rankId < 4) return 40; // Chunin, Tensai Chunin
        if ($rankId < 7) return 60; // Jounin, Tensai Jounin, Special Jounin
        if ($rankId < 9) return 80; // Tensai Special Jounin, Ninja Tutor
        
        return 100; // Senior Ninja Tutor and above
    }

    /**
     * Check and process character level up
     * 
     * @param Character $char
     * @return bool Whether the character leveled up
     */
    public static function checkCharacterLevelUp(Character $char): bool
    {
        $leveledUp = false;
        $maxLevel = self::getLevelCap($char->rank);
        
        while ($char->level < $maxLevel) {
            $requiredXp = self::$characterXpTable[$char->level] ?? 999999999;
            
            if ($char->xp >= $requiredXp) {
                // Deduct XP used for this level up (User Request for non-cumulative XP model)
                $char->xp -= $requiredXp;
                
                $char->level++;
                $leveledUp = true;
            } else {
                break;
            }
        }
        
        return $leveledUp;
    }

    /**
     * Add XP to character and check for level up
     * Only adds XP if character is below level 100
     * 
     * @param Character $char
     * @param int $xpAmount
     * @return bool Whether the character leveled up
     */
    public static function addCharacterXp(Character $char, int $xpAmount): bool
    {
        // Don't add XP if already max level
        if ($char->level >= 100) {
            return false;
        }
        
        $char->xp += $xpAmount;
        return self::checkCharacterLevelUp($char);
    }

    /**
     * Check and process pet level up
     * Prevents pet from exceeding level 100 or owner's level
     * 
     * @param CharacterPet $pet
     * @param int|null $ownerLevel Optional owner level to cap pet level
     * @return bool Whether the pet leveled up
     */
    public static function checkPetLevelUp(CharacterPet $pet, ?int $ownerLevel = null): bool
    {
        $leveledUp = false;
        $maxLevel = 100;
        
        // If owner level is provided, pet cannot exceed owner's level
        if ($ownerLevel !== null) {
            $maxLevel = min(100, $ownerLevel);
        }
        
        while ($pet->pet_level < $maxLevel) {
            $requiredXp = self::$petXpTable[$pet->pet_level] ?? 999999999;
            
            if ($pet->pet_xp >= $requiredXp) {
                $pet->pet_level++;
                $leveledUp = true;
            } else {
                break;
            }
        }
        
        return $leveledUp;
    }

    /**
     * Add XP to pet and check for level up
     * Only adds XP if pet is below level 100
     * 
     * @param CharacterPet $pet
     * @param int $xpAmount
     * @param int|null $ownerLevel Optional owner level to cap pet level
     * @return bool Whether the pet leveled up
     */
    public static function addPetXp(CharacterPet $pet, int $xpAmount, ?int $ownerLevel = null): bool
    {
        // Don't add XP if already max level
        if ($pet->pet_level >= 100) {
            return false;
        }
        
        // Don't add XP if pet level already matches or exceeds owner level
        if ($ownerLevel !== null && $pet->pet_level >= $ownerLevel) {
            return false;
        }
        
        $pet->pet_xp += $xpAmount;
        return self::checkPetLevelUp($pet, $ownerLevel);
    }

    /**
     * Add XP to equipped pet by character ID
     * Automatically caps pet level to owner's level
     * 
     * @param int $charId
     * @param int $xpAmount
     * @return bool Whether the pet leveled up
     */
    public static function addEquippedPetXp(int $charId, int $xpAmount): bool
    {
        $char = Character::find($charId);
        
        if (!$char || !$char->equipped_pet_id) {
            return false;
        }
        
        $pet = CharacterPet::where('character_id', $charId)
            ->where('id', $char->equipped_pet_id)
            ->first();
        
        if (!$pet) {
            return false;
        }
        
        // Pass owner level to prevent pet from exceeding it
        $leveledUp = self::addPetXp($pet, $xpAmount, $char->level);
        $pet->save();
        
        return $leveledUp;
    }

    /**
     * Get required XP for a character level
     * 
     * @param int $level
     * @return int
     */
    public static function getRequiredCharacterXp(int $level): int
    {
        return self::$characterXpTable[$level] ?? 999999999;
    }

    /**
     * Get required XP for a pet level
     * 
     * @param int $level
     * @return int
     */
    public static function getRequiredPetXp(int $level): int
    {
        return self::$petXpTable[$level] ?? 999999999;
    }
}
