<?php # (c) 2007-2012 Martin Smidek <martin@smidek.eu>

# ======================================================================================> STATISTIKA
# ---------------------------------------------------------------------------------------- stat brno
# pokud fce=n_skupin pak par.delena=1|1
# pokud par.fce=n_skupin vrátí pole inf.roky:rok=>{vek:věk}
function stat_brno($par) {  //trace();
  global $abs_root;
  $inf= (object)array('html'=>'','roky'=>array());
  switch ($par->fce) {
    case 'survey': // --------------------------------------------------------- přehled identifikace
      $radku= select('COUNT(*)','chlapi.xucast',"jmeno_remove=''");
      $celkem= select('COUNT(DISTINCT jmeno_id)','chlapi.xucast',"jmeno_id>0");
      $answer= select('COUNT(DISTINCT id_osoba)','chlapi.xucast',"id_osoba>0");
      $lst= '';
      $n= 0;
      $qr= pdo_qry("
        SELECT TRIM(IF(jmeno_corr='',jmeno,jmeno_corr)) AS _jmeno,COUNT(*) AS _pocet
        FROM chlapi.xucast 
        WHERE jmeno_id>0 AND id_osoba=0
        GROUP BY jmeno_id
        ORDER BY _jmeno
      ");
      while ($qr && (list($jmeno,$pocet)= pdo_fetch_row($qr))) {
        $n++;
        list($nidos,$idos,$obce)= select('COUNT(*),GROUP_CONCAT(id_osoba),GROUP_CONCAT(obec)','ezer_db2.osoba',
            "'$jmeno' LIKE CONCAT(TRIM(jmeno),' ',TRIM(prijmeni)) AND deleted='' ");   
        $je= $nidos ? " v Answeru {$nidos}x ($idos-$obce)" : '';
        $lst.= "{$pocet}x $jmeno$je<br>";
      }
      $inf->html.= "Na setkáních se do tabulky účasti na $radku řádků zapsalo
        <br> celkem $celkem chlapů
        <br> z toho $answer chlapů je v Answeru
        <br> a $n není, jsou to tito:<hr>$lst ";
      break;
    case 'jmeno_id.csv':  // ------------------------------------ opravy jmen podle doc/jmeno_id.csv 4.
      $fp= fopen("$abs_root/doc/jmeno_id.csv",'r'); // jmeno,jmeno_corr
//      fgetcsv($fp,0,';'); 
      $n= $r= $d= 0;
      while (($row= fgetcsv($fp,0,';')) !== false) {
        list($jmeno,$id_osoba)= $row;
        if ($id_osoba) {
          display("$jmeno => id_osoba=$id_osoba");
          $n+= query("UPDATE chlapi.xucast SET jmeno_corr='$corr' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR  TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno')) ");
        }
        else {
          display("$jmeno => $corr");
          $n+= query("UPDATE chlapi.xucast SET jmeno_corr='$corr' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR  TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno')) ");
        }
      }
      fclose($fp);
      $inf->html.= "JMENO_CORR.CSV: opraveno $n překlepů ";
      break;
    case 'jmeno_corr.csv':  // -------------------------------- opravy jmen podle doc/jmeno_corr.csv 2.
      $fp= fopen("$abs_root/doc/jmeno_corr.csv",'r'); // jmeno,jmeno_corr
//      fgetcsv($fp,0,';'); 
      $n= $r= $d= 0;
      while (($row= fgetcsv($fp,0,';')) !== false) {
        list($jmeno,$corr,$id_osoba)= $row;
        if ($id_osoba) {
          display("$jmeno => id_osoba=$id_osoba");
          $n+= query("UPDATE chlapi.xucast SET jmeno_corr='$corr' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR  TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno')) ");
        }
        else {
          display("$jmeno => $corr");
          $n+= query("UPDATE chlapi.xucast SET jmeno_corr='$corr' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR  TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno')) ");
        }
      }
      fclose($fp);
      $inf->html.= "JMENO_CORR.CSV: opraveno $n překlepů ";
      break;
    case 'copy_gnucast':  // ------------------------------------------ zkopírovat gnucast do xucast 1.
//      ezer_connect('setkani');
      query("DELETE FROM chlapi.xucast WHERE gnucast!=0");
      $n= 0;
      $qr= pdo_qry("
        SELECT gnucast,jmeno,jmeno_corr,jmeno_id
        FROM setkani4.gnucast
        WHERE jmeno_remove=''
        ORDER BY gnucast
        -- LIMIT 1");
      while ($qr && (list($idg,$jmeno,$jmeno_corr,$idj)= pdo_fetch_row($qr))) {
        $n+= query("INSERT INTO chlapi.xucast SET 
          gnucast=$idg, jmeno='$jmeno', jmeno_corr='$jmeno_corr', jmeno_id=$idj");
      }
      $inf->html.= "vkopírováno $n účastí";
      break;
    case 'corr_jmeno':  // --------------------------------------------- opravy jmen podle doc/*.csv 2.
      query("UPDATE chlapi.xucast SET jmeno_remove='', jmeno_corr='', jmeno_id=0, id_osoba=0 ");
      $fp= fopen("$abs_root/doc/gnucast.csv",'r');
      fgetcsv($fp,0,';'); 
      $n= $r= $d= 0;
      while (($row= fgetcsv($fp,0,';')) !== false) {
        list($id,$pocet,$jmeno,$corr,$delete)= $row;
        if ($corr) {
          $n+= query("UPDATE chlapi.xucast SET jmeno_corr='$corr' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno')) ");
        }
        if ($delete) {
          $r+= query("UPDATE chlapi.xucast SET jmeno_remove='x' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno'))");
        }
        $d+= query("UPDATE chlapi.xucast SET jmeno_remove='o' 
          WHERE jmeno='max' OR skupina='maximum' OR LENGTH(jmeno)=1");
      }
      fclose($fp);
      $inf->html.= "GNUCAST.CSV: opraveno $n překlepů a zrušeno $r řádků a $d označeno jako pomocných";
      // opravy jmen podle doc/xucast.csv
      $fp= fopen("$abs_root/doc/xucast.csv",'r');
      fgetcsv($fp,0,';'); 
      $n= $r= $d= 0;
      while (($row= fgetcsv($fp,0,';')) !== false) {
        list($pocet,$jmeno,$corr,$delete)= $row;
        if ($corr) {
          $n+= query("UPDATE chlapi.xucast SET jmeno_corr='$corr' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno'))");
        }
        if ($delete) {
          $r+= query("UPDATE chlapi.xucast SET jmeno_remove='x' 
            WHERE TRIM(UPPER(jmeno)) LIKE TRIM(UPPER('$jmeno')) 
              OR TRIM(UPPER(jmeno))=TRIM(UPPER('$jmeno'))");
        }
        $d+= query("UPDATE chlapi.xucast SET jmeno_remove='o' 
          WHERE jmeno='max' ");
      }
      fclose($fp);
      $inf->html.= "<br>XUCAST.CSV: opraveno $n překlepů a zrušeno $r řádků a $d označeno jako pomocných";
      break;
    case 'id_jmeno':  // -------------------------------------------------------- anonymizovat jména 3.
                      // a předvyplnění id_osoba ze souboru jmeno_id.csv
      query("UPDATE chlapi.xucast SET jmeno_id=0, id_osoba=0 ");
      $fp= fopen("$abs_root/doc/jmeno_id.csv",'r'); // jmeno,id_osoba
      $nid= 0;
      while (($row= fgetcsv($fp,0,';')) !== false) {
        list($jmeno,$id_osoba)= $row;
        display("$jmeno => id_osoba=$id_osoba");
        $nid+= query("UPDATE chlapi.xucast SET id_osoba=$id_osoba 
          WHERE TRIM(UPPER(IF(jmeno_corr!='',jmeno_corr,jmeno))) LIKE TRIM(UPPER('$jmeno')) 
            OR  TRIM(UPPER(IF(jmeno_corr!='',jmeno_corr,jmeno)))=TRIM(UPPER('$jmeno')) ");
      }
      fclose($fp);
      $inf->html.= "JMENO_ID.CSV: doplněno $nid x id_osoba ";
      $jmena= array(); // jmeno->[jmeno_id,id_osoba]
      $n_ok= $n= $n_no= $n_more= 0;
      $msg= $msg_more= $msg_no= $msg_ok= '';
      // ve sjednoceném xucast
      $qr= pdo_qry("
        SELECT gnucast,id_xucast,
          IF(jmeno_corr!='',TRIM(UPPER(jmeno_corr)),TRIM(UPPER(jmeno))) AS _jmeno, id_osoba
        FROM chlapi.xucast
        WHERE jmeno_remove='' -- AND jmeno_id=0
        -- AND jmeno='Petr Jílek'
        ORDER BY 1000*gnucast+id_xucast -- _jmeno
        -- LIMIT 50
      ");
      while ($qr && (list($idg,$idx,$jmeno,$ido)= pdo_fetch_row($qr))) {
        $jmeno= str_replace('  ',' ',$jmeno);
        if (isset($jmena[$jmeno])) {
          $id= $jmena[$jmeno][0];
          $ido= $jmena[$jmeno][1];
        }
        else {
          $nidos2= -1;
          $n++;
          $id= $n;
          $jmena[$jmeno]= array($id,$ido);
          if (!$ido) {
            // zjisti jednoznačné jméno, ohlaš nejednoznačná
            list($nidos,$idos)= select('COUNT(*),GROUP_CONCAT(id_osoba)','ezer_db2.osoba',
                "'$jmeno' LIKE CONCAT(TRIM(jmeno),' ',TRIM(prijmeni)) AND deleted='' AND iniciace>0");
            if (!$nidos) {
              list($nidos2,$idos2)= select('COUNT(*),GROUP_CONCAT(id_osoba)','ezer_db2.osoba',
                  "'$jmeno' LIKE CONCAT(TRIM(jmeno),' ',TRIM(prijmeni)) AND deleted='' AND iniciace=0");
              if ($nidos2==1) { 
                $idos= $idos2; $nidos= $nidos2;            
              }
              elseif ($nidos2>1) {
                $msg_no.= "<br>$idx/$idg: $jmeno není iniciován a není jednoznačný: $idos2";
              }
            }
            if ($nidos==1) {
              $ido= $idos;
              $n_ok++;
            }
            else {
              $nn= select('COUNT(*)','chlapi.xucast',
                  "IF(jmeno_corr!='',TRIM(UPPER(jmeno_corr)),TRIM(UPPER(jmeno)))='$jmeno'");
              $nn= $nn>=5 ? "<span style='color:red'>($nn x)</span>" : "($nn x)";
              if ($nidos>1) {
                $n_more++;
                $msg_more.= "<br>$idx/$idg: $jmeno není jednoznačné: $idos $nn ";
              }
              else {
                $n_no++;
                if ($nidos2<=0) $msg_no.= "<br>$idx/$idg: $jmeno nebylo nalezeno $nn ";
              }
            }
            $ido= $nidos==1 ? $idos : 0;
            if ($ido) {
              $n_ok++;
              $jmena[$jmeno][1]= $ido;
              $msg_ok.= "<br>$idx/$idg: $jmeno bylo nalezeno ";
            }
          }
          else {
            $n_ok++;
            $msg_ok.= "<br>$idx/$idg: $jmeno bylo nalezeno ručně ";
          }
        }
        query("UPDATE chlapi.xucast SET jmeno_id=$id, id_osoba=$ido WHERE id_xucast=$idx ");
      }
   
      $inf->html.= "<br>zpracováno $n jmen, $n_ok je známých, $n_more je nejednoznačných a $n_no je neznámých 
          <hr>$msg_ok<hr>$msg_more<hr>$msg_no";
      break;
    case 'n_skupin': // -------------------------------------------------------------------- skupiny
      // přehled
      ezer_connect('setkani');
      $setkani= array(); // den->{delene:0|1,skupiny:...}
      $qr= pdo_qry("
        SELECT 
          LOCATE(',',GROUP_CONCAT(DISTINCT IF(gnucast=0,u.skupina,g.skupina)))>0 AS _delene,
          GROUP_CONCAT(DISTINCT IF(gnucast=0,u.skupina,g.skupina)) AS _skupiny,
          IF(gnucast=0,a.datum_od,g.datum) AS _den
        FROM xucast AS u
        LEFT JOIN xakce AS a ON xelems=CONCAT('aclanek=',id_xclanek)
        LEFT JOIN setkani4.gnucast AS g USING (gnucast)
        WHERE u.jmeno_remove=''
        GROUP BY _den
        HAVING _delene=$par->delena
        ORDER BY _den          
      ");
      while ($qr && (list($delene,$skup,$den)= pdo_fetch_row($qr))) {
        $setkani[$den]= (object)array('delene'=>$delene,'skupiny'=>$skup);
      }
//                                      debug($setkani);
      // zpracování
      $dny= array();
      $roky= array();
      $roky2= array();
      $roky3= array();
      $roky4= array();
      $roky5= array(); // neidentifikovaní v Answeru
      $roky6= array(); // identifikovaní v Answeru
      // rozbor tabulky XUCAST
      $od= '9999-99-99';
      $do= '0000-00-00';
      $n= 0;
      $qr= pdo_qry("
        SELECT IF(gnucast=0,a.datum_od,g.datum) AS _den,IF(gnucast=0,u.skupina,g.skupina) AS _skup,
          u.jmeno_id,u.id_osoba
        FROM xucast AS u
        LEFT JOIN xakce AS a ON xelems=CONCAT('aclanek=',id_xclanek)
        LEFT JOIN setkani4.gnucast AS g USING (gnucast)
        WHERE u.jmeno_remove='' 
        ORDER BY _den
      ");
      while ($qr && (list($den,$skupina,$jmeno_id,$ido)= pdo_fetch_row($qr))) {
        if (!isset($setkani[$den])) continue;
        if (!isset($dny[$den][$skupina])) $dny[$den][$skupina]= 0;
        $dny[$den][$skupina]++;
        $rok= substr($den,0,4);
        if (!isset($roky2[$rok])) $roky2[$rok]= array(); // různí
        if (!isset($roky5[$rok])) $roky5[$rok]= array(); // nejsou v Answeru
        if (!isset($roky6[$rok])) $roky6[$rok]= array(); // jsou v Answeru
        if (!in_array($jmeno_id,$roky2[$rok])) $roky2[$rok][]= $jmeno_id;
        if (!isset($roky3[$rok])) $roky3[$rok]= array(); // seznam
        $roky3[$rok][]= $jmeno_id;
        $roky4[]= $jmeno_id;
        if (!$ido) $roky5[$rok][]= $jmeno_id;
        if ($ido) {
          if (!in_array($ido,$roky6[$rok])) $roky6[$rok][]= $ido;
        }
        $od= min($od,$den);
        $do= max($do,$den);
        $n++;
      }
      $od= sql_date1($od);
      $do= sql_date1($do);
      $inf->html.= "<br>$n termínů dělených skupin od $od na webu <b>setkani.org</b> 
        a od léta 2020 do $do na webu <b>chlapi.cz</b>";
//                                        debug($roky5[2006],"2006 - neznáme");
//                                        debug($roky6[2022],"2022 - známe");
      foreach ($dny as $den=>$skupiny) {
        $rok= substr($den,0,4);
        $skupin= count($skupiny);
//          display("$rok $skupin");
        if (!isset($roky[$rok])) $roky[$rok]= array(0,0,0,0); // počet: setkání, skupin, účastí, stáří
        $roky[$rok][0]++; 
        $roky[$rok][1]+= $skupin; 
        $roky[$rok][2]+= array_sum($skupiny); 
        // průměr stáří
        $idos= implode(',',$roky6[$rok]);
        $roky[$rok][3]= select1("AVG(TIMESTAMPDIFF(YEAR,narozeni,'$rok-06-30'))",'ezer_db2.osoba',
            "id_osoba IN ($idos)");
//                                        display("$rok - věk={$roky[$rok][3]}");
      }
      // zobrazení
      $inf->html.= "<h3>Přehled dělených skupin brněnských chlapů podle let</h3>";
      $legenda= "<ol>
        <li>&sum; setkání: počet setkání s přihlašovací tabulkou
        <li>&Oslash; skupin: průměrně skupin - průměrný počet skupinek na jeden termín
        <li>&Oslash; chlapů: průměrně chlapů v jedné skupině
        <li>&sum; účastí: počet účastí = v podstatě (1)*(2)*(3)
        <li>X chlapů: různých chlapů - kolik chlapů se v roce zúčastnilo setkání 
        <li>@ chlapů: X chlapi, kteří jsou v Answeru
        <li>? chlapů: X chlapi, kteří nejsou v Answeru
        <li>&#10084; chlapů: průměrný věk @ chlapů
        <li>většinou: kolik X chlapů se zúčastnilo všech nebo všech až na jedno
        <li>poprvé a naposled - kolik chlapů přišlo na dělené setkání poprvé a pak už nikdy
        <li>dtto v procentech vzhledem k (5)
        </ol>";
      $td= "td style='text-align:right'";
      $inf->html.= "<table class='systable'><tr><th>rok</th>"
          . "<th>&sum; setkání</th><th>&Oslash; skupin</th>"
          . "<th>&Oslash; chlapů</th><th>&sum; účastí</th><th>X chlapů</th><th>@ chlapů</th><th>? chlapů</th>"
          . "<th>&#10084; chlapů</th><th>většinou</th><th colspan=2>poprvé a naposled</th></tr>";
      $hist4= array_count_values($roky4);
      foreach ($roky as $rok=>list($setkani,$skupin,$ucasti,$stari)) {
        if (!isset($inf->roky[$rok])) $inf->roky[$rok]= (object)array('vek'=>0);
        $p_skupin= number_format($skupin/$setkani,1);
        $p_ucast= number_format($ucasti/$skupin,1);
        $n_ucast= number_format($ucasti,0);
        $v_ucast= number_format($stari,0); // věk
        $inf->roky[$rok]->vek= $stari;
        $i_ucast= count($roky2[$rok]); // různí
        $a_ucast= count($roky6[$rok]); // identifikovaní v Answeru
        $na_ucast= count($roky5[$rok]); // neidentifikovaní v Answeru
        $x1_ucast= $xx_ucast= 0;
        $hist= array_count_values($roky3[$rok]);
//                                              debug($hist,$rok);
        foreach ($hist as $frequency) {
          $xx_ucast+= $frequency>=$setkani-1 ? 1 : 0;
        }
        foreach ($roky2[$rok] as $id) {
          $x1_ucast+= $hist4[$id]==1 ? 1 : 0;
        }
        $x1_proc= round(100*$x1_ucast/$i_ucast);
        $inf->html.= "<tr><th>$rok</th>"
            . "<$td>$setkani</td><$td>$p_skupin</td>"
            . "<$td>$p_ucast</td><$td>$n_ucast</td><$td>$i_ucast</td><$td>$a_ucast</td><$td>$na_ucast</td>"
            . "<$td>$v_ucast</td><$td>$xx_ucast</td><$td>$x1_ucast</td><$td>$x1_proc%</td></tr>";
        
      }
      $inf->html.= "</table>$legenda";
//      debug($inf->roky);
      break;
  }
//                                                      debug($inf,"stat_brno");
  return $inf;
}
# ----------------------------------------------------------------------------------==> . chart brno
# infografika údajů o LK podle db resp. pro YS podle dotazníku
# graf=line|bar|bar%|pie, x=od-do, y=vek|pocet [,z=typ-ucasti]
function chart_brno($par) { debug($par,'chart_akce2');
  $y= (object)array('err'=>'','note'=>'');
  $chart= (object)array('chart'=>(object)array());
  $regression= 0;
  switch ($par->graf) {
    case 'spline/regression':
      $chart->chart= 'spline';
      $regression= 1;
      break;
    case 'column':
      $chart->chart= 'column';
      $chart->plotOptions= (object)array();
      $chart->plotOptions->column= (object)array('stacking'=>$par->prc ? 'percent' : 'value');
      break;
    case 'pie':
      $chart->chart= 'pie';
      break;
    default:
      $chart->chart= $par->graf;
      break;
  }
  if ($par->rok=='od-do') { $od= $par->od; $do= $par->do; }
  $brno_s= stat_brno((object)array('fce'=>'n_skupin','delena'=>0)); unset($brno_s->html);
  $brno_d= stat_brno((object)array('fce'=>'n_skupin','delena'=>1)); unset($brno_d->html);
  $data= array('delena'=>array(),'spolecna'=>array());
  $roky= array();
  for ($rok= $od; $rok<=$do; $rok++) {
    $data['delena'][]= (float)number_format($brno_d->roky[$rok]->vek,1);
    $data['spolecna'][]= (float)number_format($brno_s->roky[$rok]->vek,1);
    $roky[]= $rok;
  }
  debug($data,'data');
  // názvy kategorií
  $names= array(
      'typ-ucasti'  => array('delena'=>"věk na dělených",'spolecna'=>"věk na společných")
  );
  $colors= array(
      'typ-ucasti'  => array('delena'=>'','spolecna'=>'')
  );
  $notes= array(
      'typ-ucasti'  => ""
  );
  $y->note= $notes[$par->z] ?: ' ';
  // zobrazený interval
  $chart->series= array();
  // popis os
  list($yTitle,$yMin,$yMax,$yTicks)= explode(',',$par->yaxis);
  foreach ($data as $id=>$serie) {
    $name= $names[$par->z][$id];
    $color= $colors[$par->z][$id] ? $colors[$par->z][$id] : null;
    $desc= (object)array('name'=>$name,'data'=>$serie);
    if ($color) $desc->color= $color;
    $chart->series[]= $desc;
    if ($regression && count($roky)>1) {
      $last_x= count($serie)-1;
      $lr= linear_regression(range(0,$last_x),$serie); $m= $lr['m']; $b= $lr['b']; 
      $desc= (object)array('name'=>"$name - trend", 
          'dashStyle'=>'Dash',
          'marker'=>(object)array('enabled'=>0),
          'data'=>array(array(0,round($b,1)),array($last_x,round($b+$m*$last_x,1))), 'color'=>'grey');
      if ($color) $desc->color= $color;
      $chart->series[]= $desc; 
    }
  }
//  goto end;
  $chart->title= $par->title;
  switch ($chart->chart) {
    case 'column':
      $chart->tooltip= (object)array(
        'pointFormat'=>"<span>{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>");
    case 'line':
    case 'spline':
      $chart->xAxis= (object)array('categories'=>$roky,
          'title'=>(object)array('text'=>'rok'));  
      $chart->yAxis= (object)array(
          'title'=>(object)array('text'=>$yTitle),
          'min'=>$yMin,'max'=>$yMax,'tickAmount'=>$yTicks);
      break;
    case 'pie':
      $chart->tooltip= (object)array(
        'pointFormat'=>"<span>{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>");
      break;
  }
  $y->chart= $chart;
  debug($y);
end:
  return $y;
}
# --------------------------------------------------------------------------------==> . sta2 ms stat
/** https://stackoverflow.com/questions/4563539/how-do-i-improve-this-linear-regression-function
 * linear regression function
 * @param $x array x-coords
 * @param $y array y-coords
 * @returns array() m=>slope, b=>intercept
 */
function linear_regression($x, $y) {
  // calculate number points
  $n = count($x);
  // ensure both arrays of points are the same size
  if ($n != count($y)) {
    trigger_error("linear_regression(): Number of elements in coordinate arrays do not match.", E_USER_ERROR);
  }
  // calculate sums
  $x_sum = array_sum($x);
  $y_sum = array_sum($y);
  $xx_sum = 0;
  $xy_sum = 0;
  for($i = 0; $i < $n; $i++) {
    $xy_sum+=($x[$i]*$y[$i]);
    $xx_sum+=($x[$i]*$x[$i]);
  }
  // calculate slope
  $m = (($n * $xy_sum) - ($x_sum * $y_sum)) / (($n * $xx_sum) - ($x_sum * $x_sum));
  // calculate intercept
  $b = ($y_sum - ($m * $x_sum)) / $n;
  // return result
  return array("m"=>$m, "b"=>$b);
}
# =============================================================================================> BAN
# ----------------------------------------------------------------------------------- ban maily_auto
function ban_maily_auto($patt,$par) {  //trace();
//                                                      debug($par,"test_auto.par");
  $a= (object)array();
  $limit= 10;
  $n= 0;
  if ( !$patt ) {
    $a->{0}= "... zadejte vzor";
  }
  else {
    if ( $par->prefix ) {
      $patt= "{$par->prefix}$patt";
    }
    // zpracování vzoru
    $qry= "SELECT id_osoba AS _key,IF(email REGEXP '^$patt.*|,$patt.*',email,gmail) AS _value
           FROM ezer_db2.osoba
           WHERE email REGEXP '^$patt.*|,$patt.*' OR gmail REGEXP '^$patt.*|,$patt.*' 
           ORDER BY email LIMIT $limit";
    $res= pdo_qry($qry);
    while ( $res && $t= pdo_fetch_object($res) ) {
      if ( ++$n==$limit ) break;
      $a->{$t->_key}= $t->_value;
    }
//                                                        display("test_auto:$n,$limit");
    // obecné položky
    if ( !$n )
      $a->{0}= "... nic nezačíná $patt";
    elseif ( $n==$limit )
      $a->{999999}= "... a další";
  }
//                                                      debug($a,"test_auto");
  return $a;
}
# ============================================================================================ MAILY
# ---------------------------------------------------------------------------------------- note text
# vytvoří dopis pro $who
function note_text($who) {
  $subst= array(
    'ds' => "<b>DS</b>: kniha hostů, účetní doklady, obchůdek+zásoby, fyzický stav pokladny (+soubor tabulka pokladna) – odpovídá Pepa Náprstek",
    'ms' => "<b>MS</b>: účetní doklady, soubor pokladní deník (export z Answeru), fyzický stav pokladny - Helenka Imramovská",
    'pc' => "<b>Pečovatelé<b>: účetní doklady, soubor pokladní deník (ve spolupráci s Jirkou), fyzický stav pokladny – Anička Štykarová-Lakosilová</li>",
    'pb' => "<b>MV</b>: účetní doklady, soubor pokladní deník, fyzický stav pokladny - Pavel Bajer",
    'mv' => "<b>Pokladna Krnov</b>: účetní doklady, fyzický stav pokladny – Miloš Vyleťal",
    'ja' => "<b>jen</b>: test"
  );
  $ref= "https://docs.google.com/spreadsheets/d/1WF1NPFWvGpMCLmgypNUOf901nWOG81o-xpmlSY1-oGA/edit#gid=1081184551";
  $text= "Přátelé,
    <br>prosím o <b>zapsání fyzického zůstatku</b> ve vámi vedené pokladně k poslednímu dni tohoto 
    měsíce na Intranetu <a href='$ref' target='doc'>zde</a>
    <br><b>Účetní zůstatek ponechte prázdný pro doplnění</b> od našeho účetního.
    <br>Děkuji a přeji vše dobré.
    <br>Miloš
    <br>
    <br>P.S. Stav zůstatků pokladen je potřeba <b>zapisovat nejpozději první den následujícího měsíce</b>.</li>
    <br><b>Do 5. dne každého měsíce</b> nejpozději je nutné zaslat účetnímu následující podklady:
    <br>{$subst[$who]}
    <br><br><em>Pokud jste již vše vyřídili, tak prosím přijměte poděkování i od automatického upomínače :-) </em>";
  // dočasná poznámka
//  $text.= "<br><em>Toto upozornění vám bude jinak chodit vždy ke konci měsíce.</em>";
  return $text;
}
# ---------------------------------------------------------------------------------------- note send
# pošle dopis pro $who - pokud je to * tak všem
function note_send($whos) {
  $adresy= array(
    'ds' => "dum@setkani.org,ivana.zivnustkova@seznam.cz",
    'ms' => "pokladna@setkani.org",
    'pc' => "bucek@fem.cz",
    'pb' => "mila.bajerova@volny.cz,pavel.bajer@volny.cz",
    'mv' => "ymca@setkani.org",
    'ja' => "martin@smidek.eu"
  );
  $n= 0;
  $whos= $whos=='*' ? array_keys($adresy) : explode(',',$whos);
  foreach ($whos as $who) {
    $text= note_text($who);
    $ok= rr_send_mail("Měsíční připomenutí zápisu zůstatků pokladen",$text,
        'ymca@setkani.org',$adresy[$who],'','mail','ymca@setkani.org');
    if (!$ok) break;
    $n++;
  }
  $html= "odesláno $n mailů z ".count($whos);
  return $html;
}
# ============================================================================================ BIBLE
# --------------------------------------------------------------------------------- bib save_aliases
# aktualizuje aliasy dané knihy
function bib_save_aliases($kniha,$aliasy,$nazev,$poradi) {
  list($as,$poradi)= 
      select('GROUP_CONCAT(alias),MIN(poradi)','bible_kniha',"kniha='$kniha' ",'setkani');
  $old= preg_split("~\s*,\s*~",$as);
  $new= preg_split("~\s*,\s*~",$aliasy);
  // přidáme chybějící
  $xs= array_diff($new,$old);
  foreach ($xs as $x) {
    if ($x!=$kniha) { // knihu přidat nesmíme
      query("INSERT INTO bible_kniha (kniha,alias,bible) VALUES ('$kniha','$x',1)",'setkani');
    }
  }
  // odebereme přebývající
  $xs= array_diff($old,$new);
  foreach ($xs as $x) {
    if ($x!=$kniha) { // knihu odebrat nesmíme
      query("DELETE FROM bible_kniha WHERE kniha='$kniha' AND alias='$x' ",'setkani');
    }
  }
  // případně upravíme název
  if ($nazev) {
    query("UPDATE bible_kniha SET nazev='$nazev',poradi=$poradi WHERE kniha='$kniha' AND alias='$kniha' ",'setkani');
  }
  // případně upravíme pořadí
  if ($poradi) {
    query("UPDATE bible_kniha SET poradi='$poradi' WHERE kniha='$kniha' AND alias='$kniha' ",'setkani');
  }
}
# ============================================================================================ STAMP
# --------------------------------------------------------------------------------------- stamp show
# zobrazí časová razítka
function stamp_show($typ,$subj='') {
  $html= '<dl>';
  ezer_connect("ezertask");
  $rs= pdo_qry("SELECT DATE(kdy),GROUP_CONCAT(TIME(kdy)),pozn  
    FROM stamp WHERE typ='$typ' GROUP BY CONCAT(DATE(kdy),pozn) ORDER BY kdy DESC LIMIT 24");
  while ( $rs && (list($den,$cas,$pozn)= pdo_fetch_row($rs)) ) {
    $html.= "<dt>$den $cas</dt><dd>$pozn $subj</dd>";
  }
  $html.= "</dl>";
  return $html;
}
# ============================================================================================== CAC
# ------------------------------------------------------------------------------------ cac show_diff
# vrátí překlad s vyznačenými odchylkami od původního překladu DeepL
#   par.html zaobrazí jako html jinak s entitami
#   par.ins zobrazí zeleně přidaný text
#   par.del zobrazí červeně zrušený text
# používá modul https://github.com/gorhill/PHP-FineDiff
function cac_show_diff($idc,$par) {
  require_once 'finediff.php';
  $html= "<style type='text/css'>";
  $html.= $par->ins ? "ins {color:green;background:#dfd;text-decoration:none}" : "ins {display:none}";
  $html.= $par->del ? "del {color:red;background:#fdd;text-decoration:none}" : "del {display:none}";
  $html.= "</style>";
  list($to_text,$from_text)= select('text_cz,text_cz_deepl','cac',"id_cac=$idc");
  // odstraníme html tagy
  if ($par->html) {
    $to_text= preg_replace('~<\/?[^>]+>~', '', strtr($to_text,array('&nbsp;'=>' ','</p>'=>"\n")));
    $to_text= str_replace("\n\n\n","\n\n",$to_text);
    $from_text= preg_replace('~<\/?[^>]*>~', '', strtr($from_text,array('&nbsp;'=>' ','</p>'=>"\n")));
    $from_text= str_replace("\n\n","\n",$from_text);
  }
  $granularity= 2;
  $granularityStacks = array(
      FineDiff::$paragraphGranularity,
      FineDiff::$sentenceGranularity,
      FineDiff::$wordGranularity,
      FineDiff::$characterGranularity
      );
  $diff_opcodes = FineDiff::getDiffOpcodes($from_text, $to_text, $granularityStacks[$granularity]);
  $html.= FineDiff::renderDiffToHTMLFromOpcodes($from_text, $diff_opcodes);
  if ($par->html) {
    $html= str_replace("\n\n","\n",$html);
    $html= strtr($html,array('<del>\n</del>'=>'',"\n"=>'<br><br>'));
  }
  return $html;
}
# ------------------------------------------------------------------------------------ cac make_free
# TECHNICKÁ funkce - uvolní datum ale pokud
#  je na webu => vrátí upozornění
#  překládáno => vrátí na rezervováno, smaže texty
#  rezervováno => smaže texty
function cac_make_free($idc) {
  $msg= '';
  $stav= select('stav','cac',"id_cac=$idc");
  if ($stav!=3) {
    if ($stav==2) $stav= 1;
    query("UPDATE cac SET stav=$stav,id_cactheme=0,
      text_eng='',title_eng='',imported_eng='',url_text='',author='',reference='',
      text_cz='',title_cz='',title_cz_deepl='',changed_cz='' WHERE id_cac=$idc");
  }
  else {
    $msg= 'publikovaný text nelze zrušit';
  }
  return $msg;
}
# ------------------------------------------------------------------------------- cac get_new_medits
# doplní nové úvahy do CAC
function cac_through_DeepL($idc) {
  global $ezer_server_ostry;
  list($idt,$theme_eng,$theme_cz,$title_eng,$text_eng)= 
      select('id_cactheme,theme_eng,theme_cz,title_eng,text_eng',
          'cac LEFT JOIN cactheme USING (id_cactheme)',"id_cac=$idc");
  // překlad téma, není-li
  if ($idt && !$theme_cz) {
    $theme_cz= cac_deepl_en2cs($theme_eng);
    query("UPDATE cactheme SET theme_cz=\"$theme_cz\" WHERE id_cactheme=$idt");
  }
  // překlad textu
  if (!$ezer_server_ostry) // v lokálu neplýtváme :-) ... domény bean a petr
    $text_eng= "<p>Testing <em>this</em> awesome <b>translator.</b></p>";
  $title_cz= cac_deepl_en2cs($title_eng);
  $text_cz= cac_deepl_en2cs($text_eng);
  $dt= date('Y-m-d H:i:s');
  query("UPDATE cac SET changed_cz='$dt',
    text_cz=\"$text_cz\",title_cz=\"$title_cz\",text_cz_deepl=\"$text_cz\" WHERE id_cac=$idc");
  return $title_cz;
}
# ---------------------------------------------------------------------------------- cac deepl_en2cs
# překlad anglického textu pomocí DeepL
function cac_deepl_en2cs($eng) {
  global $deepl_auth_key;
  $cz= '';
  $options= array(
    CURLOPT_URL => 'https://api-free.deepl.com/v2/translate',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => http_build_query(array(
      'auth_key' => $deepl_auth_key,
      'text' => $eng, 'source_lang' => 'EN', 'target_lang' => 'CS'
    )), 
    CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
  );
  $ch= curl_init();
  curl_setopt_array($ch, $options);
  $result= curl_exec($ch);
  if (curl_errno($ch)) { 
    $err= 'Error:' . curl_error($ch);
    fce_warning($err);
    goto end;
  }
  // extrakce textu
  $translatedWords= json_decode($result, true); // Decode the word
  $cz= $translatedWords['translations'][0]['text']; // Search the word
  $cz= pdo_real_escape_string($cz);
end:  
  curl_close($ch);
  return $cz;
}
# ---------------------------------------------------------------------------------- cac read_medits
# doplní nové úvahy do CAC 
#   $dueto=AUTO|TEST|USER|MENU
function cac_read_medits($dueto) {
  $msg= '';
  $ok= 0;
  $dnes= date('Y-m-d');
  // nejprve zjisti, jestli už jsme dnešní den neimportovali
  $mame= select('datum','cac',"datum='$dnes' AND text_eng!='' ");
  if ($mame) { 
    $ok= 1; $msg= "dnešní meditaci už máme"; 
    // zápis do stamp
    $dt= date('Y-m-d H:i:s');
    query("INSERT INTO stamp (typ,kdy,pozn) VALUES ('cac','$dt','$dueto READ: $dnes already exists')");
    goto end; 
  }
  // zajištění naplnění prázdnými záznamy na měsíc dopředu
  $date= new DateTime($dnes);
  $date->modify('+1 month');
  $za_mesic= $date->format('Y-m-d');
  // zajisti aby byly prázdné záznamy od posledního až do za měsíc
  $last= select('datum','cac',"1 ORDER BY datum DESC LIMIT 1");
  if (!$last) $last= '2021-12-31'; // start
  $date= new DateTime($last);
  $z= 100; // zarážka
  while ($z>0 && $last<$za_mesic) {
    $z--;
    $date->modify('+1 day');
    $last= $date->format('Y-m-d');
    query("INSERT INTO cac (datum) VALUE ('$last')");
  }
  // potom doplň z CAC chybějící texty
  $n= 3;
  $last= select('datum','cac',"IFNULL(text_eng,'')!='' ORDER BY datum DESC LIMIT 1");
  if (!$last) $last= '2021-12-31'; // start
  while ($n>0 && $last<$dnes) {
    $n--;
    $date= new DateTime($last);
    $date->modify('+1 day');
    $last= $date->format('Y-m-d');
    // získání a zápis úvahy
    $x= cac_save_medit($dueto,$last);
    if ($x->idc) {
      $title_cz= cac_through_DeepL($x->idc);
      $msg.= "$last: $x->title ... $title_cz<br>";
    }
    else {
      $msg.= "SELHALO";
    }
    $ok= 1;
  }
  $msg=  $ok ? $msg : ' novější úvahy CAC zatím nejdou importovat ';
end:  
  return $msg;
}
# ----------------------------------------------------------------------------------- cac save_medit
# uloží do daného dne danou meditaci - pokud je úspěšně načtená
function cac_save_medit($dueto,$last) { trace();
  // načtení úvahy
  $x= cac_read_medit($dueto,$last);
  // zapsání úvahy do tabulky
  $x->idc= select('id_cac','cac',"datum='$last'");
  if (!$x->idc) {
    query("INSERT INTO cac (datum) VALUE ('$last')");
    $x->idc= pdo_insert_id();
  }
  $tema= pdo_real_escape_string($x->tema);
  // test, jestli nejde o nové téma
  $idt= select('id_cactheme','cactheme',"theme_eng='$tema' ");
  if (!$idt) {
    query("INSERT INTO cactheme SET theme_eng='$tema',url_theme='$x->url_tema' ");
    $idt= pdo_insert_id();
  }
  $title= pdo_real_escape_string($x->title);
  $text= pdo_real_escape_string($x->text);
  $reference= pdo_real_escape_string($x->reference);
  $dt= date('Y-m-d H:i:s');
  if ($text) {
    query("UPDATE cac SET 
        id_cactheme='$idt',url_text='$x->url_title',imported_eng='$dt',
        author='$x->autor',reference='$reference',title_eng='$title',text_eng='$text' 
        WHERE id_cac=$x->idc");
  }
  return $x;
}
# ----------------------------------------------------------------------------------- cac read_medit
# vrátí meditaci ze dne {r,m,d} jako objekt {ok,datum,title,url_title,tema,url_tema,autor,text}
function cac_read_medit($dueto,$ymd) { 
  $ret= (object)array('ok'=>0,'stamp'=>"$dueto READ: ");
  $cac_month= "https://cac.org/category/daily-meditations";
  list($year,$month,$day)= explode('-',$ymd);
  $cac_month= "$cac_month/$year/$month/";
  $html= file_get_contents($cac_month);
  // rozklad
  $m= null;
  $ret->ok= preg_match_all(
      '~<h2 class="daily-meditations-loop__title">\s*<a href="([^"]+)">([^<]+)</a>\s*</h2>\s*'
      . '<div class="daily-meditations-loop__themes">\s*<small>Theme:</small>\s*'
      . '<a href="([^"]+)" class="daily-meditations-loop__theme">([^<]+)</a>\s*</div>'
      . '\s*<div class="daily-meditations-loop__tags">\s*<small>Tags:<\/small>(?:\s*<a href="[^"]+" class="daily-meditations-loop__tag">[^<]*<\/a>)+\s*<\/div>\s*<div class="daily-meditations-loop__author"><small>Author:<\/small>\s*<strong>([^<]+)<\/strong>\s*<\/div>~',
      $html,$m);
  $ret->stamp.= "match1={$ret->ok}; ";
  display("načtení měsíce: $ret->ok úvah ($cac_month)");
  for ($i= 0; $i<count($m[0]); $i++) {
    $ret->date= '';
    // text daného dne
    $ret->title= $m[2][$i];
    $ret->url_title= $m[1][$i];
    $ret->tema= $m[4][$i];
    $ret->url_tema= $m[3][$i];
    $ret->autor= $m[5][$i];
    $cac_day= $m[1][$i];
    $d= null;
    preg_match('~.*(\d\d\d\d-\d\d-(\d\d))~',$cac_day,$d);
    if ($d[2]!=$day) continue;
    $ret->stamp.= "title={$ret->title}; ";
    $ret->date= sql_date1($d[1]);
    $html= file_get_contents($cac_day);
    $p= null;
    $ret->cut= preg_match(
        '~<div class="(?:wp-block-gecko-blocks-section|entry-content article)">(.*)~ms',
        $html,$p);
    $ret->stamp.= "match2={$ret->cut}; ";
    display("načtení dne: $ret->ok ($cac_day)");
    $text= preg_split('~<p><strong>(Story|Reference(?:s|)|Explore|Breath)~',$p[1],-1,PREG_SPLIT_DELIM_CAPTURE);
    $ret->text= $text[0];
    $ret->reference= '';
    for ($i= 1; $i<count($text); $i+= 2) {
      if (substr($text[$i],0,3)=='Ref') {
        $ret->reference= "<strong>Odkazy{$text[$i+1]}";
      }
    }
    break;
  }
  // zápis do stamp
  $dt= date('Y-m-d H:i:s');
  query("INSERT INTO stamp (typ,kdy,pozn) VALUES ('cac','$dt','$ret->stamp')");
  // návrat
  return $ret;
}
# --------------------------------------------------------------------------------- cac change_state
# změní stav překladu a případně odebere nebo přidá překladatele
# TODO otestovat, zda existuje překlad
function cac_change_state($idc,$s) {
  global $USER;
  $msg= '';
  $preklada= select('preklada','cac',"id_cac=$idc");
  $dt= date('Y-m-d H:i:s');
  $me= $USER->id_user;
  if (!$me) fce_error("uživatel není přihlášen, nelze provést změnu stavu");
  if (!$preklada) {
    query("UPDATE cac SET preklada=$me,stav=$s WHERE id_cac=$idc");
  }
  else {
    $kdo= $s==0 ? 0 : $me;
    query("UPDATE cac SET changed_cz='$dt',stav=$s, preklada=$kdo WHERE id_cac=$idc");
  }
  return $msg;
}
# ------------------------------------------------------------------------------------- cac save_fld
# uloží text překladu (obejdeme form.save kvůli problémům s FCEditorem)
function cac_save_fld($idc,$fld,$text) {
  $text= pdo_real_escape_string($text);
  query("UPDATE cac SET $fld='$text' WHERE id_cac=$idc");
}
# =============================================================================================== RR
# ---------------------------------------------------------------------------------------- rr nastav
# $par = {den:ode dneška,poslat: 0/1}
function rr_nastav($den,$datum,$pocet) {  trace();
  $ret= (object)array('msg'=>'','last'=>'','next'=>'');
  $nastaveno= $zruseno= 0;
  $dat= sql_date1($datum,1);
  $ndat0= sql2stamp($dat);
  for ($d= 0; $d<$pocet; $d++) {
    $day_n= $den+$d;
    $ndatum= date('Y-m-d',$ndat0+$d*60*60*24);
    $zruseno+= query("UPDATE rr SET datum='0000-00-00',state='' WHERE datum='$ndatum'");
    $nastaveno+= query("UPDATE rr SET datum='$ndatum',state='prepared' WHERE day_n=$day_n");
  }
  $ret->last= date('Y-m-d',$ndat0+($pocet-1)*60*60*24);
  $ret->next= date('Y-m-d',$ndat0+$pocet*60*60*24);
  $ret->msg= "nastaveno $nastaveno dnů od $dat po $ndatum, zrušeno $zruseno předchozích nastavení";
  return $ret;
}
# ------------------------------------------------------------------------------------------ rr zrus
# zruší nastavená data
function rr_zrus($den,$pocet) {  trace();
  $zruseno= 0;
  for ($d= 0; $d<$pocet; $d++) {
    $day_n= $den+$d;
    $zruseno+= query("UPDATE rr SET datum='0000-00-00',state='unasigned' WHERE day_n=$day_n");
  }
  $msg= "zrušeno $zruseno předchozích nastavení";
  return $msg;
}
# ------------------------------------------------------------------------------------------ rr send
# $par = {den:ode dneška,poslat: 0/1}
function rr_send($par) {
  $offset= $par->den<0 ? "-INTERVAL ".abs($par->den)." DAY" : ($par->den>0 ? "+INTERVAL {$par->den} DAY" : '');
  $plus= $par->den ? $par->den : 0;
  $dnes= date('j/n/Y',mktime(0,0,0,date('n'),date('j')+$plus,date('Y')));
  $html= "neni pro $dnes nastaveno! ($offset)";
  ezer_connect("ezertask");
  $qry= "SELECT * FROM rr WHERE datum=curdate()$offset ";
  $res= pdo_qry($qry);
  while ( $res && ($o= pdo_fetch_object($res)) ) {
    $day_n= $o->day_n;
    $subject= $o->subject;
    $state= $o->state;
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
    $subj= "Richard Rohr na $subject - $title_cz";
    $body= "<table cellpadding='10'><tr>";
    $body.= "<td valign='top' width='50%'><b>$title_cz</b><br>$text_cz</td>";
    $body.= "<td valign='top' width='50%'><b>$title_en</b><br>$text_en<div align='right'>$from_en</div></td>";
    $body.= "</tr></table>";
    $html= "<h1>$subj</h1>$body";
    if ( $par->poslat  ) {
      if ( $state=='prepared' || $par->opakovat || $_GET['again']=='yes' ) {
        // odeslání a ochrana proti zdvojení
        $email= isset($par->test)
            ? $par->test
            : ($_GET['email'] ? $_GET['email'] : 'chlapi-myslenky@googlegroups.com');
        $html.= "<hr/>zaslání na <i>$email</i> skončilo se stavem ";
        $ok= rr_send_mail($subj,$body,'martin.smidek@setkani.org',$email,'Richard Rohr','rr');
        $html.= $ok;
        //$html.= $mail->sendHtmlMail('smidek@proglas.cz',$email,'','',$subj,$body,'Richard Rohr');
        if ( $ok && !isset($_GET['email']) ) {
          query("UPDATE rr SET state='sent' WHERE day_n=$day_n ");
        }
      }
      else $html= "Pozor! Už bylo jednou zasláno, lze vynutit klíčem again=yes<hr/>$html";
    }
  }
  return $html;
}
# ---------------------------------------------------------------------------------------- send mail
# pošle systémový mail, pokud není určen adresát či odesílatel jde o mail správci aplikace
# $to může být seznam adres oddělený čárkou
function rr_send_mail($subject,$html,$from='',$to='',$fromname='',$typ='',$replyto='') { //trace();
  global $ezer_path_serv, $EZER, $api_gmail_user, $api_gmail_pass;
  $to= $to ? $to : $EZER->options->mail;
  // poslání mailu
  $phpmailer_path= "$ezer_path_serv/licensed/phpmailer";
  require_once("$phpmailer_path/class.smtp.php");
  require_once("$phpmailer_path/class.phpmailer.php");
  // napojení na mailer
  $mail= new PHPMailer;
  $mail->SetLanguage('cs',"$phpmailer_path/language/");
  
  $mail->IsSMTP();
  $mail->Mailer= 'smtp';
  $mail->Host= "smtp.gmail.com";
  $mail->Port= 465;
  $mail->SMTPAuth= 1;
  $mail->SMTPSecure= "ssl";
  $mail->Username= $api_gmail_user;
  $mail->Password= $api_gmail_pass;
  $mail->CharSet = "utf-8";
  $mail->From= $from;
  $mail->AddReplyTo($replyto?:$from);
  $mail->FromName= $fromname;
  foreach (explode(',',$to) as $to1) {
    $mail->AddAddress($to1);
  }
  $mail->Subject= $subject;
  $mail->Body= $html;
  $mail->IsHTML(true);
  // pošli
  $ok= $mail->Send();
  if ( !$ok )
    fce_warning("Selhalo odeslání mailu: $mail->ErrorInfo");
  else {
    // zápis do stamp
    $dt= date('Y-m-d H:i:s');
    query("INSERT INTO stamp (typ,kdy,pozn) VALUES ('$typ','$dt','$subject')");
  }
  return $ok;
}
