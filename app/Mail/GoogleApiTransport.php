<?php

namespace App\Mail;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GoogleApiTransport extends AbstractTransport
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        $refreshToken = config('services.google.refresh_token');

        if (! $refreshToken) {
            throw new RuntimeException('Google refresh token is not configured.');
        }

        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($token['error'])) {
            throw new RuntimeException(
                'Google authentication failed: '.($token['error_description'] ?? $token['error'])
            );
        }

        $client->setAccessToken($token);

        $gmail = new Gmail($client);

        $rawMessage = base64_encode(
            $message->getOriginalMessage()->toString()
        );

        $rawMessage = strtr($rawMessage, '+/', '-_');
        $rawMessage = rtrim($rawMessage, '=');

        $gmailMessage = new GmailMessage();
        $gmailMessage->setRaw($rawMessage);

        $gmail->users_messages->send('me', $gmailMessage);
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
