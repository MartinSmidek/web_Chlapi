<?php # (c) 2010 Martin Smidek <martin@smidek.eu>

  // nastavení verze jádra
  $EZER= (object)array();
  $EZER->version= "ezer2.2";

  $ezer_local= preg_match('/^\w+\.ezer/',$_SERVER["SERVER_NAME"]); // identifikace ladícího serveru

  require_once("{$EZER->version}/server/ae_slib.php");

  // OBECNÉ PARAMETRY

  // parametry s první hodnotou pro server a druhou (případně) pro local
  //   databáze => (,server,username,userpass,kódování,[jméno databáze])
  // databáze 'ezer_system' obsahuje platnou tabulku _user
  // (fyzické jméno databáze může být změněno pátým členem v tabulce $dbs)
  $db= array('ezertask','ezer_task');
  $dbs= array(
    array(
      'ezertask'      => array(0,'localhost','gandi','radost','utf8'),
      'ezer_system'   => array(0,'localhost','gandi','radost','utf8','ezertask')),
    array(
      'ezer_task'     => array(0,'localhost','gandi','','utf8'),
      'ezer_system'   => array(0,'localhost','gandi','','utf8','ezer_task')
    )
  );
  // kořeny cest
  $path_root=  array("/home/users/gandi/smidek.eu/web/ezer","C:/Apache/htdocs/ezer/www-ys2");
  $path_pspad= array(null,"C:/Program Files (x86)/PSPad editor/Syntax");
  // ostatní parametry
  $tracking= '_track';
  $tracked= ',_user,';
  root_inc($db,$dbs,$tracking,$tracked,$path_root,$path_pspad);

  // PARAMETRY SPECIFICKÉ PRO APLIKACI

  // specifické cesty
  // moduly interpreta zahrnuté do aplikace - budou zpracovány i reference.i_doc pro tabulky kompilátoru
  $ezer_comp_ezer= "app,ezer,ezer_report,ezer_fdom1,ezer_fdom2";
  $ezer_comp_root= "fis/fis2_user";
  // moduly v Ezerscriptu mimo složku aplikace
  $ezer_ezer= array(
  );
  // standardní moduly v PHP obsažené v $ezer_path_root/ezer2 - vynechané v dokumentaci
  $ezer_php_libr= array(
    'server/session.php',
    'server/ae_slib.php',
    'server/reference.php',
    'ezer2_fce.php',
    'server/sys_doc.php',
    'server/ezer2.php'
  );
  // uživatelské i knihovní moduly v PHP obsažené v $ezer_path_root
  $ezer_php= array(
//     "{$EZER->version}/ezer2_fce.php",
    'rr/rr_fce.php'
  );

  // parametrizace $EZER
  $EZER->options->web=    'www.smidek.eu';
  $EZER->options->author= 'Martin Šmídek';
  $EZER->options->mail=   'martin@smidek.eu';
  $EZER->options->phone=  '603150565';
  $EZER->options->skype=  'martin_smidek';
  $EZER->activity->skip=  'GAN';             // viz system.php::sys_table
  // PDF knihovny
  // vložení modulů
  foreach($ezer_php as $php) {
    require_once("$ezer_path_root/$php");
  }

?>
