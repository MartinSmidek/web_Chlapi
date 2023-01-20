<?php

// ---------------------------------------------------------------------------------------------- //
// funkce aplikace chlapi.online pro mode CMS                                                     //
//                                                                                                //
// CMS/Ezer                                             (c) 2018 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //

/** ========================================================================================> COMMON */
function escape_string($inp) {
  return str_replace(
      array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), 
      array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
}
/** ===========================================================================================> GIT */
/*
# ----------------------------------------------------------------------------------------- git make
# provede git par.cmd>.git.log a zobrazí jej
function git_make($par) {
  global $abs_root;
  $cmd= $par->cmd;
  $msg= "";
  // proveď operaci
  switch ($par->op) {
  case 'cmd':
    $state= 0;
    // zruš starý obsah .git.log
    $f= @fopen("$abs_root/docs/.git.log", "r+");
    if ($f !== false) {
        ftruncate($f, 0);
        fclose($f);
    }
    if ( $par->folder=='cms') {
      $exec= "git {$par->cmd}>$abs_root/docs/.git.log";
      exec($exec,$lines,$state);
    }
    else if ( $par->folder=='ezer') {
      chdir("ezer3.1");
      $exec= "git {$par->cmd}>$abs_root/docs/.git.log";
      exec($exec,$lines,$state);
      chdir($abs_root);
    }
    debug($lines,$state);
    $msg= "$state:$exec<hr>";
  case 'show':
    $msg.= file_get_contents("$abs_root/docs/.git.log");
    $msg= nl2br(htmlentities($msg));
    break;
  }
  return $msg;
}
*/
# ----------------------------------------------------------------------------------------- git make
# provede git par.cmd>.git.log a zobrazí jej
# fetch pro lokální tj. vývojový server nepovolujeme
function git_make($par) {
  global $abs_root;
  $bean= preg_match('/bean/',$_SERVER['SERVER_NAME'])?1:0;
                                                    display("bean=$bean");
  $msg= "";
  // proveď operaci
  switch ($par->op) {
  case 'cmd':
    $cmd= $par->cmd;
    $folder= $par->folder;
    $workdir= $folder=='ezer' ? "$abs_root/ezer3.1" : null;
    $lines= '';
    if ( $cmd=='fetch' && $bean) {
      $msg= "na vývojových serverech (*.bean) příkaz fetch není povolen ";
      break;
    }
    $state= 0;
    // zruš starý obsah .git.log
    $f= @fopen("$abs_root/docs/.git.log", "r+");
    if ($f !== false) {
        ftruncate($f, 0);
        fclose($f);
    }
    $exec= "git $cmd";
    $answer= execute($exec,$workdir);
    debug($answer,"execute($exec)");
    $msg.= "<u>code</u>: {$answer['code']}\n";
    $msg.= $answer['out'] ? "<u>output</u>: {$answer['out']}\n" : "<u>no output</u>\n";
    $msg.= $answer['err'] ? "<u>error</u>: {$answer['err']}" : "<u>no error</u>";
    file_put_contents("$abs_root/docs/.git.log",$msg);
    // po fetch ještě nastav shodu s github
    if ( $cmd=='fetch') {
      $msg.= "$state:$exec\n";
      $exec= "reset --hard origin/".($folder=='ezer'?'ezer3.1':'master');
      $answer= execute($exec,$workdir);
      debug($answer,"execute($exec)");
      $msg.= "<u>code</u>: {$answer['code']}\n";
      $msg.= $answer['out'] ? "<u>output</u>: {$answer['out']}\n" : "<u>no output</u>\n";
      $msg.= $answer['err'] ? "<u>error</u>: {$answer['err']}" : "<u>no error</u>";
      file_put_contents("$abs_root/docs/.git.log",$msg);
    }
    break;
  case 'show':
    $msg.= file_get_contents("$abs_root/docs/.git.log");
    break;
  }
  $msg= nl2br($msg);
  $msg= "<i>Synology: musí být spuštěný Git Server (po aktualizaci se vypíná)</i><hr>$msg";
  return $msg;
}
/**
 * Executes a command and reurns an array with exit code, stdout and stderr content
 * @param string $cmd - Command to execute
 * @param string|null $workdir - Default working directory
 * @return string[] - Array with keys: 'code' - exit code, 'out' - stdout, 'err' - stderr
 */
