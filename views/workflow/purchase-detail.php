<?php

declare(strict_types=1);

use App\Support\Http;

?>
<section class="page-heading"><h1><?= Http::e($order['order_number']) ?></h1><p><?= Http::e($order['supplier']) ?> &middot; <?= Http::e($order['warehouse']) ?> &middot; Status: <?= Http::e($order['status']) ?></p></section>
<form method="post" action="/purchase-orders/workflow/<?= (int) $order['id'] ?>/receive"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
<div class="table responsive-table"><table><thead><tr><th>SKU</th><th>Produk</th><th>Dipesan</th><th>Diterima</th><th>Terima Sekarang</th></tr></thead><tbody>
<?php if ($order['items'] === []) :
    ?><tr><td colspan="5" class="empty-state">Order ini belum memiliki item.</td></tr><?php
else :
    foreach ($order['items'] as $item) :
        ?><tr><td><?= Http::e($item['sku']) ?></td><td><?= Http::e($item['product_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td><?= (int) $item['received_quantity'] ?></td><td><label for="received-<?= (int) $item['id'] ?>" class="visually-hidden">Jumlah diterima untuk <?= Http::e($item['product_name']) ?></label><input id="received-<?= (int) $item['id'] ?>" type="number" min="0" max="<?= (int) $item['quantity'] - (int) $item['received_quantity'] ?>" name="received[<?= (int) $item['id'] ?>]" value="0"></td></tr><?php
    endforeach;
endif; ?>
</tbody></table></div>
<?php if ($order['items'] !== [] && in_array($order['status'], ['Ordered', 'PartiallyReceived'], true)) :
    ?><button type="submit">Catat Penerimaan</button><?php
endif; ?></form>
<?php if ($order['status'] === 'Draft') :
    ?><form method="post" action="/purchase-orders/workflow/<?= (int) $order['id'] ?>/order"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button>Kirim Purchase Order</button></form><?php
endif; ?>
<?php if (in_array($order['status'], ['Draft', 'Ordered', 'PartiallyReceived'], true)) :
    ?><form method="post" action="/purchase-orders/workflow/<?= (int) $order['id'] ?>/cancel"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button class="button-danger">Batalkan Purchase Order</button></form><?php
endif; ?>
