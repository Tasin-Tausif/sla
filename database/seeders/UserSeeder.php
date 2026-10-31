<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = $this->upsertUser(
            name: 'System Administrator',
            email: 'admin@example.com',
            attributes: [
                'is_available' => false,
                'max_open_tickets' => 0,
            ],
        );
        $administrator->syncRoles(['admin']);

        $aisha = $this->upsertUser(
            name: 'Aisha Rahman',
            email: 'agent.aisha@example.com',
            attributes: [
                'is_available' => true,
                'max_open_tickets' => 10,
            ],
        );
        $aisha->syncRoles(['agent']);

        $daniel = $this->upsertUser(
            name: 'Daniel Chen',
            email: 'agent.daniel@example.com',
            attributes: [
                'is_available' => true,
                'max_open_tickets' => 8,
            ],
        );
        $daniel->syncRoles(['agent']);

        $customerOne = $this->upsertUser(
            name: 'Fatima Al-Sayed',
            email: 'customer.fatima@example.com',
            attributes: [
                'is_available' => false,
                'max_open_tickets' => 0,
            ],
        );
        $customerOne->syncRoles(['customer']);

        $customerTwo = $this->upsertUser(
            name: 'Omar Haddad',
            email: 'customer.omar@example.com',
            attributes: [
                'is_available' => false,
                'max_open_tickets' => 0,
            ],
        );
        $customerTwo->syncRoles(['customer']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertUser(string $name, string $email, array $attributes = []): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => config('app.seed_password')],
        );

        $user->forceFill(array_merge([
            'name' => $name,
            'email_verified_at' => now(),
        ], $attributes))->save();

        return $user;
    }
}
