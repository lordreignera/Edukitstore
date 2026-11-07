<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_lists', function (Blueprint $table): void {
            // Payment remains independent from the operational packing and refund stages.
            $table->timestamp('packed_at')->nullable();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('manual_refund_amount')->default(0);
            $table->string('manual_refund_reference', 160)->nullable();
            $table->timestamp('manual_refunded_at')->nullable();
            $table->foreignId('manual_refund_recorded_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('shopping_list_items', function (Blueprint $table): void {
            // Keep the original ordered quantity even when only some units can be delivered.
            $table->unsignedInteger('ordered_quantity')->default(0);
            $table->unsignedInteger('unavailable_quantity')->default(0);
            $table->string('unavailable_reason', 40)->nullable();
            $table->text('unavailable_note')->nullable();
        });

        DB::table('shopping_list_items')->update([
            'ordered_quantity' => DB::raw('quantity'),
        ]);

        // Existing paid assignments predate packing timestamps; preserve their active workflow.
        DB::table('shopping_lists')
            ->where('payment_status', 'paid')
            ->whereNotNull('assigned_driver_id')
            ->whereNull('packed_at')
            ->update(['packed_at' => DB::raw('COALESCE(driver_started_at, paid_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table): void {
            $table->dropColumn([
                'ordered_quantity',
                'unavailable_quantity',
                'unavailable_reason',
                'unavailable_note',
            ]);
        });

        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('packed_by');
            $table->dropConstrainedForeignId('manual_refund_recorded_by');
            $table->dropColumn([
                'packed_at',
                'manual_refund_amount',
                'manual_refund_reference',
                'manual_refunded_at',
            ]);
        });
    }
};
