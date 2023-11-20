<?php # (c) 2018 Martin Smidek <martin@smidek.eu>

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

  date_default_timezone_set('Europe/Prague');

  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 'On');
  }

  // obnov skryté definice, cesty, databáze, aktuální server
  $abs_root= $_SESSION['man']['abs_root'];
  chdir($abs_root);
  $deep_root= "../files/chlapi";
  require_once("$deep_root/man.dbs.php");

  // inicializace objektu Ezer
  $EZER= (object)array(
      'version'=>"ezer{$_SESSION[$ezer_root]['ezer']}",
      'options'=>(object)array(
          'mail' => "martin@smidek.eu",
          'phone' => "603&nbsp;150&nbsp;565",
          'author' => "Martin"
      ),
      'activity'=>(object)array('skip'=>1));

  // úschovy databáze
  $path_backup= "$deep_root/sql";

  // ostatní parametry
  $tracking= '_track';
  $tracked= ',_user,';

  // PHP moduly aplikace 
  $app_php= array(
    "$ezer_root/2template_ch.php",
    "$ezer_root/man_cms.php",
    "$ezer_root/man_web.php",
  );

  // je to aplikace se startem v rootu
  require_once("{$EZER->version}/ezer_ajax.php");

