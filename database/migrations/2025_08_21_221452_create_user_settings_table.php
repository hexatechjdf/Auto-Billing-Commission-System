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
        Schema::create("user_settings", function (Blueprint $table) {
            $table->id();

            $table->foreignId("user_id")->constrained()->onDelete("cascade")->unique();

            $table->string("location_id", 36)->unique();
            $table->string("location_name")->nullable();

            $table->string("email")->nullable();
            $table->string('contact_id')->nullable();
            $table->string("contact_name")->nullable();
            $table->string("contact_phone")->nullable();

            $table->string("stripe_payment_method_id")->nullable();
            $table->string("stripe_customer_id")->nullable();

            $table->boolean("chargeable")->default(true);
            $table->boolean("allow_uninstall")->default(false);
            $table->decimal("threshold_amount", 10, 2);
            $table->string('currency', 3)->default('USD');               // (3-character currency code)
            $table->decimal("amount_charge_percent", 10, 2)->default(2); // cahregeAmount in % (like 2% or 2.5% )
            $table->string("price_id")->nullable()->default(null);
            $table->timestamp('last_checked_at')->nullable()->comment('last time when monthly threshold checked');
            $table->date('pause_at')->nullable()->comment('the date when this subaccunt will be paused automaticaly via cron job');
            $table->boolean("paused")->default(false);
            $table->timestamps();

            // $table->unique('location_id');
            // $table->unique(['user_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("user_settings");
    }
};
