<?php
class Yllapito_kalenterit extends CI_Controller
{
    
    private $allowed_user_groups = array('admin', 'jaos', 'jaos-yp', 'kisakalenteri');
    private $url;
    
    function __construct()
    {
        parent::__construct();
              
        $this->load->library('user_rights', array('groups' => $this->allowed_user_groups));
        if (!$this->user_rights->is_allowed()){       
            redirect($this->user_rights->redirect());
        }
       
       $this->load->model('Jaos_model');
       $this->load->model('Kisakeskus_model');

              $this->load->library('Kisajarjestelma');
              $this->load->library('Porrastetut');

        $this->url = "yllapito/kalenterit/";
        
    }
    
    private function _is_allowed_to_process_calendar($jaos, &$msg)	{
		//are you admin or editor?
        if(!($this->_is_jaos_admin() || ($this->Jaos_model->is_jaos_owner($this->ion_auth->user()->row()->tunnus, $jaos)))){
			$msg = "Jos et ole ylläpitäjä tai jaosvastaava, voit käsitellä vain omia jaoksiasi, tai niitä jaoksia joissa toimit kalenterityöntekijänä.";
			return false;
		}
		
		
		return true;		
		
	
    }
    
    private function _is_jaos_admin(){
        return $this->user_rights->is_allowed(array('admin', 'jaos'));
    }
    
    private function _is_jaos_owner($jaos){
        return $this->Jaos_model->is_jaos_owner($this->ion_auth->user()->row()->tunnus, $jaos, 1);
    }
    
    public function pipari(){
		$this->fuel->pages->render('misc/pipari');
	}
    
    
////////////////////////////////////////////////////////////////////////////7
// PANEELI
////////////////////////////////////////////////////////////////////////////7

    public function index(){
        $data = array();
        $only_active = false;
        $skip_shows = false;
        $only_leveled = false;
        $data['jaokset'] = $this->Jaos_model->get_jaos_list($only_active, $only_leveled, $skip_shows);
        $data['url'] = $this->url;
        
        foreach ($data['jaokset'] as &$jaos){
        
               // $this->_sort_panel_info_applications('hakemukset_porr', $this->raceApplicationsMaintenance($jaos['id'], true), $jaos);
               $this->_sort_panel_info_applications('hakemukset_norm', $this->raceApplicationsMaintenance($jaos['id'], false, $jaos), $jaos);
               //$this->_sort_panel_info_applications('tulokset_porr', $this->resultApplicationsMaintenance($jaos['id'], true), $jaos);
               $this->_sort_panel_info_applications('tulokset_norm', $this->resultApplicationsMaintenance($jaos['id'], false, $jaos), $jaos);
           
        }
        
        $data['porrastetut_amount'] = sizeof($this->porrastetut->get_resultless_leveled_competitions(100));
    
    	$this->fuel->pages->render('yllapito/kisakalenteri/kisakalenterit_etusivu', $data);


    }
    
    public function porrastetut_run(){
        
        $done = $this->porrastetut->generate_results_automatically($max = 10);
        $kpl = sizeof($this->porrastetut->get_resultless_leveled_competitions(100));

        $this->fuel->pages->render('misc/naytaviesti', array('msg_type' => 'success', 'msg' => $done .' kpl porrastettuja kisoja arvottu. ' . $kpl . ' kpl jäljellä. Paina f5 jos haluat arpoa lisää.'));

    }
    
    private function _sort_panel_info_applications($key, $data, &$jaos){
        $jaos[$key] = $data['kpl'];
        if($jaos[$key] > 0){
            $jaos[$key . '_latest'] = $data['ilmoitettu'];
        }

    }
    
    
    function raceApplicationsMaintenance( $jaos, $leveled = false, $jaosdata = array()) {
        
        $nayttelyt = false;
        
        if(isset($jaosdata['nayttelyjaos']) && $jaosdata['nayttelyjaos'] == 1){
            $nayttelyt = true;
        } else {
            $nayttelyt = $this->kisajarjestelma->nayttelyjaos($jaos);
        }
        
        $this->db->select('COUNT(kisa_id) as kpl, MIN(ilmoitettu) as ilmoitettu');
        if($nayttelyt){
            $this->db->from('vrlv3_kisat_nayttelykalenteri');

        } else {
            $this->db->from('vrlv3_kisat_kisakalenteri');
            $this->db->where('porrastettu', $leveled);
            $this->db->where ('vanha', 0);



        }
        $this->db->where('jaos', $jaos);
        $this->db->where('hyvaksytty', NULL);
        if($leveled){
            $this->load->library("Kisajarjestelma");     
            $this->db->where('ilmoitettu <', $this->kisajarjestelma->new_leveled_start_time());

        }
        
		 $query = $this->db->get();
        
        if ($query->num_rows() > 0)
        {
            return $query->result_array()[0]; 
        }else {
            return array('kpl'=>0, 'ilmoitettu'=>NULL);
        }
		
	}
    
