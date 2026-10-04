<?php
declare(strict_types=1);
use App\Support\Http;
$canCancel = in_array($order['status'], ['Draft', 'PendingApproval', 'Approved'], true)
    && in_array($user['role'], ['Admin', 'Sales'], true)
    && ($user['role'] !== 'Sales' || (int) $order['created_by'] === (int) $user['id']);
?>
<section class="page-heading"><h1><?= Http::e($order['order_number']) ?></h1><p><?= Http::e($order['customer']) ?> &middot; <?= Http::e($order['warehouse']) ?> &middot; Status: <?= Http::e($order['status']) ?></p></section>
<div class="table responsive-table"><table><thead><tr><th>SKU</th><th>Produk</th><th>Jumlah</th><th>Harga</th></tr></thead><tbody>
<?php if ($order['items'] === []): ?><tr><td colspan="4" class="empty-state">Order ini belum memiliki item.</td></tr><?php else: foreach ($order['items'] as $item): ?><tr><td><?= Http::e($item['sku']) ?></td><td><?= Http::e($item['product_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td><?= number_format((float) $item['selling_price'], 0, ',', '.') ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div>
<div class="action-group workflow-actions">
    <?php if ($order['status'] === 'Draft' && ($user['role'] === 'Admin' || (int) $order['created_by'] === (int) $user['id'])): ?><form method="post" action="/sales-orders/workflow/<?= (int) $order['id'] ?>/submit"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button>Ajukan</button></form><?php endif; ?>
    <?php if ($user['role'] === 'Admin' && $order['status'] === 'PendingApproval'): ?><form method="post" action="/sales-orders/workflow/<?= (int) $order['id'] ?>/approve"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button>Setujui</button></form><form method="post" action="/sales-orders/workflow/<?= (int) $order['id'] ?>/reject"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><label for="reason">Alasan penolakan</label><input id="reason" name="reason" required><button class="button-danger">Tolak</button></form><?php endif; ?>
    <?php if (in_array($user['role'], ['Admin', 'WarehouseStaff'], true) && $order['status'] === 'Approved'): ?><form method="post" action="/sales-orders/workflow/<?= (int) $order['id'] ?>/fulfill"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button>Proses Goods Issue</button></form><?php endif; ?>
    <?php if ($canCancel): ?><form method="post" action="/sales-orders/workflow/<?= (int) $order['id'] ?>/cancel"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button class="button-danger">Batalkan Order</button></form><?php endif; ?>
</div>
