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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->foreignId('category_id')->constrained('categories');
            $table->integer('invoice_of')->nullable();
            $table->string('description')->nullable();
            $table->enum('type', ['income', 'expense'])->nullable();
            $table->integer('amount');
            $table->string('currency')->default('BRL');
            $table->date('due_at');
            $table->date('paid_at')->nullable();
            $table->string('repeat_when');
            $table->string('period')->default('monthly');
            $table->integer('enrollments')->nullable();
            $table->integer('enrollments_of')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
