<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->text('product_categories')->nullable()->after('address');
            $table->string('supply_capacity')->nullable()->after('product_categories');
            $table->text('notes')->nullable()->after('supply_capacity');
            $table->string('verification_document_path')->nullable()->after('notes');
            $table->string('verification_document_name')->nullable()->after('verification_document_path');
            $table->string('source')->default('admin')->after('verification_document_name');
            $table->timestamp('submitted_at')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'product_categories',
                'supply_capacity',
                'notes',
                'verification_document_path',
                'verification_document_name',
                'source',
                'submitted_at',
            ]);
        });
    }
};
