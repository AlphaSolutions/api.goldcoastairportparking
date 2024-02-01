<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller
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

    public function update()
    {  
        $this->load->model('Customers_model');

        //User ID
        $userId         = isset($_POST['user_id']) ? $_POST['user_id'] : '';
        $loyaltyNo      = isset($_POST['loyalty_no']) ? $_POST['loyalty_no'] : '';

        $locationId     = isset($_POST['location_id']) ? $_POST['location_id'] : '';
        $vehicleTypeId  = isset($_POST['vehicle_type_id']) ? $_POST['vehicle_type_id'] : '';
        $phoneType      = isset($_POST['phone_type']) ? $_POST['phone_type'] : '';
        //For booking
        $firstName      = isset($_POST['first_name']) ? $_POST['first_name'] : '';
        $lastName       = isset($_POST['last_name']) ? $_POST['last_name'] : '';
        $phoneNumber    = isset($_POST['phone']) ? $_POST['phone'] : '';
        $emailAddress   = isset($_POST['email']) ? $_POST['email'] : '';
        $postCode       = isset($_POST['post_code']) ? $_POST['post_code'] : '';
        $paxNo          = isset($_POST['pax_no']) ? $_POST['pax_no'] : '';
        $make           = isset($_POST['make']) ? $_POST['make'] : '';
        $model          = isset($_POST['model']) ? $_POST['model'] : '';
        $colour         = isset($_POST['colour']) ? $_POST['colour'] : '';
        $rego           = isset($_POST['rego']) ? $_POST['rego'] : '';

        $errors = [];
        if(empty($userId) && !is_numeric($userId))
        {
            $errors[] = [
                        'field'     => 'user_id',
                        'message'   => 'Invalid user ID'
                    ];
        }

        if(empty($locationId) && !is_numeric($locationId))
        {
            $errors[] = [
                        'field'     => 'location_id',
                        'message'   => 'Location ID is required.'
                    ];
        }

        if(empty($vehicleTypeId) && !is_numeric($vehicleTypeId))
        {
            $errors[] = [
                        'field'     => 'vehicle_type_id',
                        'message'   => 'Vehicle Type ID is required.'
                    ];
        }

        if(empty($phoneType))
        {
            $errors[] = [
                        'field'     => 'phone_type',
                        'message'   => 'Phone type is required.'
                    ];   
        }

        if(empty($firstName))
        {
            $errors[] = [
                        'field'     => 'first_name',
                        'message'   => 'First name is required.'
                    ];  
        }

        if(empty($lastName))
        {
            $errors[] = [
                        'field'     => 'last_name',
                        'message'   => 'Last name is required.'
                    ];  
        }

        if(empty($phoneNumber))
        {
            $errors[] = [
                        'field'     => 'phone_no',
                        'message'   => 'Phone number is required.'
                    ];
        }

        if(!filter_var($emailAddress, FILTER_VALIDATE_EMAIL))
        {
            $errors[] = [
                        'field'     => 'email',
                        'message'   => 'Please enter a valid email address'
                    ];
        }

        if(strlen(trim($postCode)) != 4)
        {
            $errors[] = [
                        'field'     => 'post_code',
                        'message'   => 'Please enter the 4-digit post code'
                    ];
        }

        if(empty($make))
        {
            $errors[] = [
                        'field'     => 'make',
                        'message'   => 'Please enter the make of your vehicle'
                    ];
        }

        if(empty($model))
        {
            $errors[] = [
                        'field'     => 'model',
                        'message'   => 'Please enter the model of your vehicle'
                    ];
        }

        if(empty($colour))
        {
            $errors[] = [
                        'field'     => 'colour',
                        'message'   => 'Please enter the colour of your vehicle'
                    ];
        }

        if(empty($rego))
        {
            $errors[] = [
                        'field'     => 'rego',
                        'message'   => 'Please enter the rego of your vehicle'
                    ];
        }

        if(!empty($errors))
        {
            echo json_encode(['code' => 'API0007', 'message' => API0007, 'details' => $errors]);
            return;
        }

        //Check if record exist
        $where = ['customer_id' => $userId, 'customer_loyalty_tag' => $loyaltyNo];
        $resCheckRecord = $this->Customers_model->get_where($where);

        if(empty($resCheckRecord))
        {
            echo json_encode(['code' => 'API0004', 'message' => API0004]);
            return;
        }

        //Check existing record not equal to current record
        //Comment temporarily since there's a duplicate email on the current records
        /*
        $where2 = "LOWER(email) = '{$emailAddress}' AND customer_id != $userId";
        $resCheckExistingRecord = $this->Customers_model->get_where($where2);

        if(!empty($resCheckRecord))
        {
            $message = 'It appears that we already have a record for that email address. 
                        If you are experiencing difficulties accessing our Loyalty service, 
                        please email us at <a href=\"mailto:info@alphacarparking.com.au\">info@alphacarparking.com.au</a>
                        ' . $where2;
            echo json_encode(['code' => 'API0001', 'message' => $message]);
            return;
        }
        */

        $data = [
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'contact_phone' => $phoneNumber,
                'phone_type'    => $phoneType,
                'email'         => $emailAddress,
                'post_code'     => $postCode,
                'vehicle_type_id'   => $vehicleTypeId,
                'vehicle_rego'  => $rego,
                'vehicle_make'  => $make,
                'vehicle_model' => $model,
                'vehicle_colour'=> $colour,
            ];

        $where = ['customer_id' => $userId, 'customer_loyalty_tag' => $loyaltyNo];

        $resUpdate = $this->Customers_model->update($where, $data);

        if(!$resUpdate)
        {
            echo json_encode(['code' => 'API0009', 'message' => API0009]);
            return;
        }

        echo json_encode(['code' => 'API0000', 'message' => API0000]);
        return;
    }
}