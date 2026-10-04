<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\InventoryService;
use App\Repository\MysqlInventoryRepository;
use App\Support\Http;

final class InventoryController
{
    public function __construct(private InventoryService $service, private MysqlInventoryRepository $repo)
    {
    }
    public function form(): void
    {
        Http::requireRole(['Admin','WarehouseStaff']);
        Http::view('inventory/move', ['products' => $this->repo->products(),'warehouses' => $this->repo->all('warehouses')]);
    }
    public function move(): void
    {
        $u = Http::requireRole(['Admin','WarehouseStaff']);
        Http::verifyCsrf();
        try {
            $this->service->move((int)$_POST['product_id'], (int)$_POST['warehouse_id'], (string)$_POST['type'], (int)$_POST['quantity'], 'Manual', 0, $u['id']);
            Http::flash('success', 'Pergerakan stok berhasil dicatat.');
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
        } catch (\Throwable) {
            Http::flash('error', 'Pergerakan stok gagal dicatat. Coba lagi.');
        }Http::redirect('/inventory/move');
    }
}
