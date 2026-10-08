<?php

use App\Support\Http;
use App\Support\DisplayText; ?><h1>Daftar <?= $type === 'so' ? 'Pesanan Penjualan' : 'Pesanan Pembelian' ?></h1>
<div class="table">
    <table>
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody><?php foreach ($rows as $r): ?><tr>
                    <td><?= Http::e($r['order_number']) ?></td>
                    <td><span class="badge"><?= Http::e(DisplayText::orderStatus((string) $r['status'])) ?></span></td>
                    <td><?= Http::e($r['order_date']) ?></td>
                    <td><?php if ($type === 'so' && $user['role'] === 'Admin' && $r['status'] === 'PendingApproval'): ?><form method="post" action="/sales-orders/<?= $r['id'] ?>/approve"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button>Setujui</button></form><?php endif; ?></td>
                </tr><?php endforeach; ?></tbody>
    </table>
</div>
