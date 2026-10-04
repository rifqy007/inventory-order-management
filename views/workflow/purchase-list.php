<?php

declare(strict_types=1);

use App\Support\Http;
use App\Support\RoutePath;
use App\Support\TableView;

$pages = max(1, (int) ceil($total / $filters['per_page']));
$query = $filters;


?>
<section class="page-heading"><div><span class="eyebrow">PEMBELIAN</span><h1>Purchase Order</h1><p>Kelola pemesanan barang kepada supplier dan pantau penerimaan.</p></div><a class="button" href="<?= RoutePath::PURCHASE_WORKFLOW ?>/new">＋ Buat Purchase Order</a></section>
<details class="filter-disclosure" <?= ($filters['q'] !== '' || $filters['status'] !== '') ? 'open' : '' ?>>
    <summary><span class="filter-summary-icon">⌕</span><span><strong>Cari dan filter pesanan</strong><small>Nomor order, supplier, dan status</small></span><span class="summary-chevron">⌄</span></summary>
    <form class="filter-content" method="get" action="<?= RoutePath::PURCHASE_WORKFLOW ?>">
        <div class="filter-fields">
            <label for="q">Nomor order atau supplier<input id="q" name="q" value="<?= Http::e($filters['q']) ?>" placeholder="Cari nomor atau nama supplier" autocomplete="off"></label>
            <label for="status">Status pesanan<select id="status" name="status"><option value="">Semua status</option><?php foreach (['Draft', 'Ordered', 'PartiallyReceived', 'Received', 'Cancelled'] as $status) :
                ?><option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Http::e(match ($status) {
                'Ordered' => 'Dipesan', 'PartiallyReceived' => 'Diterima sebagian', 'Received' => 'Diterima', 'Cancelled' => 'Dibatalkan', default => $status
                }) ?></option><?php
                                                                                                                      endforeach; ?></select></label>
        </div>
        <input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>"><input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>"><input type="hidden" name="per_page" value="<?= (int) $filters['per_page'] ?>">
        <div class="filter-actions"><input type="hidden" name="page" value="1"><button type="submit">Terapkan pencarian</button><a class="button button-secondary" href="<?= RoutePath::PURCHASE_WORKFLOW ?>">Reset</a></div>
    </form>
</details>
<div class="table responsive-table"><table><thead><tr><?= TableView::sortHeader($filters, 'order_number', 'Nomor', RoutePath::PURCHASE_WORKFLOW) ?><?= TableView::sortHeader($filters, 'supplier', 'Supplier', RoutePath::PURCHASE_WORKFLOW) ?><?= TableView::sortHeader($filters, 'order_date', 'Tanggal', RoutePath::PURCHASE_WORKFLOW) ?><?= TableView::sortHeader($filters, 'status', 'Status', RoutePath::PURCHASE_WORKFLOW) ?><th>Aksi</th></tr></thead><tbody>
<?php if ($rows === []) :
    ?><tr><td colspan="5" class="empty-state">Belum ada Purchase Order yang cocok.</td></tr><?php
else :
    foreach ($rows as $r) :
        ?><tr>
    <td data-label="Nomor"><?= Http::e($r['order_number']) ?></td><td data-label="Supplier"><?= Http::e($r['supplier']) ?></td><td data-label="Tanggal"><?= Http::e($r['order_date']) ?></td><td data-label="Status"><span class="badge"><?= Http::e($r['status']) ?></span></td><td data-label="Aksi"><a class="button button-secondary" href="<?= RoutePath::PURCHASE_WORKFLOW ?>/<?= (int) $r['id'] ?>">Detail</a></td>
</tr>
    <?php endforeach;
endif; ?></tbody></table></div>
<div class="table-controls"><form class="table-footer" method="get" action="<?= RoutePath::PURCHASE_WORKFLOW ?>">
    <label for="rows-per-page">Baris per halaman<select id="rows-per-page" name="per_page"><?php foreach ([10, 25, 50, 100] as $size) :
        ?><option value="<?= $size ?>" <?= $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php
                                                                                           endforeach; ?></select></label>
    <input type="hidden" name="q" value="<?= Http::e($filters['q']) ?>"><input type="hidden" name="status" value="<?= Http::e($filters['status']) ?>"><input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>"><input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>"><input type="hidden" name="page" value="1"><button class="secondary" type="submit">Terapkan</button>
</form>
<nav class="pagination" aria-label="Navigasi halaman Purchase Order"><?php if ($filters['page'] > 1) :
    $query['page']--; ?><a href="<?= RoutePath::PURCHASE_WORKFLOW ?>?<?= Http::e(http_build_query($query)) ?>">Sebelumnya</a><?php
                                                                     endif; ?><span>Halaman <?= (int) $filters['page'] ?> dari <?= $pages ?></span><?php if ($filters['page'] < $pages) :
    $query['page'] = $filters['page'] + 1; ?><a href="<?= RoutePath::PURCHASE_WORKFLOW ?>?<?= Http::e(http_build_query($query)) ?>">Berikutnya</a><?php
                                                                     endif; ?></nav></div>
