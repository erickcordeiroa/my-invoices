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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('price')->default(0); // em centavos
            $table->integer('cost_price')->default(0)->nullable(); // preço de custo em centavos
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0); // estoque mínimo para alertas
            $table->string('unit')->default('un'); // un, kg, l, m, etc
            $table->string('brand')->nullable();
            $table->string('color')->nullable();
            $table->json('images')->nullable(); // array de imagens
            $table->boolean('is_active')->default(true);
            $table->boolean('has_variations')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
