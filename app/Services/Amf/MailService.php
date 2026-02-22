<?php

namespace App\Services\Amf;

use App\Services\Amf\MailService\MailboxService;

class MailService
{
    private MailboxService $mailboxService;

    public function __construct()
    {
        $this->mailboxService = new MailboxService();
    }

    public function executeService($command, $params)
    {
        return match ($command) {
            'getMails' => $this->mailboxService->getMails(...$params),
            'openMail' => $this->mailboxService->openMail(...$params),
            'deleteMail' => $this->mailboxService->deleteMail(...$params),
            'claimReward' => $this->mailboxService->claimReward(...$params),
            'claimAllRewards' => $this->mailboxService->claimAllRewards(...$params),
            'deleteAllMails' => $this->mailboxService->deleteAllMails(...$params),
            'acceptFriendRequest' => $this->mailboxService->acceptFriendRequest(...$params),
            default => (object)['status' => 0, 'error' => "Command $command not implemented"]
        };
    }
}
