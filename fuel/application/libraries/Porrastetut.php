<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Porrastetut
{

    private $CI;
    
    private $levels = array(
                            0=>array("point_min"=>0, "point_max"=>200, "points_to_level_up"=>200, "min_age"=>3),
                            1=>array("point_min"=>201, "point_max"=>600, "points_to_level_up"=>400, "min_age"=>4),
                            2=>array("point_min"=>601, "point_max"=>1000, "points_to_level_up"=>400, "min_age"=>4),
                            3=>array("point_min"=>1001, "point_max"=>1400, "points_to_level_up"=>400, "min_age"=>5),
                            4=>array("point_min"=>1401, "point_max"=>1800, "points_to_level_up"=>400, "min_age"=>5),
                            5=>array("point_min"=>1801, "point_max"=>2400, "points_to_level_up"=>600, "min_age"=>6),
                            6=>array("point_min"=>2401, "point_max"=>3000, "points_to_level_up"=>600, "min_age"=>6),
                            7=>array("point_min"=>3001, "point_max"=>3800, "points_to_level_up"=>800, "min_age"=>7),
                            8=>array("point_min"=>3801, "point_max"=>4600, "points_to_level_up"=>800, "min_age"=>7),
                            9=>array("point_min"=>4601, "point_max"=>5600, "points_to_level_up"=>1000, "min_age"=>8),
                            10=>array("point_min"=>5601, "point_max"=>6600, "points_to_level_up"=>1000, "min_age"=>8)
                                                   
                            );
    private $aste = array(1=> "Seurataso", 2=>"Aluetaso", 3=> "Kansallinen taso");
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model("Trait_model");
        $this->CI->load->model("Jaos_model");
        $this->CI->load->model("Hevonen_model");
    }
    
    public function get_levels(){
        return $this->levels;
    }
    
    public function get_level_by_points($points){
        $level = 0;
        
        foreach ($this->levels as $nro=>$info){
            if($points > $info['point_max']){
                $level = $nro + 1;
            }else {
                break;
            }
        }
        
        return $level;
        
    }
    
    public function is_horse_allowed_in_class_level($horselevel, $classlevel){
        // Hevonen saa kilpailla vaan omalla tasollaan ja yhtä ylemmällä tasolla. 			
				// Jos hevosen taso on 2 ja luokan taso 0
				// Jos hevosen taso on sama kuin luokan taso
				// Jos hevosen taso on yhtä isompi kuin luokan taso | hevonen lv 2 voi osallistua lk lv3
        return ( ($horselevel == 1 && $classlevel = 0) OR
					( $horselevel == 2 && $classlevel == 1 ) OR
					$horselevel == $classlevel OR 
					($horselevel+1) == $classlevel);
    }

    
    public function get_porrastetut_jaokset(){
        return $this->CI->Jaos_model->get_jaos_porr_list();
    }
    
    public function get_all_porrastettu_info(){
        $full_info = array();
        $jaokset =  $this->CI->Jaos_model->get_jaos_porr_list();
        foreach ($jaokset as $jaos){
            $full_info[$jaos['id']]['jaos'] = $jaos;
            $full_info[$jaos['id']]['traits'] = $this->get_traits($jaos['id']);
            $full_info[$jaos['id']]['classes'] = $this->get_classes_by_jaos($jaos['id']);
        }
        
        return $full_info;
            
    }
    
    public function get_classes_by_jaos($id){ 
        return $this->CI->Jaos_model->get_class_list($id, true, true);
    }
    
    public function get_asteet(){
        return $this->aste;
    }
    
    //Ominaisuudet
    public function get_traits($jaos = null){
        return $this->CI->Trait_model->get_trait_list($jaos);
    }
    
    public function get_trait_names_array(){
        $traits = $this->get_traits();
        $full_trait_list = array();
        
        foreach ($traits as $trait){
            $full_trait_list[$trait['id']] = $trait['ominaisuus'];
        }
        
        return $full_trait_list;
    }
    
    public function get_empty_trait_array(){
        $traits = $this->get_traits();
        $full_trait_list = array();
        
        foreach ($traits as $trait){
            $full_trait_list[$trait['id']] = 0.00;
        }
        
        return $full_trait_list;
    }
    
    
    
    public function get_horses_full_traitlist($reknro, $full_trait_list = array()){
        if(sizeof($full_trait_list) < 1){
            $full_trait_list = $this->get_empty_trait_array();

        }
        
        if(isset($reknro)){
            $horse_traits = $this->CI->Hevonen_model->get_horse_traits($reknro);
            foreach ($horse_traits as $horse){
                $full_trait_list[$horse['id']] = $horse['arvo'];
            }
        }
        return $full_trait_list;
    
    }
    
    
    public function get_horses_point_sum_for_sport($horse_list, $jaos, $jaos_traits = null){
        if(!isset($jaos_traits)){
            $jaos_traits = $this->get_traits($jaos);
        }
        
        $sum = 0.00;           
        foreach ($jaos_traits as $trait){
            $sum += $horse_list[$trait['id']];
        }
        
        return $sum;
    }
    
    
    
    public function get_horses_full_level_list($reknro, $empty_trait_list = array(), $jaokset=array(), $full_jaos_info = array()){
        $full_info = array();
        if(sizeof($jaokset) < 1){
            $jaokset =  $this->CI->Jaos_model->get_jaos_porr_list();
        }
        $horse_list = $this->get_horses_full_traitlist($reknro, $empty_trait_list);
        
        foreach ($jaokset as $jaos){
            $jaos_traits = $full_jaos_info[$jaos['id']]['traits'] ?? null;

            $full_info[$jaos['lyhenne']]['points'] = $this->get_horses_point_sum_for_sport($horse_list, $jaos['id'], $jaos_traits);           
            $full_info[$jaos['lyhenne']]['level'] = $this->get_level_by_points($full_info[$jaos['lyhenne']]['points']);

        }
        
        return $full_info;

    }
        
        
        

        //periytyminen
        
    public function get_foals_traitlist($i, $e){
        $empty = $this->get_empty_trait_array();
        $i_list = $this->get_horses_full_traitlist($i);
        $e_list = $this->get_horses_full_traitlist($e);
        $foal = array();
        foreach ($empty as $id=>$trait){
            $foal[$id] = (($i_list[$id] + $e_list[$id])/2)*0.25;
        }
        return $foal;
    }
    
    
    //ikä
    
    public function level_by_age($age){
        $level = -1;
        
        foreach ($this->levels as $nro=>$info){
            if($age >= $info['min_age']){
                $level = $nro;
            }else {
                break;
            }
        }
        
        return $level;
        
    }
    
    public function check_horses_age( $vh ) {

	$vh = $this->CI->vrl_helper->vh_to_number( $vh );	
	
	$age = $this->CI->Hevonen_model->get_hevonen_ages($vh);
    if(sizeof($age) == 0){
        $ageNow = 0;
						
    }else {
        $ageNow = $this->calculate_age($age);   
    }
	
	// print '-'.$ageNow.'-';
	return $ageNow;
}

