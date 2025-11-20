<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Admin\SearchWalletDTO;
use App\DTO\Admin\StoreUpdateWalletDTO;
use App\Exceptions\Admin\WalletException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchWalletRequest;
use App\Http\Requests\Admin\StoreUpdateWalletRequest;
use App\Http\Resources\Admin\WalletResource;
use App\Services\WalletServices;
use Symfony\Component\HttpFoundation\Response;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletServices $walletServices
    ){}

    public function index()
    {
        try {
            $wallets = $this->walletServices->getAll();
            return response()->json(
                WalletResource::collection($wallets), 
                Response::HTTP_OK
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function search(SearchWalletRequest $request)
    {
        try {
            $search = new SearchWalletDTO($request->name);
            $wallets = $this->walletServices->search($search);
            return response()->json(
                WalletResource::collection($wallets), 
                Response::HTTP_OK
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id)
    {
        try {
            $wallet = $this->walletServices->show($id);
            return response()->json(
                new WalletResource($wallet), 
                Response::HTTP_OK
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StoreUpdateWalletRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateWalletDTO($validated['name']);
            $wallet = $this->walletServices->store($data);
            return response()->json(
                new WalletResource($wallet), 
                Response::HTTP_CREATED
            );
        } catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, StoreUpdateWalletRequest $request)
    {
        try {
            $validated = $request->validated();
            $data = new StoreUpdateWalletDTO($validated['name']);
            $wallet = $this->walletServices->update($id, $data);
            
            return response()->json(
                new WalletResource($wallet),
                Response::HTTP_OK
            );
        }
        catch (WalletException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->walletServices->destroy($id);
            return response()->json(['message' => 'Carteira deletada com sucesso'], Response::HTTP_OK);
        } catch (WalletException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
