<?php

// ---------------------------------------------------------------------------------------------- //
// funkce aplikace chlapi.online pro mode CMS                                                     //
//                                                                                                //
// CMS/Ezer                                             (c) 2018 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //

/** ===========================================================================================> WEB */
# ------------------------------------------------------------------------------------ menu add_elem
# přidá do menu další element
function menu_copy_elem($from,$pid,$mid,$type) {
  global $y, $ezer_local;
  $first= true;
  // získej kopii článku
  ask_server((object)array('cmd'=>'clanek','pid'=>$pid));
  $fileadmin= $ezer_local 
      ? "http://setkani.bean:8080/fileadmin"
      : "https://www.setkani.org/fileadmin";
  // uprav odkazy
  $obsah= preg_replace("/(src|href)=(['\"])(?:\\/|)fileadmin/","$1=$2$fileadmin",$y->obsah);
  $clanek= "<h1>$y->nadpis</h1>$obsah";
  $clanek= str_replace("'","\\'",$clanek);
  query("INSERT INTO xclanek (web_text) VALUES ('$clanek')");
  $id= mysql_insert_id();
  // přidej do menu.elem
  $elem= select("elem","menu","wid=2 AND mid=$mid");
  if ( $first )
    $elem= "$type=$id" . ($elem ? ";$elem" : '');
  else
    $elem= ($elem ? "$elem;" : '') . "$type=$id";
  query("UPDATE menu SET elem='$elem' WHERE wid=2 AND mid=$mid");
  return 1;
}
# ------------------------------------------------------------------------------------ menu add_elem
# přidá do menu další element
function menu_add_elem($mid,$table,$first=0) {
  $elem= select("elem","menu","wid=2 AND mid=$mid");
  query("INSERT INTO $table () VALUES ()");
  $id= mysql_insert_id();
  if ( $first )
    $elem= "$table=$id" . ($elem ? ";$elem" : '');
  else
    $elem= ($elem ? "$elem;" : '') . "$table=$id";
  query("UPDATE menu SET elem='$elem' WHERE wid=2 AND mid=$mid");
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
# --------------------------------------------------------------------------------------- access_get
# vrátí přístupová práva ve _SESSION[web][fe_usergroups]
function access_get($key=0) {
  $ret= '';
  if ( $key ) {
    $x= explode(',',$_SESSION['web']['fe_usergroups']);
    $i= array_search($key,$x);
    $ret= $i===false ? 0 : 1;
  }
  else {
    $ret= $_SESSION['web']['fe_usergroups'];
  }
  return $ret;
}
# --------------------------------------------------------------------------------------- access_set
# upraví přístupová práva ve _SESSION[web][fe_usergroups]
function access_set($keys,$on) {
  $x= explode(',',$_SESSION['web']['fe_usergroups']);
  foreach (explode(',',$keys) as $key) {
    $i= array_search($key,$x);
    if ( $i===false && $on )
      $x[]= $key;
    elseif ( $i!==false && !$on )
      unset($x[$i]);
  }
  $_SESSION['web']['fe_usergroups']= implode(',',$x);
  return 1;
}
# --------------------------------------------------------------------------------------- visibility
# vrátí resp. nastaví nastavenou hodnotu _SESSION[web][hidden|deleted]
function visibility($key,$value='-') {
  if ( $value=='-' ) { // getter
    $y= isset($_SESSION['web'][$key]) ? $_SESSION['web'][$key] : 0;
  }
  else {
    $_SESSION['web'][$key]= $value;
    $y= 1;
  }
  return $y;
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
    $res= mysql_query($qry);
  }
  return $ip_ok;
}
?>
