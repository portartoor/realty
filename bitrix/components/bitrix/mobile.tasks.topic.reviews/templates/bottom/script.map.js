{"version":3,"file":"script.min.js","sources":["script.js"],"names":["__MB_TASKS_TASK_TOPIC_REVIEWS_RenderComment","arComment","callbackInserter","commentNode","ratingNode","anchor_id","you_like_class","Math","floor","random","length","ratingTypeId","entityTypeId","eventEntityId","ownerId","allowRatingVote","vote_id","BX","create","props","id","className","children","html","message","parseInt","avatar","style","backgroundImage","backgroundRepeat","backgroundSize","comment_datetime","class_name_unread","attrs","href","__MB_TASKS_TASK_TOPIC_REVIEWS_ShowComments","data","arComments","indx","hasOwnProperty","comNode","commentId","commentText","appendChild","tempDiv","document","createElement","innerHTML","arScripts","getElementsByTagName","script","i","type","parentNode","insertBefore","nextSibling","window","RatingLikeComments","top","Set","display","__MB_TASKS_TASK_TOPIC_REVIEWS_scrollPageBottom","platform","scrollTo","documentElement","scrollHeight","div","scrollTop","offsetHeight"],"mappings":"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"}