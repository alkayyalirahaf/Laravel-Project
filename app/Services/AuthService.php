<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_AIRLINE_ADMIN = 'airline_admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public function __construct(private readonly Request $request) {}

    /**
     * Authenticate the credentials and require exactly the expected profile role.
     */
    public function attempt(array $credentials, string $expectedRole): bool
    {
        if (! in_array($expectedRole, [self::ROLE_CUSTOMER, self::ROLE_AIRLINE_ADMIN, self::ROLE_SUPER_ADMIN], true)) {
            return false;
        }

        if (! Auth::attempt($credentials)) {
            return false;
        }

        $user = Auth::user();
        $role = $user instanceof User ? $this->resolveRole($user) : null;

        if ($role === null || $role !== $expectedRole) {
            $this->clearAuthenticatedSession();

            return false;
        }

        if ($role === self::ROLE_CUSTOMER) {
            // TODO(Dev 1): Reject blocked customers once the blocked-status field is added to the customer profile.
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

        return count($roles) === 1 ? $roles[0] : null;
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
