<?php

date_default_timezone_set('Europe/Prague');
//$ezer_local= $_SERVER['SERVER_NAME']=='chlapi.bean' ? 1 : 0;
//$index= $ezer_local ? "index.php" : "index.php";
$ezer_server= 
    $_SERVER["SERVER_NAME"]=='chlapi.bean' ? 0 : (          // 0:lokální 
    $_SERVER["SERVER_NAME"]=='www.chlapi.cz' ? 1 : (        // x:ostrý server
    $_SERVER["SERVER_NAME"]=='web.chlapi.online' ? 2 : -1));

# ------------------------------------------ init

$microtime_start= microtime();
if ( !isset($_SESSION) ) session_start();
$_SESSION['web']['index']= 'index.php';
$_SESSION['web']['server']= $ezer_server;
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
  $z= json_last_error();
  if ( $z!=JSON_ERROR_NONE ) {
    $z= (object)array('error'=>$z);
    $yjson= json_encode($z);
  }
  echo $yjson;
  exit;
}
def_user();
if ( $REDAKCE ) {
  // ----------------------------------------- zobraz CMS pomocí Ezer
  chdir('man');
  require_once("man.php"); 
}
else {
  require_once("man/2mini.php");
  # ------------------------------------------ zobraz prostý web

  $href= $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].
    $_SERVER['SCRIPT_NAME'].'?page=';
  $path= isset($_GET['page']) ? explode('!',$_GET['page']) : array('home');
//  $ezer_local= preg_match('/^\w+\.bean/',$_SERVER["SERVER_NAME"]);

  // pamatování GET
  global $GET_rok;
  $GET_rok= isset($_GET['rok']) ? $_GET['rok'] : '';
  read_menu();
  $path= isset($_GET['page']) ? explode('!',$_GET['page']) : array('home');
  $elem= eval_menu($path);
  $html= eval_elem($elem);
  show_page($html);
  exit;
}
?>
