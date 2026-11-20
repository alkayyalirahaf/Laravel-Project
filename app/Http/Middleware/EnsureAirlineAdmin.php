<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAirlineAdmin
{
    public function __construct(private readonly AuthService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('airline.login'));
        }

        abort_unless($this->authService->resolveRole($user) === AuthService::ROLE_AIRLINE_ADMIN, 403);

        return $next($request);
    }
}
