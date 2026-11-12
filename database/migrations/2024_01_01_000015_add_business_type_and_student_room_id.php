<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'business_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('business_type', ['pg', 'hostel', 'lodge'])->default('pg')->after('role');
            });
        }

        if (! Schema::hasColumn('students', 'room_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('room_id')->nullable()->after('bed_id')->constrained()->nullOnDelete();
                $table->index('room_id');
            });

            // Backfill room_id for any existing students assigned to a bed
            DB::table('students')
                ->whereNotNull('bed_id')
                ->whereNull('room_id')
                ->orderBy('id')
                ->get(['id', 'bed_id'])
                ->each(function ($student) {
                    $roomId = DB::table('beds')->where('id', $student->bed_id)->value('room_id');
                    if ($roomId) {
                        DB::table('students')->where('id', $student->id)->update(['room_id' => $roomId]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'room_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('room_id');
            });
        }

        if (Schema::hasColumn('users', 'business_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('business_type');
            });
        }
    }
};
