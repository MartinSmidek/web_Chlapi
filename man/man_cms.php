<?php

// ---------------------------------------------------------------------------------------------- //
// funkce aplikace chlapi.online pro mode CMS                                                     //
//                                                                                                //
// CMS/Ezer                                             (c) 2018 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //

/** =========================================================================================> FOTKY */
# --------------------------------------------------------------------------------==> . create fotky
# přidání fotek - pokud je definováno x.kapitola pak pod příslušné part - jinak na konec
function create_fotky($x) {
  $cid= $x->cid;
  $autor= mysql_real_escape_string($x->autor);
  $nadpis= mysql_real_escape_string($x->nadpis);
  $psano= sql_date1($x->psano,1);
  $editors= $x->editors ? implode(',',(array)$x->editors) : '';
  query("INSERT INTO xfotky (id_xclanek,editors,nazev,kdy,autor,seznam)
         VALUES ($cid,'$editors','$nadpis','$psano','$autor','')");
  $fid= mysql_insert_id();
  return $fid;
}
# ----------------------------------------------------------------------------------==> . load fotky
function load_fotky($fid) { trace();
  global $REDAKCE, $href0, $clear;
  $x= (object)array();
  list($id_xclanek,$x->editors,$x->autor,$x->nadpis,$lst,$psano)=
    select('id_xclanek,editors,autor,nazev,seznam,kdy','xfotky',"id_xfotky=$fid");
  $x->fotky= "<span class='foto drop' data-foto-n='-1'></span><ul class='foto' id='foto'>";
  $x->psano= sql_date1($psano);
  $fs= explode(',',$lst);
  $last= count($fs)-1;
  for ($i= 0; $i<$last; $i+=2) {
    $mini= "inc/f/$fid/..$fs[$i]";
    if ( file_exists($mini) ) {
      $title= $fs[$i] ? "title='{$fs[$i]}'" : '';
      $tit= $fs[$i+1] ? "<div>{$fs[$i+1]}</div>" : '';
      $chk= "<input type='checkbox' onchange=\"this.parentNode.dataset.checked=this.checked;\" />";
      $menu= "oncontextmenu=\"Ezer.fce.contextmenu([
          ['smazat fotku',foto_delete],
          ['upravit popis',foto_note]
        ],arguments[0]);return false;\"";
      $n= $i/2;
      $x->fotky.=
        " <li class='foto' data-foto-n='$n' $title $menu style='background-image:url($mini)'>"
        . "$chk$tit</li>";
    }
  }
  $x->fotky.= "</ul>";
  return $x;
}
# ----------------------------------------------------------------------------------==> . save fotky
function save_fotky($x,$perm=null) {
  $fid= $x->fid;
  $autor= mysql_real_escape_string($x->autor);
  $nadpis= mysql_real_escape_string($x->nadpis);
  $psano= sql_date1($x->psano,1);
  $editors= $x->editors ? implode(',',(array)$x->editors) : '';
  $set_seznam= '';
//  $text= select('seznam',"xfotky","id_xfotky='$fid'");
//  // přeskládání textu podle order
//  $nt= array();
//  $t= explode(',',$text);
//                                                         debug($t,$perm);
//  $p= explode(',',$perm);
//  for ($i= 0; $i<count($p); $i++) {
//    $nt[$i*2]=   $t[$p[$i]*2];
//    $nt[$i*2+1]= $t[$p[$i]*2+1];
//  }
//  $text2= implode(',',$nt);
//                                                         display($text);
//                                                         display($text2);
//  $set_text= $text==$text2 ? '' : ",text='$text2'";
  // zápis
  query("UPDATE xfotky
         SET editors='$editors',nazev='$nadpis',kdy='$psano',autor='$autor'
           $set_seznam
         WHERE id_xfotky='$fid'");
  return 1;
}
# --------------------------------------------------------------------------------==> . upload fotky
# originál fotky je již ve složce inc/f/fid mechanismem label.drop
# vytvoří miniatury a přidá je do složky, název přidá do xfotky[fid].seznam
function minify_fotky($file,$fid) { 
  global $ezer_path_root;
  $path= "$ezer_path_root/inc/f/$fid";
  // přidání názvu fotky do záznamu v tabulce
  query("UPDATE xfotky SET seznam=CONCAT(seznam,'$file,,') WHERE id_xfotky=$fid");
  // doplnění thumbs ..$file
  $w= $h= 80;
  $ok= x_resample("$path/$file","$path/..$file",$w,$h) ? 'ok' : 'ko';
  // ZMENŠENINA .file
  if ( !is_file("$path/.$file") ) // pokud zmenšenina neexistuje, vynuť její vytvoření
    $width0= $height0= -1;
  else // pokud existuje zmenšenina, podívej se na její rozměry
    list($width0, $height0, $type0, $attr0)= getimagesize("$path/.$file");
  // zajisti zmenšeninu
  if ( $width!=$width0 || $height!=$height0 ) {
    // je požadována změna rozměrů, transformuj obrázek
    $width= $height= 1920;
    $w= $width; $h= $height;
    $ok= x_resample("$path/$file","$path/.$file",$w,$h,0,1) ? 'ok' : 'ko';
  }
end:
  return $file;
}
# ------------------------------------------------------------------------------------- delete fotky
function delete_fotky($uid,$foto) {
//  global $ezer_path_root, $ezer_root;
//  // zrušení odkazu na fotku
//  $text= select('text','tx_gncase_part',"uid=$uid");
//  $fotky= explode(',',$text);
//  while (1) {
//    $n= array_search($foto,$fotky);
//    if ( $n===false ) break;
//    unset($fotky[$n]); unset($fotky[$n+1]);
//  }
//  $text= implode(',', $fotky);
//  query("UPDATE xfotky SET text='$text' WHERE uid=$uid");
//  // smazání fotky
//  $path= "$ezer_path_root/fileadmin/photo/$uid";
//  unlink("$path/$foto"); unlink("$path/.$foto"); unlink("$path/..$foto");
  return 1;
}
# --------------------------------------------------------------------------------------- note fotky
function note_fotky($uid,$foto0,$note) {
//  // načtení
//  $text= select('text',"setkani.tx_gncase_part","uid='$uid'");
//  $f= array();
//  $t= explode(',',$text);
//  for ($i= 0; $i<count($t)-1; $i+=2) {
//    $foto= $t[$i]; $desc= $t[$i+1];
//    $f[$foto]= $desc;
//  }
//  // změna
//  $f[$foto0]= $note;
//  // zápis
//  $text= '';
//  foreach($f as $foto=>$desc) {
//    $text.= "$foto,$desc,";
//  }
//  query("UPDATE xfotky SET text='$text' WHERE uid='$uid'");
  return 1;
}
# ----------------------------------------------------------------------------------==> . sort fotky
# seřadí fotky podle jména souboru
function sort_fotky($uid) { trace();
//  $text= select('text',"setkani.tx_gncase_part","uid='$uid'");
//  $f= array();
//  $t= explode(',',$text);
//                                                        debug($t);
//  for ($i= 0; $i<count($t)-1; $i+=2) {
//    $foto= $t[$i]; $desc= $t[$i+1];
//                                                        display("$i,$foto,$desc");
//    $f[$foto]= $desc;
//  }
//                                                        debug($f);
//  ksort($f);
//                                                        debug($f);
//  $text= '';
//  foreach($f as $foto=>$desc) {
//    $text.= "$foto,$desc,";
//  }
//                                                        display($text);
//  // zápis
//  query("UPDATE setkani.tx_gncase_part SET text='$text' WHERE uid='$uid'");
  return 1;
}
# ----------------------------------------------------------------------------------==> . move fotky
# přesune fotky s pořadími uvedenými v lst z part.uid=from do part.uid=to
function move_fotky($from,$to,$checked) { trace();
//  global $ezer_path_root, $ezer_root;
//  $path_from= "$ezer_path_root/fileadmin/photo/$from";
//  $text_from= select('text',"setkani.tx_gncase_part","uid='$from'");
//  $path_to= "$ezer_path_root/fileadmin/photo/$to";
//  $text_to= select('text',"setkani.tx_gncase_part","uid='$to'");
//  // zajisti cílovou složku
//  if ( !is_dir($path_to) ) {
//    $ok= mkdir($path_to,0777);
//    if (!$ok) { fce_warning("POZOR nepodařilo se vytvořit složku pro fotografie ($path_to)"); goto end;}
//  }
//  // úprava seznamů a fotek
//  $add= $sub= '';
//  $t= explode(',',$text_from);
//  $p= explode(',',$checked);
//  for ($i= 0; $i<count($p); $i++) {
//    if ( $p[$i]>0 ) {
//      $pi= $p[$i]-1;
//      $foto= $t[$pi*2]; $desc= $t[$pi*2+1];
//      $add.= "$foto,$desc,";
//      // přesun fotek mezi složkami
//                                                        display("copy($path_from/$foto,$path_to/$foto)");
//      foreach (array($foto,".$foto","..$foto") as $f) {
//        if ( file_exists("$path_from/$f") ) {
//          copy("$path_from/$f","$path_to/$f");
//          unlink("$path_from/$f");
//        }
//      }
//    }
//    else {
//      $pi= -$p[$i]-1;
//      $foto= $t[$pi*2]; $desc= $t[$pi*2+1];
//      $sub.= "$foto,$desc,";
//    }
//  }
//  $text_from= $sub;
//  $text_to= $text_to.$add;
//                                                        display("from=$text_from");
//                                                        display("to=$text_to");
//  // zápis
//  query("UPDATE setkani.tx_gncase_part SET text='$text_from' WHERE uid='$from'");
//  query("UPDATE setkani.tx_gncase_part SET text='$text_to' WHERE uid='$to'");
//end:
  return 1;
}
# ----------------------------------------------------------------------------------==> . upload url
# zapíše soubor zadaný urldo fileadmin/img/cid
function upload_url($url,$cid) { trace();
//  global $ezer_path_root, $ezer_root;
//  $ret= (object)array('err'=>'');
//  // zajisti složku
//  $path= "$ezer_path_root/fileadmin/img/$cid";
//  if ( !is_dir($path) ) {
//    $ok= mkdir($path,0777);
//    if (!$ok) { $ret->err= "POZOR nepodařilo se vytvořit složku pro soubor ($path)"; goto end;}
//  }
//  // zjisti velikost a zda je dost místa
//  $free= floor(disk_free_space("/")/(1024*1024));
//  $headers= get_headers($url, 1);
//                                                        debug($headers,$url);
//  $size= $headers["Content-Length"];
//  if ( is_array($size) ) $size= $size[count($size)-1];
//  $size= ceil($size/(1024*1024));
//                                                        display("volných $free MB, soubor má $size MB");
//  if ( 5*$size > $free ) {
//    $ret->err= "Na serveru je $free volných MB - to je dost málo (soubor má $size MB)"; goto end; }
//
//  // zjisti a uprav jméno
//  $disp= $headers["Content-Disposition"];
//  $ok= preg_match("/attachment; filename=\"([^\"]+)\"/",$disp,$m);
//                                                        debug($m,$ok);
//  if (!$ok) { $ret->err= "POZOR soubor má nečekaný popis ($disp)"; goto end;}
//  $file= utf2ascii($m[1],'.');
//  $pathfile= "$path/$file";
//                                                        display("file=$file");
//  // soubor přepíšeme pokud existuje
//  if ( file_exists($pathfile) ) unlink($pathfile);
//  // zkopíruj do souboru
//  if (!copy($url,$pathfile)) { $ret->err= "POZOR soubor $file se nepodařilo přečíst"; goto end; }
//end:
//  return $ret;
}
# ----------------------------------------------------------------------------------==> . upload zip
function upload_zip($url,$uid,$cid) { trace();
//  global $ezer_path_root;
//  $ret= (object)array('err'=>'');
//  $free= floor(disk_free_space("/")/(1024*1024));
//  $headers= get_headers($url, 1);
//  $size= $headers["Content-Length"];
//  if ( is_array($size) ) $size= $size[count($size)-1];
//  $size= ceil($size/(1024*1024));
//                                                        display("volných $free MB, zip má $size MB");
//  if ( 5*$size > $free ) {
//    $ret->err= "Na serveru je $free volných MB - to je dost málo (soubor má $size MB)"; goto end; }
//  $path= "$ezer_path_root/fileadmin/photo/$uid";
//
//  // zkopíruj do dočasného souboru
//  if ( file_exists("tmp_file.zip") ) unlink("tmp_file.zip");
//  $tmp= "tmp_file.zip";
//  if (!@copy($url,$tmp)) { $ret->err= "POZOR archiv se nepodařilo přečíst"; goto end; }
//  // zajisti složku
//  if ( !is_dir($path) ) {
//    $ok= mkdir($path,0777);
//    if (!$ok) { $ret->err= "POZOR nepodařilo se vytvořit složku pro fotografie ($path)"; goto end;}
//  }
//  // otevři archiv
//  $z= new ZipArchive;
//  $ok= $z->open($tmp);
//  if ( $ok===true ) {
//                                                        display("files={$z->numFiles}");
//    for ($i=0; $i<$z->numFiles;$i++) {
//      $f= $z->statIndex($i);
//      $file0= $f['name'];
//      $file= utf2ascii($file0,'.');
//      if ( $file0!=$file ) {
//        $z->renameName($file0,$file);
//      }
//      $z->extractTo($path,array($file));
//      list($width, $height, $type, $attr)= getimagesize("$path/$file");
//      // file na HD 1080
//      if ( $width>1920 || $height>1080 ) {
//        $w= 1920; $h= 1080;
//        $ok= x_resample("$path/$file","$path/$file",$w,$h) ? 'ok' : 'ko';
//      }
//      // .file na HD 720
//      if ( $width>1280 || $height>720 ) {
//        $w= 1280; $h= 720;
//        $ok= x_resample("$path/$file","$path/.$file",$w,$h) ? 'ok' : 'ko';
//      }
//      else
//        copy("$path/$file","$path/.$file");
//      // doplnění thumbs ..$file
//      $w= $h= 80;
//      $ok= x_resample("$path/.$file","$path/..$file",$w,$h) ? 'ok' : 'ko';
//      // přidání názvu fotky do záznamu v tabulce
//      query("UPDATE tx_gncase_part SET text=CONCAT('$file,,',text) WHERE uid=$uid");
//    }
//    $z->close();
//  }
//  else { $ret->err= "POZOR archiv se nepodařilo otevřít (chyba:$ok)"; goto end;}
//end:
//  // uvolni prostor
//  if ( file_exists("tmp_file.zip") ) unlink("tmp_file.zip");
//  return $ret;
}
# -------------------------------------- x_resample
// změna velikosti obrázku typu gif, jpg nebo png (na jiných zde není realizována)
//   $source, $dest -- cesta k souboru, ktery chcete zmensit  a cesta, kam zmenseny soubor ulozit
//   $maxWidth, $maxHeight  -- maximalni sirka a vyska změněného obrazku
//     hodnota 0 znamena, ze sirka resp. vyska vysledku muze byt libovolna
//     hodnoty 0,0 vedou na kopii obrázku
//     $copy_bigger==1 vede na kopii (např.miniatury) místo na zvětšení
//   výsledek 0 - operace selhala
function x_resample($source, $dest, &$width, &$height,$copy_bigger=0,$use_min=0) {
  global $gn;
  $maxWidth= $width;
  $maxHeight= $height;
  $ok= 1;
//                               display("... RESAMPLE($source, $dest, &$width, &$height,$copy_bigger)<br>");
  // zjistime puvodni velikost obrazku a jeho typ: 1 = GIF, 2 = JPG, 3 = PNG
  list($origWidth, $origHeight, $type)=@ getimagesize($source);
  if ( !$type ) $ok= 0;
  if ( $ok ) {
    if ( !$maxWidth ) $maxWidth= $origWidth;
    if ( !$maxHeight ) $maxHeight= $origHeight;
    // nyni vypocitam pomer změny
    $pw= $maxWidth / $origWidth;
    $ph= $maxHeight / $origHeight;
    $p= $use_min ? min($pw, $ph) : max($pw, $ph);
    // vypocitame vysku a sirku změněného obrazku - vrátíme ji do výstupních parametrů
    $newWidth = (int)round($origWidth * $p);
    $newHeight = (int)round($origHeight * $p);
    $width= $newWidth;
    $height= $newHeight;
    if ( ($pw == 1 and $ph == 1) or ($copy_bigger and $p>1) ) {
      // jenom zkopírujeme
      copy($source,$dest);
    }
    else {
      // zjistíme velikost cíle - abychom nedělali zbytečnou práci
      $destWidth= $destHeight= -1; $ok= 2; // ok=2 -- nic se nedělalo
      if ( file_exists($dest) ) list($destWidth, $destHeight)= getimagesize($dest);
      if ( $destWidth!=$newWidth || $destHeight!=$newHeight ) {
        // vytvorime novy obrazek pozadovane vysky a sirky
        #if ( $CONST['GraphicTool']['name']=='GD' ) { // GD Library
          // karel: nezapomeň ještě taky, že když zmenšuješ GIF s průhlednou barvou, musíš touto barvou nejprve vyplnit cílový obrázek a nastavit ji jako průhlednou
          $image_p= ImageCreateTrueColor($newWidth, $newHeight);
          // otevreme puvodni obrazek se souboru
          switch ($type) {
          case 1: $image= ImageCreateFromGif($source); break;
          case 2: $image= ImageCreateFromJpeg($source); break;
          case 3: $image= ImageCreateFromPng($source); break;
          }
          // okopirujeme zmenseny puvodni obrazek do noveho
          if ( $maxWidth || $maxHeight )
            ImageCopyResampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
          else
            $image_p= $image;
          // ulozime
          $ok= 0;
          switch ($type) {
          case 1: /*ImageColorTransparent($image_p);*/ $ok= ImageGif($image_p, $dest);  break;
          case 2: $ok= ImageJpeg($image_p, $dest);  break;
          case 3: $ok= ImagePng($image_p, $dest);  break;
          }
        #}
        #elseif ( $CONST['GraphicTool']['name']=='ImageMagic' ) { // ImageMagic
        #  // proveď externí program
        #  $mode= $destWidth<100 ? " +contrast -sharpen 10 " : ''; // malým obrázkům přidej kontrast a zaostři je
        #  $cmd= "convert -geometry {$newWidth}x$newHeight $mode $source $dest";
        #  $ok= system("{$CONST['GraphicTool']['binary']}$cmd") ? 1 : 0;
        #}
      }
    }
  }
  return $ok;
}
/** ===================================================================================> setkani.org */
# --------------------------------------------------------------------------------------------- TEST
# přidá do menu další element
function TEST() {
  global $abs_root;
  $url= 'https://www.setkani.org/fileadmin/photo/5880/.p1450211.JPG';
  $path= '/home/users/gandi/chlapi.online/web/inc/g';
  $path= "$abs_root/inc/g";
  mkdir("$path/2");
  $img= "$path/2/..p1070086.jpg";
  // file
  file_put_contents($img, file_get_contents($url));
  return $msg;
}
# ----------------------------------------------------------------------------------- menu copy_foto
# zkopíruje chybějící fotky ze setkani.org/filedamin/photo do chlapi.cz/inc/f
# je voláno po založení záznamu pro fotky v menu_copy_elem('F',...)
function menu_copy_foto($fid,$test=1) {
  global $ezer_local, $abs_root;
  $fileadmin= $ezer_local 
      ? "http://setkani.bean:8080/fileadmin"
      : "https://www.setkani.org/fileadmin";
//  $fileadmin= "https://www.setkani.org/fileadmin";
  $msg= '?';
  // složka pro fotky
  list($lst,$pid)= select("seznam,path","xfotky","id_xfotky='$fid'");
  $path= "$abs_root/inc/f";
  if ( !file_exists("$path/$fid") ) {
    mkdir("$path/$fid");
  }
  $url= "$fileadmin/photo/$pid";
  // zcizit fotky
  $fs= explode(',',$lst);
  $last= count($fs)-1;
  for ($i= 0; $i<$last; $i+=2) {
    foreach (array("..$fs[$i]",".$fs[$i]"/*,$fs[$i]*/) as $f) {  // vynecháme originály
      $ext= strtolower(substr($f,-3));
      // pokud je to fotka
      if ( $ext=='jpg' || $ext=='png' || $ext=='gif' ) {
        $img= "$path/$fid/$f";
        // ještě nebyla zkopírována
        if ( !file_exists($img) ) {
          // a existuje na serveru
          if ( $test ) {
            $msg.= " +$f ";
          }
          else {
            $fh= get_headers("$url/$f");
            if (strpos($fh[0],'404')== false ) {
              $ximg= file_get_contents("$url/$f");
              file_put_contents($img, $ximg);
            }
          }
        }
      }
    }
  }
                                                display($msg);
  return $msg;
}
# ----------------------------------------------------------------------------------- menu copy_elem
# zkopíruje ze setkani.org článek nebo knihu
function menu_copy_elem($co,$pid,$mid,$test=true) {
  global $y, $ezer_local, $abs_root;
  $fileadmin= $ezer_local 
      ? "http://setkani.bean:8080/fileadmin"
      : "https://www.setkani.org/fileadmin";
  $msg= '?';
  $elems= '';
  $aid= 0; 
  $kid= 0; 
  $xid= 0;
  $fid= 'f';
  $typ= 'aclanek';

  // zjisti části - u knihy kapitoly - u článku, pokud jsou, vytvoř knihu
  ask_server((object)array('cmd'=>'kapitoly','pid'=>$pid));
                                                      display("pids/$pid=$y->pids");
  $pids= explode(',',$y->pids);
  // pokud je více části - vytvoř knihu
  if ( $co=='akce' ) {
    $aid= 'a';
    if ( $test ) {
      $msg.= " akce: ";
    }
    else {
      query("INSERT INTO xakce () VALUES ()");
      $aid= mysql_insert_id();
    }
  }
  elseif ( count($pids)>1 ) {
    $kid= 'k';
    if ( $test ) {
      $msg.= " kniha: ";
    }
    else {
      query("INSERT INTO xkniha () VALUES ()");
      $kid= mysql_insert_id();
    }
    $typ= 'akniha';
  }
  // projdi části 
  foreach ($pids as $xpid) {
    $x= substr($xpid,0,1);
    $pid= substr($xpid,1);
    switch ( $x ) {
      
    case 'F': // ------------ fotky - bez kopírování souborů
      if ( !$xid ) { 
        if ( $test ) display("<b style='color:red'> fotky nesmí být jako první </b>");
        else fce_error("fotky nesmí být jako první"); 
      }
      ask_server((object)array('cmd'=>'clanek','pid'=>$pid));
      $a= $y->autor; $n= $y->nadpis; $lst= $y->obsah; $p= sql_date($y->psano,1);
      if ( $test ) {
        $msg.= " fotky/$xpid  ";
      }
      else {
        query("INSERT INTO xfotky (id_xclanek,autor,nazev,kdy,seznam,path) "
            . "VALUES ($xid,'$a','$n','$p','$lst','$pid')");
        $fid= mysql_insert_id();
      }
      break;
      
    case 'C': // ------------ nadpis
    case 'E': // ------------ kapitola
    case 'A': // ------------ ...
    case 'D': // ------------ ...
      ask_server((object)array('cmd'=>'clanek','pid'=>$pid));
      // uprav odkazy
      $obsah= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$y->obsah);
      if ( $co=='akce' ) {  // ------------------------------ akce
        $oddo= '';
        $skill= 0;
        if ( $x=='A' ) {    
          // hlavička
          $oddo= datum_oddo($y->od,$y->do);
          $skill= in_array($y->fe_groups,array(4,6)) ? 8 : 0;
          $tit= "$y->nadpis";
          if ( $test ) {
            $msg.= " akce.nazev=$oddo:$tit  ";
          }
          else {
            query("UPDATE xakce SET nazev='$tit',datum_od='$y->od',datum_do='$y->do' "
                . "WHERE id_xakce=$aid");
          }
        }
        // hlavní článek
        $clanek= "<h1>$oddo $y->nadpis</h1>$obsah";
        $clanek= str_replace("'","\\'",$clanek);
        if ( $test ) {
          $xid= 'x';
          $msg.= " akce/$xpid:$skill  ";
        }
        else {
          query("INSERT INTO xclanek (web_text,web_skill) VALUES ('$clanek',$skill)");
          $xid= mysql_insert_id();
        }
        $elems= ($elems ? "$elems;" : '')."aclanek=$xid";
      }
      else { // $co=setkani_* ------------------------------- článek
        // případně zapiš celkový název knihy
        if ( $x=='C' && $kid ) {
          $tit= "$y->autor: $y->nadpis";
          $tit= str_replace("'","\\'",$tit);
          if ( $test ) {
            $msg.= " kniha.nazev=$tit  ";
          }
          else {
            query("UPDATE xkniha SET nazev='$tit' WHERE id_xkniha=$kid");
          }
        }
        $clanek= "<h1>$y->nadpis</h1>$obsah";
        $clanek= str_replace("'","\\'",$clanek);
        if ( $test ) {
          $xid= 'x';
          $msg.= " clanek/$xpid  ";
        }
        else {
          query("INSERT INTO xclanek (web_text) VALUES ('$clanek')");
          $xid= mysql_insert_id();
        }
        $elems= ($elems ? "$elems;" : '')."aclanek=$xid";
      }
      break;
    default:
      if ( $test )
        display("chybný tag kapitoly $xpid");
      else
        fce_error("chybný tag kapitoly $xpid");
    }
  }
  $elem= select("elem","menu","wid=2 AND mid=$mid");
  if ( $aid ) {
    // zapiš jen do hlavičky akce
    if ( $test ) {
      $msg.= " akce.xelems='$elems' ";
    }
    else {
      // přidej do akce.xelems
      query("UPDATE xakce SET xelems='$elems' WHERE id_xakce=$aid");
    }
  }
  elseif ( $kid ) {
    // zapiš do menu a do knihy
    $elem= "akniha=$kid" . ($elem ? ";$elem" : '');
    if ( $test ) {
      $msg.= " kniha.xelems='$elems' ";
      $msg.= " menu.elem/$mid='$elem' ";
    }
    else {
      // přidej do menu.elem, kniha.elem
      query("UPDATE xkniha SET xelems='$elems' WHERE id_xkniha=$kid");
      query("UPDATE menu SET elem='$elem' WHERE wid=2 AND mid=$mid");
    }
  }
  else {
    // zapiš jen do menu 
    $elem= "aclanek=$xid" . ($elem ? ";$elem" : '');
    if ( $test ) {
      $msg.= " menu.elem/$mid='$elem' ";
    }
    else {
      // přidej do menu.elem
      query("UPDATE menu SET elem='$elem' WHERE wid=2 AND mid=$mid");
    }
  }
                                                      display($msg);
  return $msg;
}
/** ===========================================================================================> WEB */
# ------------------------------------------------------------------------------------ menu add_elem
# přidá do menu další element, resp. pro xakce vytvoří novou akci roku daného $mid
function menu_add_elem($mid,$table,$first=0,$id_user=0) {
  switch ($table) {
  case 'xakce':        // ---------------------------------- nová akce roku mid
    query("INSERT INTO xclanek (editors) VALUES ('$id_user')");
    $idc= mysql_insert_id();
    log_obsah('i','c',$idc);
    $ymd= "$mid-12-31";
    query("INSERT INTO xakce (xelems,datum_od,datum_do) VALUES ('aclanek=$idc','$ymd','$ymd')");
    break;
  case 'xkniha':       // ---------------------------------- nová kniha s prvním článkem
    $elem= select("elem","menu","wid=2 AND mid=$mid");
    query("INSERT INTO xclanek (editors) VALUES ('$id_user')");
    $cid= mysql_insert_id();
    log_obsah('i','c',$cid);
    query("INSERT INTO xkniha (xelems) VALUES ('aclanek=$cid')");
    $kid= mysql_insert_id();
    if ( $first )
      $elem= "xkniha=$kid" . ($elem ? ";$elem" : '');
    else
      $elem= ($elem ? "$elem;" : '') . "xkniha=$kid";
    query("UPDATE menu SET elem='$elem' WHERE wid=2 AND mid=$mid");
    break;
  case 'xkniha.elem':  // ---------------------------------- nový článek knihy 
    $elem= select("xelems","xkniha","id_xkniha=$mid");
    query("INSERT INTO xclanek () VALUES ()");
    $id= mysql_insert_id();
    log_obsah('i','c',$id);
    if ( $first )
      $elem= "aclanek=$id" . ($elem ? ";$elem" : '');
    else
      $elem= ($elem ? "$elem;" : '') . "aclanek=$id";
    query("UPDATE xkniha SET xelems='$elem' WHERE id_xkniha=$mid");
    break;
  case 'xclanek':     // ----------------------------------- nový článek
    $elem= select("elem","menu","wid=2 AND mid=$mid");
    query("INSERT INTO $table () VALUES ()");
    $id= mysql_insert_id();
    log_obsah('i','c',$id);
    if ( $first )
      $elem= "$table=$id" . ($elem ? ";$elem" : '');
    else
      $elem= ($elem ? "$elem;" : '') . "$table=$id";
    query("UPDATE menu SET elem='$elem' WHERE wid=2 AND mid=$mid");
    break;
  }
  return 1;
}
# ----------------------------------------------------------------------------------- menu chng_elem
# přidá do menu další element
function menu_chng_elem($mid,$typ1,$id,$typ2) {
  $desc= select("elem","menu","wid=2 AND mid=$mid");
  $elems= explode(';',$desc);
  for ($i= 0; $i<count($elems); $i++) {
    list($typx,$idx)= explode('=',$elems[$i]);
    if ( $typx==$typ1 && $idx==$id ) {
      $elems[$i]= "$typ2=$id";
      $desc= implode(';',$elems);
      query("UPDATE menu SET elem='$desc' WHERE wid=2 AND mid=$mid");
      break;
    }
  }
  return 1;
}
# ---------------------------------------------------------------------------------- menu shift_elem
# posune element o jedno dolů (pro down=0 nahoru)
function menu_shift_elem($typ,$mid,$id,$down) {
  // zjistíme všechna menu na stejné úrovni
  $elems= select("elem","menu","mid=$mid");
                                                      display($elems);
  $ms= explode(';',$elems);
  $elem= "$typ=$id";
  $i= array_search($elem,$ms);
  $last= count($ms)-1;
  if ( $down ) { // dolů
    if ( $i<$last ) {
      $ms[$i]= $ms[$i+1];
      $ms[$i+1]= $elem;
    }
  }
  else { // nahoru
    if ( $i>0 ) {
      $ms[$i]= $ms[$i-1];
      $ms[$i-1]= $elem;
    }
  }
  $elems= implode(';',$ms);                 
                                                      display($elems);
  query("UPDATE menu SET elem='$elems' WHERE wid=2 AND mid=$mid");
  return 1;
}
# --------------------------------------------------------------------------------------- menu shift
# posune menu o jedno dolů (pro down=0 nahoru)
function menu_shift($mid,$down) {
  // zjistíme všechna menu na stejné úrovni
  list($mid_top,$typ)= select("mid_top,abs(typ)","menu","mid=$mid");
  $cond= $typ==2 && $mid_top ? "mid_top=$mid_top" : (
    $typ==1 || $typ==0 ? "typ=$typ" : 0 );
  $ms= select("GROUP_CONCAT(mid ORDER BY rank)","menu","wid=2 AND $cond");
//                                              display("x:$ms");
  $ms= explode(',',$ms);
  $i= array_search($mid,$ms);
  $last= count($ms)-1;
  if ( $down ) { // dolů
    if ( $i<$last ) {
      $ms[$i]= $ms[$i+1];
      $ms[$i+1]= $mid;
    }
  }
  else { // nahoru
    if ( $i>0 ) {
      $ms[$i]= $ms[$i-1];
      $ms[$i-1]= $mid;
    }
  }
//                                              display("y:".implode(',',$ms));
  foreach ($ms as $i=>$mi) {
    $i1= $i+1;
    query("UPDATE menu SET rank=$i1 WHERE wid=2 AND mid=$mi");
  }
  return 1;
}
# ---------------------------------------------------------------------------------------- menu save
function menu_save($wid,$tree) {
  $walk= function ($node,$delv='') use (&$walk) {
    $n= 0;
    if ( isset($node->prop->data->ref) && $node->prop->data->mid>=0 ) {
      $fields= $values= $del= "";
      foreach($node->prop->data as $field => $v) {
        $values.= "$del\"$v\"";
        $fields.= "$del$field";
        $del= ',';
      }
      $qry= "INSERT INTO menu ($fields) VALUES ($values)";
      query($qry,'setkani');
      $n++;
    }
    if ( isset($node->down) ) {
      foreach($node->down as $child) {
        if ( !$delv ) 
          $delv= $value ? ',' : '';
        $n+= $walk($child,$delv);
      }
    }
    return $n;
  };
  query("DELETE FROM menu_save WHERE wid=$wid",'setkani');
  query("INSERT INTO menu_save SELECT * FROM menu WHERE wid=$wid",'setkani');
  query("DELETE FROM menu WHERE wid=$wid",'setkani');
  $len= strlen($tree);
  $m= json_decode($tree);
  $n= $walk($m);
  return "$n položek menu pro $wid";
}
# ---------------------------------------------------------------------------------------- menu undo
function menu_undo($wid) {
  query("DELETE FROM menu WHERE wid=$wid",'setkani');
  query("INSERT INTO menu SELECT * FROM menu_save WHERE wid=$wid",'setkani');
  return 1;
}
# ---------------------------------------------------------------------------------------- menu tree
function menu_tree($wid) {
  //{prop:°{id:'ONE'},down:°[°{prop:°{id:'TWO'}},°{prop:°{id:'THREE'}}]}
  $data= (object)array('mid'=>0);
  $menu= 
    (object)array(
      'prop' => (object)array('id'=>'menu'),
      'down' => array(
        (object)array(
          'prop' => (object)array('id'=>'top menu','data'=>$data),
          'down' => array()
        ),    
        (object)array(
          'prop' => (object)array('id'=>'main menu','data'=>$data),
          'down' => array()
        )
      )
    );    
  $mn= mysql_qry("SELECT * FROM menu WHERE wid=$wid ORDER BY typ,mid_top,rank",
      0,0,0,'setkani');
  while ( $mn && ($m= mysql_fetch_object($mn)) ) {
    $mid= $m->mid;
    $mid_top= $m->mid_top;
    $typ= $m->typ;
    $nazev= $m->ref;
    $nazev= "$mid.$nazev";
    if ( $typ==0 ) {
      $node= (object)array('prop'=>(object)array('id'=>$nazev,'data'=>$m));
      $menu->down[0]->down[]= $node;
    }
    elseif ( $typ==1 ) {
      $node= (object)array('prop'=>(object)array('id'=>$nazev,'data'=>$m));
      $menu->down[1]->down[]= $node;
    }
    elseif ( $typ==2 ) {
      foreach ( $menu->down[1]->down as $i => $sm ) {
        if ( $sm->prop->data->mid===$mid_top ) {
          $node= (object)array('prop'=>(object)array('id'=>$nazev,'data'=>$m));
          $sm->down[]= $node;
          break;
        }
      } 
    }
  }
  return $menu;
}
# --------------------------------------------------------------------------------------- datum akce
function datum_akce($from,$until) {
  date_default_timezone_set('Europe/Prague');
  if ( $from == $until ) {  //zacatek a konec je stejny den
    $datum_dmy= date(date('Y',$from)==date('Y',time()) ? 'j.n.' : 'j.n.Y',$from);
  }
  elseif ( date('n.Y',$from)==date('n.Y',$until) ) { //zacatek a konec je stejny mesic
    $datum_dmy= date('j',$from).".- ".date('j',$until)
      .".".date(date('Y',$from)==date('Y',$until) && date('Y',$from)==date('Y',time()) ? 'n.' : 'n.Y',$until);
  }
  else { //ostatni pripady
    $datum_dmy= date('j.n.',$from)."- ".date('j.n',$until)
      .".".(date('Y',$from)==date('Y',$until) && date('Y',$from)==date('Y',time()) ? '' : date('Y',$until));
  }
  return $datum_dmy;
}
# ------------------------------------------------------------------------------------------ seradit
# seřadí články na stránce podle abecedy
function seradit($ids,$typ) {
  $sorting= 0;
  $rc= mysql_qry(
    $typ=='knihy' ? "
      SELECT c.uid
      FROM setkani.tx_gncase AS c
        JOIN setkani.tx_gncase_part AS p ON p.cid=c.uid
      WHERE !c.deleted AND !c.hidden AND c.pid IN ($ids) AND tags='C'
      ORDER BY p.author DESC,p.title DESC" : (
    $typ=='tance' ? "
      SELECT c.uid
      FROM setkani.tx_gncase AS c
        JOIN setkani.tx_gncase_part AS p ON p.cid=c.uid
      WHERE !c.deleted AND !c.hidden AND c.pid IN ($ids)
      ORDER BY p.title DESC" : ''
  ));
  while ( $rc && (list($uid)= mysql_fetch_row($rc)) ) {
    $sorting++;
    query("UPDATE setkani.tx_gncase SET sorting=$sorting WHERE uid=$uid");
  }
  return 1;
}
# ------------------------------------------------------------------------------------------- ip get
# zjištění klientské IP
function ip_get() {
  return isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
}
# ----------------------------------------------------------------------------------------- ip watch
function ip_watch(&$my_ip,$log=0) {
  // ověříme známost počítače - zjištění klientské IP
  $my_ip= ip_get();
  // zjištění dosud povolených IP
  $ips= select("GROUP_CONCAT(ips)","_user","ips!=''");
  // kontrola
  $ips= str_replace(' ','',$ips);
  $ip_ok= strpos(",$ips,",",$my_ip,")!==false;
  if ( $log && !$ip_ok ) {
    // zapiš pokus o neautorizovaný přístup
    $day= date('Y-m-d'); $time= date('H:i:s');
    $browser= $_SERVER['HTTP_USER_AGENT'];
    $qry= "INSERT _touch (day,time,user,module,menu,msg)
           VALUES ('$day','$time','','error','ip?','|$my_ip||$browser')";
    mysql_query($qry);
  }
  return $ip_ok;
}
?>
