<?php

$data = [];

while ($row = mysqli_fetch_assoc($products)) {

    $pid = $row['p_id'];

    if (!isset($data[$pid])) {
        $data[$pid] = [
            'p_id' => $row['p_id'],
            'product_name' => $row['product_name'],
            'batches' => []
        ];
    }

    $data[$pid]['batches'][] = [
        'batch_id' => $row['batch_id'],
        'batch_no' => $row['batch_no'],
        'pts'      => $row['pts']
    ];
}

$sr = 1;

foreach ($data as $product) {
?>

<tr data-pid="<?= $product['p_id']; ?>">

    <td><?= $sr++; ?></td>

    <td>
        <?= htmlspecialchars($product['product_name']); ?>
        <input type="hidden" name="product[]" value="<?= $product['p_id']; ?>">
    </td>

    <td>
        <select name="batch_id[]" class="form-control batch-select">
            <option value="">Select Batch</option>

            <?php foreach ($product['batches'] as $batch) { ?>

                <option value="<?= $batch['batch_id']; ?>"
                        data-batchno="<?= htmlspecialchars($batch['batch_no']); ?>">
                    <?= htmlspecialchars($batch['batch_no']); ?>
                    (PTS: <?= number_format($batch['pts'],2); ?>)
                </option>

            <?php } ?>

        </select>
    </td>

    <td>
        <input type="number"
               class="form-control current-qty"
               value="0"
               readonly
               style="background:#f5f5f5;font-weight:bold;">
    </td>

    <td>
        <input type="number"
               name="qty[]"
               class="form-control qty-input"
               min="0"
               value="0">
    </td>

</tr>

<?php } ?>