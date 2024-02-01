<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends MY_Controller
{    
    function __construct()
    {
        parent::__construct();

        $xAuth      = isset($_SERVER['HTTP_X_AUTH_TOKEN']) ? $_SERVER['HTTP_X_AUTH_TOKEN'] : NULL;

        // $userName   = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
        // $password   = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;
        $userName = '';
        $password = '';
        if(!empty($xAuth))
        {
            //Explode
            $auth = explode(":", $xAuth);
            $userName = isset($auth[0]) ? $auth[0] : NULL;
            $password = isset($auth[1]) ? $auth[1] : NULL;
        }

        
        //Only accept post method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode(['code' => 'API0004', 'message' => API0004]);
            exit();
        }

        //Validate API access
        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            $this->validateApi($userName, $password);            
        }
    }

    public function process()
    {
        $this->load->model('Travel_agents_model');
        $this->load->library('validation');

        $userName       = $this->input->post('username');
        $password       = $this->input->post('password');
        $confirmPassword= $this->input->post('confirm_password');
        $firstName      = $this->input->post('first_name');
        $lastName       = $this->input->post('last_name');
        $phone          = $this->input->post('phone');
        $email          = $this->input->post('email');
        $companyName    = $this->input->post('company_name');
        $stateId        = $this->input->post('state_id');
        $suburb         = $this->input->post('suburb');
        $postCode       = $this->input->post('post_code');

        //Travel agent reference
        $travelAgentNo  = $this->input->post('travel_agent_no');

        $where = "email = '$email' OR user_name = '$userName'";
        $resCheckUserEmail = $this->Travel_agents_model->get_where($where);

        $validate['password']         = $this->validation->password_validation($password, 0, 'Password');
        $validate['confirm_password'] = $this->validation->password_validation($confirmPassword, 0, 'Confirm Password');
        $validate['first_name']       = $this->validation->name_validation($firstName, 'First Name');
        $validate['last_name']        = $this->validation->name_validation($lastName, 'Last Name'); 
        $validate['phone']            = $this->validation->digits_validation($phone, 'Phone');

        // check if an email or username already exist
        if( !empty($resCheckUserEmail) )
        {
            $validate['email']        = 'Email already exists.';
            $validate['user_name']    = 'Username already exists.';
        } 
        else 
        {
            $validate['email']      = $this->validation->email_validation($email, 'Email');    
            $validate['username']   = $this->validation->username_validation($userName, 'Username');
        }

        // check if username and password did not match
        if( $password != $confirmPassword )
        {
            $validate['password']         = 'Must match confirm password.';
            $validate['confirm_password'] = 'Must match password.';
        }

        $validate['company_name']       = $this->validation->name_validation($companyName, 'Company');
        $validate['state']              = $this->validation->state_validation($stateId, 'State');
        $validate['suburb']             = $this->validation->name_validation($suburb, 'Suburb');
        $validate['postcode']           = $this->validation->post_code($postCode, 'Post Code');

        //Check validation error
        if( count( array_filter( $validate ) ) > 0 )
        {
            echo json_encode(['code' => 'API0007', 'message' => API0007, 'details' => $validate]);
            return;
        }

        $validationHash = $travelAgentNo;

        $data = [
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'phone'         => $phone,
                'email'         => $email,
                'company_name'  => $companyName, 
                'suburb'        => $suburb,
                'postcode'      => $postCode,
                'state_id'      => $stateId,
                'user_name'     => $userName,
                'password'      => sqlPassword($password),
                'discount'      => 10,
                'validation_hash'   => $validationHash,
                'email_validated'   => 0,
                'company_id'        => 19
            ];

        $resAdd = $this->Travel_agents_model->add($data);

        if(!$resAdd)
        {
            echo json_encode(['code' => 'API0006', 'message' => API0006, 'details' => $validate]);
            return;
        }

        echo json_encode(['code' => 'API0000', 'message' => API0000, 'travel_agent_id' => $resAdd]);
        return;
    }

    public function verification()
    {
        $this->load->model('Travel_agents_model');

        $travelAgentNo = $this->input->post('travel_agent_no');
        $travelAgentId = $this->input->post('travel_agent_id');

        $where = [
                'travel_agent_id'   => $travelAgentId,
                'validation_hash'   => $travelAgentNo
            ];

        $resCheck = $this->Travel_agents_model->get_where($where);

        if(empty($resCheck))
        {
            echo json_encode([
                            'code'   => 'API0001', 
                            'message'=> API0001
                        ]
                    );
            return;
        }

        if($resCheck[0]['email_validated'] == 1)
        {
            echo json_encode([
                            'code'   => 'API0008', 
                            'message'=> API0008
                        ]
                    );
            return;
        }

        $data = [
                'email_validated' => 1,
                'active' => 1
            ];

        $resUpdate = $this->Travel_agents_model->update($where, $data);

        if(!$resUpdate)
        {            
            echo json_encode([
                            'code'   => 'API0001', 
                            'message'=> API0001
                        ]
                    );
            return;
        }

        echo json_encode([
                            'code'              => 'API0000', 
                            'message'           => API0000, 
                            'travel_agent_id'   => $travelAgentId,
                            'travel_agent_no'   => $travelAgentNo
                        ]
                    );
        return;
    }
}