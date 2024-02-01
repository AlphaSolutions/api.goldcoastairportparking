<?php
class Validation
{
    function name_validation($name, $field = 'Name', $min_length = 3, $max_length = 33)
    {
        // Full Name must contain letters, dashes and spaces only. We have to eliminate dash at the begining of the name.
        $name = trim($name);
        if (strlen($name) >= $min_length )
        {
            if (strlen($name) <= $max_length )
            {
                if(preg_match("/^[a-zA-Z][a-zA-Z -]+$/", $name) === 0)
                    $error = $field.' must contain letters, dashes and spaces only. we don\'t accept dash at the begining of the '.$field;
                else $error = null;
            }else $error = $field.' must contain less than '.$max_length.' letters.';
        }else $error = $field.' must contain at least '.$min_length.' letters.';
        
        return $error;
    }
    
    function email_validation($email, $email_label)
    {
        //E-mail validation: We use regexp to validate email.
        $email = trim($email);
        if (strlen($email) >= 1 )
        {
            if(preg_match("/^[a-zA-Z]\w+(\.\w+)*\@\w+(\.[0-9a-zA-Z]+)*\.[a-zA-Z]{2,4}$/", $email) === 0)
                $error = 'Please enter a valid '.$email_label;
            else $error = null;
        }else $error = 'Please enter your '.$email_label;
        
        return $error;
    }
    
    function digits_validation($digits, $digits_label)
    {
        //Value must be digits.
        $digits = trim($digits);
        if (strlen($digits) >= 1 )
        {
            if(preg_match("/^[0-9]+$/", $digits) === 0)
                $error = 'Please enter a valid '.$digits_label;
            else $error = null;
        }else $error = 'Please enter your '.$digits_label;
        
        return $error;
    }

    function state_validation($digits, $digits_label)
    {
        //Value must be digits.
        if($digits==="0"){
            $error = 'Please select '.$digits_label;
        } else {
            $error = null;
        }
            
        return $error;
    }

    function post_code($digits, $digits_label)
    {
        //Value must be digits.
        if(strlen($digits) >= 1){
            $error = null;
        } else {        
            $error = 'Please enter your '.$digits_label;
        }
            
        return $error;
    }

    function username_validation($username, $username_label)
    {
        //User must be digits and letters.
        $username = trim($username);
        if (strlen($username) >= 1 )
        {
            if(preg_match("/^[0-9a-zA-Z_]{3,}$/", $username) === 0)
                $error = $username_label.' must be digits and letters and at least 3 characters.';
            else $error = null;
        }else $error = 'Please enter your '.$username_label;
        
        return $error;
    }
    
    function date_validation($date, $date_label)
    {
        //Date must be with this form: YYYY-MM-DD.
        $date = trim($date);
        if (strlen($date) >= 1 )
        {
            if(preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $date) === 0)
                $error = $date_label.' Date must be with this form: YYYY-MM-DD.';
            else $error = null;
        }else $error = 'Please enter your '.$date_label;
        
        return $error;
    }
    
    function address_validation($address, $required, $address_label)
    {
        //Address must be only letters, numbers or one of the following ". , : /"
        $address = trim($address);
        if (strlen($address_label) == 0) $address_label = 'address';
        
        if (strlen($address) >= 1)
        {
            if(preg_match("/^[a-zA-Z0-9 _.,:\"\']+$/", $address) === 0)
                $error = 'Please enter a valid '.$address_label;
            else $error = null;
        }elseif ($required == true) 
            $error = 'Plesae enter your '.$address_label;
        else $error = null;
        
        return $error;
    }
    
    function password_validation($password, $level, $password_label)
    {
        $password = trim($password);
        
        switch ($level)
        {
            //Password must be at least 8 characters
            case 0:
            if (strlen($password) >= 1 )
            {
                if(preg_match("/^.*(?=.{8,}).*$/", $password) === 0)
                    $error = 'Password must be at least 8 characters.';
                else $error = null;
            }else $error = 'Please enter your '.$password_label;
            
            break;
            
            //Password must be at least 8 characters and at least one digit.
            case 1:
            if (strlen($password) >= 1 )
            {
                if(preg_match("/^.*(?=.{8,})(?=.*[0-9]).*$/", $password) === 0)
                    $error = 'Password must be at least 8 characters and one digit.';
                else $error = null;
            }else $error = 'Please enter your '.$password_label;
            
            break;
            
            //Password must be at least 8 characters and must contain at least one lower case letter, one upper case letter and one digit(alphanumeric).
            case 2:
            if (strlen($password) >= 1 )
            {
                if(preg_match("/^.*(?=.{8,})(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).*$/", $password) === 0)
                    $error = 'Password must be at least 8 characters and must contain at least one lower case letter, one upper case letter and one digit.';
                else $error = null;
            }else $error = 'Please enter your '.$password_label;
            
            break;
        
            default:
            $error = null;
            break;          
        }
        return $error;
    }
}