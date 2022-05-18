<?php # (c) 2018 Martin Smidek <martin@smidek.eu>

  global $ezer_root, $ezer_local, $ezer_server, $EZER, $abs_root, $rel_root;
  
  date_default_timezone_set('Europe/Prague');

  // nastavení zobrazení PHP-chyb klientem při &err=1
  if ( isset($_GET['err']) && $_GET['err'] ) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 'On');
  }

//  $ezer_server= 
//    $_SERVER["SERVER_NAME"]=='chlapi.bean'       ? 0 : (         // 0:lokální         = oranžové logo
//    $_SERVER["SERVER_NAME"]=='chlapi.online'     ? 1 : (         // Synology - online = modré logo
//    $_SERVER["SERVER_NAME"]=='www.chlapi.online' ? 1 : (
//    $_SERVER["SERVER_NAME"]=='chlapi.cz'         ? 2 : (         // Synology - cz     = šedé logo
//    $_SERVER["SERVER_NAME"]=='www.chlapi.cz'     ? 2 : (
//    $_SERVER["SERVER_NAME"]=='chlapi.doma'       ? 3 : (         // Synology - DOMA   = modré logo
//    $_SERVER["SERVER_NAME"]=='chlapi.ben'        ? 4 : -1)))))); // 4:lokální - ben   = oranžové logo
//
  $abs_root= $_SESSION['man']['abs_root'];
  $rel_root= $_SESSION['man']['rel_root'];

  chdir($abs_root);
  // skryté definice
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

//  // databáze
//  $deep_root= "../files/chlapi";
//  require_once("$deep_root/man.dbs.php");
  $path_backup= "$deep_root/sql";

  // cesta k utilitám MySQL/MariaDB
  $ezer_mysql_path= array(
      "C:/Apache/bin/mysql/mysql5.7.21/bin",  // martin
      "D:/wamp64/bin/mysql/mysql5.7.36/bin",  // petr
      "/volume1/@appstore/MariaDB/usr/bin",   // Synology YMCA
      "/volume1/@appstore/MariaDB/usr/bin"    // Synology DOMA
    )[$ezer_server];

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

