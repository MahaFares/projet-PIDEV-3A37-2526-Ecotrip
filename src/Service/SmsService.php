<?php

namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private Client $client;
    private string $from;

    public function __construct(string $sid, string $token, string $from)
    {
        $this->client = new Client($sid, $token);
        $this->from = $from;
    }

    public function send(string $to, string $message): void
    {
        if (trim($to) === '' || trim($message) === '') {
            return;
        }

        $this->client->messages->create($to, [
            'from' => $this->from,
            'body' => $message,
        ]);
    }
}

