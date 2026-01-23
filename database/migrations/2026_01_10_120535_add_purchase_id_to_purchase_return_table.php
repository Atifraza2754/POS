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
            Schema::table('purchase_return', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_id')
                  ->nullable()
                  ->after('state_id');

            $table->foreign('purchase_id')
                  ->references('id')
                  ->on('purchases')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return', function (Blueprint $table) {
            //
        });
    }
};
