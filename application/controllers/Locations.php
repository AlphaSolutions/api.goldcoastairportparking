<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Locations extends MY_Controller
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

        $fileName = 'server_request_' . date("Y-m-d") . '.log';
        $content  = date("Y-m-d H:i:s") . "| Username: $userName | Password : $password";
        $content  .= date("Y-m-d H:i:s") . "| Server requests : " . json_encode($_SERVER);
        sysOutLog($fileName, $content);

        //Validate API access
        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            $this->validateApi($userName, $password);            
        }
    }

    public function index()
    {  
        $this->load->model('Locations_model');
        $this->load->model('Vehicle_types_model');
        $this->load->model('Parking_types_model');
        $this->load->model('Sources_model');
        $this->load->model('Travel_agents_model');
        $this->load->model('Promotions_model');
        $this->load->model('Washes_model');

        //Get locations
        $where = "active = 1"; // AND location_id != 3";
        $resGetLocations = $this->Locations_model->get_where($where);

        if(empty($resGetLocations))
        {
            echo json_encode(['code' => 'API0001', 'message' => API0001]);
            exit();
        }

        $sourceType = $this->input->post('sourceType');
        $companyId  = $this->input->post('companyId');

        //Vehicle types
        $sortVehicleTypes   = "location_id, vehicle_type_name";
        $resGetVehicleType  = $this->Vehicle_types_model->get_where(['active' => 1], $sortVehicleTypes);

        //Parking types
        $sortParkingTypes   = "location_id, parking_type_name DESC";
        $resParkingTypes    = $this->Parking_types_model->get_where(['active' => 1], $sortParkingTypes);

        //Sources
        /*N = Normal Sources, L = Loyalty, TA = Travel Agent , TC = Travel Company*/
        $sourceWhereClause  = "active = 1 AND show_on_web = 1";
        if($sourceType == 'N')
        {
            $sourceWhereClause  .= " AND source_id != 105 AND source_id != 106 ";
        }
        else if($sourceType == 'L')
        {
            $sourceWhereClause = "source_id = 3 OR source_id = 11 OR source_id = 56";
        }
        else if($sourceType == 'TA')
        {
            $sourceWhereClause = "source_id IN (12,26,87)";
        }
        else if($sourceType == 'TC' && $companyId == 19) 
        {
            $sourceWhereClause = "source_id IN (105, 106, 134)";
        }

        $sortSources    = "location_id, source_name"; 
        $resSource      = $this->Sources_model->get_where($sourceWhereClause, $sortSources) ;

        //Agents
        $whereTA    = ['active' => 1, 'company_id' => 19];
        $sortTA     = "last_name asc, first_name asc";
        $resTA      = $this->Travel_agents_model->getWhereV2($whereTA, $sortTA);

        //Promotions
        $wherePromotions    = [''];
        $resGetPromotions   = $this->Promotions_model->get_where($wherePromotions);

        //Washes
        $washesWhere    = ['active' => 1, 'show_on_web' => 1];
        $sortClause     = "location_id, vehicle_type, price, wash_name";
        $resGetWashes   = $this->Washes_model->get_where($washesWhere, $sortClause);

        $data = [
                'locations'     => $resGetLocations,
                'vehicle_types' => $resGetVehicleType,
                'parking_types' => $resParkingTypes,
                'sources'       => $resSource,
                'travel_agents' => $resTA,
                'post_data'     => $this->input->post(),
                'promotions'    => $resGetPromotions,
                'washes'        => $resGetWashes
            ];

        $results = [
                'code'      => 'API0000',
                'message'   => API0000,
                'data'      => $data
            ];
        
        echo json_encode($results);
        return;
    }
}
