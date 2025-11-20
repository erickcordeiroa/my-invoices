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
            $table->string('repeat_when')->nullable();
            $table->string('period')->default('monthly');
            $table->integer('enrollments')->nullable();
            $table->integer('enrollments_of')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->timestamps();
            $table->softDeletes();

            // Relatório: usuário + carteira + categoria + status
            $table->index(['user_id', 'wallet_id', 'category_id', 'status'], 'invoices_report_wallet_category_status_index');
            
            // ===== ÍNDICES PARA CONSULTAS ESPECÍFICAS =====
            // Índice composto mais comum: filtrar por usuário e status
            $table->index(['user_id', 'status'], 'invoices_user_status_index');
            
            // Índice para filtrar receitas/despesas por usuário
            $table->index(['user_id', 'type'], 'invoices_user_type_index');
            
            // Índice para buscar invoices relacionadas (parcelas)
            $table->index('invoice_of', 'invoices_invoice_of_index');
            
            // Índice para job de recorrência mensal
            $table->index(['repeat_when', 'enrollments_of'], 'invoices_recurring_index');
            
            // Índice para data de vencimento (jobs de aviso)
            $table->index('due_at', 'invoices_due_at_index');
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
