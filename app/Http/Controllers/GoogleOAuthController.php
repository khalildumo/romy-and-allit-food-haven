<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleOAuthController extends Controller
{
    private function googleClient(): Client
    {
        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->setScopes([Gmail::GMAIL_SEND]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);

        $request->session()->put('google_oauth_state', $state);

        $client = $this->googleClient();
        $client->setState($state);

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request): Response
    {
        $state = $request->session()->pull('google_oauth_state');

        if (! $state || ! hash_equals($state, (string) $request->query('state'))) {
            abort(403, 'Invalid Google OAuth state.');
        }

        if ($request->has('error')) {
            abort(400, 'Google authorization was cancelled or denied.');
        }

        $code = $request->query('code');

        if (! $code) {
            abort(400, 'Google authorization code is missing.');
        }

        $client = $this->googleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException(
                'Google authorization failed: '.($token['error_description'] ?? $token['error'])
            );
        }

        $refreshToken = $token['refresh_token'] ?? null;

        if (! $refreshToken) {
            throw new RuntimeException(
                'Google did not return a refresh token. Please authorize again.'
            );
        }

        return response()->view('google.oauth-token', [
            'refreshToken' => $refreshToken,
        ]);
    }
}
