<?php
declare(strict_types=1);
use App\Support\Http;
use App\Support\DisplayText;
?>
<section class="page-heading"><h1>Riwayat Stok</h1><p>Setiap penerimaan dan pengeluaran dapat ditelusuri.</p></section>
<form class="panel filters" method="get" action="/stock-ledger">
    <label for="start">Dari tanggal</label><input id="start" type="date" name="start" value="<?= Http::e($start) ?>">
    <label for="end">Sampai tanggal</label><input id="end" type="date" name="end" value="<?= Http::e($end) ?>">
    <button type="submit">Filter</button>
    <a class="button button-secondary" href="/reports/stock-ledger.csv?start=<?= Http::e($start) ?>&amp;end=<?= Http::e($end) ?>">Unduh CSV Ledger</a>
    <?php if (($user['role'] ?? '') === 'Admin'): ?><a class="button button-secondary" href="/reports/order-status.csv?start=<?= Http::e($start) ?>&amp;end=<?= Http::e($end) ?>">Unduh CSV Order</a><?php endif; ?>
</form>
<div class="table responsive-table"><table><thead><tr><th>Tanggal</th><th>Produk</th><th>Gudang</th><th>Jenis</th><th>Jumlah</th><th>Sebelum</th><th>Sesudah</th><th>Referensi</th></tr></thead><tbody>
<?php if ($rows === []): ?><tr><td colspan="8" class="empty-state">Tidak ada pergerakan stok pada rentang tanggal tersebut.</td></tr><?php else: foreach ($rows as $row): ?><tr>
    <td data-label="Tanggal"><?= Http::e($row['created_at']) ?></td><td data-label="Produk"><?= Http::e($row['sku'] . ' - ' . $row['product']) ?></td><td data-label="Gudang"><?= Http::e($row['warehouse']) ?></td><td data-label="Jenis"><?= Http::e(DisplayText::movementType((string) $row['movement_type'])) ?></td><td data-label="Jumlah"><?= (int) $row['quantity'] ?></td><td data-label="Sebelum"><?= (int) $row['quantity_before'] ?></td><td data-label="Sesudah"><?= (int) $row['quantity_after'] ?></td><td data-label="Referensi"><?= Http::e(DisplayText::referenceType((string) $row['reference_type']) . '-' . $row['reference_id']) ?></td>
</tr><?php endforeach; endif; ?></tbody></table></div>
