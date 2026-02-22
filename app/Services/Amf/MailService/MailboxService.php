<?php

namespace App\Services\Amf\MailService;

use App\Models\Character;
use App\Models\Mail;
use App\Models\Friend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MailboxService
{
    public function getMails($charId, $sessionKey)
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

    public function openMail($charId, $sessionKey, $mailId)
    {
        Mail::where('id', $mailId)
            ->where('character_id', $charId)
            ->update(['is_viewed' => true]);

        return (object)['status' => 1];
    }

    public function deleteMail($charId, $sessionKey, $mailId)
    {
        Mail::where('id', $mailId)
            ->where('character_id', $charId)
            ->delete();

        return (object)['status' => 1, 'result' => 'Mail has been deleted!'];
    }

    public function claimReward($charId, $sessionKey, $mailId)
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

    public function claimAllRewards($charId, $sessionKey)
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

    public function deleteAllMails($charId, $sessionKey)
    {
        Mail::where('character_id', $charId)->delete();
        return (object)['status' => 1, 'result' => 'All mails deleted!'];
    }

    public function acceptFriendRequest($charId, $sessionKey, $mailId)
    {
        try {
            return DB::transaction(function () use ($charId, $mailId) {
                $mail = Mail::where('id', $mailId)
                    ->where('character_id', $charId)
                    ->where('type', 2)
                    ->first();

                if (!$mail) return (object)['status' => 2, 'result' => 'Request not found'];
                
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
