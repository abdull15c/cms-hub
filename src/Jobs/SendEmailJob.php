<?php
namespace Src\Jobs;
use Src\Services\MailService;

class SendEmailJob {
    public function handle($data) {
        // $data = ['to' => ..., 'subject' => ..., 'body' => ...]
        // Use native MailService but purely backend
        $ms = new MailService();
        // Since templates are handled before pushing to queue usually, we assume raw body here
        // OR we can reconstruct template. Let's assume raw body for simplicity
        if(isset($data['template'])) {
            $ms->sendTemplate($data['to'], $data['subject'], $data['template'], $data['vars']);
        } else {
            $ms->send($data['to'], $data['subject'], $data['body']);
        }
    }
}