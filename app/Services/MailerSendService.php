<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

class MailerSendService
{
    public function enviarCorreo($to, $subject, $data, $templateId)
    {
        $response = Http::withToken(env('MAILERSEND_API_KEY'))
            ->post('https://api.mailersend.com/v1/email', [
                "from" => [
                    "email" => env('MAIL_FROM_ADDRESS')
                ],
                "to" => [
                    [
                        "email" => $to
                    ]
                ],
                "subject" => $subject,
                "personalization" => [
                    [
                        "email" => $to,
                        "data" => $data
                    ]
                ],
                "template_id" => $templateId
            ]);

        return $response->json();
    }
}