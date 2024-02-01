<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

if (! function_exists('debug'))
{
    function debug($msg="", $exit = false)
    {
        $today = date("Y-m-d H:i:s");

        if (is_array($msg) || is_object($msg))
        {
            echo "<hr>DEBUG ::[".$today."]<pre>\n";
            print_r($msg);
            echo "\n</pre><hr>";
        }
        else
        {
            echo "<hr>DEBUG ::[".$today."] $msg <hr>\n";
        }

        if ($exit) {
            $CI = get_instance();
            $CI->load->library('profiler');
            echo $CI->profiler->run();
            exit;
        }
    }
}