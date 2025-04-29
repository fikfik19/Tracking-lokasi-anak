<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Tambahkan penanganan untuk JSON exception
        $this->renderable(function (\Illuminate\Http\JsonException $e) {
            \Log::error('JSON Parse error: ' . $e->getMessage());
            return response('OK', 200); // Return OK response even if there's an error
        });

        // Tambahkan penanganan untuk semua exception
        $this->renderable(function (Throwable $e) {
            \Log::error('General error: ' . $e->getMessage());
            if (request()->is('api/*')) {
                return response('OK', 200); // Return OK for API requests
            }
        });
    }
}
