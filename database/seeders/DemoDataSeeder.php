<?php

namespace Database\Seeders;

use App\Models\EmailThread;
use App\Models\Mailbox;
use Illuminate\Database\Seeder;

/**
 * Dummy conversations so the inbox can be previewed before Gmail is connected.
 * Remove any time with: php artisan demo:clear  (or just delete threads whose
 * gmail_thread_id starts with "demo-").
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            '1Dollar Digitizing' => ['email' => 'support@1dollardigitizing.com', 'sig' => "Best regards,\n1Dollar Digitizing Team\nwww.1dollardigitizing.com"],
            'Digitizing Zone'       => ['email' => 'info@digitizingzone.com',         'sig' => "Thanks & regards,\nDigitizing Zone\nwww.digitizingzone.com"],
            'Aplus Digitizing'     => ['email' => 'orders@aplusdigitizing.com',      'sig' => "Kind regards,\nAplus Digitizing\nwww.aplusdigitizing.com"],
        ];
        $mb = [];
        foreach ($brands as $name => $info) {
            $m = Mailbox::where('brand_name', $name)->first();
            if ($m) {
                $m->forceFill(['email' => $m->email ?? $info['email'], 'signature' => $m->signature ?? $info['sig']])->save();
                $mb[$name] = $m;
            }
        }
        if (count($mb) < 3) {
            return;
        }

        $demos = [
            [
                'brand' => '1Dollar Digitizing', 'name' => 'Michael Brown', 'email' => 'michael.brown1984@gmail.com',
                'subject' => 'Logo digitizing quote needed', 'unread' => true, 'minutes_ago' => 25,
                'messages' => [
                    ['in', "Hi,\n\nI need my company logo digitized for left chest embroidery, around 3.5 inches wide. It has 4 colors and some small text.\n\nCan you tell me the price and turnaround time? I've attached the logo file.\n\nThanks,\nMichael Brown"],
                ],
                'attachments' => [['company-logo.png', 'image/png', 245760]],
            ],
            [
                'brand' => '1Dollar Digitizing', 'name' => 'Sarah Jenkins', 'email' => 'sarah.j.embroidery@gmail.com',
                'subject' => 'Left chest file in DST format', 'unread' => false, 'minutes_ago' => 180,
                'messages' => [
                    ['in', "Hello,\n\nCould you send the final file in DST format for my Tajima machine? Order #1092.\n\nSarah"],
                    ['out', "Hi Sarah,\n\nSure! Your DST file is ready â€” we'll email it within the next 30 minutes. Stitch count came to 8,450.\n\nLet us know if you need any adjustments."],
                ],
            ],
            [
                'brand' => '1Dollar Digitizing', 'name' => 'David Miller', 'email' => 'dmiller.workwear@outlook.com',
                'subject' => 'Revision: stitch density too high', 'unread' => true, 'minutes_ago' => 60 * 26,
                'messages' => [
                    ['in', "Hi team,\n\nThe design you sent is puckering on polo shirts â€” I think the density is too high in the fill areas. Can you reduce it and resend?\n\nAlso the small text is getting lost, maybe bump it up slightly.\n\nDavid"],
                ],
            ],
            [
                'brand' => 'Digitizing Zone', 'name' => 'Emma Wilson', 'email' => 'emma.wilson.caps@gmail.com',
                'subject' => 'Cap digitizing order #4521', 'unread' => true, 'minutes_ago' => 65,
                'messages' => [
                    ['in', "Hi,\n\nPlacing a new order for cap front digitizing â€” 2.25\" height, 3 colors. Design attached as PDF.\n\nNeed it within 12 hours if possible. Please confirm.\n\nEmma"],
                ],
                'attachments' => [['cap-design.pdf', 'application/pdf', 512000]],
            ],
            [
                'brand' => 'Digitizing Zone', 'name' => 'James Anderson', 'email' => 'janderson.sports@yahoo.com',
                'subject' => 'Payment confirmation needed', 'unread' => false, 'minutes_ago' => 60 * 49,
                'messages' => [
                    ['in', "Hello,\n\nI just sent the PayPal payment for invoice #DZ-889. Can you confirm you received it so we can start the order?\n\nJames"],
                    ['out', "Hi James,\n\nPayment received â€” thank you! Your order is now in the queue and the digitized files will be delivered by tomorrow morning.\n\nWe appreciate your business."],
                ],
            ],
            [
                'brand' => 'Digitizing Zone', 'name' => 'Sophia Garcia', 'email' => 'sophiagarcia.boutique@gmail.com',
                'subject' => 'Thank you â€” great work!', 'unread' => false, 'minutes_ago' => 60 * 96,
                'messages' => [
                    ['in', "Just wanted to say the jacket back design came out beautifully. The client loved it! Will definitely send more work your way.\n\nSophia"],
                    ['out', "Thank you so much, Sophia! It was a pleasure working on it. Looking forward to your next project."],
                ],
            ],
            [
                'brand' => 'Aplus Digitizing', 'name' => 'Olivia Martinez', 'email' => 'olivia.m.apparel@gmail.com',
                'subject' => '3D puff digitizing for hoodie', 'unread' => true, 'minutes_ago' => 10,
                'messages' => [
                    ['in', "Hi,\n\nDo you do 3D puff digitizing? I have a hoodie design â€” bold lettering, about 10 inches wide across the chest.\n\nWhat would that cost and how long would it take?\n\nOlivia"],
                ],
            ],
            [
                'brand' => '1Dollar Digitizing', 'name' => 'Carlos Rivera', 'email' => 'carlos.rivera.uniforms@hotmail.com',
                'subject' => 'Bulk order - 50 uniform logos', 'unread' => true, 'spam' => true, 'minutes_ago' => 60 * 5,
                'messages' => [
                    ['in', "Hello,\n\nWe run a uniform supply company and need 50 logos digitized monthly. Looking for a long-term partner with volume pricing.\n\nPlease send your rates and a sample of your work. This went to your spam maybe, so following up here.\n\nCarlos Rivera\nRivera Uniforms LLC"],
                ],
            ],
            [
                'brand' => 'Aplus Digitizing', 'name' => 'Liam Thompson', 'email' => 'liam.thompson.print@gmail.com',
                'subject' => 'Vector conversion + digitizing bundle', 'unread' => false, 'minutes_ago' => 300,
                'messages' => [
                    ['in', "Hey,\n\nI have 5 low-res logos that need vector conversion AND digitizing. Do you offer a bundle discount for that?\n\nLiam"],
                    ['out', "Hi Liam,\n\nYes â€” for 5+ designs we offer 15% off the combined price. Send the files over and we'll get you an exact quote within the hour."],
                    ['in', "Perfect, sending them now. Go ahead and start with the eagle logo first â€” that one's urgent."],
                ],
            ],
        ];

        $i = 0;
        foreach ($demos as $d) {
            $i++;
            $mailbox = $mb[$d['brand']];
            $threadKey = ['mailbox_id' => $mailbox->id, 'gmail_thread_id' => "demo-thread-$i"];
            if (EmailThread::where($threadKey)->exists()) {
                continue;
            }

            $count = count($d['messages']);
            $lastAt = now()->subMinutes($d['minutes_ago']);
            $lastBody = end($d['messages'])[1];

            $thread = EmailThread::create($threadKey + [
                'customer_name'   => $d['name'],
                'customer_email'  => $d['email'],
                'subject'         => $d['subject'],
                'snippet'         => mb_substr(preg_replace('/\s+/u', ' ', $lastBody), 0, 280),
                'last_message_at' => $lastAt,
                'unread'          => $d['unread'],
                'spam'            => $d['spam'] ?? false,
            ]);

            $admin = \App\Models\User::where('role', 'admin')->orderBy('id')->first();
            foreach ($d['messages'] as $j => [$dir, $body]) {
                // Space messages ~2h apart, ending at last_message_at.
                $sentAt = $lastAt->copy()->subMinutes(($count - 1 - $j) * 120);
                $msg = $thread->messages()->create([
                    'gmail_id'          => "demo-msg-$i-$j",
                    'direction'         => $dir,
                    'sender_name'       => $dir === 'in' ? $d['name'] : $mailbox->brand_name,
                    'sent_by_user_id'   => $dir === 'out' ? $admin?->id : null,
                    'body_text'         => $body . ($dir === 'out' && $mailbox->signature ? "\n\n" . $mailbox->signature : ''),
                    'message_id_header' => "<demo-$i-$j@mail.gmail.com>",
                    'sent_at'           => $sentAt,
                ]);
                if ($j === 0) {
                    foreach ($d['attachments'] ?? [] as [$file, $mime, $size]) {
                        $msg->attachments()->create([
                            'gmail_message_id' => $msg->gmail_id,
                            'attachment_id'    => 'demo',
                            'filename'         => $file,
                            'mime_type'        => $mime,
                            'size'             => $size,
                        ]);
                    }
                }
            }
        }
    }
}


