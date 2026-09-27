<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Aktiver Dienst', 'receives_escalations' => false, 'sort_order' => 1],
            ['name' => 'Jugendfeuerwehr', 'receives_escalations' => false, 'sort_order' => 2],
            ['name' => 'Musikzug', 'receives_escalations' => false, 'sort_order' => 3],
            ['name' => 'Wehrführer', 'receives_escalations' => true, 'sort_order' => 4],
            ['name' => 'Stellv. Wehrführer', 'receives_escalations' => true, 'sort_order' => 5],
        ];

        foreach ($groups as $group) {
            Group::firstOrCreate(['name' => $group['name']], $group);
        }
    }
}
