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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('party_id');
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('cascade');
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('remaining_amount', 20, 2)->default(0);
            $table->string('payment_type');
             $table->enum('payment_for', ['advance', 'purchase'])->default('purchase');
            $table->string('payment_note')->nullable();
            $table->date('payment_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users'); 
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
