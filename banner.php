<?php

/* bannery směřující na chlapi.cz
 * 
 * banner.php?typ=rr|cac[&src=1]
 * 
 * rr  - denní zamyšlení Richarda Rohra
 * cac - denní meditace CAC
 * 
 * 
 */

if (!isset($_GET['typ'])) die();

$typ= $_GET['typ'];
$deep_root= "../files/chlapi";
require_once("$deep_root/man.dbs.php");

if ( isset($_GET['src']) &&$_GET['src'] ) {
    
  // vytvoř obsah
  require_once("man/man_web.php");
  require_once("man/2template_ch.php");
  require_once("man/2mini.php");
  db_connect();

  switch ($typ) {
    case 'rr':
      $obsah= rr_myslenka(); 
      $img= "<img src='/man/img/rr_gr.jpg' style='width:80px;float:right;
          margin:4px 45px 0 10px;border-radius:5px'>";
      $ref= "home!1";
      break;
    case 'cac':
      $obsah= cac_meditace('',"",0,2); // třetí parametr je vysvětlený ve funkci
      $img= "<img src='/man/img/cac_logo.jpg' style='width:80px;float:right;
          margin:20px 45px 40px 10px;border-radius:5px'>";
      $ref= "home!2";
      break;
  }
  // zobrazit jako abstrakt
  $obsah= x_shorting($obsah);
  $html= <<<__EOH
  <body style="margin:0" title="proklikem přejdete na stránky chlapi.cz s plným textem">
    <div>
      <a href='$rel_root/$ref' target='chlapi_cz' 
        style="text-decoration:none;color:black;overflow:hidden;display:block;padding:5px;
          text-align:justify;max-height:140px;background:url(man/css/more.png) 
            bottom right no-repeat,linear-gradient(to bottom right,#E6E1CF 50%,#807340 75%)">
        $img
        $obsah
        <br><i style="text-align:right;display:block">... proklik zobrazí plný text</i>
      </a>
    </div>
  </body>
__EOH;
  echo $html;      
}
else {
  // zveřejni obsah
  global $rel_root;
  echo(<<<__EOH
  <iframe 
    style="border: 1px solid black;border-radius:5px"
    width="540" height="150" id="chlapi.cz" title="chlapi.cz"
    src= "$rel_root/banner.php?typ=$typ&src=1"
  </iframe>
__EOH
  );
}
?>
