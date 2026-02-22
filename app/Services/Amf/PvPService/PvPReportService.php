<?php

namespace App\Services\Amf\PvPService;

use Illuminate\Support\Facades\Log;

class PvPReportService
{
    public function reportBug($char_id, $session_key, $title, $description)
    {
        Log::info('PvP Bug Report', [
            'char_id' => $char_id,
            'title' => $title,
            'desc' => $description
        ]);
        
        return (object)[
            'status' => 1,
            'result' => 'Report received.'
        ];
    }
}
