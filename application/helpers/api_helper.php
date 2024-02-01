<?php
/**
 * sysOutLog         
 * @param  string   $filename   //Filename of the file
 * @param  string   $content    //content
 * @param  int      $log        //1 = Enable logging, 0 = Disabled logging
 * @return array()
 */
function sysOutLog($filename = NULL, $content = NULL, $log = 1)
{
    if(empty($filename)){
        $filename = "defaultLogFile.log";
    }

    $path       = SYS_OUT_LOG_PATH . '/sysoutlogs/'.$filename;
    $content    = $content.PHP_EOL;

    //Log file
    if($log == 1) {
        file_put_contents($path, $content, FILE_APPEND);
    }
}

/**
 * getParkingDays
 * @param  date   $arrivalDate      //Filename of the file
 * @param  date   $departureDate    //content
 * @return int
 */
function getParkingDays($arrivalDate, $departureDate)
{
    $arrivalDate = date("Y-m-d", strtotime($arrivalDate));
    $departureDate = date("Y-m-d", strtotime($departureDate));
    
    // Fix for parking - server may be using daylight savings time and this should ensure to fix it
    $arrivalDate   = $arrivalDate . ' 10:00:00';
    $departureDate = $departureDate . ' 14:00:00';

    $arrivalTimestamp   = strtotime($arrivalDate);
    $departureTimestamp = strtotime($departureDate);

    $days = floor(($departureTimestamp - $arrivalTimestamp)/(60*60*24)) + 1;

    return $days;
}

/**
 * getDateRange
 * @param  date   $startDate        //Filename of the file
 * @param  date   $endDate          //content
 * @return array
 */
function getDateRange($startDate, $endDate, $format="d-m-Y")
{
    //To see weather it is a school or public holiday
    //Create output variable
    $datesArray = array();
    //Calculate number of days in the range
    $total_days = round(abs(strtotime($endDate) - strtotime($startDate)) / 86400, 0) + 1;
    if($total_days<0) { return false; }
    //Populate array of weekdays and counts
    for($day=0; $day<$total_days; $day++)
    {
        $datesArray[] = date($format, strtotime("{$startDate} + {$day} days"));
    }
    //Return results array
    return $datesArray;
}

/**
 * sqlPassword
 * @param  string   $password        //password
 * @return string   $pass
 */
function sqlPassword($password = '') 
{
    $pass = strtoupper(
            sha1(
                    sha1($password, true)
            )
    );

    $pass = '*' . $pass;
    
    return $pass;
}

/**
 * ------------------------------------------------------------------
 * getCustomerDiscount
 * ------------------------------------------------------------------
 * Gets customer discount as a whole number (eg: 10% will return 10, not 0.1)
 * @param  string   $password        //password
 * @return string   $pass
 */
function getCustomerDiscount($minerStatus = '') 
{    
    $discount = 0;

    if ($minerStatus != 'A')
    { 
        $discount = 15;
    }

    return $discount;
}