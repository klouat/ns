<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\Mail;
use App\Models\Friend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MailService
{
    public function executeService($command, $params)
    {
        return match ($command) {
            'getMails' => $this->getMails(...$params),
            'openMail' => $this->openMail(...$params),
            'deleteMail' => $this->deleteMail(...$params),
            'claimReward' => $this->claimReward(...$params),
            'claimAllRewards' => $this->claimAllRewards(...$params),
            'deleteAllMails' => $this->deleteAllMails(...$params),
            'acceptFriendRequest' => $this->acceptFriendRequest(...$params),
            // 'acceptInvitation' => $this->acceptInvitation(...$params),
            default => (object)['status' => 0, 'error' => "Command $command not implemented"]
        };
    }

    private function getMails($charId, $sessionKey)
    {
        $mails = Mail::where('character_id', $charId)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];
        foreach ($mails as $mail) {
            $data[] = (object)[
                'mail_id' => $mail->id,
                'mail_title' => $mail->title,
                'mail_sender' => $mail->sender_name ?? 'System',
                'sent_date' => $mail->created_at->format('Y-m-d H:i'),
                'mail_viewed' => $mail->is_viewed ? 1 : 0,
                'mail_body' => $mail->body,
                'mail_type' => $mail->type,
                'mail_rewards' => $mail->rewards ?? '',
                'mail_claimed' => $mail->is_claimed ? 1 : 0
            ];
        }

        return (object)[
            'status' => 1,
            'mails' => $data
        ];
    }

    private function openMail($charId, $sessionKey, $mailId)
    {
        Mail::where('id', $mailId)
            ->where('character_id', $charId)
            ->update(['is_viewed' => true]);

        return (object)['status' => 1];
    }

    private function deleteMail($charId, $sessionKey, $mailId)
    {
        Mail::where('id', $mailId)
            ->where('character_id', $charId)
            ->delete();

        return (object)['status' => 1, 'result' => 'Mail has been deleted!'];
    }

    private function claimReward($charId, $sessionKey, $mailId)
    {
        try {
            return DB::transaction(function () use ($charId, $mailId) {
                $mail = Mail::where('id', $mailId)
                    ->where('character_id', $charId)
                    ->lockForUpdate()
                    ->first();

                if (!$mail) return (object)['status' => 0, 'error' => 'Mail not found'];
                if ($mail->is_claimed) return (object)['status' => 2, 'result' => 'Reward already claimed'];
                if (empty($mail->rewards)) return (object)['status' => 2, 'result' => 'No rewards in this mail'];

                $mail->is_claimed = true;
                $mail->save();

                // Logic for adding rewards to character would go here.
                // For now, return the rewards string as expected by the client.
                return (object)[
                    'status' => 1,
                    'rewards' => $mail->rewards
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function claimAllRewards($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $mails = Mail::where('character_id', $charId)
                    ->whereNotNull('rewards')
                    ->where('is_claimed', false)
                    ->lockForUpdate()
                    ->get();

                if ($mails->isEmpty()) {
                    return (object)['status' => 2, 'result' => 'No rewards to claim'];
                }

                $allRewards = [];
                foreach ($mails as $mail) {
                    $allRewards[] = $mail->rewards;
                    $mail->is_claimed = true;
                    $mail->save();
                }

                $rewardsString = implode(',', $allRewards);

                return (object)[
                    'status' => 1,
                    'rewards' => $rewardsString,
                    'result' => 'All rewards claimed!'
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function deleteAllMails($charId, $sessionKey)
    {
        Mail::where('character_id', $charId)->delete();
        return (object)['status' => 1, 'result' => 'All mails deleted!'];
    }

    private function acceptFriendRequest($charId, $sessionKey, $mailId)
    {
        try {
            return DB::transaction(function () use ($charId, $mailId) {
                $mail = Mail::where('id', $mailId)
                    ->where('character_id', $charId)
                    ->where('type', 2) // Friend Request
                    ->first();

                if (!$mail) return (object)['status' => 2, 'result' => 'Request not found'];

                // Assuming mail body contains sender character ID or we can extract it
                // This is a simplified implementation
                
                $mail->delete();

                return (object)[
                    'status' => 1,
                    'result' => 'Friend request accepted!'
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
