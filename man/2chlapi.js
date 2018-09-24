/* global Web, Ezer */

// ---------------------------------------------------------------------------------------------- //
// uživatelské funkce aplikace Ezer/MAN specifické pro chlapi.online/chlapi.cz                    //
//                                                                                                //
// CMS/Ezer                                             (c) 2016 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //
 
// =========================================================================================> COMMON
jQuery.fn.extend({
  // ------------------------------------------------- + scrollIntoViewIfNeeded
  Ezer_scrollIntoView: function() {
    var target= this[0];
    let rect = target.getBoundingClientRect(),
        bound= this.parent()[0].getBoundingClientRect();
    if (rect.bottom > bound.bottom) {
        target.scrollIntoView(false);
    }
    else if (rect.top < bound.top) {
        target.scrollIntoView(true);
    }
  }
});
// -------------------------------------------------------------------------------------- jump fokus
// nastaví polohu stránky
// zamění <span style='neodkaz'> na alert
function jump_fokus() {
  // najdi cíl podle priority
  var jump= jQuery('#fokus_part') || jQuery('#fokus_case') || jQuery('#fokus_page');
  if ( jump[0] ) {
    jump[0].scrollIntoView(true);
  }
  return 1;
}
// ----------------------------------------------------------------------------------------- refresh
// obnoví stránku
function refresh() {
  location.reload(true);
}
// -----------------------------------------------------------------------------------==> . bar menu
function bar_menu(e,x) {
  if ( e ) { e.stopPropagation(); e.preventDefault(); }
  var items= jQuery('#bar_items'), body= jQuery(document);
  var off= function(e) {
    items.css({display:'none'});
    body.off("click contextmenu");
  };
  if ( x==='menu_on' ) {
    items.css({display:'block'});
    body.on({click:off,contextmenu:off});
  }
  else {
    switch (x) {
    case 'me_login':
      jQuery('#user_mail').css({display:'block'}).addClass('key_in').attr('data-login','me');
      break;
    }
    items.css({display:'none'});
  }
  return false;
}
// ------------------------------------------------------------------------------------- change info
// alternace informačního rohu
function change_info() {
  var info= jQuery('#info');
  if ( info ) {
    var on= info.css('display');
    info.css('display',on==='block'?'none':'block');
    if ( on==='none' ) {
      var scr= info.find('#info_screen');
      var xy= jQuery('body').getSize();
      scr.set('html',xy.x+'*'+xy.y+'<br>('+screen.width+'*'+screen.height+')');
    }
  }
}
// ==================================================================================> LOGIN, LOGOUT
// --------------------------------------------------------------------------------------- be logout
function be_logout(page) {
  ask({cmd:'be_logout',page:page},_be_logout,'jo?');
}
function _be_logout(y) {
  window.location= Ezer.web.index+'?page='+y.page;
}
// -----------------------------------------------------------------------------------==> . me login
function me_login(page) {
//  me_ip({run:me_login_,page:page});
//}
//function me_login_(page,myip) {
  var mail= jQuery('#mail').val(), pin= jQuery('#pin').val();
  ask({cmd:'me_login',mail:mail,pin:pin,page:page,web:'chlapi.cz'},me_login__);
}
function me_login__(y) {
  if ( y && y.txt ) {
    jQuery('#user_mail_txt').html(y.txt);
  }
  if ( y && y.msg ) {
//    jQuery('#user_mail').html(y.msg);
    alert(y.msg);
  }
  if ( y && y.redakce ) {
    jQuery('a.noedit').css({display:'inline-block'});
  }
  else if (y && y.state=='ok') {
    refresh();
  }
}
function me_noedit(no) {
  if ( no ) {
    ask({cmd:'me_noedit'},me_noedit__);
  }
  else {
    refresh();
  }
}
function me_noedit__(y) {
  refresh();
}
// ===========================================================================================> AJAX
// --------------------------------------------------------------------------------------------- ask
// ask(x,then): dotaz na server se jménem funkce po dokončení
function ask(x,then,arg) {
  var xx= x;
  jQuery.ajax({url:Ezer.web.index, data:x, method: 'POST',
    success: function(y) {
      if ( typeof(y)==='string' )
        error("Došlo k chybě 1 v komunikaci se serverem - '"+xx.cmd+"'");
      else if ( y.error )
        error("Došlo k chybě 2 v komunikaci se serverem - '"+y.error+"'");
      else if ( then ) {
        then.apply(undefined,[y,arg]);
      }
    },
    error: function(xhr) {
      error("Došlo k chybě 3 v komunikaci se serverem");
    }
  })
}
// ------------------------------------------------------------------------------------------- error
function error(msg) {
  alert(msg + " pokud napises na martin@smidek.eu pokusim se pomoci, Martin");
}
// ===========================================================================================> HOME
// ----------------------------------------------------------------------------------------- display
// zobrazí element
function display(el,on) {
  jQuery(el).css({display:on?'block':'none'})
}
// =======================================================================================> KONTAKTY
// --------------------------------------------------------------------------------------- kont show
function kont_show() {
  var kont= jQuery('#kont');
  if ( kont ) 
    kont.css({display:'block'});
}
// ===========================================================================================> SKUP
// -------------------------------------------------------------------------------------- ondomready
var panel, label, geo;
function ondomready() {
  skup_mapka();
}
var code= {
  "app": {
    "part": {
      "x": {
        "options": {"css":"mapa"},
        "type": "panel.main",
        "part": {
          "f": {
            "options": {"css":"mapa"},
            "type": "var", "_of": "form", "_init": "$.x._f"
          },
          "_f": {
            "type": "form",
            "part": {
              "l": {
                "options": {"css":"mapa"},
                "type": "label.map"
              }
            }
          }
        }
      }
    }
  }
};
// -------------------------------------------------------------------------------------- skup mapka
function skup_mapka() {
  label= jQuery('div.cms_mapa');
  if ( label[0] ) {
    label.css({display:'block'});
    label= label.data('ezer');
  }
  else {
    jQuery('#skup0').css({display:'block'});
    Ezer.App.load_root(code);
    panel= Ezer.run.$.part.x;
    label= panel.part.f.value.part.l;
  }
  label.part= {
    onmarkclick: function(mark) {
      skup_dialog(mark);
  }};
  label.init('ROADMAP');
  ask({cmd:'mapa',mapa:'skupiny'},skup_mapka_);
}
function skup_mapka_(y) {
  if ( y && y.mapa && y.mapa.mark ) {
    geo= {ezer:'PSČ',mark:y.mapa.mark,clmns:y.mapa.clmns,emails:y.mapa.emails};
    label.set(geo);
  }
}
// ---------------------------------------------------------------------------------- skup mapka_off
// používá se jen v CMS
function skup_mapka_off() {
  label= jQuery('div.cms_mapa');
  label.css({display:'none'});
}  
// ------------------------------------------------------------------------------------- skup dialog
function skup_dialog(mark) {
  var mark_json= JSON.stringify({id:mark.id,title:mark.title});
  jQuery('#skup0').css({display:'none'});
  jQuery('#skup2').css({display:'none'});
  jQuery('#skup1').css({display:'block'}).html(
    mark.title+"<div><a class='jump' onclick='skup_dialog2("+mark_json+");'> \
    <span>Chci se zeptat organizátorů</span></a></div>"
  );
}
// ------------------------------------------------------------------------------------- skup dialog
function skup_dialog2(mark) {
  jQuery('#skup1').css({display:'none'});
  jQuery('#skup2').css({display:'block'}).html(
      mark.title
    + "<div>"
    + "  <input class='skup_x' type='text' id='skup_from' placeholder='tvůj email'>"
    + "  <textarea class='skup_x' id='skup_body' placeholder='dotaz na organizátory'></textarea>"
    + "<a class='skup_x jump' onclick=\"skup_sendmail('"+mark.id+"','"+mark.title+"');\">Poslat mail</a>"
    + "<a class='skup_x jump' onclick=\"jQuery('#skup2').css({display:'none'});\">Zpět</a>"
    + "<div id='skup_msg'></div>"
    + "</div>"
  );
}
// ----------------------------------------------------------------------------------- skup sendmail
function skup_sendmail(psc,skupina) {
  var to= geo.emails[psc], reply, subj, body, msg, chlapi_online= "www.chlapi.cz";
  reply= jQuery('#skup_from').val();
  subj= "SeSkup: Dotaz na organizátora skupiny";
  body= jQuery('#skup_body').val();
  if ( !reply ) return skup_sendmail_({ok:0,txt:"není vyplněna tvoje emailová adresa"});
  if ( !body ) return skup_sendmail_({ok:0,txt:"není vyplněn text dotazu na organizátory"});
  body= body.replace(/\n/,'<br>');
  body= "<b>Odesílatel:</b> "+reply+"<br><b>Zpráva:</b><br> "+body+"<hr>\
    POZOR: pokud budeš odpovídat, pohlídej prosím, aby odpověď šla na mail "+reply
    +" a ne na www.chlapi.cz@gmail.com ...\
    <br><br>\
    <i>Tento mail byl zaslán ze stránky <a href='http://${chlapi_online}/skupiny'>chlapi.cz/skupiny</a>\
       po kliknutí na ikonu tvojí chlapské skupiny '"+skupina+"'. Tvůj mail byl získán z tabulky\
       chlapských skupin. Pokud myslíš, že něco není v pořádku, obrať se prosím na správce\
       aplikace <a href='mailto:martin@smidek.eu'>Martina Šmídka</a>.</i>";
  ask({cmd:'sendmail',to:to,reply:reply,subj:subj,body:body,skupina:skupina},skup_sendmail_);
}
function skup_sendmail_(y) {
  if ( y.ok ) {
    jQuery('.skup_x').css({display:'none'});
  }
  if ( y.txt ) {
    jQuery('#skup_msg').html(y.txt);
  }
}