    function resultApplicationsMaintenance( $jaos, $leveled = false, $jaosdata = array() ) {
        
        $nayttelyt = false;
        
        if(isset($jaosdata['nayttelyjaos']) && $jaosdata['nayttelyjaos'] == 1){
            $nayttelyt = true;
        } else {
            $nayttelyt = $this->kisajarjestelma->nayttelyjaos($jaos);
        }
        
        if($nayttelyt){
             $this->db->select('COUNT(t.bis_id) as kpl, MIN(t.ilmoitettu) as ilmoitettu');
            $this->db->from('vrlv3_kisat_nayttelytulokset as t');
            $this->db->join('vrlv3_kisat_nayttelykalenteri as k', 't.bis_id = k.kisa_id');

        } 
        else {
        
         $this->db->select('COUNT(t.tulos_id) as kpl, MIN(t.ilmoitettu) as ilmoitettu');
        $this->db->from('vrlv3_kisat_tulokset as t');
            $this->db->join('vrlv3_kisat_kisakalenteri as k', 't.kisa_id = k.kisa_id');
            $this->db->where ('k.vanha', 0);
            $this->db->where('k.porrastettu', $leveled);
        }
        $this->db->where('k.jaos', $jaos);    
        $this->db->where('t.hyvaksytty', NULL);

        
		 $query = $this->db->get();
        
        if ($query->num_rows() > 0)
        {
            return $query->result_array()[0]; 
        }else {
            return array('kpl'=>0, 'ilmoitettu'=>NULL);
        }
    			
	}
    
   
    
////////////////////////////////////////////////////////////////////////////////////////77
// Käsittele hyväksyttyjä
/////////////////////////////////////////////////////////////////////////////////////////
    
    public function hyvaksytytkisat($tapa = null, $type = null, $id = null){
        if($tapa == "delete"){
            $msg = "";
            if($this->_delete_competition($id, $type, $msg)){
                $this->fuel->pages->render('misc/naytaviesti', array('msg_type' => 'success', 'msg' => 'Kilpailu #'.$id.' poistettu!'));
            }else {
                $this->fuel->pages->render('misc/naytaviesti', array('msg_type' => 'danger', 'msg' => 'Kilpailu #'.$id.' poisto epäonnistui! ' . $msg));
            }
        }else if($tapa == "edit"){
            $data = array();
            if($this->_edit_competition($id, $type, $msg, $data)){
                

                $this->fuel->pages->render('misc/haku', $data);
                
            }else {
                $data['msg_type'] = 'danger';
                $data['msg'] = 'Kilpailu #'.$id.' muokkaus epäonnistui! ' . $msg;
                if(isset($data['form'])){
                    $this->fuel->pages->render('misc/haku', $data);
                }
                
                else {
                    $this->fuel->pages->render('misc/naytaviesti', $data);

                }
            }

            
        }else {      
        
            $data = $this->_hyvaksytyt_selaa(false, true, $this->url."hyvaksytytkisat");
            $data['title'] = "Kilpailukalenteri: Hyväksytyt kilpailut";
            $data['text_view'] = "Hakutuloksina näytetään 1000 viimeksi hyväksyttyä hakukriteereihin sopivaa kilpailua.";
    
            $this->fuel->pages->render('misc/haku', $data);
        }

    }
    
    private function _edit_competition($id, $type, &$msg = "", &$data = array()){
        $nayttelyt = false;
        $ok = true;

        if($type == "nayttelyt"){
               $nayttelyt = true;
           }
           
        $porrastettu = false;

        $kutsu = $this->Kisakeskus_model->hae_kutsutiedot($id, null, 0, $nayttelyt);
        if(!$nayttelyt){
            $porrastettu = $kutsu['porrastettu'];
        }
        if(sizeof($kutsu)>0){
            $jaos = $kutsu['jaos'];
             if($this->_is_jaos_owner($jaos) || $this->_is_jaos_admin()){
                 if($this->input->server('REQUEST_METHOD') == 'POST'){
                    
                     $kutsu_new = $this->kisajarjestelma->parse_competition_application(false);
                     if(!$this->kisajarjestelma->validate_competition_application($porrastettu, $nayttelyt, false)){
                             $msg = "Virhe syötetyissä tiedoissa.";
                             $ok = false;
                             
                     }else if(!$this->kisajarjestelma->check_competition_edit_info(array_merge($kutsu, $kutsu_new), $msg)){
                         $ok = false;
                     }else if (!$this->kisajarjestelma->edit_competition($id, $jaos, $kutsu_new, $msg)){
                        $ok = false;
                     } else {
                            $data['msg_type'] = 'success';
                            $data['msg'] = 'Kilpailu #'.$id.' muokattu!';
                     }
                     
                     $kutsu = array_merge($kutsu, $kutsu_new);
                     
                 
                 }
             
                 
                 $data['form'] = $this->kisajarjestelma->get_competition_application ("edit", $this->url."hyvaksytytkisat/edit/".$type."/".$kutsu['kisa_id'],
                                                                                      $porrastettu, $nayttelyt, $kutsu, $kutsu['jaos']);           
                 $data['title'] = "Muokkaa kilpailukutsua (#".$kutsu['kisa_id'];
                 if($porrastettu){
                     $data['title'] .= ", porrastettu)";
                 }else {
                     $data['title'] .= ")";
                 }
                 
             }else {
                 $msg = 'Vain ylläpitäjillä, jaosvastaavalla tai jaoksen ylläpitäkällä on oikeus muokata kutsuja.';
                 $ok = false;
 
             }
         }else {
             $msg = 'Kutsua ei löydy, tai sillä on jo hyväksytyt tai ilmoitetut tulokset!';
             $ok = false;

         }
         return $ok;
    }
    

    
    public function hyvaksytyttulokset($tapa = null, $type = null, $id = null){
        if($tapa == "delete"){
            $msg = "";
            if($this->_delete_result($id, $type, $msg)){
                $this->fuel->pages->render('misc/naytaviesti', array('msg_type' => 'success', 'msg' => 'Tulos #'.$id.' poistettu!'));
            }else {
                $this->fuel->pages->render('misc/naytaviesti', array('msg_type' => 'danger', 'msg' => 'Tuloksen #'.$id.' poisto epäonnistui! ' . $msg));
            }
        }else {      
        
            $data = $this->_hyvaksytyt_selaa(true, false, $this->url."hyvaksytyttulokset");
            $data['title'] = "Kilpailukalenteri: Hyväksytyt tulokset";
            $data['text_view'] = "Hakutuloksina näytetään 1000 viimeksi hyväksyttyä hakukriteereihin sopivaa tulosta.";
    
            $this->fuel->pages->render('misc/haku', $data);
        }
    }
    
