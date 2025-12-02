<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aplikasis', function (Blueprint $table) {
            $table->string('admin_aplikasi_nip')->nullable()->after('code');
            $table->string('backup_admin_nip')->nullable()->after('admin_aplikasi_nip');
            $table->foreign('admin_aplikasi_nip')->references('nip')->on('admin_aplikasis');
            $table->foreign('backup_admin_nip')->references('nip')->on('admin_aplikasis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aplikasis', function (Blueprint $table) {
            $table->dropForeign(['admin_aplikasi_nip']);
            $table->dropForeign(['backup_admin_nip']);
            $table->dropColumn(['admin_aplikasi_nip', 'backup_admin_nip']);
        });
    }
};
