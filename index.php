<?php

date_default_timezone_set('Europe/Prague');

# ------------------------------------------ init

$microtime_start= microtime();
$ezer_version= "3.2";

// skryté definice
global $ezer_server, $paths_log, $abs_root, $rel_root, $log_path, $db, $dbs, $lang;
$deep_root= "../files/chlapi";
require_once("$deep_root/man.dbs.php");

//if ( !isset($_SESSION) ) 
session_start();
$_SESSION['web']['index']= $index= 'index.php';
$_SESSION['web']['server']= $ezer_server;
if ( isset($_GET['err']) && $_GET['err'] ) error_reporting(E_ERROR); else error_reporting(0);
ini_set('display_errors', 'On');
require_once("man/man_web.php");
require_once("man/2template_ch.php");

// pokud se stránka jmenuje en-* přepni na angličtinu a nové menu
$path= isset($_GET['page']) ? explode('!',$_GET['page']) : array('home');
// screen
if (isset($_GET['screen'])) {
  $_SESSION['web']['screen']= $_GET['screen'];
}
// menu
if (isset($_GET['menu'])) {
  $_SESSION['web']['menu']= $_GET['menu'];
}
elseif (preg_match('/^en\\-.*$/',$path[0])) {
  $_SESSION['web']['menu']= 'new';
  $_GET['lang']= 'en';
}
elseif (is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "mobile"))) {
  $_SESSION['web']['menu']= 'new';
}
elseif (!isset($_SESSION['web']['menu'])) {
  $_SESSION['web']['menu']= 'new';
}
// jazyk
if (isset($_GET['lang'])) {
  $lang= $_GET['lang'];
  set_lang($lang);
}
// menu
if (isset($_GET['ukazat_plan'])) {
  $_SESSION['web']['ukazat_plan']= $_GET['ukazat_plan'];
}
// pro testovací GETs
$_SESSION['web']['GET']= isset($_SESSION['web']['GET']) 
    ? array_merge($_SESSION['web']['GET'],$_GET) : $_GET;
# ------------------------------------------ ajax
if ( count($_POST) ) {
  global $s;
  try {
  require_once("man/2mini.php");
  $x= array2object($_POST);
  $s= $x;
  $_SESSION['web']['*server_ask']= $x;
  ask_server($x);
  $_SESSION['web']['*server_answer']= $s;
  header('Content-type: application/json; charset=UTF-8');
  $yjson= json_encode($s);
  $z= json_last_error();
  if ( $z!=JSON_ERROR_NONE ) {
    $z= (object)array('error'=>$z);
    $yjson= json_encode($z);
  }
  echo $yjson;
  exit;
  }
  catch (Exception $e) {
    $_SESSION['web']['*server_catch']= $e->getMessage();
  }
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
    // pamatování GET
  global $GET_rok, $counts;
  $GET_rok= isset($_GET['rok']) ? $_GET['rok'] : '';
  $counts= array(); // typ -> počet
//  if ($_GET['menu']=='new' || $_SESSION['web']['menu']=='new') {
    $elem= '';
    $part= (object)array(); // části výsledné stránky
    $html= new_menu($path,$elem);
    $html.= eval_elem($elem);
    show_page($html,'new');
//  }
//  else {
//    read_menu($path);
//    $elem= eval_menu($path);
//    $html= eval_elem($elem);
//    show_page($html,'old');
//  }
  exit;
}
?>
