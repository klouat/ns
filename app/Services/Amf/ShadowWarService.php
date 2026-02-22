<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\ShadowWarSeason;
use App\Models\ShadowWarPlayer;
use App\Models\ShadowWarPreset;
use App\Models\ShadowWarBattle;
use App\Models\ShadowWarEnemyCache;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShadowWarService
{
    // ─── Constants ───────────────────────────────────────────────────
    private const MAX_ENERGY          = 100;
    private const ENERGY_REFILL_COST  = 50;  // tokens
    private const REFRESH_COST        = 40;  // tokens
    private const ENEMIES_PER_LIST    = 5;
    private const ENERGY_PER_BATTLE   = 10;
    private const BASE_TROPHY_WIN     = 30000;
    private const BASE_TROPHY_LOSE    = 15;

    // League thresholds (index = rank id)
    private const LEAGUE_THRESHOLDS = [0, 200, 500, 1000, 1800, 2800, 4000, 5500];

    private const SQUAD_NAMES  = ['assault', 'ambush', 'medic', 'kage', 'hq'];
    private const LEAGUE_NAMES = ['Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond', 'Master', 'Grand Master', 'Sage'];

    // ─── Entry point called by AmfController ──────────────────────
    public function executeService($action, $params)
    {
        Log::info('ShadowWarService::executeService', [
            'action' => $action,
            'params' => $params,
        ]);

        return match ($action) {
            'getSeason'           => $this->get_season($params),
            'getStatus'           => $this->get_status($params),
            'getProfile'          => $this->get_profile($params),
            'getEnemies'          => $this->get_enemies($params),
            'getEnemyInfo'        => $this->get_enemy_info($params),
            'refreshEnemies'      => $this->refresh_enemies($params),
            'refillEnergy'        => $this->refill_energy($params),
            'startBattle'         => $this->start_battle($params),
            'finishBattle'        => $this->finish_battle($params),
            'getPresets'          => $this->get_presets($params),
            'usePreset'           => $this->use_preset($params),
            'globalLeaderboard'   => $this->global_leaderboard($params),
            'squadLeaderboard'    => $this->squad_leaderboard($params),
            default => (object)['status' => 0, 'error' => 'Unknown action: ' . $action],
        };
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function find_character($char_id)
    {
        return Character::find($char_id);
    }

    private function active_season()
    {
        return ShadowWarSeason::activeSeason();
    }

    private function get_or_create_player($char_id, $season_id)
    {
        return ShadowWarPlayer::firstOrCreate(
            ['character_id' => $char_id, 'season_id' => $season_id],
            [
                'squad'  => rand(0, 4),
                'trophy' => 0,
                'rank'   => 0,
                'energy' => self::MAX_ENERGY,
            ]
        );
    }

    private function rank_from_trophy(int $trophy): int
    {
        $rank = 0;
        foreach (self::LEAGUE_THRESHOLDS as $i => $threshold) {
            if ($trophy >= $threshold) {
                $rank = $i;
            }
        }
        return $rank;
    }

    /**
     * Build squad ranking list for the active season (top squad by avg trophies).
     */
    private function build_squads_ranking(int $season_id): array
    {
        $squads_raw = DB::table('shadow_war_players')
            ->select('squad', DB::raw('COALESCE(SUM(trophy), 0) as total_trophy'))
            ->where('season_id', $season_id)
            ->groupBy('squad')
            ->orderByDesc('total_trophy')
            ->get();

        $squads = [];
        foreach ($squads_raw as $s) {
            $squads[] = (object)[
                'squad'        => (int)$s->squad,
                'squad_name'   => self::SQUAD_NAMES[$s->squad] ?? 'assault',
                'total_trophy' => (int)$s->total_trophy,
            ];
        }

        // Ensure all 5 squads are represented
        $present = array_map(fn($s) => $s->squad, $squads);
        for ($i = 0; $i < 5; $i++) {
            if (!in_array($i, $present)) {
                $squads[] = (object)[
                    'squad'        => $i,
                    'squad_name'   => self::SQUAD_NAMES[$i],
                    'total_trophy' => 0,
                ];
            }
        }

        usort($squads, fn($a, $b) => $b->total_trophy <=> $a->total_trophy);

        return $squads;
    }

    /**
     * Build equipment set object for an enemy display in the battle popup.
     */
    private function build_enemy_set(Character $char): object
    {
        $gender_suffix = $char->gender == 1 ? '_1' : '_0';

        $skills_raw = $char->equipment_skills ?? '';
        $skills_arr = $skills_raw !== '' ? explode(',', $skills_raw) : [];

        return (object)[
            'weapon'     => $char->equipment_weapon   ?? 'wpn_01',
            'clothing'   => $char->equipment_clothing ?? 'set_01' . $gender_suffix,
            'back_item'  => $char->equipment_back     ?? 'back_01',
            'accessory'  => $char->equipment_accessory ?? 'accessory_01',
            'hairstyle'  => $char->hair_style         ?? 'hair_01' . $gender_suffix,
            'face'       => 'face_01' . $gender_suffix,
            'hair_color' => $char->hair_color ?? '0|0',
            'skin_color' => $char->skin_color ?? '0|0',
            'skills'     => $skills_arr,
        ];
    }

    /**
     * Build the full character data for battle rendering (used by getEnemyInfo).
     * Expects $char to have talent_skills, senjutsu_skills, and pets eager-loaded.
     */
    private function build_character_data(Character $char): object
    {
        $gender_suffix = $char->gender == 1 ? '_1' : '_0';

        $weapon   = $char->equipment_weapon   ?? 'wpn_01';
        $clothing = $char->equipment_clothing ?? 'set_01' . $gender_suffix;
        $back     = $char->equipment_back     ?? 'back_01';
        $acc      = $char->equipment_accessory ?? 'accessory_01';
        $hair     = $char->hair_style         ?? 'hair_01' . $gender_suffix;
        $skills   = $char->equipment_skills   ?? 'skill_01';

        // Pet data — use eager-loaded pets if available
        $pet_data = (object)[];
        $pet_swf  = null;

        if ($char->equipped_pet_id) {
            $pet = $char->relationLoaded('pets')
                ? $char->pets->firstWhere('id', $char->equipped_pet_id)
                : \App\Models\CharacterPet::find($char->equipped_pet_id);

            if ($pet) {
                $pet_swf  = $pet->pet_swf;
                $pet_data = (object)[
                    'pet_id'     => $pet->id,
                    'pet_swf'    => $pet->pet_swf,
                    'pet_name'   => $pet->pet_name,
                    'pet_level'  => $pet->pet_level,
                    'pet_xp'     => $pet->pet_xp,
                    'pet_mp'     => $pet->pet_mp,
                    'pet_skills' => $pet->pet_skills,
                ];
            }
        }

        // Use eager-loaded relationships if available, fallback to query
        $talent_skills = $char->relationLoaded('talent_skills')
            ? $char->talent_skills->map(fn($t) => $t->skill_id . ':' . $t->level)->implode(',')
            : \App\Models\CharacterTalentSkill::where('character_id', $char->id)
                ->get()->map(fn($t) => $t->skill_id . ':' . $t->level)->implode(',');

        $senjutsu_skills_inv = $char->relationLoaded('senjutsu_skills')
            ? $char->senjutsu_skills->map(fn($s) => $s->skill_id . ':' . $s->level)->implode(',')
            : \App\Models\CharacterSenjutsuSkill::where('character_id', $char->id)
                ->get()->map(fn($s) => $s->skill_id . ':' . $s->level)->implode(',');

        return (object)[
            'character_data' => (object)[
                'character_id'        => $char->id,
                'character_name'      => $char->name,
                'character_name_color' => $char->name_color,
                'character_level'     => $char->level,
                'character_xp'        => $char->xp,
                'character_gender'    => $char->gender,
                'character_rank'      => match ($char->rank) {
                    'Chunin'                 => 2,
                    'Tensai Chunin'          => 3,
                    'Jounin'                 => 4,
                    'Tensai Jounin'          => 5,
                    'Special Jounin'         => 6,
                    'Tensai Special Jounin'  => 7,
                    'Ninja Tutor'            => 8,
                    'Senior Ninja Tutor'     => 9,
                    'Sage'                   => 10,
                    default                  => 1,
                },
                'character_prestige'  => $char->prestige,
                'character_element_1' => $char->element_1,
                'character_element_2' => $char->element_2 ?? 0,
                'character_element_3' => $char->element_3 ?? 0,
                'character_talent_1'  => $char->talent_1,
                'character_talent_2'  => $char->talent_2,
                'character_talent_3'  => $char->talent_3,
                'character_gold'      => $char->gold,
                'character_tp'        => $char->tp,
                'character_ss'        => $char->character_ss,
                'character_class'     => $char->class,
                'character_senjutsu'  => $char->senjutsu ? strtolower($char->senjutsu) : null,
            ],
            'character_points' => (object)[
                'atrrib_wind'      => $char->point_wind,
                'atrrib_fire'      => $char->point_fire,
                'atrrib_lightning' => $char->point_lightning,
                'atrrib_water'     => $char->point_water,
                'atrrib_earth'     => $char->point_earth,
                'atrrib_free'      => $char->point_free,
            ],
            'character_sets' => (object)[
                'weapon'           => $weapon,
                'back_item'        => $back,
                'accessory'        => $acc,
                'hairstyle'        => $hair,
                'clothing'         => $clothing,
                'skills'           => $skills,
                'senjutsu_skills'  => $char->equipped_senjutsu_skills,
                'hair_color'       => $char->hair_color ?? '0|0',
                'skin_color'       => $char->skin_color ?? '0|0',
                'face'             => 'face_01' . $gender_suffix,
                'pet'              => $pet_swf,
                'anims'            => $char->equipped_animations ? (object)json_decode($char->equipped_animations, true) : (object)[],
            ],
            'character_inventory' => (object)[
                'char_talent_skills'   => $talent_skills,
                'char_senjutsu_skills' => $senjutsu_skills_inv,
            ],
            'pet_data' => $pet_data,
            'clan'     => null,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Action handlers
    // ═══════════════════════════════════════════════════════════════

    // ── getSeason ────────────────────────────────────────────────────
    // Client stores entire response as Character.shadow_war_season
    // Then accesses: shadow_war_season.season.num, season.date, season.time
    private function get_season(array $params): object
    {
        [$char_id, $session_key] = $params;

        $season = $this->active_season();

        if (!$season) {
            return (object)[
                'status' => 1,
                'active' => false,
                'season' => (object)[
                    'num'  => 0,
                    'date' => '',
                    'time' => 0,
                ],
            ];
        }

        $remaining_seconds = 0;
        if ($season->end_at) {
            $remaining_seconds = max(0, $season->end_at->timestamp - now()->timestamp);
        }

        return (object)[
            'status' => 1,
            'active' => true,
            'season' => (object)[
                'num'  => $season->num,
                'date' => $season->date ?? '',
                'time' => $remaining_seconds,
            ],
        ];
    }

    // ── getStatus ────────────────────────────────────────────────────
    // Response is stored as this.shadowWarData
    // Client accesses: .energy, .squad, .rank, .trophy, .show_profile, .squads[]
    private function get_status(array $params): object
    {
        [$char_id, $session_key] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $player = $this->get_or_create_player($char_id, $season->id);

        // Sync rank from trophy
        $player->rank = $this->rank_from_trophy($player->trophy);
        $player->save();

        $squads = $this->build_squads_ranking($season->id);

        $show_profile = $player->show_profile;

        // Only show the squad card once, then dismiss
        if ($player->show_profile) {
            $player->show_profile = false;
            $player->save();
        }

        return (object)[
            'status'       => 1,
            'energy'       => $player->energy,
            'max_energy'   => self::MAX_ENERGY,
            'trophy'       => $player->trophy,
            'rank'         => $player->rank,
            'squad'        => $player->squad,
            'squad_name'   => self::SQUAD_NAMES[$player->squad] ?? 'assault',
            'league_name'  => self::LEAGUE_NAMES[$player->rank] ?? 'Bronze',
            'show_profile' => $show_profile,
            'squads'       => $squads,
            'season'       => (object)[
                'num'  => $season->num,
                'date' => $season->date ?? '',
                'time' => $season->end_at ? max(0, $season->end_at->timestamp - now()->timestamp) : 0,
            ],
        ];
    }

    // ── getProfile ───────────────────────────────────────────────────
    // ArenaStatistic.as reads: param1.overall (Object), param1.seasonal (Array)
    // overall: seasons_played, total_battles, overall_attack_win_rate, overall_defend_win_rate,
    //          total_attacks, total_attack_wins, total_defends, total_defend_wins,
    //          avg_battles_per_season, performance_grade, overall_win_rate,
    //          best_season, best_season_win_rate, worst_season, worst_season_win_rate
    // seasonal[]: season, started_at, ended_at, stats {
    //   total_battles, win_rate_attack, win_rate_defend,
    //   attacks, attack_win, defends, defend_win, avg_battle_time
    // }
    private function get_profile(array $params): object
    {
        [$char_id, $session_key] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        // Get all seasons the player participated in
        $player_seasons = ShadowWarPlayer::where('character_id', $char_id)
            ->pluck('season_id')
            ->toArray();

        if (empty($player_seasons)) {
            // Include current season even if no battles yet
            $season = $this->active_season();
            if ($season) {
                $this->get_or_create_player($char_id, $season->id);
                $player_seasons = [$season->id];
            }
        }

        $seasons = ShadowWarSeason::whereIn('id', $player_seasons)
            ->orderByDesc('num')
            ->get();

        // Build per-season stats
        $seasonal        = [];
        $all_attacks     = 0;
        $all_attack_wins = 0;
        $all_defends     = 0;
        $all_defend_wins = 0;
        $all_battles     = 0;
        $best_season     = null;
        $best_win_rate   = -1;
        $worst_season    = null;
        $worst_win_rate  = 101;

        foreach ($seasons as $s) {
            // Attacks (this player as attacker)
            $attacks    = ShadowWarBattle::where('attacker_id', $char_id)
                ->where('season_id', $s->id)->where('is_finished', true)->count();
            $attack_win = ShadowWarBattle::where('attacker_id', $char_id)
                ->where('season_id', $s->id)->where('is_finished', true)
                ->where('trophies_change', '>', 0)->count();

            // Defends (this player as defender)
            $defends    = ShadowWarBattle::where('defender_id', $char_id)
                ->where('season_id', $s->id)->where('is_finished', true)->count();
            $defend_win = ShadowWarBattle::where('defender_id', $char_id)
                ->where('season_id', $s->id)->where('is_finished', true)
                ->where('trophies_change', '<=', 0)->count();

            $total_battles   = $attacks + $defends;
            $win_rate_attack = $attacks > 0 ? round(($attack_win / $attacks) * 100) : 0;
            $win_rate_defend = $defends > 0 ? round(($defend_win / $defends) * 100) : 0;
            $season_win_rate = $total_battles > 0
                ? round((($attack_win + $defend_win) / $total_battles) * 100)
                : 0;

            // Avg battle time from created_at → updated_at on finished battles
            $avg_seconds = ShadowWarBattle::where('is_finished', true)
                ->where('season_id', $s->id)
                ->where(function ($q) use ($char_id) {
                    $q->where('attacker_id', $char_id)
                      ->orWhere('defender_id', $char_id);
                })
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time')
                ->value('avg_time');

            $avg_battle_time = $avg_seconds > 0
                ? round($avg_seconds) . 's'
                : '0s';

            // Track best/worst
            if ($total_battles > 0) {
                if ($season_win_rate > $best_win_rate) {
                    $best_win_rate = $season_win_rate;
                    $best_season   = $s->num;
                }
                if ($season_win_rate < $worst_win_rate) {
                    $worst_win_rate = $season_win_rate;
                    $worst_season   = $s->num;
                }
            }

            // Accumulate totals
            $all_attacks     += $attacks;
            $all_attack_wins += $attack_win;
            $all_defends     += $defends;
            $all_defend_wins += $defend_win;
            $all_battles     += $total_battles;

            $seasonal[] = (object)[
                'season'     => $s->num,
                'started_at' => $s->start_at ? $s->start_at->format('M d, Y') : '',
                'ended_at'   => $s->end_at ? $s->end_at->format('M d, Y') : 'Ongoing',
                'stats'      => (object)[
                    'total_battles'   => $total_battles,
                    'attacks'         => $attacks,
                    'attack_win'      => $attack_win,
                    'win_rate_attack' => $win_rate_attack,
                    'defends'         => $defends,
                    'defend_win'      => $defend_win,
                    'win_rate_defend' => $win_rate_defend,
                    'avg_battle_time' => $avg_battle_time,
                ],
            ];
        }

        $seasons_played            = count($seasons);
        $overall_attack_win_rate   = $all_attacks > 0 ? round(($all_attack_wins / $all_attacks) * 100) : 0;
        $overall_defend_win_rate   = $all_defends > 0 ? round(($all_defend_wins / $all_defends) * 100) : 0;
        $overall_win_rate          = $all_battles > 0
            ? round((($all_attack_wins + $all_defend_wins) / $all_battles) * 100)
            : 0;
        $avg_battles_per_season    = $seasons_played > 0 ? round($all_battles / $seasons_played) : 0;

        $performance_grade = $this->calculate_grade($overall_win_rate);

        $overall = (object)[
            'seasons_played'           => $seasons_played,
            'total_battles'            => $all_battles,
            'total_attacks'            => $all_attacks,
            'total_attack_wins'        => $all_attack_wins,
            'overall_attack_win_rate'  => $overall_attack_win_rate,
            'total_defends'            => $all_defends,
            'total_defend_wins'        => $all_defend_wins,
            'overall_defend_win_rate'  => $overall_defend_win_rate,
            'overall_win_rate'         => $overall_win_rate,
            'avg_battles_per_season'   => $avg_battles_per_season,
            'performance_grade'        => $performance_grade,
            'best_season'              => $best_season ?? ($seasons_played > 0 ? $seasons->first()->num : 0),
            'best_season_win_rate'     => $best_win_rate >= 0 ? $best_win_rate : 0,
            'worst_season'             => $worst_season ?? ($seasons_played > 0 ? $seasons->first()->num : 0),
            'worst_season_win_rate'    => $worst_win_rate <= 100 ? $worst_win_rate : 0,
        ];

        return (object)[
            'status'   => 1,
            'overall'  => $overall,
            'seasonal' => $seasonal,
        ];
    }

    /**
     * Map overall win rate to a letter grade for ArenaStatistic display.
     */
    private function calculate_grade(int $win_rate): string
    {
        return match (true) {
            $win_rate >= 95 => 'S+',
            $win_rate >= 85 => 'S',
            $win_rate >= 75 => 'A+',
            $win_rate >= 65 => 'A',
            $win_rate >= 55 => 'B+',
            $win_rate >= 45 => 'B',
            $win_rate >= 35 => 'C+',
            default         => 'C',
        };
    }

    // ── getEnemies ───────────────────────────────────────────────────
    // Response is stored as this.battleData
    // Client accesses: .enemies[] with .id, .squad, .rank, .set (weapon, clothing, etc)
    private function get_enemies(array $params): object
    {
        [$char_id, $session_key] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $player = $this->get_or_create_player($char_id, $season->id);

        // Check for cached enemies first
        $cache = ShadowWarEnemyCache::where('character_id', $char_id)
            ->where('season_id', $season->id)
            ->first();

        if ($cache && !empty($cache->enemies)) {
            return (object)[
                'status'  => 1,
                'enemies' => $this->build_enemies_response($cache->enemies, $season->id),
            ];
        }

        // Generate new enemy list
        $enemies = $this->generate_enemies($char_id, $season->id, $player->trophy);

        ShadowWarEnemyCache::updateOrCreate(
            ['character_id' => $char_id, 'season_id' => $season->id],
            ['enemies' => $enemies]
        );

        return (object)[
            'status'  => 1,
            'enemies' => $this->build_enemies_response($enemies, $season->id),
        ];
    }

    private function generate_enemies(int $char_id, int $season_id, int $trophy): array
    {
        $range = max(200, (int)($trophy * 0.3));
        $low   = max(0, $trophy - $range);
        $high  = $trophy + $range;

        $candidates = ShadowWarPlayer::where('season_id', $season_id)
            ->where('character_id', '!=', $char_id)
            ->whereBetween('trophy', [$low, $high])
            ->inRandomOrder()
            ->limit(self::ENEMIES_PER_LIST)
            ->pluck('character_id')
            ->toArray();

        // Fallback: if not enough, grab any other players
        if (count($candidates) < self::ENEMIES_PER_LIST) {
            $fill = ShadowWarPlayer::where('season_id', $season_id)
                ->where('character_id', '!=', $char_id)
                ->whereNotIn('character_id', $candidates)
                ->inRandomOrder()
                ->limit(self::ENEMIES_PER_LIST - count($candidates))
                ->pluck('character_id')
                ->toArray();

            $candidates = array_merge($candidates, $fill);
        }

        return $candidates;
    }

    /**
     * Build the enemies array for client display.
     * Each enemy needs: id, name, level, squad, rank, trophy, squad_name, league_name, set {}
     * Batch-loads all characters, players, and presets to avoid N+1 queries.
     */
    private function build_enemies_response(array $enemy_ids, int $season_id): array
    {
        if (empty($enemy_ids)) {
            return [];
        }

        // Batch-load all data in 3 queries instead of 3*N
        $characters = Character::whereIn('id', $enemy_ids)->get()->keyBy('id');
        $players    = ShadowWarPlayer::where('season_id', $season_id)
            ->whereIn('character_id', $enemy_ids)->get()->keyBy('character_id');
        $presets    = ShadowWarPreset::whereIn('character_id', $enemy_ids)
            ->where('is_active', true)->get()->keyBy('character_id');

        $result = [];
        foreach ($enemy_ids as $eid) {
            $char   = $characters->get($eid);
            $player = $players->get($eid);

            if (!$char || !$player) {
                continue;
            }

            // Apply active preset override
            $preset = $presets->get($eid);
            if ($preset) {
                $this->apply_preset_to_char($char, $preset);
            }

            $result[] = (object)[
                'id'          => $char->id,
                'name'        => $char->name,
                'level'       => $char->level,
                'trophy'      => $player->trophy,
                'rank'        => $player->rank,
                'squad'       => $player->squad,
                'squad_name'  => self::SQUAD_NAMES[$player->squad] ?? 'assault',
                'league_name' => self::LEAGUE_NAMES[$player->rank] ?? 'Bronze',
                'set'         => $this->build_enemy_set($char),
            ];
        }
        return $result;
    }

    /**
     * Apply a ShadowWarPreset to a Character model (in-memory, no save).
     */
    private function apply_preset_to_char(Character $char, ShadowWarPreset $preset): void
    {
        $char->equipment_weapon    = $preset->weapon    ?? $char->equipment_weapon;
        $char->equipment_clothing  = $preset->clothing  ?? $char->equipment_clothing;
        $char->hair_style          = $preset->hair      ?? $char->hair_style;
        $char->equipment_back      = $preset->back_item ?? $char->equipment_back;
        $char->equipment_accessory = $preset->accessory ?? $char->equipment_accessory;
        $char->hair_color          = $preset->hair_color ?? $char->hair_color;
        $char->skin_color          = $preset->skin_color ?? $char->skin_color;
        if ($preset->skills) {
            $char->equipment_skills = $preset->skills;
        }
    }

    // ── getEnemyInfo ─────────────────────────────────────────────────
    // Called during battle loading (CharacterModel constructor)
    private function get_enemy_info(array $params): object
    {
        [$char_id, $session_key, $target_id] = $params;

        if (is_string($target_id) && str_starts_with($target_id, 'char_')) {
            $target_id = str_replace('char_', '', $target_id);
        }

        // Eager-load relationships needed by build_character_data
        $char = Character::with(['talent_skills', 'senjutsu_skills', 'pets'])
            ->find($target_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Enemy not found'];
        }

        // Apply preset if active
        $preset = ShadowWarPreset::where('character_id', $target_id)
            ->where('is_active', true)
            ->first();

        if ($preset) {
            $this->apply_preset_to_char($char, $preset);
        }

        $data = $this->build_character_data($char);

        return (object)array_merge((array)$data, [
            'status' => 1,
            'error'  => 0,
        ]);
    }

    // ── refreshEnemies ───────────────────────────────────────────────
    // Costs 40 tokens
    private function refresh_enemies(array $params): object
    {
        [$char_id, $session_key] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $player = $this->get_or_create_player($char_id, $season->id);

        $user = $char->user;
        if (!$user || $user->tokens < self::REFRESH_COST) {
            return (object)['status' => 2, 'result' => 'Not enough tokens'];
        }

        $user->tokens -= self::REFRESH_COST;
        $user->save();

        // Regenerate enemy list
        $enemies = $this->generate_enemies($char_id, $season->id, $player->trophy);

        ShadowWarEnemyCache::updateOrCreate(
            ['character_id' => $char_id, 'season_id' => $season->id],
            ['enemies' => $enemies]
        );

        return (object)[
            'status'  => 1,
            'result'  => 'Enemies refreshed',
            'enemies' => $this->build_enemies_response($enemies, $season->id),
            'tokens'  => $user->tokens,
        ];
    }

    // ── refillEnergy ─────────────────────────────────────────────────
    // Costs 50 tokens
    private function refill_energy(array $params): object
    {
        [$char_id, $session_key] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $user = $char->user;
        if (!$user || $user->tokens < self::ENERGY_REFILL_COST) {
            return (object)['status' => 2, 'result' => 'Not enough tokens'];
        }

        $player = $this->get_or_create_player($char_id, $season->id);

        $user->tokens -= self::ENERGY_REFILL_COST;
        $user->save();

        $player->energy = self::MAX_ENERGY;
        $player->save();

        return (object)[
            'status' => 1,
            'energy' => $player->energy,
            'tokens' => $user->tokens,
        ];
    }

    // ── startBattle ──────────────────────────────────────────────────
    // Client stores param1.id as Character.battle_code
    private function start_battle(array $params): object
    {
        [$char_id, $session_key, $enemy_id] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $player = $this->get_or_create_player($char_id, $season->id);

        if ($player->energy < self::ENERGY_PER_BATTLE) {
            return (object)['status' => 2, 'result' => 'Not enough energy'];
        }

        $player->energy -= self::ENERGY_PER_BATTLE;
        $player->save();

        $battle_code = Str::random(16);

        ShadowWarBattle::create([
            'battle_code' => $battle_code,
            'attacker_id' => $char_id,
            'defender_id' => $enemy_id,
            'season_id'   => $season->id,
        ]);

        // Cache for fast lookup during finishBattle
        Cache::put("sw_battle_{$char_id}", [
            'battle_code' => $battle_code,
            'enemy_id'    => $enemy_id,
            'season_id'   => $season->id,
        ], 1800);

        return (object)[
            'status'   => 1,
            'id'       => $battle_code,   // Client reads param1.id
            'energy'   => $player->energy,
            'enemy_id' => $enemy_id,
        ];
    }

    // ── finishBattle ─────────────────────────────────────────────────
    // Called from Battle.as: [char_id, sessionkey, battle_code, totalDamage, battleData, hash]
    // Client checks for status==1, then reads trophies_got (for ShadowWarReward)
    private function finish_battle(array $params): object
    {
        [$char_id, $session_key, $battle_code, $total_damage, $battle_data, $hash] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $battle = ShadowWarBattle::where('battle_code', $battle_code)
            ->where('attacker_id', $char_id)
            ->where('is_finished', false)
            ->first();

        if (!$battle) {
            return (object)['status' => 0, 'error' => 'Battle not found or already finished'];
        }

        $player  = $this->get_or_create_player($char_id, $season->id);
        $enemy_p = ShadowWarPlayer::where('character_id', $battle->defender_id)
            ->where('season_id', $season->id)
            ->first();

        $trophies_won = self::BASE_TROPHY_WIN;

        // Rank difference bonus
        if ($enemy_p) {
            $rank_diff = $enemy_p->rank - $player->rank;
            if ($rank_diff > 0) {
                $trophies_won += $rank_diff * 5;
            }
        }

        DB::transaction(function () use ($player, $enemy_p, $battle, $trophies_won) {
            $player->trophy += $trophies_won;
            $player->rank    = $this->rank_from_trophy($player->trophy);
            $player->save();

            if ($enemy_p) {
                $loss = min($enemy_p->trophy, self::BASE_TROPHY_LOSE);
                $enemy_p->trophy -= $loss;
                $enemy_p->rank    = $this->rank_from_trophy($enemy_p->trophy);
                $enemy_p->save();
            }

            $battle->trophies_change = $trophies_won;
            $battle->is_finished     = true;
            $battle->save();
        });

        // Clear caches
        Cache::forget("sw_battle_{$char_id}");
        ShadowWarEnemyCache::where('character_id', $char_id)
            ->where('season_id', $season->id)
            ->delete();

        // XP reward
        $xp_reward   = 50;
        $gold_reward = 0;

        $char->xp += $xp_reward;
        $level_up = ExperienceHelper::checkCharacterLevelUp($char);
        $char->save();

        return (object)[
            'status'       => 1,
            'error'        => 0,
            'trophies_got' => $trophies_won,
            'trophy'       => $player->trophy,
            'win_trophy'   => $trophies_won,
            'rank'         => $player->rank,
            'result'       => [$xp_reward, $gold_reward, []],
            'level'        => $char->level,
            'xp'           => $char->xp,
            'level_up'     => $level_up,
        ];
    }

    // ── getPresets ────────────────────────────────────────────────────
    // ArenaPreset.as reads: param1.presets[] (always 4 items), param1.active
    // Each preset: .id, .name, .weapon, .clothing, .hair, .back_item, .accessory,
    //              .hair_color, .skin_color, .skills, .pet { .pet_swf, .pet_id }, .is_active
    private function get_presets(array $params): object
    {
        [$char_id, $session_key] = $params;

        $presets = ShadowWarPreset::where('character_id', $char_id)->get();

        // Client expects exactly 4 presets — auto-create if missing
        $default_names = ['Preset 1', 'Preset 2', 'Preset 3', 'Preset 4'];
        while ($presets->count() < 4) {
            $idx = $presets->count();
            $new_preset = ShadowWarPreset::create([
                'character_id' => $char_id,
                'name'         => $default_names[$idx] ?? 'Preset ' . ($idx + 1),
                'is_active'    => $idx === 0,
            ]);
            $presets->push($new_preset);
        }

        $active_id = null;
        $list = [];
        foreach ($presets as $p) {
            if ($p->is_active) {
                $active_id = $p->id;
            }

            $list[] = (object)[
                'id'          => $p->id,
                'name'        => $p->name,
                'weapon'      => $p->weapon,
                'clothing'    => $p->clothing,
                'hair'        => $p->hair,
                'back_item'   => $p->back_item,
                'accessory'   => $p->accessory,
                'hair_color'  => $p->hair_color,
                'skin_color'  => $p->skin_color,
                'skills'      => $p->skills,
                'pet'         => (object)[
                    'pet_swf' => $p->pet_swf,
                    'pet_id'  => $p->pet_id,
                ],
                'is_active'   => $p->is_active,
            ];
        }

        return (object)[
            'status'  => 1,
            'active'  => $active_id,
            'presets' => $list,
        ];
    }

    // ── usePreset ────────────────────────────────────────────────────
    private function use_preset(array $params): object
    {
        [$char_id, $session_key, $preset_id] = $params;

        $char = $this->find_character($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        // Deactivate all presets first
        ShadowWarPreset::where('character_id', $char_id)
            ->update(['is_active' => false]);

        if ($preset_id === 'current') {
            // Save current equipment as new active preset
            ShadowWarPreset::create([
                'character_id' => $char_id,
                'name'         => 'Current',
                'weapon'       => $char->equipment_weapon,
                'clothing'     => $char->equipment_clothing,
                'hair'         => $char->hair_style,
                'back_item'    => $char->equipment_back,
                'accessory'    => $char->equipment_accessory,
                'hair_color'   => $char->hair_color,
                'skin_color'   => $char->skin_color,
                'skills'       => $char->equipment_skills,
                'pet_swf'      => null,
                'pet_id'       => $char->equipped_pet_id,
                'is_active'    => true,
            ]);
        } else {
            $preset = ShadowWarPreset::where('id', $preset_id)
                ->where('character_id', $char_id)
                ->first();

            if (!$preset) {
                return (object)['status' => 0, 'error' => 'Preset not found'];
            }

            $preset->is_active = true;
            $preset->save();
        }

        return (object)[
            'status' => 1,
            'result' => 'Preset activated',
        ];
    }

    // ── globalLeaderboard ────────────────────────────────────────────
    // ArenaLeaderboard.as reads: .players[] with .id, .name, .trophy, .rank, .squad
    // Also reads: .squads[] (5 items) with .trophy, .squad
    private function global_leaderboard(array $params): object
    {
        [$char_id, $session_key] = $params;

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $top = ShadowWarPlayer::where('season_id', $season->id)
            ->orderByDesc('trophy')
            ->limit(50)
            ->get();

        // Batch-load all characters in 1 query instead of 50
        $char_ids   = $top->pluck('character_id')->toArray();
        $characters = Character::whereIn('id', $char_ids)->get()->keyBy('id');

        $players = [];
        foreach ($top as $p) {
            $char = $characters->get($p->character_id);
            if (!$char) {
                continue;
            }

            $players[] = (object)[
                'id'          => $char->id,
                'name'        => $char->name,
                'level'       => $char->level,
                'trophy'      => $p->trophy,
                'rank'        => $p->rank,
                'squad'       => $p->squad,
                'squad_name'  => self::SQUAD_NAMES[$p->squad] ?? 'assault',
                'league_name' => self::LEAGUE_NAMES[$p->rank] ?? 'Bronze',
            ];
        }

        // Own position
        $own = ShadowWarPlayer::where('season_id', $season->id)
            ->where('character_id', $char_id)
            ->first();

        $own_pos = 0;
        if ($own) {
            $own_pos = ShadowWarPlayer::where('season_id', $season->id)
                ->where('trophy', '>', $own->trophy)
                ->count() + 1;
        }

        // Squad rankings — client reads .squads[i].trophy and .squads[i].squad
        $squads_raw = $this->build_squads_ranking($season->id);
        $squads = [];
        foreach ($squads_raw as $s) {
            $squads[] = (object)[
                'squad'  => $s->squad,
                'trophy' => $s->total_trophy,
            ];
        }

        return (object)[
            'status'       => 1,
            'players'      => $players,
            'squads'       => $squads,
            'own_position' => $own_pos,
        ];
    }

    // ── squadLeaderboard ─────────────────────────────────────────────
    // Client accesses: .players[] with .id, .name, .trophy, .rank
    private function squad_leaderboard(array $params): object
    {
        [$char_id, $session_key, $squad_id] = $params;

        $season = $this->active_season();
        if (!$season) {
            return (object)['status' => 2, 'result' => 'No active season'];
        }

        $top = ShadowWarPlayer::where('season_id', $season->id)
            ->where('squad', $squad_id)
            ->orderByDesc('trophy')
            ->limit(50)
            ->get();

        // Batch-load all characters in 1 query instead of 50
        $char_ids   = $top->pluck('character_id')->toArray();
        $characters = Character::whereIn('id', $char_ids)->get()->keyBy('id');

        $players = [];
        foreach ($top as $p) {
            $char = $characters->get($p->character_id);
            if (!$char) {
                continue;
            }

            $players[] = (object)[
                'id'          => $char->id,
                'name'        => $char->name,
                'level'       => $char->level,
                'trophy'      => $p->trophy,
                'rank'        => $p->rank,
                'squad'       => $p->squad,
                'squad_name'  => self::SQUAD_NAMES[$p->squad] ?? 'assault',
                'league_name' => self::LEAGUE_NAMES[$p->rank] ?? 'Bronze',
            ];
        }

        return (object)[
            'status'  => 1,
            'players' => $players,
        ];
    }
}
