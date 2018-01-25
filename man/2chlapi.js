/* global Web, Ezer */

// ---------------------------------------------------------------------------------------------- //
// uživatelské funkce aplikace Ezer/MAN specifické pro chlapi.online                              //
//                                                                                                //
// CMS/Ezer                                             (c) 2016 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //
// =========================================================================================> COMMON
// -------------------------------------------------------------------------------------- $
function $() {
  // mootools relikt
  Ezer.fce.error("MooTools $-call");
  return 1;
}
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
function jump_fokus() {
  // najdi cíl podle priority
  var jump= jQuery('#fokus_part') || jQuery('#fokus_case') || jQuery('#fokus_page');
  if ( jump[0] )
//    jump.Ezer_scrollIntoView();
    jump[0].scrollIntoView(true);
  return 1;
}
// ----------------------------------------------------------------------------------------- context
// předá kontext _SESSION[web] a inicializuje aplikaci pod CMS
function context(web) {
  jQuery("#horni").addClass("admin");
  jQuery("#dolni").addClass("admin");
  jQuery("#StatusIcon_idle").hide();
  admin(0);
  Ezer.web= {};
  for (let [id,val] of Object.entries(web)) {
    Ezer.web[id]= val;
  };
  return 1;
}
// ----------------------------------------------------------------------------------------- noadmin
// zpřístupní ladící a administrátorské prvky pro a=1 znepřístupní pro a=0
function admin(a) {
  let state= a ? 'block' : 'none',
      margin= a ? '30px' : '0px';
  jQuery('div.admin').css({display:state});
  jQuery('div.cms_page').css({top:margin});
//  var logo=  jQuery('#logo'),
//      work=  jQuery('#work'),
//      dolni= jQuery('#dolni');
//  if ( !a ) work.css({height:'inherit'});
//  if ( logo ) logo.css({zIndex:a?99999:0});
//  if ( dolni ) dolni.css({display:a && Ezer.options.to_trace ?'block':'none'});
  return 1;
}
// ---------------------------------------------------------------------------------------------- go
// předá CMS info na kterou stránku webu přepnout
function go(e,href,mref,input,nojump) {
  if ( e ) e.stopPropagation();
  nojump= nojump||0;
  var url, http, page, u= href.split('page=');
  if ( u.length==2 ) {
    http= u[0];
    page= u[1].split('#');
    page= page[0];
  }
  else {
    http= u;
    page= 'home';
  }
  if ( input ) {
    // go je voláno přes <enter> v hledej
    var search= $('search').value;
    document.cookie= 'web_search='+search+';path=/';
    page= page + '!!'+ search;
  }
  history.pushState({},'',mref ? mref : http+'page='+page);
  Ezer.run.$.part.p._call(0,nojump?'cms_menu':'cms_go',page)
  return false;
}
//// ---------------------------------------------------------------------------------------------- go
//// přepne na page=foto&rok={}&id={}
//function go(e,ref,input) {
//  if ( e ) e.stopPropagation();
//  var foto= ref.match(/page=foto/) && !ref.match(/rok=/), newref= ref;
//  if ( foto ) {
//    var root= ref.match(/([^&]+)/);
//    newref= root[1];
//    if ( Web.rok )
//      newref+= '&rok='+Web.rok;
//    var pid= ref.match(/!(\d+)#/);
//    if ( pid )
//      newref+= '&id='+pid[1];
//  }
//  location.href= newref;
//  return false;
//}
// ----------------------------------------------------------------------------------------- refresh
// obnoví stránku
function refresh() {
  location.reload(true);
}
// ----------------------------------------------------------------------------------==> . ajax_test
// test funkce ze serveru
//function ajax_test(div_id) {
//  ask({cmd:'test',div_id:div_id,faze:1},ajax_test_,div_id);
//}
//function ajax_test_(y,div_id) {
//  jQuery(div_id).html(y.msg);
//}
// -----------------------------------------------------------------------------------==> . fe login
//function fe_login(page) {
//  var name= jQuery('#name').val(), pass= jQuery('#pass').val(), akey= jQuery('#akey').val(),
//      type= jQuery('#user_login').attr('data-login');
//  ask({cmd:'fe_login',name:name,pass:pass,akey:akey,page:page,type:type},_fe_login,'jo?');
//}
//function _fe_login(y) {
//  jQuery('#user_login').css('display','none');
//  if ( window['Ezer'] ) {
//    Ezer.web= y.web;
//  }
//  if ( !y.fe_user ) {
//    alert('chybné přihlášení');
//  }
//  if ( y.be_user ) {
//    window.location= 'index.php?page='+y.page;
//  }
//  else {
//    refresh();
//  }
//}
// ----------------------------------------------------------------------------------------- fe init
// inicializuje stránku
function fe_init() {
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
//    case 'grid': change_mode(1,1); break;
//    case 'rows': change_mode(1,0); break;
//    case 'fe_login':
//      jQuery('#user_login').css({display:'block'}).removeClass('key_in').attr('data-login','fe');
//      break;
//    case 'be_login':
//      jQuery('#user_login').css({display:'block'}).addClass('key_in').attr('data-login','be');
//      break;
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
  ask({cmd:'me_login',mail:mail,pin:pin,page:page,web:'chlapi.online'},me_login__);
}
function me_login__(y) {
  if ( y && y.txt ) {
    jQuery('#user_mail_txt').html(y.txt);
  }
  if ( y && y.msg ) {
    jQuery('#user_mail').html(y.msg);
  }
  if ( y && y.fe_user ) {
    refresh();
  }
}
//function me_ip(then) {
//  then.run(then.page,'-'); return;                              // vypnutí
//  // podle https://github.com/diafygi/webrtc-ips
//  window.RTCPeerConnection= window.RTCPeerConnection
//    || window.mozRTCPeerConnection
//    || window.webkitRTCPeerConnection;                          // compatibility for firefox and chrome
//  if ( !window.RTCPeerConnection )                              // for edge not yet, ... later we use
//    then.run(then.page,'?');                                    // https://github.com/webrtc/adapter
//  var pc= new RTCPeerConnection({iceServers:[]}),
//      noop= function(){};
//  pc.createDataChannel("");                                     //create a bogus data channel
//  pc.createOffer(pc.setLocalDescription.bind(pc), noop);        // create offer and set local description
//  pc.onicecandidate= function(ice){                             //listen for candidate events
//    if (!ice || !ice.candidate || !ice.candidate.candidate)
//      return;
//    var myIP= /([0-9]{1,3}(\.[0-9]{1,3}){3}|[a-f0-9]{1,4}(:[a-f0-9]{1,4}){7})/
//      .exec(ice.candidate.candidate)[1];
//    console.log('my IP: ', myIP);
//    then.run(then.page,myIP);
//    pc.onicecandidate= noop;
//  };
//}
// ===========================================================================================> AJAX
// --------------------------------------------------------------------------------------------- ask
// ask(x,then): dotaz na server se jménem funkce po dokončení
function ask(x,then,arg) {
  var xx= x;
  jQuery.ajax({url:Ezer.web.index, data:x, method: 'POST',
    success: function(y) {
      if ( typeof(y)==='string' )
        error(`Došlo k chybě 1 v komunikaci se serverem - '${xx.cmd}'`);
      else if ( y.error )
        error(`Došlo k chybě 2 v komunikaci se serverem - 'y.error'`);
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
//  if ( Ezer.version=='ezer3' ) {
//    jQuery('#skup0').css({display:'none'});
//    panel= Ezer.run.$.part.p;
//    label= panel.part.w.value.part.mapa;
//    jQuery(label.DOM_Block).css({display:'none'});
//  }
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
// ... totéž jako template string
//   $('skup2').css({display:'block'}).set('html',`
//     ${mark.title}
//     <div>
//       <input class="skup_x" type="text" id="skup_from" placeholder="tvůj email">
//       <textarea class="skup_x" id="skup_body" placeholder="dotaz na organizátory"></textarea>
//       <a class="skup_x jump" onclick="$('skup2').css({display:'none'});">
//         <span>Zpět</span></a>
//       <a class="skup_x jump" onclick="skup_sendmail('${mark.id}','${mark.title}');">
//         <span>Poslat mail</span></a>
//       <div id="skup_msg"></div>
//     </div>
//   `);
}
// ----------------------------------------------------------------------------------- skup sendmail
function skup_sendmail(psc,skupina) {
  var to= geo.emails[psc], reply, subj, body, msg, chlapi_online= "chlapi.online";
  reply= jQuery('#skup_from').val();
  subj= "SeSkup: Dotaz na organizátora skupiny";
  body= jQuery('#skup_body').val();
  if ( !reply ) return skup_sendmail_({ok:0,txt:"není vyplněna tvoje emailová adresa"});
  if ( !body ) return skup_sendmail_({ok:0,txt:"není vyplněn text dotazu na organizátory"});
  body= body.replace(/\n/,'<br>');
  body= "<b>Odesílatel:</b> "+reply+"<br><b>Zpráva:</b><br> "+body+"<hr>\
    POZOR: pokud budeš odpovídat, pohlídej prosím, aby odpověď šla na mail "+reply+" a ne na answer@setkani.org ...\
    <br><br>\
    <i>Tento mail byl zaslán ze stránky <a href='http://${chlapi_online}?skupiny'>chlapi.online?skupiny</a>\
       po kliknutí na ikonu tvojí chlapské skupiny '"+skupina+"'. Tvůj mail byl získán z tabulky\
       chlapských skupin. Pokud myslíš, že něco není v pořádku, obrať se prosím na správce\
       aplikace <a href='mailto:martin@smidek.eu'>Martina Šmídka</a>.</i>";
//   body= `<b>Odesílatel:</b> ${reply}<br><b>Zpráva:</b><br> ${body}<hr>
//     <POZOR: pokud budeš odpovídat, pohlídej prosím, aby odpověď šla na mail ${reply} a ne na answer@setkani.org ...
//     <br><br>
//     <i>Tento mail byl zaslán ze stránky <a href="http://${chlapi_online}?skupiny">chlapi.online?skupiny</a>
//        po kliknutí na ikonu tvojí chlapské skupiny "${skupina}". Tvůj mail byl získán z tabulky
//        chlapských skupin. Pokud myslíš, že něco není v pořádku, obrať se prosím na správce
//        aplikace <a href="mailto:martin@smidek.eu">Martina Šmídka</a>.</i>
//   `;
  ask({cmd:'sendmail',to:to,reply:reply,subj:subj,body:body},skup_sendmail_);
}
function skup_sendmail_(y) {
  if ( y.ok ) {
    jQuery('.skup_x').css({display:'none'});
  }
  if ( y.txt ) {
    jQuery('#skup_msg').html(y.txt);
  }
}
