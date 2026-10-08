<?php

declare(strict_types=1);

namespace App\Support;

final class DisplayText
{
    public static function orderStatus(string $status): string
    {
        return match ($status) {
            'Draft' => 'Draf',
            'PendingApproval' => 'Menunggu persetujuan',
            'Approved' => 'Disetujui',
            'Fulfilled' => 'Selesai',
            'Cancelled' => 'Dibatalkan',
            'Ordered' => 'Dipesan',
            'PartiallyReceived' => 'Diterima sebagian',
            'Received' => 'Diterima',
            default => $status,
        };
    }

    public static function movementType(string $type): string
    {
        return match ($type) {
            'Receipt' => 'Penerimaan',
            'Issue' => 'Pengeluaran',
            'Adjustment' => 'Penyesuaian',
            default => $type,
        };
    }

    public static function referenceType(string $type): string
    {
        return match ($type) {
            'SO' => 'Pesanan Penjualan',
            'PO' => 'Pesanan Pembelian',
            default => $type,
        };
    }
}
