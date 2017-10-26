<?php

$local_test= $_SERVER['SERVER_NAME']=='web.bean' ? 1 : 0;
$index= $local_test ? "chlapi-online.php" : "index.php";

$secret= "WEBKEYNHCHEIYSERVANTAFVUOVKEYWEB";
$servant= $local_test
  ? "http://web.bean:8080/servant.php?secret=$secret"
  : "https://www.setkani.org/servant.php?secret=$secret";

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
if ( isset($_GET['err']) && $_GET['err'] ) error_reporting(E_ERROR);
ini_set('display_errors', 'On');
require_once("cms/template_ch.php");
// require_once("cms/web_fce.php");
require_once("cms/mini.php");
$ezer_local= preg_match('/^\w+\.(ezer|bean)|^localhost|^192\.168\./',$_SERVER["SERVER_NAME"]);

# ------------------------------------------ ajax

if ( count($_POST) ) {
  $x= array2object($_POST);
  $y= $x;
  server($x);
  header('Content-type: application/json; charset=UTF-8');
  $yjson= json_encode($y);
  echo $yjson;
  exit;
}

# ------------------------------------------ web

global $ezer_path_root, $GET_rok;

$href= $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].
  $_SERVER['SCRIPT_NAME'].'?page=';
$path= isset($_GET['page']) ? explode('!',$_GET['page']) : array('home');
$fe_user= isset($_SESSION['web']['fe_user']) ? $_SESSION['web']['fe_user'] : 0;
$be_user= 0;
$fe_host= 0;
$fe_user_display= isset($_GET['login']) ? 'block' : 'none';

// pamatování GET
$GET_rok= isset($_GET['rok']) ? $_GET['rok'] : '';

// absolutní cesta
$ezer_path_root= $_SESSION['web']['path']= $_SERVER['DOCUMENT_ROOT'];

template($href,$path,$fe_host,$fe_user,$be_user);
die();

