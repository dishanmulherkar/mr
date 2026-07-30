<?php

$group=[];

while($r=mysqli_fetch_assoc($products))
{
    $pid=$r['p_id'];

    if(!isset($group[$pid]))
    {
        $group[$pid]=[
            'product_code'=>$r['product_code'],
            'product_name'=>$r['product_name'],
            'batches'=>[]
        ];
    }

    $group[$pid]['batches'][]=$r;
}

$sr=1;

foreach($group as $pid=>$row)
{
?>
<tr data-pid="<?= $pid ?>">

<td><?= $sr++ ?></td>

<td>


<?= $row['product_name'] ?>

<input type="hidden"
name="product[]"
value="<?= $pid ?>">

</td>

<td>

<select
name="batch_id[]"
class="form-control batch-select">

<option value="">Select Batch</option>

<?php
foreach($row['batches'] as $b)
{
?>

<option
value="<?= $b['batch_id']?>"
data-batchno="<?= $b['batch_no']?>">

<?= $b['batch_no']?> (PTS <?= number_format($b['pts'],2)?>)

</option>

<?php
}
?>

</select>

</td>

<td>

<input
type="number"
name="qty[]"
value="0"
min="0"
class="form-control qty-input">

</td>

</tr>

<?php
}
?>