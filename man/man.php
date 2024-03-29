<?php

  // volba verze jádra Ezer, vnucení parametrů
  $ezer_version= "3.2";
  $kernel= "ezer$ezer_version"; 
  $_GET['pdo']= 2;

  // verze js + css
  $v_app= '';
  if (file_exists("man/version.php")) {
    require "man/version.php";
    $v_app= "?v=$version";
  }
  
  // základní údaje aplikace MAN
  $app_name=  "chlapi.cz";
  $app_root=  'man';
  $app_js=    array("/man/2chlapi.js$v_app", "/man/man.js$v_app", 
                    "/man/fotorama/fotorama.js$v_app");
  $app_css=   array("/man/css/mini.css$v_app","/man/css/2chlapi.css=css_chlapi$v_app",
                    "/man/css/edit.css$v_app","/man/fotorama/fotorama.css$v_app");
  $skin=      'ck';
  
  require_once("../$kernel/server/ae_slib.php");
  require_once '2template_ch.php';
  require_once("../$kernel/pdo.inc.php");
  require_once("../$kernel/server/ezer_pdo.php");

  // určení uživatele podle session.web.fe_user
  db_connect();
  $username= select("username","_user","id_user='{$_SESSION['web']['user']}'");
  if ( $username ) {
    $app_login= "$username/";
    log_login('r'); // (be_)login 
  }
  else {
    // nebo odmítnutí přihlášení
    session_destroy();
    header("Location: $rel_root"); 
  }

  // specifická část aplikace předávaná do options
  specific($template_meta,$template);
  
  // (re)definice Ezer.options
  $add_options= (object)array(
    'to_trace' => 1,
    'mini_debug' => 1,
    'path_files_href' => "'$rel_root'",
    'path_files_s' => "'$abs_root/'"  // absolutní cesta pro přílohy
  );

  // (re)definice Ezer.options
  global $icon;
  $add_pars= array(
    'log_login' => false,   // nezapisovat standardně login do _touch (v ezer2.php)
    'favicon' => $icon,
//                  array('chlapi_ico_local.png','chlapi_ico_dsm.png','chlapi_ico.png',
//                        'chlapi_ico_doma.png','chlapi_ico_local.png')[$ezer_server],    // ben
    'template' => "user",
    'template_meta' => $template_meta,
    'template_body' => $template,
    'CKEditor' => "{
      version:'4.6',
      WEB:{
        skin:'moono-lisa',
        toolbar:[['Maximize','Styles','-','Bold','Italic','TextColor','BGColor', 'RemoveFormat',
          '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Outdent', 'Indent', 'Blockquote',
          '-','NumberedList','BulletedList','Table',
          '-','Anchor','Link','Unlink','HorizontalRule','Image','Embed',
          '-','Source','ShowBlocks','RemoveFormat']],
        // Configure the Enhanced Image plugin to use classes instead of styles and to disable the
        // resizer (because image size is controlled by widget styles or the image takes maximum
        // 100% of the editor width).
        image2_alignClasses: [ 'image-align-left', 'image-align-center', 'image-align-right' ],
        image2_disableResizer: false,
        extraPlugins:'widget,filetools,embed', // ezer
        entities:true,  // →
        embed_provider: '//iframe.ly/api/oembed?url={url}&callback={callback}&api_key=313b5144bfdde37b95c235',
        uploadUrl:'man/upload.php?root=man&type=Images',
        stylesSet:[
          {name:'název',     element:'h1'},
          {name:'nadpis',    element:'h2'},
          {name:'podnadpis', element:'h3'},
          {name:'odstavec',  element:'p'},
          {name:'odstavec!', element:'p',    attributes:{'class':'p-clear'}},
          {name:'neodkaz',   element:'span', attributes:{'class':'neodkaz'}},
          {name:'stín',      element:'p',    attributes:{'class':'shadow'}},
          {name:'stín/i',    element:'img',  attributes:{'class':'shadow'}},
          {name:'ikona',     element:'span',  attributes:{'class':'ikona'}},
          {name:'odkaz',     element:'a',    attributes:{'class':'jump'}},
          {name:'bible',     element:'span', attributes:{'class':'bible'}}
        ],
        contentsCss:'man/css/edit.css'
      },
      Cac:{toolbar:[['Styles','-','Undo','Redo','-','Bold','Italic','-','Outdent','Indent',
              '-','Link','Unlink','-','Source']],
        stylesSet:[
          {name:'odstavec',  element:'p'},
          {name:'bible',     element:'span', attributes:{'class':'bible'}}
        ],
        contentsCss:'man/css/edit.css'
      }
    }"
  );
  
  // je to aplikace se startem v podsložce a chceme mapy
  $_GET['gmap']= 1;
  require_once("../$kernel/ezer_main.php");
  
