<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_Controller
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

    public function travelAgent()
    {
        $this->load->model('Travel_agents_model');

        $userName = $this->input->post('username');
        $password = $this->input->post('password');

        if(empty($userName) || empty($password))
        {
            echo json_encode(['code' => 'API0007', 'message' => API0007]);
            return;
        }

        $decryptedPassword = sqlPassword($password);
        
        $where = [
                'user_name' => $userName,
                'password'  => $decryptedPassword,
                'email_validated' => 1
            ];

        $resTa = $this->Travel_agents_model->get_where($where);

        if(empty($resTa))
        {
            echo json_encode(['code' => 'API0001', 'message' => API0001 . '! Invalid username and password.']);
            return;
        }

        $details = [
                'user_name'         => $resTa[0]['user_name'],
                'travel_agent_id'   => $resTa[0]['travel_agent_id'],
                'email_validated'   => $resTa[0]['email_validated'],
                'discount'          => $resTa[0]['discount'],
                'company_id'        => $resTa[0]['company_id'],
            ];

        echo json_encode(['code' => 'API0000', 'message' => API0000 . 'y login.', 'data' => $details]);
        return;
    }

    public function customer()
    {
        $this->load->model('Customers_model');

        $loyaltyNumber = $this->input->post('loyalty_no');
        $email         = $this->input->post('email');

        if(empty($loyaltyNumber) && empty($email))
        {
            echo json_encode(['code' => 'API0007', 'message' => API0007]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            echo json_encode(['code' => 'API0007', 'message' => API0007]);
            return;
        }

        //Trim loyalty number
        $loyaltyNumber = trim($loyaltyNumber);
        $where = "LOWER(c.email) = '$email' AND TRIM(c.customer_loyalty_tag) = '$loyaltyNumber'";

        $resValidate = $this->Customers_model->getJoinWhere($where);

        if(empty($resValidate))
        {
            $message = "I'm afraid that your details have not been recognised. You may have entered them incorrectly or you are not yet registered for our Loyalty service.
                        To register for our Loyalty Program please ask our staff when you next make a booking.";
            echo json_encode(['code' => 'API0001', 'message' => $message]);
            return;
        }

        $data = [
                'customer_id'       => $resValidate[0]['customer_id'],
                'customer_login_loc'=> $resValidate[0]['customer_login_loc'],
                'customer_login_id' => $resValidate[0]['customer_login_id'],
                'first_name'        => $resValidate[0]['first_name'],
                'last_name'         => $resValidate[0]['last_name'],
                'contact_phone'     => $resValidate[0]['contact_phone'],
                'phone_type'        => $resValidate[0]['phone_type'],
                'email'             => $resValidate[0]['email'],
                'post_code'         => $resValidate[0]['post_code'],
                'vehicle_type_id'   => $resValidate[0]['vehicle_type_id'],
                'vehicle_rego'      => $resValidate[0]['vehicle_rego'],
                'vehicle_make'      => $resValidate[0]['vehicle_make'],
                'vehicle_model'     => $resValidate[0]['vehicle_model'],
                'vehicle_colour'    => $resValidate[0]['vehicle_colour'],
                'bookings'          => $resValidate[0]['bookings'],
                'MinerStatus'       => $resValidate[0]['MinerStatus'],
                'customer_loyalty_tag' => $resValidate[0]['customer_loyalty_tag'],
                'date_created'      => $resValidate[0]['date_created'],
                'last_free_wash'    => $resValidate[0]['last_free_wash'],
                'vehicle_type_id'   => $resValidate[0]['vehicle_type_id'],
                'vehicle_type_name' => $resValidate[0]['vehicle_type_name'],
                'show_on_web'       => $resValidate[0]['show_on_web'],
                'active'            => $resValidate[0]['active'],
                'location_id'       => $resValidate[0]['location_id']
            ];

        echo json_encode(['code' => 'API0000', 'message' => API0000, 'data' => $data]);
        return;
    }
}