public function calculate_age($age, $today = null){
    
    if (!isset($today)){
        $today = date('Y-m-d');
    }
    
        if( $age['3vuotta'] != '0000-00-00' AND !empty($age['3vuotta']) ) {
        
            if( empty($age['4vuotta']) OR $age['4vuotta'] == '0000-00-00' ) { $age['4vuotta'] = strtotime( "+1 year", $today ); }
            if( empty($age['5vuotta']) OR $age['5vuotta'] == '0000-00-00' ) { $age['5vuotta'] = strtotime( "+1 year", $today ); }
            if( empty($age['6vuotta']) OR $age['6vuotta'] == '0000-00-00' ) { $age['6vuotta'] = strtotime( "+1 year", $today ); }
            if( empty($age['7vuotta']) OR $age['7vuotta'] == '0000-00-00' ) { $age['7vuotta'] = strtotime( "+1 year", $today ); }
            if( empty($age['8vuotta']) OR $age['8vuotta'] == '0000-00-00' ) { $age['8vuotta'] = strtotime( "+1 year", $today ); }
        
            
            if ( $today >= strtotime($age['3vuotta']) AND $today < strtotime($age['4vuotta']) ) {
                $ageNow = 3;
                // print '-'.$today.' >= '.strtotime($age['3vuotta']).'-';
            } elseif ( $today >= strtotime($age['4vuotta']) AND $today < strtotime($age['5vuotta']) ) {
                $ageNow = 4;
            } elseif ( $today >= strtotime($age['5vuotta']) AND $today < strtotime($age['6vuotta']) ) {
                $ageNow = 5;
            } elseif ( $today >= strtotime($age['6vuotta']) AND $today < strtotime($age['7vuotta']) ) {
                $ageNow = 6;
            } elseif ( $today >= strtotime($age['7vuotta']) AND $today < strtotime($age['8vuotta']) ) {
                $ageNow = 7;
            } elseif ( $today >= strtotime($age['8vuotta']) ) {
                $ageNow = 8;
            }
        } else {
            $ageNow = 0;
        }
        
        return $ageNow;
}

