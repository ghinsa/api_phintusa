<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction extends CI_Controller {

	public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
   		header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    	parent::__construct();
    }

	public function listproduct()
	{
		$method = $_SERVER['REQUEST_METHOD'];

		if($method != 'GET'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$check_auth_client = $this->MyModel->check_auth_client();
			if($check_auth_client == true){
		        $response = $this->MyModel->auth();
		        if($response['status'] == 200){
		        	$resp = $this->MyModel->list_product();
	    			json_output($response['status'],$resp);
		        }
			}
		}
	}
	
	
	public function order()
	{
		$method = $_SERVER['REQUEST_METHOD'];
		if($method != 'POST'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {

			$params = json_decode(file_get_contents('php://input'), TRUE);
			if ($params['kode_sepeda'] == "" || $params['lama_sewa'] == "" || $params['user_id'] == "" || $params['stand_id'] == "" || $params['waktu_sewa_start'] == "" || $params['waktu_sewa_end'] == "" ) {
				$respStatus = 400;
				$resp = array('status' => 400,'message' =>  'Kode Sepeda, Lama sewa, User id, Stand Id, waktu sewa start, waktu sewa end can\'t empty');
			} else {
			    $respStatus = 200;
			    $params['status'] = 1;
        		$resp = $this->MyModel->order_create_data($params);
			}
			json_output($respStatus,$resp);
		}
	}
	
	
	public function listtransaction()
	{
		$method = $_SERVER['REQUEST_METHOD'];
        $user_id = $_GET['user_id'];
		$is_riwayat = $_GET['is_riwayat']; // 1 untuk tab riwayat, 2 untuk tab sedang 
    
		if($method != 'GET'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$check_auth_client = $this->MyModel->check_auth_client();
			if($check_auth_client == true){
		        $response = $this->MyModel->auth();
		        if($response['status'] == 200){
		        	$resp = $this->MyModel->list_order($user_id, $is_riwayat);
	    			json_output($response['status'],$resp);
		        }
			}
		}
	}
	
	
	public function detailtransaction()
	{
		$method = $_SERVER['REQUEST_METHOD'];
        $no_order = $_GET['no_order'];
		
    
		if($method != 'GET'){
			json_output(400,array('status' => 400,'message' => 'Bad request.'));
		} else {
			$check_auth_client = $this->MyModel->check_auth_client();
			if($check_auth_client == true){
		        $response = $this->MyModel->auth();
		        if($response['status'] == 200){
		        	$resp = $this->MyModel->detail_order($no_order);
	    			json_output($response['status'],$resp);
		        }
			}
		}
	}
	
	
}