    private function _hyvaksytyt_selaa($result, $competition, $url){
        $data = array();
        $nayttelyt = false;
        $data['search'] = array();
        $data['kisat'] = array();
        
        if($this->input->server('REQUEST_METHOD') == 'POST'){
            $data['search'] = $this->_read_search_form();
            if (!isset($data['search']['jaos'])){
             $data['msg'] = "Jaos on pakollinen hakukriteeri. ";
             $data['msg_type'] = "danger";
            }
            else if (!$this->_is_allowed_to_process_calendar($data['search']['jaos'], $msg)){
             $data['msg'] = "Sinulla ei ole oikeutta jaoksen ".$jaos_data['lyhenne']." kilpailukalenteriin. ";
             $data['msg_type'] = "danger";
            }else {
                $nayttelyt = $this->kisajarjestelma->nayttelyjaos($data['search']['jaos']);
                if($result){
                    $data['kisat'] = $this->Kisakeskus_model->search_results($data['search']['jaos'], $data['search'], $nayttelyt);
                }if($competition){
                    $data['kisat'] = $this->Kisakeskus_model->search_competitions($data['search']['jaos'], $data['search'], $nayttelyt);
                }
            }
            
        }
        

        $vars['headers'][1] = array('title' => 'Hyväksytty', 'key' => 'hyvaksytty', 'type'=>'date');
        $handle_id = "kisa";
        $result_url_id = "tulos";
            if($nayttelyt){
                $handle_id = "nayttelyt";
                $result_url_id = "bis";
            }

        if($result){
   
            $vars['headers'][2] = array('title' => '#', 'key' => 'tulos_id', 'key_link'=> site_url('kilpailutoiminta/tulosarkisto/'.$result_url_id.'/'), 'prepend_text' => '#');

        }else if ($competition){
            $vars['headers'][2] = array('title' => '#', 'key' => 'kisa_id');
        }
        $vars['headers'][3] = array('title' => 'KP', 'key' => 'kp', 'type'=>'date');
        $vars['headers'][4] = array('title' => 'VIP', 'key' => 'vip', 'type'=>'date');
        $vars['headers'][5] = array('title' => 'Jaos', 'key' => 'jaoslyhenne');
        $vars['headers'][6] = array('title' => 'Järjestäjä', 'key' => 'tunnus', 'key_link' => site_url('tunnus/'), 'prepend_text' => 'VRL-');
        $vars['headers'][7] = array('title' => 'Hyväksyjä', 'key' => 'hyvaksyi', 'key_link' => site_url('tunnus/'), 'prepend_text' => 'VRL-');

        
        if ($competition){
            $vars['headers'][8] = array('title' => 'Tulokset', 'key' => 'tulokset');
            $vars['headers'][9] = array('title' => 'Editoi', 'key' => 'kisa_id', 'key_link' => site_url($url) . "/edit/".$handle_id ."/", 'image' => site_url('assets/images/icons/edit.png'));
            $vars['headers'][10] = array('title' => 'Poista', 'key' => 'kisa_id', 'key_link' => site_url($url) . "/delete/" . $handle_id ."/", 'image' => site_url('assets/images/icons/delete.png'));
        }
        else if($result){
            $vars['headers'][8] = array('title' => 'Poista', 'key' => 'tulos_id', 'key_link' => site_url($url) . "/delete/" . $handle_id ."/", 'image' => site_url('assets/images/icons/delete.png'));

        }
        
        $vars['headers'] = json_encode($vars['headers']);
                
        $vars['data'] = json_encode($data['kisat']);
    
		$data['tulokset'] = $this->load->view('misc/taulukko', $vars, TRUE);
      
        
        $data['form'] = $this->_search_form($result, $competition, $url,  $data['search']);
        return $data;
    }
    
    
    
