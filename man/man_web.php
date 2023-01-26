<?php
// ---------------------------------------------------------------------------------------------- //
// funkce aplikace chlapi.online společné pro pro mode CMS i WEB                                  //
//                                                                                                //
// CMS/Ezer                                             (c) 2018 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //

/** ==========================================================================================> LANG */
# ----------------------------------------------------------------------------------------- set lang
# zapíše aktuální jazyk webu en/cs do SESSION  a COOKIE
function set_lang ($lang) {
  setcookie('lang',$lang);
  $_SESSION['web']['lang']= $lang;
  return $lang=='en' ? 1 : 2;
}
# ----------------------------------------------------------------------------------------- get lang
# zjistí aktuální jazyk webu en/cs v pořadí GET > SESSION > COOKIE > 'cs'
function get_lang () {
  $lang= '';
  if (!$lang && isset($_GET['lang'])) $lang= $_GET['lang'];
  if (!$lang && isset($_SESSION['web']['lang'])) $lang= $_SESSION['web']['lang'];
  if (!$lang && isset($_COOKIE['lang'])) $lang= $_COOKIE['lang'];
  if (!$lang) $lang= 'cs';
  return $lang;
}
# ----------------------------------------------------------------------------------------- get menu
# zjistí aktuální menu
function get_menu () {
  return isset($_SESSION['web']['menu']) ? $_SESSION['web']['menu'] : 'old';
}
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
  $ok= preg_match("/(\d*\s*\pL+)(?:\s|&nbsp;)*(\d+),(\d+)(?:-(\d+)|)/u",$ref,$m);
  if (!$ok) goto end;
  debug($m,$ref);
  $k= str_replace(' ','',$m[1]);
  $kap= $m[2];
  $v1= $m[3];
  $v2= isset($m[4]) ? $m[4] : $v1;
  $bib= select("GROUP_CONCAT(text SEPARATOR ' ')",'bible JOIN bible_kniha USING (kniha)',
      "alias='$k' AND kapitola=$kap AND vers BETWEEN $v1 AND $v2");
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
# -------------------------------------------------------------------------------------- rr myslenka
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
    $subj= "Myšlenka Richarda Rohra na $subject - ";
    $body= "<table cellpadding='10'><tr>";
    $body.= "<td valign='top' width='50%'><b>$title_cz</b><br>$text_cz</td>";
    $body.= "<td valign='top' width='50%'><b>$title_en</b><br>$text_en<div align='right'>$from_en</div></td>";
    $body.= "</tr></table>";
    $html= "<h1>$subj</h1>$body";
  }
  return $html;
}
# ------------------------------------------------------------------------------------- cac meditace
# $par: 1=jen upravené nebo přeložené, 
#       2=přeložené i bez kontroly ale nikoliv překládané  ... DEFAULT
#       3=přeložené bez omezení 
# $plny 1=má být plný text včetně odkazů vpřed/vzad
function cac_meditace($ymd,$jmp,$plny,$par=2) {
  $dny= array('z neděle','z pondělí','z úterý','ze středy','ze čtvrtka','z pátku','ze soboty');
  $cond= 
      $par==1 ? "stav IN (3,4)" : (     // upraveno nebo přeloženo
      $par==2 ? "stav IN (0,1,3,4)"     // upraveno nebo přeloženo ale nepřekládáno
              : "1" );                  // vždy když existuje nějaký překlad
  if ($ymd) {
    $x= select_object('*','cac LEFT JOIN cactheme USING (id_cactheme)',
        "text_cz!='' AND $cond AND datum<='$ymd' ORDER BY datum DESC LIMIT 1",'ezertask');
  }
  if (!$ymd || !isset($x->datum)) {
    $x= select_object('*','cac LEFT JOIN cactheme USING (id_cactheme)',
        "text_cz!='' AND $cond ORDER BY datum DESC LIMIT 1",'ezertask');
  }
  $ymd= $x->datum;
  $w= $dny[(int)date("w",strtotime($x->datum))];
  $z_data= $w.' '.sql_date1($x->datum,0,'. ');
  // prefix
  $prefix= "";
  // přeložený text
  $preklad= $x->preklada 
      ? ( $x->stav==4 ? "přeložil " : ($x->stav==3 ? "po DeepL upravil " : "bude upravovat "))
        .select('forename','_user',"id_user='$x->preklada'",'ezertask') 
      : "přeloženo DeepL";
  $body= "<table cellpadding='10'><tr>";
  $body.= "<td valign='top' width='50%'><b>$x->title_cz</b><br>$x->text_cz
    <div align='right'><i>$x->author<br>$preklad</i></div></td>";
  $body.= "<td valign='top' width='50%'><a href='$x->url_text' target='cac'><b>$x->title_eng</b></a>
    <br>$x->text_eng
    <div align='right'><i>$x->author</i></div></td>";
  $body.= "</tr></table>";
  if ($plny) {
    // starší a novější myšlenka
    $go=  select('datum','cac',
        "text_cz!='' AND $cond AND datum<'$ymd' ORDER BY datum DESC LIMIT 1",'ezertask');
    $dalsi= $go
        ? "<a class='jump' href='$jmp,$go'>starší</a>"
        : "<span class='neodkaz'><a class='jump'>starší</a></span>";
    $go= select('datum','cac',
        "text_cz!='' AND $cond AND datum>'$ymd' ORDER BY datum ASC LIMIT 1",'ezertask');
    $dalsi.= $go
        ? "<a class='jump' href='$jmp,$go'>novější</a>"
        : "<span class='neodkaz'><a class='jump'>novější</a></span>";
  }
  // patička a redakce
  $postfix= "Zde se nacházejí překlady <b>Daily Meditations</b>, jejichž anglické originály 
    se nacházejí na webu <a href='https://cac.org/' target='cac'>CAC</a>. 
    V den jejich vydání je zde nalezneš přeložené strojově pomocí DeepL, 
    zpravidla do druhého dne pak projdou jazykovou úpravou někým z týmu překladatelů :-)  
    Pokud vládneš dobrou angličtinou, přihlas se asi raději přímo u zdroje těchto úvah, 
    tedy na webu CAC. Budeš je pak do své mailové schránky dostávat již k ranní kávě. 
    -mš-";
  $odkazy= $plny
      ? "<div style='float:right;text-align:right'>$dalsi<br>$preklad</div>"
      : '';
  $html= "$odkazy<h1>Překlad meditace CAC $z_data <br>na téma: 
      <a href='$x->url_theme' target='cac'>$x->theme_cz</a></h1>
      $prefix $body $x->reference
      <hr><i>$postfix</i>";
  return $html;
}
?>
