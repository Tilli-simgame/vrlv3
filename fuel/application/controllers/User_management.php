<?php
   defined('BASEPATH') OR exit('No direct script access allowed');

   class User_management extends CI_Controller {

       public function __construct()
       {
           parent::__construct();
           $this->load->model('fuel_users_model');
       }

       public function add_user()
       {
           // Ensure only authorized users can access this
           //if (!$this->fuel->auth->has_permission('users')) {
            //   show_error('You do not have permission to add users.');
           //}

           $user_data = array(
               'user_name' => 'new_username',
               'email' => 'user@example.com',
               'first_name' => 'John',
               'last_name' => 'Doe',
               'password' => 'strong_password',
               'active' => 'yes'
           );

           $user_id = $this->fuel_users_model->save($user_data);

           if ($user_id) {
               echo "User successfully added with ID: " . $user_id;
           } else {
               echo "Error adding user: " . $this->fuel_users_model->get_validation()->error_string();
           }
       }
   }