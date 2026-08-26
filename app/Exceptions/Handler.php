<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    private const CONNECTION_ERRORS = [
        1040 => 'Too many connections',
        1203 => 'User connection limit reached',
        2002 => 'Connection refused by server',
        2006 => 'Server closed the connection',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (PDOException $e, Request $request) {
            $code = $this->connectionErrorCode($e);

            if ($code === null) {
                return null;
            }
            $reference = strtoupper(bin2hex(random_bytes(4)));

            Log::warning('Database connection refused', [
                'reference' => $reference,
                'mysql_code' => $code,
                'endpoint' => $request->method().' '.$request->path(),
                'ip' => $request->ip(),
            ]);

            $context = [
                'errorCode' => $code,
                'errorLabel' => self::CONNECTION_ERRORS[$code],
                'reference' => $reference,
                'endpoint' => '/'.ltrim($request->path(), '/'),
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The database refused a new connection. Retry shortly.',
                    'error' => 'mysql_'.$code,
                    'reference' => $reference,
                ], 503, ['Retry-After' => 30]);
            }

            return response()->view('errors.503', $context, 503, ['Retry-After' => 30]);
        });
    }

    /**
     * Return the MySQL error number if this exception is the database refusing a
     * connection, or null if it is an ordinary query fault.
     */
    private function connectionErrorCode(PDOException $e): ?int
    {
        $code = $e->errorInfo[1] ?? null;

        if ($code !== null) {
            return isset(self::CONNECTION_ERRORS[(int) $code]) ? (int) $code : null;
        }

        // Some connection failures arrive before errorInfo is populated, so fall
        // back to the bracketed code MySQL puts in the message text.
        $pattern = '/\[('.implode('|', array_keys(self::CONNECTION_ERRORS)).')\]/';

        return preg_match($pattern, $e->getMessage(), $m) ? (int) $m[1] : null;
    }
}