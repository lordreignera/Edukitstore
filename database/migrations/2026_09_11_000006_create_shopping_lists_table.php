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
            $table->string('school_name')->nullable();
            $table->string('learner_name')->nullable();
            $table->string('class_level')->nullable();
            $table->string('delivery_location')->nullable();
            $table->string('delivery_preference')->default('school');
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('status')->default('pending');
            $table->unsignedInteger('estimated_total')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_lists');
    }
};
