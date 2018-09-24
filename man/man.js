/* global Web, Ezer */

// ---------------------------------------------------------------------------------------------- //
// uživatelské funkce aplikace Ezer/MAN specifické pro chlapi.online/.cz                          //
//                                                                                                //
// CMS/Ezer                                             (c) 2016 Martin Šmídek <martin@smidek.eu> //
// ---------------------------------------------------------------------------------------------- //

// ============================================================================================> CMS
// ---------------------------------------------------------------------------------- opravit clanek
function opravit(typ,id,idk) {
  idk= idk || 0;
  Ezer.run.$.part.p._call(0,'opravit',typ,id,idk);
  return 1;
}
// ----------------------------------------------------------------------------------- zcizit clanek
function zcizit(typ,id,mid) {
  Ezer.run.$.part.p._call(0,'zcizit',typ,id,mid);
  return 1;
}
// ----------------------------------------------------------------------------------- změnit clanek
// změní typ elementu z typ1 na typ2
function zmenit(mid,typ1,id,typ2) {
  Ezer.run.$.part.p._call(0,'zmenit',mid,typ1,id,typ2);
  return 1;
}
// ----------------------------------------------------------------------------------- přidat clanek
function pridat(typ,mid,first) {
  Ezer.run.$.part.p._call(0,'pridat',typ,mid,first);
  return 1;
}
// --------------------------------------------------------------------------------- posunout clanek
function posunout(_typ1,_mid,_id,_dolu) {
  Ezer.run.$.part.p._call(0,'posunout',_typ1,_mid,_id,_dolu);
  return 1;
}
// =========================================================================================> COMMON
// ----------------------------------------------------------------------------------------------- $
function $() {
  // mootools relikt
  Ezer.fce.error("MooTools $-call");
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
// ----------------------------------------------------------------------------------------- context
// předá kontext _SESSION[web] a inicializuje aplikaci pod CMS
function context(web) {
  Ezer.web= {};
  for (let [id,val] of Object.entries(web)) {
    Ezer.web[id]= val;
  };
  return 1;
}
