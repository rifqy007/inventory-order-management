<?php

declare(strict_types=1);

use App\Support\Http;
use App\Support\RoutePath;
use App\Support\TableView;

$pages = max(1, (int) ceil($total / $filters['per_page']));
$query = $filters;
unset($query['owner']);


?>
<section class="page-heading"><div><span class="eyebrow">PENJUALAN</span><h1>Sales Order</h1><p><?= $user['role'] === 'WarehouseStaff' ? 'Lihat pesanan yang disetujui dan proses pengeluaran barang.' : 'Kelola pesanan penjualan dan pantau proses persetujuannya.' ?></p></div><?php if (in_array($user['role'], ['Admin', 'Sales'], true)) :
    ?><a class="button" href="<?= RoutePath::SALES_WORKFLOW ?>/new">＋ Buat Sales Order</a><?php
                                                                                                endif; ?></section>
<details class="filter-disclosure" <?= ($filters['q'] !== '' || $filters['status'] !== '') ? 'open' : '' ?>>
    <summary><span class="filter-summary-icon">⌕</span><span><strong>Cari dan filter pesanan</strong><small>Nomor order, customer, dan status</small></span><span class="summary-chevron">⌄</span></summary>
    <form class="filter-content" method="get" action="<?= RoutePath::SALES_WORKFLOW ?>">
        <div class="filter-fields">
            <label for="q">Nomor order atau customer<input id="q" name="q" value="<?= Http::e($filters['q']) ?>" placeholder="Cari nomor atau nama customer" autocomplete="off"></label>
            <label for="status">Status pesanan<select id="status" name="status"><option value="">Semua status</option><?php foreach (['Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled'] as $status) :
                ?><option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Http::e(match ($status) {
                'PendingApproval' => 'Menunggu persetujuan', 'Approved' => 'Disetujui', 'Fulfilled' => 'Selesai', 'Cancelled' => 'Dibatalkan', default => $status
                }) ?></option><?php
                                                                                                                      endforeach; ?></select></label>
        </div>
        <input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>"><input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>"><input type="hidden" name="per_page" value="<?= (int) $filters['per_page'] ?>">
        <div class="filter-actions"><input type="hidden" name="page" value="1"><button type="submit">Terapkan pencarian</button><a class="button button-secondary" href="<?= RoutePath::SALES_WORKFLOW ?>">Reset</a></div>
    </form>
</details>
<div class="table responsive-table"><table><thead><tr><?= TableView::sortHeader($filters, 'order_number', 'Nomor', RoutePath::SALES_WORKFLOW, true) ?><?= TableView::sortHeader($filters, 'customer', 'Customer', RoutePath::SALES_WORKFLOW, true) ?><?= TableView::sortHeader($filters, 'creator', 'Pembuat', RoutePath::SALES_WORKFLOW, true) ?><?= TableView::sortHeader($filters, 'order_date', 'Tanggal', RoutePath::SALES_WORKFLOW, true) ?><?= TableView::sortHeader($filters, 'status', 'Status', RoutePath::SALES_WORKFLOW, true) ?><th>Aksi</th></tr></thead><tbody>
<?php if ($rows === []) :
    ?><tr><td colspan="6" class="empty-state">Belum ada Sales Order yang cocok.</td></tr><?php
else :
    foreach ($rows as $r) :
        ?><tr>
    <td data-label="Nomor"><?= Http::e($r['order_number']) ?></td><td data-label="Customer"><?= Http::e($r['customer']) ?></td><td data-label="Pembuat"><?= Http::e($r['creator']) ?></td><td data-label="Tanggal"><?= Http::e($r['order_date']) ?></td><td data-label="Status"><span class="badge"><?= Http::e($r['status']) ?></span></td><td data-label="Aksi"><a class="button button-secondary" href="<?= RoutePath::SALES_WORKFLOW ?>/<?= (int) $r['id'] ?>">Detail</a></td>
</tr>
    <?php endforeach;
endif; ?></tbody></table></div>
<div class="table-controls"><form class="table-footer" method="get" action="<?= RoutePath::SALES_WORKFLOW ?>">
    <label for="rows-per-page">Baris per halaman<select id="rows-per-page" name="per_page"><?php foreach ([10, 25, 50, 100] as $size) :
        ?><option value="<?= $size ?>" <?= $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php
                                                                                           endforeach; ?></select></label>
    <input type="hidden" name="q" value="<?= Http::e($filters['q']) ?>"><input type="hidden" name="status" value="<?= Http::e($filters['status']) ?>"><input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>"><input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>"><input type="hidden" name="page" value="1"><button class="secondary" type="submit">Terapkan</button>
</form>
<nav class="pagination" aria-label="Navigasi halaman Sales Order"><?php if ($filters['page'] > 1) :
    $query['page']--; ?><a href="<?= RoutePath::SALES_WORKFLOW ?>?<?= Http::e(http_build_query($query)) ?>">Sebelumnya</a><?php
                                                                  endif; ?><span>Halaman <?= (int) $filters['page'] ?> dari <?= $pages ?></span><?php if ($filters['page'] < $pages) :
    $query['page'] = $filters['page'] + 1; ?><a href="<?= RoutePath::SALES_WORKFLOW ?>?<?= Http::e(http_build_query($query)) ?>">Berikutnya</a><?php
                                                                  endif; ?></nav></div>