/** ========================================================================================> SERVER */
# funkce na serveru přes AJAX
function servant($qry) {
  global $y, $servant;
  $json= file_get_contents("$servant&$qry");
  if ( $json===false ) {
    $y->msg= "$qry vrátilo false";
  }
  elseif ( substr($json,0,1)=='{' ) {
    $y= json_decode($json);
  }
  else {
    $y->msg= "'$servant&$qry' vrátil '$json'";
  }
}
function server($x) {
  global $y;
//   $x->cmd= 'test';
  switch ( $x->cmd ) {
  case 'test':     // ------------------------------------------------------------------------- test
    $y= (object)array('msg'=>'TEST neprošel');
    servant('test=1');
    break;
  case 'clanek':   // ----------------------------------------------------------------------- clanek
    $y= (object)array('msg'=>'neznámý článek');
    servant("clanek=$x->pid"); // part.uid
    break;
  case 'roky':     // ------------------------------------------------------------------------- roky
    $y= (object)array('msg'=>'roky?');
    servant('roky=1');
    break;
  case 'foto':     // ------------------------------------------------------------------------- foto
    $y= (object)array('msg'=>'TEST neprošel');
    $AND= "&rok={$x->rok}";
    $AND.= isset($_GET['id']) ? "&id={$_GET['id']}" : "";
    servant("foto=1$AND&groups=0,4,6");
    break;
  case 'free':     // ------------------------------------------------------------------------- free
    $y= (object)array('msg'=>'TEST neprošel');
    servant('free=1');
    break;
  case 'sendmail': // -------------------------------------------------------------------- send mail
    // ask({cmd:'sendmail',to:to,reply:reply,subj:subj,body:body},skup_sendmail_);
    $y->ok= emailIsValid($x->to,$err) ? 1 : 0;
    if ( $y->ok ) {
      $ret= mail_send($x->reply,$x->to,$x->subj,$x->body);
      $_SESSION['ans']['phpmailer']= $ret;
      $y->txt= $ret->err
        ? "Mail se bohužel nepovedlo odeslat ($ret->err)"
        : "mail byl odeslán organizátorům skupiny";
    }
    else {
      $y->txt= "'$x->to' nevypadá jako mailová adresa ($err)";
    }
    break;

  case 'mapa':     // ------------------------------------------------------------------------- mapa
    servant("mapa=$x->mapa");
    break;

  case 'me_login': // ------------------------------------------------------------------------ login
    servant("mail=$x->mail&pin=$x->pin&web=$x->web");
    if ( $y->state=='ok') {
      // přihlas uživatele jako FE
      $_SESSION['web']['fe_usergroups']= '4,6';
      $_SESSION['web']['fe_userlevel']= 0;
      $_SESSION['web']['fe_user']= $y->user;
      $_SESSION['web']['fe_username']= $y->name;
      $y->fe_user= $y->user;
      $y->be_user= 0;
    } 
    break;
  /*
    // v x.local_ip je lokální IP adresa, ip_get() dá externí
    // chybové hodnoty
    $ido= $iniciace= $user= $timeout= '';
    // ----------------------- nulování stavu po 3 minutách nebo po odhlášení
    if ( isset($_SESSION['ans']['stamp']) ) {
      $min= 2;
      $now= time();
      $sec= $now - $_SESSION['ans']['stamp'];
      if ( $sec > $min*60 && $_SESSION['ans']['phase']>1 ) {
        $timeout= 1;
      }
    }
    else {
      unset($_SESSION['ans']);
    }
    $faze= isset($_SESSION['ans']['phase']) ? $_SESSION['ans']['phase'] : 0;
    switch ( $faze ) {

    case 0: // ----------------------- ověření syntaxe adresy a existence domény
      $ok= emailIsValid($x->mail,$err);                 // 0 = byla zadána adresa a možná PIN
      if ( !$ok ) { $y->txt= "'$x->mail' nevypadá jako mailová adresa ($err)"; }
      $ans= 'nevolalo se';
      $iniciace= 0;
      if ( $ok ) {                                      
        $_SESSION['ans']['phase']= 1;                   // 1 = ověření adresy z Answeru
        $json= file_get_contents("$servant&mail=$x->mail&pin=$x->pin");
        $ans= json_decode($json);
        $ido= $ans->user;
        $user= $ans->name;
        $iniciace= $ans->mrop;
        $_SESSION['ans']['phase']= $iniciace ? 2 : 3;   // 2 = adresa je ověřená nebo 3 = odmítnutá
        $y->my_ip= isset($ans->my_ip) ? $ans->my_ip : '?';
        $y->trace= $ans->trace;
      }
      $y->ans= $ans;
      if ( !$ok ) { goto end; }
      if ( !$iniciace ) {
        $y->txt= "adresu '$x->mail' jsme nenašli, požádej Martina o pomoc.";
        goto end;
      }
      // vytvoření PIN a zaslání mailem
      $pin= rand(1000,9999);
      // odeslání mailu
      $_SESSION['ans']= array();
      $_SESSION['ans']['me_user']= $ido;
      $_SESSION['ans']['fe_usergroups']= $iniciace ? '4,6' : '4';
      $_SESSION['ans']['fe_username']= $user;
      $_SESSION['ans']['mail']= $x->mail;
      $_SESSION['ans']['pin']= $pin;
      $_SESSION['ans']['stamp']= time();
      $_SESSION['ans']['phase']= 1;
      $y->txt= "na uvedenou adresu byl odeslán mail obsahující PIN, zapiš jej do pole vedle adresy";
      $ret= mail_send('martin@smidek.eu',$x->mail,"Rozšíření přístupu na chlapi.online",
        "V přihlašovacím dialogu webové stránky napiš vedle svojí mailové adresy $pin.
        <br>Přeji Ti příjemné prohlížení, Tvůj web");
      $_SESSION['ans']['phpmailer']= $ret;
      if ( $ret->err ) { $y->msg= "Mail se bohužel nepovedlo odeslat ($ret->err)"; goto end; }
      $_SESSION['ans']['phase']= 4;                     // 4 = byl poslán mail s PIN
      break;

    case '6': // ----------------------- byl zapsán prázdný PIN
    case '8': // ----------------------- byl podruhé zapsán PIN
      if ( $timeout ) {
        $y->txt= "během přihlašování došlo k chybě $faze, dej Zpět a zkus to znovu";
        unset($_SESSION['ans']);
        goto end;
      }
    case '4': // ----------------------- byl poprvé zapsán PIN
      $pin= $x->pin;
      $mail= $x->mail;
      if ( $mail==$_SESSION['ans']['mail'] ) {
        if ( !$pin ) {
          $_SESSION['ans']['phase']= 6;                 // 6 = PIN chybí
          $y->txt= "do druhého pole je třeba zapsat PIN, který došel mailem";
          goto end;
        }
        elseif ( $pin==$_SESSION['ans']['pin'] ) {
          // přihlas ANS uživatele jako FE
          $_SESSION['ans']['phase']= 9;                 // 9 = PIN ok, přihlášení
          $uid= 999999;
          $_SESSION['web']['fe_usergroups']= $_SESSION['ans']['fe_usergroups'];
          $_SESSION['web']['fe_userlevel']= 0;
          $_SESSION['web']['fe_user']= $uid;
          $_SESSION['web']['fe_username']= $user;
          $y->fe_user= $uid;
          $y->be_user= 0;
        }
        elseif ( $faze==8 ) {
          $_SESSION['ans']['phase']= 7;                 // 7 = PIN hádán
          $y->txt= "je zapotřebí požádat o nový PIN";
          goto end;
        }
        else {
          $_SESSION['ans']['phase']= 8;                 // 8 = PIN ko
          $y->txt= "to je jiný PIN, než který byl poslán v posledním mailu - nepřeklepl ses?";
          goto end;
        }
      }
      break;
    default:
      if ( $timeout ) {
        $y->txt= "během přihlašování došlo k chybě $faze, zkus to znovu";
      }
      else {
        $y->txt= "během přihlašování došlo k chybě $faze, počkej 2 minuty";
      }
      unset($_SESSION['ans']);
      break;
    }
  end:
//     $y->trace= $trace;
//     $CMS= $oldCMS;
    break;
*/
  case 'be_logout': // ---------------------------------------------------------------------- logout
    $_SESSION['web']= array();
    $_SESSION['web']['fe_user']= 0;
    $_SESSION['web']['be_user']= 0;
    unset($_SESSION['ans']);
    unset($_SESSION['cms']);
    session_write_close();
    $y->fe_user= 0;
    $y->be_user= 0;
    $y->page= $x->page;
    break;
  }
  return 1;
}
# ------------------------------------------------------------------------------------------- ip get
# zjištění klientské IP
function ip_get() {
  return isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
}
?>
