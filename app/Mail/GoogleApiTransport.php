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
        // Load Google OAuth credentials.
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $refreshToken = config('services.google.refresh_token');

        if (! $clientId || ! $clientSecret || ! $refreshToken) {
            throw new RuntimeException(
                'Google API configuration is incomplete. Check GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET and GOOGLE_REFRESH_TOKEN.'
            );
        }

        // Initialize Google API client.
        $client = new Client();

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);

        // Generate a new access token using the refresh token.
        $token = $client->fetchAccessTokenWithRefreshToken(
            $refreshToken
        );

        if (
            ! is_array($token) ||
            isset($token['error']) ||
            empty($token['access_token'])
        ) {
            $error = is_array($token)
                ? ($token['error_description'] ?? $token['error'] ?? 'No access token returned.')
                : 'Invalid Google authentication response.';

            throw new RuntimeException(
                'Google authentication failed: '.$error
            );
        }

        $client->setAccessToken($token);

        // Initialize Gmail API.
        $gmail = new Gmail($client);

        // Convert the email into URL-safe Base64.
        $rawMessage = base64_encode(
            $message->getOriginalMessage()->toString()
        );

        $rawMessage = strtr($rawMessage, '+/', '-_');
        $rawMessage = rtrim($rawMessage, '=');

        // Create Gmail message.
        $gmailMessage = new GmailMessage();
        $gmailMessage->setRaw($rawMessage);

        // Send the email through Gmail API.
        $gmail->users_messages->send(
            'me',
            $gmailMessage
        );
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
