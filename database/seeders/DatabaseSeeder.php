<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DemoTeamBuilder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the fixed team with the logins mentioned in the README.
     * Safe to run on every deploy: it does nothing if the team exists.
     */
    public function run(DemoTeamBuilder $builder): void
    {
        if (User::where('email', 'manager@shiftly.test')->exists()) {
            return;
        }

        $builder->build([
            'demo' => false,
            'manager_email' => 'manager@shiftly.test',
            'employee_email' => 'employee@shiftly.test',
        ]);
    }
}
