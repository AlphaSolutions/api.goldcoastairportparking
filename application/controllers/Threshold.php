<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Threshold extends MY_Controller
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

    public function checkAvailability()
    {  
        $this->load->model('Locations_model');        

        $locationId     = isset($_POST['location_id']) ? $_POST['location_id'] : '';
        $arrivalDate    = isset($_POST['arrival_date']) ? $_POST['arrival_date'] : '';
        $departureDate  = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $vehicleTypeId  = isset($_POST['vehicle_type_id']) ? $_POST['vehicle_type_id'] : '';
        $parkingTypeId  = isset($_POST['parking_type_id']) ? $_POST['parking_type_id'] : '';
        $source         = isset($_POST['source']) ? $_POST['source'] : '';
        $promoCode      = isset($_POST['promo_code']) ? $_POST['promo_code'] : '';
        $sourceType     = isset($_POST['source_type']) ? $_POST['source_type'] : '';
        $minerStatus    = isset($_POST['miner_status']) ? $_POST['miner_status'] : '';
        $userId         = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    
        //Only validate location Id as we have a validation on the wordpress back-end
        if(empty($locationId) || !is_numeric($locationId))
        {
            echo json_encode(['code' => 'API0001', 'message' => API0001, 'details' => 'Location ID is required.']);
            return;
        }

        //Check location ID in the database if exist and valid
        $resGetLocation = $this->Locations_model->get_where(['active' => '1', 'location_id' => $locationId]);

        if(empty($resGetLocation))
        {
            echo json_encode(['code' => 'API0001', 'message' => API0001]);
            exit();
        }

        $ignoreThresholdDays = 50;
        
        if(!empty($resGetLocation[0]['ignore_threshold_days']) && is_numeric($ignoreThresholdDays))
        {
            $ignoreThresholdDays = $resGetLocation[0]['ignore_threshold_days'];
        }

        $underCoverSpaces   = $resGetLocation[0]['undercover_spaces'];
        $openAirSpaces      = $resGetLocation[0]['open_air_spaces'];

        $arrivalDateTimeStamp   = strtotime(date("Y-m-d", strtotime($arrivalDate)));
        $departureDateTimeStamp = strtotime(date("Y-m-d", strtotime($departureDate)));

        //Get the number of day/s
        $days = floor(($departureDateTimeStamp - $arrivalDateTimeStamp)/(60*60*24)) + 1;

        $msg = '';
        //Only check if available if the number of days parked is less than the minimum required to by pass check
        if ($days < $ignoreThresholdDays) 
        {
            // check what parking type does the user select
            if ($parkingTypeId == 1 || $parkingTypeId == 3 || $parkingTypeId == 5) 
            {
                $preference  = 'Undercover';
                $alternative = 'Open Air';
            } 
            else {
                $preference  = 'Open Air';
                $alternative = 'Undercover';
            }

            $param = [
                    'location_id'           => $locationId,
                    'arrival_timestamp'     => $arrivalDateTimeStamp,
                    'departure_timestamp'   => $departureDateTimeStamp,
                    'parking_type_name'     => $preference,
                    'under_cover_spaces'    => $underCoverSpaces,
                    'open_air_spaces'       => $openAirSpaces,
                ];

            $preferenceAvailable = $this->isParkingAvailable($param);

            // if first preference not available, check alternative
            if (!$preferenceAvailable) 
            {
                $param['parking_type_name'] = $alternative;

                $alternativeAvailable = $this->isParkingAvailable($param);

                // show message as to what is available
                if ($alternativeAvailable) 
                {
                    $msg = "Sorry! $preference Parking is fully booked at this time.<br>But secure $alternative parking is still available!<br>";
                } else {
                    $msg = 'Sorry but we are fully booked for this period.<br><br>Please try Alpha again next time!<br>';
                }                
            }
        }

        $result = [
                    'code'      => empty($msg) ? 'API0000' : 'API0001',
                    'message'   => empty($msg) ? API0000 : API0001,
                    'details'   => $msg
                ];
        echo json_encode($result);
        return $msg;
    }

    public function isParkingAvailable($param = [])
    {
        $this->load->model('Threshold_overrides_model');

        if(empty($param))
            return true;

        $locationId         = isset($param['location_id']) ? $param['location_id'] : NULL;
        $arrivalTimestamp   = isset($param['arrival_timestamp']) ? $param['arrival_timestamp'] : NULL;
        $departureTimestamp = isset($param['departure_timestamp']) ? $param['departure_timestamp'] : NULL;
        $parkingTypeName    = isset($param['parking_type_name']) ? $param['parking_type_name'] : NULL;

        //Open spaces
        $underCoverSpaces   = isset($param['under_cover_spaces']) ? $param['under_cover_spaces'] : NULL;
        $openAirSpaces      = isset($param['open_air_spaces']) ? $param['open_air_spaces'] : NULL;

        $capacity = 0;

        switch ($parkingTypeName) :
            case 'Undercover': 
                    $capacity = $underCoverSpaces;
                break;

            case 'Open Air': 
                    $capacity = $openAirSpaces;
                break;

            case '': 
                    $capacity = $underCoverSpaces + $openAirSpaces;
                break;
        endswitch;

        $resThresholdOverrides = $this->Threshold_overrides_model->get_where(['location_id' => $locationId]);
        
        $dateFrom = $arrivalTimestamp;

        while($dateFrom <= $departureTimestamp):
            
            $spacesOccupied = $this->getVehicleCount($dateFrom, $locationId, $parkingTypeName);
            
            $tempCapacity   = $capacity;

            foreach($resThresholdOverrides as $row):

                if ($dateFrom >= strtotime($row['start_date']) && $dateFrom <= strtotime($row['end_date'])) 
                {
                    switch ($parkingTypeName) 
                    {
                        case 'Undercover': 
                                $tempCapacity = $row['ucs'];
                            break;

                        case 'Open Air': 
                                $tempCapacity = $row['oas'];
                            break;

                        case '': 
                                $tempCapacity = $row['ucs'] + $row['oas'];
                            break;
                    }

                    break;                
                }

            endforeach;

            if ($spacesOccupied >= $tempCapacity) 
            {
                return false;
            }

            $dateFrom = $dateFrom + (3600 * 24);

        endwhile;

        return true;
    }

    public function getVehicleCount($arrivalTimestamp, $locationId, $parkingTypeName = '') 
    {
        $this->load->model('Bookings_model');

        $count = 0;

        $where =  "deleted = 0 AND b.location_id = $locationId  AND b.arrival_date <= '" . date('Y-m-d', $arrivalTimestamp) . "'";
        $where .= " AND b.departure_date > '" . date('Y-m-d', $arrivalTimestamp) . "' AND b.deleted = 0";
        $where .= " AND b.status_id IN (1,2)";
        
        if ($parkingTypeName != '')
        {
            $where .= " AND pt.parking_type_name = '$parkingTypeName'";
        }

        $resGetCount = $this->Bookings_model->getJoinCountWhere($where);
        
        return $resGetCount->record_count;
    }
}
