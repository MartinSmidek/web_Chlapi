<?php

  // volba verze jádra Ezer
  $kernel= "ezer3.1"; 
  $_GET['pdo']= 1;

  // hostující servery
  $ezer_server= 
    $_SERVER["SERVER_NAME"]=='chlapi.bean'      ? 0 : (       // 0:lokální NTB
    $_SERVER["SERVER_NAME"]=='chlapi.cz'        ? 1 : (       // 1:Synology - YMCA
    $_SERVER["SERVER_NAME"]=='192.168.1.213'    ? 1 : (       // 1:Synology - YMCA (lokálně)
    $_SERVER["SERVER_NAME"]=='ezer.smidek.eu'   ? 2 : (       // 2:endora
    $_SERVER["SERVER_NAME"]=='chlapi.doma'      ? 3 : -1)))); // Synology - DOMA

  $app=      'rr';
  $app_name= 'Myšlenky Richarda Rohra';
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
      "/var/services/web/www/chlapi",
      "C:/Ezer/beans/chlapi.online",
    );
  $rel_roots= array(
      "http://chlapi.bean:8080",
      "http://chlapi.cz",
      "http://chlapi.cz",
      "http://chlapi.doma",
      "http://chlapi.ben:8080",
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
      echo "rr_send=$html";
      break;
    case 'rr-test':
      $html= rr_send((object)array('den'=>'','poslat'=>0,'opakovat'=>0));
      echo "rr_send=$html";
      break;
    }
  }
  else {
    // je to standardní aplikace se startem v kořenu
    require_once("$kernel/ezer_main.php");
  }
?>
