<?php # (c) 2007-2012 Martin Smidek <martin@smidek.eu>
# ============================================================================================== CAC
# ------------------------------------------------------------------------------------ cac make_free
# uvolní datum ale pokud
#  je na webu => vrátí upozornění
#  překládáno => vrátí na rezervováno, smaže texty
#  rezervováno => smaže texty
function cac_make_free($idc) {
  $msg= '';
  $stav= select('stav','cac',"id_cac=$idc");
  if ($stav!=3) {
    if ($stav==2) $stav= 1;
    query("UPDATE cac SET stav=$stav,
      text_eng='',title_eng='',theme_eng='',url_theme='',url_text='',author='',
      text_cz='',title_cz='',theme_cz='' WHERE id_cac=$idc");
  }
  else {
    $msg= 'publikovaný text nelze zrušit';
  }
  return $msg;
}
# ------------------------------------------------------------------------------- cac get_new_medits
# doplní nové úvahy do CAC
function cac_through_DeepL($idc) {
  list($theme_eng,$title_eng,$text_eng)= select('theme_eng,title_eng,text_eng','cac',"id_cac=$idc");
  $text_eng= "<p>Testing <em>this</em> awesome <b>translator.</b></p>";
  $theme_cz= cac_deepl_en2cs($theme_eng);
  $title_cz= cac_deepl_en2cs($title_eng);
  $text_cz= cac_deepl_en2cs($text_eng);
  query("UPDATE cac SET 
    text_cz=\"$text_cz\",title_cz=\"$title_cz\",theme_cz=\"$theme_cz\" WHERE id_cac=$idc");
  return $title_cz;
}
# ---------------------------------------------------------------------------------- cac deepl_en2cs
# překlad anglického textu pomocí DeepL
function cac_deepl_en2cs($eng) {
  global $deepl_auth_key;
  $cz= '';
  $options= array(
    CURLOPT_URL => 'https://api-free.deepl.com/v2/translate',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => http_build_query(array(
      'auth_key' => $deepl_auth_key,
      'text' => $eng, 'source_lang' => 'EN', 'target_lang' => 'CS'
    )), 
    CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
  );
  $ch= curl_init();
  curl_setopt_array($ch, $options);
  $result= curl_exec($ch);
  if (curl_errno($ch)) { 
    $err= 'Error:' . curl_error($ch);
    fce_warning($err);
    goto end;
  }
  // extrakce textu
  $translatedWords= json_decode($result, true); // Decode the word
  $cz= $translatedWords['translations'][0]['text']; // Search the word
  $cz= pdo_real_escape_string($cz);
end:  
  curl_close($ch);
  return $cz;
}
# ------------------------------------------------------------------------------- cac get_new_medits
# doplní nové úhay do CAC
function cac_get_new_medits() {
  $msg= '';
  $ok= 0;
  $dnes= date('Y-m-d');
  // zjištění data za měsíc
  $date= new DateTime($dnes);
  $date->modify('+1 month');
  $za_mesic= $date->format('Y-m-d');
  // zajisti aby byly prázdné záznamy od posledního až do za měsíc
  $last= select('datum','cac',"1 ORDER BY datum DESC LIMIT 1");
  if (!$last) $last= '2021-12-31'; // start
  $date= new DateTime($last);
  $z= 100; // zarážka
  while ($z>0 && $last<$za_mesic) {
    $z--;
    $date->modify('+1 day');
    $last= $date->format('Y-m-d');
    query("INSERT INTO cac (datum) VALUE ('$last')");
  }
  // potom doplň z CAC chybějící texty
  $n= 3;
  $last= select('datum','cac',"IFNULL(text_eng,'')!='' ORDER BY datum DESC LIMIT 1");
  if (!$last) $last= '2021-12-31'; // start
  while ($n>0 && $last<$dnes) {
    $n--;
    $date= new DateTime($last);
    $date->modify('+1 day');
    $last= $date->format('Y-m-d');
    // získání a zápis úvahy
    $x= cac_save_medit_from($last);
    $title_cz= cac_through_DeepL($x->idc);
    $msg.= "$last: $x->title ... $title_cz<br>";
    $ok= 1;
  }
  return $ok ? $msg : ' novější úvahy CAC zatím nejdou importovat ';
}
# ------------------------------------------------------------------------------ cac save_medit_from
# uloží do daného dne danou meditaci
function cac_save_medit_from($last) { trace();
  // načtení úvahy
  $x= cac_get_medit_from($last);
  // zapsání úvahy do tabulky
  $x->idc= select('id_cac','cac',"datum='$last'");
  if (!$x->idc) {
    query("INSERT INTO cac (datum) VALUE ('$last')");
    $x->idc= pdo_insert_id();
  }
  $tema= pdo_real_escape_string($x->tema);
  $title= pdo_real_escape_string($x->title);
  $text= pdo_real_escape_string($x->text);
  query("UPDATE cac SET 
      url_theme='$x->url_tema',url_text='$x->url_title',theme_eng='$tema',
      author='$x->autor',reference='$x->reference',title_eng='$title',text_eng='$text' 
    WHERE id_cac=$x->idc");
  return $x;
}
# ------------------------------------------------------------------------------- cac get_medit_from
# vrátí meditaci ze dne {r,m,d} jako objekt {ok,datum,title,url_title,tema,url_tema,autor,text}
function cac_get_medit_from($ymd) { 
  $ret= (object)array('ok'=>0);
  $cac_month= "https://cac.org/category/daily-meditations";
  list($year,$month,$day)= explode('-',$ymd);
  $cac_month= "$cac_month/$year/$month/";
  $html= file_get_contents($cac_month);
  // rozklad
  $m= null;
  $ret->ok= preg_match_all(
      '~<h2 class="daily-meditations-loop__title">\s*<a href="([^"]+)">([^<]+)</a>\s*</h2>\s*'
      . '<div class="daily-meditations-loop__themes">\s*<small>Theme:</small>\s*'
      . '<a href="([^"]+)" class="daily-meditations-loop__theme">([^<]+)</a>\s*</div>'
      . '\s*<div class="daily-meditations-loop__tags">\s*<small>Tags:<\/small>(?:\s*<a href="[^"]+" class="daily-meditations-loop__tag">[^<]*<\/a>)+\s*<\/div>\s*<div class="daily-meditations-loop__author"><small>Author:<\/small>\s*<strong>([^<]+)<\/strong>\s*<\/div>~',
      $html,$m);
  display("načtení měsíce: $ret->ok úvah ($cac_month)");
  for ($i= 0; $i<count($m[0]); $i++) {
    $ret->date= '';
    // text daného dne
    $ret->title= $m[2][$i];
    $ret->url_title= $m[1][$i];
    $ret->tema= $m[4][$i];
    $ret->url_tema= $m[3][$i];
    $ret->autor= $m[5][$i];
    $cac_day= $m[1][$i];
    $d= null;
    preg_match('~.*(\d\d\d\d-\d\d-(\d\d))~',$cac_day,$d);
    if ($d[2]!=$day) continue;
    $ret->date= sql_date1($d[1]);
    $html= file_get_contents($cac_day);
    $p= null;
    $ret->cut= preg_match(
        '~<div class="(?:wp-block-gecko-blocks-section|entry-content article)">(.*)~ms',
        $html,$p);
    display("načtení dne: $ret->ok ($cac_day)");
    $text= preg_split('~<p><strong>(Story|References|Explore|Breath)~',$p[1],-1,PREG_SPLIT_DELIM_CAPTURE);
    $ret->text= $text[0];
    $ret->reference= '';
    for ($i= 1; $i<count($text); $i+= 2) {
      if (substr($text[$i],0,3)=='Ref') {
        $ref= null;
        $ret->reference= "<strong>Odkazy{$text[$i+1]}";
      }
    }
    break;
  }
  // návrat
  return $ret;
}
# --------------------------------------------------------------------------------- cac change_state
# změní stav překladu a případně odebere nebo přidá překladatele
# TODO otestovat, zda existuje překlad
function cac_change_state($idc,$s) {
  global $USER;
  $msg= '';
  $preklada= select('preklada','cac',"id_cac=$idc");
  if (!$preklada) {
    $me= $USER->id_user;
    if (!$me) fce_error("uživatel není přihlášen, nelze provést změnu stavu");
    query("UPDATE cac SET preklada=$me,stav=$s WHERE id_cac=$idc");
  }
  else {
    $and= $s==0 ? ", preklada=0" : '';
    query("UPDATE cac SET stav=$s $and WHERE id_cac=$idc");
  }
  return $msg;
}
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
    $zruseno+= query("UPDATE rr SET datum='0000-00-00',state='' WHERE datum='$ndatum'");
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
//                                                $html.= "<br>$res=$qry";
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
        $email= isset($par->test)
            ? $par->test
            : ($_GET['email'] ? $_GET['email'] : 'chlapi-myslenky@googlegroups.com');
        $html.= "<hr/>zaslání na <i>$email</i> skončilo se stavem ";
        $ok= rr_send_mail($subj,$body,'martin.smidek@setkani.org',$email,'Richard Rohr');
//        $ok= send_mail($subj,$body,'smidek@proglas.cz',$email,'Richard Rohr');
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
# ---------------------------------------------------------------------------------------- send mail
# pošle systémový mail, pokud není určen adresát či odesílatel jde o mail správci aplikace
# $to může být seznam adres oddělený čárkou
function rr_send_mail($subject,$html,$from='',$to='',$fromname='') { trace();
  global $ezer_path_serv, $ezer_root, $EZER, $api_gmail_user, $api_gmail_pass;
//  $from= $from ? $from : ($EZER->smtp->from ? $EZER->smtp->from : $EZER->options->mail);
//  $fromname= $fromname ? $fromname : $ezer_root;
  $to= $to ? $to : $EZER->options->mail;
  // poslání mailu
  $phpmailer_path= "$ezer_path_serv/licensed/phpmailer";
  require_once("$phpmailer_path/class.smtp.php");
  require_once("$phpmailer_path/class.phpmailer.php");
  // napojení na mailer
  $mail= new PHPMailer;
  $mail->SetLanguage('cs',"$phpmailer_path/language/");
  
  $mail->IsSMTP();
  $mail->Mailer= 'smtp';
  $mail->Host= "smtp.gmail.com";
  $mail->Port= 465;
  $mail->SMTPAuth= 1;
  $mail->SMTPSecure= "ssl";
  $mail->Username= $api_gmail_user;
  $mail->Password= $api_gmail_pass;
//  $mail->AddAddress("martin@smidek.eu");
  
  
//  $mail->IsSMTP();
//  $mail->Host= isset($EZER->smtp->host) ? $EZER->smtp->host : "192.168.1.1";
//  $mail->Port= isset($EZER->smtp->port) ? $EZER->smtp->port : 25;
  $mail->CharSet = "utf-8";
  $mail->From= $from;
  $mail->FromName= $fromname;
  foreach (explode(',',$to) as $to1) {
    $mail->AddAddress($to1);
  }
  $mail->Subject= $subject;
  $mail->Body= $html;
  $mail->IsHTML(true);
//   $mail->Mailer= "smtp";
  // pošli
  $ok= $mail->Send();
//                                                display("send_mail=$ok,".$mail->ErrorInfo);
  if ( !$ok )
    fce_warning("Selhalo odeslání mailu: $mail->ErrorInfo");
  else {
//                                                $mail->Subject= $mail->Body= $mail->language= "---";
//                                                debug($mail,"send_mail(..,..,$from,$to)=$ok");
  }
  return $ok;
}
