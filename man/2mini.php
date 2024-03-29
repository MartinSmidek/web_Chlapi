<?php
// <editor-fold defaultstate="collapsed" desc="++++++++++++++++++++++++++ EZER functions">
/** ==========================================================================================> EZER */
function wu($x) { return $x; }
# -------------------------------------------------------------------------------------- kolik_1_2_5
# výběr správného tvaru slova podle množství a tabulky tvarů pro 1,2-4,5 a více
# např. kolik_1_2_5(dosp,"dospělý,dospělí,dospělých")
function kolik_1_2_5($kolik,$tvary) { //trace();
  $tvar= explode(',',$tvary);
  return "$kolik ".($kolik>4 ? $tvar[2] : ($kolik>1 ? $tvar[1] : ($kolik>0 ? $tvar[0] : $tvar[2])));
}
# -------------------------------------------------------------------------------------------- trace
# $note je poznámka uvedená za trasovací informací
function trace($note='') { //,$coding='') {
  global $trace, $totrace;
  if ( strpos($totrace,'u')===false ) return;
  $act= debug_backtrace();
  $x= call_stack($act,1).($note?" / $note":'');
  global $REDAKCE;
  if ( $REDAKCE ) {
    $time= date("H:i:s");
    $trace.= "$time $x<br>";
  }
  else {
    $rx= str_replace("'",'"',$x);
    $line= str_pad($act[1]['line'],4,' ',STR_PAD_LEFT);
    $trace.= "<script>console.log( ':  trace/$line: $rx' );</script>";
  }
}
function display($x) {
  global $trace, $totrace;
  if ( strpos($totrace,'u')===false ) return;
  global $REDAKCE;
  if ( $REDAKCE ) {
    $trace.= "$x<br>";
  }
  else {
    $rx= str_replace("'",'"',$x);
    $stack= debug_backtrace();
    $line= str_pad($stack[0]['line'],4,' ',STR_PAD_LEFT);
    $trace.= "<script>console.log( ':display/$line: $rx' );</script>";
  }
}
function debug($x,$label=false,$options=null) {
  global $trace, $totrace;
  if ( strpos($totrace,'u')===false ) return;
  global $REDAKCE;
  if ( $REDAKCE ) {
    debug1($x,$label,$options);
  }
  else {
//    $x= pdo_real_escape_string(var_export($x,true));
    $x= str_replace("'",'"',var_export($x,true));
    $trace.= "<script>console.log( \"" . str_replace("\n",'\n',$x) . "\" );</script>";
  }
}
# -------------------------------------------------------------------------------------------- debug
# vygeneruje čitelný obraz pole nebo objektu
# pokud jsou data v kódování win1250 je třeba použít  debug($s,'s',(object)array('win1250'=>1));
# options:
#   gettype=1 -- ve třetím sloupci bude gettype(hodnoty)
function debug1($gt,$label=false,$options=null) {
  global $trace, $debug_level;
  $debug_level= 0;
  $html= ($options && $options->html) ? $options->html : 0;
  $depth= ($options && $options->depth) ? $options->depth : 64;
  $length= ($options && $options->length) ? $options->length : 64;
  $win1250= ($options && $options->win1250) ? $options->win1250 : 0;
  $gettype= ($options && $options->gettype) ? 1 : 0;
  if ( is_array($gt) || is_object($gt) ) {
    $x= debugx($gt,$label,$html,$depth,$length,$win1250,$gettype);
  }
  else {
//     $x= $html ? htmlentities($gt) : $gt;
    $x= $html ? htmlspecialchars($gt,ENT_NOQUOTES,'UTF-8') : $gt;
    $x= "<table class='dbg_array'><tr>"
      . "<td valign='top' class='title'>$label</td></tr><tr><td>$x</td></tr></table>";
  }
  if ( $win1250 ) $x= wu($x);
//   $x= strtr($x,'<>','«»'); //$x= str_replace('{',"'{'",$x);
  $trace.= $x;
  return $x;
}
function debugx(&$gt,$label=false,$html=0,$depth=64,$length=64,$win1250=0,$gettype=0) {
  global $debug_level;
  if ( $debug_level > $depth ) return "<table class='dbg_over'><tr><td>...</td></tr></table>";
  if ( is_array($gt) ) {
    $debug_level++;
    $x= "<table class='dbg_array'>";
    $x.= $label!==false
      ? "<tr><td valign='top' colspan='".($gettype?3:2)."' class='title'>$label</td></tr>" : '';
    foreach($gt as $g => $t) {
      $x.= "<tr><td valign='top' class='label'>$g</td><td>"
      . debugx($t,NULL,$html,$depth,$length,$win1250,$gettype) //TEST==1 ? $t : htmlspecialchars($t)
      .($gettype ? "</td><td>".gettype($t) : '')                      //+typ
      ."</td></tr>";
    }
    $x.= "</table>";
    $debug_level--;
  }
  else if ( is_object($gt) ) {
    $debug_level++;
    $x= "<table class='dbg_object'>";
    $x.= $label!==false ? "<tr><td valign='top' colspan='".($gettype?3:2)."' class='title'>$label</td></tr>" : '';
//     $obj= get_object_vars($gt);
    $len= 0;
    foreach($gt as $g => $t) {
      $len++;
      if ( $len>$length ) break;
//       if ( is_string($t) ) {
//         $x.= "<td>$g:$t</td>";
//       }
//       if ( $g=='parent' ) {
//         $td= $t==null ? "<td class='label'>nil</td>" : (
//           is_object($t) && isset($t->id) ? "<td class='label'>{$t->id}</td>" : (
//           is_string($t) ? "<td>$t</td>" :
//           "<td class='label'>?</td>"));
//         $x.= "<tr><td class='dbg_over'>$g:</td>$td</tr>";
//       }
//       else {
        $x.= "<tr><td valign='top' class='label'>$g:</td><td>"
        . debugx($t,NULL,$html,$depth,$length,$win1250,$gettype) //TEST==1 ? $t : htmlspecialchars($t)
        .($gettype ? "</td><td>".gettype($t) : '')                      //+typ
        ."</td></tr>";
//       }
    }
    $x.= "</table>";
    $debug_level--;
  }
  else {
    if ( is_object($gt) )
      $x= "object:".get_class($gt);
    else
//       $x= $html ? htmlentities($gt) : $gt;
      $x= $html ? htmlspecialchars($gt,ENT_NOQUOTES,'UTF-8') : $gt;
//       if ( is_string($x) ) $x= "'$x'";
  }
  return $x;
}
function fce_error($x) {
  global $trace;
  $x= pdo_real_escape_string($x);
  global $REDAKCE;
  if ( $REDAKCE ) {
    $trace.= "ERROR: $x<br>";
  }
  else {
    $trace.= "<script>console.log( 'ERROR: " . $x . "' );</script>";
  }
}
# --------------------------------------------------------------------------------------- call_stack
function call_stack($act,$n,$hloubka=2,$show_call=1) { #$this->debug($act,'call_stack');
  $fce= isset($act[$n]['class'])
    ? "{$act[$n]['class']}{$act[$n]['type']}{$act[$n]['function']}" : $act[$n]['function'];
  $del= '';
  $max_string= 36;
  mb_internal_encoding("UTF-8");
  $args= '';
  if ( $show_call and isset($act[$n]['args']) )
  foreach ( $act[$n]['args'] as $arg ) {
    if ( is_string($arg) ) {
      $arg= mb_substr(htmlspecialchars($arg,ENT_NOQUOTES,'UTF-8'),0,$max_string)
          .(mb_strlen($arg)>$max_string?'...':'');
    }
    $typ= gettype($arg);
    $val= '';
    switch ( $typ ) {
    case 'boolean': case 'integer': case 'double': case 'string': case 'NULL':
      $val= $arg; break;
    case 'array':
      $val= count($arg); break;
    case 'object':
      $val= get_class($arg); break;
    }
    $args.= "$del$typ:$val";
    $del= ',';
  }
  $from= '';
  /*
  for ($k= $n; $k<$n+$hloubka; $k++) {
    if ( isset($act[$k]) )
    switch ( key($act[$k]) ) {
    case 'file':
      $from_file= str_replace('.php','',$act[$k]['file']);
      $from.= " < ".substr(strrchr($from_file,'\\'),1);
      $from.= "/{$act[$k]['line']}";
      break;
    case 'function':
      $from.= " < ".($act[$k]['class']?"{$act[$k]['class']}.":'').$act[$k]['function'];
      break;
    default:
      $from.= " < ? ";
      break;
    }
  }
  */
  return $show_call ? "$fce($args)$from" : $from;
}
# ------------------------------------------------------------------------------------- array2object
function array2object(array $array) {
  $object = new stdClass();
  foreach($array as $key => $value) {
    if(is_array($value)) {
      $object->$key = array2object($value);
    }
    else {
      $object->$key = $value;
    }
  }
  return $object;
}
# ======================================================================================= driver PDO
# ------------------------------------------------- ezer_connect
# spojení s databází
# $db = jméno databáze uvedené v konfiguraci aplikace
# $db = .main. pokud má být připojena první databáze z konfigurace
# $initial=1 pokud není ještě aktivní fce_error
function ezer_connect ($db0='.main.',$even=false,$initial=0) {
  global $curr_db, $ezer_db;
  $err= '';
  $db= $db0;
  if ( $db=='.main.' || !$db ) {
    reset($ezer_db);
    $db= key($ezer_db);
  }
  // ------------------------------------------- připojení PDO - return vrací PDO objekt!
  if ( isset($ezer_db[$db]) ) {
    if ( !$ezer_db[$db][0] || $even ) {
      // vlastní připojení, pokud nebylo ustanoveno
      $db_name= (isset($ezer_db[$db][5]) && $ezer_db[$db][5]!='') ? $ezer_db[$db][5] : $db;
      $dsn= "mysql:host={$ezer_db[$db][1]};dbname=$db_name;charset={$ezer_db[$db][4]}";
      $opt = [
          PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT, //PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES   => false,
          PDO::ATTR_STRINGIFY_FETCHES  => true,
      ];
      try {
                        $errmode= isset($_COOKIE['error_reporting']) ? $_COOKIE['error_reporting'] : 1;
                        if ( $errmode==333) {
                          if (!defined('PDO::ATTR_DRIVER_NAME')) {
                            echo 'PDO unavailable ... ';
                          }
                          else {
                            echo '('.print_r(PDO::getAvailableDrivers(),true).')';
                            echo "$db,$dsn,...";
                          }
                        }
        $ezer_db[$db][0]= new PDO($dsn, $ezer_db[$db][2], $ezer_db[$db][3], $opt);
      } 
      catch(PDOException $ex) {
        $err= "connect: databaze '$db_name' je nepristupna: ".$ex->getMessage();
        if ( !$initial ) fce_error($err);
        else die($err);
      }
    }
    $curr_db= $db;
    return $ezer_db[$db][0];
  }
  fce_error("connect: nezname jmeno '$db' databaze");
}
# ------------------------------------------------- pdo funkce
function pdo_num_rows($rs) {
  $num= $rs->rowCount();
  return $num;
}
function pdo_result($rs,$cnum) {
  $mix= $rs->fetchColumn($cnum);
  return $mix;
}
function pdo_fetch_object($rs) {
  $row= $rs->fetch(PDO::FETCH_OBJ);
  return $row;
}
function pdo_fetch_assoc($rs) {
  $row= $rs->fetch(PDO::FETCH_ASSOC);
  return $row;
}
function pdo_fetch_row($rs) {
  $row= $rs->fetch(PDO::FETCH_NUM);
  return $row;
}
function pdo_fetch_array($rs) {
  $row= $rs->fetch(PDO::FETCH_BOTH);
  return $row;
}
function pdo_fetch_all($rs) {
  $rows= $rs->fetchAll();
  return $rows;
}
function pdo_real_escape_string($inp) {
  return str_replace(
      array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), 
      array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
}
// pdo_query je netrasovaný dotaz - náhrada pdo_query
// pro INSERT|UPDATE|DELETE vrací počet modifikovaných řádků
// pro SELECT vrací PDOStatement
// jinak vrací chybu
function pdo_query($query) {
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  if ( preg_match('/^\s*(SET|INSERT|UPDATE|REPLACE|DELETE|TRUNCATE|DROP|CREATE|ALTER)/',$query) ) {
    $res= $pdo->exec($query);
    if ( $res===false ) fce_error($pdo->errorInfo()[2]);
  }
  else if ( preg_match('/^\s*(SELECT|SHOW)/',$query) ) {
    $res= $pdo->query($query);
    if ( $res===false ) fce_error($pdo->errorInfo()[2]);
  }
  else {
    fce_error("pdo_query nelze použít pro ".substr($query,0,6).' ...');
  }
  return $res;
}
function pdo_insert_id() {              
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  $id= $pdo->lastInsertId();
  return $id;
}
function pdo_error() {                
  global $ezer_db, $curr_db;
  $pdo= $ezer_db[$curr_db][0];
  $err= $pdo->errorInfo();
  return "You have an error in your SQL:".$err[2];
}
function pdo_object($qry) {
  $res= pdo_qry($qry,1);
  $x= $res ? pdo_fetch_object($res) : array();
  if ( !$res ) pdo_err($qry);
  return $x;
}
function pdo_affected_rows($res) {       
  // pro kompatibilitu s pdo_affcted_rows
  return ($res===false || is_int($res)) ? $res : $res->rowCount();
}
//function pdo_qry($qry,$pocet=null,$err=null,$to_throw=null,$db=null) {
//  return pdo_qry($qry,$pocet,$err,$to_throw,$db);
//}
# ------------------------------------------------------------------------------------------ pdo qry
# pdo_qry je trasovaný dotaz - náhrada pdo_qry
# pro INSERT|UPDATE|DELETE vrací počet modifikovaných řádků
# pro SELECT vrací PDOStatement
# jinak vrací chybu
# 
# provedení dotazu a textu v $s->qry="..." a případně doplnění $s->err
#   $qry      -- SQL dotaz
#   $pocet    -- pokud je uvedeno, testuje se a při nedodržení se ohlásí chyba
#   $err      -- text chybové hlášky, která se použije místo standardní ... pokud končí znakem':'
#                bude za ni doplněna standardní chybová hláška;
#                pokud $err=='-' nebude generována chyba a funkce vrátí false
#   $to_throw -- chyba způsobí výjimku
#   $db       -- před dotazem je přepnuto na databázi daného jména v tabulce $ezer_db nebo na hlavní
function pdo_qry($qry,$pocet=null,$err=null,$to_throw=false,$db='') {
  global $s, $totrace, $qry_del, $qry_count, $curr_db, $ezer_db;
//  if ( !isset($s) ) $s= (object)array();
  $msg= ''; $abbr= $ok= '';
  $qry_count++;
  $myqry= strtr($qry,array('"'=>"'","<="=>'&le;',"<"=>'&lt;'));
//                                                         display($myqry);
  // dotaz s měřením času
  $time_start= getmicrotime();
  // přepnutí na databázi
  if ( $db ) ezer_connect($db);
  $pdo= $ezer_db[$curr_db][0];
  if ( preg_match('/^\s*(SET|INSERT|UPDATE|REPLACE|DELETE|TRUNCATE|DROP|CREATE|ALTER)/',$qry) ) {
    // pro INSERT|UPDATE|DELETE vrací počet modifikovaných řádků
    $res= $pdo->exec($qry);
    if ( $res===false ) {
      $msg.= $pdo->errorInfo()[2];
    }
    $time= round(getmicrotime() - $time_start,4);
    if ( $pocet  ) {
//      fce_error("pdo_qry: OBSOLETE - 2.parametr (počet záznamů & PHP7/PDO)");
      if ( $pocet!=$res ) {
        if ( $res==0 ) {
          $msg.= "nezmenen zadny zaznam " . ($err ? ", $err" : ""). " v $qry";
          $abbr= '/0';
        }
        else {
          $msg.= "zmeneno $res zaznamu misto $pocet" . ($err ? ", $err" : ""). " v $qry";
          $annr= "/$res";
        }
        if ( isset($s) ) $s->ok= 'ko';
        $ok= "ko [$res]";
        $res= null;
      }
    }
  }
  else if ( preg_match('/^\s*(SELECT|SHOW)/',$qry) ) {
    // pro SELECT vrací PDOStatement
    $res= $pdo->query($qry);
    $time= round(getmicrotime() - $time_start,4);
    $ok= $res ? 'ok' : '--';
    if ( !$res ) {
      if ( $err==='-' ) goto end;
      $merr= $pdo->errorInfo()[2];
      $serr= "You have an error in your SQL";
      if ( $merr && substr($merr,0,strlen($serr))==$serr ) {
        $msg.= "SQL error ".substr($merr,strlen($serr))." in:$qry";
        $abbr= '/S';
      }
      else {
        $myerr= $merr;
        if ( $err ) {
          $myerr= $err;
          if ( substr($err,-1,1)==':' )
            $myerr.= $merr;
        }
        $myerr= str_replace('"',"U",$myerr);
        $msg.= "\"$myerr\" \nQRY:$qry";
        $abbr= '/E';
      }
      if ( isset($s) ) $s->ok= 'ko';
    }
    else if ( $pocet  ) {
//      fce_error("pdo_qry: OBSOLETE - 2.parametr (počet záznamů & PHP7/PDO)");
      $num= pdo_num_rows($res);
      if ( $pocet!=$num ) {
        if ( $num==0 ) {
          $msg.= "nenalezen záznam " . ($err ? ", $err" : ""). " v $qry";
          $abbr= '/0';
        }
        else {
          $msg.= "vraceno $num zaznamu misto $pocet" . ($err ? ", $err" : ""). " v $qry";
          $annr= "/$num";
        }
        if ( isset($s) ) $s->ok= 'ko';
        $ok= "ko [$num]";
        $res= null;
      }
    }
  }
  else {
    fce_error("pdo_qry nelze použít pro ".substr($qry,0,6).' ...');
  }
  if ( strpos($totrace,'M')!==false ) {
    $pretty= trim($myqry);
    if ( strpos($pretty,"\n")===false )
      $pretty= preg_replace("/(FROM|LEFT JOIN|JOIN|WHERE|GROUP|HAVING|ORDER)/","\n\t\$1",$pretty);
    if ( isset($s) ) $s->qry= (isset($s->qry)?"$s->qry\n":'')."$ok $time \"$pretty\" ";
  }
  if ( isset($s) ) $s->qry_ms= isset($s->qry_ms) ? $s->qry_ms+$time : $time;
  $qry_del= "\n: ";
  if ( $msg ) {
    if ( $to_throw ) throw new Exception($err ? "$err$abbr" : $msg);
    elseif ( isset($s) ) $s->error= (isset($s->error) ? $s->error : '').$msg;
    else fce_error($msg);
  }
end:
  return $res;
}
# ------------------------------------------------------------------------------------------- select
# navrácení hodnoty jednoduchého dotazu
# pokud $expr obsahuje čárku, vrací pole hodnot, pokud $expr je hvězdička vrací objekt
# příklad 1: $id= select("id","tab","x=13")
# příklad 2: list($id,$x)= select("id,x","tab","x=13")
function select($expr,$table,$cond=1,$db='.main.') {
  if ( strstr($expr,",") ) {
    $result= array();
    $qry= "SELECT $expr FROM $table WHERE $cond";
    $res= pdo_qry($qry,0,0,0,$db);
    if ( !$res ) { fce_error("chyba funkce select:$qry/".pdo_error()); goto end; }
    $result= pdo_fetch_row($res);
  }
  elseif ( $expr=='*' ) {
    $qry= "SELECT * FROM $table WHERE $cond";
    $res= pdo_qry($qry,0,0,0,$db);
    if ( !$res ) fce_error(wu("chyba funkce select:$qry/".pdo_error()));
    $result= pdo_fetch_object($res);
  }
  else {
    $result= '';
    $qry= "SELECT $expr AS _result_ FROM $table WHERE $cond";
    $res= pdo_qry($qry,0,0,0,$db);
    if ( !$res ) fce_error(wu("chyba funkce select:$qry/".pdo_error()));
    $o= pdo_fetch_object($res);
    $result= $o->_result_;
  }
//                                                 debug($result,"select");
end:
  return $result;
}
# ------------------------------------------------------------------------------------- getmicrotime
function getmicrotime() {
//   list($usec, $sec) = explode(" ", microtime());
//   return ((float)$usec + (float)$sec);
  return round(microtime(true)*1000);
}
# ------------------------------------------------------------------------------------------ select1
# navrácení hodnoty jednoduchého dotazu - $expr musí vracet jednu hodnotu
function select1($expr,$table,$cond=1,$db='.main.') {
  $result= '';
  $qry= "SELECT $expr AS _result_ FROM $table WHERE $cond";
  $res= pdo_qry($qry,0,0,0,$db);
  if ( !$res ) fce_error(wu("chyba funkce select1:$qry/".pdo_error()));
  $o= pdo_fetch_object($res);
  $result= $o->_result_;
  return $result;
}
# ------------------------------------------------------------------------------------ select_object
# navrácení hodnot jednoduchého jednoznačného dotazu jako objektu (funkcí pdo_fetch_object)
function select_object($expr,$table,$cond=1,$db='.main.') {
  $qry= "SELECT $expr FROM $table WHERE $cond";
  $res= pdo_qry($qry,0,0,0,$db);
  if ( !$res ) fce_error(wu("chyba funkce select_object:$qry/".pdo_error()));
  $result= pdo_fetch_object($res);
  return $result;
}
# -------------------------------------------------------------------------------------------- query
# provedení MySQL dotazu
function query($qry,$db='.main.') {
  $res= pdo_qry($qry,0,0,0,$db);
  if ( !$res ) fce_error(wu("chyba funkce query:$qry/".pdo_error()));
  return $res;
}
# ---------------------------------------------------------------------------------------- sql_query
# provedení MySQL dotazu
function sql_query($qry,$db='.main.') {
  $obj= (object)array();
  $res= pdo_qry($qry,0,0,0,$db);
  if ( $res ) {
    $obj= pdo_fetch_object($res);
  }
  return $obj;
}
# ---------------------------------------------------------------------------------------- sql_date1
// datum bez dne v týdnu
function sql_date1 ($datum,$user2sql=0,$del='.') {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datum ) {
      $datum= str_replace(' ','',$datum);
      list($d,$m,$s)= explode('.',$datum);
      $text= $s.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $text= '';
    if ( $datum && substr($datum,0,10)!='0000-00-00' ) {
      $s=substr($datum,0,4);
      $m=substr($datum,5,2);
      $d=substr($datum,8,2);
      //$h=substr($datum,11,2);
      //$n=substr($datum,14,2);

      $text.= date("j{$del}n{$del}Y",strtotime($datum));
//      $text.= "$d.$m.$s";
//                                                 display("$datum:$text");
    }
  }
  return $text;
}
# ----------------------------------------------------------------------------------------- sql_date
// datum
function sql_date ($datum,$user2sql=0) {
  if ( $user2sql ) {
    // převeď uživatelskou podobu na sql tvar
    $text= '';
    if ( $datum ) {
      $datum= trim($datum);
      list($d,$m,$s)= explode('.',$datum);
      $text= $s.'-'.str_pad($m,2,'0',STR_PAD_LEFT).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
    }
  }
  else {
    // převeď sql tvar na uživatelskou podobu (default)
    $dny= array('ne','po','út','st','čt','pá','so');
    $text= '';
    if ( $datum && substr($datum,0,10)!='0000-00-00' ) {
      $s= 0+substr($datum,0,4);
      $m= 0+substr($datum,5,2);
      $d= 0+substr($datum,8,2);
      //$h=substr($datum,11,2);
      //$n=substr($datum,14,2);
      $t= mktime(0,0,1,$m,$d,$s)+1;
//                                                 display("$datum:$m,$d,$s:$text:$t");
      $text= $dny[date('w',$t)];
      $text.= " $d.$m.$s";
    }
  }
  return $text;
}
# ------------------------------------------------------------------------------------- emailIsValid
# tells you if an email is in the correct form or not
# emailIsValid - http://www.kirupa.com/forum/showthread.php?t=323018
# args:  string - proposed email address
# ret:   bool
function emailIsValid($email,&$reason) {
   $isValid= true;
   $reasons= array();
   $atIndex= strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)    {
      $isValid= false;
      $reasons[]= "chybí @";
   }
   else    {
      $domain= substr($email, $atIndex+1);
      $local= substr($email, 0, $atIndex);
      $localLen= strlen($local);
      $domainLen= strlen($domain);
      if ($localLen < 1 || $localLen > 64)       {
         $isValid= false;
         $reasons[]= "dlouhé jméno";
      }
      else if ($domainLen < 1 || $domainLen > 255)       {
         $isValid= false;
         $reasons[]= "dlouhá doména";
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')       {
         $reasons[]= "tečka na kraji";
         $isValid= false;
      }
      else if (preg_match('/\\.\\./', $local))  {
         $reasons[]= "dvě tečky ve jménu";
         $isValid= false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))   {
         $reasons[]= "chybný znak v doméně";
         $isValid= false;
      }
      else if (preg_match('/\\.\\./', $domain))  {
         $reasons[]= "dvě tečky v doméně";
         $isValid= false;
      }
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))   {
         $reasons[]= "chybný znak ve jménu";
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))            {
            $isValid= false;
         }
      }
      if ( $domain!='proglas.cz' && $domain!='setkani.org' ) {
        if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))      {
           $reasons[]= "$domain je neznámá doména";
           $isValid= false;
        }
      }
   }
   $reason= count($reasons) ? implode(', ',$reasons) : '';
   return $isValid;
}
# ---------------------------------------------------------------------------------------- mail send
# ASK
# odešle mail
# k posílání přes GMail viz http://phpmailer.worxware.com/?pg=examplebgmail
function mail_send($reply_to,$address,$subject,$body) { trace(); 
  global $api_gmail_user, $api_gmail_pass, $chlapi_gmail_user, $kernel;
  $ret= (object)array('msg'=>'');
  $TEST= 0;
//  $TEST= 1;
  $ezer_path_serv= "$kernel/server";
  $phpmailer_path= "$ezer_path_serv/licensed/phpmailer";
  require_once("$phpmailer_path/class.phpmailer.php");
  require_once("$phpmailer_path/class.smtp.php");
  $nko= 0;
  // nastavení phpMail
  $mail= new PHPMailer(true);
  $mail->SetLanguage('cs',"$phpmailer_path/language/");
  $mail->IsSMTP();
  $mail->Mailer= 'smtp';
  $mail->Host= "smtp.gmail.com"; // sets GMAIL as the SMTP server
  $mail->Port= 465; // set the SMTP port for the GMAIL server
  $mail->SMTPAuth = 1; // enable SMTP authentication
  $mail->SMTPSecure= "ssl"; // sets the prefix to the server
  // Answer
  $mail->Username= $api_gmail_user;
  $mail->Password= $api_gmail_pass;
  // další nastavení
  $mail->CharSet= "utf-8";
  $mail->From= $chlapi_gmail_user;
  $mail->FromName= 'chlapi.cz';
  $mail->AddAddress($address);
  $mail->Subject= $subject;
  $mail->Body= $body;
  $mail->IsHTML(true);
  if ( $TEST ) {
    $ret->msg= "TESTOVÁNÍ - vlastní mail.send je vypnuto";
    goto end;
  }
  else {
    // odeslání mailu
    try {
      $ok= $mail->Send();
    }
    catch (Exception $e) { 
      $_SESSION['web']['phpmailer_errorinfo']= $mail->ErrorInfo;
      goto end;
    };
    $msg= $ok ? '' : $mail->ErrorInfo;
  }
  $ret->msg= $msg;
end:
  return $ret;
}
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="++++++++++++++++++++++++++ CMS functions">
// </editor-fold>
?>
