<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Monitoring</title>
</head>
<style>
    body {
        color: #2d3436;
        font-family: 'courier';
    }
    .header {
        text-align: center;
    }
    #tbl_body {
        border-collapse: collapse;
    }
    #tbl_body th, td{
        border: 1px solid #b2bec3;
        font-size: 9px;
        text-align: center;
    }
    #tbl_body th {
        background: #8F3F96;
        color: #fff;
    }
</style>
<body>
    <div class="header">
        <img src="<?= base_url('assets/img/jacks.png')?>" alt="Header-Img" width="200">
        <h2>SALES REPORT</h2>
        <h5><?= date('D F j, Y h:i a')?></h5>
        <hr>
    </div>
    <div class="body">
        <table id="tbl_body" width="100%">
            <tr>
                <th>Serial No.</th>
                <th>Date</th>
                <th>Package</th>
                <th style="width: 20%;">Guest/Kids Name</th>
                <th>Parent/Guardian</th>
                <th>Contact No.</th>
                <th style="width: 10%;">Category</th>
                <th style="width: 5%;">Amount</th>
                <th>Inventory Amount</th>
                <th>Discount Amount</th>
            </tr>
            <tbody>
                <?php foreach($transaction as $list) : ?>
                    <tr>
                        <td><?= $list->slip_app_no;?></td>
                        <td style="width: 100px;"><?= date('M j, Y', strtotime($list->date_added));?></td>
                        <td style="width: 150px;"><?= $list->admission_type;?></td>
                        <?php
                            if ($list->service == 'INFLATABLES') {
                                $children = $this->db
                                    ->select("CONCAT(child_fname, ' ', child_lname) AS children")
                                    ->from('guest_children')
                                    ->where('parent_id', $list->guest_id)
                                    ->get()
                                    ->result_array();
                                $children_names = "";
                                foreach ($children as $child) {
                                    $children_names .= $child['children'] . "<br>";
                                }
                                $kids = ucwords($children_names);
                            } else {
                                $kids = $list->guest_fname. ' ' .$list->guest_lname;
                            }
                        ?>
                        <?php
                            $sales = $this->db
                                ->select("SUM(total_amt) as total_sales")
                                ->from('consumable_stocks')
                                ->where('type_id', 0)
                                ->get()
                                ->row();

                            $inv_sales = $this->db
                                ->select("SUM(total_amt) as total_inv")
                                ->from('consumable_stocks')
                                ->where('type_id !=', 0)
                                ->get()
                                ->row();

                            $discount = $this->db
                                ->select('discount_amt')
                                ->from('consumable_stocks')
                                ->group_by('serial_no')
                                ->get()
                                ->row();

                            //Per row
                            $sales_guest = $this->db
                                ->select("SUM(total_amt) as total_sales")
                                ->from('consumable_stocks')
                                ->where('type_id', 0)
                                ->where('guest_id', $list->guest_id)
                                ->get()
                                ->row();

                            $inv_guest = $this->db
                                ->select("SUM(total_amt) as inv_sales")
                                ->from('consumable_stocks')
                                ->where('type_id !=', 0)
                                ->where('guest_id', $list->guest_id)
                                ->get()
                                ->row();

                            $discount_guest = $this->db
                                ->select('discount_amt')
                                ->from('consumable_stocks')
                                ->where('guest_id', $list->guest_id)
                                ->group_by('serial_no')
                                ->get()
                                ->row();
                        ?>

                        <td><?= ucwords($kids)?></td>
                        <td><?= ucwords($list->guest_fname);?> <?= ucwords($list->guest_lname);?></td>
                        <td><?= $list->contact_no;?></td>
                        <td><?= $list->service;?></td>
                        <td><?= number_format($sales_guest->total_sales, 2)?></td>
                        <td><?= number_format($inv_guest->inv_sales, 2)?></td>
                        <td><?= number_format($discount_guest->discount_amt, 2)?></td>
                    </tr>
                <?php endforeach;?>
            </tbody>
        </table>
        <hr>
        <div class="total" style="text-align: right;">
            <h5 style="margin-bottom:5px;">Sales: ₱ <?= number_format($sales->total_sales, 2);?></h5>
            <h5>Inventory Sales: ₱ <?= number_format($inv_sales->total_inv, 2);?></h5>
            <h5>Discount: ₱ -<?= number_format($discount->discount_amt, 2);?></h5>
            <?php
                $total = $sales->total_sales + $inv_sales->total_inv - $discount->discount_amt;
            ?>
            <h4>Total Sales: ₱ <?= number_format($total, 2)?></h4>
        </div>
    </div>
</body>
</html>