<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('color')->nullable()->after('brand');
        });

        Schema::table('supplier_offers', function (Blueprint $table): void {
            $table->string('submitted_color')->nullable()->after('submitted_brand');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_offers', function (Blueprint $table): void {
            $table->dropColumn('submitted_color');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }
};
