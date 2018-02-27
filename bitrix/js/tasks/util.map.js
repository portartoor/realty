{"version":3,"file":"util.min.js","sources":["util.js"],"names":["BX","namespace","mergeEx","Tasks","Util","formatTimeAmount","time","format","parseInt","isNaN","sign","Math","abs","hours","floor","minutes","seconds","nPad","num","substring","length","result","delay","action","actionCancel","ctx","DoNothing","this","timer","f","args","arguments","setTimeout","apply","cancel","clearTimeout","showByClass","node","hasClass","removeClass","hideByClass","addClass","fadeToggleByClass","duration","onComplete","animateShowHide","toShow","opacity","toHide","complete","fadeSlideToggleByClass","height","getInvisibleSize","fadeSlideHToggleByClass","width","params","type","isElementNode","p","Promise","reject","invisible","way","resolve","animate","start","finish","style","cssText","isFunction","call","step","state","rt","Runtime","animations","anim","k","easing","transition","transitions","linear","splice","fulfill","push","stop","pos","isEnter","e","getKeyFromEvent","isEsc","window","event","keyCode","which","filterFocusBlur","cbFocus","cbBlur","timeout","focus","eventArgs","bind","bindInstantChange","cb","value","debounce","toString","disable","setAttribute","enable","removeAttribute","getMessagePlural","n","msgId","pluralForm","langId","message","isArray","fireGlobalTaskEvent","taskData","options","taskDataUgly","task","taskUgly","onCustomEvent","top","hintManager","bindHelp","scope","target","className","bindDelegate","passCtx","onHelpShow","onHelpHide","showDisposable","body","id","parameters","isPlainObject","closeLabel","autoHide","show","callback","util","hashCode","random","hintPopup","popup","wasDisposed","content","isNotEmptyString","title","create","attrs","text","htmlspecialchars","replace","html","margin","children","props","href","events","click","hide","PopupWindowManager","closeByEsc","closeIcon","angle","offsetLeft","offsetTop","onPopupClose","delegate","onViewModeHintClose","close","userOptions","save","disableSeveral","pack","enabled","data","innerHTML","PopupWindow","lightShadow","darkMode","bindOptions","position","zIndex","destroy","helpWindow","setAngle","offset","MouseTracker","coords","x","y","document","pageX","clientX","documentElement","scrollLeft","clientLeft","pageY","clientY","scrollTop","clientTop","getCoordinates","clone","getInstance","mouseTracker"],"mappings":"AAIAA,GAAGC,UAAU,aAEbD,IAAGE,QAAQF,GAAGG,MAAMC,MAEnBC,iBAAmB,SAASC,EAAMC,GAEjCD,EAAOE,SAASF,EAChB,IAAGG,MAAMH,GACT,CACC,MAAO,GAGR,GAAII,GAAOJ,EAAO,EAAI,IAAM,EAC5BA,GAAOK,KAAKC,IAAIN,EAEhB,IAAIO,GAAQ,GAAKF,KAAKG,MAAMR,EAAO,KACnC,IAAIS,GAAU,GAAMJ,KAAKG,MAAMR,EAAO,IAAM,EAC5C,IAAIU,GAAU,GAAKV,EAAO,EAE1B,IAAIW,GAAO,SAASC,GACnB,MAAO,KAAKC,UAAU,EAAG,EAAID,EAAIE,QAAQF,EAG1C,IAAIG,GAASJ,EAAKJ,GAAO,IAAII,EAAKF,EAElC,KAAIR,GAAUA,GAAU,WACxB,CACCc,GAAU,IAAIJ,EAAKD,GAGpB,MAAON,GAAKW,GAIbC,MAAO,SAASC,EAAQC,EAAcF,EAAOG,GAE5CF,EAASA,GAAUvB,GAAG0B,SACtBF,GAAeA,GAAgBxB,GAAG0B,SAClCJ,GAAQA,GAAS,GACjBG,GAAMA,GAAOE,IAEb,IAAIC,GAAQ,IAEZ,IAAIC,GAAI,WAEP,GAAIC,GAAOC,SACXH,GAAQI,WAAW,WAClBT,EAAOU,MAAMR,EAAKK,IAChBR,GAEJO,GAAEK,OAAS,WAEVV,EAAaS,MAAMR,KACnBU,cAAaP,GAGd,OAAOC,IAGRO,YAAa,SAASC,GAErB,GAAGrC,GAAGsC,SAASD,EAAM,aACrB,CACCrC,GAAGuC,YAAYF,EAAM,eAIvBG,YAAa,SAASH,GAErB,IAAIrC,GAAGsC,SAASD,EAAM,aACtB,CACCrC,GAAGyC,SAASJ,EAAM,eAQpBK,kBAAmB,SAASL,EAAMM,EAAUC,GAE3C,MAAO5C,IAAGG,MAAMC,KAAKyC,iBACpBR,KAAMA,EACNM,SAAUA,EACVG,QAASC,QAAS,KAClBC,QAASD,QAAS,GAClBE,SAAUL,KAQZM,uBAAwB,SAASb,EAAMM,EAAUC,GAEhD,MAAO5C,IAAGG,MAAMC,KAAKyC,iBACpBR,KAAMA,EACNM,SAAUA,EACVG,QAASC,QAAS,IAAKI,OAAQnD,GAAGG,MAAMC,KAAKgD,iBAAiBf,GAAMc,QACpEH,QAASD,QAAS,EAAGI,OAAQ,GAC7BF,SAAUL,KAQZS,wBAAyB,SAAShB,EAAMM,EAAUC,GAEjD,MAAO5C,IAAGG,MAAMC,KAAKyC,iBACpBR,KAAMA,EACNM,SAAUA,EACVG,QAASC,QAAS,IAAKO,MAAOtD,GAAGG,MAAMC,KAAKgD,iBAAiBf,GAAMiB,OACnEN,QAASD,QAAS,EAAGO,MAAO,GAC5BL,SAAUL,KAIZC,gBAAiB,SAASU,GAEzBA,EAASA,KACT,IAAIlB,GAAOkB,EAAOlB,MAAQ,IAE1B,KAAIrC,GAAGwD,KAAKC,cAAcpB,GAC1B,CACC,GAAIqB,GAAI,GAAI1D,IAAG2D,OACfD,GAAEE,QACF,OAAOF,GAGR,GAAIG,GAAY7D,GAAGsC,SAASD,EAAM,YAClC,IAAIyB,SAAcP,GAAOO,KAAO,aAAeP,EAAOO,MAAQ,KAAQD,IAAcN,EAAOO,GAE3F,IAAGD,GAAaC,EAChB,CACC,GAAIJ,GAAI,GAAI1D,IAAG2D,OACfD,GAAEK,SACF,OAAOL,GAGR,GAAIZ,GAASS,EAAOT,UACpB,IAAIE,GAASO,EAAOP,UAEpB,OAAOhD,IAAGG,MAAMC,KAAK4D,SACpB3B,KAAMA,EACNM,SAAUY,EAAOZ,SACjBsB,OAAQH,EAAMhB,EAASE,EACvBkB,OAAQJ,EAAMhB,EAASE,EACvBC,SAAU,WACTjD,IAAI8D,EAAM,WAAa,eAAezB,EAAM,YAC5CA,GAAK8B,MAAMC,QAAU,EAErB,IAAGpE,GAAGwD,KAAKa,WAAWd,EAAON,UAC7B,CACCM,EAAON,SAASqB,KAAK3C,QAGvB4C,KAAM,SAASC,GAEd,SAAUA,GAAMzB,SAAW,YAC3B,CACCV,EAAK8B,MAAMpB,QAAUyB,EAAMzB,QAAQ,IAEpC,SAAUyB,GAAMrB,QAAU,YAC1B,CACCd,EAAK8B,MAAMhB,OAASqB,EAAMrB,OAAO,KAElC,SAAUqB,GAAMlB,OAAS,YACzB,CACCjB,EAAK8B,MAAMb,MAAQkB,EAAMlB,MAAM,UASnCU,QAAS,SAAST,GAEjBA,EAASA,KACT,IAAIlB,GAAOkB,EAAOlB,MAAQ,IAE1B,IAAIqB,GAAI,GAAI1D,IAAG2D,OAEf,KAAI3D,GAAGwD,KAAKC,cAAcpB,GAC1B,CACCqB,EAAEE,QACF,OAAOF,GAGR,GAAIf,GAAWY,EAAOZ,UAAY,GAElC,IAAI8B,GAAKzE,GAAGG,MAAMuE,OAElB,UAAUD,GAAGE,YAAc,YAC3B,CACCF,EAAGE,cAIJ,GAAIC,GAAO,IACX,KAAI,GAAIC,KAAKJ,GAAGE,WAChB,CACC,GAAGF,EAAGE,WAAWE,GAAGxC,MAAQA,EAC5B,CACCuC,EAAOH,EAAGE,WAAWE,EACrB,QAIF,GAAGD,IAAS,KACZ,CACC,GAAIE,GAAS,GAAI9E,IAAG8E,QACnBnC,SAAWA,EACXsB,MAAOV,EAAOU,MACdC,OAAQX,EAAOW,OACfa,WAAY/E,GAAG8E,OAAOE,YAAYC,OAClCV,KAAOhB,EAAOgB,KACdtB,SAAU,WAGT,IAAI,GAAI4B,KAAKJ,GAAGE,WAChB,CACC,GAAGF,EAAGE,WAAWE,GAAGxC,MAAQA,EAC5B,CACCoC,EAAGE,WAAWE,GAAGC,OAAS,IAC1BL,GAAGE,WAAWE,GAAGxC,KAAO,IAExBoC,GAAGE,WAAWO,OAAOL,EAAG,EAExB,QAIFxC,EAAO,IACPuC,GAAO,IAEPrB,GAAON,SAASqB,KAAK3C,KAErB,IAAG+B,EACH,CACCA,EAAEyB,aAILP,IAAQvC,KAAMA,EAAMyC,OAAQA,EAE5BL,GAAGE,WAAWS,KAAKR,OAGpB,CACCA,EAAKE,OAAOO,MAEZ,IAAG3B,EACH,CACCA,EAAEE,UAIJgB,EAAKE,OAAOd,SAEZ,OAAON,IAGRN,iBAAkB,SAASf,GAE1B,GAAIwB,GAAY7D,GAAGsC,SAASD,EAAM,YAElC,IAAGwB,EACH,CACC7D,GAAGuC,YAAYF,EAAM,aAEtB,GAAIqB,GAAI1D,GAAGsF,IAAIjD,EACf,IAAGwB,EACH,CACC7D,GAAGyC,SAASJ,EAAM,aAGnB,MAAOqB,IAGR6B,QAAS,SAASC,GAEjB,MAAO7D,MAAK8D,gBAAgBD,IAAM,IAGnCE,MAAO,SAASF,GAEf,MAAO7D,MAAK8D,gBAAgBD,IAAM,IAGnCC,gBAAiB,SAASD,GAEzBA,EAAIA,GAAKG,OAAOC,KAChB,OAAOJ,GAAEK,SAAWL,EAAEM,OAGvBC,gBAAiB,SAAS1D,EAAM2D,EAASC,EAAQC,GAEhD,IAAIlG,GAAGwD,KAAKC,cAAcpB,GAC1B,CACC,MAAO,OAGR,GAAIT,GAAQ,KAEZoE,GAAUA,GAAWhG,GAAG0B,SACxBuE,GAASA,GAAUjG,GAAG0B,SACtBwE,GAAUA,GAAW,EAErB,IAAIrE,GAAI,SAASsE,EAAOC,GAEvB,GAAGD,EACH,CACC,GAAGvE,GAAS,MACZ,CACCO,aAAaP,EACbA,GAAQ,UAGT,CACCoE,EAAQ/D,MAAMN,KAAMyE,QAItB,CACCxE,EAAQI,WAAW,WAClBJ,EAAQ,KACRqE,GAAOhE,MAAMN,KAAMyE,IACjBF,IAILlG,IAAGqG,KAAKhE,EAAM,OAAQ,WAAWR,EAAEI,MAAMN,MAAO,MAAOI,aACvD/B,IAAGqG,KAAKhE,EAAM,QAAS,WAAWR,EAAEI,MAAMN,MAAO,KAAMI,aAEvD,OAAO,OAGRuE,kBAAmB,SAASjE,EAAMkE,EAAI9E,GAErC,IAAIzB,GAAGwD,KAAKC,cAAcpB,GAC1B,CACC,MAAOrC,IAAG0B,UAGXD,EAAMA,GAAOY,CAEb,IAAImE,GAAQnE,EAAKmE,KAEjB,IAAI3E,GAAI7B,GAAGyG,SAAS,SAASjB,GAE5B,GAAGnD,EAAKmE,MAAME,YAAcF,EAAME,WAClC,CACCH,EAAGtE,MAAMR,EAAKM,UAEdyE,GAAQnE,EAAKmE,QAEZ,EAAG/E,EAENzB,IAAGqG,KAAKhE,EAAM,QAASR,EACvB7B,IAAGqG,KAAKhE,EAAM,QAASR,EACvB7B,IAAGqG,KAAKhE,EAAM,SAAUR,IAGzB8E,QAAS,SAAStE,GAEjB,GAAGrC,GAAGwD,KAAKC,cAAcpB,GACzB,CACCA,EAAKuE,aAAa,WAAY,cAIhCC,OAAQ,SAASxE,GAEhB,GAAGrC,GAAGwD,KAAKC,cAAcpB,GACzB,CACCA,EAAKyE,gBAAgB,cAIvBC,iBAAkB,SAASC,EAAGC,GAE7B,GAAIC,GAAYC,CAEhBA,GAASnH,GAAGoH,QAAQ,cACpBJ,GAAIxG,SAASwG,EAEb,IAAIA,EAAI,EACR,CACCA,GAAM,EAAKA,EAGZ,GAAIG,EACJ,CACC,OAAQA,GAEP,IAAK,KACL,IAAK,KACJD,EAAeF,IAAM,EAAK,EAAI,CAC9B,MAED,KAAK,KACL,IAAK,KACJE,EAAiBF,EAAE,KAAO,GAAOA,EAAE,MAAQ,GAAO,EAAOA,EAAE,IAAM,GAAOA,EAAE,IAAM,IAAQA,EAAE,IAAM,IAAQA,EAAE,KAAO,IAAQ,EAAI,CAC7H,MAED,SACCE,EAAa,CACb,YAIH,CACCA,EAAa,EAGd,GAAGlH,GAAGwD,KAAK6D,QAAQJ,GACnB,CACC,MAAOA,GAAMC,GAGd,MAAQlH,IAAGoH,QAAQH,EAAQ,WAAaC,IAGzCI,oBAAqB,SAAS9D,EAAM+D,EAAUC,EAASC,GAEtD,IAAIjE,EACJ,CACC,MAAO,OAGRA,EAAOA,EAAKkD,UACZc,GAAUA,KAEV,IAAGhE,GAAQ,OAASA,GAAQ,UAAYA,GAAQ,UAAYA,GAAQ,OACpE,CACC,MAAO,OAGR,GAAI4C,IAAa5C,GAAOkE,KAAMH,EAAUI,SAAUF,EAAcD,QAASA,GAEzExH,IAAG4H,cAAcjC,OAAQ,iBAAkBS,EAC3C,IAAGT,QAAUA,OAAOkC,IACpB,CACClC,OAAOkC,IAAI7H,GAAG4H,cAAcjC,OAAOkC,IAAK,iBAAkBzB,GAG3D,MAAO,QAITpG,IAAGG,MAAMC,KAAK0H,aAEbC,SAAU,SAASC,GAElB,GAAIC,IAAUC,UAAW,kBAEzBlI,IAAGmI,aAAaH,EAAO,YAAaC,EAAQjI,GAAGG,MAAMiI,QAAQzG,KAAK0G,WAAY1G,MAC9E3B,IAAGmI,aAAaH,EAAO,WAAYC,EAAQjI,GAAGG,MAAMiI,QAAQzG,KAAK2G,WAAY3G,QAG9E4G,eAAgB,SAASlG,EAAMmG,EAAMC,EAAIC,GAExC,IAAI1I,GAAGwD,KAAKmF,cAAcD,GAC1B,CACCA,KAED,KAAK,cAAgBA,IACrB,CACCA,EAAWE,WAAa5I,GAAGoH,QAAQ,gCAEpC,KAAK,YAAcsB,IACnB,CACCA,EAAWG,SAAW,KAGvBlH,KAAKmH,KAAKzG,EAAMmG,EAAM,MAAOC,EAAIC,IAWlCI,KAAM,SAASzG,EAAMmG,EAAMO,EAAUN,EAAIC,GAExCD,EAAKA,GAAMzI,GAAGgJ,KAAKC,UAAUtI,KAAKuI,SAAS,KAAKxC,YAAYA,UAC5DgC,GAAaA,KAEb,IAAIjE,GAAKzE,GAAGG,MAAMuE,OAElBD,GAAG0E,UAAY1E,EAAG0E,aAElB,UAAU1E,GAAG0E,UAAUV,IAAO,YAC9B,CACChE,EAAG0E,UAAUV,IACZW,MAAO,KACPzC,QAAS,OAIX,GAAGhF,KAAK0H,YAAYZ,GACpB,CACC,OAGD,GAAGhE,EAAG0E,UAAUV,GAAIW,OAAS,KAC7B,CACC,GAAIE,KACJ,IAAGtJ,GAAGwD,KAAK+F,iBAAiBb,EAAWc,OACvC,CACCF,EAAQlE,KAAKpF,GAAGyJ,OAAO,QACrBC,OAAQxB,UAAW,yBAA0ByB,KAAMjB,EAAWc,SAGjE,IAAIxJ,GAAGwD,KAAK+F,iBAAiBf,GAC7B,CACCA,EAAO,GAERA,EAAOxI,GAAGgJ,KAAKY,iBAAiBpB,GAAMqB,QAAQ,QAAS,SAEvDP,GAAQlE,KAAKpF,GAAGyJ,OAAO,KAAMK,KAAMtB,EAAMrE,OAAQ4F,OAAQ,wBAEzD,IAAG/J,GAAGwD,KAAK+F,iBAAiBb,EAAWE,YACvC,CACCU,EAAQlE,KAAKpF,GAAGyJ,OAAO,KAErBtF,OAAQ4F,OAAQ,sBAChBC,UACChK,GAAGyJ,OAAO,KAERQ,OAAQC,KAAM,sBACdP,KAAMjB,EAAWE,WACjBuB,QAASC,MAAS,WACjBpK,GAAGG,MAAMC,KAAK0H,YAAYnB,QAAQ8B,EAClCzI,IAAGG,MAAMC,KAAK0H,YAAYuC,KAAK5B,WAStChE,EAAG0E,UAAUV,GAAIW,MAAQpJ,GAAGsK,mBAAmBb,OAAOhB,EACrDpG,GAECkI,WAAY,MACZC,UAAW,KACXC,SACA5B,SAAUH,EAAWG,WAAa,KAClC6B,WAAY,GACZC,UAAY,EACZR,QAASS,aAAc5K,GAAG6K,SAASlJ,KAAKmJ,oBAAqBnJ,OAC7D2H,QAAStJ,GAAGyJ,OAAO,OAEjBC,OAAQxB,UAAW,4BACnB8B,SAAUV,MAOf7E,EAAG0E,UAAUV,GAAIW,MAAMN,QAGxBO,YAAa,SAASZ,GAErBzI,GAAGG,MAAMuE,QAAQyE,UAAYnJ,GAAGG,MAAMuE,QAAQyE,aAC9CnJ,IAAGG,MAAMuE,QAAQyE,UAAUV,GAAMzI,GAAGG,MAAMuE,QAAQyE,UAAUV,MAE5D,OAAOzI,IAAGG,MAAMuE,QAAQyE,UAAUV,GAAI9B,SAGvC0D,KAAM,SAAS5B,GAEd,IAECzI,GAAGG,MAAMuE,QAAQyE,UAAUV,GAAIW,MAAM2B,QAEtC,MAAMvF,MAKPmB,QAAU,SAAS8B,GAElBzI,GAAGG,MAAMuE,QAAQyE,UAAYnJ,GAAGG,MAAMuE,QAAQyE,aAC9CnJ,IAAGG,MAAMuE,QAAQyE,UAAUV,GAAMzI,GAAGG,MAAMuE,QAAQyE,UAAUV,MAE5DzI,IAAGG,MAAMuE,QAAQyE,UAAUV,GAAI9B,QAAU,IACzC3G,IAAGgL,YAAYC,KACd,QACA,aACAxC,EACA,IACA,QAIFyC,eAAgB,SAASC,GAExB,GAAGnL,GAAGwD,KAAKmF,cAAcwC,GACzB,CACC,GAAI1G,GAAKzE,GAAGG,MAAMuE,OAClBD,GAAG0E,UAAY1E,EAAG0E,aAElB,KAAI,GAAIV,KAAM0C,GACd,CACC1G,EAAG0E,UAAUV,GAAMhE,EAAG0E,UAAUV,MAChChE,GAAG0E,UAAUV,GAAI9B,SAAWwE,EAAK1C,MAKpCJ,WAAY,SAAShG,GAEpB,GAAI+I,GAAUpL,GAAGqL,KAAKhJ,EAAM,eAC5B,IAAG+I,IAAY,YAAeA,IAAW,aAAeA,GAAW,IACnE,CACC,OAGD,GAAIzB,GAAO3J,GAAGqL,KAAKhJ,EAAM,YACzB,KAAIsH,EACJ,CACCA,EAAOtH,EAAKiJ,UAGb,GAAGtL,GAAGwD,KAAK+F,iBAAiBI,GAC5B,CACChI,KAAK2G,YAEL,IAAIc,GAAQ,GAAIpJ,IAAGuL,YAAY,2BAA4BlJ,GAC1DmJ,YAAa,KACb3C,SAAU,MACV4C,SAAU,KACVf,WAAY,EACZC,UAAW,EACXe,aAAcC,SAAU,OACxBC,OAAQ,IACRzB,QACCS,aAAe,WACdjJ,KAAKkK,SACL7L,IAAGG,MAAMuE,QAAQoH,WAAa,OAGhCxC,QAAUtJ,GAAGyJ,OAAO,OAASC,OAAUvF,MAAQ,qCAAuC2F,KAAMH,KAE7FP,GAAM2C,UAAUC,OAAO,GAAIL,SAAU,UACrCvC,GAAMN,MAEN9I,IAAGG,MAAMuE,QAAQoH,WAAa1C,IAIhCd,WAAY,WAEX,GAAGtI,GAAGG,MAAMuE,QAAQoH,WACpB,CACC9L,GAAGG,MAAMuE,QAAQoH,WAAWf,UAK/B/K,IAAGG,MAAMC,KAAK6L,aAAe,WAE5BtK,KAAKuK,QAAUC,EAAG,EAAGC,EAAG,EAExBpM,IAAGqG,KAAKgG,SAAU,YAAarM,GAAG6K,SAAS,SAASrF,GACnD7D,KAAKuK,QACJC,EAAG3G,EAAE8G,MAAQ9G,EAAE8G,MAAQ9G,EAAE+G,QAAU/G,EAAE+G,SAAWF,SAASG,gBAAgBC,YAAcJ,SAAS7D,KAAKiE,YAAcJ,SAASG,gBAAgBE,WAAa,EACzJN,EAAG5G,EAAEmH,MAAQnH,EAAEmH,MAAQnH,EAAEoH,QAAUpH,EAAEoH,SAAWP,SAASG,gBAAgBK,WAAaR,SAAS7D,KAAKqE,WAAaR,SAASG,gBAAgBM,UAAY,IAErJnL,OAEJ3B,IAAGG,MAAMC,KAAK6L,aAAac,eAAiB,WAE3C,MAAO/M,IAAGgN,MAAMhN,GAAGG,MAAMC,KAAK6L,aAAagB,cAAcf,QAE1DlM,IAAGG,MAAMC,KAAK6L,aAAagB,YAAc,WAExC,SAAUjN,IAAGG,MAAMuE,QAAQwI,cAAgB,YAC3C,CACClN,GAAGG,MAAMuE,QAAQwI,aAAe,GAAIlN,IAAGG,MAAMC,KAAK6L,aAGnD,MAAOjM,IAAGG,MAAMuE,QAAQwI,aAGzB,UAAUlN,IAAGG,MAAMuE,SAAW,YAC9B,CACC1E,GAAGG,MAAMuE"}