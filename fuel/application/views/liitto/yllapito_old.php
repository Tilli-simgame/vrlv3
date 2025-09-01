<h1>Ylläpito & yhteydenotto</h1>

<p>Virtuaalisen ratsastajainliiton johdossa on VRL:n työryhmän käsittävä ylläpito, jonka alaisuudessa toimii kourallinen osa-alueiden vastaavia. Kaikilla osa-alueiden vastaavilla on alaisuudessaan vaihteleva määrä työntekijöitä. Koko liittoa pyöritetään täysin vapaaehtoistoimin.</p>

<p>Otathan aina ensisijaisesti yhteyttä tuen kautta <a href="<?php echo site_url();?>/tuki"><?php echo site_url();?>tuki</a>, josta eri osa-alueiden työntekijät pääsevät käsittelemään kysymyksesi. Tuessa on useita eri kategorioita, joista voit valita sopivimman. Useamman asian ollessa kyseessä tee niistä erilliset ilmoitukset. Mikäli asiasi ei koske varsinaisesti tukiasioita, ota yhteyttä vastuuhenkilöihin, joiden vastuualueet ja sähköpostiosoitteet löydät alta.</p>
<div class="panel panel-default yllapito">
  <div class="panel-heading">
    <h3 class="panel-title">Ylläpito</h3>
  </div>
  <div class="panel-body">
   <p>Ylläpito vastaa VRL:n palvelimesta rahallisesti, ja tekee päätökset uusien ominaisuuksien ohjelmoimisesta. Ylläpitoon otetaan myös yhteys esimerkiksi sponsori- ja mainosasioissa. Ylläpito myös vastaa suurista sääntömuutoksista, ja siitä, että osa-alueiden vastaavat hoitavat tehtävänsä. </p>
   <p><b>Ylläpitäjinä toimivat:</b><br>
      <?php print_users($users['admin']);?>
    
    </p>
  </div>
   <div class="panel-footer">vrlvirallinen@gmail.com</div>
</div>

<div class="panel panel-default yllapito">
  <div class="panel-heading">
    <h3 class="panel-title">Tunnus-, työvoima- ja jaosvastaavat</h3>
  </div>
  <div class="panel-body">

  
<p>Tunnusvastaava vastaa VRL-tunnusten hyväksynnästä, tuplatunnusten käsittelystä ja väärinkäyttötapauksissa tunnusten jäähylle asettamisesta sekä jäädyttämisestä. </p>

<p>Työvoimavastaavan tehtäviin kuuluu uusien työntekijöiden rekrytointi ja valinta, osa-alueiden vastaavien rekrytointi ja valinta yhdessä ylläpidon kanssa sekä rekistereiden ja rekisterityöntekijöiden valvonta kilpailujaoksia lukuun ottamatta.</p>


<p>Jaosvastaava valvoo, että kaikki jaokset toimivat ja noudattavat yhteisiä sääntöjä. Hän toimii yhteyshenkilönä jaosten ja VRL:n ylläpidon välillä, rekrytoi tarpeen tullen uusia apukäsiä jaosten tehtäviin, vie läpi sääntömuutokset ja vastaa jaosten ylläpitäjien kysymyksiin.</p>


<p>Ota yhteyttä tunnus-, työvoima- ja jaosvastaaviin <a href="<?php echo site_url();?>tuki" title="Ota yhteyttä tuen kautta">tuen</a>  kautta. </p>
   <table width="100%">
    
    <tr>
      <td><b>Tunnusvastaava</b></td><td><strong>Työvoimavastaava</strong></td><td><strong>Jaosvastaava</strong></td>
    </tr>
    <tr>
      <td valign="top"><?php print_users($users['tunnukset']);?></td>
      <td valign="top"><?php print_users($users['tyovoima']);?></td>
      <td valign="top"><?php print_users($users['jaos']);?></td>
    </tr>
   
   </table>
  </div>
  <div class="panel-footer">Ota yhteyttä tunnus-, työvoima- ja jaosvastaaviin <a href="<?php echo site_url();?>tuki" title="Ota yhteyttä tuen kautta">tuen</a> kautta.</div>
</div>



<div class="panel panel-default yllapito">
  <div class="panel-heading">
    <h3 class="panel-title">Rekisterityöntekijät</h3>
  </div>
  <div class="panel-body">
<p>Hevos-, talli- ja kasvattajanimirekistereissä työskentelee vapaaehtoisia, jotka huolehtivat rekisterien sujuvasta toiminnasta ja virheiden korjaamisesta sekä ylläpidon luomien sääntöjen noudattamisesta. </p>

<p>Ota yhteyttä rekisterityöntekijöihin <a href="<?php echo site_url();?>tuki" title="Ota yhteyttä tuen kautta">tuen</a>  kautta. </p>
   <table width="100%">
    
    <tr>
      <td><b>Hevosrekisteri</b></td><td><strong>Tallirekisteri</strong></td><td><strong>Kasvattajanimet</strong></td>
    </tr>
    <tr>
      <td valign="top"><?php print_users($users['hevosrekisteri']);?></td>
      <td valign="top"><?php print_users($users['tallirekisteri']);?></td>
      <td valign="top"><?php print_users($users['kasvattajanimet']);?></td>
    </tr>
   
   </table>
  </div>
  <div class="panel-footer">Ota yhteyttä rekisterityöntekijöihin <a href="<?php echo site_url();?>tuki" title="Ota yhteyttä tuen kautta">tuen</a> kautta.</div>
</div>



<!---älä koske -->
<?php
function print_users($array){

    if(isset($array) && sizeof($array) > 0) {
      echo '<ul>';
      foreach  ($array as $user){
      echo '<li><a href="' . site_url('tunnus/'.$user['tunnus']) . '">VRL-' . $user['tunnus'] . '</a> ' . $user['nimimerkki'] . '</li>';
      }
    } else {
      echo '<li>Tällä hetkellä ei kukaan.</li>';
    }
    echo '</ul>';

}

?>
