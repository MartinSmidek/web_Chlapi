Přihlašování na akci z webu {#procesy-prihlasky}
===========================

<!-- ------------------------------------------------------------------------------------------- -->

\startuml 
autonumber
hide footbox
skinparam handwritten true
actor účastník
participant "mailová\nschránka\núčastníka" as mailbox
database Answer
účastník -> Answer: kliknutí na [Přihláška] \nvyplnění mailové adresy + [Poslat]

Answer -> Answer: ověření způsobilosti\nvlastníka adresy, jen někdy \nlze dál pustit i neznámého
Answer [#black]-> mailbox: zaslání ověřovacího PINu nebo \ninformace o nezpůsobilosti \nnapř. musí poslat podepsanou přihlášku
...
účastník -> Answer: zapsání PINu + [Poslat] 

Answer -> Answer: ověření PINu
Answer o-> účastník: zobrazení zprávy, pokud PIN nesouhlasí 

== máme pokud uživatel vrátil správný PIN, máme ověřenu mailovou adresu ==

Answer -> Answer: výběr potřebných \ninformací pro tuto akci
Answer -> účastník: žádost o kontrolu resp. úpravu či doplnění vybraných informací
...
účastník -> Answer: případné úpravy + [Poslat]

group uživatel vložil či změnil údaje
create actor kontrola #green
Answer -> kontrola: kontrola vložených údajů
kontrola -> Answer: vše ok => zápis do akce
kontrola [#black]-> mailbox: potvrzení účasti nebo jiná reakce
end

group nebyla žádná změna
Answer -> Answer: zápis do akce
Answer -> účastník: poděkování a potvrzení přihlášky
end
\enduml 
<!-- ------------------------------------------------------------------------------------------- -->







