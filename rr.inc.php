<?php # (c) 2010 Martin Smidek <martin@smidek.eu>

  global // import 
    $ezer_root; 
  global // export
    $EZER, $ezer_server;
  
  // vyzvednutí ostatních hodnot ze SESSION
  $ezer_server=  $_SESSION[$ezer_root]['ezer_server'];
  $kernel= "ezer{$_SESSION[$ezer_root]['ezer']}";
  $abs_root=     $_SESSION[$ezer_root]['abs_root'];
  $rel_root=     $_SESSION[$ezer_root]['rel_root'];
  chdir($abs_root);

  // inicializace objektu Ezer
  $EZER= (object)array(
      'version'=>'ezer'.$_SESSION[$ezer_root]['ezer'],
      'options'=>(object)array(),
      'activity'=>(object)array()
  );

  // informace pro debugger o poloze ezer modulů
  $dbg_info= (object)array(
    'src_path'  => array('rr','man','ezer3.1') // poloha a preference zdrojových modulů
  );

  // databáze
  $deep_root= "../files/chlapi";
  require_once("$deep_root/rr.dbs.php");
  $path_backup= "$deep_root/sql";

  $app_php= array('rr/rr_fce.php');
  
  // je to standardní aplikace
  require_once("{$EZER->version}/ezer_ajax.php");
  
?>
