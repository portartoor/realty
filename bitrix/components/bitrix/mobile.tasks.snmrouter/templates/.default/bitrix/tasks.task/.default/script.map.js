{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","window","namespace","counter","getId","util","getRandomString","initCheckList","d","id","checkList","this","clickAdd","delegate","clickSeparator","clickMenu","callback","ii","taskId","length","bindItem","container","canAdd","bind","prototype","proxy","e","checkbox","node","hasClass","setAttribute","findParent","tagName","className","hasAttribute","fireEvent","eventCancelBubble","sort","form","elements","value","buttons","push","title","checked","message","findChild","attribute","type","name","show","remove","BXMobileApp","UI","ActionSheet","PreventDefault","text","extraData","separator","showAdd","create","attrs","html","join","appendChild","f","isNotEmptyString","replaceNode","parentNode","removeChild","keyCode","setTimeout","focus","app","exec","attachButton","attachedFiles","mentionButton","smileButton","htmlspecialcharsback","okButton","cancelButton","data","params","htmlspecialchars","removeClass","for","replaceChild","innerHTML","eventName","onCustomEvent","initCheckListView","select","eventNode","superclass","constructor","apply","arguments","actCallback","extend","ids","queue","toggle","TITLE","IS_COMPLETE","SORT_INDEX","indexOf","modify","startQueue","getQuery","query","Tasks","Util","Query","url","add_url_param","act","statusQueue","checkQueue","shift","isFunction","errors","alert","add","TASK_ID","result","execute","realId","titleTask","click","multiple","showDrop","showMenu","parentId","drop","del","addCustomEvent","PageManager","loadPageModal","bx24ModernStyle","taskData","isArray","removeCustomEvent","Events","unsubscribe","closeModalDialog","duration","durationType","durationTypeLabel","SelectPicker","values","multiselect","default_value","pop","timetracker","objectId","tasks","timer","check","time","trueTime","parseInt","currentTime","start","stop","disabled","startTimer","stopTimer","stopPrevious","showPopupLoader","hidePopupLoader","error","getByCode","confirm","replace","stopPr","index","setInterval","clearInterval","refresh","t","Math","floor","i","substring","autoExec","timeEstimate","end","keypress","minsNode","hoursNode","init","onchange","target","h","m","run","key","test","k","keyIdentifier","which","Mobile","edit","opts","nf","parentConstruct","merge","sys","classCode","vars","task","usePull","setTitle","setPullDown","handleInitStack","page","init2","formId","gridId","obj","option","initRestricted","initFull","onChange","onSubmitForm","Grid","Form","getByFormId","restricted","markNode","pNode","addClass","nextSibling","obForm","nullObj","res","submit","Page","LoadingScreen","formData","ajax","prepareForm","tmp","ID","userid","taskid","onExecuted","response","status","BasicAuth","success","actExecute","failure","errorConnection","hide","checkHasErrors","variable","getMenu"],"mappings":"CAAE,WACD,GAAIA,GAAKC,OAAOD,EAChB,IAAIA,GAAMA,EAAG,WAAaA,EAAG,UAAU,UAAYA,EAAG,UAAU,SAAS,QACxE,MACDA,GAAGE,UAAU,uBACb,IAAIC,GAAU,EACbC,EAAQ,WAAY,MAAO,cAAgBD,EAAWH,EAAGK,KAAKC,mBAC9DC,EAAgB,WAChB,GAAIC,GAAI,SAASC,EAAIC,GACpBC,KAAKC,SAAWZ,EAAGa,SAASF,KAAKC,SAAUD,KAC3CA,MAAKG,eAAiBd,EAAGa,SAASF,KAAKG,eAAgBH,KACvDA,MAAKI,UAAYf,EAAGa,SAASF,KAAKI,UAAWJ,KAC7CA,MAAKK,SAAWhB,EAAGa,SAASF,KAAKK,SAAUL,KAC3C,IAAIM,EACJN,MAAKO,OAAST,CACdC,GAAaA,KACb,KAAKO,EAAK,EAAGA,EAAKP,EAAUS,OAAQF,IACpC,CACCN,KAAKS,SAASV,EAAUO,IAEzBN,KAAKU,UAAYrB,EAAG,YAAcS,EAAK,YACvC,IAAIE,KAAKU,WAAarB,EAAG,YAAcS,EAAK,OAC5C,CACCE,KAAKW,OAAS,IACdtB,GAAGuB,KAAKvB,EAAG,YAAcS,EAAK,OAAQ,QAASE,KAAKC,SACpD,IAAIZ,EAAG,YAAcS,EAAK,aAC1B,CACCT,EAAGuB,KAAKvB,EAAG,YAAcS,EAAK,aAAc,QAASE,KAAKG,kBAI7DN,GAAEgB,WACDF,OAAS,MACTD,UAAY,KACZD,SAAW,SAASX,GACnB,GAAIT,EAAG,gBAAkBS,EAAK,QAC7BT,EAAGuB,KAAKvB,EAAG,gBAAkBS,EAAK,QAAS,QAAST,EAAGyB,MAAM,SAASC,GAAIf,KAAKI,UAAUW,EAAGjB,IAAQE,MACrG,IAAIgB,GAAW3B,EAAG,gBAAkBS,GACnCmB,EAAO5B,EAAG,gBAAkBS,EAAK,QAElC,IAAIT,EAAG6B,SAASD,EAAM,8BACtB,CACCA,EAAKE,aAAa,YAAa,IAC/BH,GAASG,aAAa,YAAa,KAEpC,GAAI9B,EAAG6B,SAASD,EAAM,8BACtB,CACCA,EAAKE,aAAa,YAAa,IAC/BH,GAASG,aAAa,YAAa,KAEpC,GAAI9B,EAAG6B,SAASD,EAAM,8BACtB,CACCA,EAAKE,aAAa,YAAa,IAC/BH,GAASG,aAAa,YAAa,KAEpC,GAAI9B,EAAG+B,WAAWJ,GAAWK,QAAU,OAAQC,UAAY,6BAA8BL,GACzF,CACCA,EAAKE,aAAa,eAAgB,IAClCH,GAASG,aAAa,eAAgB,KAEvC,GAAIF,EAAKM,aAAa,aACrBlC,EAAGuB,KAAKI,EAAU,QAAS3B,EAAGyB,MAAM,WAAYd,KAAKwB,UAAU1B,EAAI,cAAkBE,WAErFX,GAAGuB,KAAKI,EAAU,QAAS3B,EAAGyB,MAAM,SAASC,GAAK,MAAO1B,GAAGoC,kBAAkBV,IAAOf,MACtFA,MAAK0B,KAAOV,EAASW,KAAKC,SAAS,sBAAwB9B,EAAK,iBAAiB+B,OAElFzB,UAAY,SAASW,EAAGjB,GACvB,GAAIkB,GAAW3B,EAAG,gBAAkBS,GACnCmB,EAAO5B,EAAG,gBAAkBS,EAAK,SACjCgC,IACD,KAAKb,EAAKM,aAAa,gBACvB,CACC,GAAIN,EAAKM,aAAa,aACrBO,EAAQC,MACPC,MAAOhB,EAASiB,QAAU5C,EAAG6C,QAAQ,yBAA2B7C,EAAG6C,QAAQ,uBAC3E7B,SAAUhB,EAAGa,SAAS,WACrBc,EAASiB,SAAYjB,EAASiB,OAC9BjC,MAAKwB,UAAU1B,EAAI,cACjBE,OAEL,IAAIiB,EAAKM,aAAa,aACrBO,EAAQC,MACPC,MAAO3C,EAAG6C,QAAQ,sBAClB7B,SAAUhB,EAAGa,SAAS,WACrB,GAAI8B,GAAQ3C,EAAG8C,UAAUlB,GAAOI,QAAU,QAASe,WAAaC,KAAO,SAAUC,KAAO,sBAAwBxC,EAAK,aAAe,KACpI,IAAIkC,EACHhC,KAAKuC,KAAKP,EAAMH,MAAO/B,IACtBE,QAGN,GAAIiB,EAAKM,aAAa,aACrBO,EAAQC,MACPC,MAAO3C,EAAG6C,QAAQ,wBAClB7B,SAAUhB,EAAGa,SAAS,WACrBF,KAAKwB,UAAU1B,EAAI,YACnBT,GAAGmD,OAAOvB,IACRjB,OAEL,IAAI8B,EAAQtB,OAAS,EACpB,GAAKlB,QAAOmD,YAAYC,GAAGC,aAAeb,QAAUA,GAAW,kBAAoBS,MACpF,OAAOlD,GAAGuD,eAAe7B,IAE1BvB,QAAU,EACVkC,KAAO,EACPvB,eAAkB,SAASY,GAC1B,GAAIf,KAAKW,OACRX,KAAKK,UAAUwC,KAAO,MAAOC,WAAchD,GAAK,IAAOE,KAAKR,aAAgBuD,UAAY,MACzF,OAAQhC,GAAI1B,EAAGuD,eAAe7B,GAAK,OAEpCd,SAAW,SAASc,GACnB,GAAIf,KAAKW,OACRX,KAAKgD,QAAQ,IAAOhD,KAAKR,UAC1B,OAAQuB,GAAI1B,EAAGuD,eAAe7B,GAAK,OAEpCiC,QAAU,SAASlD,GAClB,GAAImB,GAAO5B,EAAG4D,OAAO,SACnBC,OACCpD,GAAK,gBAAkBA,EAAK,QAC5BwB,UAAY,QAEb6B,MACC,wDACC,0EACA,uCAAwCrD,EAAI,+BAAgCT,EAAG6C,QAAQ,uCAAuC,MAC/H,WACCkB,KAAK,KAGTpD,MAAKU,UAAU2C,YAAYpC,EAE3B,IAAIzB,GAAU,EACb8D,EAAIjE,EAAGyB,MAAM,SAAShB,GACtB,GAAIN,EAAU,IACb,MACDA,IAEA,IAAIH,EAAG,gBAAkBS,EAAK,QAAS,CACtCT,EAAGuB,KAAKvB,EAAG,gBAAkBS,EAAK,QAAS,OAAQT,EAAGyB,MAAM,WAC3D,GAAIzB,EAAG,gBAAkBS,EAAK,QAC9B,CACC,GAAI+C,GAAOxD,EAAG,gBAAkBS,EAAK,QAAQ+B,MAC5CZ,EAAO5B,EAAG,gBAAkBS,EAAK,QAClC,IAAIT,EAAGgD,KAAKkB,iBAAiBV,GAC5B7C,KAAKK,UAAUwC,KAAOA,EAAMC,WAAahD,GAAKA,KAAQ0D,YAAcnE,EAAG,gBAAkBS,EAAK,eAC1F,IAAImB,GAAQA,EAAKwC,WACrBxC,EAAKwC,WAAWC,YAAYzC,KAE5BjB,MACHX,GAAGuB,KAAKvB,EAAG,gBAAkBS,EAAK,QAAS,QAAST,EAAGyB,MAAM,SAAUC,GACtE,GAAIA,EAAE4C,SAAW,GACjB,CACC,GAAId,GAAOxD,EAAG,gBAAkBS,EAAK,QAAQ+B,MAC5CZ,EAAO5B,EAAG,gBAAkBS,EAAK,QAClC,IAAIT,EAAGgD,KAAKkB,iBAAiBV,GAC5Be,WAAWvE,EAAGyB,MAAMd,KAAKC,SAAUD,MAAO,SACtC,IAAIiB,GAAQA,EAAKwC,WACrBxC,EAAKwC,WAAWC,YAAYzC,KAE5BjB,MAEH4D,YAAW,WAAWvE,EAAGwE,MAAMxE,EAAG,gBAAkBS,EAAK,UAAW,SAEhE,CAAE8D,WAAW,WAAYN,EAAExD,IAAQ,OACtCE,KACHsD,GAAExD,IAEHyC,KAAO,SAASV,EAAO/B,GACtBR,OAAOwE,IAAIC,KAAK,gBACfC,aAAe,KACfC,cAAgB,KAChBnB,WACChD,GAAKA,GAENoE,cAAe,KACfC,YAAa,KACbjC,SAAYW,KAAOxD,EAAGK,KAAK0E,qBAAqBvC,IAChDwC,UACChE,SAAUL,KAAKK,SACfiC,KAAMjD,EAAG6C,QAAQ,wBAElBoC,cACCjE,SAAW,aACXiC,KAAOjD,EAAG6C,QAAQ,6BAIrB7B,SAAU,SAASkE,EAAMC,GACxBD,EAAK1B,KAAQxD,EAAGK,KAAK+E,iBAAiBF,EAAK1B,OAAS,EACpD2B,GAAUA,KACV,IAAI1E,GAAMyE,EAAKzB,UAAY,GAC1B7B,EAAMgB,EAAU,MAChBuB,EAAcgB,EAAOhB,YACrBT,EAAYyB,EAAOzB,SACpB,IAAI1D,EAAG,gBAAkBS,GACzB,CACCmB,EAAO5B,EAAG,gBAAkBS,EAAK,QACjCT,GAAGqF,YAAYzD,EAAM,OACrBgB,GAAU5C,EAAG,gBAAkBS,GAAImC,YAGpC,CACChB,EAAO5B,EAAG4D,OAAO,SAAUC,OAC1ByB,MAAQ,gBAAkB7E,EAC1BA,GAAK,gBAAkBA,EAAK,QAC5BwB,UAAY,yGAEb,IAAIjC,EAAGmE,GACP,CACCA,EAAYC,WAAWmB,aAAa3D,EAAMuC,OAG3C,CACCxD,KAAKU,UAAU2C,YAAYpC,IAI7BA,EAAK4D,WACH,gBAAkB9B,EAAY,4BAA8B,yCAA2C,KACtG,iDAAkDjD,EAAI,iBAAkBA,EAAI,OAC5E,mDAAoDA,EAAI,oCAAqCA,EAAI,IAAMmC,EAAU,YAAc,GAAK,gBACnIc,EAAY,GAAK,6DAA+DwB,EAAK1B,KAAO,UAC7F,gDAAiD/C,EAAI,aACrD,iDAAkDA,EAAI,oBAAqByE,EAAK1B,KAAM,OACtF,iDAAkD/C,EAAI,yBAA2ByE,EAAK7C,QAAW1B,KAAK0B,KAAQ,OAC/G,WACC0B,KAAK,GACR,IAAI5D,GAAU,EACb8D,EAAIjE,EAAGyB,MAAM,SAAShB,GACtB,GAAIN,EAAU,IACb,MACDA,IACA,IAAIH,EAAG,gBAAkBS,EAAK,QAAS,CACtCE,KAAKS,SAASX,EACdE,MAAKwB,UAAU1B,EAAI,SAAU0E,OAEzB,CAAEZ,WAAW,WAAYN,EAAExD,IAAQ,OACtCE,KACHsD,GAAExD,IAEH0B,UAAY,SAAS1B,EAAIgF,EAAWP,GACnClF,EAAG0F,cAAc/E,KAAM,YAAaA,KAAMX,EAAG,gBAAkBS,GAAKgF,EAAWP,KAGjF,OAAO1E,MAEPmF,EAAoB,WACnB,GAAInF,GAAI,SAASoF,EAAQC,EAAWxE,GACnCsE,EAAkBG,WAAWC,YAAYC,MAAMrF,KAAMsF,UACrDtF,MAAKuF,YAAclG,EAAGa,SAASF,KAAKuF,YAAavF,MAElDX,GAAGmG,OAAO3F,EAAGD,EACbC,GAAEgB,UAAU4E,MACZ5F,GAAEgB,UAAU6E,QACZ7F,GAAEgB,UAAUpB,MAAQ,SAASK,GAC5B,MAAQE,MAAKyF,IAAI3F,IAAOA,EAEzBD,GAAEgB,UAAUW,UAAY,SAAS1B,EAAIgF,EAAWN,GAC/CxE,KAAK0F,MAAM3D,MAAM1C,EAAGyB,MAAM,WACzB,GAAIG,GAAO5B,EAAG,gBAAkBS,EAChC,IAAImB,GAAQA,EAAKU,KACjB,CACC,GAAImD,GAAa,SAChB9E,KAAKwC,OAAO1C,OACR,IAAIgF,GAAa,SACrB9E,KAAK2F,OAAO7F,EAAImB,OAEjB,CACC,GAAIsD,IACHqB,MAAQ3E,EAAKU,KAAKC,SAAS,sBAAwB9B,EAAK,YAAY+B,MACpEgE,YAAc5E,EAAKU,KAAKC,SAAS,sBAAwB9B,EAAK,kBAAkBmC,QAAU,IAAM,IAChG6D,WAAa7E,EAAKU,KAAKC,SAAS,sBAAwB9B,EAAK,iBAAiB+B,MAE/E,IAAIiD,GAAa,WAAa9E,KAAKP,MAAMK,GAAM,IAAIiG,QAAQ,OAAS,EACnE/F,KAAKiD,OAAOnD,EAAIyE,EAAMC,OAEtBxE,MAAKgG,OAAOlG,EAAIyE,MAGjBvE,MAAOsF,WACVtF,MAAKiG,aAENpG,GAAEgB,UAAUqF,SAAW,WAEtB,IAAKlG,KAAKmG,MACV,CACCnG,KAAKmG,MAAQ,GAAI9G,GAAG+G,MAAMC,KAAKC,OAAOC,IAAMlH,EAAGK,KAAK8G,cAAcnH,EAAG6C,QAAQ,sBAAuBuE,IAAM,YAAa3G,GAAKE,KAAKO,WAGlI,MAAOP,MAAKmG,MAEbtG,GAAEgB,UAAU6F,YAAc,OAC1B7G,GAAEgB,UAAUoF,WAAa,WAExB,GAAIjG,KAAK0G,cAAgB,QACzB,CACC1G,KAAK0G,YAAc,MACnB1G,MAAK2G,cAGP9G,GAAEgB,UAAU8F,WAAa,WAExB,GAAIrD,GAAItD,KAAK0F,MAAMkB,OACnB,IAAItD,GAAKjE,EAAGgD,KAAKwE,WAAWvD,EAAE,IAC9B,CACCA,EAAE,GAAG+B,MAAMrF,KAAMsD,EAAE,QAGpB,CACCtD,KAAK0G,YAAc,SAGrB7G,GAAEgB,UAAU0E,YAAc,SAASuB,GAClC,GAAIA,GAAUA,EAAOtG,OAAS,EAC9B,CACC,IAAK,GAAIF,GAAK,EAAGA,EAAKwG,EAAOtG,OAAQF,IACpCwG,EAAOxG,GAAOwG,EAAOxG,GAAI,YAAcwG,EAAOxG,GAAI,OACnDhB,QAAOwE,IAAIiD,OAAOlE,KAAMiE,EAAO1D,KAAK,MAAOpB,MAAQ3C,EAAG6C,QAAQ,+BAE/DlC,KAAK2G,aAEN9G,GAAEgB,UAAUoC,OAAS,SAASnD,EAAIyE,GACjCvE,KACAkG,WACAc,IAAI,sBAAuBzC,MACzB0C,QAASjH,KAAKO,OACdqF,MAAOrB,EAAKqB,MACZC,YAAatB,EAAKsB,YAClBC,WAAYvB,EAAKuB,gBACVzG,EAAGyB,MAAM,SAASgG,EAAQI,GAClC,GAAIJ,GAAUA,EAAOtG,OAAS,EAC9B,CACC,IAAK,GAAIF,GAAK,EAAGA,EAAKwG,EAAOtG,OAAQF,IACpCwG,EAAOxG,GAAOwG,EAAOxG,GAAI,YAAcwG,EAAOxG,GAAI,OACnDhB,QAAOwE,IAAIiD,OAAOlE,KAAMiE,EAAO1D,KAAK,MAAOpB,MAAQ3C,EAAG6C,QAAQ,8BAC9D7C,GAAGmD,OAAOnD,EAAG,gBAAkBS,EAAK,cAGrC,CACCE,KAAKyF,IAAI3F,GAAMoH,EAAO,UAAU,QAAQ,MAEzClH,KAAK2G,cACH3G,OACHmH,UAEDtH,GAAEgB,UAAUmF,OAAS,SAASlG,EAAIyE,GACjC,GAAI6C,GAASpH,KAAKP,MAAMK,EACxBE,MACAkG,WACAc,IACC,yBACClH,GAAIsH,EAAQ7C,MACZqB,MAAOrB,EAAKqB,WAGb5F,KAAKuF,aACN4B,UAEDtH,GAAEgB,UAAU2B,OAAS,SAAS1C,GAC7B,GAAIsH,GAASpH,KAAKP,MAAMK,EACxBE,MACAkG,WACAc,IACC,yBACClH,GAAIsH,MAELpH,KAAKuF,aACN4B,UAEDtH,GAAEgB,UAAU8E,OAAS,SAAS7F,EAAImB,GACjC,GAAImG,GAASpH,KAAKP,MAAMK,EACxBE,MACAkG,WACAc,IACC,mBAAqB/F,EAAKgB,QAAU,WAAa,UAChDnC,GAAIsH,MAELpH,KAAKuF,aACN4B,UAED,OAAOtH,MAERwH,EAAY,WACZ,GAAIxH,GAAI,SAASC,GAChBE,KAAKsH,MAAQjI,EAAGa,SAASF,KAAKsH,MAAOtH,KACrCA,MAAKK,SAAWhB,EAAGa,SAASF,KAAKK,SAAUL,KAC3CA,MAAKiB,KAAO5B,EAAG,QAAUS,EACzBE,MAAKU,UAAYrB,EAAG,QAAUS,EAAK,YACnC,IAAIE,KAAKiB,MAAQjB,KAAKU,UACtB,CACCrB,EAAGuB,KAAKZ,KAAKU,UAAU+C,WAAY,QAASzD,KAAKsH,QAGnDzH,GAAEgB,WACD0G,SAAW,MACXtC,OAAS,KACTC,UAAY,KACZxE,UAAY,KACZ8G,SAAW,KACXC,SAAW,MACXH,MAAQ,SAASvG,GAChBf,KAAKuC,MACL,OAAOlD,GAAGuD,eAAe7B,IAE1BwB,KAAO,WACNjD,OAAOwE,IAAIC,KAAK,gBACfC,aAAe,KACfC,cAAgB,KAChBnB,aACAoB,cAAe,KACfC,YAAa,KACbjC,SAAYW,KAAOxD,EAAGK,KAAK0E,qBAAqBpE,KAAKiB,KAAKY,QAC1DwC,UACChE,SAAUL,KAAKK,SACfiC,KAAMjD,EAAG6C,QAAQ,wBAElBoC,cACCjE,SAAW,aACXiC,KAAOjD,EAAG6C,QAAQ,6BAIrB7B,SAAU,SAASkE,GAClBA,EAAK1B,KAAQxD,EAAGK,KAAK+E,iBAAiBF,EAAK1B,OAAS,EACpD,IAAI0B,EAAK1B,KAAKrC,OAAS,EACvB,CACCR,KAAKU,UAAUmE,UAAYN,EAAK1B,IAChC7C,MAAKiB,KAAKY,MAAQ0C,EAAK1B,KAExBxD,EAAG0F,cAAc/E,KAAM,YAAaA,KAAMA,KAAKiB,QAGjD,OAAOpB,MAEP6H,EAAW,WACX,GAAI7H,GAAI,SAASC,GAChBE,KAAKsH,MAAQjI,EAAGa,SAASF,KAAKsH,MAAOtH,KACrCA,MAAKK,SAAWhB,EAAGa,SAASF,KAAKK,SAAUL,KAC3CA,MAAK2H,KAAOtI,EAAGa,SAASF,KAAK2H,KAAM3H,KAEnCA,MAAKF,GAAKA,CACVE,MAAKiB,KAAO5B,EAAG,WAAaS,EAC5BE,MAAKU,UAAYrB,EAAG,WAAaS,EAAK,YACtC,IAAIE,KAAKiB,MAAQjB,KAAKU,UACtB,CACCrB,EAAGuB,KAAKvB,EAAG,WAAaS,EAAK,UAAW,QAASE,KAAKsH,MACtD,IAAIM,GAAMvI,EAAG8C,UAAUnC,KAAKU,UAAU+C,YAAapC,QAAU,OAAQ,KACrE,IAAIuG,EACHvI,EAAGuB,KAAKgH,EAAK,QAAS5H,KAAK2H,OAG9B9H,GAAEgB,WACD0G,SAAW,MACXtC,OAAS,KACTC,UAAY,KACZxE,UAAY,KACZ8G,SAAW,KACXC,SAAW,MACXH,MAAQ,SAASvG,GAChBf,KAAKuC,MACL,OAAOlD,GAAGuD,eAAe7B,IAE1BwB,KAAO,WAENE,YAAYoF,eAAevI,OAAQ,iCAAkCU,KAAKK,SAC1Ef,QAAOmD,YAAYqF,YAAYC,eAC9BxB,IAAKlH,EAAG6C,QAAQ,yBAA2B,sBAAwBlC,KAAKF,GACxEkI,gBAAkB,QAGpBL,KAAO,WACN3H,KAAKiB,KAAKY,MAAQ,CAClBxC,GAAG0F,cAAc/E,KAAM,YAAaA,KAAMA,KAAKiB,QAEhDZ,SAAW,SAASP,EAAImI,GACvB,IAAKA,GAAY5I,EAAGgD,KAAK6F,QAAQpI,GACjC,CACCmI,EAAWnI,EAAG,EACdA,GAAKA,EAAG,GAET,GAAIA,GAAME,KAAKF,IAAMmI,EACrB,CACC5I,EAAG8I,kBAAkB7I,OAAQ,iCAAkCU,KAAKK,SACpEoC,aAAY2F,OAAOC,YAAY,iCAC/BrI,MAAKiB,KAAKY,MAAQoG,EAAS,KAC3BjI,MAAKU,UAAUmE,UAAYxF,EAAGK,KAAK+E,iBAAiBwD,EAAS,SAC7D5I,GAAG0F,cAAc/E,KAAM,YAAaA,KAAMA,KAAKiB,OAEhD3B,OAAOwE,IAAIwE,sBAGb,OAAOzI,MAEP0I,EAAW,WACT,GAAI1I,GAAI,SAASC,GAChBE,KAAKsH,MAAQjI,EAAGa,SAASF,KAAKsH,MAAOtH,KACrCA,MAAKK,SAAWhB,EAAGa,SAASF,KAAKK,SAAUL,KAC3CA,MAAKwI,aAAenJ,EAAG,eAAiBS,EACxCE,MAAKyI,kBAAoBpJ,EAAG,eAAiBS,EAAK,QAClDT,GAAGuB,KAAKZ,KAAKyI,kBAAmB,QAASzI,KAAKsH,OAE/CzH,GAAEgB,WACDyG,MAAQ,SAASvG,GAChBf,KAAKuC,MACL,OAAOlD,GAAGuD,eAAe7B,IAE1BwB,KAAO,WACNE,YAAYC,GAAGgG,aAAanG,MAC3BlC,SAAUL,KAAKK,SACfsI,QACCtJ,EAAG6C,QAAQ,8CACX7C,EAAG6C,QAAQ,8CAEZ0G,YAAa,MACbC,cAAiB7I,KAAKwI,aAAa3G,OAAS,QAAUxC,EAAG6C,QAAQ,8CAAgD7C,EAAG6C,QAAQ,gDAG9H7B,SAAW,SAASkE,GACnB,GAAIA,GAAQA,EAAKoE,QAAUpE,EAAKoE,OAAOnI,OAAS,EAChD,CACC,GAAIwB,GAAQuC,EAAKoE,OAAOG,KACxB,IAAI9G,GAAS3C,EAAG6C,QAAQ,6CACxB,CACClC,KAAKwI,aAAa3G,MAAQ,MAC1B7B,MAAKyI,kBAAkB5D,UAAYxF,EAAG6C,QAAQ,iDAG/C,CACClC,KAAKwI,aAAa3G,MAAQ,OAC1B7B,MAAKyI,kBAAkB5D,UAAYxF,EAAG6C,QAAQ,iDAKlD,OAAOrC,MAETkJ,EAAc,WACb,GAAIlJ,GAAI,SAASC,EAAIyE,GACpBvE,KAAKgJ,SAAW3J,EAAGK,KAAKC,iBACxBK,MAAKF,GAAKA,CACVE,MAAKuE,KAAOA,CACZvE,MAAKiB,KAAO5B,EAAG,wBAA0BS,EACzCE,MAAKiJ,QACLjJ,MAAKkJ,MAAQ,IACblJ,MAAKmJ,MAAQ9J,EAAGa,SAASF,KAAKmJ,MAAOnJ,KACrCA,MAAKsH,MAAQjI,EAAGa,SAASF,KAAKsH,MAAOtH,KACrCA,MAAKoJ,MACJC,SAAY9E,GAAQA,EAAK,gBAAkB+E,SAAS/E,EAAK,iBAAmB,EAC5EgF,YAAc,EAEf,IAAIvJ,KAAKiB,KACT,CACC,GAAIjB,KAAKiB,KAAKgB,QACbjC,KAAKwJ,OACNnK,GAAGuB,KAAKZ,KAAKiB,KAAM,QAASjB,KAAKsH,MACjC,IAAItH,KAAKoJ,KAAKC,UAAY,EACzBrJ,KAAKoJ,KAAKC,SAAWC,SAAStJ,KAAKiB,KAAKY,MAEzCY,aAAYoF,eAAe,qBAAsBxI,EAAGyB,MAAM,SAASP,EAAQyI,EAAUzE,GACpF,IAAKA,EACL,CACCA,EAAOhE,EAAO,EACdyI,GAAWzI,EAAO,EAClBA,GAASA,EAAO,GAEjB,GAAIP,KAAKF,IAAMS,GAAUP,KAAKgJ,UAAYA,EAC1C,CACC,GAAIzE,EAAK,cAAgB,2BACzB,CACCvE,KAAKwJ,YAED,IAAIjF,EAAK,cAAgB,2BAC7BA,EAAK,cAAgB,aACtB,CACCvE,KAAKyJ,WAED,IAAIlF,EAAK,cAAgB,gBAC9B,CACCvE,KAAKyJ,MACLzJ,MAAKiB,KAAKyI,SAAW,SAEjB,IAAInF,EAAK,cAAgB,cAAgBA,EAAK,cAAgB,aACnE,CACCvE,KAAKiB,KAAKyI,SAAW,SAGrB1J,QAGLH,GAAEgB,WACDyG,MAAQ,SAASvG,GAChB1B,EAAGoC,kBAAkBV,EACrB,IAAIf,KAAKiB,KAAKgB,QACbjC,KAAK2J,iBAEL3J,MAAK4J,WACN,OAAOvK,GAAGuD,eAAe7B,IAE1B4I,WAAY,SAASE,GAEpBvK,OAAOwE,IAAIgG,iBACX9J,MAAKkG,WAAWc,IAAI,4BAA6BzG,OAAQP,KAAKF,GAAI+J,aAAcA,GAAgB,UAAYxK,EAAGa,SAAS,SAAS4G,EAAQvC,GACxIjF,OAAOwE,IAAIiG,iBACX,IAAIC,GAAQlD,EAAOmD,UAAU,sBAC7B,IAAID,EACJ,CACC,GAAInK,GAAImK,EAAMzF,MACdjF,QAAOwE,IAAIoG,SACVlI,MAAO3C,EAAG6C,QAAQ,yBAClBW,KAAMxD,EAAG6C,QAAQ,wBAAwBiI,QAAQ,UAAWtK,EAAE,QAAQ,UACtEQ,SAAWhB,EAAGyB,MAAM,SAASsJ,GAAQ,MAAO/K,GAAGyB,MAAM,SAASuJ,GAC7D,GAAIA,GAAS,EACZrK,KAAK2J,WAAWS,IACfpK,OAAQA,MAAOH,EAAE,QAAQ,OAC5BiC,SAAUzC,EAAG6C,QAAQ,qBAAsB7C,EAAG6C,QAAQ,0BAIxD,CACClC,KAAKwJ,OACLlK,QAAOmD,YAAYsC,cAAc,sBAAuB/E,KAAKF,GAAIE,KAAKgJ,SAAUzE,GAAO,KAAM,QAE5FvE,QAEJ4J,UAAW,WAEVtK,OAAOwE,IAAIgG,iBACX9J,MAAKkG,WAAWc,IAAI,2BAA4BzG,OAAQP,KAAKF,OAAST,EAAGa,SAAS,SAAS4G,EAAQvC,GAClGjF,OAAOwE,IAAIiG,iBACX,IAAIjD,GAAUA,EAAOtG,OAAS,EAC9B,CACC,IAAK,GAAIF,GAAK,EAAGA,EAAKwG,EAAOtG,OAAQF,IACpCwG,EAAOxG,GAAOwG,EAAOxG,GAAI,YAAcwG,EAAOxG,GAAI,OACnDhB,QAAOwE,IAAIiD,OAAOlE,KAAMiE,EAAO1D,KAAK,MAAOpB,MAAQ3C,EAAG6C,QAAQ,mCAG/D,CACClC,KAAKyJ,MACLnK,QAAOmD,YAAYsC,cAAc,sBAAuB/E,KAAKF,GAAIE,KAAKgJ,SAAUzE,GAAO,KAAM,QAE5FvE,QAEJwJ,MAAQ,WACPxJ,KAAKiB,KAAKgB,QAAU,IACpB,IAAIjC,KAAKkJ,QAAU,KAClBlJ,KAAKkJ,MAAQoB,YAAYtK,KAAKmJ,MAAO,MAEvCM,KAAO,WACNzJ,KAAKiB,KAAKgB,QAAU,KACpBjC,MAAKiB,KAAKY,MAAS7B,KAAKoJ,KAAKC,SAAWrJ,KAAKoJ,KAAKG,WAClDgB,eAAcvK,KAAKkJ,MACnBlJ,MAAKkJ,MAAQ,MAEdC,MAAQ,WACPnJ,KAAKwK,UAAWxK,KAAKoJ,KAAKG,YAAevJ,KAAKoJ,KAAKC,WAEpDmB,QAAU,SAASpB,GAClB,GAAInI,GAAO5B,EAAG,wBAA0BW,KAAKF,GAAK,UACjD2K,GACCC,KAAKC,MAAMvB,EAAO,MACjBsB,KAAKC,MAAMvB,EAAO,IAAM,GACzBA,EAAO,IACLwB,CACJ,KAAKA,EAAI,EAAGA,EAAIH,EAAEjK,OAAQoK,IAAK,CAC9BH,EAAEG,IAAM,EACRH,GAAEG,GAAK,KAAKC,UAAU,EAAG,EAAIJ,EAAEG,GAAGpK,QAAUiK,EAAEG,GAE/C3J,EAAK4D,UAAY4F,EAAErH,KAAK,MAEzB8C,SAAW,WAEV,IAAKlG,KAAKmG,MACV,CACCnG,KAAKmG,MAAQ,GAAI9G,GAAG+G,MAAMC,KAAKC,OAAOC,IAAMlH,EAAGK,KAAK8G,cAAcnH,EAAG6C,QAAQ,sBAAuBuE,IAAM,UAAW3G,GAAKE,KAAKF,KAAMgL,SAAW,OAEjJ,MAAO9K,MAAKmG,OAGd,OAAOtG,MAERkL,EAAe,WACd,GAAIlL,GAAI,SAASC,GAChBE,KAAKwJ,MAAQnK,EAAGa,SAASF,KAAKwJ,MAAOxJ,KACrCA,MAAKgL,IAAM3L,EAAGa,SAASF,KAAKgL,IAAKhL,KACjCA,MAAKiL,SAAW5L,EAAGa,SAASF,KAAKiL,SAAUjL,KAC3CA,MAAKkJ,MAAQ,IACblJ,MAAKiB,KAAO5B,EAAG,eAAiBS,EAAK,UACrCE,MAAKkL,SAAW7L,EAAG,eAAiBS,EAAK,UACzCE,MAAKmL,UAAY9L,EAAG,eAAiBS,EAAK,QAC1C,IAAIE,KAAKiB,MAAQjB,KAAKkL,UAAYlL,KAAKmL,UACtCnL,KAAKoL,MACN/L,GAAGuB,KAAKZ,KAAKmL,UAAW,QAASnL,KAAKwJ,MACtCnK,GAAGuB,KAAKZ,KAAKmL,UAAW,OAAQnL,KAAKgL,IACrC3L,GAAGuB,KAAKZ,KAAKkL,SAAU,QAASlL,KAAKwJ,MACrCnK,GAAGuB,KAAKZ,KAAKkL,SAAU,OAAQlL,KAAKgL,IACpC3L,GAAGuB,KAAKZ,KAAKmL,UAAW,WAAYnL,KAAKiL,SACzC5L,GAAGuB,KAAKZ,KAAKkL,SAAU,WAAYlL,KAAKiL,UAEzCpL,GAAEgB,WACDuK,KAAO,WACN,GAAIhC,GAAOE,SAAStJ,KAAKiB,KAAKY,MAC9BuH,GAAQA,EAAO,EAAIA,EAAO,CAC1BpJ,MAAKmL,UAAUtJ,MAAQ6I,KAAKC,MAAMvB,EAAO,KACzCpJ,MAAKmL,UAAU7J,UAAY,wBAA0BtB,KAAKmL,UAAUtJ,MAAMrB,MAC1ER,MAAKkL,SAASrJ,MAAQ6I,KAAKC,MAAMvB,EAAO,IAAM,EAC9CpJ,MAAKkL,SAAS5J,UAAY,wBAA0BtB,KAAKkL,SAASrJ,MAAMrB,QAEzEgJ,MAAQ,SAASzI,GAChB,GAAIf,KAAKkJ,QAAU,KAClBqB,cAAcvK,KAAKkJ,MACpBlJ,MAAKkJ,MAAQoB,YAAYjL,EAAGyB,MAAM,WACjCd,KAAKqL,SAAStK,EAAEuK,SACdtL,MAAO,MAEXgL,IAAM,WACLT,cAAcvK,KAAKkJ,MACnBlJ,MAAKkJ,MAAQ,MAEdmC,SAAW,SAASpK,GACnBA,EAAKY,OAASZ,EAAKY,MAAQ,IAAIsI,QAAQ,QAAS,GAChD,IAAI9K,EAAG4B,GACP,CACCA,EAAKK,UAAY,wBAA0BL,EAAKY,MAAMrB,OAEvD,GAAI+K,GAAIjC,SAAStJ,KAAKmL,UAAUtJ,OAAQ2J,EAAIlC,SAAStJ,KAAKkL,SAASrJ,MACnE7B,MAAKiB,KAAKY,OAAU0J,EAAI,EAAIA,EAAI,KAAO,IAAMC,EAAI,EAAIA,EAAI,GAAK,IAE/DP,SAAW,SAASlK,GACnB,GAAI0K,GAAM,KACV,KAAK1K,EACL,MAEK,IAAIA,EAAE2K,IACX,CACCD,EAAM,KAAKE,KAAK5K,EAAE2K,SAGnB,CACC,GAAIE,GAAK7K,EAAE4C,SAAW5C,EAAE8K,eAAiB9K,EAAE+K,KAC3CL,GAAO,GAAKG,GAAKA,EAAI,GAEtB,GAAIH,EACH,MAAO,KACR,OAAOpM,GAAGuD,eAAe7B,IAG3B,OAAOlB,KAGTR,GAAG0M,OAAO3F,MAAM4F,KAAO,SAASC,EAAMC,GAErClM,KAAKmM,gBAAgB9M,EAAG0M,OAAO3F,MAAM4F,KAAMC,EAE3C5M,GAAG+M,MAAMpM,MACRqM,KACCC,UAAW,QAEZC,MACCzM,GAAKL,KAEN+M,KAAOP,EAAKhE,UAEb5I,GAAG+M,MAAMH,GACRQ,QAAU,MACVC,SAAW,KACXC,YAAc,OAEf3M,MAAK4M,gBAAgBV,EAAI7M,EAAG0M,OAAO3F,MAAM4F,KAAMC,GAEhD5M,GAAGmG,OAAOnG,EAAG0M,OAAO3F,MAAM4F,KAAM3M,EAAG0M,OAAO3F,MAAMyG,KAEhDxN,GAAG+M,MAAM/M,EAAG0M,OAAO3F,MAAM4F,KAAKnL,WAE7BuK,KAAM,WACL,GAAI0B,GAAQzN,EAAGa,SAAS,SAAS6M,EAAQC,EAAQC,GAChD,GAAIF,GAAU/M,KAAKkN,OAAO,WAAaD,EACvC,CACC,GAAIA,EAAI,oBAAoB,MAAQA,EAAI,mBAAmB,IAC1DjN,KAAKmN,eAAeF,OAEpBjN,MAAKoN,SAASH,EAEf5N,GAAGwI,eAAeoF,EAAK,WAAY5N,EAAGyB,MAAMd,KAAKqN,SAAUrN,MAC3DX,GAAGwI,eAAeoF,EAAK,WAAY,WAAa3N,OAAOwE,IAAIwE,sBAE3D7F,aAAYoF,eAAeoF,EAAK,eAAgB5N,EAAGyB,MAAMd,KAAKsN,aAActN,SAE3EA,KACHX,GAAGwI,eAAe,gBAAiBiF,EACnC,IAAInL,GAAOtC,EAAG0M,OAAOwB,KAAKC,KAAKC,YAAYzN,KAAKkN,OAAO,UACvDJ,GAAM9M,KAAKkN,OAAO,UAAW,gBAAiBvL,IAE/CwL,eAAiB,SAASF,GACzBjN,KAAK0N,WAAa,IAClB,IAAI1I,GAAkBhF,KAAKwM,KAAK,MAAOxM,KAAKwM,KAAK,aACjD,IAAIzD,GAAY/I,KAAKwM,KAAK,MAAOxM,KAAKwM,KAEtC,IAAI5B,GAAIqC,EAAIrL,SAASpB,MACrByM,GAAIrL,SAASG,KAAK,GAAIsF,GAAUrH,KAAKwM,KAAK,OAC1CS,GAAIrL,SAASG,KAAK,GAAIwG,GAASvI,KAAKwM,KAAK,OAEzC,IAAIlJ,GAAI,WAAa2J,EAAI5H,MAAMA,MAAMC,WACrC,KAAK,GAAIhF,GAAKsK,EAAGtK,EAAK2M,EAAIrL,SAASpB,OAAQF,IAC3C,CACCjB,EAAGwI,eAAeoF,EAAIrL,SAAStB,GAAK,WAAYgD,KAIlD8J,SAAY,SAASH,GACpBA,EAAIrL,SAASG,KAAK,GAAInC,GAAcI,KAAKwM,KAAK,MAAOxM,KAAKwM,KAAK,cAC/DS,GAAIrL,SAASG,KAAK,GAAI2F,GAAS1H,KAAKwM,KAAK,OACzCS,GAAIrL,SAASG,KAAK,GAAIwG,GAASvI,KAAKwM,KAAK,OACzCS,GAAIrL,SAASG,KAAK,GAAIgJ,GAAa/K,KAAKwM,KAAK,SAE9Ca,SAAW,SAASJ,EAAKhM,GACxB,GAAIU,GAAOtC,EAAGW,KAAKkN,OAAO,WACzBS,EAAWhM,EAAKC,SAAS,aAC1B,IAAIvC,EAAG4B,IAASA,GAAQ0M,EACxB,CACC,GAAIC,GAAQvO,EAAG+B,WAAWH,GAAOK,UAAY,sBAAuBK,EACpE,IAAIV,EAAKY,OAAS,IAClB,CACCxC,EAAGqF,YAAYkJ,EAAO,uBACtB,KAAKvO,EAAG6B,SAAS0M,EAAO,wBACvBvO,EAAGwO,SAASD,EAAO,4BAEhB,IAAI3M,EAAKY,OAAS,IACvB,CACCxC,EAAGqF,YAAYkJ,EAAO,uBACtB,KAAKvO,EAAG6B,SAAS0M,EAAO,wBACvBvO,EAAGwO,SAASD,EAAO,4BAGrB,CACCvO,EAAGqF,YAAYkJ,EAAO,uBACtBvO,GAAGqF,YAAYkJ,EAAO,yBAGxB,GAAI3M,EAAKqB,MAAQ,iBACjB,CACCrB,EAAK6M,YAAYjJ,UAAYxF,EAAG6C,QAAQ,mBAAqBjB,EAAKgB,QAAU,IAAM,UAE9E,IAAIhB,EAAKqB,MAAQ,wBACtB,CACCrB,EAAK6M,YAAYjJ,UAAa5D,EAAKgB,QAAU5C,EAAG6C,QAAQ,qBAAuB7C,EAAG6C,QAAQ,uBAG5FoL,aAAe,SAASL,EAAKc,EAAQC,EAASC,GAC7CA,EAAIC,OAAS,KACb,KAAKlO,KAAK0N,WACTjL,YAAYC,GAAGyL,KAAKC,cAAc7L,MACnC,IAAI8L,GAAWhP,EAAGiP,KAAKC,YAAYR,GAAQxJ,KAC1CA,EAAO8J,EAAS9J,KAChBzE,EAAKE,KAAKwM,KAAK,MACfjG,EAAMlH,EAAGK,KAAK8G,cAAcnH,EAAG6C,QAAQ,sBAAuBuE,IAAM,SAAU3G,GAAKA,IACnFQ,EAAIW,EAAMuN,CAEX,IAAIjK,EAAK,cACT,CACCiK,EAAMjK,EAAK,aACXA,GAAK,gBACL,QAAQjE,EAAKkO,EAAI1F,QAAUxI,EAC1BiE,EAAK,cAAcxC,MAAM0M,GAAKnO,IAEhC,GAAIiE,EAAK,iBACT,CACCiK,EAAMjK,EAAK,gBACXA,GAAK,mBACL,QAAQjE,EAAKkO,EAAI1F,QAAUxI,EAC1BiE,EAAK,iBAAiBxC,MAAM0M,GAAKnO,IAEnC,GAAIyN,EAAOnM,SAAS,gBACpB,CACC,IAAKtB,EAAK,EAAGA,EAAKyN,EAAOnM,SAAS,gBAAgBpB,OAAQF,IAC1D,CACCW,EAAO8M,EAAOnM,SAAS,gBAAgBtB,EACvCiE,GAAKtD,EAAKY,OAAUZ,EAAKgB,QAAU,IAAM,KAG3C,GAAIjC,KAAK0N,WACT,OACQnJ,GAAK,gBAGb,GAAIC,IAAU1E,GAAKA,EAAI4O,OAASrP,EAAG6C,QAAQ,WAAYyM,OAAS7O,EAAIyE,KAAMA,EAE1E,IAAKlF,GAAG+G,MAAMC,KAAKC,OAAOC,IAAKA,IAAOS,IAAKlH,EAAK,EAAI,cAAgB,WAAa0E,MAChFoK,WAAavP,EAAGyB,MAAM,SAAS+N,GAC9B,GAAIA,GAAYA,EAASA,UAAYA,EAASA,SAASC,QAAU,SACjE,CACCxP,OAAOwE,IAAIiL,WACVC,QAAS3P,EAAGyB,MAAM,WACjB,GAAKzB,GAAG+G,MAAMC,KAAKC,OAAOC,IAAKA,IAC9BS,IAAI,cAAexC,MAAaoK,WAAY5O,KAAKiP,aACjD9H,WACEnH,MACJkP,QAAS,WACR5P,OAAOwE,IAAIiD,OAAOlE,KAAOxD,EAAG6C,QAAQ,wBAAyBF,MAAQ3C,EAAG6C,QAAQ,sCAMlFlC,MAAKiP,WAAW5J,MAAMrF,KAAMsF,YAC3BtF,QACHmH,WAEF8H,WAAa,SAASE,EAAiB5K,GAItC9B,YAAYC,GAAGyL,KAAKC,cAAcgB,MAClC,IAAID,EAAgBE,iBACpB,CACC,GAAIvI,KACJ,KAAK,GAAIxG,GAAK,EAAGA,EAAK6O,EAAgB3O,OAAQF,IAC9C,CACCwG,EAAO/E,KAAKoN,EAAgB7O,GAAI,YAEjChB,OAAOwE,IAAIiD,OAAOlE,KAAMiE,EAAO1D,KAAK,MAAOpB,MAAQ3C,EAAG6C,QAAQ,mCAG/D,CACC5C,OAAOmD,YAAYsC,cAAe/E,KAAKwM,KAAK,MAAQ,EAAI,mBAAqB,oBAAsBxM,KAAKwM,KAAK,MAAOxM,KAAKsP,SAAS,MAAO/K,EAAK,UAAU,QAASA,GAAO,KAAM,KAC9K,KAAKvE,KAAK0N,WACV,CACCpO,OAAOwE,IAAIwE,wBAKd1G,YACA2N,QAAU,WACT"}