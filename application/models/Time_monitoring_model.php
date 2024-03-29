<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * User_model class.
 * 
 * @extends CI_Model
 */
class Time_monitoring_model extends CI_Model
{
    var $guest = 'time_management';
    var $guest_order = array('G.slip_app_no', 'G.guest_fname', 'G.guest_mname', 'G.guest_lname', 'G.service');
    var $guest_search = array('G.slip_app_no', 'G.guest_fname', 'G.guest_mname', 'G.guest_lname', 'G.service'); //set column field database for datatable searchable just article , description , serial_num, property_num, department are searchable
    var $order = array('G.guest_id' => 'desc'); // default order

    /**
     * __construct function.
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getTimeMonitoring()
    {
        $this->_getTimeMonitoring_query();
        if ($_POST['length'] != -1)
        $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    public function count_filtered()
    {
        $this->_getTimeMonitoring_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db
            ->select('TM.*')
            ->select('G.slip_app_no, G.guest_fname, G.guest_mname, G.guest_lname, G.service, G.contact_no')
            ->select('P.admission_type')
            ->select("CONCAT(GC.child_fname, ' ', GC.child_lname) as children, GC.child_id")
            ->from($this->guest.' TM')
            ->join('guest_details G', 'TM.guest_id = G.guest_id', 'LEFT')
            ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
            ->join('guest_children GC', 'TM.children_id = GC.child_id', 'LEFT')
            ->where('TM.status', 'Ongoing');

        return $this->db->count_all_results();
    }

    private function _getTimeMonitoring_query()
    {
        $searchValue = $this->input->post('search_value');
        if ($searchValue) {
            $this->db->group_start();
            $this->db->or_like('G.guest_fname', $searchValue);
            $this->db->or_like('G.guest_lname', $searchValue);
            $this->db->or_like('G.guest_mname', $searchValue);
            $this->db->or_like('G.slip_app_no', $searchValue);
            $this->db->group_end();
        }

        $this->db
            ->select('TM.*')
            ->select('G.slip_app_no, G.guest_fname, G.guest_mname, G.guest_lname, G.service, G.contact_no')
            ->select('P.admission_type')
            ->select("CONCAT(GC.child_fname, ' ', GC.child_lname) as children, GC.child_id")
            ->from($this->guest.' TM')
            ->join('guest_details G', 'TM.guest_id = G.guest_id', 'LEFT')
            ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
            ->join('guest_children GC', 'TM.children_id = GC.child_id', 'LEFT')
            ->where('TM.status', 'Ongoing');

        if ($this->input->post('package')) {
            $this->db->where('G.service', $this->input->post('package'));
        }

        $sort = $this->input->post('sort');
        if ($sort == '5') {
            $this->db->where('TIMESTAMPDIFF(SECOND, NOW(), TM.time_out) <', 5 * 60);
        } else if ($sort == '15') {
            $this->db->where('TIMESTAMPDIFF(SECOND, NOW(), TM.time_out) <=', 15 * 60);
        } else if ($sort == 'Open') {
            $this->db->where('TIMESTAMPDIFF(SECOND, NOW(), TM.time_out) >', 15 * 60);
        }

        $i = 0;
        foreach ($this->guest_search as $item) // loop column 
        {
            if ($_POST['search']['value']) // if datatable send POST for search
            {
                if ($i === 0) // first loop
                {
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($this->guest_search) - 1 == $i) //last loop
                    $this->db->group_end(); //close bracket
            }
            $i++;
        }
        if (isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($this->guest_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_time_report()
    {
        $query = $this->db
            ->select('TM.*')
            ->select('G.slip_app_no, G.guest_fname, G.guest_mname, G.guest_lname, G.service, G.contact_no')
            ->select('P.admission_type')
            ->select("CONCAT(GC.child_fname, ' ', GC.child_mname) as children, GC.child_id")
            ->from($this->guest.' TM')
            ->join('guest_details G', 'TM.guest_id = G.guest_id', 'LEFT')
            ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
            ->join('guest_children GC', 'TM.children_id = GC.child_id', 'LEFT')
            ->where('TM.guest_id IS NOT NULL')
            ->get();
        return $query->result();
    }

    public function export_time_report()
	{
		$query = $this->db
            ->select('TM.*')
            ->select('G.slip_app_no, G.guest_fname, G.guest_mname, G.guest_lname, G.service, G.contact_no')
            ->select('P.admission_type')
            ->select("CONCAT(GC.child_fname, ' ', GC.child_mname) as children, GC.child_id")
            ->from($this->guest.' TM')
            ->join('guest_details G', 'TM.guest_id = G.guest_id', 'LEFT')
            ->join('pricing_promo P', 'TM.package_promo = P.pricing_id', 'LEFT')
            ->join('guest_children GC', 'TM.children_id = GC.child_id', 'LEFT')
            ->where('TM.guest_id IS NOT NULL')
            ->get();
		return $query->result_array();
	}

    function checkout_guest($slip_no, $child_id)
    {
        $this->db->where('serial_no', $slip_no);
        $this->db->where('children_id', $child_id);
        $checkout = $this->db->update('time_management', array('status' => 'Checkout'));
        return $checkout?TRUE:FALSE;
    }

    function checkout_guest_park($slip_no)
    {
        $this->db->where('serial_no', $slip_no);
        $checkout = $this->db->update('time_management', array('status' => 'Checkout'));
        return $checkout?TRUE:FALSE; 
    }

}