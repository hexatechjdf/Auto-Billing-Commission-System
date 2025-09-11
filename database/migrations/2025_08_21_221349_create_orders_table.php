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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('contact_id')->nullable();
            $table->string('location_id', 36);
            $table->string('user_id');
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('USD'); // ← Add this line (3-character currency code)
            $table->decimal("amount_charge_percent", 10, 2)->default(2);
            $table->decimal('calculated_commission_amount', 10, 2)->nullable()->comment('Calculated based on %');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status');
            $table->json('payload')->nullable();

            $table->timestamps();

            // $table->index('transaction_id');
            // $table->index('created_at');
            // $table->index('location_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
