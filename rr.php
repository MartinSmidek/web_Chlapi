<?php

  // volba verze jádra Ezer
  $kernel= "ezer3.1"; 
  $_GET['pdo']= 2;

  // hostující servery
  $ezer_server= 
    $_SERVER["SERVER_NAME"]=='chlapi.bean'      ? 0 : (       // 0:lokální NTB
    $_SERVER["SERVER_NAME"]=='chlapi.cz'        ? 1 : (       // 1:Synology - YMCA
    $_SERVER["SERVER_NAME"]=='192.168.7.111'    ? 1 : (       // 1:Synology - YMCA (pro cron!!!) 
    $_SERVER["SERVER_NAME"]=='chlapi.doma'      ? 3 : -1))); // Synology - DOMA

  $app=      'rr';
  $app_name= 'Myšlenky Richarda Rohra / CAC';
  $app_root=  'rr';
  $app_js=    array();
  $app_css=   array("rr/rr.css");
  $skin=     'default';
  $skin=     'ch';
  
  // cesty
  $abs_roots= array(
      "C:/Ezer/beans/chlapi.online",
      "/var/services/web/www/chlapi",
      "/var/services/web/www/chlapi",
      "/var/services/web/www/chlapi"
    );
  $rel_roots= array(
      "http://chlapi.bean:8080",
      "https://chlapi.cz",
      "https://chlapi.cz",
      "http://chlapi.doma"
    );
  
  // (re)definice Ezer.options
  $favicons= array('chlapi_rr_ico_local.png',
      'chlapi_rr_ico.png','chlapi_rr_ico.png','chlapi_rr_ico.png');
  $add_pars= array(
    'favicon' => $favicons[$ezer_server],
    'CKEditor' => "{
      version:'4.6',
      Cac:{toolbar:[['Bold','Italic','-','Outdent','Indent','-','Source']]}
    }"
  );

  if ( isset($_GET['batch']) && $_GET['batch'] ) {
    // batch - verze
    echo($_SERVER["SERVER_NAME"].'<br>');
//    require_once("$kernel/pdo.inc.php");
//    require_once("$kernel/server/ezer_pdo.php");
//    require_once("$kernel/server/ae_slib.php");
//    require_once("rr/rr_fce.php");
    $ezer_root= 'rr';
    session_start();
    $_SESSION[$ezer_root]['ezer_server']= $ezer_server;
    $_SESSION[$ezer_root]['ezer']= "3.1";
    $_SESSION[$ezer_root]['abs_root']= $abs_roots[$ezer_server];
    $_SESSION[$ezer_root]['rel_root']= $rel_roots[$ezer_server];
    $_SESSION[$ezer_root]['pdo']= $_GET['pdo'];
    $_POST['root']= $ezer_root;
    require_once("rr.inc.php");
    switch ($_GET['batch']) {
    case 'rr-today':
      $html= rr_send((object)array('den'=>'','poslat'=>1,'opakovat'=>0));
      echo "rr_send/sent=$html";
      $html= cac_get_new_medits();
      echo "<hr><h2>Daily Meditations from CAC</h2><br>$html";
      break;
    case 'rr-test':
      $html= rr_send((object)array('den'=>'','poslat'=>0,'opakovat'=>0));
      echo "rr_send/test=$html";
      $html= cac_get_new_medits();
      echo "<hr><h2>Daily Meditations from CAC</h2><br>$html";
      break;
    }
  }
  else {
    // je to standardní aplikace se startem v kořenu
    require_once("$kernel/ezer_main.php");
  }
?>
