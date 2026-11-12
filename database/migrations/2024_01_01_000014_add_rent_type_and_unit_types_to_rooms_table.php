<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM('single', 'double', 'triple', 'four_sharing', 'custom', 'family', 'shop') NOT NULL DEFAULT 'single'");
        }

        if (! Schema::hasColumn('rooms', 'rent_type')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->enum('rent_type', ['per_bed', 'per_room'])->default('per_bed')->after('rent_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rooms', 'rent_type')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('rent_type');
            });
        }
    }
};
