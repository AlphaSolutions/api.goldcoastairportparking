<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{   
    /**
     * This variable holds the module name
     * @var string
     * @access public
     */
    public $module_name = NULL;
    /**
     * This variable holds the model name
     * @var string
     * @access public
     */
    
    function __construct() 
    {    
        parent::__construct();        
    }
  
    public function validateApi($userName, $password)
    {
        $this->load->model("Api_user_model");

        $fileName = 'credentials_' . date("Y-m-d") . '.log';
        $content  = date("Y-m-d H:i:s") . "| User name: $userName" . "| Password : $password";
        sysOutLog($fileName, $content);

        if(empty($userName) || empty($password))
        {
            echo json_encode(['code' => 'API0002', 'message' => API0002]);
            exit();
        }

        $where = ['username' => $userName, 'password' => hash('sha512', $password), 'is_active' => 'Y'];
        $resCheck = $this->Api_user_model->get_where($where);

        if(empty($resCheck))
        {
            echo json_encode(['code' => 'API0003', 'message' => API0003]);
            exit();
        }
    }
}
