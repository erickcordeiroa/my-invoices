<?php

namespace App\Services;

use App\DTO\Admin\SearchWalletDTO;
use App\DTO\Admin\StoreUpdateWalletDTO;
use App\Exceptions\Admin\WalletException;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\CursorPaginator;

class WalletServices
{
    public function getAll(): CursorPaginator
    {
        $user = Auth::user();
        $wallets = Wallet::where('user_id', $user->id)->cursorPaginate(12);

        return $wallets;
    }

    public function search(SearchWalletDTO $data): Collection
    {
        $user = Auth::user();
        $wallets = $user->wallets()->where('name', 'like', '%' . $data->name . '%')->get();

        if ($wallets->isEmpty()) {
            throw new WalletException('Nenhuma carteira encontrada');
        }

        return $wallets;
    }

    public function show(int $id): Wallet
    {
        $user = Auth::user();
        $wallet = $user->wallets()->where('id', $id)->first();

        if (!$wallet || $wallet?->user_id != Auth::user()->id) {
            throw new WalletException('Carteira não encontrada');
        }

        return $wallet;
    }

    public function store(StoreUpdateWalletDTO $data): Wallet
    {
        $user = Auth::user();

        $wallet = $user->wallets()->where('name', $data->name)->first();

        if ($wallet) {
            throw new WalletException('Carteira já existente, verifique os dados');
        }

        $wallet = $user->wallets()->create([
            'name' => $data->name,
            'balance' => $data->balance,
        ]);

        return $wallet;
    }

    public function update(int $id, StoreUpdateWalletDTO $data): Wallet
    {
        $user = Auth::user();
        $wallet = $user->wallets()->where('id', $id)->first();

        if (!$wallet || $wallet?->user_id != Auth::user()->id) {
            throw new WalletException('Carteira não encontrada');
        }

        if ($wallet->name == $data->name) {
            throw new WalletException('Carteira já existente, verifique os dados');
        }

        $wallet->update([
            'name' => $data->name,
            'balance' => $data->balance,
        ]);


        return $wallet;
    }   

    public function destroy(int $id): void
    {
        $user = Auth::user();
        $wallet = $user->wallets()->where('id', $id)->first();

        $wallet->delete();
    }
}