<?php

$microtime_start= microtime();

  // nastavení zobrazení PHP-chyb a výjimek v ezer2.php
  // err=0 potlačí zobrazení chyb při inicializaci (tj. mimo kód v ezer2.php), jinak jako err=1
  // err=1 ... (default) standardní handler: E_ALL & ~E_NOTICE 
  // err=2 ... v PHP7 vlastní handler: E_ALL & ~E_NOTICE     
  // err=3 ... v PHP7 vlastní handler: E_ALL
  if ( isset($_GET['err']) && ($err= $_GET['err']) ) {
    error_reporting($err==3 ? E_ALL : E_ALL & ~E_NOTICE);
    ini_set('display_errors', 'On');
  }
  else {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 'Off');
  }

//require_once("ezer3.1/mysql.inc.php");
//require_once("ezer3.1/server/ezer_pdo.php");
require_once("man/2mini.php");
require_once("man/man_web.php");
//die('?');
# ------------------------------------------ nastavení pracovního prostředí
global $EZER, $dbs, $ezer_db, $ezer_server;

$ezer_server= 
  $_SERVER["SERVER_NAME"]=='chlapi.bean'       ? 0 : (         // 0:lokální         = oranžové logo
  $_SERVER["SERVER_NAME"]=='chlapi.online'     ? 1 : (         // Synology - online = modré logo
  $_SERVER["SERVER_NAME"]=='www.chlapi.online' ? 1 : (
  $_SERVER["SERVER_NAME"]=='chlapi.cz'         ? 2 : (         // Synology - cz     = šedé logo
  $_SERVER["SERVER_NAME"]=='www.chlapi.cz'     ? 2 : (
  $_SERVER["SERVER_NAME"]=='chlapi.doma'       ? 3 : (         // Synology - DOMA   = modré logo
  $_SERVER["SERVER_NAME"]=='chlapi.ben'        ? 4 : -1)))))); // 4:lokální - ben   = oranžové logo

// přístup k serveru se servant_ch.php pro import článků brněnské skupiny z chlapi.cz
$chlapi_cz= array(
  'http://chlapi.bean:8080',
  'http://chlapi.cz',
  'http://chlapi.cz',
  'http://chlapi.doma',
  'http://chlapi.ben'
);

$chlapi_url= $chlapi_cz[$ezer_server];

// databáze
$deep_root= "../files/chlapi";
require_once("$deep_root/man.dbs.php");
$ezer_db= $dbs[$ezer_server];

//echo("ezer_server=$ezer_server\n{$dbs[0]['setkani'][1]}\n");

# ------------------------------------------ dotaz na články pro brněnské chlapy

$typ= $_GET['typ']; // nové=1 nebo staré=2 tzn. daného roku
$rok= isset($_GET['rok']) ? $_GET['rok'] : '';

ezer_connect('setkani');

// json= {clanky:[clanek,...]}
//   clanek= from:timestamp zahájení akce,kdy:formátované od-do,nadpis,abstract,img:url

$dbg= '';
$clanky= array();
$AND= '';
$AND= $typ==1 ? "AND datum_od>=NOW()" : "AND YEAR(datum_od)=$rok AND datum_od<NOW()"; 
$rc= pdo_query("
  SELECT id_xakce,datum_od,datum_do,nazev,xelems FROM xakce 
  WHERE skupina!=0 $AND ");
while ( $rc && (list($ida,$od,$do,$nadpis,$elems)=pdo_fetch_row($rc))) {
  $m= null;
  $ok= preg_match("/^aclanek=(\d+)/",$elems,$m);
  $dbg= "$ok/$m[0]"; 
  $idc= $m[1];
  if ( $ok ) {
    list($obsah)= select('web_text,ch_date','xclanek',"id_xclanek='$idc'");
    $href= $rok ? "$chlapi_url/skupiny!brno!$rok,$idc" : "$chlapi_url/skupiny!brno!$idc";
    $abstrakt= x_shorting($obsah,500,$chlapi_url);
    $flags= '';
    $flags.= select('COUNT(*)','xucast',"id_xclanek=$idc") ? 'T' : '';
    $flags.= select('COUNT(*)','xfotky',"id_xclanek=$idc") ? 'F' : '';
    $clanek= (object)array('ida'=>$ida,'idc'=>$idc,'flags'=>$flags,'href'=>$href,
        'od'=>$od,'do'=>$do,'nadpis'=>$nadpis,'abstrakt'=>$abstrakt);
    $clanky[]= $clanek;
  }
}

$y= (object)array('rok'=>$rok,'clanky'=>$clanky,'dbg'=>$dbg);

$answer= json_encode($y);
header('Content-type: application/json; charset=UTF-8');
echo $answer;
exit;

//# ------------------------------------------------------------ prozatímní funkce 
//function fce_error($msg) {
//  echo($msg);
//}
?>
