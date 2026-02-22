<?php

namespace App\Services\Amf;

use App\Services\Amf\EventsService\GetService;

class EventsService
{
    private GetService $getService;

    public function __construct()
    {
        $this->getService = new GetService();
    }

    public function get($params = null)
    {
        return $this->getService->get($params);
    }
}
