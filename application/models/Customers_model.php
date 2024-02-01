<?php
class Customers_model extends CI_Model 
{
    public $table = 'customers';
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

    public function getJoinWhere( $where = NULL, $sort = null )
    {
        $sql    = "SELECT * 
                    FROM customers c
                INNER JOIN vehicle_types vt ON c.vehicle_type_id = vt.vehicle_type_id
                ";

        if(!empty($where))
        {
            $sql .= " WHERE $where";
        }

        if(!empty($sort))
        {
            $sql .= " ORDER BY $sort";
        }

        $query  = $this->db->query($sql);
        $result = $query->result_array();
        return $result;
    }
}

/* End of file Customers_model.php */
/* Location: ./system/application/models/Customers_model.php */