<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            'customer' => config('auth_seeds.customer'),
            'super_admin' => config('auth_seeds.super_admin'),
            'airline_admin' => config('auth_seeds.airline_admin'),
        ];

        foreach ($accounts as $role => $account) {
            if (! is_array($account) || ! is_string($account['password'] ?? null) || trim($account['password']) === '') {
                throw new InvalidArgumentException("Set AUTH_SEED_{$this->environmentRole($role)}_PASSWORD before seeding auth accounts.");
            }

            if (! is_string($account['email'] ?? null) || trim($account['email']) === '') {
                throw new InvalidArgumentException("Set AUTH_SEED_{$this->environmentRole($role)}_EMAIL before seeding auth accounts.");
            }
        }

        $emails = array_map(fn (array $account): string => strtolower(trim($account['email'])), $accounts);

        if (count(array_unique($emails)) !== count($emails)) {
            throw new InvalidArgumentException('Auth seed account emails must be distinct.');
        }

        $airlineData = config('auth_seeds.airline');

        if (! is_array($airlineData) || ! is_string($airlineData['code'] ?? null) || trim($airlineData['code']) === '') {
            throw new InvalidArgumentException('Set AUTH_SEED_AIRLINE_CODE before seeding auth accounts.');
        }

        DB::transaction(function () use ($accounts, $airlineData): void {
            $airline = Airline::updateOrCreate(
                ['code' => $airlineData['code']],
                [
                    'name' => $airlineData['name'],
                    'country' => $airlineData['country'],
                    'status' => 'active',
                ],
            );

            $customer = $this->user($accounts['customer']);
            $customer->customer()->firstOrCreate([], ['phone' => null, 'credit_balance' => '0.00']);

            $superAdmin = $this->user($accounts['super_admin']);
            $superAdmin->superAdmin()->firstOrCreate([], ['phone' => null]);

            $airlineAdmin = $this->user($accounts['airline_admin']);
            $airlineAdmin->airlineAdmin()->firstOrCreate([], [
                'airline_id' => $airline->id,
                'phone' => null,
                'job_title' => 'Administrator',
            ]);
        });
    }

    private function user(array $account): User
    {
        return User::updateOrCreate(
            ['email' => strtolower(trim($account['email']))],
            ['name' => $account['name'], 'password' => $account['password']],
        );
    }

    private function environmentRole(string $role): string
    {
        return match ($role) {
            'super_admin' => 'SUPER_ADMIN',
            'airline_admin' => 'AIRLINE_ADMIN',
            default => 'CUSTOMER',
        };
    }
}
