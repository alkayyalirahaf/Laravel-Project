<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function __construct(private readonly AuthService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        abort_unless($this->authService->resolveRole($user) === AuthService::ROLE_CUSTOMER, 403);

        if ((bool) $user->customer?->getAttribute('is_banned')) {
            $this->authService->logout();

            return redirect()->route('login')->withErrors([
                'email' => AuthService::CUSTOMER_SUSPENDED_MESSAGE,
            ]);
        }

        return $next($request);
    }
}