function execute($cmd, $workdir = null) {
    if (is_null($workdir)) {
        $workdir = __DIR__;
    }
    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin
       1 => array("pipe", "w"),  // stdout
       2 => array("pipe", "w"),  // stderr
    );
    $process = proc_open($cmd, $descriptorspec, $pipes, $workdir, null);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    return [
        'code' => proc_close($process),
        'out' => trim($stdout),
        'err' => trim($stderr),
    ];
}
/** =========================================================================================> TABLE */
# zobrazované tabulky >* je označuje klíč, >tab označuje klíč jiné tabulky
//$app_tables= (object)array(
//  'menu'    => "mid>*,mid_top>menu,mid_sub>menu,nazev,elem>;",
//  'xakce'   => "id_xakce>*,nazev,xelems>;",
//  'xclanek' => "id_xclanek>*",
//  '_' => 'ADMIN,systable' // cesta k funkci tab_append, css tabulky
//);
# --------------------------------------------------------------------------------------- man append
# zobraz záznam referovaný daným elementem
function man_append($table,$elem) {
  global $app_tables;
  $html= '';
  // rozeber element
  list($name,$value)= explode('=',trim($elem,' -'));
  switch ($name) {
    case 'aclanek':
    case 'xclanek':
      $html.= sys_db_append('xclanek',"id_xclanek='$value'");
      break;
  }
  return $html;
}
# -------------------------------------------------------------------------------------- tab selects
//function tab_selects() { 
//  global $app_tables;
//  $selects= $del= '';
//  $key= 1;
//  foreach ((array)$app_tables as $id=>$flds) {
//    if ($id=='_') continue;
//    $selects.= "$del$id:$key";
//    $del= ',';
//    $key++;
//  }
//  return $selects;
//}
# --------------------------------------------------------------------------------- tab append_using
# zobraz všechny záznamy ve všech tabulkách obsahujících daný primární klíč dané tabulky
//function tab_append_using($table,$idt) {
//  global $app_tables;
//  $html= '';
//  // najdi tabulky referující danou tabulku
//  foreach ($app_tables as $tab=>$flds) {
//    $fld= explode(',',$flds);
//    foreach ($fld as $f) {
//      list($f,$tab2)= explode('>',$f);
//      if ($tab2==$table) {
//        $html.= tab_append($tab,"$f=$idt");
//      }
//    }
//  }
//  return $html;
//}
# --------------------------------------------------------------------------------------- tab append
# ukaž záznamy dané tabulky s danou podmínkou
//function tab_append($table,$cond) { 
//  global $app_tables;
//  $limit= 7;
//  list($path,$css)= explode(',',$app_tables->_);
//  $html= '';
//  // vytvoř header a nalezni primární klíč
//  $ths= $key= '';  
//  $fld= explode(',',$app_tables->$table);
//  foreach ($fld as $f) {
//    list($f,$x)= explode('>',$f);
//    $ff= $f;
//    if ($x=='-' || $x=='*') {
//      $key= $f;
//      $href= "href='ezer://$path.tab_append/$table//2'";
//      $ff= "<a title='$table' $href>$f</a>";
//    }
//    $ths.= "<th>$ff</th>";
//  }
//  if (!$key) { $html= "chybí primární klíč"; goto end; }
//  // čti tabulku
//  $html.= "<table class='$css'><tr>$ths</tr>";
//  $cond= str_replace('*',$key,$cond);
//  $rt= pdo_qry("SELECT * FROM $table WHERE $cond ORDER BY $key DESC LIMIT $limit");
//  while ( $rt && ($t= pdo_fetch_object($rt)) ) {
//    $html.= '<tr>';
//    foreach ($fld as $f) {
//      list($f,$tab2)= explode('>',$f);
//      $val= $t->$f;
//      if ($tab2=='*') {
//        // zobraz záznamy obsahující tento klíč
//        $href= "href='ezer://$path.tab_append/$table/$val/1'";
//        $html.= "<th><a title='$tab2' $href>$val</a></th>";
//      }
//      elseif ($tab2==';') { 
//        // rozkóduj $val jako středníkem oddělené elementy, pro každý dej odkaz
//        $vals= explode(';',$val);
//        $vals= array_map(function($elem) use ($path,$tab){
//          return "<a href='ezer://$path.tab_append/$tab/$elem/3'>$elem</a>";
//        },$vals);
//        $html.= "<th>".implode(';',$vals)."</th>";
//      }
//      elseif ($tab2 && $tab2!='-') {
//        // ukaž záznam s tímto klíčem
//        $fld2= explode(',',$app_tables->$tab2);
//        list($key2)= explode('>',$fld2[0]);
//        $href= "href='ezer://$path.tab_append/$tab2/$key2=$val/0'";
//        $html.= "<th><a title='$tab2' $href>$val</a></th>";
//      }
//      else {
//        $html.= "<td>$val</td>";
//      }
//    }
//    $html.= '</tr>';
//  }
//  $html.= "</table><br>";
//end:  
//  return $html;
//}
/** ========================================================================================> ÚČASTI */
# funkce pro úpravu tabulky účastí
# --------------------------------------------------------------------------------------- table load
# načtení tabulky pro editaci
function table_load($idc) {
  $ret= (object)array('ok'=>1,'msg'=>'','rows'=>array());
  $err= '';
  // kontrola, zda k pozvánce tabulka existuje
  $idu= select("id_xucast","xucast","id_xclanek=$idc");
  if ( !$idu ) { $err= "tabulka pro pozvánku $idc neexistuje"; goto end; }
  // přečtení tabulky 
  $tr= pdo_qry("
    SELECT COUNT(*),skupina,MAX(poradi) FROM xucast WHERE id_xclanek=$idc GROUP BY skupina");
  while ( $tr && (list($pocet,$nazev,$maxim)= pdo_fetch_row($tr)) ) {
    $ret->rows[]= (object)array('nazev'=>$nazev,'maxim'=>$maxim,'pocet'=>$pocet-1);
  }
end:
  if ( $err ) { $ret->ok= 0; $ret->msg= $err; }
                                                        debug($ret,"table_load($idc)");
  return $ret;
}
# ------------------------------------------------------------------------------------- table change
# vytvoření tabulky
function table_change($idc,$rows) {
                                                        debug($rows,"case=$idc");
  $ret= (object)array('ok'=>1,'msg'=>'');
  $max= array();
  $err= '';
  // kontrola, zda k pozvánce tabulka existuje
  $idu= select("id_xucast","xucast","id_xclanek=$idc");
  if ( !$idu ) { $err= "tabulka pro pozvánku $idc neexistuje"; goto end; }
  // přečtení tabulky 
  $tr= pdo_qry("
    SELECT skupina,MAX(poradi) FROM xucast WHERE id_xclanek=$idc GROUP BY skupina");
  while ( $tr && (list($nazev,$maxim)= pdo_fetch_row($tr)) ) {
    $max[$nazev]= $maxim;
  }
  // úprava změněných maxim a názvů dat tabulky v xucast, přidání či ubrání řádků
  foreach ($rows as $row) {
    if ( isset($max[$row->stary]) ) {                                   // skupina existuje
      if ( $row->maxim<$row->pocet ) {                                  // .. změna maxima = ko
        $err= "nelze snížit maximum pod počet již přihlášených";
        goto end;
      }
      if ( $row->maxim==0 && $row->pocet==0) {                          // .. zrušení
        query("DELETE FROM xucast
               WHERE id_xclanek=$idc AND skupina='{$row->stary}'");
      }
      if ( $row->maxim!=$max[$row->stary] ) {                           // .. změna maxima = ok
        query("UPDATE xucast SET poradi='{$row->maxim}'
               WHERE id_xclanek=$idc AND skupina='{$row->stary}' AND jmeno='max'");
      }
      if ( $row->nazev!=$row->stary ) {                                 // .. změna názvu
        query("UPDATE xucast SET skupina='{$row->nazev}'
               WHERE id_xclanek=$idc AND skupina='{$row->stary}'");
      }
    }
    else {                                                              // nová skupina
      query("INSERT INTO xucast(id_xclanek,skupina,jmeno,poradi)
             VALUES ($idc,'{$row->nazev}','max',{$row->maxim})");
    }
  }
  $ret->msg= "tabulka byla změněna";
end:
  if ( $err ) { $ret->ok= 0; $ret->msg= $err; }
  return $ret;
}
# ------------------------------------------------------------------------------------- table create
# vytvoření tabulky účasti k pozvánce= článku
function table_create($idc,$rows) {
                                                        debug($rows,"case=$idc");
  $ret= (object)array('ok'=>1,'msg'=>'');
  $err= '';
  // kontrola, zda již k pozvánce tabulka neexistuje
  $idu= select("id_xucast","xucast","id_xclanek=$idc");
  if ( $idu ) { $err= "tabulka pro pozvánku $idc již existuje"; goto end; }
  // vytvoření tabulky jako xucast(id_xclanek=idc,skupina=...,jmeno=max,poradi=...)
  foreach ($rows as $row) {
    // normalizace názvu
    $nazev= str_replace(' ','_',$row->nazev);
    // vytvoření skupiny
    query("INSERT INTO xucast(id_xclanek,skupina,jmeno,poradi)
           VALUES ($idc,'$nazev','max',{$row->maxim})");
  }
  $ret->msg= "tabulka byla vytvořena";
end:
  if ( $err ) { $ret->ok= 0; $ret->msg= $err; }
  return $ret;
}
/** =========================================================================================> FOTKY */
//# ---------------------------------------------------------------------------------==> . ERRATA_2020
//# oprava fotek - kopie z fileadmin/photo do inc/f
//function ERRATA_2020($cond) {
//  global $ezer_server;
//  if ($ezer_server) { // Synology
//    $incf=  "/var/services/web/www/chlapi/inc/f";
//    $photo= "/var/services/web/www/setkani4/fileadmin/photo";
//  }
//  else { // local
//    $incf=  "C:/Ezer/beans/chlapi.online/inc/f";
//    $photo= "C:/Ezer/beans/setkani4/fileadmin/photo";
//  }
//
//  $html= '';
//  $mn= pdo_qry("SELECT id_xfotky,path FROM xfotky WHERE $cond AND path!='' ");
//  while ( $mn && (list($ch,$ys)= pdo_fetch_row($mn)) ) {
//    $html.= "<hr> ($ch,$ys) ";
//    $src= "$photo/$ys";
//    $dst= "$incf/$ch";
//    if (file_exists($src)) {
//      if (file_exists($dst)) {
//        // vyprázdníme cíl
//        $files= files($dst);
//        $html.= count($files)." unlink ";
//        foreach ($files as $file) {
////          $html.= "<br> unlink($dst/$file)";
//          unlink("$dst/$file");
//        }
//      }
//      else {
//        $html.= "<br> mkdir($dst)";
//        mkdir($dst);
//      }
//      // a zkopírujeme 
//      $files= files($src);
//      $html.= count($files)." copy ";
//      foreach ($files as $file) {
////        $html.= "<br> copy($src/$file,$dst/$file)";
//        copy("$src/$file","$dst/$file");
//      }
//    }
//    else {
//      $html.= " mising";
//    }
//  }
//  return $html;
//}
//function files($dirname) {
//  $return= array();
//  if ( file_exists($dirname) ) {
//    $dir= opendir($dirname);
//    if ($dir) {
//      while (($filename= readdir($dir)) !== false) {
//        if (!is_dir($filename)) {
//          $return[] = "$filename";
//        }
//      }
//      closedir($dir);
//    }
//  }
//  return $return;
//}
# -------------------------------------------------------------------------------------- fotky2array
// přečte fotky ze složky inc/f/fid do pole time=fname->time
// pokud složka neexistuje, založí ji
// obnov popisky: pokud neexistuje soubor fotka.txt vytvoř jej z zfotky.seznam
function fotky2array($fid) { 
  global $ezer_path_root, $abs_root, $ezer_server_ostry;
  $path= "$abs_root/inc/f/$fid";
  $time= array(); // fname->time
  // pokud složka neexistuje vytvoříme ji a návrat
  if ( !is_dir($path) ) {
    $ok= mkdir($path,0777);
                                                  display("mkdir($path)=$ok");
    return $time;
  }
  // pokud složka existuje projdeme ji
  $fotky= simple_glob("$path/*");
  $n= 0;
  foreach ($fotky as $fotka) {
    $n++;
    $orig= mb_substr($fotka,mb_strlen($path)+1);
    // případná transformace jména do ASCII pro Windows
    $file= $ezer_server_ostry 
        ? $orig 
        : iconv( "windows-1250", "utf-8", $orig );
    $ascii= utf2ascii($file,'.');
    // případné přejmenování
    if ($ascii!=$file && rename("$path/$orig","$path/$ascii")) {
      $file= $ascii;
    }
    // dále už jen s originálem
    if (substr($orig,0,1)=='.') continue;
    // získání Exif 
    $datetime= '';
    $exif= @exif_read_data("$path/$file",'FILE,EXIF',true,false);
    // pokračujeme jen v případě úspěchu
    if ($exif) {
      $datetime= $exif['EXIF']['DateTimeOriginal'];
      if ($datetime) {
        $datetime[4]= '-'; $datetime[7]= '-';
        $time[$file]= $datetime;
      }
    }
    // fotografie bez Exif vložíme nakonec se zachováním původního pořadí ve složce
    if (!$datetime) {
      $ext= substr($file,-3,3);
      if (in_array($ext,array('jpg','png','gif'))) {
        $time[$file]= "9999-".str_pad($n,3,'0',STR_PAD_LEFT);
      }
    }
  }
  // vytvoř chybějící fotka.txt
  $seznam= select('seznam','xfotky',"id_xfotky=$fid");
  $seznam= explode(',',$seznam);
  for ($i= 0; $i<count($seznam); $i+=2) {
    $file= $seznam[$i];
    $desc= trim($seznam[$i+1]);
    if ($desc && !file_exists("$path/$file.txt")) {
      file_put_contents("$path/$file.txt",$desc);
    }
  }
                                              debug($time,"fotky2array($fid)");
  return $time;
}
# --------------------------------------------------------------------------------------- corr fotky
// 1) fotky a popisy se berou z adresáře a přemístí do textu
// POZDEJI: ma žádost provést kontrolu úplnosti fotek 
//     (po zobrazení udělat test na počet fotek v text a v adresáři
//     pokud není shoda, uložit chybějící do text na konec včetně případných popisů)
// 2) převést soubory na mini+thumbs
// 4) dát zprávu $gn->gn_msg("$n fotografií bylo přidáno do pásu $uid");
function corr_fotky($fid) {
  global $abs_root;
  $path= "$abs_root/inc/f/$fid";
  $time= fotky2array($fid);
  // seřazení podle data pořízení
  uasort($time,function ($a, $b) { return strncmp($a,$b,19);});
  // ------------ $time obsahuje fotografie seřazené podle data pořízení
  $th= 80; // šířka thumbnail
  foreach ($time as $file=>$tm) {
                                                  display($file);
    $src= "$path/$file";
    $thumb= "$path/..$file";
    if (!file_exists($thumb)) {
      #$cmd= "convert -geometry {$th}x$th +contrast -sharpen 10 $src $thumb";
      #       //$cmd = ereg_replace('/','\\',$cmd);  #echo "$cmd<br>";  snad má být ve Windows potřeba - není
      #system(IMAGE_TRANSFORM_LIB_PATH.$cmd);
      $width= $height= $th;
      x_resample($src,$thumb,$width,$height);
    }
    // udělej variantu pro web, pokud neexistuje
    $small= "$path/.$file";
    if (!file_exists($small)) {
      #$cmd= "convert -geometry 512x512 $src $small";
      #system(IMAGE_TRANSFORM_LIB_PATH.$cmd);
      $width= $height= 512;
      x_resample($src,$small,$width,$height);
    }
    // zjisti popisek (existuje jako soubor fotka.txt)
    $txt= "$path/$file.txt";
    $popisek= file_exists($txt) ? file_get_contents($txt) : '';
    $popisek= strtr($popisek,'"','\"');
    // vytvoř xfotky.seznam (nahraď čárky)
    $text.= $file . ',' . str_replace(',','##44;',$popisek) . ',';
  }
  query("UPDATE xfotky SET seznam=\"$text\" WHERE id_xfotky='$fid'");
  return count($time);
}
# ----------------------------------------------------------------------------------==> . sort fotky
# seřadí fotky pro mode='time' podle času pořízení, pro mode='name' podle názvu
function sort_fotky($fid,$mode='time') { 
  global $abs_root;
  $path= "$abs_root/inc/f/$fid";
  $time= fotky2array($fid);
  // seřazení
  if ($mode=='time')
    uasort($time,function ($a, $b) { return strncmp($a,$b,19);});
  elseif ($mode=='name')
    ksort($time);
  $text= '';
  foreach ($time as $file=>$tm) {
    $desc= file_exists("$path/$file.txt") ? file_get_contents("$path/$file.txt") : '';
    $text.= "$file,$desc,";
  }
  // zápis
  query("UPDATE xfotky SET seznam=\"$text\" WHERE id_xfotky='$fid'");
  return count($time);
}
# --------------------------------------------------------------------------------==> . create fotky
# přidání fotek - pokud je definováno x.kapitola pak pod příslušné part - jinak na konec
function create_fotky($x) {
  $cid= $x->cid;
  $autor= pdo_real_escape_string($x->autor);
  $nadpis= pdo_real_escape_string($x->nadpis);
  $psano= sql_date1($x->psano,1);
  $editors= $x->editors ? implode(',',(array)$x->editors) : '';
  query("INSERT INTO xfotky (id_xclanek,editors,nazev,kdy,autor,seznam)
         VALUES ($cid,'$editors','$nadpis','$psano','$autor','')");
  $fid= pdo_insert_id();
  return $fid;
}
# ----------------------------------------------------------------------------------==> . load fotky
function load_fotky($fid) { trace();
  global $abs_root;
  $path= "$abs_root/inc/f/$fid";
  $x= (object)array();
  $time= time();
  list($id_xclanek,$x->editors,$x->autor,$x->nadpis,$lst,$psano)=
    select('id_xclanek,editors,autor,nazev,seznam,kdy','xfotky',"id_xfotky=$fid");
  $x->fotky= "<span class='foto drop' data-foto-n='-1'></span><ul class='foto' id='foto'>";
  $x->psano= sql_date1($psano);
  $fs= explode(',',$lst);
  $last= count($fs)-1;
  for ($i= 0; $i<$last; $i+=2) {
    $fsi= $fs[$i];
    $datetime= '';
    $mini= "inc/f/$fid/..$fsi";
    $midi= "inc/f/$fid/.$fsi";
    $foto= "$path/$fsi";
    if ( file_exists($foto) ) {
      // získání Exif
      $exif= @exif_read_data($foto,'FILE,EXIF',true,false);
      // pokračujeme jen v případě úspěchu
      if ($exif) {
        $datetime= $exif['EXIF']['DateTimeOriginal'];
        if ($datetime) {
          $datetime[4]= '-'; $datetime[7]= '-';
        }
      }
    }
    if ( file_exists($mini) ) {
      $title= $fsi ? "title='$fsi $datetime'" : '';
      $desc= $fs[$i+1];
      $tit= $desc 
          ? "<div title='$desc'>".(mb_strlen($desc)>10?mb_substr($desc,0,10).'...':$desc)."</div>" 
          : '';
      $chk= '';
//      $chk= "<input type='checkbox' onchange=\"this.parentNode.dataset.checked=this.checked;\" />";
//          ['upravit popis',foto_note]
      $menu= "oncontextmenu=\"Ezer.fce.contextmenu([
          ['<a href=\'inc/f/$fid/$fsi\' target=\'foto\'>ukázat fotku</a> (pravý klik)',function(){}],
          ['-url fotky do schránky',function(){Ezer.fce.clipboard('$midi');}],
          ['url miniatury do schránky',function(){Ezer.fce.clipboard('$mini');}],
          ['-otočit doleva',function(){cmd_fotky($fid,'$fsi','rotate_l','')}],
          ['otočit doprava',function(){cmd_fotky($fid,'$fsi','rotate_r','')}],
          ['zkopírovat do příloh',function(){cmd_fotky($fid,'$fsi','attach','')}],
          ['-upravit popis',function(){cmd_fotky($fid,'$fsi','popis','$desc')}],
          ['-smazat fotku',function(){cmd_fotky($fid,'$fsi','delete','')}],
          ['přesunout fotky do inc/f/?',function(){cmd_fotky($fid,'$fsi','moveto','')}]
        ],arguments[0]);return false;\"";
      $n= $i/2;
      $x->fotky.=
        " <li class='foto' data-foto-n='$n' $title $menu style='background-image:url($mini?time=$time)'>"
        . "$chk$tit</li>";
    }
  }
  $x->fotky.= "</ul>";
  return $x;
}
# ----------------------------------------------------------------------------------==> . save fotky
function save_fotky($x,$perm=null) {
  $fid= $x->fid;
  $autor= pdo_real_escape_string($x->autor);
  $nadpis= pdo_real_escape_string($x->nadpis);
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
# ---------------------------------------------------------------------------------------- cmd fotky 
# různé operace nad jednou fotkou
function cmd_fotky($fid,$foto,$cmd,$desc='') {
  global $abs_root;
  $path= "$abs_root/inc/f/$fid";
  $err= '';
  $deg= 90;
  $path= "inc/f/$fid";
  // extrakce seznamu fotek do $fotky, $n je index $foto
  $chng= 0; // je třeba změnit seznam
  list($seznam,$idc)= select('seznam,id_xclanek','xfotky',"id_xfotky=$fid");
  $fotky= explode(',',$seznam);
  $n= array_search($foto,$fotky);
  if ( $n===false ) goto end;   // $foto nebyla nalezena
  // operace
  switch ($cmd) {
  case 'moveto':  // ---------------------------------------- kopírování fotky a dalších do desc
    $to= "$abs_root/inc/f/$desc";
    if (!is_dir($to)) { $err= "složka $to neexistuje"; goto end; }
    $ok= 1;
    for ($i= $n; $i<count($fotky); $i+=2) {
      $fotka= $fotky[$i];
      if (!$fotka) break;
      $err= rename("$path/$fotka","$to/$fotka") ? '' : "fotka $fotka nešla přesunout do $to"; 
      if (!$err) display("$fotka --> $desc");  
      rename("$path/.$fotka","$to/.$fotka");
      rename("$path/..$fotka","$to/..$fotka");
      rename("$path/$fotka.txt","$to/$fotka.txt");
      if ($err) goto end;
    }
    for ($i= $n; $i<count($fotky); $i+=2) {
      unset($fotky[$i]); unset($fotky[$i+1]);
    }
    $chng= 1;
    break;
  case 'popis':   // ---------------------------------------- popsání fotky
    $fotka= $fotky[$n];
    $fotky[$n+1]= $desc;
    if ($desc) {
      if (!file_put_contents("$path/$fotka.txt",$desc)) { $err= "nešlo vytvořit $path/$fotka.txt"; }
    }
    else {
      unlink("$path/$fotka.txt");
    }
    $chng= 1;
    break;
  case 'delete':  // ---------------------------------------- smazání fotky
    $err= unlink("$path/$foto") ? '' : "selhalo unlink($path/$foto)"; 
    unlink("$path/.$foto"); unlink("$path/..$foto");
    unset($fotky[$n]); unset($fotky[$n+1]);
    $chng= !$err;
    break;
  case 'attach':  // ---------------------------------------- zkopírování do příloh
    $path_c= "inc/c/$idc";
    if ( !file_exists($path_c) && !mkdir($path_c) ) {
      $err= "$path_c neexistuje nebo nelze vytvořit"; goto end;
    }
    $err= copy("$path/$foto","$path_c/$foto") ? '' : "selhalo copy($path/$foto,$path_c/$foto)"; 
    break;
  case 'rotate_r':  // -------------------------------------- otočit fotku o -90
    $deg= 270;
  case 'rotate_l':  // -------------------------------------- otočit fotku o +90
    $part= pathinfo("$path/$foto");
    $ext= strtolower($part['extension']);
    switch ($ext) {
    case 'jpg':
      $img= @imagecreatefromjpeg("$path/$foto");
      if ( !$img ) { $err= "$foto nema format JPEG"; goto end; }
      $img= imagerotate($img,$deg,0);
      if ( !imagejpeg($img,"$path/$foto") ) { $err= "$foto nelze ulozit"; goto end; }
      break;
    case 'png':
      $img= @imagecreatefrompng("$path/$foto");
      if ( !$img ) { $err= "$foto nema format PNG"; goto end; }
      $img= imagerotate($img,$deg,0);
      if ( !imagepng($img,"$path/$foto") ) { $err= "$foto nelze ulozit"; goto end; }
      break;
    case 'gif':
      $img= @imagecreatefromgif("$path/$foto");
      if ( !$img ) { $err= "$foto nema format GIF"; goto end; }
      $img= imagerotate($img,$deg,0);
      if ( !imagegif($img,"$path/$foto") ) { $err= "$foto nelze ulozit"; goto end; }
      break;
    default:
      $err= "$foto ma neznamy typ";
    }
    if ( !$err ) {
      $width= $height= 512;
      x_resample("$path/$foto","$path/.$foto",$width,$height);
      $width= $height= 80;
      x_resample("$path/$foto","$path/..$foto",$width,$height);
    }
    break;
  }
  // pokud je třeba změň seznam
  if ( $chng ) {
    $seznam= implode(',', $fotky);
    query("UPDATE xfotky SET seznam=\"$seznam\" WHERE id_xfotky='$fid'");
  }
end:  
  return $err;
}
# --------------------------------------------------------------------------------==> . namiru fotky
# upraví velikost obrázků podle dodané šířky
# obrázek musí být ve složce inc/c/$id
# pokud je $replace=1 nahradí v textu článku odkazy 
# jinak vrátí dotaz, zda to udělat s informací o získaném prostoru
function namiru_fotky($id,$imgs,$replace) { 
  global $ezer_path_root;
  $s= (object)array('n'=>0,'msg'=>'');
  $prefix= get_prefix();
  $dir= "/inc/c/$id";
  $text= select('web_text','xclanek',"id_xclanek=$id");
  foreach ((array)$imgs as $img) {
    if ( strpos($img->src,$dir)===0 ) {
      $orig= $ezer_path_root.$img->src;
      list($width, $height, $type)=@ getimagesize($orig);
      if ( ceil($img->width) < $width ) {
        $name= basename($orig);
        $small= "$ezer_path_root$dir/.$name";
        if ( x_resample($orig,$small,$img->width,$img->height) ) {
          if ( $replace ) {
            $text= preg_replace(
                "~<img([^>]*)src=\"$dir/$name\"([^>]*)>~Umu",
                "<a href='$prefix/$dir/$name' target='img'><img $1 src='$dir/.$name' $2></a>",
                $text);
          }
          else {
            $old= ceil(filesize($orig)/1024);
            $new= ceil(filesize($small)/1024);
            $s->msg.= "<br>$name byl zmenšený z {$old}KB na {$new}KB";
          }
          $s->n++;
        }
      }
    }
  }
  if ( $s->n ) {
    if ( $replace ) {
      $text= escape_string($text);
      query("UPDATE xclanek SET web_text=\"$text\" WHERE id_xclanek=$id");
      log_obsah('r','c',$id);
    }
    else {
      $s->msg.= "<hr>nahradit v článku?";
    }
  }
  else {
    $s->msg.= "žádný obrázek nelze zmenšit";
  }
  return $s;
}
# --------------------------------------------------------------------------------------- img oprava
# opraví obrázky v part
#  a) odstraní embeded obrázky
function bez_embeded($idxc,$update,$inline='') {
  $s= (object)array('n'=>0,'msg'=>'');
  $text= $inline ? $inline : select("web_text","xclanek","id_xclanek=$idxc");
  $text= preg_replace("/<img[^>]+src=.data:image[^>]+\>/i","(embeded image)",$text,-1,$s->n);
  if ( $inline && $s->n ) {
    $s->msg.= "POZOR obrázky do článku je třeba přidávat přes Přílohy";
  }
  elseif ( $s->n ) {
    if ( $update ) {
      $text= pdo_real_escape_string($text);
  //                                                       display($text);
      pdo_qry("UPDATE xclanek SET web_text='$text' WHERE id_xclanek=$idxc");
      $s->msg.= "z článku $idxc byl odstraněno $s->n embeded obrázek";
    }
    else {
      $s->msg.= "v článku $idxc je $s->n embeded obrázek - mám je(j) vyjmout?";
    }
  }
  else {
    $s->msg.= "v článku $idxc není žádný embeded obrázek";
  }
  return $s;
}
# --------------------------------------------------------------------------------==> . minify fotky
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
//# ----------------------------------------------------------------------------------==> . upload url
//# zapíše soubor zadaný urldo fileadmin/img/cid
//function upload_url($url,$cid) { trace();
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
//}
//# ----------------------------------------------------------------------------------==> . upload zip
//function upload_zip($url,$uid,$cid) { trace();
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
//}
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
  global $yy;
  $pass= substr(base64_encode(openssl_random_pseudo_bytes(6)),0,-1);
  $yy= (object)array('pass'=>$pass);
  return $yy;
}
# ----------------------------------------------------------------------------------- menu copy_foto
# zkopíruje chybějící fotky ze setkani.org/filedamin/photo do chlapi.cz/inc/f
# je voláno po založení záznamu pro fotky v menu_copy_elem('F',...)
function menu_copy_foto($fid,$test=1) {
  global $abs_root;
  $fileadmin= get_fileadmin();
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
  global $wid, $s, $abs_root;
  $fileadmin= get_fileadmin();
  $msg= '?';
  $elems= '';
  $aid= 0; 
  $kid= 0; 
  $xid= 0;
  $fid= 'f';
  $typ= 'aclanek';

  // zjisti části - u knihy kapitoly - u článku, pokud jsou, vytvoř knihu
  ask_server((object)array('cmd'=>'kapitoly','pid'=>$pid));
                                                      display("pids/$pid=$s->pids");
  $pids= explode(',',$s->pids);
  // pokud je více části - vytvoř knihu
  if ( $co=='akce' ) {
    $aid= 'a';
    if ( $test ) {
      $msg.= " akce: ";
    }
    else {
      query("INSERT INTO xakce () VALUES ()");
      $aid= pdo_insert_id();
    }
  }
  elseif ( count($pids)>1 ) {
    $kid= 'k';
    if ( $test ) {
      $msg.= " kniha: ";
    }
    else {
      query("INSERT INTO xkniha () VALUES ()");
      $kid= pdo_insert_id();
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
      $a= $s->autor; $n= $s->nadpis; $lst= $s->obsah; $p= sql_date($s->psano,1);
      if ( $test ) {
        $msg.= " fotky/$xpid  ";
      }
      else {
        query("INSERT INTO xfotky (id_xclanek,autor,nazev,kdy,seznam,path) "
            . "VALUES ($xid,'$a','$n','$p','$lst','$pid')");
        $fid= pdo_insert_id();
      }
      break;
      
    case 'C': // ------------ nadpis
    case 'E': // ------------ kapitola
    case 'A': // ------------ ...
    case 'D': // ------------ ...
      ask_server((object)array('cmd'=>'clanek','pid'=>$pid));
      // uprav odkazy
      $obsah= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$s->obsah);
      if ( $co=='akce' ) {  // ------------------------------ akce
        $oddo= '';
        $skill= 0;
        if ( $x=='A' ) {    
          // hlavička
          $oddo= datum_oddo($s->od,$s->do);
          $skill= in_array($s->fe_groups,array(4,6)) ? 8 : 0;
          $tit= "$s->nadpis";
          if ( $test ) {
            $msg.= " akce.nazev=$oddo:$tit  ";
          }
          else {
            query("UPDATE xakce SET nazev='$tit',datum_od='$s->od',datum_do='$s->do' "
                . "WHERE id_xakce=$aid");
          }
        }
        // hlavní článek
        $clanek= "<h1>$oddo $s->nadpis</h1>$obsah";
        $clanek= str_replace("'","\\'",$clanek);
        if ( $test ) {
          $xid= 'x';
          $msg.= " akce/$xpid:$skill  ";
        }
        else {
          query("INSERT INTO xclanek (web_text,web_skill) VALUES ('$clanek',$skill)");
          $xid= pdo_insert_id();
        }
        $elems= ($elems ? "$elems;" : '')."aclanek=$xid";
      }
      else { // $co=setkani_* ------------------------------- článek
        // případně zapiš celkový název knihy
        if ( $x=='C' && $kid ) {
          $tit= "$s->autor: $s->nadpis";
          $tit= str_replace("'","\\'",$tit);
          if ( $test ) {
            $msg.= " kniha.nazev=$tit  ";
          }
          else {
            query("UPDATE xkniha SET nazev='$tit' WHERE id_xkniha=$kid");
          }
        }
        $clanek= "<h1>$s->nadpis</h1>$obsah";
        $clanek= str_replace("'","\\'",$clanek);
        if ( $test ) {
          $xid= 'x';
          $msg.= " clanek/$xpid  ";
        }
        else {
          query("INSERT INTO xclanek (web_text) VALUES ('$clanek')");
          $xid= pdo_insert_id();
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
  $elem= select("elem","menu","wid=$wid AND mid=$mid");
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
      query("UPDATE menu SET elem='$elem' WHERE wid=$wid AND mid=$mid");
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
      query("UPDATE menu SET elem='$elem' WHERE wid=$wid AND mid=$mid");
    }
  }
                                                      display($msg);
  return $msg;
}
/** ==========================================================================================> MENU */
# ----------------------------------------------------------------------------------- menu change_ok
# posoudí přípustnost změn v menu
function menu_change_ok($mid,$zmeny) {
  $err= '';
  // získej staré hodnoty
  $old= select_object('*', 'menu', "mid=$mid");
  debug($zmeny,'new'); debug($old,'old'); 
  if (($old->ref=='home' || $old->ref=='en-home') && isset($zmeny->ref)) 
    $err.= "reference 'home' a 'en-home' nesmí být změněny"; 
  if ($old->wid==1 && isset($zmeny->ref) && substr($zmeny->ref,0,3)!='en-')
    $err.= "reference anglické verze nechť začínají na 'en-' "; 
  if ($err) display($err);
  return $err;
}
# -------------------------------------------------------------------------------- menu clanek2kniha
# vytvoří knihu a přidá článek $id jako první kapitolu
# ty=aclanek|xclanek
function menu_clanek2kniha($mid,$typ,$cid) {
  // zjištění pozice v Menu
  $elems= select("elem","menu","mid=$mid");
  $ms= explode(';',$elems);
  $elem= "$typ=$cid";
  $i= array_search($elem,$ms);
  // vytvoření knihy s článkem jako abstraktem
  query("INSERT INTO xkniha (xelems) VALUES ('aclanek=$cid')");
  $kid= pdo_insert_id();
  // uložení změnu do Menu
  $ms[$i]= "akniha=$kid";
  $elems= implode(';',$ms);                 
  query("UPDATE menu SET elem='$elems' WHERE mid=$mid");
  return 1;
}
# ------------------------------------------------------------------------------------ menu add_elem
# přidá do menu další element, resp. pro xakce vytvoří novou akci roku daného $mid
function menu_add_elem($wid,$mid,$table,$first=0,$id_user=0) {
  switch ($table) {
  case 'pozvanka':     // ---------------------------------- pozvánka na novou akci skupiny mid
    query("INSERT INTO xclanek (editors,cms_skill) VALUES ('$id_user',4)");
    $idc= pdo_insert_id();
    log_obsah('i','c',$idc);
    $ymd= date('Y-m-d',strtotime('next monday'));
    query("INSERT INTO xakce (xelems,datum_od,datum_do,skupina) 
        VALUES ('aclanek=$idc','$ymd','$ymd',$mid)");
    break;
  case 'xakce':        // ---------------------------------- nová akce roku mid
    query("INSERT INTO xclanek (editors,cms_skill) VALUES ('$id_user',4)");
    $idc= pdo_insert_id();
    log_obsah('i','c',$idc);
    $ymd= "$mid-01-01";
    query("INSERT INTO xakce (xelems,datum_od,datum_do) VALUES ('aclanek=$idc','$ymd','$ymd')");
    break;
  case 'xkniha':       // ---------------------------------- nová kniha s prvním článkem
    $elem= select("elem","menu","wid=$wid AND mid=$mid");
    query("INSERT INTO xclanek (editors,cms_skill) VALUES ('$id_user',4)");
    $cid= pdo_insert_id();
    log_obsah('i','c',$cid);
    query("INSERT INTO xkniha (xelems) VALUES ('aclanek=$cid')");
    $kid= pdo_insert_id();
    if ( $first )
      $elem= "xkniha=$kid" . ($elem ? ";$elem" : '');
    else
      $elem= ($elem ? "$elem;" : '') . "xkniha=$kid";
    query("UPDATE menu SET elem='$elem' WHERE wid=$wid AND mid=$mid");
    break;
  case 'xkniha.elem':  // ---------------------------------- nový článek knihy 
    $elem= select("xelems","xkniha","id_xkniha=$mid");
    query("INSERT INTO xclanek (editors,cms_skill) VALUES ('$id_user',4)");
    $id= pdo_insert_id();
    log_obsah('i','c',$id);
    if ( $first )
      $elem= "aclanek=$id" . ($elem ? ";$elem" : '');
    else
      $elem= ($elem ? "$elem;" : '') . "aclanek=$id";
    query("UPDATE xkniha SET xelems='$elem' WHERE id_xkniha=$mid");
    break;
  case 'xclanek':     // ----------------------------------- nový článek
    $vzor= "<h1>Název (abstrakt tučně)</h1><h2>Nadpis (abstrakt kurzíva)</h2><hr />"
      . "<p>Po dokončení nezapomeň zrušit omezení</p>";
    $elem= select("elem","menu","wid=$wid AND mid=$mid");
    query("INSERT INTO xclanek (editors,cms_skill,web_text) VALUES ('$id_user',4,\"$vzor\")");
    $id= pdo_insert_id();
    log_obsah('i','c',$id);
    if ( $first )
      $elem= "aclanek=$id" . ($elem ? ";$elem" : '');
    else
      $elem= ($elem ? "$elem;" : '') . "aclanek=$id";
    query("UPDATE menu SET elem='$elem' WHERE wid=$wid AND mid=$mid");
    break;
  }
  return 1;
}
# ----------------------------------------------------------------------------------- menu chng_elem
# přidá do menu další element
function menu_chng_elem($mid,$typ1,$id,$typ2) {
  global $wid;
  $desc= select("elem","menu","wid=$wid AND mid=$mid");
  $elems= explode(';',$desc);
  for ($i= 0; $i<count($elems); $i++) {
    list($typx,$idx)= explode('=',$elems[$i]);
    if ( $typx==$typ1 && $idx==$id ) {
      $elems[$i]= "$typ2=$id";
      $desc= implode(';',$elems);
      query("UPDATE menu SET elem='$desc' WHERE wid=$wid AND mid=$mid");
      break;
    }
  }
  return 1;
}
# ---------------------------------------------------------------------------------- menu shift_elem
# posune element o jedno dolů (pro down=0 nahoru)
function menu_shift_elem($typ0,$mid,$id,$down) {
  global $wid;
  // zjistíme seznam elementů
  if ( $typ0=='xkniha.elem' ) {
    $elems= select("xelems","xkniha","id_xkniha=$mid");
    $typ= 'aclanek';
  }
  else {
    $elems= select("elem","menu","mid=$mid");
    $typ= $typ0;
  }
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
  if ( $typ0=='xkniha.elem' ) {
    query("UPDATE xkniha SET xelems='$elems' WHERE id_xkniha=$mid");
  }
  else {
    query("UPDATE menu SET elem='$elems' WHERE wid=$wid AND mid=$mid");
  }
  return 1;
}
# --------------------------------------------------------------------------------------- menu shift
# posune menu o jedno dolů (pro down=0 nahoru)
function menu_shift($wid,$mid,$down) {
  // zjistíme všechna menu na stejné úrovni
  list($mid_top,$typ)= select("mid_top,abs(typ)","menu","mid=$mid");
  $cond= $typ==2 && $mid_top ? "mid_top=$mid_top" : (
    $typ==1 || $typ==0 ? "typ=$typ" : 0 );
  $ms= select("GROUP_CONCAT(mid ORDER BY rank)","menu","wid=$wid AND $cond");
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
    query("UPDATE menu SET rank=$i1 WHERE wid=$wid AND mid=$mi");
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
function menu_tree($wid) { trace();
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
  $mn= pdo_qry("SELECT * FROM menu WHERE wid=$wid ORDER BY typ,mid_top,rank",
      0,0,0,'setkani');
  while ( $mn && ($m= pdo_fetch_object($mn)) ) {
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
  $rc= pdo_qry(
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
  while ( $rc && (list($uid)= pdo_fetch_row($rc)) ) {
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
    pdo_query($qry);
  }
  return $ip_ok;
}
