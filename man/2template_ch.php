<?php
define(VERZE,   '19/7 9:43'); 
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
define(ADMIN,   1); // a
define(SUPER,   2); // s
define(REDAKTOR,4); // e
define(MROP,    8); // i
define(TESTER, 16); // t
# -------------------------------------------------------------------------------------==> page
function page($a,$b) { 
  global $CMS, $fe_user, $fe_level, $be_user;
//  global $edit_entity, $edit_id;
  $CMS= 1;
  $be_user= isset($_SESSION['web']['be_user']) ? $_SESSION['web']['be_user'] : 0;
  $fe_user= isset($_SESSION['web']['fe_user']) ? $_SESSION['web']['fe_user'] : 0;
  read_menu();
  $path= explode('!',$b);
  $elem= eval_menu($path);
  $html= eval_elem($elem);
  $page= show_page($html);
//  return $page;
//  return (object)array('html'=>$page,'edit'=>$edit_entity,'id'=>$edit_id);
  return (object)array('html'=>$page);
}
# -------------------------------------------------------------------------------------==> def_menu
// načte záznamy z tabulky MENU do kterých uživatel smí vidět
// přidá položku has_subs pokud má hlavní menu submenu
function read_menu() { 
  global $ezer_db, $fe_level, $menu;
  // výpočet fe_level podle záznamu v ezer_db2.osoba.web_level a 
  $fe_level= isset($_SESSION['web']['fe_level']) ? $_SESSION['web']['fe_level'] : 0;
  // connect
  db_connect();
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
  global $CMS, $currpage, $fe_level, $tm_active, $ezer_local, $index;
  global  $menu, $topmenu, $mainmenu, $submenu, $submenu_shift, $elem, $curr_menu, $backref, $top;
  global $prefix, $href, $input;
//      ? "http://chlapi.bean:8080/$index?page="
//      : "http://chlapi.online/$index?page=";
  $prefix= $ezer_local
      ? "http://chlapi.bean:8080/"
      : "http://www.chlapi.cz/";
//      ? "http://chlapi.bean:8080/"
//      : "http://chlapi.online/";
  $topmenu= $mainmenu= $submenu= '';
  $currpage= implode('!',$path);
  $top= array_shift($path);
  $main= $main_ref= $main_sub= 0;
  $elem= '';
  $curr_menu= 0;
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
        $curr_menu= $m;
//        $top= array_pop($path);
        $tm_active= " class='active'";
        $backref= $CMS 
          ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
          : "href='{$prefix}$href!*'";
        $top= array_shift($path);
      }
      $topmenu.= $m->nazev ? "<a $jmp class='jump$level$active'><span>$m->nazev</span></a>" : '';
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
        $curr_menu= $m;
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
          $curr_menu= $m;
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
function eval_elem($desc,$book=null) {
  global $CMS, $ezer_local, $index, $load_ezer, $fe_level, $curr_menu, $top, $prefix;
//  global $edit_entity, $edit_id;
  $elems= explode(';',$desc);
  $html= '';
  $html= $CMS ? "<script>skup_mapka_off();</script>" : '';
  $layout= ''; // default layout stránky 
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

    switch ($typ) {

    case 'verze':   # ----------------------------------------------- . verze
      $v= VERZE;
      $html.= <<<__EOT
        <script>alert("verze CMS je $v");</script>
__EOT;
      break; 
    
    case 'layout':  # ----------------------------------------------- . layout
      $layout= $id;
      break; 
    
    case 'akniha':  # ----------------------------------------------- . kniha
    case 'xkniha':  
      global $backref;
      list($idk,$ida)= explode(',',$top);
      list($nazev,$xelems,$xskill)= select("nazev,xelems,web_skill","xkniha","id_xkniha=$id");
      $xskill= 0+$xskill;
      $otevrena= $top && $idk==$id && (!$xskill || $fe_level & $xskill);
      $menu= '';
      if ( $otevrena ) {
        // otevřená kniha
        if ( $CMS ) {
          $menu= " title='kniha $idk' oncontextmenu=\"
              Ezer.fce.contextmenu([
                ['upravit název',function(el){ opravit('xkniha',$idk); }],
                ['-nová kapitola na začátek',function(el){ pridat('xkniha.elem',$idk,1); }],
                ['nová kapitola na konec',function(el){ pridat('xkniha.elem',$idk,0); }]
              ],arguments[0],0,0,'#xclanek$id');return false;\"";
        }
        // nadpis
        $html.= "
          <div class='kniha_bg' id='fokus_page'>
            <div class='kniha_br' $menu>
              <b>$nazev</b>
            </div>";
        // kapitoly
        $top= $ida;
        if ( $elems ) {
          $html.= eval_elem($xelems,(object)array('open'=>true,'idk'=>$id));
        }
        // konec 
        $html.= "
            <div class='kniha_br'>
              Konec knihy <b>$nazev</b>
            </div>
          </div>";
      }
      else {
        // zavřená kniha
        // zobrazení 1. kapitoly - musí to být aclanek
        list($xelem)= explode(';',$xelems);
        if ( $xelem ) {
          $html.= "<div title='kniha $id'>";
          $html.= eval_elem($xelem,(object)array(
              'open'=>false,'idk'=>$id/*,'tit'=>$nazev*/,'skill'=>$xskill));
          $html.= "</div>";
        }
      }
      break;

    case 'note':    # ----------------------------------------------- . note
      $html.= "<div style='background:white;color:black;text-align:center'>POZNAMKA</div>";
      break;
    
    case 'aclanek': # ------------------------------------------------ . ačlánek - abstrakt
      global $backref, $links;
      $links= "fotorama";
      $html.= "<script>jQuery('.fotorama').fotorama();</script>";
      list($obsah,$xskill)= select("web_text,web_skill","xclanek","id_xclanek=$id");
      $xskill= 0+$xskill;
      $obsah= str_replace('$index',$index,$obsah);
      $menu= $note= '';
      $idn= $id;
      if ( $book ) {
        $idn= $book->open ? "$book->idk,$id" : $book->idk;
        $xskill= $book->skill ? $book->skill | $xskill : $xskill; 
      }
      $plny= $top==$id && (!$xskill || $fe_level & $xskill);
      $co= $plny ? 'článek' : 'abstrakt';
      if ( $CMS ) {
        $obsah= preg_replace("~href=\"(?:http://www.chlapi.cz/|/|(?!https?://))(.*)\"~U", 
              "onclick=\"go(arguments[0],'page=$1','$prefix$1','',0);\" title='$1'", 
              $obsah);
        if ( !$book  ) 
          $menu= " title='$co $idn' oncontextmenu=\"
              Ezer.fce.contextmenu([
                ['editovat článek',function(el){ opravit('xclanek',$id); }],
                ['-zobrazit jako článek',function(el){ zmenit($curr_menu->mid,'aclanek',$id,'xclanek'); }],
                ['-přidat fotky',function(el){ pridat('xfotky',$id); }],
                ['-nový článek na začátek',function(el){ pridat('xclanek',$curr_menu->mid,1); }],
                ['nový článek na konec',function(el){ pridat('xclanek',$curr_menu->mid,0); }]
              ],arguments[0],0,0,'#xclanek$id');return false;\"";
        elseif ( $book->open) 
          $menu= " title='$co $idn' oncontextmenu=\"
              Ezer.fce.contextmenu([
                ['editovat článek',function(el){ opravit('xclanek',$id); }],
                ['-zobrazit jako článek',function(el){ zmenit($curr_menu->mid,'aclanek',$id,'xclanek'); }],
                ['-přidat fotky',function(el){ pridat('xfotky',$id); }],
                ['-nová kapitola na začátek',function(el){ pridat('xkniha.elem',$book->idk,1); }],
                ['nová kapitola na konec',function(el){ pridat('xkniha.elem',$book->idk,0); }]
              ],arguments[0],0,0,'#xclanek$id');return false;\"";
      }
      $jmp= str_replace('*',$idn,$backref);
      if ( $plny ) {
        // zobrazit jako plný článek
        $html.= "
          <div class='back' $menu>
            <div id='fokus_part' class='home'>
              $obsah
            </div>
          </div>";
        // pokud jsou fotky, přidáme
        $rf= mysql_qry("SELECT id_xfotky,nazev,seznam,path FROM xfotky WHERE id_xclanek=$id");
        while ($rf && list($fid,$nazev,$seznam,$path)=mysql_fetch_row($rf)) {
          if ( $CMS ) {
            $note= "<span style='float:right;color:red;font-style:italic;font-size:x-small'>
                  ... zjednodušené zobrazení fotogalerie pro editaci</span>";
            $menu= " title='fotky $fid' oncontextmenu=\"
                Ezer.fce.contextmenu([
                  ['organizovat fotky',function(el){ opravit('xfotky',$fid); }],
                  ['kopírovat ze setkání',function(el){ zcizit('fotky',$fid,0); }],
                  ['... jen test',function(el){ zcizit('fotky',$fid,1); }]
                ],arguments[0],0,0,'#xclanek$id');return false;\"";
          }
          $galery= show_fotky2($fid,$seznam);
          $html.= "
            <div class='galery_obal' $menu>
              <div id='xfotky$fid' class='galerie'>
                <div class='text'>
                  <h1>&nbsp;&nbsp;&nbsp;$nazev $note</h1>
                  $galery
                  <div class='podpis'>podpis</div>
                </div>
              </div>
            </div>
          ";
        }
      }
      else {
        // zobrazit jako abstrakt
        $obsah= x_shorting($obsah);
        if ( $book->tit ) {
          $obsah= "<b>$book->tit</b> $obsah";
        }
        $styl= 'aclanek';
        $neodkaz= '';
        if ( $xskill && !($fe_level & $xskill) ) {
          $jmp= '';
          $neodkaz= "onclick=\"jQuery('div.neodkaz').fadeIn();\"";
          $styl= 'aclanek_nojump';
        }
        $html.= "
          <div class='back' $menu $neodkaz>
            <a class='$styl home' $jmp>
              $obsah
            </a>
          </div>";
      }
      break;

    case 'xclanek': # ------------------------------------------------ . xčlánek
      list($obsah,$xskill)= select("web_text,web_skill","xclanek","id_xclanek=$id");
      if ( $xskill && !($fe_level & $xskill) ) {
        break;
      }
      $obsah= str_replace('$index',$index,$obsah);
      $menu= '';
      if ( $CMS ) {
        $obsah= preg_replace("~href=\"(?:http://www.chlapi.cz/|/|(?!https?://))(.*)\"~U", 
              "onclick=\"go(arguments[0],'page=$1','$prefix$1','',0);\" title='$1'", 
              $obsah);
        $menu= " title='článek $id' oncontextmenu=\"
            Ezer.fce.contextmenu([
              ['editovat článek',function(el){ opravit('xclanek',$id); }],
              ['-zobrazit jako abstrakt',function(el){ zmenit($curr_menu->mid,'xclanek',$id,'aclanek'); }],
              ['-přidat článek na začátek',function(el){ pridat('xclanek',$curr_menu->mid,1); }],
              ['přidat článek na konec',function(el){ pridat('xclanek',$curr_menu->mid,0); }]
            ],arguments[0],0,0,'#xclanek$id');return false;\"";
      }
      $html.= "
        <div class='back' $menu>
          <div id='xclanek$id' class='home'>
            $obsah
          </div>
        </div>
      ";
      break;

    case 'kalendar': # ----------------------------------------------- . kalendar
      global $y;
//      $edit_entity= 'kalendar';
      $edit_id= 0;
      // zjistíme YS + FA
      ask_server((object)array('cmd'=>'kalendar'));
      // přidáme lokálně zapsané akce
      ezer_connect('setkani');
      $qa= "SELECT id_xakce,datum_od,datum_do,nazev,misto,web_text 
          FROM xakce WHERE datum_od>NOW() ORDER BY datum_od";
      $ra= mysql_query($qa);
      while ( $ra && list($id,$od,$do,$nazev,$misto,$text)=mysql_fetch_array($ra)) {
        $oddo= datum_oddo($od,$do);
        if ( !$edit_id )
          $edit_id= $id;
        $y->akce[]= (object)array('od'=>$od,'nazev'=>$nazev,'misto'=>$misto,
            'oddo'=>$oddo,'text'=>$text);
      }
      // seřadíme podle data
      usort($y->akce,function($a,$b) { 
        return strnatcmp($a->od,$b->od);
      });
      $menu= '';
      if ( $CMS ) {
        $menu= " oncontextmenu=\"
            Ezer.fce.contextmenu([
              ['editovat kalendář',function(el){ opravit('kalendar',$edit_id); }]
            ],arguments[0],0,0,'#xclanek$id');return false;\"";
      }
      // zformátujeme kalendář
      $html.= "<div class='back' $menu><div id='clanek2' class='home'><table class='kalendar'>";
      if ( count($y->akce) ) {
        foreach ($y->akce as $a) {
          if ( $a->org ) {
            $org=
              $a->org==1 ? "YMCA Setkání" : (
              $a->org==2 ? "YMCA Familia" : '');
            $web= $a->url ? "<a href='$a->url' target='web'>$org</a>" : (
              $a->org==1 ? "<a href='https://www.setkani.org'>$org</a>" : (
              $a->org==2 ? "<a href='http://www.familia.cz'>$org</a>" : '')
            );
            $web= "přihlášku najdeš na webu $web";
          }
          else {
            $web= $a->text;
          }
          $oddo= $a->oddo;
          if ( $a->obsazeno ) {
            $web.= ", ale akce je <b>obsazena</b>";
            $oddo= "<s>$oddo</s>";
          }
          $anotace= $a->anotace ? "<br><i>$a->anotace</i>" : '';
          $html.= "<tr><td>$oddo</td><td><b>$a->nazev</b>, $a->misto<br><i>$web$anotace</i></td></tr>";
        }
      }
      $html.= "</table></div></div>";
      break;

    case 'ppt':     # ------------------------------------------------ . ppt
      global $CMS;
      $fname= "pdf/$id.html";
      if ( file_exists($fname) ) {
        $doc= file_get_contents($fname);
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
      $tabulku= $fe_level
          ? "<a target='tab' href='https://docs.google.com/spreadsheets/d/1mp-xXrF1I0PAAXexDH5FA-n5L71r5y0Qsg75cU82X-4/edit#gid=0'>Tabulku</a>"
          : "Tabulku";
      $html.= <<<__EOT
        <div id='skup0' class="skup">V mapě ČR jsou zobrazena místa, na kterých se
          muži scházejí v malých skupinách, aby si pomáhali ... Pokud bydlíš poblíž
          nějaké skupiny a chceš vědět víc, klikni na její umístění.
          <br>
          $tabulku seznamu skupin spravuje Lukáš Novotný - lenochod<i class='fa fa-at'></i>tiscali.cz 
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
      global $y, $backref, $top, $links, $CMS;
      $links= "fotorama";
      $ys_html= "<script>jQuery('.fotorama').fotorama();</script>";
      // získání pole abstraktů článků s danými ids 
      $patt= $top ? "$id!$top" : $id;
      ask_server((object)array('cmd'=>'clanky','chlapi'=>$patt,'back'=>$backref)); 
      // úzké abstrakty
      $ys_html.= $y->obsah;
      $ys_html= strtr($ys_html,array(
          "class='abstr-line'"   => "class='back'",
          "class='abstr-line x'" => "class='back'",
          "class='abstrakt  x '" => "class='aclanek home'",
          "class='abstrakt x'"   => "class='aclanek home'",
          "<hr style='clear:both;border:none'>"  => "<hr style='clear:both;display:none'>"
      ));
      // překlad na globální odkazy do setkani.(org|bean)
      $fileadmin= $ezer_local 
          ? "http://setkani.bean:8080/fileadmin"
          : "https://www.setkani.org/fileadmin";
      $ys_html= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$ys_html);
      // vložení tranformované prezentace
      // 1) z PPT uložené jako PDF s minimalizací pro web
      // 2) pdf2htmlEX --use-cropbox 0 --fit-width 800 --embed CFIJO --bg-format jpg $fname.pdf
      if ( $top ) {
        if ( $CMS ) {
          $ys_kniha= preg_match("~id='fokus_page'~", $ys_html);
          $co= "setkani_".($ys_kniha?"k":"c");
          $cosi= $ys_kniha?'knihy':'článku';
          $menu= " title='".($ys_kniha?'kniha':'článek')." setkani.org: $top' oncontextmenu=\"
              Ezer.fce.contextmenu([
                ['kopie $cosi ze setkání/$top',function(el){ zcizit('$co',$top,$curr_menu->mid); }],
                ['... jen test',function(el){ zcizit('?$co',$top,$curr_menu->mid); }]
              ],arguments[0],0,0,'#clanek$top');return false;\"";
          $ys_html= strtr($ys_html,array(
              "id='fokus_page'"  => "id='fokus_page' $menu",
              "id='clanek$top'"  => "id='clanek$top' $menu"
          ));
        }
        $ys_html= preg_replace_callback("/(###([\w\-]+)###)/",
          function($m) {
            global $CMS;
            $fname= "pdf/$m[2].html";
            if ( file_exists($fname) ) {
              if ( $CMS )
                return "<span class='sorry'>soubor $fname existuje, 
                        ale jako administrátor jej neuvidíš (velký overhead)</span>";
              else
                return file_get_contents($fname);
            }
            else
              return "<span class='sorry'>soubor $fname neexistuje</span>";
          }, 
          $ys_html);
      }
      $html.= $ys_html;
      break;

    // clanek=pid -- samostatně zobrazený rozvinutý part
    case 'clanek':  # ------------------------------------------------ . clanek
      global $y;
      ask_server((object)array('cmd'=>'clanek','pid'=>$id));
      $fileadmin= $ezer_local 
          ? "http://setkani.bean:8080/fileadmin"
          : "https://www.setkani.org/fileadmin";
      $obsah= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$y->obsah);
      $obsah= str_replace('$index',$index,$obsah);
      $nadpis= "<h1>$y->nadpis</h1>";
      $html.= "
        <div class='back' title='setkani.org: $id'>
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
  if ( $layout ) {
    $html= "<div class='$layout'>$html</div>";
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

  // Google Analytics
  $GoogleAnalytics= $ezer_local ? '' : <<<__EOD
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
    ga('create', 'UA-99235788-2', 'auto');
    ga('send', 'pageview');
__EOD;

  // gmaps
  $api_key= "AIzaSyAq3lB8XoGrcpbCKjWr8hJijuDYzWzImXo"; // Google Maps JavaScript API 'answer-test'
  $script.= !$load_ezer ? '' : <<<__EOJ
    <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=$api_key"></script>
__EOJ;
  
  $script.= <<<__EOJ
    <script src="$client/licensed/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="$client/licensed/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/man/2chlapi.js" type="text/javascript" charset="utf-8"></script>
__EOJ;
  
  $script.= $links!='fotorama' ? '' : <<<__EOJ
    <script src="/man/fotorama/fotorama.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="/man/fotorama/fotorama.css" type="text/css" media="screen" charset="utf-8">
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
    $GoogleAnalytics
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
  $n= isset($_GET['test']) ? $_GET['test'] : '2';
  $eb_link= <<<__EOJ
      <link rel="stylesheet" href="/man/css/{$n}chlapi.css" type="text/css" media="screen" charset="utf-8" />
      <link rel="stylesheet" href="/man/css/edit.css" type="text/css" media="screen" charset="utf-8" />
      <link rel="stylesheet" href="/$client/licensed/font-awesome/css/font-awesome.min.css" type="text/css" media="screen" charset="utf-8" />
__EOJ;

  // head
  $icon= $ezer_local ? "/man/img/chlapi_ico_local.png" : "/man/img/chlapi_ico.png";
  $head=  <<<__EOD
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=11" />
    <meta name="viewport" content="width=device-width,user-scalable=yes,initial-scale=1" />
    <title>chlapi.cz</title>
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
  $go_home= $CMS 
      ? "onclick=\"go(arguments[0],'page=home','{$prefix}home','',0);\""
      : "href='{$prefix}home'";

  $body=  <<<__EOD
    <div id='page'>
      <a $go_home style="cursor:pointer"><img id='logo' src='/man/img/kriz.png'></a>
      <div id='motto'>Mladý muž, který neumí plakat, je barbar.
          <br>Starý muž, který se neumí smát, je pitomec.
          <br><i>Richard Rohr</i>
      </div>
      <div id='menu'>
        $bar_menu
        $menu
      </div>
      <div class='neodkaz' style="display:none">
        <div id='clanek2' class='home' style="background:#CFDDE6">
          <p>Modré <span class='neodkaz'><a class='jump'>odkazy</a></span> jsou bez přihlášení neaktivní.</p>
          <p> Pokud chceš vidět úplné texty článků, musíš být přihlášen.</p>
          <p>K přihlašovacímu dialogu se dostaneš pomocí menu <i class="fa fa-bars"></i> v pravém horním rohu.</p>
          <p>Přihlásit se můžeš pomocí mailové adresy, kterou jsi 
          uvedl v přihlášce na akci MROP. Pokud ses této akce ještě nezúčastnil, 
          přihlášení možné nebude.</p>
          <a class='jump' onclick="jQuery('div.neodkaz').fadeOut();">Rozumím</a>
        </div>
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
          <a class='jump noedit' onclick="me_noedit(1);">chci prohlížet</a>
          <a class='jump noedit' onclick="me_noedit(0);">chci editovat</a>
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
  global $fe_level;
  echo <<<__EOD
  $head
  <body onload="jump_fokus($fe_level);">
    <div id='web'>
      <div id='work'>
      $body
      </div>
    </div>
  </body>
  </html>
__EOD;
}
/** ========================================================================================> KLIENT */
# dá další nebo předchozí akci
function next_xakce($curr_id,$smer=1) {
  $msg= '';
  if ( $curr_id ) {
    $curr_datum= select('datum_od','xakce',"id_xakce=$curr_id");
    $rel= $smer==1 ? '<' : '>';
    $mmm= $smer==1 ? 'MAX' : 'MIN';
    $id= select1("SUBSTR($mmm(CONCAT(datum_od,id_xakce)),11)",'xakce',
        "datum_od>NOW() AND datum_od $rel '$curr_datum'");
    if ( !$id ) {
      $id= $curr_id;
      $msg= "To je informace o ".($smer==1 ? 'první' : 'poslední').
          " připravovaná akci - ostatní informace jsou do kalendáře importovány přímo z "
          . "databáze akcí YMCA Setkání a YMCA Familia";
      
    }
  }
  else {
    $msg= "";
  }
  return (object)array('msg'=>$msg,'id'=>$id);
}
/** =========================================================================================> FOTKY */
# --------------------------------------------------------------------------------==> . show fotky 2
# uid určuje složku
function show_fotky2($fid,$lst,$back_href='') {
  global $CMS, $href0, $clear;
  if ( $CMS ) return show_fotky($fid,$lst,$back_href);
  $lstx= $lst;
  $h= '';
  $fs= explode(',',$lstx);
  $last= count($fs)-1;
  $ih= "<div class='fotorama'
    data-allowfullscreen='native'
    data-caption='true'
    data-width='100%'
    xdata-ratio='800/400'
    data-nav='thumbs'
    data-x-autoplay='true'
  >";
  for ($i= 0; $i<$last; $i+=2) {
    $mini= "inc/f/$fid/..$fs[$i]";
    $open= "inc/f/$fid/.$fs[$i]";
    $orig= "inc/f/$fid/$fs[$i]";
    if ( file_exists($mini) ) {
      $mini= str_replace(' ','%20',$mini);
      $title= '';
      if ( $fs[$i+1] ) {
        $title= $fs[$i+1];
        $title= strtr($title,array('##44;'=>',',"'"=>'"','~'=>'-'));
        $title= " data-caption='$title'";
      }
      $i2= $i/2;
      $ih.= "<img src='$open' $title>";
    }
  }
  $ih.= "</div>";
  return $ih;
}
# ----------------------------------------------------------------------------------==> . show fotky
# uid určuje složku
function show_fotky($fid,$lst,$back_href) { 
  global $CMS, $href0, $clear;
  $lstx= $lst;
//  popup("Prohlížení fotografií","$fid~$lstx",$back_href,'foto');
  $h= '';
  $fs= explode(',',$lstx);
  $last= count($fs)-1;
  for ($i= 0; $i<$last; $i+=2) {
    $mini= "inc/f/$fid/..$fs[$i]";
    $open= "inc/f/$fid/.$fs[$i]";
    $orig= "inc/f/$fid/$fs[$i]";
    if ( file_exists($mini) ) {
      $mini= str_replace(' ','%20',$mini);
      $title= $fs[$i];
      if ( $fs[$i+1] ) {
        $title= $fs[$i+1];
        $title= strtr($title,array('##44;'=>',',"'"=>'"','~'=>'-'));
      }
      $i2= $i/2;
      $onclick= $CMS ? '' : " onclick=\"foto_show(arguments[0],$i2);return false;\"";
      $h.= " <span data-foto-n='$i2' title='$title' $onclick
               class='foto foto_cms' style='background-image:url($mini)'></span>";
    }
  }
  return $h;
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
  case 'kalendar': // --------------------------------------------------------------------- kalendar
    $y= (object)array('anotace'=>'zatím nejsou naplánovány žádné akce');
    servant("kalendar");
    break;
  
  case 'clanky':   // ----------------------------------------------------------------------- clanky
    $y= (object)array('msg'=>'neznámý článek');
    servant("clanky=$x->chlapi&back=$x->back&groups={$_SESSION['web']['fe_usergroups']}"); // part.uid
    break;
  
  case 'akce':     // ------------------------------------------------------------------------- akce
    $y= (object)array('msg'=>'neznámá akce');
    servant("akce=$x->chlapi&back=$x->back&groups={$_SESSION['web']['fe_usergroups']}"); // part.uid
    break;
  
  case 'knihy':   // ------------------------------------------------------------------------- knihy
    $y= (object)array('msg'=>'neznámá kniha');
    servant("knihy=$x->chlapi&back=$x->back&groups={$_SESSION['web']['fe_usergroups']}"); // part.uid
    break;
  
  case 'clanek':   // ----------------------------------------------------------------------- clanek
    $y= (object)array('msg'=>'neznámý článek');
    servant("clanek=$x->pid&groups={$_SESSION['web']['fe_usergroups']}"); // part.uid
    break;
  
  case 'kapitoly':   // ------------------------------------------------------------------- kapitoly
    $y= (object)array('msg'=>'neznámá kniha');
    servant("kapitoly=$x->pid"); // part.uid
    break;
  
  case 'kniha':     // ----------------------------------------------------------------------- kniha
    $y= (object)array('msg'=>'neznámý článek');
    servant("kniha=$x->cid&page=$x->page&kapitola=$x->kapitola&groups={$_SESSION['web']['fe_usergroups']}"); // case.uid,part.uid
    break;
  
  case 'sendmail': // -------------------------------------------------------------------- send mail
    $tos= preg_split("~[\s,;]~", $x->to,-1,PREG_SPLIT_NO_EMPTY);
    foreach($tos as $to) {
      $_SESSION['web']['phpmailer1']= $to;
      $y->ok= emailIsValid($to,$err) ? 1 : 0;
      if ( $y->ok ) {
        $ret= mail_send($x->reply,$to,$x->subj,$x->body);
        $_SESSION['web']['phpmailer2']= $ret;
        $y->txt= $ret->err
          ? "Mail se bohužel nepovedlo odeslat ($ret->err)"
          : "mail byl odeslán organizátorům skupiny";
      }
      else {
        $y->txt= "'$to' nevypadá jako mailová adresa ($err)";
      }
    }
    break;

  case 'mapa':     // ------------------------------------------------------------------------- mapa
    servant("mapa=$x->mapa");
    break;

  case 'me_login': // ------------------------------------------------------------------------ login
    servant("cmd=me_login&mail=$x->mail&pin=$x->pin&web=$x->web");
    if ( isset($y->state) && $y->state=='ok') {
      // přihlas uživatele jako FE
      $_SESSION['web']['fe_usergroups']= '0,4,6';
      $_SESSION['web']['fe_userlevel']= 0;
      $_SESSION['web']['fe_user']= $y->user;
      $_SESSION['web']['fe_level']= $y->level | ($y->mrop ? MROP : 0);
      $_SESSION['web']['fe_username']= $y->name;
      $y->fe_user= $y->user;
      $y->be_user= 0;
      if ( !($y->level & REDAKTOR) ) {
        // pokud to není redaktor zapiš me_login 
        log_login('u',$x->mail);
      }
    }
    else {
      // zapiš problém s me_login
      log_login('-',$x->mail);
    }
    break;
    
  case 'me_noedit': // ---------------------------------------------------------------------- noedit
    // zrušení možnosti editovat => plné prohlížení
    $_SESSION['web']['fe_level']= $_SESSION['web']['fe_level'] & ~ADMIN & ~SUPER & ~REDAKTOR;
    $y->user= $_SESSION['web']['fe_user'];
    $y->username= $_SESSION['web']['fe_username'];
    log_login('u',$x->mail);
    break;

  case 'be_logout': // ---------------------------------------------------------------------- logout
    log_login('x');
    unset($_SESSION['web']);
    unset($_SESSION['man']);
    session_write_close();
    $y->fe_user= 0;
    $y->be_user= 0;
    $y->page= $x->page;
    break;

  }
  return 1;
}
# --------------------------------------------------------------------------------------- datum oddo
function datum_oddo($x1,$x2) {
  $d1= 0+substr($x1,8,2);
  $d2= 0+substr($x2,8,2);
  $m1= 0+substr($x1,5,2);
  $m2= 0+substr($x2,5,2);
  $r1= 0+substr($x1,0,4); 
  $r2= 0+substr($x2,0,4);
  $r= date('Y');
  if ( $x1==$x2 ) {  //zacatek a konec je stejny den
    $datum= "$d1. $m1. $r1"; // . ($r1!=$r ? ". $r1" : '');
  }
  elseif ( $r1==$r2 ) {
    if ( $m1==$m2 ) { //zacatek a konec je stejny mesic
      $datum= "$d1 - $d2. $m1. $r1";
    }
    else { //ostatni pripady
      $datum= "$d1. $m1 - $d2. $m2. $r1";
    }
  }
  else { //ostatni pripady
    $datum= "$d1. $m1. $r1 - $d2. $m2. $r2";
  }
  return $datum;
}
# ------------------------------------------------------------------------------------------ session
# getter a setter pro _SESSION
function session($is,$value=null) {
  $i= explode(',',$is);
  if ( is_null($value) ) {
    // getter
    switch (count($i)) {
    case 1: $value= $_SESSION[$i[0]]; break;
    case 2: $value= $_SESSION[$i[0]][$i[1]]; break;
    case 3: $value= $_SESSION[$i[0]][$i[1]][$i[2]]; break;
    }
  }
  else {
    // setter
    switch (count($i)) {
    case 1: $_SESSION[$i[0]]= $value; break;
    case 2: $_SESSION[$i[0]][$i[1]]= $value; break;
    case 3: $_SESSION[$i[0]][$i[1]][$i[2]]= $value; break;
    }
    $value= 1;
  }
  return $value;
}
# --------------------------------------------------------------------------------------- db connect
# připojí databázi
function db_connect() { 
  global $ezer_db;
  $ezer_local= preg_match('/^.*\.(bean)$/',$_SERVER["SERVER_NAME"]);
  $hst1= 'localhost';
  $nam1= $ezer_local ? 'gandi'    : 'gandi';
  $pas1= $ezer_local ? ''         : 'radost';
  $db1=  $ezer_local ? 'chlapi'  : 'ezerweb';
  $ezer_db= array( /* lokální */
    'setkani'  =>  array(0,$hst1,$nam1,$pas1,'utf8',$db1),
  );
  ezer_connect('setkani');
}
/** =========================================================================================> ADMIN */
# --------------------------------------------------------------------------------------- log report
# vrátí seznam 
# - změn obsahu
# - chybných pokusů o přihlášení do chlapi.online
function log_report($par) { trace();
  $html= "";
  switch ($par->cmd) {
  case 'obsah':    // -------------------------------------- obsah
    $dnu= $par->days;
    $html.= "<dl>";
    $cr= mysql_qry("
      SELECT kdo,MAX(kdy),jak,tab,id_tab,IFNULL(username,kdo),COUNT(*) AS _krat
      FROM log LEFT JOIN _user ON id_user=kdo
      WHERE kdy > DATE_SUB(NOW(),INTERVAL $dnu DAY)
      GROUP BY tab,id_tab,kdo,DATE(kdy)
      ORDER BY kdy DESC
    ");
    while ( $cr && (list($kdo,$kdy,$jak,$tab,$id_tab,$username,$krat)
        = mysql_fetch_row($cr)) ) {
      $jak= $jak=='u' ? 'oprava' : ($jak=='i' ? 'vložení' : ($jak=='d' ? 'smazání' : '?'));
      $co= $tab=='c' ? 'článku' : 'akce';
      $txt= $tab=='c' ? log_show('xclanek',$id_tab) : "akce $id_tab";
      $krat= $krat==1 ? "" : " ($krat x)";
      $html.= "$kdy <b>$username</b> - $jak $co $txt $krat<br>";
//      $html.= "<dt>$kdy <b>$username</b></dt><dd>$jak $co $txt $krat</dd>";
    }
    $html.= "</dl>";
    break;
  case 'me_login': // -------------------------------------- přihlášení
    $html.= "<dl>";
    $cr= mysql_qry("
      SELECT day,time,msg
      FROM _touch
      WHERE module='log' AND menu='me_login'
      ORDER BY day DESC, time desc
    ");
    while ( $cr && (list($day,$time,$msg)= mysql_fetch_row($cr)) ) {
      list($ok,$mail,$ip,$os,$brow1,$brow2,$txt)= explode('|',$msg);
//      if ( $ok=='ko' && $par->typ=='ko' || $ok=='ok' && $par->typ=='ok' )
        $html.= "<dt>$day $time <b>$mail</b></dt><dd>$ok $ip $os $brow1 $brow2 <i>$txt</i></dd>";
    }
    $html.= "</dl>";
    break;
  }
  return $html;
}
# ----------------------------------------------------------------------------------------- log show
# zobrazí náhled článku
function log_show($tab,$id_tab) { 
  $html= "$id_tab";
  switch ($tab) {
  case 'xclanek':  
  $obsah= select("web_text","xclanek","id_xclanek=$id_tab");
  $obsah= str_replace('$index',$index,$obsah);
  // zobrazit jako abstrakt
  $obsah= x_shorting($obsah);
  $html= "
    <div class='back'>
      <a class='aclanek home'>
        $obsah
      </a>
    </div>";
    $html= "<a onclick=\"Ezer.fce.alert(`$html`)\">$id_tab</a>";
    break;
  }
  return $html;
}
# ---------------------------------------------------------------------------------------- log obsah
# zapíše informace o změně obsahu
# jak= i/u/d   tab=a/c   id=id_xclanek/id_xakce
function log_obsah($jak,$tab,$id_tab) { 
  global $USER;
  $kdo= $USER->id_user;
  $kdy= date('Y-m-d H:i:s');
  $menu= $ok=='r' ? 'login' : 'me_login';
  db_connect();
  $qry= "INSERT INTO log (kdo,kdy,jak,tab,id_tab)
         VALUES ('$kdo','$kdy','$jak','$tab','$id_tab')";
  $res= mysql_qry($qry);
  return 1;
}
# ---------------------------------------------------------------------------------------- log login
# zapíše informace o přihlášení
# ok= u pri uživatele, r pro redaktora, - pro chybu, x pro odhlášení redaktora
# id_user pro přihlášeného redaktora (zapisuje se v man.php)
function log_login($ok,$mail='') { 
  global $USER, $y;
  $day= date('Y-m-d');
  $time= date('H:i:s');
  $abbr= '';
  $ip= isset($_SERVER['HTTP_X_FORWARDED_FOR'])
      ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
  $browser= $_SERVER['HTTP_USER_AGENT'];
  $id_user= $_SESSION['web']['fe_user'];
  $abbr= $username= '';
  db_connect();
  if ( $id_user ) {
    list($abbr,$username)= select("abbr,username","_user","id_user='$id_user'");
    // uvolni všechny uzamčené záznamy, které jsi zamkl
    record_unlock('xclanek',0,true);
    record_unlock('xkniha',0,true);
  }
  if ( $ok=='x') {
    // odhlášení
    $menu= 'logout';
    $msg= "$username|";
  }
  elseif ( $ok=='r') {
    $menu= 'login';
    $msg= "$username||$ip|{$_SESSION['platform']}|{$_SESSION['browser']}|$browser";
  }
  else { // $ok= u|-
    $txt= str_replace("'","\\'",(isset($y->txt)?$y->txt:'?').(isset($y->msg)?$y->msg:'?'));
    $menu= 'me_login';
    $msg= $ok=='u'
        ? "ok|$mail|$ip|{$_SESSION['platform']}|{$_SESSION['browser']}|$browser" 
        : "ko|$mail|$ip||||$txt";
  }
  $qry= "INSERT INTO _touch (day,time,user,module,menu,msg)
         VALUES ('$day','$time','$abbr','log','$menu','$msg')";
  $res= mysql_qry($qry);
}
/** =========================================================================================> LOCKS */
# -------------------------------------------------------------------------------------- record lock
# pokud je rekord volný tj. table.locked=0 vrátí {kdo:''} a zapíše kdy kdo uzamkl
# pokud je rekord zamknutý tj. table.locked=id_user vrátí {kdo:_user.username,kdy:datetime}
function record_lock ($table,$id_table) {
  $ret= (object)array();
  list($kdo,$kdy)= select("lock_kdo,lock_kdy",$table,"id_$table=$id_table");
  if ( $kdo ) {
    // zamknutý záznam
    $ret->kdo= select("username","_user","id_user='$kdo'") ?: "???";
    $ret->kdy= sql_date($kdy);
  }
  else {
    // volný záznam - zmkni jej
    $ret->kdo= '';
    $id_user= $_SESSION['web']['fe_user'];
    query("UPDATE $table SET lock_kdo='$id_user',lock_kdy=NOW() WHERE id_$table=$id_table");
  }
  return $ret;
}
# ------------------------------------------------------------------------------------ record unlock
# uvolni rekord
# pokud je $unlock_all - uvolni všechny které jsi zamkl 
#   (děje se při přihlášení a odhlášení tedy i přo refresh)
function record_unlock ($table,$id_table,$unlock_all=false) {
  if ( $unlock_all ) {
    $id_user= $_SESSION['web']['fe_user'];
    query("UPDATE $table SET lock_kdo=0,lock_kdy=NOW() WHERE lock_kdo='$id_user'");
  }
  else {
    query("UPDATE $table SET lock_kdo=0,lock_kdy=NOW() WHERE id_$table=$id_table");
  }
  return 1;
}
/** ==========================================================================================> TEXT */
# -------------------------------------------------------------------------------------- x first_img
# vrátí první obrázek s doplněnými atributy, nebo ''
function x_first_img ($html,$size=1) { //trace();
  global $ezer_path_root, $FREE;
  $h= '';
  $is1= preg_match('/<img[^>]+>/i',$html, $m);
  if ( !$is1 ) goto video;
//                                                 debug($m,htmlentities($m[0]));
  $is2= preg_match('/src=(["\'][^"\']*["\'])/i',$m[0], $src);
  if ( !$is2 ) goto video;
//                                                 debug($src,1);
  // našli jsme a zjístíme, zda existuje
  $url= trim(str_replace("'",'"',$src[1])," '\"");
  // překlad na globální odkazy pro ty lokální (pro servant.php)
  $http= $FREE && preg_match("/^fileadmin/",$url) ? "https://www.setkani.org/" : '';
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
function x_shorting ($text,$n=500) { //trace();
  $img= '';
  $stext= xi_shorting ($text,$img,$n);
  if ( $img ) {
    $stext= $img ? "<div>$img$stext ...</div>" : "$stext ...";
  }
  return $stext;
}
function xi_shorting ($text,&$img,$n=300) { //trace();
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
  $img= x_first_img($text,8);
  $stext.= " &hellip;";
  return $stext;
}
function closetags($html) {
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
?>
