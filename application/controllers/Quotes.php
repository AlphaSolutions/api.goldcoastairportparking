<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Quotes extends MY_Controller
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

    public function processQuotation()
    {  
        $this->load->model('Quotes_model');
        
        $quoteNo        = isset($_POST['quote_no']) ? $_POST['quote_no'] : '';
        $locationId     = isset($_POST['location_id']) ? $_POST['location_id'] : '';
        $arrivalDate    = isset($_POST['arrival_date']) ? $_POST['arrival_date'] : '';
        $departureDate  = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $vehicleTypeId  = isset($_POST['vehicle_type_id']) ? $_POST['vehicle_type_id'] : '';
        $parkingTypeId  = isset($_POST['parking_type_id']) ? $_POST['parking_type_id'] : '';
        $source         = isset($_POST['source_id']) ? $_POST['source_id'] : '';
        $days           = isset($_POST['days']) ? $_POST['days'] : '';
        $cost           = isset($_POST['cost']) ? $_POST['cost'] : '';
        $createdAt      = isset($_POST['created_at']) ? $_POST['created_at'] : ''; //Local created at
        $quotationLocalId = isset($_POST['quotation_local_id']) ? $_POST['quotation_local_id'] : '';
        $parkingQuoteId = isset($_POST['parking_quote_id']) ? $_POST['parking_quote_id'] : '';
        $quotationNo    = isset($_POST['quotation_no']) ? $_POST['quotation_no'] : '';
        $sessionId      = session_id();

        $data = [
                'location_id'       => $locationId,
                'arrival_date'      => date("Y-m-d", strtotime($arrivalDate)),
                'departure_date'    => date("Y-m-d", strtotime($departureDate)),
                'vehicle_type_id'   => $vehicleTypeId,
                'parking_type_id'   => $parkingTypeId,
                'source_id'         => $source,
                'days'              => $days,
                'cost'              => $cost
            ];

        if(empty($parkingQuoteId))
        {
            $data = array_merge($data, ['datetime_quoted'   => date("Y-m-d H:i:s"), 'session_id'  => $sessionId]);
        }

        $transactionStatus  = true;
        $prodQuoteId        = NULL;
        // echo json_encode($this->input->post());
        // return;
        if(!empty($parkingQuoteId))
        {
            $where = ['quote_id' => $parkingQuoteId];
            $resAddQuote = $this->Quotes_model->update($where, $data);

            if(!$resAddQuote)
            {
                $transactionStatus = false;
            }

            $prodQuoteId = $parkingQuoteId;
        }
        else
        {
            $resAddQuote = $this->Quotes_model->add($data);
            
            if(!$resAddQuote)
            {
                $transactionStatus = false;
            }
            else
            {
                $prodQuoteId = $resAddQuote;
            }
        }

        if(!$transactionStatus)
        {
            echo json_encode(['code' => 'API0006', 'message' => API0006]);
            return;
        }

        $details = [
                'quotation_local_id'    => $quotationLocalId,
                'prod_quote_id'         => $prodQuoteId,
                'session_id'            => $sessionId,
                'quotation_no'          => $quotationNo
            ];
        echo json_encode(['code' => 'API0000', 'message' => API0000, 'data' => $details]);
        return;
    }
}