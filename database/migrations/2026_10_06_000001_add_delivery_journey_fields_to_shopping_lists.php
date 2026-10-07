<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->timestamp('driver_started_at')->nullable()->after('assigned_driver_id');
            $table->foreignId('driver_started_by')->nullable()->after('driver_started_at')->constrained('users')->nullOnDelete();
            $table->timestamp('driver_reached_at')->nullable()->after('driver_started_by');
            $table->foreignId('driver_reached_by')->nullable()->after('driver_reached_at')->constrained('users')->nullOnDelete();
            $table->timestamp('customer_received_at')->nullable()->after('driver_reached_by');
            $table->string('customer_received_name')->nullable()->after('customer_received_at');
            $table->text('customer_received_notes')->nullable()->after('customer_received_name');

            $table->index(['driver_started_at', 'driver_reached_at']);
            $table->index('customer_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->dropForeign(['driver_started_by']);
            $table->dropForeign(['driver_reached_by']);
            $table->dropIndex(['driver_started_at', 'driver_reached_at']);
            $table->dropIndex(['customer_received_at']);
            $table->dropColumn([
                'driver_started_at',
                'driver_started_by',
                'driver_reached_at',
                'driver_reached_by',
                'customer_received_at',
                'customer_received_name',
                'customer_received_notes',
            ]);
        });
    }
};
