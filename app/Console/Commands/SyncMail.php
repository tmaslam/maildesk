<?php

namespace App\Console\Commands;

use App\Models\Mailbox;
use App\Services\MailboxSyncer;
use Illuminate\Console\Command;

class SyncMail extends Command
{
    protected $signature = 'mail:sync';

    protected $description = 'Pull new Gmail messages for every connected mailbox';

    public function handle(MailboxSyncer $syncer): int
    {
        $total = 0;
        foreach (Mailbox::where('active', true)->whereNotNull('refresh_token')->get() as $mb) {
            try {
                $new = $syncer->sync($mb);
                $total += $new;
                $this->info("{$mb->brand_name}: {$new} new");
            } catch (\Throwable $e) {
                $this->error("{$mb->brand_name}: " . $e->getMessage());
            }

            // Keep Gmail real-time push registered (expires every ~7 days).
            if (!$mb->watch_expires_at || $mb->watch_expires_at->lt(now()->addDay())) {
                try {
                    \App\Services\GmailClient::watch($mb);
                    $this->info("{$mb->brand_name}: push watch renewed");
                } catch (\Throwable $e) {
                    // Topic not set up yet or no permission — polling still covers us.
                }
            }
        }
        $this->info("Total new messages: {$total}");
        return self::SUCCESS;
    }
}
