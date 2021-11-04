<?php
// ---------------------------------------------------------------------------------------------- //
// funkce aplikace chlapi.online společné pro pro mode CMS i WEB                                  //
//                                                                                                //
// CMS/Ezer                                             (c) 2018 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //

/** =========================================================================================> BIBLE */
# ------------------------------------------------------------------------------------ bib transform
# transformuje text oživením biblických odkazů ozávorkovaných jako <span class='bible>...</span>
function bib_transform ($html) {
  $html= preg_replace_callback(
    "~<span class=\"bible\">(.*)<\/span>~uU",
    function ($m) {
      $ref= $m[1];
      $bib= bib_ref($ref);
      return "<span class=\"bible\" title='$bib'>$ref</span>";
    },
    $html);
  return $html;
}
# ------------------------------------------------------------------------------------------ bib ref
# vrátí text referovaných veršů
function bib_ref ($ref) { trace();
  $bib= '';
  $m= null;
  $ok= preg_match("/(\d?\pL+)(?:\s|&nbsp;)*(\d+),(\d+)(?:-(\d+)|)/u",$ref,$m);
  if (!$ok) goto end;
  debug($m,$ref);
  $k= $m[1];
  $kap= $m[2];
  $v1= $m[3];
  $v2= isset($m[4]) ? $m[4] : $v1;
  $bib= select("GROUP_CONCAT(text SEPARATOR ' ')",'bible',
      "kniha='$k' AND kapitola=$kap AND vers BETWEEN $v1 AND $v2");
  $bib.= " [$ref]";
end:
  display("$ref=$bib");
  return $bib;
}
/** ==========================================================================================> TEXT */
# ---------------------------------------------------------------------------------------- x cenzura
# úprava textu pro nepřihlášené tj. odklonění odkazů se stylem "neodkaz" na informaci o přihlášení
function x_cenzura($obsah0) {
  $obsah= preg_replace_callback (
      "~<span class=\"neodkaz\"><a (class=\"jump\"|)(.*)>(.*)</a></span>~U",
      function ($m) {
        global $KLIENT;
        if ( $KLIENT->level ) {
          if ( $m[1] )  // jump
              return "<span class='odkaz'><a class='jump' $m[2]>$m[3]</a></span>";
            else        // odkaz
              return "<span class='neodkaz'><a class='odkaz' $m[2]>$m[3]</a></span>";
        }
        else {
          $neodkaz= "onclick=\"jQuery('div.neodkaz').fadeIn();\" title='jen pro přihlášené'";
          if ( $m[1] )  // jump
              return "<span class='neodkaz'><a class='jump' $neodkaz>$m[3]</a></span>";
            else        // odkaz
              return "<span class='neodkaz'><a class='odkaz' $neodkaz>$m[3]</a></span>";
        }
      },
      $obsah0);
  return $obsah;
}
# -------------------------------------------------------------------------------------- x first_img
# vrátí první obrázek s doplněnými atributy, nebo ''
function x_first_img ($html,$size=1,$http=null) { //trace();
  global $FREE;
  $h= '';
  $m= null;
  $is1= preg_match('/<img[^>]+>/i',$html, $m);
  if ( !$is1 ) goto video;
//                                                 debug($m,htmlentities($m[0]));
  $src= null;
  $is2= preg_match('/src=(["\'][^"\']*["\'])/i',$m[0], $src);
  if ( !$is2 ) goto video;
//                                                 debug($src,1);
  // našli jsme a zjístíme, zda existuje
  $url= trim(str_replace("'",'"',$src[1])," '\"");
  // překlad na globální odkazy pro ty lokální (pro servant.php)
  $http= $http ?: ($FREE && preg_match("/^fileadmin/",$url) ? "https://www.setkani.org/" : '');
  $h= "<div style='max-height:{$size}em;overflow:hidden;float:left;margin-right:4px'>
         <img src='$http$url' style='width:{$size}em'>
       </div>";
video:
  // pokusíme se najít youtube default obrázek
  if ( !$h ) {
    $is= preg_match("~data-oembed-url=\"(?:http://youtu.be/|https?://www.youtube.com/watch\?v=)(.*)\"~iU",$html, $m);
//                                                 debug($m,$is);
    if ( $is ) {
      $h= "<div style='max-height:{$size}em;overflow:hidden;float:left'>
             <img src='https://img.youtube.com/vi/$m[1]/hqdefault.jpg' style='width:{$size}em'>
           </div>";
    }
  }
//   if ( $FREE ) $h= "is1=$is1, is2=$is2, http=$http ".$h;
  return $h;
}
# --------------------------------------------------------------------------------------- x shorting
# EPRIN
# zkrátí text na $n znaků s ohledem na html-entity jako je &nbsp;
function x_shorting ($text,$n=500,$http=null) { //trace();
  $img= '';
  $stext= xi_shorting ($text,$img,$n,$http);
  if ( $img ) {
    $stext= $img ? "<div>$img$stext ...</div>" : "$stext ...";
  }
  return $stext;
}
function xi_shorting ($text,&$img,$n=300,$http=null) { //trace();
  // náhrada <h.> za <i>
  $text= str_replace('<',' <', $text);
  $text= preg_replace("/\<(\/|)h1>/si",' <$1b> ', $text);
  $text= preg_replace("/\<(\/|)h2>/si",' <$1i> ', $text);
  // hrubé zkrácení textu
  $stext= mb_substr(strip_tags($text,'<b><i>'),0,$n);
  // odstranění poslední (případně přeříznuté) html-entity
  $in= mb_strlen($stext);
  $ia= mb_strrpos($stext,'&');
  if ( $ia!==false )
    $stext= mb_substr($stext,0,$in-$ia<10 ? $ia : $in);
  $im= mb_strrpos($stext,' ');
  if ( $im!==false )
    $stext= mb_substr($stext,0,$im);
  $stext= closetags($stext);
  $stext= preg_replace("/\s+/iu",' ', $stext);
  $img= x_first_img($text,8,$http);
  $stext.= " &hellip;";
  return $stext;
}
function closetags($html) {
  $result= null;
  preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
  $openedtags = $result[1];
  preg_match_all('#</([a-z]+)>#iU', $html, $result);
  $closedtags = $result[1];
  $len_opened = count($openedtags);
  if (count($closedtags) == $len_opened) {
    return $html;
  }
  $openedtags = array_reverse($openedtags);
  for ($i=0; $i < $len_opened; $i++) {
    if (!in_array($openedtags[$i], $closedtags)) {
      $html .= '</'.$openedtags[$i].'>';
    } else {
      unset($closedtags[array_search($openedtags[$i], $closedtags)]);
    }
  }
  return $html;
}
# ------------------------------------------------------------------------------------------ rr send
# $par = {den:ode dneška dnes=0,poslat: 0/1}
function rr_myslenka() {
  $dnes= date('j/n/Y',mktime(0,0,0,date('n'),date('j'),date('Y')));
  $html= "neni pro $dnes nastaveno!";
  //return $html;
  ezer_connect('ezertask');
  $qry= "SELECT * FROM rr WHERE datum=curdate()";
  $res= pdo_qry($qry);
//                                                $html.= "<br>$res=$qry";
  while ( $res && ($o= pdo_fetch_object($res)) ) {
//     $html= $o->text_cz;
    $subject= $o->subject;
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
    $subj= "Myšlenka na $subject - ";
    $body= "<table cellpadding='10'><tr>";
    $body.= "<td valign='top' width='50%'><b>$title_cz</b><br>$text_cz</td>";
    $body.= "<td valign='top' width='50%'><b>$title_en</b><br>$text_en<div align='right'>$from_en</div></td>";
    $body.= "</tr></table>";
    $html= "<h1>$subj</h1>$body";
  }
  return $html;
}
?>
