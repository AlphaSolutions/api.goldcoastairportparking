<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Parking_cost extends MY_Controller
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

    public function getParkingCost()
    {  
        $this->load->model('Locations_model');        
        $this->load->model('Promotions_model');
        $this->load->model('Holidays_model');
        $this->load->model('Rates_model');
        $this->load->model('Travel_agents_model');

        $locationId     = isset($_POST['location_id']) ? $_POST['location_id'] : '';
        $arrivalDate    = isset($_POST['arrival_date']) ? $_POST['arrival_date'] : '';
        $departureDate  = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $vehicleTypeId  = isset($_POST['vehicle_type_id']) ? $_POST['vehicle_type_id'] : '';
        $parkingTypeId  = isset($_POST['parking_type_id']) ? $_POST['parking_type_id'] : '';
        $source         = isset($_POST['source_id']) ? $_POST['source_id'] : '';
        $promoCode      = isset($_POST['promo_code']) ? $_POST['promo_code'] : '';
        $sourceType     = isset($_POST['source_type']) ? $_POST['source_type'] : 'N';
        $taUserName     = isset($_POST['taUserName']) ? $_POST['taUserName'] : '';
        $minerStatus    = isset($_POST['miner_status']) ? $_POST['miner_status'] : '';
        $userId         = isset($_POST['user_id']) ? $_POST['user_id'] : '';
        $travelAgentId  = isset($_POST['travel_agent_id']) ? $_POST['travel_agent_id'] : '';
        $bookingFor     = isset($_POST['booking_for']) ? $_POST['booking_for'] : '';

        $resPromotion   = $this->Promotions_model->get_where(['promo_code' => $promoCode]);

        $promoCodeDiscount  = 0;
        $promoCodeId        = '';

        if(!empty($resPromotion))
        {
            $promoCodeDiscount  = $resPromotion[0]['discount'];
            $promoCodeId        = $resPromotion[0]['promo_id'];
        }

        //Get the number of days of parking
        $daysParked = getParkingDays($arrivalDate, $departureDate);

        $arrivalDate    = date("Y-m-d", strtotime($arrivalDate));
        $departureDate  = date("Y-m-d", strtotime($departureDate));

        //Check holidays
        $whereHolidays = "holiday_date BETWEEN '".$arrivalDate."' AND '".$departureDate."'";
        $whereHolidays .= " AND location_id = '". $locationId ."'";
        $resHolidays = $this->Holidays_model->get_where($whereHolidays);

        //1 DAY FREE promocode and the parking type is Open Air/location
        if($promoCode == '1FREE' && in_array($parkingTypeId, [2, 4, 6]))
        {
            if(empty($resHolidays) && $daysParked > 5)
            {
                $daysParked = $daysParked - 1;
            }
        }

        $ratesWhere = "r.active = 1";
        //N = Normal Sources, L = Loyalty, TA = Travel Agent , TC = Travel Company
        if($sourceType == 'TA' || $sourceType == 'TC')
        {
            // $ratesWhere .= " AND ta.travel_agent_id = $userId";
        }
        else
        {
            // Brisbane Undercover Miner Rate
            if($minerStatus == 'A')
            {
                $ratesWhere .= " AND r.company_id = 9 ";
            }
            else
            {
                $ratesWhere .= " AND r.company_id = 0 ";
            }
        }

        $ratesWhere .= " AND rd.active = 1";
        $ratesWhere .= " AND r.parking_type_id = " . $parkingTypeId;
        $ratesWhere .= " AND r.vehicle_type_id = " . $vehicleTypeId;
        $ratesWhere .= " AND rd.date_effective <= '" . $arrivalDate . "'";

        $sortClause  = "rd.date_effective DESC";

        $limitClause = 1;

        //Get rates
        $resRates = $this->Rates_model->getJoinWhere($ratesWhere, $sortClause, $limitClause);
        
        //If no rates found
        if(empty($resRates))
        {
            echo json_encode(['code' => 'API0001', 'message' => API0001 . '! A web quote is not available at this time.  Please contact us for a quote.' ,]);
            return;
        }

        $rateDetailId = $resRates[0]['rate_detail_id'];
        $cost       = 0; //Initialise cost
        $lastRate   = 0;

        //Get all the rates
        for ($i = 1; $i <= $daysParked; $i++)
        {
            if ($i <= 14) 
            {
                $cost = $cost + $resRates[0]['day_' . $i];
                $lastRate = $resRates[0]['day_' . $i];
            } 
            else 
            {
                //Melbourne and Undercover
                if(($locationId == 2 ) && ($parkingTypeId == 3))
                {
                    // for jetport computations, should be removed after jetport
                    if($i==15)
                    {
                        $cost       = $cost + $resRates[0]['day_' . $i];
                        $lastRate   = $resRates[0]['day_' . $i];
                    } 
                    else if( $i >= 16)
                    {
                        $ptype      = "undercover";
                        $ucover     = $this->jetportOpen($i, $ptype);
                        $cost       = $cost + $ucover;
                        $lastRate   = $ucover;
                    }
                } 
                else if( ( $locationId == 2 ) && ( $parkingTypeId == 4 ) )
                {
                    // for jetport computations, should be removed after jetport
                    if($i == 15)
                    {
                        $cost       = $cost + $resRates[0]['day_' . $i];
                        $lastRate   = $resRates[0]['day_' . $i];
                    } 
                    else if($i >= 16)
                    {
                        $ptype      = "outdoor";
                        $open       = $this->jetportOpen($i, $ptype);
                        $cost       = $cost + $open;
                        $lastRate   = $open;  

                    }
                }
                else 
                {
                    // non-jetport computations
                    $cost       = $cost + $resRates[0]['day_other'];
                    $lastRate   = $resRates[0]['day_other'];    
                } 
            }
        }

        if($cost <= 0)
        {
            echo json_encode(['code' => 'API0005', 'message' => API0005]);
            return;
        }

        $originalCost = $cost;

        // Lock customers into Miner Rate for Brisbane Undercover without any special discounts for public booking
        if($minerStatus == 'A')
        {
            $data = ['cost' => $cost, 'minerStatus' => $minerStatus, 'originalCost' => $originalCost, 'days_parked' => $daysParked];
            echo json_encode(['code' => 'API0000', 'message' => API0000, 'data' => $data]);
            return;
        }

        //SCHOOL HOLIDAYS -> (Disabled for the moment) 
        // Travel agent, Travel Agent 20
        /*
         * 12  Travel Agent
         * 26  Travel Agent
         * 87  Travel Agent
         * 105 Travel Industry
         * 106 Travel Industry
         */
        if(in_array($source, [12, 26, 87, 105, 106]))
        {
            // Travel_agents_model
            $whereTa            = ['active' => 1, 'travel_agent_id' => $travelAgentId];
            $sortTa             = '';
            $limitClauseTa      = 1;

            $resTravelAgent = $this->Travel_agents_model->get_where($whereTa, $sortTa, $limitClauseTa);
            if(!empty($resTravelAgent))
            {
                if(!empty($resTravelAgent[0]['discount']))
                {
                    $taDiscount = $resTravelAgent[0]['discount'];
                    if($bookingFor == 2)
                    {
                        //If booking is for travel agent
                        $taDiscount = 20; 
                    }
                    $cost = $cost * ((100 - $taDiscount)/100);
                }
            }
        }

        // Email Promo - 1 day free in Melb but not holiday, min 5 day
        $arrivalDayNoOfWeek = date('w', strtotime($arrivalDate)); 
        if($daysParked >= 5 && !in_array($arrivalDayNoOfWeek, [5,6]))
        {
            if ( ($source == 34 && $parkingTypeId == 3) || ($source == 34 && $parkingTypeId == 4)) 
            {
                $cost = $cost - $lastRate;
            }
        }

        //15% OFF Promo - Wyndham Hotel
        if ( in_array($source, [135, 136]) )
        {
            $cost = $cost * 0.85;
        }

        //15% off YHA
        if ( in_array($source, [74, 75]) )
        {
            $cost = $cost * 0.85;
        } 

        // 10% DEFCOM MEMBER PROMO
        if ( in_array($source, [107, 108, 109]) )
        {
            $cost = $cost * ((100-$promoCodeDiscount) / 100);
        }

        //15% Discount QLD Seniors
        if( 
            ($promoCode === 'QLDSeniors' || in_array($source, [139, 140])) 
            &&
            $daysParked > 1 
            && 
            in_array($locationId, [1,3])            
        )
        {
            $cost = $cost * 0.85;
        } 

        //Discount from wyndam hotel || defcom
        if(in_array($promoCodeId, [1,3]))
        {   
            if(!empty($promoCodeDiscount))
            {
                $cost = $cost * ((100-$promoCodeDiscount) / 100);
            }
        }

        $promoDiscount = 0;
        if($sourceType == 'L')
        {
            $promoDiscount = getCustomerDiscount($minerStatus);
        }

        if($sourceType == 'L')
        {
            $cost = $originalCost - ($originalCost * ($promoDiscount/100));
        }

        $data = ['cost' => $cost, 'minerStatus' => $minerStatus, 'originalCost' => $originalCost, 'source' => $source, 'days_parked' => $daysParked];
        echo json_encode(['code' => 'API0000', 'message' => API0000, 'data' => $data]);
        return;
    }

    private function getHolidays()
    {
        return [
                [ 
                    'from' => '17-12-2012',
                    'to'   => '07-01-2013'
                ],
                [ 
                    'from' => '29-03-2013',
                    'to'   => '14-04-2013'
                ],
            ];
    }

    /**
     * jetportOpen
     * @param  int      $day            //Filename of the file
     * @param  string   $endDate        //content
     * @return array
     */
    private function jetportOpen($day,$ptype)
    {
        $parking_type = [
        '16' => ['undercover' => '6.6', 'outdoor' => '6.2'],
        '17' => ['undercover' => '6.6', 'outdoor' => '6.2'],
        '18' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '19' => ['undercover' => '6.7', 'outdoor' => '5.3'],
        '20' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '21' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '22' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '23' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '24' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '25' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '26' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '27' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '28' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '29' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '30' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '31' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '32' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '33' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '34' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '35' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '36' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '37' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '38' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '39' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '40' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '41' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '42' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '43' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '44' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '45' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '46' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '47' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '48' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '49' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '50' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '51' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '52' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '53' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '54' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '55' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '56' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '57' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '58' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '59' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '60' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '61' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '62' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '63' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '64' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '65' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '66' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '67' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '68' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '69' => ['undercover' => '6.6', 'outdoor' => '5.3'],
        '70' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '71' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '72' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '73' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '74' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '75' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '76' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '77' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '78' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '79' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '80' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '81' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '82' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '83' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '84' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '85' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '86' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '87' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '88' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '89' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '90' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '91' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '92' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '93' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '94' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '95' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '96' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '97' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '98' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '99' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '100' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '101' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '102' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '103' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '104' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '105' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '106' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '107' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '108' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '109' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '110' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '111' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '112' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '113' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '114' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '115' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '116' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '117' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '118' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '119' => ['undercover' => '6.6', 'outdoor' => '5.3'],
        '120' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '121' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '122' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '123' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '124' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '125' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '126' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '127' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '128' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '129' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '130' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '131' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '132' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '133' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '134' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '135' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '136' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '137' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '138' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '139' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '140' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '141' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '142' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '143' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '144' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '145' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '146' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '147' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '148' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '149' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '150' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '151' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '152' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '153' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '154' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '155' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '156' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '157' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '158' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '159' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '160' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '161' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '162' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '163' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '164' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '165' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '166' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '167' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '168' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '169' => ['undercover' => '6.6', 'outdoor' => '5.3'],
        '170' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '171' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '172' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '173' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '174' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '175' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '176' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '177' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '178' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '179' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '180' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '181' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '182' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '183' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '184' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '185' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '186' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '187' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '188' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '189' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '190' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '191' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '192' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '193' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '194' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '195' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '196' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '197' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '198' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '199' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '200' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '201' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '202' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '203' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '204' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '205' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '206' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '207' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '208' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '209' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '210' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '211' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '212' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '213' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '214' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '215' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '216' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '217' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '218' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '219' => ['undercover' => '6.6', 'outdoor' => '5.3'],
        '220' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '221' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '222' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '223' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '224' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '225' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '226' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '227' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '228' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '229' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '230' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '231' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '232' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '233' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '234' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '235' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '236' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '237' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '238' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '239' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '240' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '241' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '242' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '243' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '244' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '245' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '246' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '247' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '248' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '249' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '250' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '251' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '252' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '253' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '254' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '255' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '256' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '257' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '258' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '259' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '260' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '261' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '262' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '263' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '264' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '265' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '266' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '267' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '268' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '269' => ['undercover' => '6.7', 'outdoor' => '5.3'],
        '270' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '271' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '272' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '273' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '274' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '275' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '276' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '277' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '278' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '279' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '280' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '281' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '282' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '283' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '284' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '285' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '286' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '287' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '288' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '289' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '290' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '291' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '292' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '293' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '294' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '295' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '296' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '297' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '298' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '299' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '300' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '301' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '302' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '303' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '304' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '305' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '306' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '307' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '308' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '309' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '310' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '311' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '312' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '313' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '314' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '315' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '316' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '317' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '318' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '319' => ['undercover' => '6.6', 'outdoor' => '5.3'],
        '320' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '321' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '322' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '323' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '324' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '325' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '326' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '327' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '328' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '329' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '330' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '331' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '332' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '333' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '334' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '335' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '336' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '337' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '338' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '339' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '340' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '341' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '342' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '343' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '344' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '345' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '346' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '347' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '348' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '349' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '350' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '351' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '352' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '353' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '354' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '355' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '356' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '357' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '358' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '359' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '360' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '361' => ['undercover' => '6.5', 'outdoor' => '5.2'],
        '362' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '363' => ['undercover' => '6.6', 'outdoor' => '5.2'],
        '364' => ['undercover' => '6.7', 'outdoor' => '5.2'],
        '365' => ['undercover' => '6.6', 'outdoor' => '5.2']
        ];


        foreach ($parking_type as $key => $value) {
            if($key == $day){
                return $value[$ptype];
            }
        }
    }
}