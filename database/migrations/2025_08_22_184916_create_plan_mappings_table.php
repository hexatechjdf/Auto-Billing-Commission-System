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
        Schema::create('plan_mappings', function (Blueprint $table) {
            $table->id();
            $table->string("location_id");
            $table->string("product_id");
            $table->string("product_name");
            $table->string("price_id");
            $table->string("price_name");
            $table->decimal("threshold_amount", 10, 2);
            $table->string('currency', 3)->default('USD'); // ← Add this line (3-character currency code)
            $table->decimal("amount_charge_percent", 10, 2)->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mappings');
    }
};
