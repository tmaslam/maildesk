<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(100)->after('brand_name');
        });

        foreach ([
            '1Dollar Digitizing'  => 1,
            'Aplus Digitizing'    => 2,
            'Digitizing Zone'     => 3,
        ] as $brand => $order) {
            DB::table('mailboxes')->where('brand_name', $brand)->update(['sort_order' => $order]);
        }
    }

    public function down(): void
    {
        Schema::table('mailboxes', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};

