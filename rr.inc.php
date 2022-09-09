<?php # (c) 2010 Martin Smidek <martin@smidek.eu>

  // obnov skryté definice, cesty, databáze, aktuální server
  $abs_root= $_SESSION['rr']['abs_root'];
  chdir($abs_root);
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
