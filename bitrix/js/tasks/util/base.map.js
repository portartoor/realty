{"version":3,"file":"base.min.js","sources":["base.js"],"names":["BX","namespace","Tasks","Util","Base","options","mergeEx","prototype","construct","eb","this","option","k","hasOwnProperty","type","isFunction","bindEvent","fireEvent","name","args","onCustomEvent","callback","ctx","delegate","addCustomEvent","callMethod","classRef","arguments","isNotEmptyString","Error","apply","callConstruct","runParentConstructor","owner","superclass","constructor","walkPrototypeChain","obj","fn","ref","destroy","proto","destruct","call","value","opts","optionInteger","parseInt","isNaN","subInstance","instances","instance","Widget","parent","initialized","sys","passCtx","f","this_","Array","slice","unshift","id","toString","toLowerCase","register","Dispatcher","extend","parameters","isPlainObject","child","middle","TypeError","registerDispatcher","methods","constants","methodsStatic","ms","p","DoNothing","vars","registry","pend","bind","find","registerInstance","ReferenceError","event","cb","each","item","method","pr","reject","resolve","getRegistry","res","inst","get","Promise","castToLiteralString","push","methodName","addDeferredBind","addDeferredFire","params","arg","trim","getInstance","Singletons","dispatcher"],"mappings":"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"}