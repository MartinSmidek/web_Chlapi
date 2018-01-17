<?php

$local_test= $_SERVER['SERVER_NAME']=='chlapi.bean' ? 1 : 0;
$index= $local_test ? "index.php" : "index.php";

$secret= "WEBKEYNHCHEIYSERVANTAFVUOVKEYWEB";
$servant= $local_test
  ? "http://setkani.bean:8080/servant.php?secret=$secret"
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
if ( isset($_GET['err']) && $_GET['err'] ) error_reporting(E_ERROR); else error_reporting(0);
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

/* ========================================================================================> SERVER 
# funkce na serveru přes AJAX
function servant($qry,$context=null) {
  global $y, $servant;
  $json= file_get_contents("$servant&$qry",false,$context);
  if ( $json===false ) {
    $y->msg= "$qry vrátilo false";
  }
  elseif ( substr($json,0,1)=='{' ) {
    $y= json_decode($json);
  }
  else {
    $y->msg= "Sorry, došlo k chybě č.4, martin@smidek.eu ti poradí ...";
//    $y->msg= "'$servant&$qry' vrátil '$json'";
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
  
  case 'galerie': // ----------------------------------------------------------------------- galerie
    $y= (object)array('msg'=>'neznámý článek');
    servant("galerie=$x->pid"); // part.uid
    break;
  
  case 'kniha':     // ----------------------------------------------------------------------- kniha
    $y= (object)array('msg'=>'neznámý článek');
    servant("kniha=$x->cid&page=$x->page&kapitola=$x->kapitola"); // case.uid,part.uid
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
    servant("cmd=me_login&mail=$x->mail&pin=$x->pin&web=$x->web");
    if ( isset($y->state) && $y->state=='ok') {
      // přihlas uživatele jako FE
      $_SESSION['web']['fe_usergroups']= '4,6';
      $_SESSION['web']['fe_userlevel']= 0;
      $_SESSION['web']['fe_user']= $y->user;
      $_SESSION['web']['fe_username']= $y->name;
      $y->fe_user= $y->user;
      $y->be_user= 0;
    } 
    break;

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

  case 'upd_menu': // --------------------------------------------------------------------- upd menu
    // do y.msg vrací 
    $y= (object)array();
    $qry= menu_get();
    $query= http_build_query(array(
//        'post' => "TEST"
        'post' => $qry
    ));
    $length= strlen($query);
    $options= array('http'=>array(
        'method'  => "POST",
        'header'  => "Connection: close\r\nContent-Length: $length",
        'content' => $query
    ));
    $context= stream_context_create($options);
    servant("upd_menu=post",$context);
    $y->msg.= " <br> <hr> $qry";
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
# ----------------------------------------------------------------------------------------- menu get
# přenos $def_block do tx_gnmenu pomocí servant
function menu_get() {
  global $def_block;
  $qry= '';
  $mid= 0;
//  $qry= "DELETE FROM TABLE tx_gnmenu WHERE wid=2"; // 1=setkani.org, 2=chlapi.online
//  $qry.= "INSERT INTO tx_gnmenu (wid,mid,mid_top,ref,typ,nazev,next,val,elem) VALUES";
//  $del= "\n";
  foreach ($def_block as $ref=>$def) {
    $def= explode(':',$def);
    $def= array_map('trim',$def);
//    $def= array_map('mysql_escape_string',$def);
    list($t_bloku,$filler,$nazev,$next,$default1,$elems)= $def;
    $typ_bloku= $t_bloku=='m' ? 'mm' : 'sm'; 
    if ( $typ_bloku=='sm' ) continue;
    $mid++;
    $qry.= "$del(2,$mid,0,'$ref','$typ_bloku',\"$nazev\",'$next','$default1','$elems')";
    $del= ",\n";
    list($elem)= explode(';',$elems);
    list($typ,$ids)= explode('=',$elem.'=');
    if ( $typ=='menu' ) {
      $mid_top= $mid;
      foreach (explode(',',$ids) as $ref2) {
        $mid++;
        $def2= $def_block[$ref2];
        $def= explode(':',$def2);
        $def= array_map('trim',$def);
//        $def= array_map('mysql_escape_string',$def);
        list($t_bloku,$filler,$nazev,$next,$default2,$elems)= $def;
        $typ_bloku= $t_bloku=='m' ? 'mm' : 'sm'; 
        $qry.= "$del(2,$mid,$mid_top,'$ref2','$typ_bloku',\"$nazev\",'$next','$default2','$elems')";
      }
    }
  }
  return $qry;
}
*/    
?>
