<?php # (c) 2010 Martin Smidek <martin@smidek.eu>

  # nastavení jádra před voláním AJAX
  #   $app        = kořenová podsložka aplikace ... db2
  #   $answer_db  = logický název hlavní databáze (s případným '_test')
  #   $dbs_plus   = pole s dalšími databázemi ve formátu $dbs
  #   $php_lib    = pole s *.php - pro 'ini'

  global // import 
    $ezer_root; 
  global // export
    $EZER, $ezer_server, $ezer_version;
  global // klíče
    $api_gmail_user, $api_gmail_pass;
  
  // vyzvednutí ostatních hodnot ze SESSION
  $ezer_server=  $_SESSION[$ezer_root]['ezer_server'];
  $ezer_version= $_SESSION[$ezer_root]['ezer'];
  $abs_root=     $_SESSION[$ezer_root]['abs_root'];
  $rel_root=     $_SESSION[$ezer_root]['rel_root'];
  chdir($abs_root);

// obnov skryté definice, cesty, databáze, aktuální server
//  $abs_root= $_SESSION['rr']['abs_root'];
//  chdir($abs_root);
  $deep_root= "../files/chlapi";
  require_once("$deep_root/rr.dbs.php");

  // inicializace objektu Ezer
  $EZER= (object)array(
      'version'=>'ezer'.$_SESSION['rr']['ezer'],
      'options'=>(object)array(),
      'activity'=>(object)array()
  );

  // informace pro debugger o poloze ezer modulů
  $dbg_info= (object)array(
    'src_path'  => array('rr','man','ezer3.1') // poloha a preference zdrojových modulů
  );

  // použité funkce
  $app_php= array('rr/rr_fce.php');
  
  // je to standardní aplikace
  require_once("{$EZER->version}/ezer_ajax.php");
  
?>
