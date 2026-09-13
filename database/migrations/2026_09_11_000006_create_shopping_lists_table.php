<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->string('parent_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('school_name')->nullable();
            $table->string('learner_name')->nullable();
            $table->string('class_level')->nullable();
            $table->string('delivery_location')->nullable();
            $table->string('delivery_preference')->default('school');
            $table->text('notes')->nullable();
            $table->string('source')->default('upload');
            $table->string('reference')->unique();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->json('cart_items')->nullable();
            $table->unsignedInteger('items_subtotal')->default(0);
            $table->unsignedInteger('delivery_fee')->nullable();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('estimated_total')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->foreignId('delivery_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('delivery_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['phone', 'email']);
            $table->index(['school_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_lists');
    }
};
