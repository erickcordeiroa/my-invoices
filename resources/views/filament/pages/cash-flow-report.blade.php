<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Gerar Relatório
            </x-filament::button>
        </div>
    </form>

    @if($periodSummary)
        {{-- Cards de Resumo Principal --}}
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-chart-pie class="w-6 h-6" />
                Resumo do Período
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Saldo Atual das Carteiras --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo Atual (Carteiras)</p>
                            <p class="text-2xl font-bold {{ $periodSummary['total_wallet_balance'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $this->formatMoney($periodSummary['total_wallet_balance']) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <x-heroicon-o-wallet class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>

                {{-- Total a Receber --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total a Receber</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $this->formatMoney($periodSummary['pending_income']) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Recebido: {{ $this->formatMoney($periodSummary['paid_income']) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <x-heroicon-o-arrow-trending-up class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>

                {{-- Total a Pagar --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total a Pagar</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ $this->formatMoney($periodSummary['pending_expense']) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Pago: {{ $this->formatMoney($periodSummary['paid_expense']) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <x-heroicon-o-arrow-trending-down class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>

                {{-- Saldo Projetado --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo Projetado</p>
                            <p class="text-2xl font-bold {{ $periodSummary['projected_balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $this->formatMoney($periodSummary['projected_balance']) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Após receber e pagar tudo
                            </p>
                        </div>
                        <div class="w-12 h-12 {{ $periodSummary['projected_balance'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-full flex items-center justify-center">
                            <x-heroicon-o-calculator class="{{ $periodSummary['projected_balance'] >= 0 ? 'w-6 h-6 text-emerald-600 dark:text-emerald-400' : 'w-6 h-6 text-red-600 dark:text-red-400' }}" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cards de Estatísticas --}}
        <div class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Balanço do Período --}}
                <div class="bg-gradient-to-br {{ $periodSummary['period_balance'] >= 0 ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600' }} rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium opacity-90">Balanço do Período</p>
                            <p class="text-3xl font-bold mt-1">
                                {{ $this->formatMoney($periodSummary['period_balance']) }}
                            </p>
                            <p class="text-xs opacity-75 mt-2">
                                Receitas - Despesas
                            </p>
                        </div>
                        <div class="opacity-50">
                            @if($periodSummary['period_balance'] >= 0)
                                <x-heroicon-o-arrow-trending-up class="w-16 h-16" />
                            @else
                                <x-heroicon-o-arrow-trending-down class="w-16 h-16" />
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Total de Lançamentos --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-7 h-7 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Lançamentos</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $periodSummary['total_count'] }}</p>
                            <div class="flex gap-3 mt-1 text-xs">
                                <span class="text-green-600 dark:text-green-400">{{ $periodSummary['paid_count'] }} pagos</span>
                                <span class="text-amber-600 dark:text-amber-400">{{ $periodSummary['unpaid_count'] }} pendentes</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contas Atrasadas --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 {{ $periodSummary['overdue_expense'] > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700' }} rounded-full flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-7 h-7 {{ $periodSummary['overdue_expense'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Contas Atrasadas</p>
                            <p class="text-2xl font-bold {{ $periodSummary['overdue_expense'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $this->formatMoney($periodSummary['overdue_expense']) }}
                            </p>
                            @if($periodSummary['overdue_income'] > 0)
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    A receber atrasado: {{ $this->formatMoney($periodSummary['overdue_income']) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resumo por Carteira --}}
        @if(count($walletsSummary) > 0)
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <x-heroicon-o-wallet class="w-6 h-6" />
                    Resumo por Carteira
                </h2>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-gray-900 dark:text-white">Carteira</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Saldo Atual</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Receitas (Período)</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Despesas (Período)</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">A Receber</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">A Pagar</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Saldo Projetado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($walletsSummary as $wallet)
                                    @php
                                        $projectedWalletBalance = $wallet['current_balance'] + $wallet['pending_income'] - $wallet['pending_expense'];
                                    @endphp
                                    <tr class="hover:bg-gray-100/50 dark:hover:bg-gray-900/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                                    <x-heroicon-o-wallet class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $wallet['wallet_name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right {{ $wallet['current_balance'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }} font-semibold">
                                            {{ $this->formatMoney($wallet['current_balance']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-green-600 dark:text-green-400">
                                            {{ $this->formatMoney($wallet['income_in_period']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-red-600 dark:text-red-400">
                                            {{ $this->formatMoney($wallet['expense_in_period']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400">
                                            {{ $this->formatMoney($wallet['pending_income']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400">
                                            {{ $this->formatMoney($wallet['pending_expense']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right {{ $projectedWalletBalance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} font-bold">
                                            {{ $this->formatMoney($projectedWalletBalance) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Resumo por Categoria --}}
        @if($categorySummary->count() > 0)
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <x-heroicon-o-tag class="w-6 h-6" />
                    Resumo por Categoria
                </h2>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-gray-900 dark:text-white">Categoria</th>
                                    <th class="px-6 py-4 text-center font-semibold text-gray-900 dark:text-white">Tipo</th>
                                    <th class="px-6 py-4 text-center font-semibold text-gray-900 dark:text-white">Qtd</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Receitas</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Despesas</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Pago</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Pendente</th>
                                    <th class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">Balanço</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($categorySummary as $category)
                                    <tr class="hover:bg-gray-100/50 dark:hover:bg-gray-900/50 transition-colors ">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 {{ $category['category_type'] === 'income' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-full flex items-center justify-center">
                                                    @if($category['category_type'] === 'income')
                                                        <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-green-600 dark:text-green-400" />
                                                    @else
                                                        <x-heroicon-o-arrow-trending-down class="w-4 h-4 text-red-600 dark:text-red-400" />
                                                    @endif
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $category['category_name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $category['category_type'] === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                                {{ $category['category_type'] === 'income' ? 'Receita' : 'Despesa' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">
                                            {{ $category['count'] }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-green-600 dark:text-green-400">
                                            {{ $category['total_income'] > 0 ? $this->formatMoney($category['total_income']) : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-red-600 dark:text-red-400">
                                            {{ $category['total_expense'] > 0 ? $this->formatMoney($category['total_expense']) : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">
                                            {{ $this->formatMoney($category['total_paid']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400">
                                            {{ $this->formatMoney($category['total_unpaid']) }}
                                        </td>
                                        <td class="px-6 py-4 text-right {{ $category['balance'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-bold">
                                            {{ $this->formatMoney($category['balance']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 dark:bg-gray-700/50">
                                <tr class="font-bold">
                                    <td class="px-6 py-4 text-gray-900 dark:text-white" colspan="3">TOTAL</td>
                                    <td class="px-6 py-4 text-right text-green-600 dark:text-green-400">
                                        {{ $this->formatMoney($categorySummary->sum('total_income')) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-red-600 dark:text-red-400">
                                        {{ $this->formatMoney($categorySummary->sum('total_expense')) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 dark:text-white">
                                        {{ $this->formatMoney($categorySummary->sum('total_paid')) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400">
                                        {{ $this->formatMoney($categorySummary->sum('total_unpaid')) }}
                                    </td>
                                    <td class="px-6 py-4 text-right {{ $categorySummary->sum('balance') >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $this->formatMoney($categorySummary->sum('balance')) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabela Detalhada de Lançamentos --}}
        @if($invoices->count() > 0)
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-6 h-6" />
                    Lançamentos Detalhados
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $invoices->count() }} registros)</span>
                </h2>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Data</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Descrição</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Categoria</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Carteira</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">Parcela</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($invoices as $invoice)
                                    <tr class="hover:bg-gray-100/50 dark:hover:bg-gray-900/50 transition-colors">
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $invoice->due_at->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                @if($invoice->type === 'income')
                                                    <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" />
                                                @else
                                                    <x-heroicon-o-arrow-trending-down class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" />
                                                @endif
                                                <span class="font-medium text-gray-900 dark:text-white truncate max-w-[200px]" title="{{ $invoice->description }}">
                                                    {{ $invoice->description }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $invoice->category?->name ?? 'Sem categoria' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                {{ $invoice->wallet?->name ?? 'Sem carteira' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">
                                            @if($invoice->enrollments)
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                    {{ $invoice->enrollments_of }}/{{ $invoice->enrollments }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @switch($invoice->status)
                                                @case('paid')
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                        <x-heroicon-o-check-circle class="w-3 h-3" />
                                                        Pago
                                                    </span>
                                                    @break
                                                @case('unpaid')
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                        <x-heroicon-o-clock class="w-3 h-3" />
                                                        Pendente
                                                    </span>
                                                    @break
                                                @case('overdue')
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                        <x-heroicon-o-exclamation-circle class="w-3 h-3" />
                                                        Atrasado
                                                    </span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $invoice->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $invoice->type === 'income' ? '+' : '-' }} {{ $this->formatMoney($invoice->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="mt-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-document-magnifying-glass class="w-16 h-16 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Nenhum lançamento encontrado</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Ajuste os filtros ou selecione um período diferente.</p>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
