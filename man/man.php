<?php

  // volba verze jádra Ezer
  $kernel= "ezer".(isset($_GET['ezer'])?$_GET['ezer']:'3'); 

  // rozlišení lokální a ostré verze
  $ezer_local= preg_match('/^\w+\.bean$/',$_SERVER["SERVER_NAME"])?1:0;

  // parametry aplikace MAN
  $app_name=  "chlapi.online";
  $app_login= 'Guest/';                   // zakomentovat pro automatické přihlášení 
  $app_root=  'man';
  $app_js=    array("man/2chlapi.js","man/modernizr-custom.js",
                    "man/fotorama/fotorama.js");
  $app_css=   array("man/mini.css","man/3chlapi.css","man/web_edit.css",
                    "man/fotorama/fotorama.css");
  $skin=      'ck';
  $abs_roots= array("/home/users/gandi/chlapi.online/web","C:/Ezer/beans/chlapi.online");
  $rel_roots= array("http://chlapi.online","http://chlapi.bean:8080","man/fotorama/fotorama.css");
  
  // specifická část aplikace předávaná do options
  specific($template_meta,$template);
  
  // (re)definice Ezer.options
  $add_pars= array(
    'template' => "user",
    'template_meta' => $template_meta,
    'template_body' => $template,
  'CKEditor' => "{
      version:'4.6',
      Minimal:{toolbar:[['Bold','Italic','Source']]}
    }"
  );
  
  // (re)definice Ezer.options
  $add_options= array(
    'to_trace' => 1,
  );

  // je to aplikace se startem v podsložce a chceme mapy
  $_GET['gmap']= 1;
  require_once("../$kernel/ezer_main.php");
  
function specific(&$template_meta,&$template) {
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
      $Ezer_web.= "$del$wi:'$w'";
      $del= ',';
    }
  }

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
      <img class="StatusIcon" id="StatusIcon_server" src="man/img/+logo.gif" />
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
  <div id="dolni" style="display:none">
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
  <form><input id="drag" type="button" /></form>
</body>
%html_footer
__EOD;
}

?>
