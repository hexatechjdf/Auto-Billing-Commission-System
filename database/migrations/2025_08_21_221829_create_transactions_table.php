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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('location_id', 36)->index();

            // $table->foreign('location_id')
            //     ->references('location_id')
            //     ->on('user_settings')
            //     ->onDelete('cascade');

            $table->decimal("sum_commission_amount", 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->tinyInteger('status')->default(0)->comment('0=pending, 1=paid, 2=failed')->index();
            $table->json("metadata")->nullable();
            $table->timestamp('charged_at')->nullable(); // ->index();
            $table->text("reason")->nullable()->comment('response error message from stripe when trying to charge');
            $table->string("pm_intent", 255)->nullable();
            $table->string("invoice_id", 255)->nullable()->comment('crm_invoice_id');
            $table->timestamps();

            $table->index(['location_id', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
