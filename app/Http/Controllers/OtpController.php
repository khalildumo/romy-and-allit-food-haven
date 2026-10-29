<?php

namespace App\Http\Controllers;

use App\Models\EmailOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class OtpController extends Controller
{
    public function showSend(): View
    {
        return view('otp.send');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($validated['email']));
        $code = (string) random_int(100000, 999999);

        $otp = DB::transaction(function () use ($email, $code): EmailOtp {
            EmailOtp::query()
                ->where('email', $email)
                ->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);

            return EmailOtp::query()->create([
                'email' => $email,
                'otp_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(5),
            ]);
        });

        try {
            Mail::raw("Your email verification code is {$code}. It expires in 5 minutes. If you did not request this code, you can ignore this email.", function ($message) use ($email): void {
                $message->to($email)->subject('Your Romy and Allit Food Haven verification code');
            });
        } catch (TransportExceptionInterface $exception) {
            $otp->update(['used_at' => now()]);
            Log::warning('Standalone OTP email could not be sent.', ['otp_id' => $otp->id]);

            return back()->withInput()->withErrors(['email' => 'We could not send your OTP. Check mail settings and try again.']);
        }

        $request->session()->put([
            'otp_email' => $email,
            'otp_last_id' => $otp->id,
        ]);

        return redirect()->route('otp.verify');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('otp_email');
        $otpId = $request->session()->get('otp_last_id');
        if (! $email || ! $otpId) {
            return redirect()->route('otp.send');
        }

        return view('otp.verify', ['email' => $email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate(['otp' => ['required', 'digits:6']]);
        $email = $request->session()->get('otp_email');
        $otpId = $request->session()->get('otp_last_id');
        if (! $email || ! $otpId) {
            return redirect()->route('otp.send');
        }

        $result = DB::transaction(function () use ($email, $otpId, $validated): string {
            $otp = EmailOtp::query()
                ->whereKey($otpId)
                ->where('email', $email)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= 5) {
                return 'expired';
            }

            if (! Hash::check($validated['otp'], $otp->otp_hash)) {
                $otp->increment('attempts');

                return 'incorrect';
            }

            $otp->update(['used_at' => now()]);

            return 'verified';
        });

        if ($result !== 'verified') {
            $message = $result === 'incorrect'
                ? 'Incorrect OTP. Please try again.'
                : 'Your OTP is expired, used, or has too many failed attempts. Please request a new code.';

            return back()->withErrors(['otp' => $message]);
        }

        $request->session()->regenerate();
        $request->session()->put('email_verified', $email);
        $request->session()->forget(['otp_email', 'otp_last_id']);

        return redirect()->route('otp.success');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('email_verified');
        if (! $email) {
            return redirect()->route('otp.send');
        }

        return view('otp.success', compact('email'));
    }
}
