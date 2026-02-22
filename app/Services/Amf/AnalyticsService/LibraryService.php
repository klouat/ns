<?php

namespace App\Services\Amf\AnalyticsService;

use Illuminate\Support\Facades\Log;

class LibraryService
{
    public function libraries(...$params)
    {
        Log::info('AnalyticsService::LibraryService::libraries called', ['params' => $params]);

        return (object)[
            'status' => 1,
        ];
    }
}
