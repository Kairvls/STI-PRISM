<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReporterPins
{
    public const LENGTH = 6;

    public const SESSION_KEY = 'reporter_pin_session';

    public const SESSION_IDLE_MINUTES = 15;

    public const API_TOKEN_MINUTES = 30;

    public const MAX_ATTEMPTS = 5;

    public const LOCK_SECONDS = 15 * 60;

    public const MAX_IP_ATTEMPTS = 30;

    public const RESET_TABLE = 'reporter_pin_resets';

    public const RESET_MINUTES = 30;

    public const RESET_SENDS = 5;

    public const RESET_WINDOW_SECONDS = 3600;

    public const INCORRECT_MESSAGE = 'Employee ID or PIN is incorrect.';

    private const COMMON_PINS = [
        '123123', '321321', '112233', '332211', '121212', '212121',
        '101010', '010101', '696969', '159753', '147258', '258369',
        '789456', '456789', '987654', '102030', '111222', '222111',
    ];

    public static function ready(): bool
    {
        return Schema::hasTable('reporters_table')
            && Schema::hasColumn('reporters_table', 'reporter_pin_hash');
    }

    public static function hasPin(?object $reporter): bool
    {
        return $reporter !== null && filled($reporter->reporter_pin_hash ?? null);
    }

    public static function normalizeEmployeeId(?string $employeeId): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $employeeId));
    }

    public static function findReporter(?string $employeeId): ?object
    {
        $id = self::normalizeEmployeeId($employeeId);

        if ($id === '') {
            return null;
        }

        return DB::table('reporters_table')
            ->whereRaw('UPPER(TRIM(reporter_employee_id)) = ?', [$id])
            ->first();
    }

    public static function findReporterById(int $reporterId): ?object
    {
        return DB::table('reporters_table')
            ->where('reporter_id', $reporterId)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE / VALIDATE / ASSIGN
    |--------------------------------------------------------------------------
    */

    public static function generate(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 999999), self::LENGTH, '0', STR_PAD_LEFT);
        } while (self::weaknessReason($pin) !== null);

        return $pin;
    }

    /**
     * Null when the PIN is acceptable, otherwise a message explaining why it was refused.
     */
    public static function weaknessReason(string $pin): ?string
    {
        if (! preg_match('/^\d{'.self::LENGTH.'}$/', $pin)) {
            return 'PIN must be exactly '.self::LENGTH.' digits.';
        }

        if (count(array_unique(str_split($pin))) <= 2) {
            return 'PIN is too easy to guess. Use at least 3 different digits.';
        }

        $ascending = true;
        $descending = true;
        for ($i = 1; $i < self::LENGTH; $i++) {
            $step = ((int) $pin[$i] - (int) $pin[$i - 1] + 10) % 10;
            $ascending = $ascending && $step === 1;
            $descending = $descending && $step === 9;
        }

        if ($ascending || $descending || in_array($pin, self::COMMON_PINS, true)) {
            return 'PIN is too easy to guess. Avoid sequences and common patterns.';
        }

        return null;
    }

    /**
     * Saves a new PIN for the reporter (generated when none is given) and returns the plain PIN.
     */
    public static function assign(int $reporterId, ?string $pin = null): string
    {
        $pin ??= self::generate();

        DB::table('reporters_table')
            ->where('reporter_id', $reporterId)
            ->update([
                'reporter_pin_hash' => Hash::make($pin),
                'reporter_pin_set_at' => now(),
            ]);

        $reporter = self::findReporterById($reporterId);
        if ($reporter) {
            RateLimiter::clear(self::attemptKey((string) $reporter->reporter_employee_id));
        }

        return $pin;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY EMPLOYEE ID + PIN
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{ok: bool, status: int, message: string, reporter?: object, code?: string, retry_after?: int}
     */
    public static function verify(?string $employeeId, ?string $pin, string $ip): array
    {
        $id = self::normalizeEmployeeId($employeeId);
        $pin = trim((string) $pin);
        $idKey = self::attemptKey($id);
        $ipKey = 'reporter-pin-ip:'.$ip;

        if (RateLimiter::tooManyAttempts($idKey, self::MAX_ATTEMPTS)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS)) {
            $retryAfter = max(RateLimiter::availableIn($idKey), RateLimiter::availableIn($ipKey));

            return [
                'ok' => false,
                'status' => 429,
                'code' => 'locked',
                'retry_after' => $retryAfter,
                'message' => self::lockMessage($retryAfter),
            ];
        }

        $reporter = self::findReporter($id);

        $matches = self::hasPin($reporter)
            && preg_match('/^\d{'.self::LENGTH.'}$/', $pin)
            && Hash::check($pin, $reporter->reporter_pin_hash);

        if (! $matches) {
            RateLimiter::hit($idKey, self::LOCK_SECONDS);
            RateLimiter::hit($ipKey, self::LOCK_SECONDS);

            $left = RateLimiter::remaining($idKey, self::MAX_ATTEMPTS);

            if ($left <= 0) {
                $retryAfter = RateLimiter::availableIn($idKey);

                return [
                    'ok' => false,
                    'status' => 429,
                    'code' => 'locked',
                    'retry_after' => $retryAfter,
                    'message' => self::lockMessage($retryAfter),
                ];
            }

            return [
                'ok' => false,
                'status' => 422,
                'code' => 'incorrect',
                'attempts_left' => $left,
                'message' => self::INCORRECT_MESSAGE.' '.$left.' '.Str::plural('attempt', $left).' left before this employee ID is paused for 15 minutes.',
            ];
        }

        RateLimiter::clear($idKey);

        if (strtolower((string) $reporter->reporter_status) !== 'active') {
            return [
                'ok' => false,
                'status' => 403,
                'code' => 'inactive',
                'message' => 'This reporter account is inactive and cannot submit maintenance reports. Please contact the system administrator.',
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Reporter verified.',
            'reporter' => $reporter,
        ];
    }

    public static function lockMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return 'Too many incorrect PIN attempts. Try again in '.$minutes.' '.Str::plural('minute', $minutes).', or use Forgot PIN.';
    }

    private static function attemptKey(string $employeeId): string
    {
        return 'reporter-pin-id:'.sha1(self::normalizeEmployeeId($employeeId));
    }

    /**
     * Reporter is still allowed to report and nobody has changed their PIN since they verified.
     */
    private static function stillValid(?object $reporter, ?string $pinFingerprint): bool
    {
        return $reporter !== null
            && strtolower((string) $reporter->reporter_status) === 'active'
            && self::hasPin($reporter)
            && hash_equals(self::pinFingerprint($reporter), (string) $pinFingerprint);
    }

    private static function pinFingerprint(object $reporter): string
    {
        return hash('sha256', (string) $reporter->reporter_pin_hash);
    }

    /*
    |--------------------------------------------------------------------------
    | WEB SESSION (Phase 1 -> Phase 2)
    |--------------------------------------------------------------------------
    */

    public static function startSession(Request $request, object $reporter): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'reporter_id' => (int) $reporter->reporter_id,
            'pin' => self::pinFingerprint($reporter),
            'last_seen' => now()->timestamp,
        ]);
    }

    public static function sessionReporter(Request $request, bool $touch = true): ?object
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! is_array($state) || empty($state['reporter_id'])) {
            return null;
        }

        if (now()->timestamp - (int) ($state['last_seen'] ?? 0) > self::SESSION_IDLE_MINUTES * 60) {
            self::endSession($request);

            return null;
        }

        $reporter = self::findReporterById((int) $state['reporter_id']);

        if (! self::stillValid($reporter, $state['pin'] ?? null)) {
            self::endSession($request);

            return null;
        }

        if ($touch) {
            $state['last_seen'] = now()->timestamp;
            $request->session()->put(self::SESSION_KEY, $state);
        }

        return $reporter;
    }

    public static function sessionMatches(Request $request, ?string $employeeId): ?object
    {
        $reporter = self::sessionReporter($request);

        if (! $reporter) {
            return null;
        }

        return self::normalizeEmployeeId($reporter->reporter_employee_id) === self::normalizeEmployeeId($employeeId)
            ? $reporter
            : null;
    }

    public static function endSession(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE API TOKENS
    |--------------------------------------------------------------------------
    */

    public static function issueApiToken(object $reporter): string
    {
        $token = Str::random(64);

        Cache::put(self::apiTokenKey($token), [
            'reporter_id' => (int) $reporter->reporter_id,
            'pin' => self::pinFingerprint($reporter),
        ], now()->addMinutes(self::API_TOKEN_MINUTES));

        return $token;
    }

    public static function apiTokenFromRequest(Request $request): ?string
    {
        $token = $request->header('X-Reporter-Token') ?: $request->input('reporter_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function apiReporter(Request $request): ?object
    {
        $token = self::apiTokenFromRequest($request);

        if (! $token) {
            return null;
        }

        $state = Cache::get(self::apiTokenKey($token));

        if (! is_array($state) || empty($state['reporter_id'])) {
            return null;
        }

        $reporter = self::findReporterById((int) $state['reporter_id']);

        if (! self::stillValid($reporter, $state['pin'] ?? null)) {
            Cache::forget(self::apiTokenKey($token));

            return null;
        }

        return $reporter;
    }

    public static function revokeApiToken(?string $token): void
    {
        if ($token) {
            Cache::forget(self::apiTokenKey($token));
        }
    }

    private static function apiTokenKey(string $token): string
    {
        return 'reporter-api-token:'.hash('sha256', $token);
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    public static function mailerIsConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return true;
        }

        return filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && filled(config('mail.from.address'));
    }

    public static function canEmail(?object $reporter): bool
    {
        return $reporter !== null
            && filter_var((string) ($reporter->reporter_email_address ?? ''), FILTER_VALIDATE_EMAIL)
            && self::mailerIsConfigured();
    }

    /**
     * Sends the plain PIN to the reporter. Returns false when there is no email or sending failed.
     */
    public static function emailPin(object $reporter, string $pin, bool $isNew = true): bool
    {
        if (! self::canEmail($reporter)) {
            return false;
        }

        try {
            Mail::send('emails.reporter-pin', [
                'name' => $reporter->reporter_full_name,
                'employeeId' => $reporter->reporter_employee_id,
                'pin' => $pin,
                'isNew' => $isNew,
                'appUrl' => rtrim((string) config('app.url'), '/'),
            ], function ($message) use ($reporter, $isNew) {
                $message->to($reporter->reporter_email_address)
                    ->subject($isNew ? 'Your PaAyo reporting PIN' : 'Your PaAyo reporting PIN was changed');
            });

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PIN (reset link by email)
    |--------------------------------------------------------------------------
    */

    public static function resetsReady(): bool
    {
        return self::ready() && Schema::hasTable(self::RESET_TABLE);
    }

    /**
     * Emails a one-time reset link when the employee ID belongs to an active reporter with an email.
     * Callers always show the same response so the result does not reveal which IDs exist.
     */
    public static function sendResetLink(?string $employeeId): bool
    {
        if (! self::resetsReady()) {
            return false;
        }

        $reporter = self::findReporter($employeeId);

        if (! $reporter
            || strtolower((string) $reporter->reporter_status) !== 'active'
            || ! self::canEmail($reporter)) {
            return false;
        }

        $plainToken = Str::random(64);
        $expiresAt = now()->addMinutes(self::RESET_MINUTES);

        DB::table(self::RESET_TABLE)
            ->where('reporter_id', $reporter->reporter_id)
            ->where(function ($query) {
                $query->whereNotNull('used_at')
                    ->orWhere('expires_at', '<=', now());
            })
            ->delete();

        DB::table(self::RESET_TABLE)->insert([
            'reporter_id' => $reporter->reporter_id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        $resetUrl = rtrim((string) config('app.url'), '/').'/reporter/pin/reset/'.$plainToken;

        try {
            Mail::send('emails.reporter-pin-reset', [
                'name' => $reporter->reporter_full_name,
                'employeeId' => $reporter->reporter_employee_id,
                'resetUrl' => $resetUrl,
                'minutes' => self::RESET_MINUTES,
                'expiresAt' => $expiresAt->timezone(config('app.timezone'))->format('g:i A'),
                'expiresOn' => $expiresAt->timezone(config('app.timezone'))->format('M j, Y'),
            ], function ($message) use ($reporter) {
                $message->to($reporter->reporter_email_address)->subject('Reset your PaAyo reporting PIN');
            });

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Sends at most 5 reset emails per employee ID each hour.
     *
     * @return array{limited: bool, retry_after: int|null, message: string}
     */
    public static function requestResetLink(?string $employeeId): array
    {
        $key = 'reporter-pin-forgot:'.sha1(self::normalizeEmployeeId($employeeId));

        if (RateLimiter::tooManyAttempts($key, self::RESET_SENDS)) {
            $retryAfter = RateLimiter::availableIn($key);

            return [
                'limited' => true,
                'retry_after' => $retryAfter,
                'message' => self::resetWaitMessage($retryAfter),
            ];
        }

        RateLimiter::hit($key, self::RESET_WINDOW_SECONDS);
        self::sendResetLink($employeeId);

        return [
            'limited' => false,
            'retry_after' => null,
            'message' => 'If this employee ID has an email on file, we sent a link to set a new PIN. It expires in '
                .self::RESET_MINUTES.' minutes. No email on file? Ask the system administrator to reset your PIN.',
        ];
    }

    public static function resetWaitMessage(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.Str::plural('hour', $hours);
        }
        if ($minutes > 0) {
            $parts[] = $minutes.' '.Str::plural('minute', $minutes);
        }
        if ($hours === 0) {
            $parts[] = $remain.' '.Str::plural('second', $remain);
        }

        return 'You can only request '.self::RESET_SENDS.' reset emails per hour. Try again in '.implode(' ', $parts).'.';
    }

    public static function matchesCurrentPin(int $reporterId, string $pin): bool
    {
        $hash = DB::table('reporters_table')
            ->where('reporter_id', $reporterId)
            ->value('reporter_pin_hash');

        return filled($hash) && Hash::check($pin, (string) $hash);
    }

    public static function findReset(string $token): ?object
    {
        if (! self::resetsReady()) {
            return null;
        }

        return DB::table(self::RESET_TABLE)
            ->join('reporters_table', 'reporters_table.reporter_id', '=', self::RESET_TABLE.'.reporter_id')
            ->where(self::RESET_TABLE.'.token_hash', hash('sha256', $token))
            ->whereNull(self::RESET_TABLE.'.used_at')
            ->where(self::RESET_TABLE.'.expires_at', '>', now())
            ->where('reporters_table.reporter_status', 'Active')
            ->select(
                self::RESET_TABLE.'.id as reset_id',
                self::RESET_TABLE.'.expires_at',
                'reporters_table.reporter_id',
                'reporters_table.reporter_full_name',
                'reporters_table.reporter_employee_id'
            )
            ->first();
    }

    public static function completeReset(object $reset, string $pin): void
    {
        DB::transaction(function () use ($reset, $pin) {
            self::assign((int) $reset->reporter_id, $pin);

            DB::table(self::RESET_TABLE)
                ->where('reporter_id', $reset->reporter_id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);
        });
    }
}
