<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->cascadeOnDelete();
            $table->string('tx_ref')->unique();
            $table->string('provider_transaction_id')->nullable()->unique();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('UGX');
            $table->string('status')->default('initiated');
            $table->text('checkout_url')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->text('payment_exception')->nullable();
            $table->string('payment_exception_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shopping_lists', fn (Blueprint $table) => $table->dropColumn(['payment_exception', 'payment_exception_type']));
        Schema::dropIfExists('payment_attempts');
    }
};