public function get_resultless_leveled_competitions($max = 100){
        $this->CI->db->select('kisa_id');
        $this->CI->db->from('vrlv3_kisat_kisakalenteri as k');
        $this->CI->db->where('k.vanha', 0);
        $this->CI->db->where('k.porrastettu', 1);
        $this->CI->db->where('k.tulokset', 0);
        $this->CI->db->where('k.kp <=', date('Y-m-d'));
        $this->CI->db->where('k.hyvaksytty is NOT NULL', NULL, FALSE);
        $this->CI->db->where("EXISTS(SELECT * FROM vrlv3_kisat_kisaluokat as l WHERE l.kisa_id = k.kisa_id)");
        $this->CI->db->order_by('k.kp', 'asc');
        $this->CI->db->limit($max);

        $query = $this->CI->db->get();
        
        if ($query->num_rows() > 0)
        {
            return $query->result_array();
        }else {
            return array();
        }
}
    
    
    ////////////////////////////////////////////////////////////////////777
    // ARVO TULOKSET
    ///////////////////////////////////////////////////////////////////////////7
    
    public function generate_results_automatically($max = 10){
        $kpl = 0;
        $kisat = $this->get_resultless_leveled_competitions($max);
        foreach($kisat as $kisa){
            $this->CI->db->trans_start();
            $kisa =  $this->CI->Kisakeskus_model->hae_kutsutiedot($kisa['kisa_id'], null, 0);
            var_dump($kisa);
            if($kisa['tulokset'] == 0){
                $this->ilmoita_tulokset_porrastetut($kisa, 00000);
                $kpl = $kpl + 1;
            }
            $this->CI->db->trans_complete();

            
        }
        
        return $kpl;
        
        
    }
    
    public function ilmoita_tulokset_porrastetut($kisa, $user){ 
    
    $kantaan_luokat = "";
    $kantaan_tulokset = "";
    $kantaan_hylsyt = "";
    
    foreach ($kisa['luokat'] as $luokka){
        
        $classinfo = $luokka;
        $participants = array();
        foreach ($luokka['osallistujat'] as $rivi){
            $participants[] = $rivi['rimpsu'];
        }
        
        $this->handle_porrastettu_class_results($kisa['jaos'], $kisa, $classinfo, $participants, $kantaan_luokat, $kantaan_tulokset, $kantaan_hylsyt);

        
    }
    
        $tulos = array();
        $tulos['tunnus'] = $user;
        $tulos['ilmoitettu'] = date('Y-m-d H:i:s');
        $tulos['tulokset'] = $kantaan_tulokset;
        $tulos['hylatyt'] = $kantaan_hylsyt;
        $tulos['luokat'] = $kantaan_luokat;
        $tulos['kisa_id'] = $kisa['kisa_id'];
    

        $this->CI->db->insert('vrlv3_kisat_tulokset', $tulos);
        $this->CI->db->set('tulokset', 1);
        $this->CI->db->where('kisa_id', $tulos['kisa_id']);
        $this->CI->db->update('vrlv3_kisat_kisakalenteri');
        
        return true;     
                
    }
    
    
    private function _get_leveled_info($vhs){
        if(sizeof($vhs) > 0){
            $this->CI->db->from('vrlv3_hevosrekisteri as h');
            $this->CI->db->join('vrlv3_hevosrekisteri_ikaantyminen as i', 'h.reknro = i.reknro', 'LEFT');
            $this->CI->db->join('vrlv3_hevosrekisteri_ominaisuudet as o', 'h.reknro = o.reknro', 'LEFT');
    
            $this->CI->db->where_in('h.reknro', $vhs);
            
            $query = $this->CI->db->get();
            ECHO $this->CI->db->last_query();
            
            if ($query->num_rows() > 0)
            {
                return $query->result_array(); 
            }else {
                return array();
            }
        }
        
        else {
            return array();
        }

    }
    
    private function _parse_leveled_info_horses($list){
        $horses = array();
        foreach ($list as $row){
            $horses[$row['reknro']] = $row;
        }
        
        return $horses;
        
    }
    
    private function _parse_leveled_info_skills($list, $needed_skills){
        $horses = array();
        foreach ($list as $row){
            if(in_array($row['ominaisuus'], $needed_skills)){
                
                if(isset($horses[$row['reknro']])){
                    $horses[$row['reknro']] = $horses[$row['reknro']] + $row['arvo'];
                }else {
                 $horses[$row['reknro']] = $row['arvo'];   
                }
                
                
            }
        }
        
        return $horses;
    }
    
    
