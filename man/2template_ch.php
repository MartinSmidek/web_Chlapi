<?php
define('VERZE',   '22/9/2018'); 
define('ZMENA', 3);     // je-li článek čerstvější => upozorni na změnu
define('NEWS', 264);    // článek obsahující změny na webu - zobrazuje se iniciovaným
define('NAVOD', 268);   // článek obsahující návod na přihlášení
# -------------------------------------------------------------------------------------==> def user
// obnovuje obsah základních proměnných, které řídí viditelnost obsahu 
function def_user() { 
  global $REDAKCE, $KLIENT, $mobile;
  $KLIENT= (object)array(
      // osoba.id_osoba z Answeru
      'id'    => isset($_SESSION['web']['user'])  ? 0+$_SESSION['web']['user'] : 0,  
      // 0-kdokoliv, 1-iniciovaný, 3-účastník firmingu
      'level' => isset($_SESSION['web']['level']) ? 0+$_SESSION['web']['level'] : 0  
  );
  $REDAKCE= isset($_SESSION['man']) ? (object)array(
      // 0-kdokoliv, 1-programátor, 2-testér, 4-redaktor
      'level' => isset($_SESSION['man']['level']) ? 0+$_SESSION['man']['level'] : 0  
  ) : NULL; 
  $mobile= in_array($_SESSION['platform'],array('I','M','A'));
}
# --------------------------------------------------------------------------------==> get fileadmin
# vrátí fileadmin pro web setkani
function get_fileadmin() {
  global $ezer_server;
  $fileadmin= array(
      "http://setkani.bean:8080/fileadmin",
      "https://www.setkani.org/fileadmin",
      "https://web.setkani.org/fileadmin",
      "http://setkani4.doma/fileadmin",
      "https://www.setkani.org/fileadmin" // ben
    )[$ezer_server];
  return $fileadmin;
}
# -----------------------------------------------------------------------------------==> get prefix
# vrátí prefix
function get_prefix() {
  global $ezer_server;
  $prefix= array(
      "http://chlapi.bean:8080/",
      "http://chlapi.online/",
      "http://chlapi.cz/",
      "http://chlapi.doma/",
      "http://chlapi.ben:8080/"   // ben
    )[$ezer_server];
  return $prefix;
}
# -------------------------------------------------------------------------------------==> page
// jen pro CMS mod: vrací objekt se stránkou
function page($a,$b) { 
  global $amenu, $cmenu;
  $page= '';
  def_user();
  read_menu();
  $path= explode('!',$b);
  $elem= eval_menu($path);
//                                                  debug($amenu,"amenu");
  $cmenu= array(); // kontextové menu definované v elems
  $html= eval_elem($elem);
  $page= show_page($html);
  return (object)array('html'=>$page);
}
# -------------------------------------------------------------------------------------==> read_menu
// načte některé záznamy z tabulky MENU do pole $menu - výběr je ovlivněn obsahem REDAKCE a KLIENT
// REDAKCE=null
//   1) vynechají se záznamy s nenulovým menu.redakce
//   2) vynechají se záznamy menu.klient
// REDAKCE={skills}
//   1) pokud _user.skill neobsahuje 'm' vynechají se záznamy z menu.redakce=1 (programátor) 
//   2) pokud _user.skill neobsahuje 't' vynechají se záznamy z menu.redakce=2 (tester)
// přidá položku has_subs pokud má hlavní menu submenu
function read_menu() { 
  global $menu, $REDAKCE, $KLIENT;
  // připoj databázi
  db_connect();
  // načtení menu
  $menu= array();
  $amenu= (object)array('top'=>array(),'main'=>array(),'sub'=>array());
  $mn= pdo_qry("
    SELECT nazev,elem,mid,mid_top,mid_sub,ref,typ,level,redakce,klient,
      TO_DAYS(NOW())-IFNULL(TO_DAYS(ch_date),0) AS _zmena
    FROM menu WHERE wid=2 ORDER BY typ,rank");
  while ($mn && ($m= pdo_fetch_object($mn))) {
    // má se upozornit na změnu?
    $m->_zmena= $m->_zmena < ZMENA ? 1 : 0;
    // redakční klientům nezobrazovat
    if ( !$REDAKCE && $m->redakce ) continue;  
    // redakční menu filtrovat (m=1 programátor, t=2 testér)
    if ( $REDAKCE && $m->redakce && !($m->redakce & $REDAKCE->level)) continue;
    //  kladné menu.klient => nezobrazit když KLIENT.level nemá ten bit
    if ( $m->klient>0 && !($KLIENT->level & $m->klient) ) continue;    
    //  záporné menu.klient => nezobrazit když KLIENT.level má ten bit
    if ( $m->klient<0 && $KLIENT->level && ($KLIENT->level & -$m->klient) ) continue; 
    // co se nechalo zapiš
    $menu[$m->mid]= $m;
    if ( $m->typ==2 && isset($menu[$m->mid_top]) ) 
      $menu[$m->mid_top]->has_subs= true;
  }
}
# -------------------------------------------------------------------------------------==> eval menu
# path = [ mid, ...]
function eval_menu($path) { 
  global $REDAKCE, $currpage, $tm_active, $ezer_server;
  global  $menu, $amenu, $submenu_shift, $elem, $curr_menu, $backref, $top;
  global $prefix, $href, $input;
  $prefix= get_prefix();
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
    // zvýraznění redakčních menu
    if ( $m->redakce )
      $level.= " redakce$m->redakce";
    $href= $m->ref;
    $jmp= $REDAKCE 
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
        $backref= $REDAKCE 
          ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
          : "href='{$prefix}$href!*'";
        $top= array_shift($path);
      }
      $amenu->top[]= $m->nazev ? array($m->nazev,"jump$level$active",$jmp,$m->_zmena) : '-';
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
        $backref= $REDAKCE 
          ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
          : "href='{$prefix}$href!*'";
        $top= array_shift($path);
      }
      $amenu->main[]= $m->nazev ? array($m->nazev,"jump$level$active",$jmp,$m->_zmena) : '-';
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
          $backref= $REDAKCE 
            ? "onclick=\"go(arguments[0],'page=$href!*','{$prefix}$href!*','$input',0);\""
            : "href='{$prefix}$href!*'";
          $top= array_shift($path);
        }
        $jmp= $REDAKCE 
          ? "onclick=\"go(arguments[0],'page=$href','{$prefix}$href','$input',0);\""
          : "href='{$prefix}$href'";
        $amenu->sub[]= $m->nazev ? array($m->nazev,"jump$level$active",$jmp,$m->_zmena) : '-';
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
# -------------------------------------------------------------------------------------==> show menu
# zobrazení menu
function show_menu($part) {
  global $amenu;
  $html= '';
  if ( isset($amenu->$part) ) {
    foreach($amenu->$part as $m) {
      $class= $m[1].($m[3] ? " upd" : '');
      $html.= "<a $m[2] class='$class'><span>$m[0]</span></a>";
    }
  }
  return $html;
}
# -------------------------------------------------------------------------------------==> test elem
# vynechá neexistující článek tj. když $exists=false a v redakčním modu napíše varování do $html
function test_elem($exists,$elem_id,$elem,&$html) {
  global $REDAKCE;
  if ( !$exists ) {
    if ( $REDAKCE ) {
      $html.= "
        <div class='back'>
          <a class='home' style='outline:3px solid red;text-align:center'>
            <b>$elem_id</b> neexistuje - ale v menu je <b>$elem</b>
          </a>
        </div>";
    }
  }
  return $exists;
}
# ------------------------------------------------------------------------------------==> title menu
# vygeneruje title=... oncontextmenu=... podle dodaných parametrů, kde
# title= obsah title
# items= pole zkratek 0123 kde 0=|- 
#   1: p|e|x|m|z		=přidat|editovat|eXclude|move|zobraz(abstrakt nebo clanek) 
#   2: a|c|k|s|o|f|t|i	=akce|článek|kniha|sekce|obrázky|fotky|time-kalendář|invitation-pozvánka
#   3: n|d				=nahoru|dolů  
# id,kid,mid jsou id a x je další parametr
# pokud je definováno pole cmenu (elementem menu) přidá se na začátek
function title_menu($title,$items,$id=0,$idk=0,$idm=0) {
  global $cmenu;
  $cm= array();
  // přidej na začátek menu definované elementem menu
  if ( count($cmenu) ) {
    $items= implode(';',$cmenu).";$items";
  }
  $items= explode(';',$items);
  foreach ($items as $item) {
    $c= '';
    if ( '-'==substr($item,0,1) ) { // řádek před item
      $c= '-';
      $item= substr($item,1);
    }
    // případné parametry itemu budou číslovány od 1
    $x= explode(',',$item);
    $item= $x[0];
    switch ($item) {
    // e - editace
    case 'ec':  $cm[]= "['{$c}editovat článek',function(el){ opravit('xclanek',$id); }]"; break;
    case 'ef':  $cm[]= "['{$c}organizovat fotky',function(el){ opravit('xfotky',$x[1]);}]"; break;
    case 'ek':  $cm[]= "['{$c}upravit název',function(el){ opravit('xkniha',$idk);}]"; break;
    case 'eo':  $cm[]= "['{$c}upravit obrázky článku',function(el){ namiru('$id','$x[1]');}]"; break;
    case 'ea':  $cm[]= "['{$c}editovat akci',function(el){ opravit('xakce',$id,$x[1]);}]"; break;
    case 'et':  $cm[]= "['{$c}editovat kalendář',function(el){ opravit('kalendar',$x[1]);}]"; break;
    case 'eu':  $cm[]= "['{$c}editovat tabulku účastí',function(el){ opravit('xucasti',$id);}]"; break;
    // p - přidání
    case 'pcn': $cm[]= "['{$c}přidat článek na začátek',function(el){ pridat('xclanek',$idm,1);}]"; break;
    case 'pcd': $cm[]= "['{$c}přidat článek na konec',function(el){ pridat('xclanek',$idm,0);}]"; break;
    case 'pkn': $cm[]= "['{$c}přidat knihu na začátek',function(el){ pridat('xkniha',$idm,1);}]"; break;
    case 'pkd': $cm[]= "['{$c}přidat knihu na konec',function(el){ pridat('xkniha',$idm,0);}]"; break;
    case 'psn': $cm[]= "['{$c}přidat kapitolu na konec',function(el){ pridat('xkniha.elem',$idk,0);}]"; break;
    case 'psd': $cm[]= "['{$c}přidat kapitolu na začátek',function(el){ pridat('xkniha.elem',$idk,1);}]"; break;
    case 'pf':  $cm[]= "['{$c}přidat fotky',function(el){ pridat('xfotky',$id);}]"; break;
    case 'pa':  $cm[]= "['{$c}přidat novou akci $idk',function(el){ pridat('xakce',$idk,1);}]"; break;
    case 'pi':  $cm[]= "['{$c}přidat pozvánku',function(el){ pridat('pozvanka',$x[1]);}]"; break;
    case 'pu':  $cm[]= "['{$c}přidat tabulku účastí',function(el){ pridat('xucasti',$id);}]"; break;
    // m - posunutí
    case 'msd': $cm[]= "['{$c}posunout dolů',function(el){ posunout('xkniha.elem',$idk,$id,1);}]"; break;
    case 'msn': $cm[]= "['{$c}posunout nahoru',function(el){ posunout('xkniha.elem',$idk,$id,0);}]"; break;
    case 'mkd': $cm[]= "['{$c}posunout knihu dolů',function(el){ posunout('akniha',$idm,$idk,1);}]"; break;
    case 'mkn': $cm[]= "['{$c}posunout knihu nahoru',function(el){ posunout('akniha',$idm,$idk,0);}]"; break;
    case 'mcd': $cm[]= "['{$c}posunout článek dolů',function(el){ posunout('aclanek',$idm,$id,1);}]"; break;
    case 'mcn': $cm[]= "['{$c}posunout článek nahoru',function(el){ posunout('aclanek',$idm,$id,0);}]"; break;
    // x - rušení
    case 'xo':  $cm[]= "['{$c}vyjmout embeded obrázky',function(el){ bez_embeded('$id');}]"; break;
    // z - zobrazit
    case 'za':  $cm[]= "['{$c}zobrazit jako abstrakt',function(el){ zmenit($idm,'xclanek',$id,'aclanek');}]"; break;
    case 'zc':  $cm[]= "['{$c}zobrazit jako článek',function(el){ zmenit($idm,'aclanek',$id,'xclanek');}],"; break;
    default: fce_error("'$item' není menu");
    }
  }
  $on= " title='$title' oncontextmenu=\"Ezer.fce.contextmenu([\n"
      .implode(",\n",$cm)
      ."],arguments[0],0,0,'#xclanek$id');return false;\"";
  return $on;
}
# -------------------------------------------------------------------------------------==> eval elem
// desc :: key [ = ids ]
// ids  :: id1 [ / id2 ] , ...    -- id2 je klíč v lokální db pro ladění
function eval_elem($desc,$book=null) {
  global $REDAKCE, $KLIENT, $ezer_server, $http_server, $index, $load_ezer, $curr_menu, $top, 
      $prefix, $mobile, $cmenu, $backref;
  $elems= explode(';',$desc);
  $ipad= '';
  $html= '';
  $html= $REDAKCE ? "<script>skup_mapka_off();</script>" : '';
  $layout= ''; // default layout stránky 
  foreach ($elems as $elem) {
    list($typ,$ids)= explode('=',$elem.'=');
    // přemapování ids podle server/localhost
    $id= null;
    if ( $ids ) {
      $id= array();
      foreach (explode(',',$ids) as $id12) {
        list($id_server,$id_local)= explode('/',$id12);
        $id[]= $id_local ? (!$ezer_server ? $id_local : $id_server) : $id_server; 
      }
      $id= implode(',',$id);
    }
    $typ= str_replace(' ','',$typ);
                                                      display("$typ $id");
    switch ($typ) {

    case 'menu':    # ----------------------------------------------- . menu
      // přídavné itemy menu ve formátu item!item!... item=kod,x1,x2,...
      $cmenu= explode('!',$id);
      break; 
    
    case 'fb':      # ----------------------------------------------- . fb
      $fb_site= "fortnahradcany";
      $fb_name= "Fortna";
      $fb_note= $REDAKCE ? "<i>V redakčním režimu se FB ukáže jen poprvé po ctrl-r či F5</i>" : '';
      $fb= <<<__EOT
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/cs_CZ/sdk.js#xfbml=1&version=v6.0"></script>
        <div id='fb-root'></div>
        <div class="fb-page" data-href="https://www.facebook.com/$fb_site/" 
            data-tabs="timeline" data-width="500" data-height="500" data-small-header="false" 
            data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false">
          <blockquote cite="https://www.facebook.com/$fb_site/" class="fb-xfbml-parse-ignore">
            <a href="https://www.facebook.com/$fb_site/">$fb_name</a>
          </blockquote>
        </div>
__EOT;
      $html.= "
        <div class='back'>
          <div id='xclanek$id' class='home' style='text-align:center'>
            $fb
            $fb_note
          </div>
        </div>
      ";
      break; 
    
    case 'verze':   # ----------------------------------------------- . verze
      $v= VERZE;
      $html.= <<<__EOT
        <script>alert("verze CMS je $v");</script>
__EOT;
      break; 
    
    case 'layout':  # ----------------------------------------------- . layout
      $layout= $id;
      break; 
    
    case 'pozvanky':  # --------------------------------------------- . pozvánky skupiny $id
      // seznam aktuálních pozvánek
      $ra= pdo_qry("SELECT id_xakce,xelems,datum_od,datum_do,nazev FROM xakce 
          WHERE datum_od>=DATE(NOW()) AND xelems!='' AND skupina='$id'
          ORDER BY datum_od ASC");
      while ($ra && list($a,$elems,$datum_od,$datum_do,$nazev)= pdo_fetch_row($ra) ) {
        // abstrakty akcí
        if ( $elems ) {
          $first= true;
          $oddo= datum_oddo($datum_od,$datum_do);
          $tit= "$oddo $nazev";
          $header= "<h1>$oddo $nazev</h1><hr>";
          foreach ( explode(';',$elems) as $elem ) {
            $html.= eval_elem($elem,(object)array('subtyp'=>'pozvanka',
                'open'=>true,'idk'=>'','ida'=>$a,'tit'=>$tit,'header'=>$header,
                'first'=>$first));
            $first= false;
          }
        }
      }
      break;

    case 'archiv':  # ----------------------------------------------- . archiv akcí skupiny $id
      list($rok,$ida)= explode(',',$top);
      // projdi relevantní roky
      $html.= "<div id='roky'>";
      $rs= pdo_qry("SELECT YEAR(datum_do),COUNT(*) FROM xakce 
          WHERE datum_od<DATE(NOW()) AND xelems!='' AND skupina='$id'
          GROUP BY YEAR(datum_od) 
          ORDER BY datum_od DESC");
      while ($rs && list($r,$pocet)= pdo_fetch_row($rs) ) {
        $html.= "<br id='rok$r'>";
        $akce= kolik_1_2_5($pocet,"akce,akcí,akcí");
        $zacatek= "Archiv $akce z roku $r";
        if ( $rok==$r ) {
          // otevřený rok
          $html.= "<div id='fokus_page' class='kniha_bg'>
                     <div class='kniha_br'><b>$zacatek ...</b></div>
                     <div id='list'>";
          // seznam akcí
          $ra= pdo_qry("SELECT id_xakce,xelems,skupina,datum_od,datum_do,nazev FROM xakce 
              WHERE datum_od<DATE(NOW()) AND YEAR(datum_od)=$r AND xelems!='' AND skupina='$id'
              ORDER BY datum_od DESC");
          while ($ra && list($a,$elems,$skupina,$od,$do,$nazev)= pdo_fetch_row($ra) ) {
            // abstrakty akcí
            $top= $ida;
            if ( $elems ) {
              $first= true;
              foreach ( explode(';',$elems) as $elem ) {
                $par= (object)array('subtyp'=>'akce','open'=>true,'idk'=>$rok,'ida'=>$a,'first'=>$first);
                if ( $skupina ) {
                  $oddo= datum_oddo($od,$do);
                  $par->tit= "$oddo $nazev";
                  $par->header= "<h1>$oddo $nazev</h1><hr>";
                }
                $html.= eval_elem($elem,$par);
                $first= false;
              }
            }
          }
          // konec roku
          $konec= "... konec archivu roku $r";
          $html.=   "</div>
                     <div class='kniha_br'><b>$konec</b></div>
                   </div>";
        }
        else {
          // zavřený rok
          $jmp= str_replace('*',$r,$backref);
          $html.= "<div class='kniha_bg'><a class='jump' $jmp>$zacatek</a></div>";
        }
      }
      $html.= "</div>";
      break;

    case 'akniha':  # ----------------------------------------------- . kniha
    case 'xkniha':  
      global $backref;
      list($idk,$ida)= explode(',',$top);
      list($exists,$nazev,$xelems,$wskill)= 
          select("id_xkniha,nazev,xelems,web_skill","xkniha","id_xkniha=$id");
      if ( !test_elem($exists,"Kniha $id",$elem,$html) ) continue;
      $wskill= 0+$wskill;
      $otevrena= $top && $idk==$id && (!$wskill || $KLIENT->level & $wskill);
      $menu= '';
      if ( $otevrena ) {
        // otevřená kniha
        if ( $REDAKCE ) {
//          $kod= "Ezer.fce.contextmenu([
//                ['upravit název',function(el){ opravit('xkniha',$idk); }],
//                ['-nová kapitola na začátek',function(el){ pridat('xkniha.elem',$idk,1); }],
//                ['nová kapitola na konec',function(el){ pridat('xkniha.elem',$idk,0); }]
//              ],arguments[0],0,0,'#xclanek$id');return false;\"";
//          $menu= " title='kniha $idk' oncontextmenu=\"$kod\"";
          $menu= title_menu("kniha $idk","ek;-psn;psd",0,$idk);
          if ( $mobile ) {
            $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
              <i class='fa fa-bars'></i></span>";
          }
        }
        // nadpis
        $html.= "
          <div class='kniha_bg' id='fokus_page'>
            <div class='kniha_br' $menu>
              <b>$nazev</b>
              $ipad
            </div>";
        // kapitoly
        $top= $ida;
        if ( $elems ) {
          $html.= eval_elem($xelems,(object)array('subtyp'=>'kniha','open'=>true,'idk'=>$id));
        }
        // konec 
        $html.= "
            <div class='kniha_br'>
              Konec knihy <b>$nazev</b>
            </div>
          </div>";
      }
      else {
        // zavřená kniha - zobrazení 1. kapitoly - musí to být aclanek
        list($xelem)= explode(';',$xelems);
        if ( $xelem ) {
          $html.= "<div title='kniha $id' $menu>";
          $html.= eval_elem($xelem,(object)array('subtyp'=>'kniha',
              'open'=>false,'idk'=>$id/*,'tit'=>$nazev*/,'skill'=>$wskill));
          $html.= "</div>";
        }
      }
      break;

    case 'note':    # ----------------------------------------------- . note
      $html.= "<div style='background:white;color:black;text-align:center'>POZNAMKA</div>";
      break;
    
    case 'myslenka':# ----------------------------------------------- . myšlenka
      global $backref;
      $obsah= rr_myslenka();
      $obsah.= '<p><i>Pokud chceš tyto denní meditace Richarda Rohra z knihy "Radikální milost" '
          . 'dostávat do své mailové schránky, napiš na iv.hudec(et)gmail.com </i></p>';
      $plny= $top==$id;
      if ( $plny ) {
        // zobrazit jako plný článek
        $html.= "
          <div class='back' $menu>
            <div id='fokus_part' class='home'>
              $obsah
            </div>
          </div>";
      }
      else {
        // zobrazit jako abstrakt
        $obsah= x_shorting($obsah);
        $styl= 'aclanek';
        $jmp= str_replace('*',$id,$backref);
        $html.= "
          <div class='back'>
            <a class='$styl home' $jmp>
              <img src='/man/img/rr_gr.jpg' style='width:80px;margin-right:40px;float:right;'>
              $obsah
            </a>
          </div>";
      }
      break;
    
    case 'aclanek': # ------------------------------------------------ . ačlánek - abstrakt
      global $backref, $links;
      $links= "fotorama";
      $html.= "<script>jQuery('.fotorama').fotorama();</script>";
      $idn= $id;
      list($exists,$obsah,$wskill,$cskill,$zmena)= 
          select("id_xclanek,web_text,web_skill,cms_skill,TO_DAYS(NOW())-IFNULL(TO_DAYS(ch_date),0)",
              "xclanek","id_xclanek=$id");
      if ( !test_elem($exists,"Článek $id",$elem,$html) ) continue;
      $wskill= 0+$wskill;
      $cskill= 0+$cskill;
      // má se upozornit na změnu?
      $zmena= $zmena < ZMENA ? ' zmena' : '';
      // dědění přístupnosti kapitoly knihy
      if ( $book ) {
        $idn= $book->open ? ($book->idk ? "$book->idk,$id" : $id) : $book->idk;
        $wskill= $book->skill ? $book->skill | $wskill : $wskill; 
      }
      // viditelnost redakčních specialit
      $redakce_style= '';
      if ( $cskill ) {
        if ( $REDAKCE && $REDAKCE->level & $cskill ) 
          $redakce_style= " redakce$cskill";
        else 
          break;
      }
      $plny= $top==$id && (!$wskill || $KLIENT->level & $wskill);
      $co= $plny ? 'článek' : 'abstrakt';
      // generování obsahu
      $obsah= str_replace('$index',$index,$obsah);
      $obsah= x_cenzura($obsah);
      $menu= $note= '';
      if ( $REDAKCE ) {
        $obsah= preg_replace("~href=\"(?:$http_server/|/|(?!https?://))(.*)\"~U", 
              "onclick=\"go(arguments[0],'page=$1','$prefix$1','',0);\" title='$1'", 
              $obsah);
        if ( !$book  ) {
          $div_id= "c$id";
//          $namiru= $plny ? "['upravit obrázky článku',function(el){ namiru('$id','$div_id'); }],":'';
//          $namiru= $plny ? 
//                "['upravit obrázky článku',function(el){ namiru('$id','$div_id'); }],
//                 ['vyjmout embeded obrázky',function(el){ bez_embeded('$id'); }],":'';
//          $kod= "Ezer.fce.contextmenu([
//                ['editovat článek',function(el){ opravit('xclanek',$id); }],
//                $namiru
//                ['-zobrazit jako článek',function(el){ zmenit($curr_menu->mid,'aclanek',$id,'xclanek'); }],
//                ['-přidat fotky',function(el){ pridat('xfotky',$id); }],
//                ['-posunout článek nahoru',function(el){ posunout('aclanek',$curr_menu->mid,$id,0); }],
//                ['posunout článek dolů',function(el){ posunout('aclanek',$curr_menu->mid,$id,1); }],
//                ['-přidat článek na začátek',function(el){ pridat('xclanek',$curr_menu->mid,1); }],
//                ['přidat článek na konec',function(el){ pridat('xclanek',$curr_menu->mid,0); }],
//                ['-přidat knihu na začátek',function(el){ pridat('xkniha',$curr_menu->mid,1); }],
//                ['přidat knihu na konec',function(el){ pridat('xkniha',$curr_menu->mid,0); }]
//              ],arguments[0],0,0,'#xclanek$id');return false;";
//          $menu= " title='$co $idn' oncontextmenu=\"$kod\"";
          $namiru= $plny ? "eo;xo;" : '';
          $menu= title_menu("$co $idn","ec;{$namiru}-zc;-pf;-mcn;mcd;-pcn;pcd;-pkn;pkd",$id,0,$curr_menu->mid);
          if ( $mobile ) {
            $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
              <i class='fa fa-bars'></i></span>";
          }
          $menu.= " id='$div_id'";
        }
        elseif ( $book->open && $book->ida ) {
            $div_id= "a{$book->ida}-$id";
//            $namiru= $plny ? "['upravit obrázky článku',function(el){ namiru('$id','$div_id'); }],":'';
//            $namiru= $plny ? 
//                "['upravit obrázky článku',function(el){ namiru('$id','$div_id'); }],
//                 ['vyjmout embeded obrázky',function(el){ bez_embeded('$id'); }],":'';
//            $pridat_akci= $book->idk 
//                ? ",['-přidat novou akci $book->idk',function(el){ pridat('xakce',$book->idk,1); }]" : '';
//            $kod= "Ezer.fce.contextmenu([
//                ['editovat akci',function(el){ opravit('xakce',$id,$book->ida); }],
//                $namiru
//                ['-přidat fotky',function(el){ pridat('xfotky',$id); }]
//                $pridat_akci
//              ],arguments[0],0,0,'#xclanek$id');return false;\"";
//            $menu= " title='".($plny?'':'abstrakt ')."akce $book->idk: $book->ida/$id' 
//              oncontextmenu=\"$kod\" id='$div_id'";
            $namiru= $plny ? "eo;xo;" : '';
            $pridat_akci= $book->idk ? ";pa" 
                : (select('COUNT(*)','xucast',"id_xclanek=$id") ? ';eu' : ';pu');
            $title= ($plny?'':'abstrakt ')."akce $book->idk: $book->ida/$id";
            $menu= title_menu($title,"ea,{$book->ida};{$namiru}-pf{$pridat_akci}",$id,$book->idk,0).
                 " id='$div_id'";
            if ( $mobile ) {
              $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
                <i class='fa fa-bars'></i></span>";
            }
        }
        else {
//            $kod= "Ezer.fce.contextmenu([
//                ['editovat článek',function(el){ opravit('xclanek',$id); }],
//                ['upravit obrázky článku',function(el){ namiru('$id','fokus_part'); }],
//                ['vyjmout embeded obrázky',function(el){ bez_embeded('$id'); }],
//                ['-zobrazit jako článek',function(el){ zmenit($curr_menu->mid,'aclanek',$id,'xclanek'); }],
//                ['-přidat fotky',function(el){ pridat('xfotky',$id); }],
//                ['-posunout nahoru',function(el){ posunout('xkniha.elem',$book->idk,$id,0); }],
//                ['posunout dolů',function(el){ posunout('xkniha.elem',$book->idk,$id,1); }],
//                ['-posunout knihu nahoru',function(el){ posunout('akniha',$curr_menu->mid,$book->idk,0); }],
//                ['posunout knihu dolů',function(el){ posunout('akniha',$curr_menu->mid,$book->idk,1); }],
//                ['-nová kapitola na začátek',function(el){ pridat('xkniha.elem',$book->idk,1); }],
//                ['nová kapitola na konec',function(el){ pridat('xkniha.elem',$book->idk,0); }]
//              ],arguments[0],0,0,'#xclanek$id');return false;\"";
//            $menu= " title='".($plny?'kapitola':'abstrakt kapitoly')." $book->ida/$idn' oncontextmenu=\"$kod\"";
            $menu= title_menu(($plny?'kapitola ':'abstrakt kapitoly ').$book->ida/$idn,
                "ec;eo,fokus_part;xo;zc;-pf;-msn;msd;-mkn;mkd;-psn;psd",$id,$book->idk,$curr_menu->mid);
            if ( $mobile ) {
              $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
                <i class='fa fa-bars'></i></span>";
            }
        }
      }
      $jmp= str_replace('*',$idn,$backref);
      if ( $plny ) {
        // zobrazit jako plný článek
        $html.= "
          <div class='back' $menu>
            <div id='fokus_part' class='home$redakce_style$zmena'>
              $book->header
              $ipad
              $obsah
            </div>
          </div>";
        // pokud je tabulka účasti, přidáme
        if ( $book->ida ) {
          // pokud je to stará tabulka, zobrazí se jako abstrakt, pokud $currpage!=...,1
          // první znak $t je A pro abstrakt C pro článek
          $t= table_show($book->ida,$id);
          if ( $t ) {
            $a_c= $t[0];
            $t= substr($t,1);
            $jmp_t= $jmp= str_replace('*',"$idn,1",$backref);;
            $html.= $a_c=='A' 
            ? "
              <div class='back'>
                <a class='aclanek home$redakce_style' $jmp_t>
                  $t
                </a>
              </div>"
            : "
              <div class='back' $menu>
                <div id='fokus_part' class='home$redakce_style$zmena'>
                  $t
                </div>
              </div>";
          }
        }
        // pokud jsou fotky, přidáme
        $rf= pdo_qry("SELECT id_xfotky,nazev,seznam,path,autor FROM xfotky WHERE id_xclanek=$id");
        while ($rf && list($fid,$nazev,$seznam,$path,$podpis)=pdo_fetch_row($rf)) {
          if ( $REDAKCE ) {
            $note= "<span style='float:right;color:red;font-style:italic;font-size:x-small'>
                  ... zjednodušené zobrazení fotogalerie pro editaci</span>";
//            $kod= "Ezer.fce.contextmenu([
//                  ['organizovat fotky',function(el){ opravit('xfotky',$fid); }],
//                  //['kopírovat ze setkání',function(el){ zcizit('fotky',$fid,0); }],
//                  //['... jen test',function(el){ zcizit('fotky',$fid,1); }]
//                ],arguments[0],0,0,'#xclanek$id');return false;\"";
//            $menu= " title='fotky $fid' oncontextmenu=\"$kod\"";
            $menu= title_menu("fotky $fid","ef,$fid");
            if ( $mobile ) {
              $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
                <i class='fa fa-bars'></i></span>";
            }
          }
          $galery= show_fotky2($fid,$seznam);
          $html.= "
            <div class='galery_obal' $menu>
              <div id='xfotky$fid' class='galerie'>
                <div class='text'>
                  $ipad
                  <h1>&nbsp;&nbsp;&nbsp;$nazev $note</h1>
                  $galery
                  <div class='podpis'>$podpis</div>
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
        if ( $wskill && !($KLIENT->level & $wskill) ) {
          $jmp= '';
          $neodkaz= "onclick=\"jQuery('div.neodkaz').fadeIn();\"";
          $styl= 'aclanek_nojump';
          // varianta pro zobrazení jen prvního abstraktu knížky nebo akce
          if ( $book && !$book->first )
            break;
        }
        $zmena= $zmena ? "<img src='/man/css/upd.gif' class='zmena'>" : '';
        $html.= "
          <div class='back' $menu $neodkaz>
            <a class='$styl home$redakce_style' $jmp>
              $zmena
              $ipad
              $obsah
            </a>
          </div>";
      }
      break;

    case 'xclanek': # ------------------------------------------------ . xčlánek
      list($exists,$obsah,$wskill,$cskill,$zmena)= 
          select("id_xclanek,web_text,web_skill,cms_skill,TO_DAYS(NOW())-IFNULL(TO_DAYS(ch_date),0)",
              "xclanek","id_xclanek=$id");
      if ( !test_elem($exists,"Článek $id",$elem,$html) ) continue;
      if ( $wskill && !($KLIENT->level & $wskill) ) {
        break;
      }
      // má se upozornit na změnu?
      $zmena= $zmena < ZMENA ? ' zmena' : '';
      // viditelnost redakčních specialit
      $redakce_style= '';
      if ( $cskill ) {
        if ( $REDAKCE && $REDAKCE->level & $cskill ) 
          $redakce_style= " redakce$cskill";
        else 
          break;
      }
      $obsah= x_cenzura($obsah);
      $obsah= str_replace('$index',$index,$obsah);
      $menu= '';
      if ( $REDAKCE ) {
        $obsah= preg_replace("~href=\"(?:$http_server/|/|(?!https?://))(.*)\"~U", 
//              "onclick=\"go(arguments[0],'page=$1','$prefix$1','',0);\" title='$1'", 
              "onclick=\"go(arguments[0],'page=$1','$1','',0);\" title='$1'", 
              $obsah);
        $div_id= "c$id";
//        $kod= "Ezer.fce.contextmenu([$cmenu
//              ['editovat článek',function(el){ opravit('xclanek',$id); }],
//              ['upravit obrázky článku',function(el){ namiru('$id','$div_id'); }],
//              ['vyjmout embeded obrázky',function(el){ bez_embeded('$id'); }],
//              ['-zobrazit jako abstrakt',function(el){ zmenit($curr_menu->mid,'xclanek',$id,'aclanek'); }],
//              ['-přidat článek na začátek',function(el){ pridat('xclanek',$curr_menu->mid,1); }],
//              ['přidat článek na konec',function(el){ pridat('xclanek',$curr_menu->mid,0); }],
//              ['-přidat knihu na začátek',function(el){ pridat('xkniha',$curr_menu->mid,1); }],
//              ['přidat knihu na konec',function(el){ pridat('xkniha',$curr_menu->mid,0); }]
//            ],arguments[0],0,0,'#xclanek$id');return false;\"";
//        $menu= " title='článek $id' oncontextmenu=\"$kod\"";
        $menu= title_menu("článek $id","ec;eo,$div_id;xo;-za;-pcn;pcd;-pkn;pkd",$id,0,$curr_menu->mid);
        if ( $mobile ) {
          $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
            <i class='fa fa-bars'></i></span>";
        }
      }
      $html.= "
        <div class='back' $menu>
          <div id='xclanek$id' class='home$redakce_style$zmena'>
            $ipad
            $obsah
          </div>
        </div>
      ";
//        // pokud jsou fotky, přidáme
//        $rf= pdo_qry("SELECT id_xfotky,nazev,seznam,path,autor FROM xfotky WHERE id_xclanek=$id");
//        while ($rf && list($fid,$nazev,$seznam,$path,$podpis)=pdo_fetch_row($rf)) {
//          if ( $REDAKCE ) {
//            $note= "<span style='float:right;color:red;font-style:italic;font-size:x-small'>
//                  ... zjednodušené zobrazení fotogalerie pro editaci</span>";
//            $menu= " title='fotky $fid' oncontextmenu=\"
//                Ezer.fce.contextmenu([
//                  ['organizovat fotky',function(el){ opravit('xfotky',$fid); }],
//                  ['kopírovat ze setkání',function(el){ zcizit('fotky',$fid,0); }],
//                  ['... jen test',function(el){ zcizit('fotky',$fid,1); }]
//                ],arguments[0],0,0,'#xclanek$id');return false;\"";
//          }
//          $galery= show_fotky2($fid,$seznam);
//          $html.= "
//            <div class='galery_obal' $menu>
//              <div id='xfotky$fid' class='galerie'>
//                <div class='text'>
//                  <h1>&nbsp;&nbsp;&nbsp;$nazev $note</h1>
//                  $galery
//                  <div class='podpis'>$podpis</div>
//                </div>
//              </div>
//            </div>
//          ";
//        }
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
      $ra= pdo_query($qa);
      while ( $ra && list($id,$od,$do,$nazev,$misto,$text)=pdo_fetch_array($ra)) {
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
      if ( $REDAKCE ) {
//        $kod= "Ezer.fce.contextmenu([
//              ['editovat kalendář',function(el){ opravit('kalendar',$edit_id); }]
//            ],arguments[0],0,0,'#xclanek$id');return false;\"";
//        $menu= " oncontextmenu=\"$kod\"";
        $menu= title_menu('kalendář',"et,$edit_id");
        if ( $mobile ) {
          $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
            <i class='fa fa-bars'></i></span>";
        }
      }
      // zformátujeme kalendář
      $html.= "<div class='back' $menu><div id='clanek2' class='home'>$ipad<table class='kalendar'>";
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
      global $REDAKCE;
      $fname= "pdf/$id.html";
      if ( file_exists($fname) ) {
        $doc= file_get_contents($fname);
        $html.= $doc;
      }
      break;

    case 'mapa':    # ------------------------------------------------ . mapa
      global $REDAKCE;
      $load_ezer= true;
      $html.= !$REDAKCE ? '' : <<<__EOT
        <script>skup_mapka();</script>
__EOT;
      break;

    case 'skupiny': # ------------------------------------------------ . skupiny
      $load_ezer= true;
      $tabulku= $REDAKCE
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
/*
//    // clanky=vzor získání abstraktů akcí podle roků a vzoru  {tx_gncase.chlapi RLIKE vzor}
//    case 'akce':      # ------------------------------------------------ . akce
//      global $y, $backref, $top, $links;
//      $links= "fotorama";
//      $ys_html= "<script>jQuery('.fotorama').fotorama();</script>";
//      // získání pole abstraktů akcí
//      $patt= $top ? "$id!$top" : $id;
//      ask_server((object)array('cmd'=>'akce','chlapi'=>$patt,'back'=>$backref)); 
//      // úzké abstrakty
//      $ys_html.= str_replace("abstr-line","abstr",$y->obsah);
//      // překlad na globální odkazy do setkani.(org|bean)
//      $fileadmin= get_fileadmin();  
//      $ys_html= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$ys_html);
//      if ( $top ) {
//        if ( $REDAKCE ) {
//          list($rok,$idp)= explode(',',$top);
//          $menu= " title='akce setkani.org: $idp' oncontextmenu=\"
//              Ezer.fce.contextmenu([
//                ['kopie akce ze setkání/$idp',function(el){ zcizit('akce',$idp,$curr_menu->mid); }],
//                ['... jen test',function(el){ zcizit('?akce',$idp,$curr_menu->mid); }]
//              ],arguments[0],0,0,'#clanek$idp');return false;\"";
//          $ys_html= strtr($ys_html,array(
//              "id='fokus_case'"  => "id='fokus_case' $menu" //,
////              "id='clanek$top'"  => "id='clanek$top' $menu"
//          ));
//        }
//      }
//      $html.= $ys_html;
//      break;

    // clanky=vzor získání abstraktů článků s danou hodnotou {tx_gncase.chlapi RLIKE vzor}
    case 'clanky':    # ------------------------------------------------ . clanky
      global $y, $backref, $top, $links, $REDAKCE;
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
      $fileadmin= get_fileadmin();  
      $ys_html= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$ys_html);
      // vložení tranformované prezentace
      // 1) z PPT uložené jako PDF s minimalizací pro web
      // 2) pdf2htmlEX --use-cropbox 0 --fit-width 800 --embed CFIJO --bg-format jpg $fname.pdf
      if ( $top ) {
        if ( $REDAKCE ) {
          $ys_kniha= preg_match("~id='fokus_page'~", $ys_html);
          $co= "setkani_".($ys_kniha?"k":"c");
          $cosi= $ys_kniha?'knihy':'článku';
          $kod= "Ezer.fce.contextmenu([
                ['kopie $cosi ze setkání/$top',function(el){ zcizit('$co',$top,$curr_menu->mid); }],
                ['... jen test',function(el){ zcizit('?$co',$top,$curr_menu->mid); }]
              ],arguments[0],0,0,'#clanek$top');return false;\"";
          $menu= " title='".($ys_kniha?'kniha':'článek')." setkani.org: $top' oncontextmenu=\"$kod\"";
          if ( $mobile ) {
            $ipad= "<span class='ipad_menu' onclick=\"arguments[0].stopPropagation();$kod\">
              <i class='fa fa-bars'></i></span>";
          }
          $ys_html= strtr($ys_html,array(
              "id='fokus_page'"  => "id='fokus_page' $menu",
              "id='clanek$top'"  => "id='clanek$top' $menu"
          ));
        }
        $ys_html= preg_replace_callback("/(###([\w\-]+)###)/",
          function($m) {
            global $REDAKCE, $ipad;
            $fname= "pdf/$m[2].html";
            if ( file_exists($fname) ) {
              if ( $REDAKCE )
                return "<span class='sorry'>$ipad soubor $fname existuje, 
                        ale jako administrátor jej neuvidíš (velký overhead)</span>";
              else
                return file_get_contents($fname);
            }
            else
              return "<span class='sorry'>$ipad soubor $fname neexistuje</span>";
          }, 
          $ys_html);
      }
      $html.= $ys_html;
      break;
*/
    // clanek=pid -- samostatně zobrazený rozvinutý part
    case 'clanek':  # ------------------------------------------------ . clanek
      global $y;
      ask_server((object)array('cmd'=>'clanek','pid'=>$id));
      $fileadmin= get_fileadmin();  
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
      $fileadmin= get_fileadmin(); 
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
  global $REDAKCE, $KLIENT, $index, $GET_rok, $mode, $load_ezer, $ezer_server, $prefix;
  global $bar_menu, $links, $currpage, $tm_active;
  
  // definice do <HEAD>
  
  // jádro Ezer - jen pokud není aktivní CMS
  $script= '';
  $client= "./ezer3.1/client";
  
  // Facebook
//  $fb_root= "<div id='fb-root'></div>";      
//  $fb_script= <<<__EOD
//      <script async defer crossorigin="anonymous" src="https://connect.facebook.net/cs_CZ/sdk.js#xfbml=1&version=v6.0"></script>'
//__EOD;      

 // Google Analytics - chlapi.cz=UA-163664361-1 chlapi.online=UA-99235788-2
  $GoogleAnalytics= $ezer_server!==2 ? '' : <<<__EOD
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-163664361-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'UA-163664361-1');
</script>
__EOD;

  // gmaps
  $api_key= "AIzaSyAq3lB8XoGrcpbCKjWr8hJijuDYzWzImXo"; // Google Maps JavaScript API 'answer-test'
//    <script src="https://maps.googleapis.com/maps/api/js?key=$api_key&callback=initMap" async defer></script>
//  $script.= !$load_ezer ? '' : <<<__EOJ
//    <script src="https://maps.googleapis.com/maps/api/js?key=$api_key&callback=skup_mapka" async defer></script>
//__EOJ;
  $script.= !$load_ezer ? '' : <<<__EOJ
    <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=$api_key"></script>
__EOJ;
  
  $script.= <<<__EOJ
    <script src="$client/licensed/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="$client/licensed/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/man/2chlapi.js" type="text/javascript" charset="utf-8"></script>
__EOJ;
  
  $script.= $links!='fotorama' ? '' : <<<__EOJ
    $fb_script
    <script src="/man/fotorama/fotorama.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="/man/fotorama/fotorama.css" type="text/css" media="screen" charset="utf-8">
__EOJ;
  
  // pokud není CMS nebude uživatel přihlášen - vstup do Ezer je přes _oninit
  $web_username= isset($_SESSION['web']['username']) ? ",username:'{$_SESSION['web']['username']}'" : '';
  $script.= $REDAKCE 
      ? <<<__EOJ
__EOJ
      : <<<__EOJ
    $GoogleAnalytics
    <script type="text/javascript">
      var Ezer= {};
      Ezer.web= {rok:'$GET_rok',index:'$index'$web_username};
      Ezer.get= { dbg:'1',err:'1',gmap:'1' };
      Ezer.fce= {};
      Ezer.str= {};
      Ezer.obj= {};
      Ezer.version= 'ezer3.1'; Ezer.root= 'man'; Ezer.app_root= 'man'; 
      Ezer.options= {
        _oninit: 'skup_mapka',
        skin: 'db'
      };
    </script>
__EOJ;

  $script.= !$load_ezer || $REDAKCE 
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
  $icon= array(
      '/man/img/chlapi_ico_local.png',
      '/man/img/chlapi_ico_dsm.png',
      '/man/img/chlapi_ico.png',
      '/man/img/chlapi_ico_doma.png',
      '/man/img/chlapi_ico_local.png' // ben
    )[$ezer_server];
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
  $loginout= $KLIENT->id
    ? "<span onclick=\"be_logout('$currpage');\" class='separator'>
         <i class='fa fa-power-off'></i> odhlásit se</span>"
    : "<span onclick=\"bar_menu(arguments[0],'me_login');\" class='separator'>
         <i class='fa fa-user-secret'></i> přihlásit se emailem</span>";
//      <span onclick="bar_menu(arguments[0],'new1');"><img src='/man/img/new.png'> změny za den</span>
//      <span onclick="bar_menu(arguments[0],'new7');"><img src='/man/img/new.png'> změny za týden</span>
//      <span onclick="bar_menu(arguments[0],'new30');"><img src='/man/img/new.png'> změny za měsíc</span>
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
  $topmenu= show_menu('top');
  $mainmenu= show_menu('main');
  $submenu= show_menu('sub');
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
  $login_display= ($REDAKCE || !isset($_GET['login'])) ? 'none' : 'block';
  $go_home= $REDAKCE 
      ? "onclick=\"go(arguments[0],'page=home','{$prefix}home','',0);\""
      : "href='{$prefix}home'";

  $cookie_email= str_replace("'",'',isset($_COOKIE['email']) ? $_COOKIE['email'] : '');  
  $logo_title= isset($_SESSION['web']['username']) ? " title='{$_SESSION['web']['username']}'" : '';
  
  // informace pro přihlášené = článek NEWS - zobrazí se jen jednou 
  $news= '';
  if ( isset($KLIENT) && $KLIENT->level && $_SESSION['web']['news'] ) {
    list($obsah,$wskill,$cskill,$zmena)= 
        select("web_text,web_skill,cms_skill,TO_DAYS(NOW())-IFNULL(TO_DAYS(ch_date),0)",
            "xclanek","id_xclanek=".NEWS);
    // zobrazí se jen, když není v redakčním modu
    if ( !$cskill ) {
      $wskill= 0+$wskill;
      // zneplatnění a přebarvení nekompetentních odkazů
      $obsah= x_cenzura($obsah);
      // po kliknutí na validní odkaz zneviditelní div
      $obsah= str_replace('href=',"onclick=\"jQuery('clanek2').fadeOut();\" href=",$obsah);
      $news= <<<__EOD
        <div class='neodkaz' style="display:block">
          <div id='clanek2' class='home' style="background:#cfdde6d6">
            $obsah
          </div>
        </div>
__EOD;
    }
    $_SESSION['web']['news']= 0;
  }
  $navod= NAVOD;
  $prompt= <<<__EOD
      <div id='msg' class='box' style="display:none">
        <div class='box_title'>title</div>
        <div class='box_text'>text</div>
        <div class='box_ok'><button onclick="box_off();">OK</button></div>
      </div>
      <div id='prompt' class='box' style="display:none">
        <div class='box_title'>Doplň příjmení: Richard ...</div>
        <div class='box_input'>
          <input type='text' title='místo pro test' onchange="_table_test(this.value);">
        </div>
      </div>
__EOD;
  $body=  <<<__EOD
    $fb_root
    <div id='page'>
      <a $go_home style="cursor:pointer"><img id='logo' src='/man/img/kriz.png'$logo_title></a>
      <div id='motto'>Mladý muž, který neumí plakat, je barbar.
          <br>Starý muž, který se neumí smát, je pitomec.
          <br><i>Richard Rohr</i>
      </div>
      <div id='menu'>
        $bar_menu
        $menu
      </div>
      $news
      <div class='neodkaz' style="display:none">
        <div id='clanek2' class='home' style="background:#cfdde6d6">
          <p>Modré <span class='neodkaz'><a class='jump'>odkazy</a></span> 
          a <span class='neodkaz'><a class='odkaz'>čárkovaně podtržené</a></span> odkazy jsou bez přihlášení neaktivní.</p>
          <p> Pokud chceš vidět úplné texty článků, musíš být přihlášen.</p>
          <!-- p>K přihlašovacímu dialogu se dostaneš pomocí menu <i class="fa fa-bars"></i> v pravém horním rohu.</p>
          <p>Přihlásit se můžeš pomocí mailové adresy, kterou jsi 
          uvedl v přihlášce na akci (iniciaci, firming). Pokud ses takové akce ještě nezúčastnil, 
          přihlášení možné nebude.</p -->
          <a class='jump' onclick="jQuery('div.neodkaz').fadeOut();">Nemám zájem</a>
          <a class='jump' href="kontakty!$navod">Jak se přihlásím?</a>
        </div>
      </div>
      <div id='user_mail' style="display:$login_display">
        <span id='user_mail_head'>Přihlášení uživatele</span>
        <div>
          <span id="user_mail_txt">
            Napiš svoji mailovou adresu, na kterou ti dojde mail s PINem,
            který ti zpřístupní např. fotky z akcí, kterých ses zúčastnil ...
          </span>
          <input id='mail' type='text' placeholder='emailová adresa' value='$cookie_email'>
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
      $prompt
    </div>
  <!-- konec -->
__EOD;

  // upozornění na testovací verzi 
  $demo= '';
//  if ( $ezer_server==2 ) {
//    $click= "jQuery('#DEMO').fadeOut(1000).delay(2000).fadeIn(1000);";
//    $dstyle= "left:0; top:0; position:fixed; transform:rotate(320deg) translate(-128px,-20px); "
//        . "width:500px;height:100px;background:orange; color:white; font-weight: bolder; "
//        . "text-align: center; font-size: 40px; line-height: 96px; z-index: 16; opacity: .5;";
//    $demo= "<div id='DEMO' onmouseover=\"$click\" style='$dstyle'>nový server</div>";
//  }

  if ( $REDAKCE ) {
    return $demo.$body;
  }
  // dokončení stránky
  echo <<<__EOD
  $head
  <body onload="jump_fokus();">
    $demo
    <div id='web'>
      <div id='work'>
      $body
      </div>
    </div>
    <img id='go_up' onclick="jQuery('#menu').Ezer_scrollIntoView();" src='/man/css/backtotop.png'>
  </body>
  </html>
__EOD;
}
/** ==========================================================================================> AKCE */
// dá další nebo předchozí akci - pro smer=0 vrátí informace pro nastavenou 
function next_xakce($curr_id,$smer=1) {
  $y= (object)array('id'=>$curr_id,'msg'=>'','info'=>'','text'=>'','dotaz'=>'');
  if ( $curr_id ) {
    $curr_datum= select('datum_od','xakce',"id_xakce=$curr_id");
    if ( $smer ) {
      $rel= $smer==1 ? '<' : '>';
      $mmm= $smer==1 ? 'MAX' : 'MIN';
      $y->id= select1("SUBSTR($mmm(CONCAT(datum_od,id_xakce)),11)",'xakce',
          "datum_od $rel '$curr_datum'");
    }
    list($nazev,$elems,$byla)= select("nazev,xelems,IF(datum_od<=NOW(),1,0)",
        'xakce',"id_xakce='$y->id'");
//        "datum_od>NOW() AND datum_od $rel '$curr_datum'");
    if ( $elems ) {
      list($elem)= explode(';',$elems);
      list($typ,$id)= explode('=',$elem);
      $y->text= "-- ($typ,$id)";
      if ( $typ=='aclanek' ) {
        $y->info= "náhled na již existující zápis";
        $y->text= select("web_text","xclanek","id_xclanek='$id'");
      }
    }
    elseif ($byla) {
      $y->info= "k akci nikdo nenapsal zápis";
      $y->dotaz= "mám založit článek pro zápis $nazev? "
          . "<br>Bude zařazen mezi akce a zatím viditelný jen pro redaktory";
    }
    else {
      $y->info= "akce ještě neproběhla";
    }
    if ( !$y->id ) {
      $y->id= $curr_id;
      $y->msg= "To je informace o ".($smer==1 ? 'první' : 'poslední').
          " připravovaná akci - ostatní informace jsou do kalendáře importovány přímo z "
          . "databáze akcí YMCA Setkání a YMCA Familia";
      
    }
  }
  return $y;
}
// 
function zapis_xakce($ida) {
  list($nazev,$od,$do)= select('nazev,datum_od,datum_do','xakce',"id_xakce=$ida");
  $oddo= datum_oddo($od,$do);
  query("INSERT INTO xclanek (cms_skill,web_text) VALUES (4,'<h1>$oddo $nazev</h1><p>...</p>')");
  $idc= pdo_insert_id();
  query("UPDATE xakce SET xelems='aclanek=$idc' WHERE id_xakce='$ida'");
  return 1;
}
/** =========================================================================================> TABLE */
# obsluha přihlašovací tabulky bez REDAKCE
# ----------------------------------------------------------------------------------==> . table show
# zobraz tabulku - idc určuje pozvánku
# pokud je akce v archivu, vynuť zobrazení tabulky jako abstraktu, pokud na ni uživatel neklikne
function table_show($ida,$idc) { trace();
  global $fe_user, $fe_host, $currpage;
  $skup= $tab= array();  // tab: [skup][poradi] poradi=0 => max, poradi>0 => jméno
  list($skupiny,$skupina,$ids)= explode('!',$currpage);
  $ids= explode(',',$ids);
  $A= count($ids)==3 ? 'C' : 'A';
  $maximum= 0;
  $h= '';
  $tr= pdo_qry("SELECT skupina,jmeno,poradi FROM xucast
    WHERE id_xclanek=$idc ORDER BY skupina,poradi,id_xucast");
  while ( $tr && (list($skupina,$jmeno,$poradi)= pdo_fetch_row($tr)) ) {
    if ( $skupina=='maximum' )    { $maximum= max($maximum,$poradi); continue; }
    if ( !isset($tab[$skupina]) ) { $skup[]= $skupina; $tab[$skupina]= array(0); }
    if ( $jmeno=='max' )          { $tab[$skupina][0]= $poradi; $maximum= max($maximum,$poradi); continue; }
    $tab[$skupina][]= "$jmeno";
  }
                                                        debug($skup,"maximum=$maximum");
                                                        debug($tab);
  if ( !count($tab) ) goto end; // pro $idc není tabulka
  // zjistíme datum ukončení akce
  $day= select('datum_do','xakce',"id_xakce=$ida");
  $dnes= date('Y-m-d');
  $h.=$day>=$dnes  
    ? ( count($tab)==1
      ? "<h3>Přihlašovací tabulka</h3>
         Pokud se chceš zúčastnit tohoto setkání, klikni na <big><b>+</b></big> za názvem místa 
         (případně vyplň krátký test) a potom přidej svoje jméno a příjmení ukončené Enter. 
         Pokud bys s tím měl problémy, pošli mi SMS na 603150565 se svým jménem
         (ale napřed to zkus tady a teď). Pokud potřebuješ svoji účast zrušit, 
         napiš znovu svoje jméno jako poprvé, bude vyjmuto."
      : "<h3>Přihlašovací tabulka</h3>
         Na tomto setkání se sejdeme v jednom čase na níže uvedených místech. Pokud se chceš zúčastnit,
         klikni na <big><b>+</b></big> za názvem skupiny (případně vyplň krátký test) a potom přidej svoje jméno a příjmení
         ukončené Enter. Pokud bys s tím měl problémy, pošli SMS na 603150565 se svým jménem a názvem skupiny
         (ale napřed to zkus tady a teď). Pokud se chceš přeřadit do jiné skupiny, napiš svoje jméno do ní (z té původní se
         vyjme samo).<br>"
      )
    : "<h3>Tabulka přihlášených</h3>";
  $h.= "<br><div class='skupiny_container'><table class='skupiny' cellspacing='0' cellpadding='0'><tr>";
  $add= $event= '';
  foreach ($skup as $s) {
    if ( $day>=$dnes )
      $event= $_SESSION['web']['tab'] || $_SESSION['web']['username']
        ? "onclick=\"table_add1(arguments[0],'$s','$idc');\""
        : "onclick=\"table_test(arguments[0]);return false;\"";
    $style= "style='box-shadow:3px 2px 6px gray;float:right'";
    $class= "class='jump'";
    if ( $day>=$dnes )
      $add= "<a $event $class $style>+</a>";
    $h.= "<th>$s$add</th>";
  }
  $h.= "</tr><tr>";
  foreach ($skup as $s) {
    if ( $day>=$dnes ) {
      $ss= strtr($s,' ','_'); 
      $event= "onsubmit=\"table_add(arguments[0],'$s','$idc');return false;\"";
      $h.= "<td><form $event>
              <input type='text' size='1' maxlength='100' id='table-$ss' style='display:none'>
            </form></td>";
    }
  }
  $h.= "</tr>";
  for ($i= 1; $i<=$maximum; $i++) {
    $h.= "<tr>";
    foreach ($skup as $s) {
      if ( !$tab[$s][0] ) $tab[$s][0]= $maximum;
      $jm= isset($tab[$s][$i]) ? $tab[$s][$i] : '';
      if ( !$_SESSION['web']['tab'] && !$_SESSION['web']['username'] ) {
        list($jm)= explode(' ',trim($jm));
      }
      $cls= $i<=$tab[$s][0] ? 'ucast' : 'nic';
      $h.= "<td class='$cls'>$jm</td>";
    }
    $h.= "</tr>";
  }
  $h.= "</table></div><br>";
  $h= ($day>=$dnes ? 'C' : $A).$h;
end:
  return $h;
}
# -----------------------------------------------------------------------------------==> . table add
# přidání nového účastníka do tabulky nebo jeho odebrání či přeřazení
function table_add($idc,$skupina,$jmeno) {
  global $y;
  db_connect();
  // old_* informace o skupině, kde jmeno už je
  $old_skupina= select('skupina','xucast',"id_xclanek=$idc AND TRIM(jmeno)='$jmeno'");
  // * - informace o skupině, kam se jméno přidává
  $pocet= select('COUNT(*)','xucast',"id_xclanek=$idc AND skupina='$skupina' AND jmeno!='max'");
  $maximum= select('poradi','xucast',"id_xclanek=$idc AND skupina='$skupina' AND jmeno='max'");
  // už je ve stejné skupině
  if ( $skupina==$old_skupina) {
    // bude smazáno
    query("DELETE FROM xucast WHERE id_xclanek=$idc AND TRIM(jmeno)='$jmeno'");
    $y->msg= "jsi odhlášen ze skupiny '$skupina'";
  }
  // je v jiné skupině
  elseif ( $old_skupina  ) {
    if ( $pocet>=$maximum ) {
      // ale nová skupina je plná
      $y->msg= "skupina '$skupina' je už plná, není možné se přehlásit ze skupiny '$old_skupina'";
    }
    else {
      // pokud není plná, změníme skupinu
      query("UPDATE xucast SET skupina='$skupina' WHERE id_xclanek=$idc AND TRIM(jmeno)='$jmeno'");
      $y->msg= "jsi přehlášen ze skupiny '$old_skupina' do '$skupina'";
    }
  }
  else {
    // jmeno není v žádné skupině
    if ( $pocet>=$maximum ) {
      $y->msg= "skupina '$skupina' je už plná, není možné se přihlásit";
    }
    else {
      // přidáme do skupiny
      query("INSERT INTO xucast(id_xclanek,skupina,jmeno) VALUES ($idc,'$skupina','$jmeno')");
      $y->msg= "jsi přihlášen do skupiny '$skupina'";
    }
  }
}
/** =========================================================================================> FOTKY */
# --------------------------------------------------------------------------------==> . show fotky 2
# uid určuje složku
function show_fotky2($fid,$lst,$back_href='') {
  global $REDAKCE;
  if ( $REDAKCE ) return show_fotky($fid,$lst,$back_href);
  $lstx= $lst;
//  $h= '';
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
  // pro mobily ukážeme komprimované obrázky tzn. začínající tečkou
  $agent= $_SERVER['HTTP_USER_AGENT'];
  // identifikace platformy prohlížeče: Android => Ezer.client == 'A'
  // viz fce ezer_browser v ezer2.php
  $dot=          // x11 hlásí Chrome při vzdáleném ladění (chrome://inspect/#devices)
    preg_match('/android|x11/i',$agent)            ? '.' : (
    preg_match('/Linux/i',$agent)                  ? '' : (
    preg_match('/iPad/i',$agent)                   ? '.' : (
    preg_match('/macintosh|mac os x/i',$agent)     ? '' : (
    preg_match('/windows|win32/i',$agent)          ? '' : '.'
  ))));
  // vytvoř podklad pro fotorama
  for ($i= 0; $i<$last; $i+=2) {
    $mini= "inc/f/$fid/..$fs[$i]";
    $midi= "inc/f/$fid/.$fs[$i]";
    $orig= "inc/f/$fid/$fs[$i]";
    $open= !$dot && file_exists($orig) ? $orig : $midi;
    if ( file_exists($mini) ) {
      $mini= str_replace(' ','%20',$mini);
      $title= '';
      if ( $fs[$i+1] ) {
        $title= $fs[$i+1];
        $title= strtr($title,array('##44;'=>',',"'"=>'"','~'=>'-'));
        $title= " data-caption='$title$dot'";
      }
      $ih.= "<img src='$open' $title>";
    }
  }
  $ih.= "</div>";
  return $ih;
}
# ----------------------------------------------------------------------------------==> . show fotky
# uid určuje složku
function show_fotky($fid,$lst,$back_href) { 
  global $REDAKCE;
  $lstx= $lst;
//  popup("Prohlížení fotografií","$fid~$lstx",$back_href,'foto');
  $h= '';
  $fs= explode(',',$lstx);
  $last= count($fs)-1;
  for ($i= 0; $i<$last; $i+=2) {
    $mini= "inc/f/$fid/..$fs[$i]";
//    $open= "inc/f/$fid/.$fs[$i]";
//    $orig= "inc/f/$fid/$fs[$i]";
    if ( file_exists($mini) ) {
      $mini= str_replace(' ','%20',$mini);
      $title= $fs[$i];
      if ( $fs[$i+1] ) {
        $title= $fs[$i+1];
        $title= strtr($title,array('##44;'=>',',"'"=>'"','~'=>'-'));
      }
      $i2= $i/2;
      $onclick= $REDAKCE ? '' : " onclick=\"foto_show(arguments[0],$i2);return false;\"";
      $h.= " <span data-foto-n='$i2' title='$title' $onclick
               class='foto foto_cms' style='background-image:url($mini)'></span>";
    }
  }
  return $h;
}
/** ========================================================================================> SERVER */
# funkce na serveru přes AJAX
function servant($qry,$context=null) {
  global $y, $servant, $ezer_server;
  $secret= "WEBKEYNHCHEIYSERVANTAFVUOVKEYWEB";
  $servant= array(
//      "https://www.setkani.org/servant.php?secret=$secret", 
      "http://setkani4m.bean:8080/servant.php?secret=$secret",
      "https://www.setkani.org/servant.php?secret=$secret",
      "https://www.setkani.org/servant.php?secret=$secret",
      "http://setkani4.doma/servant.php?secret=$secret",
      "https://www.setkani.org/servant.php?secret=$secret"  // ben
    )[$ezer_server];
  $_SESSION['web']['servant_last']= "$servant&$qry";
  $json= url_get_contents("$servant&$qry",false,$context);
                                          display("<b style='color:red'>servant</b> $servant$qry");
  if ( $json===false ) {
    $y->msg= "$ezer_server:$qry vrátil false";
    $err= error_get_last();
    $_SESSION['web']['servant_state']= "false:{$err['type']},{$err['message']}";
    $_SESSION['web']['wrappers']= stream_get_wrappers();
    $_SESSION['web']['openssl']= extension_loaded('openssl');
    $y->ses= $_SESSION;
//    session_write_close ();
//    exit;
  }
  elseif ( substr($json,0,1)=='{' ) {
    $y= json_decode($json);
    $_SESSION['web']['servant_state']= "json";
  }
  else {
    $y->msg= "Sorry, došlo k chybě č.4, martin@smidek.eu ti poradí ...";
    $_SESSION['web']['servant_state']= "text:$json";
                                                  display($y->msg);
//    $y->msg= "'$servant&$qry' vrátil '$json'";
  }
}
function ask_server($x) {
  global $y;
//   $x->cmd= 'test'
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
    $poslano= 0;
    foreach($tos as $to) {
      $_SESSION['web']['phpmailer1']= $to;
      $err= '';
      $y->ok= emailIsValid($to,$err) ? 1 : 0;
      if ( $y->ok ) {
        // organizátorům
        $ret= mail_send($x->reply,$to,$x->subj,$x->body);
        $_SESSION['web']['phpmailer2']= $ret;
        $y->txt= $ret->err
          ? " Mail se bohužel nepovedlo - napiš prosím na seskup@gmail.com "
          : " Mail byl odeslán organizátorům skupiny ";
        $poslano+= $ret->err ? 0 : 1;
      }
      else {
        $y->txt= "'$to' nevypadá jako mailová adresa ($err)";
      }
    }
    if ( $poslano ) {
      // zpětná vazba odesílateli
      $body= "Posíláme Ti potvrzení o odeslání zprávy pro skupinu <i>$x->skupina</i>. 
        Pokud se od nich nedočkáš v přiměřeném čase odpovědi, 
        pošli prosím kopii zprávy na adresu seskup@gmail.com, zařídíme to.";
      $ret= mail_send("seskup@gmail.com",$x->reply,"Fwd: $x->subj",$body);
      $_SESSION['web']['phpmailer3']= $ret;
      $y->txt.= $ret->err
        ? " Potvrzující mail se bohužel nepovedlo odeslat ($ret->err)"
        : " a byl Ti odeslán potvrzující mail ";
    }
    break;

  case 'mapa':     // ------------------------------------------------------------------------- mapa
    servant("mapa=$x->mapa");
    break;

  case 're_login': // ---------------------------------------------------------------------- relogin
    servant("cmd=re_login&fe_user={$_SESSION['web']['user']}&web=chlapi.cz");
    break;
  
  case 'me_login': // ------------------------------------------------------------------------ login
    servant("cmd=me_login&mail=$x->mail&pin=$x->pin&web=$x->web");
    if ( isset($y->state) && $y->state=='ok') {
      // přepočítej _user.skill na fe_level
      db_connect();
      $skills= select('skills','_user',"id_user='$y->user'");
      $skilla= $skills ? explode(' ',$skills) : array();
      $y->skills= $skilla;
      $y->klient= $y->mrop ? 1 : 0;   // iniciace
      $y->klient+= $y->firm ? 2 : 0;  // firming
      $y->redakce= 0;
      $y->redakce+= in_array('m',$skilla) ? 1 : 0;
      $y->redakce+= in_array('t',$skilla) ? 2 : 0;
      $y->redakce+= in_array('r',$skilla) ? 4 : 0;
      // přihlas uživatele jako FE - zájímá nás jen id_osoba vrácená v y.user, y.mrop, y.name
      $_SESSION['web']['fe_usergroups']= '0,4,6';
      $_SESSION['web']['user']= $y->user;
      $_SESSION['web']['level']= $_SESSION['web']['level0']= $y->klient;
      $_SESSION['web']['username']= $y->name;
      $_SESSION['web']['usermail']= $x->mail;
      $_SESSION['web']['news']= 1;
      // případně jako BE
      if ( $y->redakce ) {
        $_SESSION['man']['level']= $_SESSION['man']['level0']= $y->redakce;
      }
      else {
        // pokud to není redaktor zapiš me_login 
        log_login('u',$_SESSION['web']['usermail']);
        unset($_SESSION['man']);
      }
    }
    else if ( isset($y->state) && $y->state=='wait') {
      // čekání na reakci nezapisujeme
      log_login('w',$x->mail);
    }
    else {
      // zapiš problém s me_login
      log_login('-',$x->mail);
    }
    break;
    
  case 'me_noedit': // ---------------------------------------------------------------------- noedit
    $y->user= $_SESSION['web']['user'];
    $y->username= $_SESSION['web']['username'];
    if ( $x->noedit ) {
      // zrušení možnosti editovat => jen prohlížení bez natažení Ezer
      log_login('u',$_SESSION['web']['usermail']);
      unset($_SESSION['man']);
    }
    else {
      log_login('r',$_SESSION['web']['usermail']);
    }
    break;

  case 'be_logout': // ---------------------------------------------------------------------- logout
    log_login('x',$_SESSION['web']['usermail']);
    unset($_SESSION['web']);
    unset($_SESSION['man']);
    session_write_close();
    $y->user= 0;
    $y->klient= 0;
    $y->redakce= 0;
    $y->page= $x->page;
    break;
  // obsluha tabulky účasti
  case 'table_tst':
    $y->ok= $_SESSION['web']['tab']= trim(strtolower($y->test))=='rohr' ? 1 : 0;
    break;
  case 'table_add': // přidat účast
    table_add($y->idc,$y->skupina,trim($y->jmeno));
    break;
  }
  return 1;
}
# --------------------------------------------------------------------------------- url get_contents
function url_get_contents($url, $useragent='cURL', $headers=false, $follow_redirects=true, $debug=false) {
  // initialise the CURL library
  $ch = curl_init();
  // specify the URL to be retrieved
  curl_setopt($ch, CURLOPT_URL,$url);
  // we want to get the contents of the URL and store it in a variable
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  // specify the useragent: this is a required courtesy to site owners
  curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
  // ignore SSL errors
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  // return headers as requested
  if ($headers==true){
    curl_setopt($ch, CURLOPT_HEADER,1);
  }
  // only return headers
  if ($headers=='headers only') {
    curl_setopt($ch, CURLOPT_NOBODY ,1);
  }
  // follow redirects - note this is disabled by default in most PHP installs from 4.4.4 up
  if ($follow_redirects==true) {
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
  }
  // if debugging, return an array with CURL's debug info and the URL contents
  if ($debug==true) {
    $result['contents']=curl_exec($ch);
    $result['info']=curl_getinfo($ch);
  }
  // otherwise just return the contents as a variable
  else $result=curl_exec($ch);
  // free resources
  curl_close($ch);
  // send back the data
  return $result;
}
# --------------------------------------------------------------------------------------- datum oddo
function datum_oddo($x1,$x2) {
  $d1= 0+substr($x1,8,2);
  $d2= 0+substr($x2,8,2);
  $m1= 0+substr($x1,5,2);
  $m2= 0+substr($x2,5,2);
  $r1= 0+substr($x1,0,4); 
  $r2= 0+substr($x2,0,4);
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
# --------------------------------------------------------------------------------------- db connect
# připojí databázi
function db_connect() { 
//  global $ezer_db;
//  $ezer_local= preg_match('/^.*\.(bean)$/',$_SERVER["SERVER_NAME"]);
//  $hst1= 'localhost';
//  $nam1= $ezer_local ? 'gandi'    : 'gandi';
//  $pas1= $ezer_local ? ''         : 'radost';
//  $db1=  $ezer_local ? 'chlapi'  : 'ezerweb';
//  $ezer_db= array( /* lokální */
//    'setkani'  =>  array(0,$hst1,$nam1,$pas1,'utf8',$db1),
//  );
//  ezer_connect('setkani');
  
  global $ezer_db, $ezer_server, $http_server;
  $http_server= "http://$ezer_server";
  $dbs= array(
    array(  // 0 = lokální
      'setkani'  => array(0,'localhost','gandi','','utf8','chlapi'),
      'ezertask' => array(0,'localhost','gandi','','utf8')
    ),
    array(  // 1 = ostré - Synology + online 
      'setkani'  => array(0,'localhost','ymca','JW4YNPTDf4Axkj9','utf8','chlapi'),
      'ezertask' => array(0,'localhost','ymca','JW4YNPTDf4Axkj9','utf8','myslenky')
    ),
    array(  // 2 = ostré - Synology + cz
      'setkani'  => array(0,'localhost','ymca','JW4YNPTDf4Axkj9','utf8','chlapi'),
      'ezertask' => array(0,'localhost','ymca','JW4YNPTDf4Axkj9','utf8','myslenky')
    ),
    array(  // 3 = ladící - Synology DOMA
      'setkani'  => array(0,'localhost','gandi','','utf8','chlapi'),
      'ezertask' => array(0,'localhost','gandi','','utf8','myslenky')
    ),
    array(  // 4 = ladící - ben
      'setkani'  => array(0,'localhost','gandi','','utf8','chlapi'),
      'ezertask' => array(0,'localhost','gandi','','utf8')
    ),
  );
  $ezer_db= $dbs[$ezer_server];
  ezer_connect('setkani'); 
}
/** =========================================================================================> ADMIN */
# --------------------------------------------------------------------------------------- log report
# vrátí seznam 
# - změn obsahu
# - chybných pokusů o přihlášení do chlapi.online
function log_report($par) { debug($par,'log_report');
  $html= "";
  switch ($par->cmd) {
  case 'obsah':    // -------------------------------------- obsah
    $dnu= $par->days;
    $html.= "<dl>";
    $cr= pdo_qry("
      SELECT kdo,LEFT(MAX(kdy),16),GROUP_CONCAT(DISTINCT jak ORDER BY jak) AS _jak,tab,id_tab,
        IFNULL(username,kdo),COUNT(*) AS _krat
      FROM log LEFT JOIN _user ON id_user=kdo
      WHERE kdy > DATE_SUB(NOW(),INTERVAL $dnu DAY)
      GROUP BY tab,id_tab,kdo,DATE(kdy)
      ORDER BY kdy DESC
    ");
    while ( $cr && (list($kdo,$kdy,$_jak,$tab,$id_tab,$username,$krat)
        = pdo_fetch_row($cr)) ) {
      $jak= '';
      foreach (explode(',',$_jak) as $j) {
        $jak.= $j=='u' ? ' úprava' : (
            $j=='i' ? ' <b>vložení</b>' : (
            $j=='d' ? ' smazání' : (
            $j=='r' ? ' resize' : '?')));
      }
      $co= $tab=='c' ? 'článku' : ( 
           $tab=='b' ? 'knihy' : 'akce' );
      $tab=$tab=='c' ? 'xclanek' : ( 
           $tab=='b' ? 'xkniha' : 'xakce' );
//      $txt= $tab=='c' ? log_show('xclanek',$id_tab) : "akce $id_tab";
      $txt= log_show($tab,$id_tab);
      $krat= $krat==1 ? "" : " ($krat x)";
      $y= log_path($tab,$id_tab); // pokus nalézt umístění
      $html.= "$kdy <b>$username</b> - $jak $co $txt $krat $y->url<br>";
    }
    $html.= "</dl>";
    break;
  case 'me_login': // -------------------------------------- přihlášení
    $html.= "<dl>";
    $cond= 
      $par->typ==='ok' ? "msg RLIKE '^ok|^x'" : (
      $par->typ==='ko' ? "msg RLIKE '^ko' AND NOT msg RLIKE 'byl odeslán'" : (
      $par->typ==='r'  ? "msg RLIKE '^ed'" : "/*typ=$par->typ*/" ));
    $cr= pdo_qry("
      SELECT day,time,msg
      FROM _touch
      WHERE module='log' AND menu='login' AND $cond
      ORDER BY day DESC, time desc
    ");
    while ( $cr && (list($day,$time,$msg)= pdo_fetch_row($cr)) ) {
      list($ok,$mail,$ip,$os,$brow1,$brow2,$txt)= explode('|',$msg);
//      if ( $ok=='ko' && $par->typ=='ko' || $ok=='ok' && $par->typ=='ok' )
        $html.= "<dt>$day $time <b>$mail</b></dt><dd>$ok $ip $os $brow1 $brow2 <i>$txt</i></dd>";
    }
    $html.= "</dl>";
    break;
  }
  return $html;
}
# ----------------------------------------------------------------------------------------- log path
# vrátí url článku nebo knihy a zkusí zjistit id_menu
# pokud tab=xclanek a zmena=1 tak zapíše menu.ch_date=MAX(menu.ch_date,xclanek.ch_date)
function log_path($tab,$idc,$zmena=0) { 
  $y= (object)array('url'=>'','path'=>'');
  $ch_date= '0000-00-00';
  $idm= 0;
  $path= array();
  switch ($tab) {
  case 'xakce':  
    $elems= select("xelems","xakce","id_xakce=$idc");
    if ( $elems ) {
      $html= 'viz akce $nazev';
    }
    else {
      list($idm,$ref,$top,$menu)= 
          select('mid,ref,mid_top,nazev','menu',"elem RLIKE 'kalendar(;|$)'");
      if ( $idm ) {
        if ( $top ) $path[]= select('ref','menu',"mid=$top");
        $path[]= $ref;
        $path[]= $idc;
        $y->path= "v menu <i>$menu</i>";
        goto end;
      }
    }
    break;
  case 'xkniha':  
    // kniha v menu ?
    list($idm,$ref,$top,$menu)= 
        select('mid,ref,mid_top,nazev','menu',"elem RLIKE 'xkniha=$idc(;|$)'");
    if ( $idm ) {
      if ( $top ) $path[]= select('ref','menu',"mid=$top");
      $path[]= $ref;
      $path[]= $idc;
      $y->path= "v menu <i>$menu</i>";
      goto end;
    }
    break;
  case 'xclanek':  
    // článek v menu ?
    list($idm,$ref,$top,$menu)= 
        select('mid,ref,mid_top,nazev,ch_date','menu',"elem RLIKE 'clanek=$idc(;|$)'");
    if ( $idm ) {
      if ( $top ) $path[]= select('ref','menu',"mid=$top");
      $path[]= $ref;
      $path[]= $idc;
      $y->path= "v menu <i>$menu</i>";
      goto end;
    }
    // článek v knize ?
    list($idk,$kniha)= 
        select('id_xkniha,nazev','xkniha',"xelems RLIKE 'clanek=$idc(;|$)'");
    if ( $idk ) {
      list($idm,$ref,$top,$menu)= 
          select('mid,ref,mid_top,nazev','menu',"elem RLIKE 'kniha=$idk(;|$)'");
      if ( $top ) $path[]= select('ref','menu',"mid=$top");
      $path[]= $ref;
      $path[]= "$idk,$idc";
      $y->path= "v knize <i>$kniha</i> v menu <i>$menu</i>";
      goto end;
    }
    // článek v akci ?
    list($ida,$akce,$rok,$nazev)= 
        select('id_xakce,nazev,YEAR(datum_od),nazev','xakce',"xelems RLIKE 'clanek=$idc(;|$)'");
    if ( $ida ) {
      $path[]= 'akce';
      $path[]= "$rok,$idc";
      $y->path= "v akci <i>$nazev</i> roku $rok";
      goto end;
    }
    break;
  }
end:
  // zapíše změny do menu
  if ( $zmena && $tab=='xclanek' && $idc && $idm ) {
    $ch_date= select('ch_date','xclanek',"id_xclanek=$idc");
    query("UPDATE menu SET ch_date=GREATEST(ch_date,'$ch_date') WHERE mid=$idm");
  }
  // konec
  $path= implode('!',$path);
  $y->url= "<a onclick=\"go(0,'page=$path','$path')\">&nbsp;<i class='fa fa-arrow-right'></i>&nbsp;</a>";
  return $y;
}
# ----------------------------------------------------------------------------------------- log show
# zobrazí náhled článku
function log_show($tab,$id_tab) { trace();
  global $index;
  $html= "$id_tab";
  switch ($tab) {
  case 'xakce':  
    list($elems,$obsah,$nazev,$od,$do,$misto)= 
      select("xelems,web_text,nazev,datum_od,datum_do,misto","xakce","id_xakce=$id_tab");
    $oddo= datum_oddo($od,$do);
    if ( $elems ) {
      $html= 'viz akce $nazev';
    }
    else {
      // zobrazit jako abstrakt
      $obsah= x_shorting($obsah);
      $html= "
        <div class='back'>
          <a class='aclanek home'>
            <b>$oddo $nazev, $misto</b> 
            <br>$obsah
          </a>
        </div>";
    }
    $html= "<a onclick=\"Ezer.fce.alert(`$html`)\">$id_tab</a>";
    break;
  case 'kapitola':
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
    if ( $tab=='xclanek' )
      $html= "<a onclick=\"Ezer.fce.alert(`$html`)\">$id_tab</a>";
    break;
  case 'xkniha':  
    $elems= select("xelems","xkniha","id_xkniha=$id_tab");
    list($elem)= explode(';',$elems);
    list($filler,$idc)= explode('=',$elem);
    $html_c= log_show('kapitola',$idc);
    $html= "<a onclick=\"Ezer.fce.alert(`$html_c`)\">$id_tab</a>";
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
  db_connect();
  $qry= "INSERT INTO log (kdo,kdy,jak,tab,id_tab)
         VALUES ('$kdo','$kdy','$jak','$tab','$id_tab')";
  pdo_qry($qry);
  return 1;
}
# ---------------------------------------------------------------------------------------- log login
# zapíše informace o přihlášení
# ok= u pri uživatele, r pro redaktora, - pro chybu, x pro odhlášení redaktora
# id_user pro přihlášeného redaktora (zapisuje se v man.php)
function log_login($ok,$mail='') { 
  global $y;
  $day= date('Y-m-d');
  $time= date('H:i:s');
  $abbr= '';
  $ip= isset($_SERVER['HTTP_X_FORWARDED_FOR'])
      ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
  $browser= $_SERVER['HTTP_USER_AGENT'];
  $id_user= $_SESSION['web']['user'];
  // detekuj relogin a zvyš jej
  $relog= isset($_SESSION['web']['cms_relog']) ? $_SESSION['web']['cms_relog'] : 0;
  $_SESSION['web']['cms_relog']= $relog+1;
  db_connect();
  if ( $id_user ) {
    $abbr= select1("abbr","_user","id_user='$id_user'");
    // uvolni všechny uzamčené záznamy, které jsi zamkl
    record_unlock('xclanek',0,true);
    record_unlock('xkniha',0,true);
  }
  if ( $ok=='x') {
    // odhlášení
//    $menu= 'logout';
//    $msg= "$username|";
    $menu= 'login';
    $msg= "x|$mail|odhlášení";
  }
  elseif ( $ok=='r') {
    $menu= 'login';
    $msg= "ed|$mail||$ip|{$_SESSION['platform']}|{$_SESSION['browser']}|$browser";
  }
  elseif ( $ok=='w') {
    $menu= 'wait';
    $msg= "wait|$mail|$ip|{$_SESSION['platform']}|{$_SESSION['browser']}|$browser";
  }
  elseif ( $ok=='u' ) {
    $menu= 'login';
    $msg= "ok|$mail|$ip|{$_SESSION['platform']}|{$_SESSION['browser']}|$browser";
  }
  else { 
    $ok= '-';
    $txt= str_replace("'","\\'",(isset($y->txt)?$y->txt:'?').(isset($y->msg)?$y->msg:'?'));
    $menu= 'login';
    $msg= "ko|$mail|$ip||||$txt";
  }
  // do _touch zapiš odhlášení nebo první přihlášení 
  if ( $ok=='u' || $ok=='x' || !$relog ) {
    $qry= "INSERT INTO _touch (day,time,user,module,menu,msg)
           VALUES ('$day','$time','$abbr','log','$menu','$msg')";
    pdo_qry($qry);
  }
  // při opakovaném přihlášení = např. obnově obrazovky obnov platnost PINu
  if ( $relog && $ok=='r') {
    ask_server((object)array('cmd'=>'re_login'));
  }
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
    $ret->idu= $kdo;
    $ret->kdo= select("username","_user","id_user='$kdo'") ?: "???";
    $ret->kdy= sql_date($kdy);
  }
  else {
    // volný záznam - zmkni jej
    $ret->idu= 0;
    $ret->kdo= '';
    $id_user= $_SESSION['web']['user'];
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
    $id_user= $_SESSION['web']['user'];
    query("UPDATE $table SET lock_kdo=0,lock_kdy=NOW() WHERE lock_kdo='$id_user'");
  }
  else {
    query("UPDATE $table SET lock_kdo=0,lock_kdy=NOW() WHERE id_$table=$id_table");
  }
  return 1;
}
?>
