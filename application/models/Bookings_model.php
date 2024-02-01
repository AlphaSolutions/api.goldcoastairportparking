<?php
class Bookings_model extends CI_Model 
{
    public $table = 'bookings';
    public function __construct() 
    {
        parent::__construct();
    }    

    public function add($data)
    {
        $query = $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    public function update($where, $data)
    {
        $this->db->where($where);
        $sql = $this->db->update($this->table, $data);
        return true;
    }

    public function get_where( $where = NULL, $sort = null )
    {
        $this->db->select('*');
        $this->db->from($this->table);

        if(!empty($where)){
            $this->db->where( $where );
        }

        if(!empty($sort)){
            $this->db->order_by($sort);
        }
        
        $result = $this->db->get();
        $res_qry = $result->result_array();
        return $res_qry;
    }
    
    public function delete($where)
    {
        $this->db->where( $where );
        $this->db->delete($this->table); 
        return true;
    }

    public function getJoinCountWhere( $where = NULL, $sort = null )
    {
        $this->db->select('COUNT(*) as record_count');
        $this->db->from($this->table . ' b');
        $this->db->join('parking_types pt', 'b.parking_type_id = pt.parking_type_id');

        if(!empty($where))
        {
            $this->db->where( $where );
        }

        if(!empty($sort))
        {
            $this->db->order_by($sort);
        }
        
        $result = $this->db->get();
        $res_qry = $result->row();
        return $res_qry;
    }

    public function getJoinWhere( $where = NULL, $sort = null )
    {
        $this->db->select('b.*, l.location_name, bs.status_name');
        $this->db->from($this->table . ' b');
        $this->db->join('locations l', 'l.location_id = b.location_id', 'left');
        $this->db->join('booking_status bs', 'bs.status_id = b.status_id', 'left');

        if(!empty($where))
        {
            $this->db->where( $where );
        }

        if(!empty($sort))
        {
            $this->db->order_by($sort);
        }
        
        $result     = $this->db->get();
        $res_qry    = $result->result_array();
        return $res_qry;

    }

    private function getDatatablesQuery()
    {
        $this->db->select('*');
        $this->db->from($this->table);

        $i = 0;
        
        foreach ($this->column_search as $item) // loop column 
        {
            if($_POST['search']['value'] && !empty($_POST['search']['value'])) // if datatable send POST for search
            {
                if($i===0) // first loop
                {
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($item, $_POST['search']['value']);
                }
                else
                {
                    $this->db->or_like($item, $_POST['search']['value']);
                }
 
                if(count($this->column_search) - 1 == $i) //last loop
                    $this->db->group_end(); //close bracket
            }
            $i++;
        }

        if(isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } 
        else if(isset($this->order))
        {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }
 
    public function getDatatables()
    {
        $this->getDatatablesQuery();
        if($_POST['length'] != -1)
        $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }
 
    public function countFiltered()
    {
        $this->getDatatablesQuery();
        $query = $this->db->get();
        return $query->num_rows();
    }
 
    public function countAll()
    {
        $this->db->select('*');
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }
}

/* End of file Bookings_model.php */
/* Location: ./system/application/models/Bookings_model.php */