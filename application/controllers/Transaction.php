<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Transaction extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Manila');
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->database();
        $this->load->model('Transaction_model', 'transaction');
    } //End __construct

    public function get_sales()
    {
        $guest = $this->transaction->get_sales();
        $data = array();
        $no = $_POST['start'];
        $total_amount = 0;
        $inv_sales = 0;
        $total_sales = 0;
        $no_transaction = 0;
        $total_discount = 0;
        $total_inv_sales = 0;
        $total_amount_sales = 0;

        $total_sales_amount= 0;
        $amount_sales = 0;
        $amount_sales_void = 0;
        foreach ($guest as $list) {
            $no++;
            $row = array();

            $sales = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                // ->where('guest_id', $list->guest_id)
                ->where('transaction_no', $list->transaction_no)
                ->group_by('serial_no')
                ->get()
                ->row();
            $total_amount += $sales->total_sales;
            
            $sales_amount = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                ->where('transaction_no', $list->transaction_no)
                // ->where('status', 0)
                ->get()
                ->row();
            $amount_sales += $sales_amount->total_sales;

            $sales_amount_void = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('status', 2)
                ->get()
                ->row();
            

            $inv = $this->db
                ->select("SUM(total_amt) as inv_sales")
                ->from('consumable_stocks')
                ->where('type_id !=', 0)
                // ->where('guest_id', $list->guest_id)
                ->where('transaction_no', $list->transaction_no)
                ->get()
                ->row();
            $inv_sales += $inv->inv_sales;

            $discount = $this->db
                ->select('discount_amt')
                ->from('consumable_stocks')
                // ->where('guest_id', $list->guest_id)
                ->where('transaction_no', $list->transaction_no)
                ->group_by('serial_no')
                ->get()
                ->row();
            $total_discount += $discount->discount_amt;

            //Void
            $total_discount_void = 0;
            $total_amount_void = 0;
            $discount_void = $this->db
                ->select('discount_amt')
                ->from('consumable_stocks')
                ->where('transaction_no', $list->transaction_no)
                ->where('status', 2)
                ->group_by('serial_no')
                ->get()
                ->row();

            if ($discount_void) {
                $total_discount_void += $discount_void->discount_amt;
            }

            $sales_void = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                ->where('status', 2)
                // ->where('transaction_no', $list->transaction_no)
                ->get()
                ->row();
            $total_amount_void += $sales_void->total_sales;

            $inv_void = $this->db
                ->select("SUM(total_amt) as inv_sales")
                ->from('consumable_stocks')
                ->where('type_id !=', 0)
                ->where('status', 2)
                ->where('transaction_no', $list->transaction_no)
                ->get()
                ->row();

            $sales_void = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                ->where('status', 2)
                ->where('transaction_no', $list->transaction_no)
                ->get()
                ->row();
            //End Void
            $total_sales_amount = $sales->total_sales + $inv->inv_sales - $discount->discount_amt;

            $row[] = '<button class="btn btn-secondary btn-sm view" 
                     id="'.$list->slip_app_no.'" 
                     data-child="'.$list->child_id.'" 
                     data-service="'.$list->service.'" 
                     data-con_id="'.$list->con_id.'" 

                     data-sales="'.$sales->total_sales.'"
                     data-inv="'.$inv->inv_sales.'"
                     data-discount="'.$discount->discount_amt.'"
                     data-total_sales="'.$total_sales_amount.'"
                     title="View"><i class="bi bi-eye-fill"></i></button>
                      <button class="btn btn-primary btn-sm print" id="'.$list->transaction_no.'" data-child="'.$list->child_id.'" title="Print"><i class="bi bi-printer-fill"></i></button>
                      <button '.($list->status == 2  ? 'disabled' : '').' class="btn btn-danger btn-sm void" id="'.$list->slip_app_no.'" data-trans="'.$list->transaction_no.'" data-child="'.$list->guest_child_id.'" data-service="'.$list->service.'" title="Void"><i class="bi bi-x-square-fill"></i></button>';
            $row[] = $list->transaction_no;
            $row[] = $list->slip_app_no;
            $row[] = date('F j, Y', strtotime($list->date_added));
            $row[] = $list->service;
            
            $row[] = date('g:i a', strtotime($list->time_in));

            if($list->extend_time == NULL) {
                $row[] = date('g:i a', strtotime($list->time_out));
            } else {
                $row[] = date('g:i a', strtotime($list->extend_time));
            }

            

            if ($list->extended == 'YES') {
                $row[] = 'Extended';
            } else {
                $row[] = '';
            }
            
            $row[] = $list->guest_fname. ' ' .$list->guest_lname;
            $row[] = $list->qty;

            $row[] = number_format($sales->total_sales, 2);
            $row[] = number_format($inv->inv_sales, 2);

            $row[] = number_format($discount->discount_amt, 2);

            
            $row[] = number_format($total_sales_amount, 2);
            
            
            
            $total_inv_sales = $inv_sales;
            $total_amount_sales = $total_amount - $sales_void->total_sales - $total_amount_void;
            $this->db->from('consumable_stocks');
            $this->db->group_by('transaction_no');
            $no_transaction = $this->db->count_all_results();

            if ($list->status == 2) {
                $row[] = 'Voided';
                $amount_sales_void += $total_sales_amount;
            } else {
                $row[] = '';
            }
            $row[] = $list->staff_in_charge;
            // $amount_sales_void = $sales_amount_void->total_sales - $total_discount;
            
            //$total_sales = $total_amount + $inv_sales - $total_amount_void - $inv_void->inv_sales - $total_discount;
            $total_sales = $amount_sales + $total_inv_sales - $amount_sales_void - $total_discount;

            $data[] = $row;
        }
        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->transaction->count_all(),
            "recordsFiltered" => $this->transaction->count_filtered(),
            "data" => $data, 
            "totalAmount" => number_format($amount_sales, 2),
            "totalAmount_void" => number_format($amount_sales_void, 2),
            "totalInv" => number_format($total_inv_sales, 2),
            "totalSales" => number_format($total_sales, 2),
            "total_discount" => number_format($total_discount, 2),
            "no_transaction" => number_format($no_transaction),
        );
        echo json_encode($output);
    }

    public function get_sales_amount()
    {
        $sales = $this->input->post('sales');

        if ($sales == 0) {
            $sales = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                ->get()
                ->row();

            $inv_sales = $this->db
                ->select("SUM(total_amt) as total_inv_sales")
                ->from('consumable_stocks')
                ->where('type_id !=', 0)
                ->get()
                ->row();

            $discount = $this->db
                ->select_sum("discount_amt", "total_discount")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                ->get()
                ->row();

            $void = $this->db
                ->select("SUM(total_amt) as total_void")
                ->from('consumable_stocks')
                ->where('status', 2)
                ->get()
                ->row();

            $total_sales_amount = 0;

            $total_sales_amount = $sales->total_sales + $inv_sales->total_inv_sales - $void->total_void - $discount->total_discount;
        }
        $output = array(
            'totalAmount' => number_format($sales->total_sales, 2),
            'totalInv' => number_format($inv_sales->total_inv_sales, 2),
            'total_discount' => number_format($discount->total_discount, 2),
            'totalAmount_void' => number_format($void->total_void, 2),
            'totalSales' => number_format($total_sales_amount, 2),
        );
        echo json_encode($output);
    }

    public function sales_report()
    {
        require_once 'vendor/autoload.php';
        $dt_from = $this->uri->segment(3);
        $dt_to = $this->uri->segment(4);

        $data['transaction'] = $this->transaction->get_transaction($dt_from, $dt_to);
        $data['dt_from'] = $dt_from;
        $data['dt_to'] = $dt_to;
        $mpdf = new \Mpdf\Mpdf( [ 
            'format' => 'A4-L',
            'margin_top' => 5,
            'margin_bottom' => 40,
        ]);
        // Enable auto-adjustment of top and bottom margins
        $mpdf->showImageErrors = true;
        $mpdf->showWatermarkImage = true;
        $html = $this->load->view('pdf/sales_report', $data, true );
        $mpdf->WriteHTML( $html );
        $mpdf->Output();
    }

    public function get_guest_data()
    {
        $output = '';
        $output_parent = '';
        $output_time_info = '';
        $serial_no = $this->input->post('serial_no');
        $service = $this->input->post('service');

        switch ($service) {
            case 'INFLATABLES':
                $query = $this->db
                    ->select('G.*')
                    ->select("CONCAT(GC.child_fname, ' ',GC.child_lname) as children, GC.child_age, GC.child_img")
                    ->from('guest_details G')
                    ->join('guest_children GC', 'G.guest_id = GC.parent_id', 'LEFT')
                    ->where('G.slip_app_no', $serial_no)
                    ->get();
                
                if ($query->num_rows() > 0) {
                    foreach ($query->result() as $list) {
                       if ($list->child_img == '' || $list->child_img == NULL) {
                         $profile_child = base_url('assets/img/avatar.png');
                       } else {
                         $profile_child = base_url($list->child_img);
                       }
                       $output .= '
                        <div class="d-flex align-items-center">
                            <img class="box-img" src="'.$profile_child.'" alt="Profile-Pic">
                            <div class="ms-3">
                                <h5 class="mb-0">Serial Number:</h5>
                                <h4 class="mb-0"><b style="color:#8E3C95;">'.$list->slip_app_no.' / '.$list->service.'</b></h4>
                            
                                <h4 class="mb-0 text-muted">'.ucwords($list->children).'</h4>
                                <b class="mb-0 text-muted">'.$list->child_age.'</b>
                            </div>
                        </div>
                        <hr>
                       ';
                    }
                }
                $parent = $query->row();
                if ($parent->picture == '' || $parent->picture == NULL) {
                    $profile_parent = base_url('assets/img/avatar.png');
                } else {
                    $profile_parent = base_url($parent->picture);
                }
                $output_parent .= '
                <div class="d-flex align-items-center">
                    <img class="box-img" src="'.$profile_parent.'" alt="Profile-Pic">
                    <div class="ms-3">
                        <h5 class="mb-0">Serial Number:</h5>
                        <h4 class="mb-0"><b style="color:#8E3C95;">'.$parent->slip_app_no.' / '.$parent->service.'</b></h4>
                    
                        <h4 class="mb-0 text-muted">'.ucwords($parent->guest_fname).' '.ucwords($parent->guest_lname).'</h4>
                        <b class="mb-0 text-muted">'.$parent->guest_age.'</b>
                    </div>
                </div>
                <hr>
                ';

                //Package Details
                $time_info = $this->db
                    ->select('TM.*')
                    ->select('P.admission_type, P.time_admission, P.weekdays_price')
                    ->from('time_management TM')
                    ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
                    ->where('TM.serial_no', $parent->slip_app_no)
                    ->get()
                    ->row();
                // Calculate remaining time in seconds
                if ($time_info->extend_time == NULL) {
                    $time_out = date('g:i A', strtotime($time_info->time_out));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->time_out) - time();
                    } else {
                        $remaining_time = 0;
                    }
                } else {
                    $time_out = date('g:i A', strtotime($time_info->extend_time));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->extend_time) - time();
                    } else {
                        $remaining_time = 0;
                    }
                }

                // Format remaining time as HH:MM:SS
                //$remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                if ($remaining_time < 0) {
                    $remaining_time_formatted = '00:00:00';
                } else {
                    $remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                }
                
                if ($remaining_time < 300) {
                    $disabled = 'disabled';
                } else {
                    $disabled = '';
                }
                //$row[] = '<span class="remaining-time" data-remaining-time="' . $remaining_time . '">' . $remaining_time_formatted . '</span>';;
                
                $trans_no = $this->db
                    ->select('transaction_no')
                    ->from('consumable_stocks')
                    ->where('serial_no', $parent->slip_app_no)
                    ->group_by('transaction_no')
                    ->get()
                    ->row();

                $output_time_info .= '
                    <div class="form-group mb-3">
                        <label>Package:</label>
                        <h5>'.$time_info->admission_type.'</h5>
                    </div>
                    <div class="form-group">
                        <label>Remaining Time (HH:MM:SS):</label>
                        <h5 style="color:#d63031;">'.$remaining_time_formatted.'</h5>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time In:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_in)).'</h5>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time Out:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_out)).'</h5>
                            </div>
                        </div>
                    </div>
                    <hr>

                    <div class="text-center">
                        <label>Total Amount</label>
                        <h4>P '.number_format($time_info->weekdays_price, 2).'</h4>
                    </div>
                    <div class="mx-auto">
                        <button class="btn btn-danger w-100 btn-rounded void_trans" data-id="'.$trans_no->transaction_no.'">VOID THIS TRANSACTION</button>
                    </div>
                    <hr>
                ';
                break;

            case 'PARK':
                $query = $this->db
                    ->select('G.*')
                    ->from('guest_details G')
                    ->where('G.slip_app_no', $serial_no)
                    ->get();
                if ($query->num_rows() > 0) {
                    $parent = $query->row();

                    if ($parent->picture == '' || $parent->picture == NULL) {
                        $profile_parent = base_url('assets/img/avatar.png');
                    } else {
                        $profile_parent = base_url($parent->picture);
                    }
                    $output_parent .= '
                        <div class="row">
                            <div class="col-3">
                                <img class="box-img" src="'.$profile_parent.'" alt="Profile-Pic">
                            </div>
                            <div class="col-5">
                                <div class="mb-0">Serial Number:</div>
                                <div class="mb-0"><b>'.$parent->slip_app_no.' / '.$parent->service.'</b></div>
                                <div class="mb-0">Guest / Kids Name</div>
                                <div class="mb-0 text-muted">'.ucwords($parent->guest_fname).' '.ucwords($parent->guest_lname).'</div>
                                <b class="mb-0 text-muted">'.$parent->guest_age.'</b>
                            </div>
                        </div>
                        <hr>
                    ';

                //Package Details
                $time_info = $this->db
                    ->select('TM.*')
                    ->select('P.admission_type, P.time_admission, P.weekdays_price')
                    ->from('time_management TM')
                    ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
                    ->where('TM.serial_no', $parent->slip_app_no)
                    ->get()
                    ->row();
                // Calculate remaining time in seconds
                if ($time_info->extend_time == NULL) {
                    $time_out = date('g:i A', strtotime($time_info->time_out));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->time_out) - time();
                    } else {
                        $remaining_time = 0;
                    }
                } else {
                    $time_out = date('g:i A', strtotime($time_info->extend_time));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->extend_time) - time();
                    } else {
                        $remaining_time = 0;
                    }
                }

                // Format remaining time as HH:MM:SS
                //$remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                if ($remaining_time < 0) {
                    $remaining_time_formatted = '00:00:00';
                } else {
                    $remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                }
                
                if ($remaining_time < 300) {
                    $disabled = 'disabled';
                } else {
                    $disabled = '';
                }
                //$row[] = '<span class="remaining-time" data-remaining-time="' . $remaining_time . '">' . $remaining_time_formatted . '</span>';;
                $trans_no = $this->db
                    ->select('transaction_no')
                    ->from('consumable_stocks')
                    ->where('serial_no', $parent->slip_app_no)
                    ->group_by('transaction_no')
                    ->get()
                    ->row();

                $output_time_info .= '
                    <div class="form-group mb-3">
                        <label>Package:</label>
                        <h5>'.$time_info->admission_type.'</h5>
                    </div>
                    <div class="form-group">
                        <label>Remaining Time (HH:MM:SS):</label>
                        <h5 style="color:#d63031;">'.$remaining_time_formatted.'</h5>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time In:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_in)).'</h5>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time Out:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_out)).'</h5>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center hide_data">
                        <label>Total Amount</label>
                        <h4>P '.number_format($time_info->weekdays_price, 2).'</h4>
                    </div>
                    <div class="mx-auto hide_data">
                        <button class="btn btn-danger w-100 btn-rounded void_trans" data-id="'.$trans_no->transaction_no.'">VOID THIS TRANSACTION</button>
                    </div>
                    <hr>
                ';
                }
                break;
        }

        $data = array(
            'children_info' => $output,
            'parent_guardian' => $output_parent,
            'time_info' => $output_time_info,
            'date_added' => date('F j, Y', strtotime($time_info->date_added)) . ' ' . date('g:i A', strtotime($time_info->time_in)),
        );
        echo json_encode($data);
    }

    public function get_guest_info()
    {
        $output = '';
        $output_parent = '';
        $output_time_info = '';
        $serial_no = $this->input->post('serial_no');
        $service = $this->input->post('service');
        $con_id = $this->input->post('con_id');

        switch ($service) {
            case 'INFLATABLES':
                $query = $this->db
                    ->select('G.*')
                    ->select("CONCAT(GC.child_fname, ' ',GC.child_lname) as children, GC.child_age, GC.child_img")
                    ->from('guest_details G')
                    ->join('guest_children GC', 'G.guest_id = GC.parent_id', 'LEFT')
                    ->where('G.slip_app_no', $serial_no)
                    ->get();
                
                if ($query->num_rows() > 0) {
                    foreach ($query->result() as $list) {
                       if ($list->child_img == '' || $list->child_img == NULL) {
                         $profile_child = base_url('assets/img/avatar.png');
                       } else {
                         $profile_child = base_url($list->child_img);
                       }
                       $output .= '
                        <div class="d-flex align-items-center">
                            <img class="box-img" src="'.$profile_child.'" alt="Profile-Pic">
                            <div class="ms-3">
                                <h5 class="mb-0">Serial Number:</h5>
                                <h4 class="mb-0"><b style="color:#8E3C95;">'.$list->slip_app_no.' / '.$list->service.'</b></h4>
                            
                                <h4 class="mb-0 text-muted">'.ucwords($list->children).'</h4>
                                <b class="mb-0 text-muted">'.$list->child_age.'</b>
                            </div>
                        </div>
                        <hr>
                       ';
                    }
                }
                $parent = $query->row();
                if ($parent->picture == '' || $parent->picture == NULL) {
                    $profile_parent = base_url('assets/img/avatar.png');
                } else {
                    $profile_parent = base_url($parent->picture);
                }
                $output_parent .= '
                <div class="d-flex align-items-center">
                    <img class="box-img" src="'.$profile_parent.'" alt="Profile-Pic">
                    <div class="ms-3">
                        <h5 class="mb-0">Serial Number:</h5>
                        <h4 class="mb-0"><b style="color:#8E3C95;">'.$parent->slip_app_no.' / '.$parent->service.'</b></h4>
                    
                        <h4 class="mb-0 text-muted">'.ucwords($parent->guest_fname).' '.ucwords($parent->guest_lname).'</h4>
                        <b class="mb-0 text-muted">'.$parent->guest_age.'</b>
                    </div>
                </div>
                <hr>
                ';

                //Package Details
                $time_info = $this->db
                    ->select('TM.*')
                    ->select('P.admission_type, P.time_admission, P.weekdays_price')
                    ->from('time_management TM')
                    ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
                    ->where('TM.serial_no', $parent->slip_app_no)
                    ->get()
                    ->row();
                // Calculate remaining time in seconds
                if ($time_info->extend_time == NULL) {
                    $time_out = date('g:i A', strtotime($time_info->time_out));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->time_out) - time();
                    } else {
                        $remaining_time = 0;
                    }
                } else {
                    $time_out = date('g:i A', strtotime($time_info->extend_time));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->extend_time) - time();
                    } else {
                        $remaining_time = 0;
                    }
                }

                // Format remaining time as HH:MM:SS
                //$remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                if ($remaining_time < 0) {
                    $remaining_time_formatted = '00:00:00';
                } else {
                    $remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                }
                
                if ($remaining_time < 300) {
                    $disabled = 'disabled';
                } else {
                    $disabled = '';
                }

                $sales = $this->db
                    ->select("SUM(total_amt) as sales")
                    ->from('consumable_stocks')
                    ->where('con_id', $con_id)
                    ->get()
                    ->row();

                $discount = $this->db
                    ->select('discount_amt AS discount')
                    ->from('consumable_stocks')
                    ->where('con_id', $con_id)
                    ->group_by('serial_no')
                    ->get()
                    ->row();
                
                $total_sales = $sales->sales - $discount->discount;

                //$row[] = '<span class="remaining-time" data-remaining-time="' . $remaining_time . '">' . $remaining_time_formatted . '</span>';;
                $sales_amount = $this->input->post('sales');
                $inv_amount = $this->input->post('inv');
                $discount_amount = $this->input->post('discount');
                $total_sales_amount = $this->input->post('total_sales');

                $floatValue = floatval($inv_amount);
                $formattedValue = number_format($floatValue, 2);
                $output_time_info .= '
                    <div class="form-group mb-3">
                        <label>Package:</label>
                        <h5>'.$time_info->admission_type.'</h5>
                    </div>
                    <div class="form-group">
                        <label>Remaining Time (HH:MM:SS):</label>
                        <h5 style="color:#d63031;">'.$remaining_time_formatted.'</h5>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time In:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_in)).'</h5>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time Out:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_out)).'</h5>
                            </div>
                        </div>
                    </div>
                    <hr>

                    <div class="text-center">
                        <span>Sales: <b>₱ '.number_format($sales_amount, 2).'</b></span><br>
                        <span>Inventory Sales: <b>₱ '.$formattedValue.'</b></span><br>
                        <span>Discount: <b>₱ -'.number_format($discount_amount, 2).'</b></span><br>
                        <hr>
                        <h4>Total Sales: ₱ '.number_format($total_sales_amount, 2).'</h4>
                    </div>
                    
                ';
                break;

            case 'PARK':
                $query = $this->db
                    ->select('G.*')
                    ->from('guest_details G')
                    ->where('G.slip_app_no', $serial_no)
                    ->get();
                if ($query->num_rows() > 0) {
                    $parent = $query->row();

                    if ($parent->picture == '' || $parent->picture == NULL) {
                        $profile_parent = base_url('assets/img/avatar.png');
                    } else {
                        $profile_parent = base_url($parent->picture);
                    }
                    $output_parent .= '
                        <div class="row">
                            <div class="col-3">
                                <img class="box-img" src="'.$profile_parent.'" alt="Profile-Pic">
                            </div>
                            <div class="col-5">
                                <div class="mb-0">Serial Number:</div>
                                <div class="mb-0"><b>'.$parent->slip_app_no.' / '.$parent->service.'</b></div>
                                <div class="mb-0">Guest / Kids Name</div>
                                <div class="mb-0 text-muted">'.ucwords($parent->guest_fname).' '.ucwords($parent->guest_lname).'</div>
                                <b class="mb-0 text-muted">'.$parent->guest_age.'</b>
                            </div>
                        </div>
                        <hr>
                    ';

                //Package Details
                $time_info = $this->db
                    ->select('TM.*')
                    ->select('P.admission_type, P.time_admission, P.weekdays_price')
                    ->from('time_management TM')
                    ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
                    ->where('TM.serial_no', $parent->slip_app_no)
                    ->get()
                    ->row();
                // Calculate remaining time in seconds
                if ($time_info->extend_time == NULL) {
                    $time_out = date('g:i A', strtotime($time_info->time_out));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->time_out) - time();
                    } else {
                        $remaining_time = 0;
                    }
                } else {
                    $time_out = date('g:i A', strtotime($time_info->extend_time));
                    // Calculate remaining time in seconds
                    if (date('Y-m-d', strtotime($time_info->date_added)) == date('Y-m-d')) {
                        $remaining_time = strtotime($time_info->extend_time) - time();
                    } else {
                        $remaining_time = 0;
                    }
                }

                // Format remaining time as HH:MM:SS
                //$remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                if ($remaining_time < 0) {
                    $remaining_time_formatted = '00:00:00';
                } else {
                    $remaining_time_formatted = sprintf('%02d:%02d:%02d', ($remaining_time / 3600), ($remaining_time / 60 % 60), ($remaining_time % 60));
                }
                
                if ($remaining_time < 300) {
                    $disabled = 'disabled';
                } else {
                    $disabled = '';
                }

                $sales = $this->db
                    ->select("SUM(total_amt) as sales")
                    ->from('consumable_stocks')
                    ->where('con_id', $con_id)
                    ->get()
                    ->row();

                $discount = $this->db
                    ->select('discount_amt AS discount')
                    ->from('consumable_stocks')
                    ->where('con_id', $con_id)
                    ->group_by('serial_no')
                    ->get()
                    ->row();
                
                $total_sales = $sales->sales - $discount->discount;

                $sales_amount = $this->input->post('sales');
                $inv_amount = $this->input->post('inv');
                $discount_amount = $this->input->post('discount');
                $total_sales_amount = $this->input->post('total_sales');

                $floatValue = floatval($inv_amount);
                $formattedValue = number_format($floatValue, 2);

                //$row[] = '<span class="remaining-time" data-remaining-time="' . $remaining_time . '">' . $remaining_time_formatted . '</span>';;
                $output_time_info .= '
                    <div class="form-group mb-3">
                        <label>Package:</label>
                        <h5>'.$time_info->admission_type.'</h5>
                    </div>
                    <div class="form-group">
                        <label>Remaining Time (HH:MM:SS):</label>
                        <h5 style="color:#d63031;">'.$remaining_time_formatted.'</h5>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time In:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_in)).'</h5>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Time Out:</label>
                                <h5>'.date('g:i A', strtotime($time_info->time_out)).'</h5>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center hide_data">
                        <span>Sales: <b>₱ '.number_format($sales_amount, 2).'</b></span><br>
                        <span>Inventory Sales: <b>₱ '.$formattedValue.'</b></span><br>
                        <span>Discount: <b>₱ -'.number_format($discount_amount, 2).'</b></span><br>
                        <hr>
                        <h4>Total Sales: ₱ '.number_format($total_sales_amount, 2).'</h4>
                    </div>
                    <hr>
                ';
                }
                break;
        }

        $data = array(
            'children_info' => $output,
            'parent_guardian' => $output_parent,
            'time_info' => $output_time_info,
            'date_added' => date('F j, Y', strtotime($time_info->date_added)) . ' ' . date('g:i A', strtotime($time_info->time_in)),
        );
        echo json_encode($data);
    }

    public function void_trans()
    {
        $message = '';
        $time_out_void = NULL;
        $trans_no = $this->input->post('trans_no');
        $passcode = $this->input->post('passwordcode');

        $child_id = $this->input->post('child_ID');

        if ($child_id != '') {
            $this->db->where('transaction_no', $trans_no);
            $this->db->where('type_id', 0);
            $void_pricing = $this->db->get('consumable_stocks')->row();

            //get time details
            $this->db->where('children_id', $void_pricing->guest_child_id);
            $time_info = $this->db->get('time_management')->row();

            $extension_time = date('H:i:s', strtotime($time_info->extend_time));

            //get pricing
            $this->db->where('pricing_id', $void_pricing->pricing_id);
            $time = $this->db->get('pricing_promo')->row();

            $input_hours = $time->time_admission;
            if (strpos($input_hours, '.') !== false) {
                $time_admission = $time->time_admission;

                $time_out = date('H:i:s', strtotime('-' . intval($time_admission) . ' hour ' . intval(($time_admission - intval($time_admission)) * 60) . ' minutes', strtotime($extension_time)));

                if ($time_out == $time_info->time_out) {
                   $time_out_void == NULL; 
                } else {
                    $time_out_void = $time_out;
                }

            } else {
                $time_out = date('H:i:s', strtotime('-'.$time->time_admission.' hour', strtotime($extension_time)));
                
                if ($time_out == $time_info->time_out) {
                    $time_out_void == NULL;
                } else {
                    $time_out_void = $time_out;
                }
            }

            $void_data = array(
                'transacation' => 'Void transaction - '. $trans_no,
                'user' => $_SESSION['loggedIn']['fullname'],
            );
            $this->db->where('passcode', $passcode);
            $query = $this->db->get('user')->row();
            $password = isset($query->passcode) ? $query->passcode : '';

            if ($password === $passcode) {
                $this->db->where('transaction_no', $trans_no)->update('consumable_stocks', array('status' => 2));
                
                //update extension time
                $this->db->where('children_id', $child_id);
                $this->db->update('time_management', array('extend_time' => $time_out_void));

                $this->db->insert('history_logs', $void_data);
                $message = 'Success';
            } else {
                $message = 'Error'; 
            }

        } else {
            $void_data = array(
                'transacation' => 'Void transaction - '. $trans_no,
                'user' => $_SESSION['loggedIn']['fullname'],
            );
            $this->db->where('passcode', $passcode);
            $query = $this->db->get('user')->row();
            $password = isset($query->passcode) ? $query->passcode : '';

            if ($password === $passcode) {
                $this->db->where('transaction_no', $trans_no)->update('consumable_stocks', array('status' => 2));
                $this->db->insert('history_logs', $void_data);
                $message = 'Success';
            } else {
                $message = 'Error'; 
            }
        }

        $output['message'] = $message;
        echo json_encode($output);
    }

    public function export_sales()
    {
        require_once 'vendor/autoload.php';
        $date_no = date('F j, Y');
        $salesData = $this->transaction->export_sales();
        $objReader = IOFactory::createReader('Xlsx');
        $fileName = 'Sales Report.xlsx';
        $newfileName = 'Sales Report as of_'.$date_no.'.xlsx';

        $spreadsheet = $objReader->load(FCPATH . '/template_reports/'. $fileName);
        $startRow = 2;
	    $currentRow = 2;
        foreach ($salesData as $list) {
            $spreadsheet->getActiveSheet()->insertNewRowBefore($currentRow+1,1);

            if ($list['service'] == 'INFLATABLES') {
                $children = $this->db
                    ->select("CONCAT(child_fname, ' ', child_lname) AS children")
                    ->from('guest_children')
                    ->where('parent_id', $list['guest_id'])
                    ->get()
                    ->result_array();
                $children_names = "";
                foreach ($children as $child) {
                    $children_names .= $child['children'] . ', ';
                }
                $kids = ucwords(trim($children_names, ', '));
            } else {
                $kids = $list->guest_fname. ' ' .$list->guest_lname;
            }

            $total_discount = 0;
            $sales = $this->db
                ->select("SUM(total_amt) as total_sales")
                ->from('consumable_stocks')
                ->where('type_id', 0)
                ->where('status', 0)
                ->get()
                ->row();

            $inv_sales = $this->db
                ->select("SUM(total_amt) as total_inv")
                ->from('consumable_stocks')
                ->where('type_id !=', 0)
                ->where('status', 0)
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
                ->where('guest_id', $list['guest_id'])
                ->get()
                ->row();

            $inv_guest = $this->db
                ->select("SUM(total_amt) as inv_sales")
                ->from('consumable_stocks')
                ->where('type_id !=', 0)
                ->where('guest_id', $list['guest_id'])
                ->get()
                ->row();

            $discount_guest = $this->db
                ->select('discount_amt')
                ->from('consumable_stocks')
                ->where('guest_id', $list['guest_id'])
                ->group_by('serial_no')
                ->get()
                ->row();
            $total_discount += $discount->discount_amt;

            $spreadsheet->getActiveSheet()->setCellValue('A'.$currentRow, $list['slip_app_no']);
            $spreadsheet->getActiveSheet()->setCellValue('B'.$currentRow, date('M j, Y', strtotime($list['date_added'])));
            $spreadsheet->getActiveSheet()->setCellValue('C'.$currentRow, $list['admission_type']);
            $spreadsheet->getActiveSheet()->setCellValue('D'.$currentRow, $kids);
            $spreadsheet->getActiveSheet()->setCellValue('E'.$currentRow, ucwords($list['guest_fname']) .' '.ucwords($list['guest_lname']));
            $spreadsheet->getActiveSheet()->setCellValue('F'.$currentRow, $list['contact_no']);
            $spreadsheet->getActiveSheet()->setCellValue('G'.$currentRow, $list['service']);
            $spreadsheet->getActiveSheet()->setCellValue('H'.$currentRow, number_format($sales_guest->total_sales, 2));
            $spreadsheet->getActiveSheet()->setCellValue('I'.$currentRow, number_format($inv_guest->inv_sales, 2));
            $spreadsheet->getActiveSheet()->setCellValue('J'.$currentRow, number_format($discount_guest->discount_amt, 2));
            $spreadsheet->getActiveSheet()->setCellValue('K'.$currentRow, $list['discount_remarks']);

            if ($list['Void_Stat'] == 2) {
                $status = 'Voided';
            } else {
                $status = '';
            }
            $spreadsheet->getActiveSheet()->setCellValue('L'.$currentRow, $status);

            $currentRow++;
        }
        $spreadsheet->getActiveSheet()->removeRow($currentRow,1);
        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        header('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); //mime type
        header('Content-Disposition: attachment;filename="'.$newfileName.'"'); //tell browser what's the file name
        header('Cache-Control: max-age=0'); //no cache
        $objWriter->save('php://output');

    }
}