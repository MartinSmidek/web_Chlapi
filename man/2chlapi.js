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
  var jump= jQuery('#fokus_part');
  if ( !jump.length )
    jump= jQuery('#fokus_case');
  if ( !jump.length )
    jump= jQuery('#fokus_page');
  if ( jump.length ) {
    jump[0].scrollIntoView(true);
  }
  return 1;
}
// ----------------------------------------------------------------------------------------- refresh
// obnoví stránku
function refresh() {
  if ( Ezer.run!==undefined ) {
    Ezer.run.$.part.p._call(0,'refresh');
  }
  else {
    location.reload(true);
  }
}
// -----------------------------------------------------------------------------------==> . bar menu
function change_css(s) {
  var link= jQuery("link#css_chlapi");
  if (link[0])
    link.attr({href:s.path}); 
}
function change_js(menu,cmd) {
  // kód pro nové menu
  if (menu=='new') {
    if (cmd && cmd=='close') {
      jQuery('nav.mobile-menu').removeClass('active');
    }
    else {
      jQuery('div.mobile-menu-open').click(function() {
        jQuery('nav.mobile-menu').addClass('active');
        return false;
      })
      jQuery('div.mobile-menu-close').click(function() {
        jQuery('nav.mobile-menu').removeClass('active');
        return false;
      })
      jQuery('li.has-children').on('click', function() {
         jQuery('ul.children[style*="block"]').slideUp('slow', 'swing');
         jQuery(this).children('ul').slideToggle('slow', 'swing');
         jQuery('.icon-arrow').toggleClass('open');
      });
    }
  }
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
    case 'menu-new':
      ask({cmd:'menu',menu:'new'},_bar_menu,'menu');
      break;
    case 'menu-old':
      ask({cmd:'menu',menu:'old'},_bar_menu,'menu');
      break;
    case 'lang-cs':
      ask({cmd:'lang',lang:'cs'},_bar_menu,'lang');
      break;
    case 'lang-en':
      ask({cmd:'lang',lang:'en'},_bar_menu,'lang');
      break;
    case 'wallpaper':
      let back= jQuery('body').css('background-image');
      ask({cmd:'wallpaper',wall:back},_bar_menu,'wallpaper');
      break;
    case 'me_login':
//      alert('Je mi líto, ale nyní se nelze přihlásit. Našemu serveru je špatně.\nŽivot je těžký ... Martin');
      jQuery('#user_mail').css({display:'block'}).addClass('key_in').attr('data-login','me');
      break;
    }
    items.css({display:'none'});
  }
  return false;
}
function _bar_menu(y,cmd) {
  switch (cmd) {
    case 'menu': 
      if ( Ezer.run!==undefined ) {
        change_css(y);
        refresh();
      }
      else 
        window.location.href= y.url;
      break; 
    case 'lang': // voláno po předchozím volání php funkce set_lang('en'|'cs')
      if ( Ezer.run!==undefined ) {
        Ezer.run.$.part.p._call(0,'set_lang',y.wid);
        refresh();
      }
      else {
        window.location.href= y.url;
      }
      break;
    case 'lang-cs': // voláno po předchozím volání php funkce set_lang('en'|'cs')
      if ( Ezer.run!==undefined ) Ezer.run.$.part.p._call(0,'set_lang',2);
      refresh();
      break;
    case 'lang-en':
      if ( Ezer.run!==undefined ) Ezer.run.$.part.p._call(0,'set_lang',1);
      refresh();
      break;
    case 'wallpaper':
      // jQuery('body').css('background-image',y.wall); ztratí !important
      jQuery('body').attr('style','background-image:'+y.wall+'!important');
      break;
  }
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
// ========================================================================================> COOKIES
// -------------------------------------------------------------------------------------- set cookie
function set_cookie(name,value,hours) {
    var expires = "";
    if (hours) {
        var date = new Date();
        date.setTime(date.getTime() + (hours*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}
// -------------------------------------------------------------------------------------- get cookie
function get_cookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
// -------------------------------------------------------------------------------------- del cookie
function del_cookie(name) {   
    document.cookie = name+'=; Max-Age=-99999999;';  
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
  var mail= jQuery('#mail').val(), pin= jQuery('#pin').val();
  ask({cmd:'me_login',mail:mail,pin:pin,page:page,web:'chlapi.cz'},me_login__);
}
function me_login__(y) {
  var txt= jQuery('#user_mail_txt');
  if ( y && y.txt ) {
    txt.html(y.txt);
    jQuery('#pin').focus();
    jQuery('#prihlasit2').css({display:'inline-block'});
  }
  if ( y && y.msg ) {
    alert(y.msg);
  }
  if (y && y.state=='ok') {
    set_cookie('email',jQuery('#mail').val(),30*24);
    set_cookie('pin',jQuery('#pin').val(),23);
    if ( !y.txt ) 
      txt.html("<br>Jsi přihlášen, tento PIN platí 24 hodin<br><br>");
    if ( y && y.redakce ) {
      jQuery('#prihlasit1').css({display:'none'});
      jQuery('#prihlasit2').css({display:'none'});
      jQuery('#prihlasit3').css({display:'none'});
      jQuery('a.noedit').css({display:'inline-block'});
    }
    else {
      jQuery('#user_mail').fadeOut(3000,function(){refresh();});
    }
  }
}
function me_noedit(no) {
  me_noedit_(no);
}
function me_noedit_(no) {
  ask({cmd:'me_noedit',noedit:no},me_noedit__);
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
// ============================================================================================> MSG
var continuation= null;
// ------------------------------------------------------------------------------------------ msg on
// zobrazí zprávu - alert
function msg_on(text,title,_continuation) {
  if ( !title ) title= 'Upozornění';
  continuation= _continuation ? _continuation : null;
  jQuery('#msg div.box_title').html(title);
  jQuery('#msg div.box_text').html(text);
  jQuery('#msg').css({display:'block'});
}
// ----------------------------------------------------------------------------------------- box off
// zhasne všechny typy boxů
function box_off() {
  jQuery('div.box').css({display:'none'});
  if ( continuation ) {
    continuation();
    continuation= null;
  }
}
// ==========================================================================================> TABLE
var table_x= {};
// -------------------------------------------------------------------------------------- table test
// vyhodnotí odpověď na testovací otázky a případně přihlásí jako fe_host
function table_test(e) {
  if ( e ) { e.stopPropagation(); e.preventDefault(); }
  var prompt= jQuery('#prompt');
  prompt.find('input').val('');
  prompt.css({display:'block'});
}
function _table_test(test) {
  ask({cmd:'table_tst',test:test},_table_test_);
}
function _table_test_(y) {
  if ( y.ok )
    refresh();
  else {
    jQuery('#prompt').css({display:'none'});
    msg_on("Richard "+(y.test?y.test:'---')+"? <br><br>to nebylo dobře :-(");
  }
}
// -------------------------------------------------------------------------------------- table add1
// zobrazí jméno přihlášeného účastníka jako vzor
function table_add1(e,skup,cid,idx) {
  if ( e ) { e.stopPropagation(); e.preventDefault(); }
  jQuery('#skupiny input').css({display:'none'});
  let input= jQuery('#table-'+idx);
  input.val(Ezer.web.username ? Ezer.web.username : '');
  input.css({display:'block'});
}
// --------------------------------------------------------------------------------------- table add
// přidá účastníka do skupiny
function table_add(e,skup,idc,idx) {
  if ( e ) { e.stopPropagation(); e.preventDefault(); }
  let input= jQuery('#table-'+idx);
  table_x= {cmd:'table_add',skupina:skup,jmeno:input.val(),idc:idc};
  ask(table_x,_table_add);
}
function _table_add(y) {
  if ( y.msg ) {
    msg_on(y.msg,'',refresh);
  }
}
// ===========================================================================================> SKUP
// -------------------------------------------------------------------------------------- ondomready
var panel, label, geo;
function onmaploaded() { // ondomready() {
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
  if ( typeof(Ezer)=='undefined' || !Ezer.App ) return;
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
    <i>Tento mail byl zaslán ze stránky <a href='https://${chlapi_online}/skupiny'>chlapi.cz/skupiny</a>\
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
