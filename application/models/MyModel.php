<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MyModel extends CI_Model {

    var $client_service = "frontend-client";
    var $auth_key       = "simplerestapi";

    public function check_auth_client(){
        $client_service = $this->input->get_request_header('Client-Service', TRUE);
        $auth_key  = $this->input->get_request_header('Auth-Key', TRUE);
        if($client_service == $this->client_service && $auth_key == $this->auth_key){
            return true;
        } else {
            return json_output(401,array('status' => 401,'message' => 'Unauthorized.'));
        }
    }

    public function login($username,$password)
    {
        $q  = $this->db->select('password,id,role_id')->from('users')->where('username',$username)->get()->row();
        if($q == null){
            return array('status' => 400,'message' => 'Username not found.');
        } else {
            $hashed_password = $q->password;
            $id              = $q->id;
            $role            = $q->role_id;
            if (hash_equals($hashed_password, $password)) {
               $last_login = date('Y-m-d H:i:s');
               $token = crypt(substr( md5(rand()), 0, 7));
               $expired_at = date("Y-m-d H:i:s", strtotime('+12 hours'));
               $this->db->trans_start();
               $this->db->where('id',$id)->update('users',array('last_login' => $last_login));
               $this->db->insert('users_authentication',array('users_id' => $id,'token' => $token,'expired_at' => $expired_at));
               if ($this->db->trans_status() === FALSE){
                  $this->db->trans_rollback();
                  return array('status' => 500,'message' => 'Internal server error.');
               } else {
                  $this->db->trans_commit();
                  return array('status' => 200,'message' => 'Successfully login.','id' => $id, 'token' => $token, 'role' => $role);
               }
            } else {
               return array('status' => 400,'message' => 'Wrong password.');
            }
        }
    }

    public function logout()
    {
        $users_id  = $this->input->get_request_header('User-ID', TRUE);
        $token     = $this->input->get_request_header('Authorization', TRUE);
        $this->db->where('users_id',$users_id)->where('token',$token)->delete('users_authentication');
        return array('status' => 200,'message' => 'Successfully logout.');
    }

    public function auth()
    {
        $users_id  = $this->input->get_request_header('User-ID', TRUE);
        $token     = $this->input->get_request_header('Authorization', TRUE);
        $q  = $this->db->select('expired_at')->from('users_authentication')->where('users_id',$users_id)->where('token',$token)->get()->row();
        if($q == ""){
            return json_output(401,array('status' => 401,'message' => 'Unauthorized.'));
        } else {
            if($q->expired_at < date('Y-m-d H:i:s')){
                return json_output(401,array('status' => 401,'message' => 'Your session has been expired.'));
            } else {
                $updated_at = date('Y-m-d H:i:s');
                $expired_at = date("Y-m-d H:i:s", strtotime('+12 hours'));
                $this->db->where('users_id',$users_id)->where('token',$token)->update('users_authentication',array('expired_at' => $expired_at,'updated_at' => $updated_at));
                return array('status' => 200,'message' => 'Authorized.');
            }
        }
    }

    public function user_profile_data($roleid,$users_id)
    {   
        if($roleid == 1){
            return $this->db->select('id,username,name,nim,jurusan,email')->from('users')->where('id',$users_id)->get()->row();
        }else{
            return $this->db->select('id,username,name,nik,email')->from('users')->where('id',$users_id)->get()->row();
        }
    }

    // public function book_detail_data($id)
    // {
    //     return $this->db->select('id,title,author')->from('books')->where('id',$id)->order_by('id','desc')->get()->row();
    // }


    public function user_create_data($data)
    {
        if($this->db->insert('users',$data)){
         return array('status' => 201,'message' => 'Your Register Success');
        }else{
        return array('status' => 403,'message' => 'Failed Register');
        }
        
    }
    
    
    public function list_product()
    {   
        $this->db->select('standpark.nama, sepeda.total_sepeda as total, standpark.lat, standpark.long');
        $this->db->from('trx_sepeda_list as sepeda');
        $this->db->join('tm_standpark as standpark', 'standpark.id = sepeda.standpark_id');
        $this->db->join('tm_sepeda', 'tm_sepeda.id = sepeda.sepeda_id','left');
        $this->db->where('standpark.active', 1); 
        $query = $this->db->get()->result();
        return $query;
    }
    
    
    
    public function order_create_data($data)
    {
        if($this->db->insert('trx_pemesanan_sepeda',$data)){
         return array('status' => 201,'message' => 'Your Order Succes, please wait confrim approval from satpam');
        }else{
        return array('status' => 403,'message' => 'Failed Order');
        }
    }
    
    
    
     public function list_order($user_id, $is_riwayat)
    {   
        if($is_riwayat == 1){
            $this->db->select('sepeda.nama, pemesanan.status as status, pemesanan.waktu_sewa_end as pengembalian, pemesanan.waktu_sewa_start as peminjaman , pemesanan.lama_sewa');
            $this->db->from('trx_pemesanan_sepeda as pemesanan');
            $this->db->join('tm_sepeda as sepeda', 'sepeda.kode_sepeda = pemesanan.kode_sepeda','left');
            $this->db->where('pemesanan.user_id', $user_id); 
            $this->db->where('pemesanan.status != ',1,FALSE);
            $query = $this->db->get()->result();
            return $query;
        }else{
            $this->db->select('sepeda.nama, pemesanan.status as status, pemesanan.waktu_sewa_end as pengembalian, pemesanan.waktu_sewa_start as peminjaman,  pemesanan.lama_sewa');
            $this->db->from('trx_pemesanan_sepeda as pemesanan');
            $this->db->join('tm_sepeda as sepeda', 'sepeda.kode_sepeda = pemesanan.kode_sepeda','left');
            $this->db->where('pemesanan.user_id', $user_id);
            $this->db->where_in('pemesanan.status', array(1,2));
            $query = $this->db->get()->result();
            return $query;
        }
    }
    
    
     public function detail_order($no_order)
    {   
        $this->db->select('pemesanan.no_order as kode_pemesanan, user.nim, user.name as nama, standpark.nama as standpark, sepeda.harga_sewa as biaya_pinjam, sepeda.kode_sepeda,  sepeda.nama, pemesanan.waktu_sewa_end as pengembalian, pemesanan.waktu_sewa_start as peminjaman , pemesanan.lama_sewa');
        $this->db->from('trx_pemesanan_sepeda as pemesanan');
        $this->db->join('users as user', 'user.id = pemesanan.user_id','left');
        $this->db->join('tm_sepeda as sepeda', 'sepeda.kode_sepeda = pemesanan.kode_sepeda','left');
         $this->db->join('tm_standpark as standpark', 'standpark.id = pemesanan.stand_id','left');
        $this->db->where('pemesanan.no_order', $no_order);
        $query = $this->db->get()->result();
        return $query;
    }

    // public function book_update_data($id,$data)
    // {
    //     $this->db->where('id',$id)->update('books',$data);
    //     return array('status' => 200,'message' => 'Data has been updated.');
    // }

    // public function book_delete_data($id)
    // {
    //     $this->db->where('id',$id)->delete('books');
    //     return array('status' => 200,'message' => 'Data has been deleted.');
    // }

}
