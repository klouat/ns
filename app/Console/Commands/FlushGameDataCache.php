<?php

namespace App\Console\Commands;

use App\Helpers\GameDataHelper;
use Illuminate\Console\Command;

class FlushGameDataCache extends Command
{
    protected $signature   = 'gamedata:flush';
    protected $description = 'Flush all cached game data (library, skills, items, gamedata, missions)';

    public function handle(): int
    {
        GameDataHelper::flush();
        $this->info('All game data caches have been flushed.');
        return 0;
    }
}