    private function _search_form($result, $competition, $url, $data = array()){
        $this->load->library('form_builder', array('submit_value' => 'Hae'));
        
        if($result){
             $fields['id_type'] = array('label'=>"Hae id:llä", 'type' => 'select', 'options' => array("kisa_id"=>"Kilpailun id", "tulos_id"=>"Tuloksen id"), 'value' => $data['id_type'] ?? 'tulos_id', 'class'=>'form-control');
        }
        else if($competition){
             $fields['id_type'] = array('type' => 'hidden', 'value' => "kisa_id", 'class'=>'form-control');
        }
        $fields['id'] = array('label' => 'ID', 'type' => 'number', 'value' =>  $data['id'] ?? "", 'class'=>'form-control', 'represents' => 'int|smallint|mediumint|bigint', 'negative' => FALSE, 'decimal' => FALSE);    

        $jaos_options = array();
        if($this->_is_jaos_admin()){
            $jaos_options = $this->Jaos_model->get_jaos_option_list(false, false, false);

        }else {
            $jaoslist = $this->Jaos_model->get_users_jaos($this->ion_auth->user()->row()->tunnus);
            foreach ($jaoslist as $jaos){
                $jaos_options[$jaos['id']]=$jaos['lyhenne'];
            }
        }
        
        $fields['jaos'] = array('type' => 'select', 'required'=> TRUE, 'options' => $jaos_options, 'value' => $data['jaos'] ?? "", 'class'=>'form-control');
        
        //$fields['kp'] = array('type' => 'date', 'first-day' => 1, 'date_format'=>'d.m.Y', 'label'=>'Päivämäärä', 'class'=>'form-control', 'value' => $data['kp'] ?? "");
        //$fields['vip'] = array('type' => 'date', 'first-day' => 1, 'date_format'=>'d.m.Y', 'label'=>'Viimeinen ilmoittautumispäivä', 'class'=>'form-control','value' => $data['vip'] ?? "");
        $fields['porrastettu'] = array('type' => 'checkbox', 'checked' => $data['porrastettu'] ?? false, 'class'=>'form-control');
        $fields['jarj_talli'] = array('type' => 'text', 'label'=>'Järjestävä talli', 'class'=>'form-control', 'value' => $data['jarj_talli'] ?? "",
                                    'after_html'=> '<span class="form_comment">Laita tunnus muodossa XXXX0000.');
        $fields['tunnus'] = array('type' => 'text', 'label'=>'Järjestäjä', 'class'=>'form-control', 'value' => $data['tunnus'] ?? "",
                                    'after_html'=> '<span class="form_comment">Laita tunnus muodossa VRL-00000.');
        $fields['hyvaksyi'] = array('type' => 'text', 'label'=>'Hyväksyi', 'class'=>'form-control', 'value' => $data['hyvaksyi'] ?? "",
                                    'after_html'=> '<span class="form_comment">Laita tunnus muodossa VRL-00000.');

        $arvontatapa_options = $this->kisajarjestelma->arvontatavat_options();
        $arvontatapa_options[0] = "";
        $fields['url'] = array('type' => 'text', 'class'=>'form-control','value' => $data['url'] ?? "");
        $fields['arvontatapa'] = array('type' => 'select', 'options' => $arvontatapa_options, 'value' => $data['arvontatapa'] ?? 0, 'class'=>'form-control');    
        
        $this->form_builder->form_attrs = array('method' => 'post', 'action' => site_url($url));
    
        return $this->form_builder->render_template('_layouts/basic_form_template', $fields);

}

private function _read_search_form(){
    $data = array();
    
    $this->_read_basic_input_field($data, 'id_type');
    $this->_read_basic_input_field($data, 'id');
    $this->_read_basic_input_field($data, 'jaos');
    $this->_read_basic_input_field($data, 'id_type');
    $this->_read_basic_input_field($data, 'kp');
    $this->_read_basic_input_field($data, 'vip');
    $this->_read_basic_input_field($data, 'jarj_talli');
    $this->_read_basic_input_field($data, 'tunnus');
    $this->_read_basic_input_field($data, 'hyvaksyi');
    $this->_read_basic_input_field($data, 'url');
    $this->_read_basic_input_field($data, 'arvontatapa');
    
    if($this->input->post('porrastettu')){
        $data['porrastettu'] = 1;
    }else {
        $data['porrastettu'] = 0;
    }

    return $data;
}

private function _read_basic_input_field(&$data, $field){
        if($this->input->post($field)){
        $data[$field] = $this->input->post($field);
    }
    
}



 
    private function _delete_competition($kisa_id, $type, &$msg){
        $ok = true;
        $db  = 'vrlv3_kisat_kisakalenteri';
        $nayttelyt = false;
        if($type == "nayttelyt"){
            $db = 'vrlv3_kisat_nayttelykalenteri';
            $nayttelyt = true;
        }
        
        $this->db->trans_start();
                
        $this->db->select('*');
        $this->db->from($db.' as k');
        $this->db->where('kisa_id', $kisa_id);
        $this->db->where('tulokset', 0);
        $this->db->where('k.hyvaksytty is NOT NULL', NULL, FALSE);
                
        $query = $this->db->get();
        
        if ($query->num_rows() > 0)
        {
            $id = $query->result_array()[0]['kisa_id'];
            $jaos  = $query->result_array()[0]['jaos'];
            $item = "Kilpailukutsu";
            if($nayttelyt){
                $item = "Näyttelykutsu";
            }
            if($this->_is_jaos_owner($jaos) || $this->_is_jaos_admin()){
                $this->db->delete($db, array('jaos'=>$jaos, 'kisa_id'=>$id));
                $this->load->model('Tunnukset_model');
                $this->Tunnukset_model->send_message($this->ion_auth->user()->row()->tunnus, $query->result_array()[0]['tunnus'] ,
                                                        $item. " #".$query->result_array()[0]['kisa_id']." on poistettu kalenterista! Jos et tiedä miksi, ole yhteydessä jaoksen ylläpitoon!");

                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE)
                {
                    $msg = "Virhe kutsun #".$id." poistossa. Yritä hetken kuluttua uudelleen, tai ole yhteydessä ylläpitoon.";        
                     $ok = false;
                }

            }else {
              $this->db->trans_complete();
              $msg = "Vain jaosvastaavalla ja jaoksen ylläpitäjällä on oikeus poistaa kilpailuja kalenterista";
              $ok = false;
            }

        } else {
            echo $this->db->last_query();
              $this->db->trans_complete();
              $msg = "Kilpailua ei löytynyt, tai siitä on jo ilmoitettu tulokset.";
              $ok = false;
        }
        
