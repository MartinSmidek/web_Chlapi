<?php # (c) 2007-2012 Martin Smidek <martin@smidek.eu>
# =============================================================================================== RR
# ---------------------------------------------------------------------------------------- rr nastav
# $par = {den:ode dneška,poslat: 0/1}
function rr_nastav($den,$datum,$pocet) {  trace();
  $ret= (object)array('msg'=>'','last'=>'','next'=>'');
  $nastaveno= $zruseno= 0;
  $dat= sql_date1($datum,1);
  $ndat0= sql2stamp($dat);
  for ($d= 0; $d<$pocet; $d++) {
    $day_n= $den+$d;
    $ndatum= date('Y-m-d',$ndat0+$d*60*60*24);
    $zruseno+= query("UPDATE rr SET datum='0000-00-00',state='unasigned' WHERE datum='$ndatum'");
    $nastaveno+= query("UPDATE rr SET datum='$ndatum',state='prepared' WHERE day_n=$day_n");
  }
  $ret->last= date('Y-m-d',$ndat0+($pocet-1)*60*60*24);
  $ret->next= date('Y-m-d',$ndat0+$pocet*60*60*24);
  $ret->msg= "nastaveno $nastaveno dnů od $dat po $ndatum, zrušeno $zruseno předchozích nastavení";
  return $ret;
}
# ------------------------------------------------------------------------------------------ rr zrus
# zruší nastavená data
function rr_zrus($den,$pocet) {  trace();
  $zruseno= 0;
  for ($d= 0; $d<$pocet; $d++) {
    $day_n= $den+$d;
    $ndatum= date('Y-m-d',$ndat0+$d*60*60*24);
    $zruseno+= query("UPDATE rr SET datum='0000-00-00',state='unasigned' WHERE day_n=$day_n");
  }
  $msg= "zrušeno $zruseno předchozích nastavení";
  return $msg;
}
# ------------------------------------------------------------------------------------------ rr send
# $par = {den:ode dneška,poslat: 0/1}
function rr_send($par) {
  global $EZER;
  $offset= $par->den<0 ? "-INTERVAL ".abs($par->den)." DAY" : ($par->den>0 ? "+INTERVAL {$par->den} DAY" : '');
  $plus= $par->den ? $par->den : 0;
  $dnes= date('j/n/Y',mktime(0,0,0,date('n'),date('j')+$plus,date('Y')));
  $html= "neni pro $dnes nastaveno! ($offset)";
  //return $html;
  ezer_connect("ezertask");
  $qry= "SELECT * FROM rr WHERE datum=curdate()$offset ";
  $res= pdo_qry($qry);
                                                $html.= "<br>$res=$qry";
  while ( $res && ($o= pdo_fetch_object($res)) ) {
//     $html= $o->text_cz;
    $day_n= $o->day_n;
    $day= $o->day;
    $subject= $o->subject;
    $datum= $o->datum;
    $state= $o->state;
    $title_cz= $o->title_cz;
    $text_cz= $o->text_cz;
    $text_cz= strtr($text_cz,array('š'=>'&scaron;','ž'=>'&#382;'));
    $title_en= $o->title_en;
    $text_en= $o->text_en;
    $from_en= $o->from_en;
    // formátování
    $subject= strtr($subject,array(
        'Neděle'=>'neděli', 'Pondělí'=>'pondělí', 'Úterý'=>'úterý', 'Středa'=>'středu'
      , 'Čtvrtek'=>'čtvrtek', 'Pátek'=>'pátek', 'Sobota'=>'sobotu'
      , 'První'=>'první', 'Druhá'=>'druhou', 'Čtvrtá'=>'čtvrtou', 'Pátá'=>'pátou'
      , 'Šestá'=>'šestou', 'Sedmá' => 'sedmou'
      ));
    $subj= "Richard Rohr na $subject - $title_cz";
    $body= "<table cellpadding='10'><tr>";
    $body.= "<td valign='top' width='50%'><b>$title_cz</b><br>$text_cz</td>";
    $body.= "<td valign='top' width='50%'><b>$title_en</b><br>$text_en<div align='right'>$from_en</div></td>";
    $body.= "</tr></table>";
    $html= "<h1>$subj</h1>$body";
    if ( $par->poslat  ) {
      if ( $state=='prepared' || $par->opakovat || $_GET['again']=='yes' ) {
        // odeslání a ochrana proti zdvojení
        $email= $_GET['email'] ? $_GET['email'] : 'chlapi-myslenky@googlegroups.com';
        $html.= "<hr/>zaslání na <i>$email</i> skončilo se stavem ";
        $ok= send_mail($subj,$body,'smidek@proglas.cz',$email,'Richard Rohr');
//        $ok= send_mail($subj,$body,'smidek@proglas.cz','martin@smidek.eu','Richard Rohr');
        $html.= $ok;
        //$html.= $mail->sendHtmlMail('smidek@proglas.cz',$email,'','',$subj,$body,'Richard Rohr');
        if ( $ok && !isset($_GET['email']) ) {
          query("UPDATE rr SET state='sent' WHERE day_n=$day_n ");
        }
      }
      else $html= "Pozor! Už bylo jednou zasláno, lze vynutit klíčem again=yes<hr/>$html";
    }
  }
  return $html;
}
