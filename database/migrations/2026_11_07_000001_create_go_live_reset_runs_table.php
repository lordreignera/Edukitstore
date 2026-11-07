<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('go_live_reset_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('reset_key')->unique();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('summary');
            $table->timestamp('completed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('go_live_reset_runs');
    }
};
