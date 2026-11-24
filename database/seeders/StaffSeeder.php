<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Demo xodimlar (parol: password). Ishga tushirish: sail artisan db:seed --class=StaffSeeder */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Moderator', 'moderator@magaziine.test', 'moderator'],
            ['Operator', 'operator@magaziine.test', 'operator'],
            ['Partner', 'partner@magaziine.test', 'partner'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->fill(['name' => $name, 'password' => 'password']);
            $user->forceFill(['role' => $role, 'is_active' => true, 'email_verified_at' => now()])->save();

            if ($role === 'partner' && ($brand = Brand::query()->first())) {
                $user->brands()->syncWithoutDetaching([$brand->id]);
            }
        }
    }
}
