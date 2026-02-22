<?php

namespace App\Services\Amf;

use App\Services\Amf\AnalyticsService\LibraryService;

class AnalyticsService
{
    private LibraryService $libraryService;

    public function __construct()
    {
        $this->libraryService = new LibraryService();
    }

    public function libraries(...$params)
    {
        return $this->libraryService->libraries(...$params);
    }
}
