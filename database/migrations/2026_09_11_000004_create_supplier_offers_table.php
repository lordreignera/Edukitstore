<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('submitted_name');
            $table->text('submitted_description')->nullable();
            $table->string('submitted_brand')->nullable();
            $table->string('submitted_unit')->nullable();
            $table->string('submitted_image_path')->nullable();
            $table->decimal('supplier_price', 12, 2);
            $table->decimal('customer_price', 12, 2)->nullable();
            $table->unsignedInteger('quantity_submitted');
            $table->unsignedInteger('pending_quantity')->default(0);
            $table->unsignedInteger('quantity_available')->default(0);
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->boolean('direct_fulfilment')->default(true);
            $table->string('status')->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['product_id', 'status', 'quantity_available']);
            $table->unique(['supplier_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_offers');
    }
};
