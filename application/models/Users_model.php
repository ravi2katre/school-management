<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Users_model extends CI_Model {

    var $table = 'users';
    var $primary_key_field = 'id';
    var $column = array('id','company','address','last_name','phone','fax','email'); //set column field database for order and search
    var $order = array('id' => 'desc'); // default order
    var $group_ids = array(ADMINISTRATOR,MEMBER,SCHOOL,PARENT,STUDENT); // default order

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function set_column($column= array()){
        $this->column = $column;
    }
    public function set_group_ids($groups= array()){
        $this->group_ids = $groups;
    }

    public function get_rows()
    {
        $this->db->from($this->table);
        $this->db->order_by($this->primary_key_field, 'ASC');
        $query = $this->db->get();
        return $query->result();
    }

    private function _get_datatables_query()
    {
        //add custom filter here

        if($this->input->post('first_name'))
        {
            $this->db->like('u.first_name', $this->input->post('first_name'));
        }
        if($this->input->post('email'))
        {
            $this->db->like('u.email', $this->input->post('email'));
        }


        $this->db->select('u.*');
        $this->db->from($this->table.' u');
        $this->db->join('users_groups ug', " ug.user_id = u.id AND ug.group_id IN('".implode(",",$this->group_ids)."')",' INNER ');

        $i = 0;
        foreach ($this->column as $item) // loop column
        {
            if($this->input->post('search[value]')) // if datatable send POST for search
            {
                if($i===0) // first loop
                {
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($item, $this->input->post('search[value]'));
                }
                else
                {
                    $this->db->or_like($item, $this->input->post('search[value]'));
                }
                if(count($this->column) - 1 == $i) //last loop
                    $this->db->group_end(); //close bracket
            }
            $column[$i] = $item; // set column array variable to order processing
            $i++;
        }
        if($this->input->post('order')) // here order processing
        {
            $this->db->order_by($column[$this->input->post('order[0][column]')], $this->input->post('order[0][dir]'));
        }
        else if(isset($this->order))
        {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_datatables()
    {
        $this->_get_datatables_query();
        if($this->input->post('length') != -1)
        $this->db->limit($this->input->post('length'), $this->input->post('start'));
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_by_id($id)
    {
        $this->db->from($this->table);
        $this->db->where($this->primary_key_field,$id);
        $query = $this->db->get();

        return $query->row();
    }

    public function save($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($where, $data)
    {
        $this->db->update($this->table, $data, $where);
        return $this->db->affected_rows();
    }

    public function delete_by_id($id)
    {
        $this->db->where($this->primary_key_field, $id);
        $this->db->delete($this->table);
    }

    public function get_by_condition($condition)
    {
        $this->db->from($this->table);
        $this->db->where($condition);
        $query = $this->db->get();

        return $query->row();
    }

    public function get_students_parents($condition)
    {
        $this->db->from('students_parents');
        $this->db->where($condition);
        $query = $this->db->get();

        return $query->result();
    }

    public function save_students_parents($data)
    {
        $rows = $this->get_students_parents($data);

        if(!isset($rows[0])){
        $this->db->insert('students_parents', $data);
        return $this->db->insert_id();
        }else{
            return FALSE;
        }
    }
}
