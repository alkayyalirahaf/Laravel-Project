<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_AIRLINE_ADMIN = 'airline_admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const CUSTOMER_SUSPENDED_MESSAGE = 'Your account is suspended. Please contact support.';

    public function __construct(private readonly Request $request) {}

    /**
     * Authenticate the credentials and require exactly the expected profile role.
     */
    public function attempt(array $credentials, string $expectedRole, bool $remember = false): bool
    {
        if (! in_array($expectedRole, [self::ROLE_CUSTOMER, self::ROLE_AIRLINE_ADMIN, self::ROLE_SUPER_ADMIN], true)) {
            return false;
        }

        if (! Auth::attempt($credentials, $remember)) {
            return false;
        }

        $user = Auth::user();
        $role = $user instanceof User ? $this->resolveRole($user) : null;

        if ($role === null || $role !== $expectedRole) {
            $this->clearAuthenticatedSession();

            return false;
        }

        if ($role === self::ROLE_CUSTOMER) {
            if ((bool) $user->customer?->getAttribute('is_banned')) {
                $this->clearAuthenticatedSession();

                throw ValidationException::withMessages([
                    'email' => self::CUSTOMER_SUSPENDED_MESSAGE,
                ])->redirectTo(route('login'));
            }
        }

        $this->request->session()->regenerate();

        return true;
    }

    /** Return a role only when the user has exactly one profile. */
    public function resolveRole(User $user): ?string
    {
        $user->load(['customer', 'airlineAdmin', 'superAdmin']);

        $profiles = [
            self::ROLE_CUSTOMER => $user->customer,
            self::ROLE_AIRLINE_ADMIN => $user->airlineAdmin,
            self::ROLE_SUPER_ADMIN => $user->superAdmin,
        ];

        $roles = array_keys(array_filter($profiles));

        if (count($roles) !== 1) {
            return null;
        }

        if ($roles[0] === self::ROLE_AIRLINE_ADMIN && $user->airlineAdmin->airline?->status !== 'active') {
            return null;
        }

        return $roles[0];
    }

    public function logout(): void
    {
        $this->clearAuthenticatedSession();
    }

    private function clearAuthenticatedSession(): void
    {
        Auth::logout();
        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();
    }
}
