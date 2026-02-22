<?php

namespace App\Services\Amf;

class EventsService
{
    public function get($params = null)
    {
        return (object)[
            'status' => 1,
            'error' => 0,
            'events' => (object)[
                'seasonal' => [
                    (object)[
                        'name' => 'Yuki Onna: Eternal Winter',
                        'desc' => 'Fight back the blizzard and stop the Eternal Winter before the Christmas Star fades away.',
                        'date' => '25/12 - 25/03, 2026',
                        'img' => 'https://images4.alphacoders.com/112/thumb-1920-1127325.jpg',
                        'panel' => 'ChristmasMenu'
                    ],
                    (object)[
                        'name' => 'Feast of Gratitude: Celebration',
                        'desc' => 'Ninjas must compete, collect, and defend the feast to ensure every villager enjoys the celebration.',
                        'date' => '04/12 - 04/03, 2026',
                        'img' => 'https://ns-assets.ninjasage.id/tmp/thanksgiving2025.jpg',
                        'panel' => 'FeastOfGratitudeMenu'
                    ],
                    (object)[
                        'name' => 'Confronting Death Event 2025',
                        'desc' => 'Lord of the Underworld is the ruler of the underworld. He is the one who controls the dead and the living. He is the one who controls the fate of the world.',
                        'date' => '04/11 - 04/02, 2026',
                        'img' => 'https://ns-assets.ninjasage.id/tmp/confrontingdeath2025.png',
                        'panel' => 'ConfrontingDeathMenu'
                    ],
                    (object)[
                        'name' => 'Confronting Death Event 2025',
                        'desc' => 'Lord of the Underworld is the ruler of the underworld. He is the one who controls the dead and the living. He is the one who controls the fate of the world.',
                        'date' => '04/11 - 04/02, 2026',
                        'img' => 'https://ns-assets.ninjasage.id/tmp/confrontingdeath2025.png',
                        'panel' => 'ConfrontingDeathMenu'
                    ],
                ],
                'event:permanent' => [
                    (object)[
                        'name' => 'Monster Hunter',
                        'icon' => 'monsterhunter',
                        'panel' => 'MonsterHunter'
                    ],
                    (object)[
                        'name' => 'Dragon Hunt',
                        'icon' => 'dragonhunt',
                        'panel' => 'DragonHunt'
                    ],
                    (object)[
                        'name' => 'Justice Badge',
                        'icon' => 'justicebadge',
                        'panel' => 'JusticeBadge'
                    ]
                ],
                'features' => [
                    (object)[
                        'name' => 'Giveaway Center',
                        'icon' => 'giveaway',
                        'panel' => 'GiveawayCenter'
                    ],
                    (object)[
                        'name' => 'Leaderboard',
                        'icon' => 'leaderboard',
                        'panel' => 'Leaderboard'
                    ],
                    (object)[
                        'name' => 'Tailed Beast',
                        'icon' => 'tailedbeast',
                        'panel' => 'TailedBeast',
                        'inside' => true
                    ],
                    (object)[
                        'name' => 'Daily Gacha',
                        'icon' => 'dailygacha',
                        'panel' => 'DailyGacha'
                    ],
                    (object)[
                        'name' => 'Dragon Gacha',
                        'icon' => 'dragongacha',
                        'panel' => 'DragonGacha'
                    ],
                    (object)[
                        'name' => 'Exotic Package',
                        'icon' => 'exotic',
                        'panel' => 'ExoticPackage'
                    ]
                ],
                'packages' => (object)[
                    'name' => 'Elemental Ars Package',
                    'date' => '15/04 - 05/03, 2026',
                    'content' => [
                        (object)[
                            'name' => 'Codex Elementia',
                            'price' => 'IDR. 100,000',
                            'outfits' => new \stdClass(),
                            'ani' => [null],
                            'pet' => [null],
                            'set' => ['set_2402_%s'],
                            'back' => ['back_2396'],
                            'hair' => ['hair_2361_%s'],
                            'skill' => [null],
                            'weapon' => [null],
                            'accessory' => [null],
                            'rewards' => [
                                'hair_2361_%s',
                                'back_2396',
                                'set_2402_%s',
                                'emblem',
                                'tokens_4500'
                            ]
                        ],
                        (object)[
                            'name' => 'Grimoire Arcanum',
                            'price' => 'IDR. 250,000',
                            'outfits' => new \stdClass(),
                            'ani' => [null],
                            'pet' => [null],
                            'set' => ['set_2402_%s', 'set_2401_%s'],
                            'back' => ['back_2396'],
                            'hair' => ['hair_2361_%s'],
                            'skill' => ['skill_2323'],
                            'weapon' => [null],
                            'accessory' => [null],
                            'rewards' => [
                                'hair_2361_%s',
                                'set_2402_%s',
                                'set_2401_%s',
                                'back_2396',
                                'skill_2323',
                                'emblem',
                                'tokens_10250'
                            ]
                        ],
                        (object)[
                            'name' => 'Elementis Corcondia',
                            'price' => 'IDR. 500,000',
                            'outfits' => new \stdClass(),
                            'ani' => [null],
                            'pet' => ['pet_ancientgolem'],
                            'set' => ['set_2402_%s', 'set_2401_%s'],
                            'back' => ['back_2396'],
                            'hair' => ['hair_2361_%s'],
                            'skill' => ['skill_2323', 'skill_2324'],
                            'weapon' => ['wpn_2399'],
                            'accessory' => [null],
                            'rewards' => [
                                'hair_2361_%s',
                                'set_2402_%s',
                                'set_2401_%s',
                                'back_2396',
                                'wpn_2399',
                                'pet_ancientgolem',
                                'skill_2323',
                                'skill_2324',
                                'emblem',
                                'tokens_21000'
                            ]
                        ],
                        (object)[
                            'name' => 'Tomea Astralis',
                            'price' => 'IDR. 1,000,000',
                            'outfits' => new \stdClass(),
                            'ani' => [null],
                            'pet' => ['pet_ancientgolem'],
                            'set' => ['set_2402_%s', 'set_2401_%s'],
                            'back' => ['back_2396'],
                            'hair' => ['hair_2361_%s'],
                            'skill' => ['skill_2323', 'skill_2324', 'skill_2325'],
                            'weapon' => ['wpn_2399'],
                            'accessory' => [null],
                            'rewards' => [
                                'hair_2361_%s',
                                'set_2402_%s',
                                'set_2401_%s',
                                'back_2396',
                                'wpn_2399',
                                'pet_ancientgolem',
                                'skill_2323',
                                'skill_2324',
                                'skill_2325',
                                'emblem',
                                'tokens_45000'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
