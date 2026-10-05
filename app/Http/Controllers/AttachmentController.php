<?php

namespace App\Http\Controllers;

use App\Models\EmailAttachment;
use App\Services\GmailClient;

class AttachmentController extends Controller
{
    public function download(EmailAttachment $attachment)
    {
        $mailbox = $attachment->message->thread->mailbox;
        abort_unless($mailbox->isConnected(), 503, 'This brand mailbox is not connected to Gmail right now.');

        try {
            $res = GmailClient::getAttachment($mailbox, $attachment->gmail_message_id, $attachment->attachment_id);
        } catch (\Throwable $e) {
            abort(503, 'Could not fetch the attachment from Gmail — try reconnecting the mailbox.');
        }
        $data = GmailClient::b64urlDecode($res['data'] ?? '');

        return response($data, 200, [
            'Content-Type'        => $attachment->mime_type,
            'Content-Disposition' => 'attachment; filename="' . str_replace('"', '', $attachment->filename) . '"',
            'Content-Length'      => strlen($data),
        ]);
    }
}
