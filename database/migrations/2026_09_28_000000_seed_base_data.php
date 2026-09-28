<?php

use Database\Seeders\GroupSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed vehicles and groups once, so production works without shell access.
     * Both seeders use firstOrCreate and are safe to run on existing data.
     */
    public function up(): void
    {
        // Tests create their own fixtures
        if (app()->environment('testing')) {
            return;
        }

        (new VehicleSeeder)->run();
        (new GroupSeeder)->run();
    }

    public function down(): void
    {
        //
    }
};
