<?php
/** seznam oprávnění - setkani_cis/druh=skupina - jejich relevantní součet je v $fe_level
 *   1   5 admin     administrátor stránek
 *   2   2 super     redaktor, který může editovat a mazat příspěvky jiných redaktorů
 *   4   3 redaktor	 může přidávat příspěvky, editovat a mazat svoje příspěvky
 *   8   6 mrop	     iniciovani chlapi
 *  16  14 testér    testování novinek
 * --
 *  9 ritualy	skupina pro přípravu rituálů
 *  4 uzivatel	zaregistrovaný uživatel
 *  1 spravce DS správce Domu Setkání a osoby zodpovědné za provoz
 * 12 VPS	    aktivní vedoucí páry skupin, lektoři, organizátoři YMCA Setkání
 */
define(ADMIN,   1);
define(SUPER,   2);
define(REDAKTOR,4);
define(MROP,    8);
define(TESTER, 16);
# -------------------------------------------------------------------------------------==> page
function page($a,$b) { 
  global $CMS, $fe_user, $fe_level, $be_user;
  $CMS= 1;
  $be_user= isset($_SESSION['web']['be_user']) ? $_SESSION['web']['be_user'] : 0;
  $fe_user= isset($_SESSION['web']['fe_user']) ? $_SESSION['web']['fe_user'] : 0;
  read_menu();
  $path= explode('!',$b);
  $elem= eval_menu($path);
  $html= eval_elem($elem);
  $page= show_page($html);
  return $page;
}
# -------------------------------------------------------------------------------------==> def_menu
// načte záznamy z tabulky MENU do kterých uživatel smí vidět
// přidá položku has_subs pokud má hlavní menu submenu
function read_menu() { 
  global $ezer_db, $fe_level, $menu;
  // výpočet fe_level podle záznamu v ezer_db2.osoba.web_level a 
  $fe_level= isset($_SESSION['web']['fe_level']) ? $_SESSION['web']['fe_level'] : 0;
  // connect
  $ezer_local= preg_match('/^.*\.(bean)$/',$_SERVER["SERVER_NAME"]);
  $hst1= 'localhost';
  $nam1= $ezer_local ? 'gandi'    : 'gandi';
  $pas1= $ezer_local ? ''         : 'radost';
  $db1=  $ezer_local ? 'setkani'  : 'ezerweb';
  $ezer_db= array( /* lokální */
    'setkani'  =>  array(0,$hst1,$nam1,$pas1,'utf8',$db1),
  );
  ezer_connect('setkani');
  // načtení menu
  $menu= array();
  $mn= mysql_qry("SELECT * FROM menu WHERE wid=2 ORDER BY typ,rank");
  while ($mn && ($m= mysql_fetch_object($mn))) {
    if ( $m->typ<0 ) continue; 
    // filtrace chráněných položek
    if ( $m->level<0 && $fe_level && ($fe_level & -$m->level) ) continue;  // -8 nezobrazit pro mrop
    if ( $m->level>0 && !($fe_level & $m->level) ) continue;    //  8 zobrazit jen pro mrop
    if ( $m->level<0 ) $m->level= 0;
    $menu[$m->mid]= $m;
    if ( $m->typ==2 ) $menu[$m->mid_top]->has_subs= true;
  }
}
# -------------------------------------------------------------------------------------==> eval_menu
# path = [ mid, ...]
function eval_menu($path) { 
  global $CMS, $currpage, $fe_level, $tm_active, $ezer_local;
  global  $menu, $topmenu, $mainmenu, $submenu, $submenu_shift, $elem, $backref, $top;
  $prefix= $ezer_local
      ? "http://chlapi.bean:8080/2index.php?page="
      : "http://chlapi.online/2index.php?page=";
  $topmenu= $mainmenu= $submenu= '';
  $currpage= implode('!',$path);
  $top= array_shift($path);
  $main= $main_ref= $main_sub= 0;
  $elem= '';
  $input= '';
  $tm_active= '';
  $n_subs= $n_main= $o_main= 0;  // počet submenu, počet mainmenu, pořadí aktivního mainmenu zprava
  foreach ($menu as $m) {
    $level= '';
    // filtrace chráněných položek
    foreach (array(ADMIN=>'admin',SUPER=>'super',REDAKTOR=>'redaktor',MROP=>'mrop',TESTER=>'tester') 
        as $skill=>$class) {
      if ( $m->level & $skill ) {
        $level.= " $class";
      }
    }
    $href= $m->ref;
    $jmp= $CMS 
      ? "onclick=\"go(arguments[0],'page=$href','{$prefix}$href','$input',0);\""
      : "href='{$prefix}$href'";
    switch ( (int)$m->typ ) {
    case 0:                             // zobrazení top menu
      $active= '';
      if ( $m->ref===$top ) {
        $active= ' active';
        $elem= $m->elem;
//        $top= array_pop($path);
        $tm_active= " class='active'";
        $backref= $CMS 
          ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
          : "href='{$prefix}$href!*'";
        $top= array_shift($path);
      }
      $topmenu.= "<a $jmp class='jump$level$active'><span>$m->nazev</span></a>";
      break;
    case 1:                             // zobrazení main menu
      $n_main++;
      $active= '';
      if ( $m->ref===$top ) {
        $main= $m->mid;
        $main_ref= $m->ref;
        $main_sub= $m->mid_sub;
        $o_main= $n_main;
        $active= $m->has_subs ? ' active subs' : ' active';
        $elem= $m->elem;
        $backref= $CMS 
          ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
          : "href='{$prefix}$href!*'";
        $top= array_shift($path);
      }
      $mainmenu.= "<a $jmp class='jump$level$active'><span>$m->nazev</span></a>";
      break;
    case 2:                             // zobrazení submenu aktivního mainmenu
      if ( $m->mid_top===$main ) {
        $n_subs++;
        $active= '';
        $href= "$main_ref!$m->ref";
        if ( $top ? $m->ref===$top : $m->mid===$main_sub ) {
          $active= ' active';
          $elem= $m->elem;
          $backref= $CMS 
            ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
            : "href='{$prefix}$href!*'";
          $top= array_shift($path);
        }
        $jmp= $CMS 
          ? "onclick=\"go(arguments[0],'page=$href','{$prefix}$href','$input',0);\""
          : "href='{$prefix}$href'";
        $submenu.= "<a $jmp class='jump$level$active'><span>$m->nazev</span></a>";
      }
      break;
    }
  }
  // případný posun submenu
  $submenu_shift= 0;
  $r_main= $n_main-$o_main;
  if ( $r_main >= $n_subs ) {
    $submenu_shift= $r_main - $n_subs + 2;
  }
  return $elem;
}
# -------------------------------------------------------------------------------------==> eval_elem
// desc :: key [ = ids ]
// ids  :: id1 [ / id2 ] , ...    -- id2 je klíč v lokální db pro ladění
function eval_elem($desc) {
  global $ezer_local, $index, $load_ezer;
  $elems= explode(';',$desc);
  $html= '';
  foreach ($elems as $elem) {
    list($typ,$ids)= explode('=',$elem.'=');
    // přemapování ids podle server/localhost
    $id= null;
    if ( $ids ) {
      $id= array();
      foreach (explode(',',$ids) as $id12) {
        list($id_server,$id_local)= explode('/',$id12);
        $id[]= $id_local ? ($ezer_local ? $id_local : $id_server) : $id_server; 
      }
      $id= implode(',',$id);
    }
    $typ= str_replace(' ','',$typ);
//    $html= $CMS ? "<script>skup_mapka_off();</script>" : '';

    switch ($typ) {

    // admin -- zobrazení/skrytí administrátorských nástrojů 
    case 'admin':   # ------------------------------------------------ . admin
      $load_ezer= true;
      $cms_bar= $_SESSION['man']['cms_bar']= isset($_SESSION['man']['cms_bar']) 
          ? 1-$_SESSION['man']['cms_bar'] : 1;
      $html.= <<<__EOT
        <script>admin($cms_bar);</script>
__EOT;
      break;

    case 'ppt':     # ------------------------------------------------ . ppt
      global $CMS;
      $fname= "docs/$id.html";
      if ( file_exists($fname) ) {
        $doc= file_get_contents($fname);
//        $beg= '<div id=\"page-container\">';
//        $end= '<div class=\"loading-indicator\">';
//        $ok= preg_match("/$beg(.*)$end/u", $doc, $m);
//        $m= $m;
        $html.= $doc;
      }
      break;

    case 'mapa':    # ------------------------------------------------ . mapa
      global $CMS;
      $load_ezer= true;
      $html.= !$CMS ? '' : <<<__EOT
        <script>skup_mapka();</script>
__EOT;
      break;

    case 'skupiny': # ------------------------------------------------ . skupiny
      $load_ezer= true;
      $html.= <<<__EOT
        <div id='skup0' class="skup">V mapě ČR jsou zobrazena místa, na kterých se
          muži scházejí v malých skupinách, aby si pomáhali ... Pokud bydlíš poblíž
          nějaké skupiny a chceš vědět víc, klikni na její umístění.
          <br>
          Seznam skupin spravuje Lukáš Novotný - lenochod<i class='fa fa-at'></i>tiscali.cz 
        </div>
        <div id="skup1"></div>
        <div id="skup2"></div>
__EOT;
      break;

    // clanky=vzor získání abstraktů akcí podle roků a vzoru  {tx_gncase.chlapi RLIKE vzor}
    case 'akce':      # ------------------------------------------------ . akce
      global $y, $backref, $top, $links;
      $links= "fotorama";
      $html.= "<script>jQuery('.fotorama').fotorama();</script>";
      // získání pole abstraktů akcí
      $patt= $top ? "$id!$top" : $id;
      ask_server((object)array('cmd'=>'akce','chlapi'=>$patt,'back'=>$backref)); 
      // úzké abstrakty
      $html.= str_replace("abstr-line","abstr",$y->obsah);
      // překlad na globální odkazy do setkani.(org|bean)
      $fileadmin= $ezer_local 
          ? "http://setkani.bean:8080/fileadmin"
          : "https://www.setkani.org/fileadmin";
      $html= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$html);
      break;

    // clanky=vzor získání abstraktů článků s danou hodnotou {tx_gncase.chlapi RLIKE vzor}
    case 'clanky':    # ------------------------------------------------ . clanky
      global $y, $backref, $top, $links;
      $links= "fotorama";
      $html.= "<script>jQuery('.fotorama').fotorama();</script>";
      // získání pole abstraktů článků s danými ids 
      $patt= $top ? "$id!$top" : $id;
      ask_server((object)array('cmd'=>'clanky','chlapi'=>$patt,'back'=>$backref)); 
      // úzké abstrakty
      $html.= str_replace("abstr-line","abstr",$y->obsah);
      // překlad na globální odkazy do setkani.(org|bean)
      $fileadmin= $ezer_local 
          ? "http://setkani.bean:8080/fileadmin"
          : "https://www.setkani.org/fileadmin";
      $html= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$html);
      break;

    // clanek=pid -- samostatně zobrazený rozvinutý part
    case 'clanek':  # ------------------------------------------------ . clanek
      global $y;
      ask_server((object)array('cmd'=>'clanek','pid'=>$id));
      $fileadmin= $ezer_local 
          ? "http://setkani.bean:8080/fileadmin"
          : "https://www.setkani.org/fileadmin";
      $obsah= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$y->obsah);
      $nadpis= "<h1>$y->nadpis</h1>";
      $html.= "
        <div class='back'>
          <div id='clanek2' class='home'>
            $nadpis
            $obsah
          </div>
        </div>
      ";
      break;

    // kniha=cid -- samostatně zobrazený rozvinutý case
    case 'kniha':  # ------------------------------------------------ . kniha
      global $y, $backref, $top, $links;
      ask_server((object)array('cmd'=>'kniha','back'=>$backref,
          'cid'=>$ids,'kapitola'=>1)); //isset($path[0]) ? $path[0] : 0)); 
      $fileadmin= $ezer_local 
          ? "http://setkani.bean:8080/fileadmin"
          : "https://www.setkani.org/fileadmin";
      $obsah= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$y->obsah);
      $html.= "
        <div id='list'>
              $y->obsah
        </div>
      ";
      break;
    }
  }
  return $html;
}
# -------------------------------------------------------------------------------------==> show_page
function show_page($html) {
  global $CMS, $index, $GET_rok, $fe_user_display, $mode, $load_ezer, $ezer_local, $fe_user, $be_user;
  global  $bar_menu, $topmenu, $mainmenu, $submenu, $submenu_shift, $links, $currpage, $tm_active;
  // definice do <HEAD>
  
  // jádro Ezer - jen pokud není aktivní CMS
  $script= '';
  $client= "./ezer3/client";

  // gmaps
  $api_key= "AIzaSyAq3lB8XoGrcpbCKjWr8hJijuDYzWzImXo"; // Google Maps JavaScript API 'answer-test'
  $script.= !$load_ezer ? '' : <<<__EOJ
    <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=$api_key"></script>
__EOJ;
  
  $script.= <<<__EOJ
    <script src="$client/licensed/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="$client/licensed/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="man/2chlapi.js" type="text/javascript" charset="utf-8"></script>
__EOJ;
  
  $script.= $links!='fotorama' ? '' : <<<__EOJ
    <script src="man/fotorama/fotorama.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="./man/fotorama/fotorama.css" type="text/css" media="screen" charset="utf-8">
__EOJ;
  
  // pokud není CMS nebude uživatel přihlášen - vstup do Ezer je přes _oninit
  $script.= $CMS 
      ? <<<__EOJ
__EOJ
      : <<<__EOJ
    <script type="text/javascript">
      var Ezer= {};
      Ezer.web= {rok:'$GET_rok',index:'$index'};
      Ezer.get= { dbg:'1',err:'1',gmap:'1' };
      Ezer.fce= {};
      Ezer.str= {};
      Ezer.obj= {};
      Ezer.version= 'ezer3'; Ezer.root= 'man'; Ezer.app_root= 'man'; 
      Ezer.options= {
        _oninit: 'skup_mapka',
        skin: 'db'
      };
    </script>
__EOJ;

  $script.= !$load_ezer || $CMS 
      ? '' 
      : <<<__EOJ
    <script src="$client/ezer_app3.js"  type="text/javascript" charset="utf-8"></script>
    <script src="$client/ezer3.js"      type="text/javascript" charset="utf-8"></script>
    <script src="$client/ezer_lib3.js"  type="text/javascript" charset="utf-8"></script>
__EOJ;

//      <link rel="stylesheet" href="./man/web_edit.css" type="text/css" media="screen" charset="utf-8" />
  $n= isset($_GET['test']) ? $_GET['test'] : '3';
  $eb_link= <<<__EOJ
      <link rel="stylesheet" href="./man/{$n}chlapi.css" type="text/css" media="screen" charset="utf-8" />
      <link rel="stylesheet" href="./$client/licensed/font-awesome/css/font-awesome.min.css" type="text/css" media="screen" charset="utf-8" />
__EOJ;

  // head
  $icon= $ezer_local ? "man/img/chlapi_ico_local.png" : "man/img/chlapi_ico.png";
  $head=  <<<__EOD
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=11" />
    <meta name="viewport" content="width=device-width,user-scalable=yes,initial-scale=1" />
    <title>CHLAPI</title>
    <link rel="shortcut icon" href="$icon" />
    $script
    $eb_link
  </head>
__EOD;

  // menu pro změnu vzhledu, přihlášení ...
  $choice_js= "bar_menu(arguments[0],'menu_on'); return false;";
  $loginout= ($fe_user || $be_user)
    ? "<span onclick=\"be_logout('$currpage');\" class='separator'>
         <i class='fa fa-power-off'></i> odhlásit se</span>"
    : "<span onclick=\"bar_menu(arguments[0],'me_login');\" class='separator'>
         <i class='fa fa-user-secret'></i> přihlásit se emailem</span>";
//      <span onclick="bar_menu(arguments[0],'new1');"><img src='man/img/new.png'> změny za den</span>
//      <span onclick="bar_menu(arguments[0],'new7');"><img src='man/img/new.png'> změny za týden</span>
//      <span onclick="bar_menu(arguments[0],'new30');"><img src='man/img/new.png'> změny za měsíc</span>
//      <span onclick="bar_menu(arguments[0],'grid');" class='separator'><i class='fa fa-th'></i> akce jako mřížka</span>
//      <span onclick="bar_menu(arguments[0],'rows');"><i class='fa fa-bars'></i> akce jako řádky</span>
  $bar_menu= <<<__EOD
    <span id='bar_menu' data-mode='$mode[1]' onclick="$choice_js" oncontextmenu="$choice_js">
      <i class='fa fa-bars'></i>
    </span>
    <div id='bar_items'>
      $loginout
    </div>
__EOD;

  // menu
//  $filler= $submenu_shift ? str_repeat("<a><span class='menu_filler'></span></a>",$submenu_shift) : '';
  $filler= $submenu ? "<div class='filler'></div>" : '';
  $submenu= $submenu ? "<div id='page_sm'><span>$submenu</span>$filler</div>" : '';
  $menu= "
        <div id='page_tm'$tm_active>
          $topmenu
        </div>
        <div id='page_hm' class='x'>$mainmenu</div>
        $submenu
    ";

  // body   - vyjmuto onkeydown="if (event.keyCode==13) me_login('$currpage');"
  // body   - mapa je vložena do BlockMain pod #work
  if ( $CMS )
    $fe_user_display= 'none';
  $body=  <<<__EOD
    <div id='page'>
      <img id='logo' src='man/img/kriz.png' onclick="change_info();">
      $bar_menu
      <div id='menu'>
        $menu
      </div>
      <div id='user_mail' style="display:$fe_user_display">
        <span id='user_mail_head'>Přihlášení uživatele</span>
        <div>
          <span id="user_mail_txt">
            Napiš svoji mailovou adresu, na kterou ti dojde mail s PINem,
            který ti zpřístupní např. fotky z akcí, kterých ses zúčastnil ...
          </span>
          <input id='mail' type='text' placeholder='emailová adresa'>
          <input id='pin' type='text' placeholder='PIN'>
          <br>
          <a class='jump' onclick="me_login('$currpage');">Přihlásit</a>
          <a class='jump' onclick="jQuery('#user_mail').hide();">Zpět</a>
        </div>
      </div>
      $filler
      $html
    </div>
  <!-- konec -->
__EOD;

  if ( $CMS ) {
    return $body;
  }
  // dokončení stránky
  echo <<<__EOD
  $head
  <body>
    <div id='web'>
      <div id='work'>
      $body
      </div>
    </div>
  </body>
  </html>
__EOD;
}
/** ========================================================================================> SERVER */
# funkce na serveru přes AJAX
function servant($qry,$context=null) {
  global $y, $servant, $ezer_local;
  $secret= "WEBKEYNHCHEIYSERVANTAFVUOVKEYWEB";
  $servant= $ezer_local
    ? "http://setkani.bean:8080/servant.php?secret=$secret"
    : "https://www.setkani.org/servant.php?secret=$secret";
  $json= file_get_contents("$servant&$qry",false,$context);
                                                  display("<b style='color:red'>servant</b> $qry");
  if ( $json===false ) {
    $y->msg= "$qry vrátilo false";
  }
  elseif ( substr($json,0,1)=='{' ) {
    $y= json_decode($json);
  }
  else {
    $y->msg= "Sorry, došlo k chybě č.4, martin@smidek.eu ti poradí ...";
                                                  display($y->msg);
//    $y->msg= "'$servant&$qry' vrátil '$json'";
  }
}
function ask_server($x) {
  global $y;
//   $x->cmd= 'test';
  switch ( $x->cmd ) {
  case 'clanky':   // ----------------------------------------------------------------------- clanky
    $y= (object)array('msg'=>'neznámý článek');
    servant("clanky=$x->chlapi&back=$x->back"); // part.uid
    break;
  
  case 'akce':     // ------------------------------------------------------------------------- akce
    $y= (object)array('msg'=>'neznámá akce');
    servant("akce=$x->chlapi&back=$x->back"); // part.uid
    break;
  
  case 'knihy':   // ------------------------------------------------------------------------- knihy
    $y= (object)array('msg'=>'neznámá kniha');
    servant("knihy=$x->chlapi&back=$x->back"); // part.uid
    break;
  
  case 'clanek':   // ----------------------------------------------------------------------- clanek
    $y= (object)array('msg'=>'neznámý článek');
    servant("clanek=$x->pid"); // part.uid
    break;
  
  case 'kniha':     // ----------------------------------------------------------------------- kniha
    $y= (object)array('msg'=>'neznámý článek');
    servant("kniha=$x->cid&page=$x->page&kapitola=$x->kapitola"); // case.uid,part.uid
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
      $_SESSION['web']['fe_level']= $y->level | ($y->mrop ? MROP : 0);
      $_SESSION['web']['fe_username']= $y->name;
      $y->fe_user= $y->user;
      $y->be_user= 0;
    } 
    break;

  case 'be_logout': // ---------------------------------------------------------------------- logout
    $_SESSION['web']= array();
    $_SESSION['web']['fe_user']= 0;
    $_SESSION['web']['fe_level']= 0;
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

?>
