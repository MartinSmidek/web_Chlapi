<?php

  // vnucení parametrů
//  $kernel= "ezer3.1"; 
  $_GET['pdo']= 2;

  // verze použitého jádra Ezeru
  $ezer_version= isset($_GET['ezer']) ? $_GET['ezer'] : '3.1'; 

  // základní údaje o aplikaci
  $app=      'rr';
  $app_name= 'Myšlenky Richarda Rohra / CAC';
  $app_root=  'rr';
  $app_js=    array("rr/rr_fce.js");
  $app_css=   array("rr/rr.css");
//  $skin=     'default';
  $skin=     'ch';
  
  // skryté definice, cesty, databáze, detekce serveru
  $deep_root= "../files/chlapi";
  require_once("$deep_root/rr.dbs.php");

  // (re)definice Ezer.options
  global $icon;
  $add_pars= array(
    'favicon' => $icon,
    'watch_ip' => 0,    
    'watch_key' => 0,    
    'watch_pin' => 0,    
    'CKEditor' => "{
      version:'4.6',
      Cac:{toolbar:[['Styles','-','Undo','Redo','-','Bold','Italic','-','Outdent','Indent','-','Source']],
        stylesSet:[
          {name:'odstavec',  element:'p'},
          {name:'bible',     element:'span', attributes:{'class':'bible'}}
        ],
        contentsCss:'man/css/edit.css'
      }
    }"
  );

  if ( isset($_GET['batch']) && $_GET['batch'] ) {
    // batch - verze
    echo($_SERVER["SERVER_NAME"].'<br>');
    $ezer_root= 'rr';
    session_start();
    $_SESSION[$ezer_root]['ezer_server']= $ezer_server;
    $_SESSION[$ezer_root]['ezer']= "3.1";
    $_SESSION[$ezer_root]['abs_root']= $abs_root; //s[$ezer_server];
    $_SESSION[$ezer_root]['rel_root']= $rel_root; //s[$ezer_server];
    $_SESSION[$ezer_root]['pdo']= $_GET['pdo'];
    $_POST['root']= $ezer_root;
    require_once("rr.inc.php");
    switch ($_GET['batch']) {
    case 'rr-today':
      $html= rr_send((object)array('den'=>'','poslat'=>1,'opakovat'=>0));
      echo "rr_send/sent=$html";
      break;
    case 'rr-test':
      $html= rr_send((object)array('den'=>'','poslat'=>0,'opakovat'=>0));
      echo "rr_send/test=$html";
      $html= cac_read_medits('TEST');
      echo "<hr><h2>Daily Meditations from CAC</h2><br>$html";
      break;
    case 'rr-cac':
      $stamp= cac_read_medits('AUTO');
      echo "<hr><h2>Daily Meditations from CAC</h2><br>$html";
      break;
    case 'rr-note':
      $stamp= note_send('*');
      echo "<hr><h2>Odeslání připomenutí</h2><br>$html";
      break;
    }
  }
  else {
    // je to standardní aplikace se startem v kořenu
    require_once("ezer$ezer_version/ezer_main.php");
//    require_once("$kernel/ezer_main.php");
  }
?>
