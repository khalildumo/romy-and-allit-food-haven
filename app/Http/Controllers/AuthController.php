<?php

namespace App\Http\Controllers;

use App\Models\EmailVerificationCode;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            $admin = User::query()
                ->where('username', $credentials['username'])
                ->where('role', 'admin')
                ->first();

            if ($admin) {
                $this->recordAdminLogin($request, $admin, 'failed');
            }

            return back()
                ->withErrors([
                    'username' => 'Invalid username or password.',
                ])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $user = $request->user();

        // Require email verification.
        if (! $user->email_verified && ! empty($user->email)) {
            $userId = $user->id;

            Auth::logout();

            $request->session()->put(
                'pending_email_verification_user_id',
                $userId
            );

            return redirect()->route('verification.notice');
        }

        // Record successful administrator login.
        if ($user->role === 'admin') {
            $this->recordAdminLogin($request, $user, 'success');
        }

        // Redirect according to account role.
        return redirect()->intended(match ($user->role) {
            'admin' => route('admin.dashboard'),
            'cashier' => route('cashier.dashboard'),
            default => route('home'),
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN LOGIN HISTORY
    |--------------------------------------------------------------------------
    */

    private function recordAdminLogin(
        Request $request,
        User $user,
        string $status
    ): void {
        try {
            $agent = $request->userAgent() ?? '';

            // Detect device.
            $device = preg_match('/tablet|ipad/i', $agent)
                ? 'Tablet'
                : (preg_match('/mobile|android|iphone/i', $agent)
                    ? 'Mobile'
                    : 'Desktop');

            // Detect browser.
            $browser = match (true) {
                preg_match('/Edg\//i', $agent) === 1 => 'Microsoft Edge',
                preg_match('/OPR\//i', $agent) === 1 => 'Opera',
                preg_match('/Firefox\//i', $agent) === 1 => 'Mozilla Firefox',
                preg_match('/Chrome\//i', $agent) === 1 => 'Google Chrome',
                preg_match('/Safari\//i', $agent) === 1 => 'Safari',
                default => 'Unknown',
            };

            // Detect operating system.
            $operatingSystem = match (true) {
                preg_match('/Windows/i', $agent) === 1 => 'Windows',
                preg_match('/Android/i', $agent) === 1 => 'Android',
                preg_match('/iPhone|iPad|iPod/i', $agent) === 1 => 'iOS',
                preg_match('/Mac OS X/i', $agent) === 1 => 'macOS',
                preg_match('/Linux/i', $agent) === 1 => 'Linux',
                default => 'Unknown',
            };

            LoginHistory::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $request->ip(),
                'user_agent' => $agent,
                'device_type' => $device,
                'browser' => $browser,
                'operating_system' => $operatingSystem,
                'status' => $status,
                'logged_in_at' => now(),
            ]);

        } catch (Throwable $exception) {
            Log::warning('Admin login history could not be recorded.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION
    |--------------------------------------------------------------------------
    */

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],

            'username' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9_.-]+$/',
                'unique:users,username',
            ],

            'institution_id' => [
                'required',
                'string',
                'max:80',
            ],

            'email' => [
                'required',
                'email',
                'max:160',
                'unique:users,email',
            ],

            'phone' => [
                'required',
                'string',
                'max:40',
            ],

            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8),
            ],
        ]);

        // Create user and verification code in one transaction.
        [$user, $code] = DB::transaction(
            function () use ($validated): array {

                $user = User::query()->create([
                    'full_name' => $validated['full_name'],
                    'username' => $validated['username'],
                    'institution_id' => $validated['institution_id'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password_hash' => $validated['password'],
                    'role' => 'customer',
                    'email_verified' => false,
                ]);

                $code = (string) random_int(100000, 999999);

                EmailVerificationCode::query()->create([
                    'user_id' => $user->id,
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                ]);

                return [$user, $code];
            }
        );

        $request->session()->put(
            'pending_email_verification_user_id',
            $user->id
        );

        // Send verification email after the database transaction.
        $sent = $this->sendVerificationCode($user, $code);

        return redirect()
            ->route('verification.notice')
            ->with(
                $sent ? 'notice' : 'error',
                $sent
                    ? 'A verification code was sent to your email.'
                    : 'Your account was created, but the verification email could not be sent. Please request another code.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL VERIFICATION PAGE
    |--------------------------------------------------------------------------
    */

    public function showVerification(
        Request $request
    ): View|RedirectResponse {

        $user = User::query()->find(
            $request->session()->get(
                'pending_email_verification_user_id'
            )
        );

        if (! $user) {
            return redirect()->route('register');
        }

        if ($user->email_verified) {
            $request->session()->forget(
                'pending_email_verification_user_id'
            );

            return redirect()->route('login');
        }

        return view('auth.verify-email', compact('user'));
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY EMAIL OTP
    |--------------------------------------------------------------------------
    */

    public function verifyEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()->find(
            $request->session()->get(
                'pending_email_verification_user_id'
            )
        );

        if (! $user) {
            return redirect()->route('register');
        }

        $result = DB::transaction(
            function () use ($user, $validated): string {

                $record = EmailVerificationCode::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $record ||
                    $record->expires_at->isPast() ||
                    $record->attempts >= 5
                ) {
                    return 'expired';
                }

                if (! Hash::check(
                    $validated['code'],
                    $record->code_hash
                )) {
                    $record->increment('attempts');

                    return 'incorrect';
                }

                $user->forceFill([
                    'email_verified' => true,
                ])->save();

                $record->delete();

                return 'verified';
            }
        );

        if ($result !== 'verified') {

            $message = $result === 'incorrect'
                ? 'That code is incorrect. Please check your email and try again.'
                : 'That code is invalid or expired. Request a new code and try again.';

            return back()->withErrors([
                'code' => $message,
            ]);
        }

        // Clear pending verification session.
        $request->session()->forget(
            'pending_email_verification_user_id'
        );

        // Log in the verified user.
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with(
                'status',
                'Your email is verified. Welcome!'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND VERIFICATION CODE
    |--------------------------------------------------------------------------
    */

    public function resendVerification(
        Request $request
    ): RedirectResponse {

        $user = User::query()->find(
            $request->session()->get(
                'pending_email_verification_user_id'
            )
        );

        if (! $user || $user->email_verified) {
            return redirect()->route('register');
        }

        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->first();

        // One-minute resend cooldown.
        if (
            $record &&
            $record->created_at->gt(now()->subMinute())
        ) {
            return back()->withErrors([
                'code' => 'Please wait a minute before requesting another code.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
            ]
        );

        $sent = $this->sendVerificationCode($user, $code);

        return back()->with(
            $sent ? 'notice' : 'error',
            $sent
                ? 'A new verification code was sent to your email.'
                : 'The verification email could not be sent. Check mail settings and try again.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(
        Request $request
    ): RedirectResponse {

        $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        try {
            $status = Password::sendResetLink([
                'email' => $request->string('email')->toString(),
            ]);

        } catch (Throwable $exception) {

            Log::error('Password reset email could not be sent.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()->with(
                'error',
                'We could not send a password reset email right now. Check the email settings and try again.'
            );
        }

        return back()->with(
            'status',
            $status === Password::RESET_THROTTLED
                ? __($status)
                : 'If an account matches that email, a reset link has been sent. Check your inbox.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD PAGE
    |--------------------------------------------------------------------------
    */

    public function showResetPassword(
        Request $request,
        string $token
    ): View {

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([
            'token' => ['required', 'string'],

            'email' => [
                'required',
                'email',
                'max:160',
            ],

            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8),
            ],
        ]);

        $status = Password::reset(
            $validated,
            function (
                User $user,
                string $password
            ): void {

                $user->forceFill([
                    'password_hash' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()
                ->route('login')
                ->with('status', __($status))
            : back()->withErrors([
                'email' => __($status),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SEND EMAIL VERIFICATION CODE
    |--------------------------------------------------------------------------
    */

    private function sendVerificationCode(
        User $user,
        string $code
    ): bool {

        try {

            Mail::raw(
                "Hello {$user->full_name},\n\n"
                . "Your Romy and Allit Food Haven verification code is: {$code}\n\n"
                . "This code expires in 10 minutes.\n\n"
                . "If you did not create this account, please ignore this email.",

                function ($message) use ($user): void {

                    $message->to($user->email)
                        ->subject(
                            'Verify your Romy and Allit Food Haven account'
                        );
                }
            );

            Log::info(
                'Email verification message accepted by mail transport.',
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]
            );

            return true;

        } catch (Throwable $exception) {

            Log::error(
                'Email verification message could not be sent.',
                [
                    'user_id' => $user->id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]
            );

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