public function handle_porrastettu_class_results($jaos, $kisa, $classinfo, $participants, &$kantaan_luokat, &$kantaan_tulokset, &$kantaan_hylsyt){

    list($accepted, $failed) = $this->_generateResults ( $kisa['kp'], $participants, $classinfo, $kisa['jaos']  );

    $kantaan_luokat = $kantaan_luokat . $classinfo['nimi'] . "\n";
    
     // Listataan tulokset tietokantamuotoon				

    $rank = 0;		
    foreach ($accepted as $key => $list) {
        $rank++; 
        $kantaan_tulokset .= $rank.'. '.$list['horse']." \n";
    }
    
    // Listataan tulokset tietokantamuotoon				
    foreach ($failed as $key => $list) {
        $rank++; 
        $kantaan_hylsyt .= $list['horse']." (".$list['reason'].") \n";
    }
    
    $kantaan_tulokset = $kantaan_tulokset .  "~";
    $kantaan_hylsyt = $kantaan_hylsyt .  "~";
}


        
    
    private function _generateResults ( $date, $participants, $classinfo, $jaos  ) {

        //haetaan luokkien ja ominaisuuksien tiedot
        $ominaisuudetlist = $this->CI->Trait_model->get_trait_array_by_jaos($jaos);
        
      
        //haetaan osallistuneiden VH-numerot        
        $vh_list = array();
        
        $participants = array_diff( $participants, array(''));  
        foreach ($participants as $rivi){
            //VH-tunnus-tarkistelu
					$tunnuksia = preg_match_all('/\VH[0-9]{2}\-[0-9]{3}\-[0-9]{4}/', $rivi , $osumat);
					
					if ($tunnuksia == 1){
                        $vh_list[] =  $this->CI->vrl_helper->vh_to_number($osumat[0]);

                    }
        }

        
        //Haetaan kaikki hevosten tieto valmiksi
        
        $leveled_info = $this->_get_leveled_info($vh_list);
        $leveled_info_horses = $this->_parse_leveled_info_horses($leveled_info);
        $leveled_info_skills = $this->_parse_leveled_info_skills($leveled_info, $ominaisuudetlist);
    
        $accepted = array();
        $failed = array();
        // print_r( $participants ); print '<hr />';
        
        for ($i = 0; $i < count($participants); $i++) {
            $horse = $participants[$i];
            $tunnuksia = preg_match_all('/\VH[0-9]{2}\-[0-9]{3}\-[0-9]{4}/', $horse , $osumat);
            // 1. Tarkistetaan, onko rivillä VH-tunnusta ja löytyykö se infotaulukosta
            if( $tunnuksia == 1 && isset($leveled_info_horses[$this->CI->vrl_helper->vh_to_number($osumat[0])])) {
                // 1.0 Otetaan VH-tunnus talteen ja jatketaan
                $vh = $this->CI->vrl_helper->vh_to_number($osumat[0]);
    
                
                // 1.1. Tarkista hevosen ikä ja millä tasolla hevgonen on 
                $age = $this->calculate_age($leveled_info_horses[$vh], $date);
                $horselevel = 0;
                if(isset($leveled_info_skills[$vh])){
                    $horselevel = $this->get_level_by_points($leveled_info_skills[$vh]);
                } else {
                    $horselevel = $this->get_level_by_points(0);
                }
                
                // 1.2. Tarkasta hevosen säkäkorkeus ja rotu
                $height = $leveled_info_horses[$vh]['sakakorkeus'];
                $breed = $leveled_info_horses[$vh]['rotu'];
                
                
                if ( $age >= 3 ) {
                    
                    if ($this->is_horse_allowed_in_class_level($horselevel, $classinfo['taso'] ))
                        {
                    
                        if ( $height >= $classinfo['minheight'] ) {
    
                            
                            // print $classinfo[0]['minheight'].'vs'.$height;
                            
                            // 1.3. Hae hevosen ominaisuuspisteet
                            $horsePropertyPoints = $leveled_info_skills[$vh];
                            
                            // 1.4. Laske hevoselle pistemäärä ja lisää se taulukkoon $accepted
                            
                            $pointsInClass = ( $horsePropertyPoints / 3 )+(rand(0,100)/100) * ( (rand(0,100)/100) + (rand(0,100)/100) + (rand(0,100)/100) + 1.00);
                            $accepted[] = array('vh' => $vh, 'horse' => $participants[$i], 'points' => $pointsInClass);
                            
                            
                        } else {
                            $failed[] = array('horse' => $horse, 'reason' => 'Hevosella ei ole riittävää säkäkorkeutta');
                        }
                        
                    } else {
                        $failed[] = array('horse' => $horse, 'reason' => 'Hevosen taso ei ole riittävä tai se on liian korkea: lk. '.$classinfo['taso'].' vs. hevonen '.$horselevel);
                    }
                } else {
                    $failed[] = array('horse' => $horse, 'reason' => 'Hevonen on liian nuori kilpailemaan');
                }
                
                
            } else if ( !empty($horse) AND $horse != '' ) {
                $failed[] = array('horse' => $horse, 'reason' => 'Hevosella ei ole VH-tunnusta');
                
            }
        }
        
        // 2.0 Järjestä kaikki osallistujat taulukossa $accepted
        foreach ($accepted as $key => $row) {
            $points[$key] = $row['points'];
            $vhIdentification[$key]  = $row['vh'];
        }
        
        array_multisort($points, SORT_DESC, $vhIdentification, SORT_DESC, $accepted);
        
        // print_r($accepted);
        
        // 2.5 Järjestä failed-taulussa kaikki osallistujat
        foreach ($failed as $key => $row) {
            $horseIdentification[$key]  = $row['horse'];
            $reason[$key] = $row['reason'];
        }
        
        array_multisort($horseIdentification, SORT_DESC, $reason, SORT_DESC, $failed);
            
        $results = array($accepted, $failed);
        
        // 3. Palauta $results
        return $results;
    }
    
    
    
}