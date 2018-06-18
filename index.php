<?php

$cms= 'man';
$ezer_local= $_SERVER['SERVER_NAME']=='chlapi.bean' ? 1 : 0;
$index= $ezer_local ? "index.php" : "index.php";

//$secret= "WEBKEYNHCHEIYSERVANTAFVUOVKEYWEB";
//$servant= $local_test
//  ? "http://setkani.bean:8080/servant.php?secret=$secret"
//  : "https://www.setkani.org/servant.php?secret=$secret";
//
# ------------------------------------------ IP test

// $ips= array(
//   '89.176.167.5','94.112.129.207',                      // zdenek
//   '83.208.101.130','80.95.103.170',                     // martin
//   '127.0.0.1','192.168.1.146'                           // local
// );
// $ip= ip_get();
// if ( !in_array($ip,$ips) ) die("Stránka ve výstavbě");

# ------------------------------------------ init

$microtime_start= microtime();
if ( !isset($_SESSION) ) session_start();
$_SESSION['web']['index']= $index;
if ( isset($_GET['err']) && $_GET['err'] ) error_reporting(E_ERROR); else error_reporting(0);
ini_set('display_errors', 'On');
require_once("man/2template_ch.php");

# ------------------------------------------ ajax

if ( count($_POST) ) {
  require_once("man/2mini.php");
  $x= array2object($_POST);
  $y= $x;
  ask_server($x);
  header('Content-type: application/json; charset=UTF-8');
  $yjson= json_encode($y);
  echo $yjson;
  exit;
}

$fe_level= isset($_SESSION['web']['fe_level']) ? $_SESSION['web']['fe_level'] : 0;
if ( $fe_level && ($fe_level & 1) ) {
  chdir('man');
  $fe_user= $be_user= $_SESSION['web']['fe_user'];
  require_once("man/man.php"); 
}
else {
  require_once("man/2mini.php");
  # ------------------------------------------ web
  global $ezer_path_root, $GET_rok;

  $href= $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].
    $_SERVER['SCRIPT_NAME'].'?page=';
  $path= isset($_GET['page']) ? explode('!',$_GET['page']) : array('home');
  $fe_user= isset($_SESSION['web']['fe_user']) ? $_SESSION['web']['fe_user'] : 0;
  $be_user= 0;
  $fe_host= 0;
  $fe_user_display= isset($_GET['login']) ? 'block' : 'none';
  $ezer_local= preg_match('/^\w+\.bean/',$_SERVER["SERVER_NAME"]);

  // pamatování GET
  $GET_rok= isset($_GET['rok']) ? $_GET['rok'] : '';

  // absolutní cesta
  $ezer_path_root= $_SESSION['web']['path']= $_SERVER['DOCUMENT_ROOT'];
  global $CMS;
  $CMS= 0;
  //require_once("man/2template_ch.php");
  //require_once("man/2mini.php");
  read_menu();
  $path= isset($_GET['page']) ? explode('!',$_GET['page']) : array('home');
  $elem= eval_menu($path);
  $html= eval_elem($elem);
  show_page($html);
  exit;
}
?>