function specific(&$template_meta,&$template) {
  global $app_root, $lang;
  $debugger= '';
  if ( isset($_GET['dbg']) && $_GET['dbg'] ) {
    $dbg_script= isset($_SESSION[$app_root]['dbg_script'])
      ? trim($_SESSION[$app_root]['dbg_script'])
      : "set_trace('m',1,'init,set,key');";
    $debugger= <<<__EOD
      <form action="" method="post" enctype="multipart/form-data" id="form">
        <textarea id="dbg" name='query' class='sqlarea jush-sql' spellcheck='false' wrap='off'
        >$dbg_script</textarea>
        <script type='text/javascript'>focus(document.getElementsByTagName('textarea')[0]);</script>
      </form>
__EOD;
  }

  // předání kontextu pro FE
  $Ezer_web= $del= '';
  if ( isset($_SESSION['web'])) {
    foreach ($_SESSION['web'] as $wi=>$w) {
      if (substr($wi,0,1)=='*') continue;
      $Ezer_web.= "$del$wi:'$w'";
      $del= ',';
    }
  }

  // počáteční tapeta pro redakční běh, pro klientský běh, je v 2template.php
  $wall= isset($_COOKIE['wallpaper']) ? $_COOKIE['wallpaper'] : 'foto_home.jpg';

//    <style>body{background-image:url(./man/css/wall/$wall) !important;}</style>
  $template_meta= <<<__EOD
    <meta name="robots" content="noindex, nofollow" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <meta name="viewport" content="width=device-width,user-scalable=yes,initial-scale=1" />
__EOD;

  $template= <<<__EOD
%header
<body id="body" onload="context({ $Ezer_web});">
<!-- bez menu a submenu -->
  <div id='horni' class="MainBar">
    <div id='logo'>
      <button id='logoContinue' style='display:none;outline:3px solid orange;'>continue</button>
      <img class="StatusIcon" id="StatusIcon_idle" src="man/img/-logo.gif" />
      <!-- img class="StatusIcon" id="StatusIcon_server" src="man/img/+logo.gif" / -->
    </div>
  </div>
  <div id='ajax_bar'></div>
<!-- pracovní plocha -->
  <div id="stred">
    <!-- div id="shield"></div -->
    <div id="work"></div>
  </div>
<!-- paticka -->
  <div id="paticka">
    <div id="warning"></div>
    <div id="kuk_err"></div>
    <div id="error"></div>
  </div>
  <div id="popup_mask3"></div>
  <div id="top_mask3">
    <div id="popup3">
      <div class="pop_head"></div>
      <div class="pop_body"></div>
      <div class="pop_tail"></div>
    </div>
  </div>
  <div id="dolni">
    <div id="status_bar" style='width:100%;height:16px;padding: 1px 0pt 0pt'>
      <div id='status_left' style="float:left;"></div>
      <div id='status_center' style="float:left;"></div>
      <div id='status_right' style="float:right;"></div>
    </div>
    <div id="trace">
      $debugger
      <pre id="kuk"></pre>
    </div>
  </div>
<!-- konec -->
  <input id="drag" type="input" />
</body>
%html_footer
__EOD;
}

?>
