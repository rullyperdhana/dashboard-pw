<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

class RecordLoginAttempt
{
    protected $request;

    /**
     * Create the event listener.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof Login) {
            $username = $event->user->username ?? ($event->user->email ?? 'unknown');
            $this->record($username, 'success', 'Login successful');
        } elseif ($event instanceof Failed) {
            $username = $event->credentials['username'] ?? ($event->credentials['email'] ?? 'unknown');
            $this->record($username, 'failed', 'Invalid credentials or user not found');
        }
    }

    protected function record($username, $status, $message)
    {
        LoginLog::create([
            'username' => $username,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'status' => $status,
            'message' => $message,
        ]);
    }
}