        return $ok;

    }
    
    
    
    
    
    private function _delete_result($result_id, $type, &$msg){
        $ok = true;
        
        $db_kisa  = 'vrlv3_kisat_kisakalenteri';
        $db_tulos  = 'vrlv3_kisat_tulokset';
        $db_kisa_id_ref = "kisa_id";
        $db_res_id = "tulos_id";
        $nayttelyt = false;
        if($type == "nayttelyt"){
            $db_kisa = 'vrlv3_kisat_nayttelykalenteri';
            $db_tulos = 'vrlv3_kisat_nayttelytulokset';
            $db_kisa_id_ref = "nayttely_id";
            $db_res_id = "bis_id";

            $nayttelyt = true;
        }
        $this->db->trans_start();
                
        $this->db->select('*, t.'.$db_kisa_id_ref.' as kisa_id');
        $this->db->from($db_tulos.' as t');
        $this->db->join($db_kisa. ' as k', 'k.kisa_id = t.'. $db_kisa_id_ref);
        $this->db->where('t.'. $db_res_id, $result_id);
        $this->db->where('t.hyvaksytty is NOT NULL', NULL, FALSE);
        $this->db->where('t.hyvaksytty !=','0000-00-00 00:00:00');

                
        $query = $this->db->get();
        
        if ($query->num_rows() > 0)
        {
            $id = $query->result_array()[0][$db_res_id];
            $jaos = $query->result_array()[0]['jaos'];
            if($this->_is_jaos_owner($jaos) || $this->_is_jaos_admin()){
                $this->db->delete($db_tulos, array($db_res_id=>$id));
                
                $this->db->where('kisa_id', $query->result_array()[0]['kisa_id']);
                $this->db->update($db_kisa, array('tulokset'=> 0));
                
                $this->load->model('Tunnukset_model');
                $item = "Kilpailun";
                if($nayttelyt){
                    $item = "Näyttelyn";
                }
                $this->Tunnukset_model->send_message($this->ion_auth->user()->row()->tunnus, $query->result_array()[0]['tunnus'] ,
                                                         $item." #".$query->result_array()[0]['kisa_id']." tulokset on poistettu arkistosta! Jos et tiedä miksi, ole yhteydessä jaoksen ylläpitoon!");

                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE)
                {
                    $msg = "Virhe tuloksen #".$id." poistossa. Yritä hetken kuluttua uudelleen, tai ole yhteydessä ylläpitoon.";        
                     $ok = false;
                }

            }else {
              $this->db->trans_complete();
              $msg = "Vain jaosvastaavalla ja jaoksen ylläpitäjällä on oikeus poistaa tuloksia kalenterista";
              $ok = false;
            }

        } else {
              $this->db->trans_complete();
              $msg = "Tulosta ei löytynyt.";
              $ok = false;
        }
        
        return $ok;

    }
	
    
    

	
   
   ///////////////////////////////////////////////////////////////////////////////
    // HYVÄKSYNNÄT: Kisakalenterin ylläpitofunktiot
    //////////////////////////////////////////////////////////////////////////////
    
   
    public function kisahyvaksynta ($jaos = null, $kasittele = null, $kisa_id = null){
        $data=array();
        $this->load->model('Tunnukset_model');
        $jaos_data = $this->Jaos_model->get_jaos($jaos);
        $msg = "";
        
        if(empty($jaos)){
            $only_active = false;
         $skip_shows = false;
        $only_leveled = false;
        $data['jaokset'] = $this->Jaos_model->get_jaos_list($only_active, $only_leveled, $skip_shows);
            $data['url'] = $this->url;
            
            foreach ($data['jaokset'] as &$jaos){
               $this->_sort_panel_info_applications('hakemukset_norm', $this->raceApplicationsMaintenance($jaos['id'], false, $jaos), $jaos);
            }
            
            $this->fuel->pages->render('yllapito/kisakalenteri/kisakalenteri_kisahyvaksynta_main', $data);

        
        } 
        
        else if(sizeof($jaos_data) == 0){
            $data['msg'] = "Hakemaasi Jaosta (".$jaos.") ei ole olemassa.";
            $data['msg_type'] = "danger";
                        $this->fuel->pages->render('misc/naytaviesti', array($data));

        }else if (!$this->_is_allowed_to_process_calendar($jaos, $msg)){
             $data['msg'] = "Sinulla ei ole oikeutta jaoksen ".$jaos_data['lyhenne']." kilpailukalenteriin. " . $msg;
            $data['msg_type'] = "danger";
                        $this->fuel->pages->render('misc/naytaviesti', array($data));

        }else {

            if(isset($kasittele) && $kasittele == 'hyvaksy'){
                if($this->_approve_competition($jaos, $kisa_id, true)){
                    $data['msg'] = "Kutsu hyväksytty.";
                    $data['msg_type'] = "success";
                }else {
                    $data['msg'] = "Kutsua #".$kisa_id." ei enää löydy, tai se on jonkun toisen käsiteltävänä.";
                    $data['msg_type'] = 'danger';
    
                }
                
            }else if( isset($kasittele) && $kasittele == 'hylkaa'){
                if($this->_approve_competition($jaos, $kisa_id, false, $this->input->post("viesti", TRUE))){
                    $data['msg'] = "Kutsu hylätty.";
                    $data['msg_type'] = "success";
                }else {
                    $data['msg'] = "Kutsua #".$kisa_id." ei enää löydy, tai se on jonkun toisen käsiteltävänä.";
                    $data['msg_type'] = 'danger';
    
                }
            }
            
            $data['kutsu'] = $this->_competitions_queue_get_next($jaos, false);
            
            if(isset($data['kutsu']) &&  sizeof($data['kutsu']) > 0){
                $this->load->model('Sport_model');
                $data['kutsu']['laji'] = $this->Sport_model->get_sport_info($data['kutsu']['laji'])['painotus'];
                $this->load->model("Tallit_model");
                $data['talli'] = $this->Tallit_model->get_stable($data['kutsu']['jarj_talli']);
                $data['jaos'] = $jaos_data;
                $data['kutsu']['arvontatapa'] = $this->kisajarjestelma->arvontatavat_options_legacy()[$data['kutsu']['arvontatapa']];
                $user = $this->ion_auth->user($this->Tunnukset_model->get_users_id($data['kutsu']['tunnus']))->row();
                $data['username'] = $user->nimimerkki;
                $data['user_email'] = $user->email;
                $data['user_vrl'] = $this->vrl_helper->get_vrl($user->tunnus);
            }
            
            $this->fuel->pages->render('yllapito/kisakalenteri/kisahyvaksynta', $data);

            

        }
        
        


        
    }
    

    public function tuloshyvaksynta ($jaos = null, $kasittele = null, $kisa_id = null){
        $data=array();
        $this->load->model('Tunnukset_model');
        $jaos_data = $this->Jaos_model->get_jaos($jaos);
        $msg = "";
        
        if(empty($jaos)){
            $only_active = false;
            $skip_shows = false;
            $only_leveled = false;
            $data['jaokset'] = $this->Jaos_model->get_jaos_list($only_active, $only_leveled, $skip_shows);
            $data['url'] = $this->url;
            
            foreach ($data['jaokset'] as &$jaos){
               $this->_sort_panel_info_applications('tulokset_norm', $this->resultApplicationsMaintenance($jaos['id'], false), $jaos);
               if($jaos['nayttelyt'] != 1){
                $this->_sort_panel_info_applications('tulokset_porr', $this->resultApplicationsMaintenance($jaos['id'], true), $jaos);
               }

            }
            $this->fuel->pages->render('yllapito/kisakalenteri/kisakalenteri_tuloshyvaksynta_main', $data);
        } 
        
        else if(sizeof($jaos_data) == 0){
            $data['msg'] = "Hakemaasi Jaosta (".$jaos.") ei ole olemassa.";
            $data['msg_type'] = "danger";
            $this->fuel->pages->render('misc/naytaviesti', array($data));

        }else if (!$this->_is_allowed_to_process_calendar($jaos, $msg)){
             $data['msg'] = "Sinulla ei ole oikeutta jaoksen ".$jaos_data['lyhenne']." kilpailukalenteriin. " . $msg;
            $data['msg_type'] = "danger";
                        $this->fuel->pages->render('misc/naytaviesti', array($data));

        }else {

            if(isset($kasittele) && $kasittele == 'hyvaksy'){
                if($this->_approve_result($jaos, $kisa_id, true)){
                    $data['msg'] = "Tulokset hyväksytty.";
                    $data['msg_type'] = "success";
                }else {
                    $data['msg'] = "Tulosta jota hait ei enää löydy, tai se on jonkun toisen käsiteltävänä.";
                    $data['msg_type'] = 'danger';
                    
    
                }
                
            }else if( isset($kasittele) && $kasittele == 'hylkaa'){
                if($this->_approve_result($jaos, $kisa_id, false, $this->input->post("viesti", TRUE))){
                    $data['msg'] = "Tulos hylätty.";
                    $data['msg_type'] = "success";
                }else {
                    $data['msg'] = "Tulosta jota hait ei enää löydy, tai se on jonkun toisen käsiteltävänä.";
                    $data['msg_type'] = 'danger';
    
                }
            }
            
            $tulos_info = $this->_results_queue_get_next($jaos);
            
            if(isset($tulos_info) &&  sizeof($tulos_info) > 0){
                $tulos_info['jaos_info'] = $this->Jaos_model->get_jaos($tulos_info['jaos']);
                $this->load->model('Tallit_model');
                $tulos_info['talli_info'] = $this->Tallit_model->get_stable($tulos_info['jarj_talli']);
                $this->load->library('Kisajarjestelma');
                $this->load->model("Tunnukset_model");
                $tulos_info['tunnus'] =  $this->ion_auth->user($this->Tunnukset_model->get_users_id($tulos_info['tunnus']))->row();
                $tulos_info['tulosten_lah'] = $this->ion_auth->user($this->Tunnukset_model->get_users_id($tulos_info['tulosten_lah']))->row();
                if(isset($tulos_info['takaaja']) && $tulos_info['takaaja'] != 00000){
                    $tulos_info['takaaja'] =  $this->ion_auth->user($this->Tunnukset_model->get_users_id($tulos_info['takaaja']))->row();
                }
                unset($tulos_info['hyvaksyi']);
                $tulos_info['jarjestelma']= & $this->kisajarjestelma;
                
                $nayttelyt =  $tulos_info['jaos_info']['nayttelyt'];                
                
                
                 if($nayttelyt){
                    $tulos_info['tulos_id'] = $tulos_info['bis_id'];
                    $tulos_info['kisa_id'] = $tulos_info['nayttely_id'];
                    $tulos_info['porrastettu'] = false;
                    
                }
                $data['tulos'] = $tulos_info;
                $data['tulos_info'] = $this->load->view('kilpailutoiminta/tulos_info', array("tulos" => $data['tulos']), TRUE);
                $luokat = "";
                if($nayttelyt){
                    
                    $bis_rivit = $this->Kisakeskus_model->get_showresult_rewards($tulos_info['bis_id']);
                    $taulu['headers'][1] = array('title' => 'Palkinto', 'key' => 'palkinto');
                    $taulu['headers'][2] = array('title' => 'Hevonen', 'key' => 'vh_nimi');
                    $taulu['headers'][3] = array('title' => 'Reknro', 'key' => 'vh_id', 'type'=>'VH', 'key_link' => site_url('virtuaalihevoset/hevonen/'));        
                    $taulu['headers'] = json_encode($taulu['headers']);
                            
                    $taulu['data'] = json_encode($bis_rivit);
                    $bis_tulokset = $this->load->view('misc/taulukko', $taulu, TRUE);
                     $data['luokat_info'] = $this->load->view('kilpailutoiminta/tulos_nayttelyt', array("tulokset" => $tulos_info, "bistulokset"=>$bis_rivit, "bistaulu"=>$bis_tulokset), TRUE);

                    

                }else {
                    
                    
                     $data['luokat_info'] = $this->load->view('kilpailutoiminta/tulos_luokat', array("tulos" => $tulos_info), TRUE);

                }
                
          
            }
            
            
            
            
            $this->fuel->pages->render('yllapito/kisakalenteri/tuloshyvaksynta', $data);
        }
        

        
    }

    
    
    private function _competitions_queue_get_next($jaos, $porrastettu = false){
        if($this->kisajarjestelma->nayttelyjaos($jaos)){
            $this->db->from('vrlv3_kisat_nayttelykalenteri as k');
            return $this->_get_next('vrlv3_kisat_nayttelykalenteri', $jaos);


        } else {
            $this->db->from('vrlv3_kisat_kisakalenteri as k');
            $this->db->where('k.porrastettu', $porrastettu);
            return $this->_get_next('vrlv3_kisat_kisakalenteri', $jaos);
        }


    }
    
    private function _results_queue_get_next($jaos, $porrastettu = false){
        if($this->kisajarjestelma->nayttelyjaos($jaos)){
            $this->db->select('t.*, k.kp, k.vip, t.ilmoitettu, k.tunnus as tunnus, k.url, t.tunnus as tulosten_lah, k.jarj_talli, k.info, k.tunnus, k.jaos, k.arvontatapa');
            $this->db->from('vrlv3_kisat_nayttelykalenteri as k');
            $this->db->join('vrlv3_kisat_nayttelytulokset as t', 'k.kisa_id = t.nayttely_id');
          return $this->_get_next('vrlv3_kisat_nayttelytulokset', $jaos);
        }
        
        else {
            $this->db->select('t.*, k.kp, k.vip, t.ilmoitettu, k.tunnus as tunnus, k.url, k.porrastettu, t.tunnus as tulosten_lah, k.jarj_talli, k.info, k.tunnus, k.jaos, k.arvontatapa');
            $this->db->from('vrlv3_kisat_kisakalenteri as k');
            $this->db->join('vrlv3_kisat_tulokset as t', 'k.kisa_id = t.kisa_id');
          return $this->_get_next('vrlv3_kisat_tulokset', $jaos);
        }
    }
    
    
     private function _get_next($table, $jaos)
    {
        $result = false;
        $show = false;
        $letter = 'k';
        
        if($table == "vrlv3_kisat_tulokset" || $table == 'vrlv3_kisat_nayttelytulokset'){
            $result = true;
        }
        if($table == "vrlv3_kisat_nayttelytulokset" || $table == 'vrlv3_kisat_nayttelykalenteri'){
            $show = true;
        }
        
        if($result){
              $letter= 't';          

        }
        $data = array();
        $date = new DateTime();
        $date->setTimestamp(time() - 60*15); //nykyhetki miinus 15min, eli ei saa ottaa samaa jonoitemiä uudestaan käsittelyyn 15 minuuttiin

        
        $this->db->where('k.jaos', $jaos);
        $this->db->where('k.vanha', 0);
        $this->db->group_start();
        $this->db->where($letter.'.hyvaksytty IS NULL OR '.$letter.'.hyvaksytty = \'0000-00-00 00:00:00\'');
        $this->db->group_end();
        $this->db->group_start();
        $this->db->where($letter.'.kasitelty IS NULL OR '.$letter.'.kasitelty < \'' . $date->format('Y-m-d H:i:s') . '\'');
        $this->db->group_end();
        $this->db->order_by($letter.".ilmoitettu", "asc"); 
        $query = $this->db->get();
        
        if ($query->num_rows() > 0)
        {
            $data = $query->row_array(); 

            $date->setTimestamp(time());
            $user = $this->ion_auth->user()->row();
            $update_data = array('kasitelty' => $date->format('Y-m-d H:i:s'), 'kasittelija'=> $this->ion_auth->user()->row()->tunnus);
            
            $id_key = "kisa_id";
            if($show && $result){
                $id_key = "bis_id";
            }else if($result){
                $id_key = 'tulos_id';
            }
            $this->db->where($id_key, $data[$id_key]);
            $this->db->update($table, $update_data);
                        
        }
        
        return $data;
    }
    
    private function _approve_competition ($jaos, $kisa_id, $approve, $disapprove_msg = false){
        $processing_ok = false;
        $nayttelyt = false;
        $db_table = 'vrlv3_kisat_kisakalenteri';
        
        if($this->kisajarjestelma->nayttelyjaos($jaos)){
            $nayttelyt = true;
            $db_table = 'vrlv3_kisat_nayttelykalenteri';
        }
        
        
        $vrl = $this->ion_auth->user()->row()->tunnus;
        $this->db->trans_start();
        $this->db->select('*');
        
        $this->db->from($db_table);
        
    
        $this->db->where('jaos', $jaos);
        $this->db->where('kisa_id', $kisa_id);
        $this->db->where('kasittelija', $vrl);
        $this->db->where('hyvaksytty', NULL);
        $query = $this->db->get();

        
        if ($query->num_rows() > 0)
        {
            if($approve){
                
                $date = new DateTime();
                $date->setTimestamp(time());
                $insert_data = array('hyvaksytty'=> $date->format('Y-m-d H:i:s'), 'hyvaksyi'=>$vrl);
                $where_data = array('jaos'=> $jaos, 'kisa_id' => $kisa_id, 'kasittelija' => $vrl);
                $this->db->where($where_data);
                $this->db->update($db_table, $insert_data);

                $this->load->model('Tunnukset_model');
                if($nayttelyt){
                    $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['tunnus'] , "Näyttely #".$query->result_array()[0]['kisa_id']." on hyväksytty kalenteriin!");

                }else {               
                    $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['tunnus'] , "Kilpailukutsu #".$query->result_array()[0]['kisa_id']." on hyväksytty kalenteriin!");
                }
            }
            
            else {
                    $where_data = array('jaos'=> $jaos, 'kisa_id' => $kisa_id, 'kasittelija' => $vrl);
                    $this->db->delete($db_table, $where_data);
                    $this->load->model('Tunnukset_model');
                    if($nayttelyt){
                        $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['tunnus'] ,
                                                         "Näyttely #".$query->result_array()[0]['kisa_id']." on hylätty! Syy: " . $disapprove_msg);
                    } else {
                        $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['tunnus'] ,
                                                         "Kilpailukutsu #".$query->result_array()[0]['kisa_id']." on hylätty! Syy: " . $disapprove_msg);

                    }


            }
            
            $this->db->trans_complete();    
            return true;
        }else {
             $this->db->trans_complete();
             return false;
        }

        
        
    }
    
     private function _approve_result ($jaos, $kisa_id, $approve, $disapprove_msg = false){
        $processing_ok = false;
        $nayttelyt = false;
        $db_comp_table = "";
        $db_res_table = "";
        $db_res_id = "";
        $db_comp_ref_id ="";
        
        $this->db->trans_start();

        $vrl = $this->ion_auth->user()->row()->tunnus;

        if($this->kisajarjestelma->nayttelyjaos($jaos)){
            $nayttelyt = true;
            $db_comp_table = 'vrlv3_kisat_nayttelykalenteri';
            $db_res_table = 'vrlv3_kisat_nayttelytulokset';
            $db_res_id = "bis_id";
            $db_comp_ref_id = "nayttely_id";
            $this->db->select('k.*, t.tunnus as ilmoittaja, t.ilmoitettu as ilmoitettu, t.bis_id');

        }else {
            $db_comp_table = 'vrlv3_kisat_kisakalenteri';
            $db_res_table = 'vrlv3_kisat_tulokset';
            $db_res_id = "kisa_id";
            $db_comp_ref_id = "kisa_id";
            $this->db->select('k.*, t.tunnus as ilmoittaja, t.ilmoitettu as ilmoitettu, t.luokat, t.tulokset, t.hylatyt, t.tulos_id');

        }
        
        $this->db->from($db_comp_table . " as k");
        $this->db->join($db_res_table . " as t", 'k.kisa_id = t.' . $db_comp_ref_id);
        $this->db->where('jaos', $jaos);
        $this->db->where('k.kisa_id', $kisa_id);
        $this->db->where('t.kasittelija', $vrl);
        $this->db->where('t.hyvaksytty', NULL);
        $query = $this->db->get();
        
        
        if ($query->num_rows() > 0)
        {
            if($approve){
                
                $date = new DateTime();
                $date->setTimestamp(time());
                $insert_data = array('hyvaksytty'=> $date->format('Y-m-d H:i:s'), 'hyvaksyi'=>$vrl);
                $where_data = array($db_comp_ref_id => $kisa_id, 'kasittelija' => $vrl);
                $this->db->where($where_data);
                $this->db->update($db_res_table, $insert_data);
                
                                //pikaviesti
                $this->load->model('Tunnukset_model');
                
                if(!$nayttelyt){
                    //statistiikka
                    $this->kisajarjestelma->add_stats($query->result_array()[0], $jaos, $query->result_array()[0]['porrastettu']);
                                    
                    //etuuspisteet
                    if($query->result_array()[0]['porrastettu'] == 0){
                        $ilmo_tunnus = $query->result_array()[0]['ilmoittaja'];
                        $takaaja_tunnus = $query->result_array()[0]['takaaja'];
        
                        //onko takaajan ilmoittama?
                        $takaaja = false;
                        if(isset($takaaja_tunnus) && $takaaja_tunnus != '00000' && $takaaja_tunnus == $ilmo_tunnus){
                            $takaaja = true;
                        }
                        $this->kisajarjestelma->add_etuuspisteet($ilmo_tunnus, $jaos, $query->result_array()[0]['kp'], $query->result_array()[0]['ilmoitettu'], $takaaja);
                    }
                    //ominaisuuspisteet
                    else {
                        $tulos_id = $query->result_array()[0]['tulos_id'];
                        $this->porrastetut->approve_propertyPoints_from_queue($tulos_id);
                    }
                    
                    $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['ilmoittaja'] , "Kilpailukutsun #".$query->result_array()[0]['kisa_id']." tulokset on hyväksytty!");

                
                } else {
                    $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['ilmoittaja'] , "Näyttelyn #".$query->result_array()[0]['kisa_id']." tulokset on hyväksytty!");

                }

                    
                

                
            }
            
            else {
                    $where_data = array($db_comp_ref_id => $kisa_id, 'kasittelija' => $vrl);
                    $this->db->delete($db_res_table, $where_data);
                    $insert_data = array('tulokset'=>0);
                    $where_data = array('jaos'=> $jaos, 'kisa_id' => $kisa_id);
                    $this->db->where($where_data);
                    $this->db->update($db_comp_table, $insert_data);
                    
                    $this->load->model('Tunnukset_model');
                    
                    if($nayttelyt){
                        $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['tunnus'] ,
                                                         "Näyttelyn #".$query->result_array()[0]['kisa_id']." tulokset on hylätty! Syy: " . $disapprove_msg);
  
                    }else  {
                        $this->Tunnukset_model->send_message($vrl, $query->result_array()[0]['tunnus'] ,
                                                         "Kilpailukutsun #".$query->result_array()[0]['kisa_id']." tulokset on hylätty! Syy: " . $disapprove_msg);
                    }


            }
            
            $this->db->trans_complete();    
            return true;
        }else {
             $this->db->trans_complete();
             return false;
        }
        
     }



    
}
    

?>