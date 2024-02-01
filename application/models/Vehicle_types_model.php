<?php
class Vehicle_types_model extends CI_Model 
{
    public $table = 'vehicle_types';
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
}

/* End of file Vehicle_types_model.php */
/* Location: ./system/application/models/Vehicle_types_model.php */