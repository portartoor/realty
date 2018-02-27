/**
 * @ignore
 */
var VoxImplant = function () {
};

(function (VoxImplant, undefined) {
  var VI_WEBRTC_STATE_IDLE = "VI_WEBRTC_STATE_IDLE";
  var VI_WEBRTC_STATE_WS_CONNECTING = "VI_WEBRTC_STATE_WS_CONNECTING";
  var VI_WEBRTC_STATE_WS_CONNECTED = "VI_WEBRTC_STATE_WS_CONNECTED";
  var VI_WEBRTC_STATE_CONNECTED = "VI_WEBRTC_STATE_WS_CONNECTED";

  var VI_CALL_STATE_ALERTING = VoxImplant.VI_CALL_STATE_ALERTING = "ALERTING";
  var VI_CALL_STATE_PROGRESSING = VoxImplant.VI_CALL_STATE_PROGRESSING = "PROGRESSING";
  var VI_CALL_STATE_CONNECTED = VoxImplant.VI_CALL_STATE_CONNECTED = "CONNECTED";
  var VI_CALL_STATE_ENDED = VoxImplant.VI_CALL_STATE_ENDED = "ENDED";

  var ICE_TIMEOUT = 6000;
  var RTC_STATS_COLLECTION_INTERVAL = 10000;

  VoxImplant.ZingayaAPI = function (videoEnabled, micRequired) {
    var PING_DELAY = 30000;

    var audioElements = [];
    var videoElements = [];
    var needMic = (false !== micRequired);


    var getAudioElement = function () {
      if (audioElements.length) {
        return audioElements.pop();
      }
      return document.createElement("audio");
    };

    var releaseAudioElement = function (el) {
      attachMediaStream(el, null);
      audioElements.push(el);
    };

    var getVideoElement = function () {
      if (videoElements.length) {
        return videoElements.pop();
      }
      return document.createElement("video");
    };

    var releaseVideoElement = function (el) {
      attachMediaStream(el, null);
      videoElements.push(el);
    };


    var getAudioTracks = function (stream) {
      if (stream) {
        if (stream.audioTracks)
          return stream.audioTracks;
        if (stream.getAudioTracks)
          return stream.getAudioTracks();
      }
      return null;
    };

    var getVideoTracks = function (stream) {
      if (stream) {
        if (stream.videoTracks)
          return stream.videoTracks;
        if (stream.getVideoTracks)
          return stream.getVideoTracks();
      }
      return null;
    };


    var enableTracks = function (tracks, b) {
      if (tracks) {
        for (var i in tracks) {
          tracks[i].enabled = b;

        }
      }
    };

    var promisifiedOldGUM = function (constraints, successCallback, errorCallback) {

      var getUserMedia = (navigator.getUserMedia ||
      navigator.webkitGetUserMedia ||
      navigator.mozGetUserMedia ||
      navigator.msGetUserMedia);

      if (!getUserMedia) {
        return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
      }

      return new Promise(function (successCallback, errorCallback) {
        getUserMedia.call(navigator, constraints, successCallback, errorCallback);
      });
    }

    var videoSupport = videoEnabled === true ? true : false;
    //var mediaConstraints = {'mandatory': { 'OfferToReceiveAudio':true, 'OfferToReceiveVideo':videoSupport }};
    var mediaConstraints = {"offerToReceiveAudio": true, "offerToReceiveVideo": videoSupport};
    var connectionProtocol = "wss";
    var getUserMedia;
    var attachMediaStream;
    var RTCPeerConnection;
    var myRTCIceCandidate;

    var sock = null;
    var state = VI_WEBRTC_STATE_IDLE;

    var isChrome;
    var isFirefox;
    var deviceEnumAPI;
    var audioSourceId;
    var videoSourceId;
    var videoConstraints;
    var rtpSenders = [];

    var microphoneMuted = false;
    var speakerMuted = false;

    var videoSent = true;
    var screenSharingStream = null;

    var pingTimer = null;
    var pongTimer = null;

    var playbackVolume = 1.0;

    var videoBandwidth = null;

    this.setVideoBandwidth = function (bandwidthKbps) {
      videoBandwidth = bandwidthKbps;
    };

    /*function parseSDP(sdpString) {
     var root = {};
     root.tags={};
     var currentContainer = root;
     var lines = sdp.sdp.split("\r\n");
     for (var i in lines) {
     var line = lines[i];
     var a = line.split("=",2);
     if (a[0] == "m") {
     var newMedia = {};
     currentContainer = newMedia;
     newMedia.tags = {};
     cure
     }
     }


     }*/

    function addBandwidthParams(sdp) {
      if (videoBandwidth)
      //sdp.sdp = sdp.sdp.replace(/(m=video.*\r\n)/g, '$1b=AS:' + videoBandwidth + '\r\n');
        sdp.sdp = sdp.sdp.replace(/(a=mid:video.*\r\n)/g, '$1b=AS:' + videoBandwidth + '\r\n');
      return sdp;
    };

    var initPCMethods = function () {
      isChrome = (typeof(webkitRTCPeerConnection) != 'undefined');
      isFirefox = (typeof(mozRTCPeerConnection) != 'undefined');	  // 
      deviceEnumAPI = ((typeof MediaStreamTrack != "undefined") && (typeof MediaStreamTrack.getSources != "undefined")) ||
        (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices);
      audioSourceId = null;
      videoSourceId = null;
      videoConstraints = null;

      if (navigator.mediaDevices === undefined) {
        navigator.mediaDevices = {};
      }

      if (navigator.mediaDevices.getUserMedia === undefined) {
        navigator.mediaDevices.getUserMedia = promisifiedOldGUM;
      }

      if (isFirefox) {
        myRTCIceCandidate = mozRTCIceCandidate;
        RTCPeerConnection = mozRTCPeerConnection;
        getUserMedia = navigator.mozGetUserMedia.bind(navigator);
        attachMediaStream = function (element, stream) {
          if (stream) {
            element.mozSrcObject = stream;
            element.load();
            element.play();
          } else {
            element.mozSrcObject = null;
            element.load();
          }
        };
      }
      if (isChrome) {
        RTCPeerConnection = webkitRTCPeerConnection;
        myRTCIceCandidate = RTCIceCandidate;
        getUserMedia = navigator.webkitGetUserMedia.bind(navigator);
        attachMediaStream = function (element, stream) {
          if (stream) {
            element.src = URL.createObjectURL(stream);
            element.load();
            element.play();
          } else {
            element.pause();
            //delete element.src;

            //element.load();
          }
        };
      } else {
        // Temasys AdapterJS?
        if (typeof AdapterJS != "undefined") {
          RTCPeerConnection = window.RTCPeerConnection;
          myRTCIceCandidate = window.RTCIceCandidate;
          getUserMedia = navigator.getUserMedia.bind(navigator);
          attachMediaStream = window.attachMediaStream;
        }
      }
    }; // initPCMethods

    var doPreFlightCheck = false;

    var localStream = null;
    var calls = {};
    var peerConnections = {};

    var numCalls = 0;

    var log = function (message) {
      if (typeof this.writeLog == "function") {
        this.writeLog(message);
      }
    }.bind(this);

    var trace = function (message) {
      if (typeof this.writeTrace == "function") {
        this.writeTrace(message);
      }
    }.bind(this);

    var checkConnection = function (func) {
      if (state != VI_WEBRTC_STATE_CONNECTED) {
        log(func + " called while in state " + state);
        return false;
      } else return true;
    };

    var localVideoSink;

    var remoteSinksContainerId;

    //Callbacks start here
    var dateTimeOptions = {year: "numeric", month: "numeric", day: "numeric", timeZone: "UTC"};

    this.writeLog = function (message) {
      BXIM.webrtc.phoneLog("VI WebRTC: " + new Date().toLocaleTimeString("en-US", dateTimeOptions) + " " + message);
    };
    this.writeTrace = function (message) {
      BXIM.webrtc.phoneLog("VI WebRTC: " + new Date().toLocaleTimeString("en-US", dateTimeOptions) + " " + message);
    };

    //onConnectionEstablished(accessServer)
    this.onConnectionEstablished = null;
    //onConnectionFailed(reason)
    this.onConnectionFailed = null;
    //onConnectionClosed()
    this.onConnectionClosed = null;
    //onLoginSuccessful(displayName)
    this.onLoginSuccessful = null;
    //onLoginFailed(statusCode[, oneTimeKey])
    this.onLoginFailed = null;
    //onIncomingCall(callId, remoteUserName, remoteDisplayName, headers)
    this.onIncomingCall = null;
    //onCallRinging(callId)
    this.onCallRinging = null;
    //onCallRinging(callId)
    this.onCallMediaStarted = null;
    //onCallRinging(callId, headers)
    this.onCallConnected = null;
    //onCallEnded(callId, headers)
    this.onCallEnded = null;
    //onCallFailed(callId, statusCode, reason, headers)
    this.onCallFailed = null;
    //onSIPInfoReceived(callId, mediaType, mediaSubType, content, headers)
    this.onSIPInfoReceived = null;
    //onInstantMessageReceived(callId, msg)
    this.onInstantMessageReceived = null;
    //onTransferComplete(callId)
    this.onTransferComplete = null;
    //onTransferFailed(callId)
    this.onTransferFailed = null;
    //onNetStatsReceived(statsObject)
    this.onNetStatsReceived = null;
    //onRTCStatsCollected(callId, results)
    this.onRTCStatsCollected = null;
    /* Instant Messaging */
    //onHandleRoster(id, roster)
    this.onHandleRoster = null;
    //onHandleRosterPresence(id, resource, presence, msg)
    this.onHandleRosterPresence = null;
    //onHandleMessage(id, resource, msg, to)
    this.onHandleMessage = null;
    //onHandleSelfPresence(id, resource, presence, msg)
    this.onHandleSelfPresence = null;
    //onHandleChatState(id, resource, state)
    this.onHandleChatState = null;
    //onHandleMessageEvent(id, resource, e, mid)
    this.onHandleMessageEvent = null;
    //onHandleMessageRemoved(id, mid, to)
    this.onHandleMessageRemoved = null;
    //onHandleMessageModified(from, mid, msg, to)
    this.onHandleMessageModified = null;
    //onHandleMessageModificationError(id, mid, code)
    this.onHandleMessageModificationError = null;
    //onHandleSubscription(id, resource, e, msg)
    this.onHandleSubscription = null;
    //onHandleRosterItem(id, resource, e)
    this.onHandleRosterItem = null;
    //onCallRemoteFunctionError(method, params, code, description)
    this.onCallRemoteFunctionError = null;
    //onIMError(type, code, description)
    this.onIMError = null;
    //onIMRosterError(code)
    this.onIMRosterError = null;
    //onMUCError(room, operation, code, text)
    this.onMUCError = null;
    //onMUCRoomCreation(room)
    this.onMUCRoomCreation = null;
    //onMUCSubject(room, id, subject)
    this.onMUCSubject = null;
    //onMUCInfo(room, features, name, info)
    this.onMUCInfo = null;
    //onMUCMessage(room, priv, timestamp, from, msg)
    this.onMUCMessage = null;
    //onMUCInvitation(room, from, reason, body, password, cont, thread)
    this.onMUCInvitation = null;
    //onMUCInviteDecline(room, invitee, reason)
    this.onMUCInviteDecline = null;
    //onMUCParticipantPresence(room, participant, presence, msg)
    this.onMUCParticipantPresence = null;
    //onMUCNewParticipant(room, participant)
    this.onMUCNewParticipant = null;
    //onMUCParticipantExit(room, participant)
    this.onMUCParticipantExit = null;
    //onMUCOperationResult(room, operation, result)
    this.onMUCOperationResult = null;
    //onMUCRooms(rooms)
    this.onMUCRooms = null;
    //onMUCParticipants(room, list)
    this.onMUCParticipants = null;
    //onMUCBanList(room, list)
    this.onMUCBanList = null;
    //onMUCHistory(room, mid, list)
    this.onMUCHistory = null;
    //onMUCMessageModified(room, mid, msg)
    this.onMUCMessageModified = null;
    //onMUCMessageModificationError(room, priv, mid, code)
    this.onMUCMessageModificationError = null;
    //onMUCMessageRemoved(room, mid)
    this.onMUCMessageRemoved = null;
    //onMUCChatState(room, from, state)
    this.onMUCChatState = null;
    //onHistory(uri, mid, list)
    this.onHistory = null;
    //onUCConnected(id)
    this.onUCConnected = null;
    //onUCDisconnected()
    this.onUCDisconnected = null;

    //onVoicemail(promptURL)
    this.onVoicemail = null;
    //onCheckComplete(micStatus, netStatus, rtpPackets)
    this.onCheckComplete = null;

    this.hasLocalStream = false;
    //onStreenCaptureStarted (callId, elementId)
    this.onRemoteScreenCaptureStarted = null;
    //onCallICETimeout (callId)
    this.onCallICETimeout = null;

    var notifyRTCStatsCollected = function (callId, results) {
      if (typeof this.onRTCStatsCollected == "function")
        this.onRTCStatsCollected(callId, results);
    }.bind(this);

    var notifyScreenCaptureStarted = function (callId, elementId) {
      if (typeof this.onRemoteScreenCaptureStarted == "function")
        this.onRemoteScreenCaptureStarted(callId, elementId);
    }.bind(this);

    var _notifyCallICETimeout = function (callId) {
      if (typeof this.onCallICETimeout == "function") this.onCallICETimeout(callId);
    }.bind(this);

    //Callbacks end here
    var cleanHeaders = function (headers) {
      var res = {};
      for (var i in headers) {
        if (i.substring(0, 2) == "X-" || i == "VI-CallData") {
          res[i] = headers[i];
        }
      }
      return res;
    };

    var isDirectCall = function (headers) {
      for (var i in headers)
        if (i == "X-DirectCall")
          return (headers[i] == "true") ? true : false;
      return false;
    };

    var recalculateNumCalls = function () {
      numCalls = 0;
      for (var i in calls) {
        numCalls++;
      }
    };
    var addCall = function (call) {
      calls[call.id()] = call;
      recalculateNumCalls();
    };
    var deleteCall = function (callId) {
      delete calls[callId];
      recalculateNumCalls();
    }.bind(this);

    initPCMethods();

    var makeid = function (len) {
      var text = "";
      var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

      for (var i = 0; i < len; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));

      return text;
    };

    var traceState = function () {
      // trace("Calls: "+JSON.stringify(calls));
      // trace("PCs: "+JSON.stringify(peerConnections));
    };

    //Public functions begin
    this.muteMicrophone = function (doMute) {
      microphoneMuted = doMute;
      if (localStream) {
        //if (isFirefox) {
        enableTracks(getAudioTracks(localStream), !doMute);
        // } else {
        // 	for (var i in peerConnections) {
        // 		peerConnections[i].updateMicrophoneMuteState();
        // 	}
        // }

      }
    };
    this.sendVideo = function (doSend) {
      videoSent = doSend;
      if (localStream) {
        //if (isFirefox) {
        enableTracks(getVideoTracks(localStream), doSend);
        // } else {
        // 	for (var i in peerConnections) {
        // 		peerConnections[i].updateMicrophoneMuteState();
        // 	}
        // }

      }
    };
    this.mutePlayback = function (doMute) {
      speakerMuted = doMute;
      for (var i in peerConnections) {
        peerConnections[i].updateSpeakerMuteState();
      }
    };
    this.setPlaybackVolume = function (volume) {
      playbackVolume = volume;
      for (var i in peerConnections) {
        peerConnections[i].setPlaybackVolume(volume);
      }
    };
    this.getCalls = function () {
      var res = [];
      for (var i in calls) {
        res.push(i);
      }
      return res;
    };
    this.setLocalVideoSink = function (element) {
      localVideoSink = element;
      if (localStream) {
        attachMediaStream(element, localStream);
      }
    };
    this.setRemoteSinksContainerId = function (id) {
      remoteSinksContainerId = id;
    };
    this.stopLocalStream = function () {
      if (localStream) {
        if (localStream.active) {
          localStream.getTracks().forEach(function (track) {
            track.stop();
          });
        }
        localStream = null;
      }
    }.bind(this);
    this.destroy = function () {
      this.disconnect();
      this.stopLocalStream();

    }.bind(this);
    this.disconnect = function () {
      if (sock) {
        onWSClosed();
        sock.onclose = null;
        sock.close();
        cleanup();
      }
    };

    this.useAudioSource = function (id, successCallback, failedCallback) {
      if (deviceEnumAPI) {
        audioSourceId = id;
        this.requestMedia(videoSupport, successCallback, failedCallback, true);
      }
    }.bind(this);
    this.useVideoSource = function (id, successCallback, failedCallback) {
      if (deviceEnumAPI) {
        videoSourceId = id;
        this.requestMedia(true, successCallback, failedCallback, true);
      }
    }.bind(this);

    this.setConstraints = function (constraints, successCallback, failedCallback, apply) {
      videoConstraints = constraints;
      if (apply === true) this.requestMedia(videoSupport, successCallback, failedCallback, videoSupport);
    }.bind(this);

    this.requestMedia = function (video, onMediaAccessGranted, onMediaAccessRejected, stopStream) {
      var constraints,
          constraintName = (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices &&
          (typeof MediaStreamTrack == "undefined") && (typeof MediaStreamTrack.getSources == "undefined")) ? "deviceId" : "sourceId";

        if (audioSourceId === null && videoSourceId === null) {
        constraints = {audio: true, video: video === true ? true : false};
      } else {
        if (audioSourceId !== null && videoSourceId !== null) {
          if (constraintName == "sourceId") constraints = {
            audio: {optional: [{sourceId: audioSourceId}]},
            video: {optional: [{sourceId: videoSourceId}]}
          };
          else constraints = {
            audio: {
              deviceId: {ideal: audioSourceId}
            },
            video: {
              deviceId: {ideal: videoSourceId}
            }
          };
        } else if (audioSourceId !== null) {
          if (constraintName == "sourceId") constraints = {
            audio: {optional: [{sourceId: audioSourceId}]},
            video: video === true ? true : false
          };
          else constraints = {
            audio: {
              deviceId: {ideal: audioSourceId}
            },
            video: video === true ? true : false
          };
        } else if (videoSourceId !== null) {
          if (constraintName == "sourceId") constraints = {audio: true, video: {optional: [{sourceId: videoSourceId}]}};
          else constraints = {
            audio: true,
            video: {
              deviceId: {ideal: videoSourceId}
            }
          };
        }
      }
      if (videoConstraints !== null) {
        if (isChrome) {
          if (videoConstraints !== false) constraints.video = {};
          else constraints.video = false;
        } else constraints.video = videoConstraints;
        if (typeof videoConstraints.mandatory != "undefined") constraints.video.mandatory = videoConstraints.mandatory;
        if (typeof videoConstraints.optional != "undefined") {
          constraints.video.optional = videoConstraints.optional;
          if (videoSourceId !== null) {
            if (constraintName == "sourceId") constraints.video.mandatory.sourceId = videoSourceId;
            else constraints.video.deviceId = {ideal: videoSourceId};
          }
        } else {
          if (videoSourceId !== null) {
            if (constraintName == "sourceId") constraints.video.mandatory.sourceId = videoSourceId;
            else constraints.video.deviceId = {ideal: videoSourceId};
          }
        }

      }

      if (localStream && stopStream) {
        this.stopLocalStream();
      }

      // TODO: remove this hack when screensharing API becomes standard
      if (typeof constraints.video.mandatory != "undefined") {
        if (typeof constraints.video.mandatory.chromeMediaSourceId != "undefined") constraints.audio = false;
      }

      /*getUserMedia(constraints, function(_localStream) {
       this.gUM_success(_localStream, onMediaAccessGranted);
       }.bind(this), function(err) {
       this.gUM_error(err, onMediaAccessRejected);
       }.bind(this));*/
      navigator.mediaDevices.getUserMedia(constraints)
        .then(function (_localStream) {
          this.gUM_success(_localStream, onMediaAccessGranted);
        }.bind(this))
        .catch(function (err) {
          this.gUM_error(err, onMediaAccessRejected);
        }.bind(this));

    }.bind(this);

    this.gUM_error = function (err, onMediaAccessRejected) {
      log("Media access rejected: " + err.name);
      if (typeof onMediaAccessRejected == "function") {
        onMediaAccessRejected(err.name);
      }
    }.bind(this);

    this.gUM_success = function (_localStream, onMediaAccessGranted) {
      var i;
      if (localStream) {
        //localStream.stop();
        if (localStream.active) {
          localStream.getTracks().forEach(function (track) {
            track.stop();
          });
        }
        for (i in peerConnections) {
          peerConnections[i].setLocalStream(null);
        }
      }

      log("Media access granted");
      localStream = _localStream;
      this.hasLocalStream = true;

      enableTracks(getAudioTracks(localStream), !microphoneMuted);

      if (localVideoSink) {
        attachMediaStream(localVideoSink, _localStream);
      }
      for (i in peerConnections) {
        peerConnections[i].setLocalStream(localStream);
      }
      //Update mute state

      if (typeof onMediaAccessGranted == "function") {
        onMediaAccessGranted(localStream);
      }
      for (i in pcsWaitingForLocalStream) {
        doCreatePC(i, pcsWaitingForLocalStream[i]);
      }
      pcsWaitingForLocalStream = {};

    }.bind(this);

    this.shareScreen = function () {
      if (typeof getScreenId != "undefined") {
        getScreenId(function (error, sourceId, screen_constraints) {
          getUserMedia(screen_constraints,
            function (screenStream) {
              screenSharingStream = screenStream;
              for (var i in peerConnections) {
                if (peerConnections[i].isDirect())
                  peerConnections[i].addScreenSharing(screenSharingStream);
              }
            }, function (err) {
              log(err);
            });
        });
      } else {
        log("No screensharing extension is available");
      }
    };

    this.getRemoteParty = function (callId) {
      if (calls[callId]) {
        return calls[callId].getRemoteParty();
      }
      return null;
    };
    this.getCallState = function (callId) {
      if (calls[callId]) {
        return calls[callId].getState();
      }
      return null;
    };
    this.setCallActive = function (callId, active) {
      if (calls[callId]) {
        if (active) {
          this.unholdCall(callId);
        } else {
          this.holdCall(callId);
        }
        return calls[callId].setStreamsActive(active);
      }
    };
    this.isCallActive = function (callId) {
      if (calls[callId]) {
        return calls[callId].streamsAreActive();
      }

      return false;
    };

    this.connectTo = function (serverAddress, referrer, extra, appName, connectivityCheck) {

      if (state == VI_WEBRTC_STATE_IDLE) {
        try {
          var client = isFirefox ? "firefox" : "chrome";
          if (typeof connectivityCheck != "undefined" && connectivityCheck == false) client = "voxmobile";
          sock = new WebSocket(connectionProtocol + "://" + serverAddress + "/" + (appName || "platform") + "?version=2&client=" + client + "&referrer=" +
            encodeURIComponent(referrer) + "&extra=" + encodeURIComponent(extra) + "&video=" + (videoSupport === true ? "true" : "false") + "&q=" + makeid(12));
          state = VI_WEBRTC_STATE_WS_CONNECTING;
          sock.onopen = onWSConnected;
          sock.onclose = onWSClosed;
          sock.onerror = onWSError;
          sock.onmessage = onWSDataReceived;
        } catch (e) {
          log("WebSocket Error: " + e);
        }

      } else {
        log("Error: called connectTo while in state " + state);
      }

    }.bind(this);

    this.startPreFlightCheck = function (mic, net) {
      if (checkConnection("__startPreFlightCheck")) callRemoteFunction("__startPreFlightCheck", [!!mic, !!net]);
    };

    //options - Object
    //Possible values: 
    //receiveCalls - boolean, optional, default - true
    //getWebphoneConfig - boolean, optional, default - false

    this.login = function (username, password, options) {
      if (checkConnection("login")) callRemoteFunction("login", [username, password, options ? options : null]);
    };

    this.loginStage2 = function (username, code, options) {
      if (checkConnection("loginStage2")) callRemoteFunction("loginStage2", [username, code, options ? options : null]);
    };

    this.loginGenerateOneTimeKey = function (username) {
      if (checkConnection("loginGenerateOneTimeKey")) callRemoteFunction("loginGenerateOneTimeKey", [username]);
    };

    this.loginUsingOneTimeKey = function (username, hash, options) {
      if (checkConnection("loginUsingOneTimeKey")) callRemoteFunction("loginUsingOneTimeKey", [username, hash, options ? options : null]);
    };

    this.setOperatorACDStatus = function (s) {
      if (checkConnection("setOperatorACDStatus")) callRemoteFunction("setOperatorACDStatus", [s]);
    };

    this.callTo = function (destination, useVideo, headers, extraParams) {
      var id = makeid(36),
        wiredRemote = !(typeof extraParams != "undefined" && extraParams["wiredRemote"] === false),
        headers = cleanHeaders(typeof headers == "undefined" ? {} : headers);

      var call = new Call(id, VI_CALL_STATE_PROGRESSING, destination, "");
      call.setHeaders(headers);
      addCall(call);

      if (isDirectCall(headers)) {
        var pcw = createPeerConnection2(id, true, wiredRemote);
        pcw.outgoingCall();
      } else {
        callRemoteFunction("createCall", [-1, destination, useVideo, id, null, null, headers, extraParams]);
      }
      return id;
    };

    this.transferCall = function (call1, call2) {
      var x = [call1, call2];
      for (var i = 0; i < x.length; i++) {
        var call = calls[x[i]];
        if (call) {
          if (call.getState() != VI_CALL_STATE_CONNECTED) {
            log("ERROR: trying to transfer call " + call.id() + " in state " + call.getState());
            return;
          }
        } else {
          log("ERROR: trying to transfer unknown call " + call.id());
          return;
        }
      }
      callRemoteFunction("transferCall", [call1, call2]);

    };

    this.hangupCall = function (callId, headers) {
      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_ALERTING) {
          callRemoteFunction("rejectCall", [callId, true, cleanHeaders(headers)]);
        } else {
          callRemoteFunction("disconnectCall", [callId, cleanHeaders(headers)]);
        }
      } else {
        log("ERROR: trying to hangup unknown call " + callId);
      }
    };

    this.rejectCall = function (callId, code, headers) {
      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_ALERTING) {
          callRemoteFunction("rejectCall", [callId, true, cleanHeaders(headers)]);
        } else {
          log("ERROR: trying to reject call " + callId + " in state " + call.getState());
        }
      } else {
        log("ERROR: trying to reject unknown call " + callId);
      }
    };

    this.answerCall = function (callId, headers) {

      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_ALERTING) {
          if (isDirectCall(call.getHeaders())) {
            var pc = call.getPeerConnection();
            pc.createAnswer();
          } else {
            callRemoteFunction("acceptCall", [callId, cleanHeaders(headers)]);
          }
        } else {
          log("ERROR: trying to answer call " + callId + " in state " + call.getState());
        }
      } else {
        log("ERROR: trying to answer unknown call " + callId);
      }
    };

    this.sendDigit = function (callId, digit) {
      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_CONNECTED) {
          //Try send DTMF native
          var pc = call.getPeerConnection().getRTCPeerConnection();
          if(pc.createDTMFSender) {
            var dtmfSender = pc.createDTMFSender(localStream.getAudioTracks()[0]);
            var duration = 500;
            var gap = 50;
            //reset digit back to strings
            if (digit == 10) digit = '*';
            else if (digit == 11) digit = '#';
            if (dtmfSender.canInsertDTMF) {
              dtmfSender.insertDTMF(digit, duration, gap);
              return;
            }
          }
          callRemoteFunction("sendDTMF", [callId, digit]);
        } else {
          log("ERROR: trying to send digit to call " + callId + " in state " + call.getState());
        }
      } else {
        log("ERROR: trying to send digit to unknown call " + callId);
      }
    };

    this.holdCall = function (callId) {
      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_CONNECTED) {
          callRemoteFunction("hold", [callId]);
        } else {
          log("ERROR: trying to hold call " + callId + " in state " + call.getState());
        }
      } else {
        log("ERROR: trying to hold unknown call " + callId);
      }
    };

    this.unholdCall = function (callId) {
      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_CONNECTED) {
          callRemoteFunction("unhold", [callId]);
        } else {
          log("ERROR: trying to unhold call " + callId + " in state " + call.getState());
        }
      } else {
        log("ERROR: trying to unhold unknown call " + callId);
      }
    };

    this.voicemailPromptFinished = function (callId) {
      var call = calls[callId];
      if (call) {
        callRemoteFunction("promptFinished", [callId]);
      } else {
        log("ERROR: trying to record voicemail for unknown call " + callId);
      }
    };

    this.sendSIPInfo = function (callId, type, subtype, body, headers) {
      var call = calls[callId];
      if (call) {
        if (call.getState() == VI_CALL_STATE_CONNECTED || call.getState() == VI_CALL_STATE_ALERTING || call.getState() == VI_CALL_STATE_PROGRESSING) {
          callRemoteFunction("sendSIPInfo", [callId, type, subtype, body, cleanHeaders(headers)]);
        } else {
          log("ERROR: trying to send SIP Info to call " + callId + " in state " + call.getState());
        }
      } else {
        log("ERROR: trying to send SIP Info to unknown call " + callId);
      }
    };

    var __sendSIPInfo = this.sendSIPInfo.bind(this);

    this.setDesiredVideoBandwidth = function (bandwidthKbps) {
      if (checkConnection("setDesiredVideoBandwidth")) callRemoteFunction("setDesiredVideoBandwidth", [bandwidthKbps]);
    };

    this.sendInstantMessage = function (callId, text) {
      this.sendSIPInfo(callId, "application", "zingaya-im", text, {});
    };

    this.sendTextMessage = function (uri, text, id) {
      if (checkConnection("sendMessage")) callRemoteFunction("sendMessage", [uri, text, id]);
    };

    this.editTextMessage = function (uri, mid, msg) {
      if (checkConnection("editMessage")) callRemoteFunction("editMessage", [uri, mid, msg]);
    };

    this.removeTextMessage = function (uri, mid) {
      if (checkConnection("removeMessage")) callRemoteFunction("removeMessage", [uri, mid]);
    };

    this.sendStatus = function (status, msg) {
      if (checkConnection("sendStatus")) callRemoteFunction("sendStatus", [status, msg]);
    };

    this.sendChatState = function (uri, status) {
      if (checkConnection("sendChatState")) callRemoteFunction("sendChatState", [uri, status]);
    };

    this.raiseMessageEvent = function (uri, type, msg_id) {
      if (checkConnection("raiseMessageEvent")) callRemoteFunction("raiseMessageEvent", [uri, type, msg_id]);
    };

    this.addRoster = function (uri, name, group, msg) {
      if (checkConnection("addRoster")) callRemoteFunction("addRoster", [uri, name, group, msg]);
    };

    this.addRosterItemGroup = function (uri, group) {
      if (checkConnection("addRosterItemGroup")) callRemoteFunction("addRosterItemGroup", [uri, group]);
    };

    this.delRosterItemGroup = function (uri, group) {
      if (checkConnection("delRosterItemGroup")) callRemoteFunction("delRosterItemGroup", [uri, group]);
    };

    this.moveRosterItemGroup = function (uri, groupSrc, groupDst) {
      if (checkConnection("moveRosterItemGroup")) callRemoteFunction("moveRosterItemGroup", [uri, groupSrc, groupDst]);
    };

    this.renameRosterItem = function (uri, name) {
      if (checkConnection("renameRosterItem")) callRemoteFunction("renameRosterItem", [uri, name]);
    };

    this.removeRoster = function (uri) {
      if (checkConnection("removeRoster")) callRemoteFunction("removeRoster", [uri]);
    };

    this.replySubscriptionRequest = function (uri, accept) {
      if (checkConnection("replySubscriptionRequest")) callRemoteFunction("replySubscriptionRequest", [uri, accept]);
    };

    this.joinMUC = function (room, pass, users) {
      if (checkConnection("joinMUC")) callRemoteFunction("joinMUC", [room, pass, users]);
    };

    this.leaveMUC = function (room, msg) {
      if (checkConnection("leaveMUC")) callRemoteFunction("leaveMUC", [room, msg]);
    };

    this.sendMUCMessage = function (room, msg, mid) {
      if (checkConnection("sendMUCMessage")) callRemoteFunction("sendMUCMessage", [room, msg, mid]);
    };

    this.editMUCMessage = function (room, mid, msg) {
      if (checkConnection("editMUCMessage")) callRemoteFunction("editMUCMessage", [room, mid, msg]);
    };

    this.removeMUCMessage = function (room, mid) {
      if (checkConnection("removeMUCMessage")) callRemoteFunction("removeMUCMessage", [room, mid]);
    };

    this.inviteMUC = function (room, to, reason) {
      if (checkConnection("inviteMUC")) callRemoteFunction("inviteMUC", [room, to, reason]);
    };

    this.declineMUCinvitation = function (room, inviter, reason) {
      if (checkConnection("declineMUCinvitation")) callRemoteFunction("declineMUCinvitation", [room, inviter, reason]);
    };

    this.ucReconnect = function () {
      if (checkConnection("ucReconnect")) callRemoteFunction("ucReconnect", []);
    };

    this.requestHistory = function (uri, mid, direction, count) {
      if (typeof direction == "undefined") direction = false;
      if (typeof count == "undefined") count = 100;
      if (typeof mid == "undefined") mid = "";
      if (checkConnection("requestHistory")) callRemoteFunction("requestHistory", [uri, mid, direction, count]);
    };

    this.requestMUCHistory = function (room, mid, direction, count) {
      if (typeof direction == "undefined") direction = false;
      if (typeof count == "undefined") count = 100;
      if (typeof mid == "undefined") mid = "";
      if (checkConnection("requestMUCHistory")) callRemoteFunction("requestMUCHistory", [room, mid, direction, count]);
    };

    this.setSubject = function (room, subject) {
      if (checkConnection("setSubject")) callRemoteFunction("setSubject", [room, subject]);
    };

    this.sendMUCChatState = function (room, status) {
      if (checkConnection("sendMUCChatState")) callRemoteFunction("sendMUCChatState", [room, status]);
    };

    this.kickMUCUser = function (room, uri, reason) {
      if (checkConnection("kickMUCUser")) callRemoteFunction("kickMUCUser", [room, uri, reason]);
    };

    this.banMUCUser = function (room, uri, reason) {
      if (checkConnection("banMUCUser")) callRemoteFunction("banMUCUser", [room, uri, reason]);
    };

    this.unbanMUCUser = function (room, uri, reason) {
      if (checkConnection("unbanMUCUser")) callRemoteFunction("unbanMUCUser", [room, uri, reason]);
    };

    this.getVideoElementId = function (callId) {
      var call = calls[callId];
      if (call) {
        return call.getVideoElementId();
      } else {
        log("ERROR: No such call " + callId);
      }
    };

    this.getAudioElementId = function (callId) {
      var call = calls[callId];
      if (call) {
        return call.getAudioElementId();
      } else {
        log("ERROR: No such call " + callId);
      }
    };

    this.getStats = function (callId, callback) {
      var pc = peerConnections[callId];
      if (pc) {

        pc.getStats(callback);
      }

    };

    this.getPeerConnection = function (callId) {
      return peerConnections[callId];
    };

    //Public functions end
    var cleanup = function () {
      for (var i in peerConnections) {
        peerConnections[i].close();
      }
      peerConnections = {};

      calls = {};
      numCalls = 0;
      if (pingTimer) {
        clearTimeout(pingTimer);
      }
      if (pongTimer) {
        clearTimeout(pongTimer);
      }
    };

    var onWSError = function (e) {
      sock.onclose = null;
      log("WS error: " + e);
      var closed = (state == VI_WEBRTC_STATE_CONNECTED);
      var cb = closed ? this.onConnectionClosed : this.onConnectionFailed;
      state = VI_WEBRTC_STATE_IDLE;
      cleanup();
      if (typeof cb == "function") {
        cb(e);
      }
    }.bind(this);

    var onWSConnected = function () {
      state = VI_WEBRTC_STATE_WS_CONNECTED;
      log("WS connected");
    }.bind(this);

    var onWSClosed = function () {
      log("WS closed");
      var closed = (state == VI_WEBRTC_STATE_CONNECTED);
      var cb = closed ? this.onConnectionClosed : this.onConnectionFailed;
      state = VI_WEBRTC_STATE_IDLE;
      cleanup();
      if (typeof cb == "function") {
        cb("Connection closed");
      }
    }.bind(this);

    function pongReceived() {
      if (pongTimer) {
        clearTimeout(pongTimer);
        pongTimer = null;
        pingTimer = setTimeout(doPing, PING_DELAY);
      }
    }

    var doPing = function () {

      pingTimer = null;
      callRemoteFunction("__ping", []);
      pongTimer = setTimeout(function () {

        if (numCalls > 0) {
          //Ignore pong timeouts in case of active call
          pongReceived();
          return;
        }

        pongTimer = null;
        log("WS closed");

        var closed = (state == VI_WEBRTC_STATE_CONNECTED);
        var cb = closed ? this.onConnectionClosed : this.onConnectionFailed;
        state = VI_WEBRTC_STATE_IDLE;
        cleanup();
        if (typeof cb == "function") {
          cb("Connection closed");
        }
      }.bind(this), PING_DELAY);
    }.bind(this);

    var pcsWaitingForLocalStream = {};

    var doCreatePC = function (id, sdp) {
      var pcw = createPeerConnection2(id, false);
      pcw.start(sdp);
    };

    var createPeerConnection2 = function (id, direct, wiredRemote) {
      BXIM.webrtc.phoneLog("createPeerConnection2 invoked");
      if (peerConnections[id]) {
        peerConnections[id].close();
      }

      var pcw = new PeerConnectionWrapper2(id, direct, wiredRemote);
      if (calls[id]) {
        pcw.setStreamsActive(calls[id].streamsAreActive());
      }
      peerConnections[id] = pcw;
      pcw.setLocalStream(localStream);
      if (screenSharingStream) {
        pcw.setScreenSharingStream(screenSharingStream);
      }
      var call = calls[id];
      if (call) {
        calls[id].setPeerConnection(pcw);
        pcw.setCall(call);
      }

      return peerConnections[id];
    };

    var ice_config = null;


    var rpcHandlers;
    rpcHandlers = {
      "__createConnection": function () {
        if (state == VI_WEBRTC_STATE_WS_CONNECTED) {
          state = VI_WEBRTC_STATE_CONNECTED;
          if (typeof this.onConnectionEstablished == "function") {
            this.onConnectionEstablished(false);
          }

          pingTimer = setTimeout(doPing, PING_DELAY);
        }
      },
      "__createPC": function (id, sdpOffer) {
        if (!needMic || localStream || id == "__default") {
          doCreatePC(id, sdpOffer);
        } else {
          pcsWaitingForLocalStream[id] = sdpOffer;
        }
      },
      "__destroyPC": function (id) {
        if (peerConnections[id]) {
          peerConnections[id].close();

          delete peerConnections[id];
        }

        delete pcsWaitingForLocalStream[id];
      },
      "__onPCStats": function (id, stats) {
        if (peerConnections[id]) {
          if (typeof this.onNetStatsReceived == "function") {
            this.onNetStatsReceived(stats);
          }
        }
      },
      "__pong": function () {
        pongReceived();
      },
      "__connectionSuccessful": function () {
        if (state == VI_WEBRTC_STATE_WS_CONNECTED) {
          state = VI_WEBRTC_STATE_CONNECTED;
          if (typeof this.onConnectionEstablished == "function") {
            this.onConnectionEstablished(true);
          }

          pingTimer = setTimeout(doPing, PING_DELAY);
        }
      },
      "loginSuccessful": function (displayName, params) {

        if (params) {
            ice_config = params.iceConfig;
        }
        if (typeof this.onLoginSuccessful == "function") {
          this.onLoginSuccessful(displayName, params);
        }
      },
      "loginFailed": function (code, oneTimeKey) {
        if (typeof this.onLoginFailed == "function") {
          this.onLoginFailed({errorCode: code, oneTimeKey: oneTimeKey});
        }
      },
      "handleConnectionConnected": function (callId, headers, sdp) {
        var call = calls[callId];
        if (call) {
          call.canStartSendingCandidates();
          if (call.getState() == VI_CALL_STATE_PROGRESSING || call.getState() == VI_CALL_STATE_ALERTING) {
            call.setState(VI_CALL_STATE_CONNECTED);
            if (typeof sdp == 'undefined') {
            }
            else {
              var pc = call.getPeerConnection();
              pc.onConnectionConnected(sdp);
            }

            if (typeof this.onCallConnected == "function") {
              this.onCallConnected(callId, headers);
            }
          } else {
            log("WARNING: received handleConnectionConnected for call: " + callId + " in invalid state: " + call.getState());
          }
        } else {
          log("WARNING: received handleConnectionConnected for unknown call: " + callId);
        }

      },
      "stopRinging": function (callId) {
        var call = calls[callId];
        if (call) {
          call.canStartSendingCandidates();
          if (typeof this.onCallMediaStarted == "function") {
            this.onCallMediaStarted(callId);
          }
        } else {
          log("WARNING: received stopRinging for unknown call: " + callId);
        }
      },
      "handleConnectionDisconnected": function (callId, headers, params) {
        var call = calls[callId];
        if (call) {

          call.setState(VI_CALL_STATE_ENDED);

          if (typeof this.onCallEnded == "function") {
            this.onCallEnded(callId, headers, params);
          } 
          deleteCall(callId);

        } else {
          log("WARNING: received handleConnectionDisconnected for unknown call: " + callId);
        }
      },
      "handleConnectionFailed": function (callId, code, reason, headers) {
        var call = calls[callId];
        if (call) {
          if (call.getState() == VI_CALL_STATE_PROGRESSING) {
            call.setState(VI_CALL_STATE_ENDED);


            if (typeof this.onCallFailed == "function") {
              this.onCallFailed(callId, code, reason, headers);
            }

            deleteCall(callId);


          } else {
            log("WARNING: received handleConnectionFailed for call: " + callId + " in invalid state: " + call.getState());
          }
        } else {
          log("WARNING: received handleConnectionFailed for unknown call: " + callId);
        }
      },
      "handleRingOut": function (callId) {
        var call = calls[callId];
        if (call) {
          call.canStartSendingCandidates();
          if (call.getState() == VI_CALL_STATE_PROGRESSING) {

            if (typeof this.onCallRinging == "function") {
              this.onCallRinging(callId);
            }
          } else {
            log("WARNING: received handleRingOut for call: " + callId + " in invalid state: " + call.getState());
          }
        } else {
          log("WARNING: received handleRingOut for unknown call: " + callId);
        }
      },
      "handleIncomingConnection": function (id, callerid, displayName, headers, sdp) {

        var call = new Call(id, VI_CALL_STATE_ALERTING, callerid, displayName);
        call.setHeaders(headers);
        addCall(call);

        if (isDirectCall(headers)) {
          var pc = createPeerConnection2(id, true);
          pc.onIncomingCall(sdp);
        }
        else {
          if (peerConnections[id])
            call.setPeerConnection(peerConnections[id]);
        }

        if (typeof this.onIncomingCall == "function") {
          this.onIncomingCall(id, callerid, displayName, headers);
        } else {
          this.rejectCall(id, 486);
          log("WARNING: Received incoming call while no handler was specified");
        }

      },
      "handleSIPInfo": function (callId, type, subType, body, headers) {
        var call = calls[callId];
        if (call) {
          if (call.getState() == VI_CALL_STATE_CONNECTED || call.getState() == VI_CALL_STATE_PROGRESSING || call.getState() == VI_CALL_STATE_ALERTING) {

            if (type == "application" && subType == "zingaya-im") {
              if (typeof this.onInstantMessageReceived == "function") {
                this.onInstantMessageReceived(callId, body);
              }
            } else if (type == "voximplant" && subType == "sdpfrag") {
              cands = JSON.parse(body);
              var pc = call.getPeerConnection();
              for (var i in cands) {
                pc.addRemoteCandidate(cands[i][0], cands[i][1]);
              }
            } else if (type == "vi" && subType == "sdpoffer") {
              var pc = call.getPeerConnection();
              pc.setRemoteSDP(true, body, headers["X-ScreenStreamId"]);
            } else if (type == "vi" && subType == "sdpanswer") {
              var pc = call.getPeerConnection();
              pc.setRemoteSDP(false, body, headers["X-ScreenStreamId"]);
            } else {
              if (typeof this.onSIPInfoReceived == "function") {
                this.onSIPInfoReceived(callId, type + '/' + subType, body, headers);
              }
            }
          } else {
            log("WARNING: received handleSIPInfo for call: " + callId + " in invalid state: " + call.getState());
          }
        } else {
          log("WARNING: received handleSIPInfo for unknown call: " + callId);
        }
      },
      "handleSipEvent": function () {
      },
      "handleTransferStarted": function () {
      },
      "handleTransferComplete": function (callId) {
        var call = calls[callId];
        if (call) {
          if (this.onTransferComplete) {
            this.onTransferComplete(callId);
          }
        } else {
          log("WARNING: received handleTransferComplete for unknown call: " + callId);
        }
      },
      "handleTransferFailed": function (callId) {
        var call = calls[callId];
        if (call) {
          if (this.onTransferFailed) {
            this.onTransferFailed(callId);
          }
        } else {
          log("WARNING: received handleTransferFailed for unknown call: " + callId);
        }
      },
      "handleRoster": function (roster) {
        trace("handleRoster");
        if (typeof this.onHandleRoster == "function") {
          this.onHandleRoster(roster);
        }
      },
      "handleRosterItem": function (id, resource, e, displayName, groups) {
        trace("handleRosterItem id " + id + " resource " + resource + " e " + e + " displayName " + displayName + " groups " + groups);
        if (typeof this.onHandleRosterItem == "function") {
          this.onHandleRosterItem(id, resource, e, displayName, groups);
        }
      },
      "handleRosterPresence": function (id, resource, presence, msg) {
        trace("handleRosterPresence");
        if (typeof this.onHandleRosterPresence == "function") {
          this.onHandleRosterPresence(id, resource, presence, msg);
        }
      },
      "handleMessage": function (id, resource, msg, mid, to) {
        trace("handleMessage with id " + mid + " from " + id + " to " + to + ": " + msg);
        if (typeof this.onHandleMessage == "function") {
          this.onHandleMessage(id, resource, msg, mid, to);
        }
      },
      "handleSelfPresence": function (id, resource, presence, msg) {
        trace("handleSelfPresence from " + id + ": " + presence);
        if (typeof this.onHandleSelfPresence == "function") {
          this.onHandleSelfPresence(id, resource, presence, msg);
        }
      },
      "handleChatState": function (id, resource, state) {
        trace("handleChatState from " + id + ": " + state);
        if (typeof this.onHandleChatState == "function") {
          this.onHandleChatState(id, resource, state);
        }
      },
      "handleMessageEvent": function (id, resource, e, mid) {
        trace("handleMessageEvent from " + id + ": " + e);
        if (typeof this.onHandleMessageEvent == "function") {
          this.onHandleMessageEvent(id, resource, e, mid);
        }
      },
      "handleMessageModified": function (id, mid, msg, to) {
        trace("handleMessageModified message id " + mid + " by " + id + " in chat with " + to + " msg " + msg);
        if (typeof this.onHandleMessageModified == "function") {
          this.onHandleMessageModified(id, mid, msg, to);
        }
      },
      "handleMessageModifyError": function (id, mid, code) {
        trace("handleMessageModifyError message id " + mid + " by " + id + " with code " + code);
        if (typeof this.onHandleMessageModificationError == "function") {
          this.onHandleMessageModificationError(id, mid, code);
        }
      },
      "handleMessageRemoved": function (id, mid, to) {
        trace("handleMessageRemoved message id " + mid + " by " + id + " in chat with " + to);
        if (typeof this.onHandleMessageRemoved == "function") {
          this.onHandleMessageRemoved(id, mid, to);
        }
      },
      "handleSubscription": function (id, resource, e, msg) {
        trace("handleSubscription from " + id + ": " + e);
        if (typeof this.onHandleSubscription == "function") {
          this.onHandleSubscription(id, resource, e, msg);
        }
      },
      "onCallRemoteFunctionError": function (method, params, code, description) {
        trace("onCallRemoteFunctionError method " + method + " params " + params + " code " + code + " description " + description);
        if (typeof this.onCallRemoteFunctionError == "function") {
          this.onCallRemoteFunctionError(method, params, code, description);
        }
      },
      "handleError": function (type, code, description) {
        trace("handleError type " + type + " code " + code + " description " + description);
        if (typeof this.onIMError == "function") this.onIMError(type, code, description);
      },
      "handleUCConnected": function (id) {
        trace("handleUCConnected id " + id);
        if (typeof this.onUCConnected == "function") this.onUCConnected(id);
      },
      "handleUCDisconnected": function () {
        trace("handleUCDisconnected");
        if (typeof this.onUCDisconnected == "function") this.onUCDisconnected();
      },
      "handleRosterError": function (code) {
        trace("handleRosterError code " + code);
        if (typeof this.onIMRosterError == "function") this.onIMRosterError(code);
      },
      "handleMUCError": function (room, operation, code, text) {
        trace("handleMUCError room " + room + " operation " + operation + " code " + code + " text " + text);
        if (typeof this.onMUCError == "function") this.onMUCError(room, operation, code, text);
      },
      "handleMUCRoomCreation": function (room) {
        trace("handleMUCRoomCreation room " + room);
        if (typeof this.onMUCRoomCreation == "function") this.onMUCRoomCreation(room);
      },
      "handleMUCSubject": function (room, id, resource, subject) {
        trace("handleMUCSubject room " + room + " id " + id + " resource " + resource + " subject " + subject);
        if (typeof this.onMUCSubject == "function") this.onMUCSubject(room, id, resource, subject);
      },
      "handleMUCInfo": function (room, features, name, info) {
        trace("handleMUCInfo room " + room + " features " + features + " name " + name + " info " + info);
        if (typeof this.onMUCInfo == "function") this.onMUCInfo(room, features, name, info);
      },
      "handleMUCMessage": function (room, priv, mid, timestamp, from, resource, msg) {
        trace("handleMUCMessage room " + room +
          " message_id " + mid +
          " private " + priv +
          " timestamp " + timestamp +
          " from " + from +
          " resource " + resource +
          " msg " + msg);
        if (typeof this.onMUCMessage == "function") this.onMUCMessage(room, mid, priv, timestamp, from, resource, msg);
      },
      "handleMUCInvitation": function (room, from, reason, body, password, cont) {
        trace("handleMUCInvitation room " + room + " from " + from + " reason " + reason + " body " + body + " password " + password + " cont " + cont);
        if (typeof this.onMUCInvitation == "function") this.onMUCInvitation(room, from, reason, body, password, cont);
      },
      "handleMUCInviteDecline": function (room, invitee, reason) {
        trace("handleMUCInviteDecline room " + room + " invitee " + invitee + " reason " + reason);
        if (typeof this.onMUCInviteDecline == "function") this.onMUCInviteDecline(room, invitee, reason);
      },
      "handleMUCParticipantPresence": function (room, participant, presence, msg) {
        trace("handleMUCParticipantPresence room " + room + " participant " + participant + " presence " + presence + " msg " + msg);
        if (typeof this.onMUCParticipantPresence == "function") this.onMUCParticipantPresence(room, participant, presence, msg);
      },
      "handleMUCParticipantJoin": function (room, participant, displayName) {
        trace("handleMUCParticipantJoin room " + room + " participant " + participant + " displayName " + displayName);
        if (typeof this.onMUCNewParticipant == "function") this.onMUCNewParticipant(room, participant, displayName);
      },
      "handleMUCParticipantLeft": function (room, participant) {
        trace("handleMUCParticipantLeft room " + room + " participant " + participant);
        if (typeof this.onMUCParticipantExit == "function") this.onMUCParticipantExit(room, participant);
      },
      "handleMUCRooms": function (rooms) {
        trace("handleMUCRooms rooms " + rooms);
        if (typeof this.onMUCRooms == "function") this.onMUCRooms(rooms);
      },
      "handleMUCParticipants": function (room, list) {
        trace("handleMUCParticipants room " + room + " list " + list);
        if (typeof this.onMUCParticipants == "function") this.onMUCParticipants(room, list);
      },
      "handleMUCBanList": function (room, list) {
        trace("handleMUCBanList room " + room + " list " + list);
        if (typeof this.onMUCBanList == "function") this.onMUCBanList(room, list);
      },
      "handleMUCOperationResult": function (room, operation, result) {
        trace("handleMUCOperationResult room " + room + " operation " + operation + " result " + result);
        if (typeof this.onMUCOperationResult == "function") this.onMUCOperationResult(room, operation, result);
      },
      "handleMUCHistory": function (room, mid, list) {
        trace("handleMUCHistory room " + room + " mid " + mid + " list " + list);
        if (typeof this.onMUCHistory == "function") this.onMUCHistory(room, mid, list);
      },
      "handleMUCMessageModified": function (room, priv, mid, timestamp, from, resource, msg) {
        trace("handleMUCMessageModified room " + room +
          " priv " + priv +
          " mid " + mid +
          " timestamp " + timestamp +
          " from " + from +
          " resource " + resource +
          " msg " + msg);
        if (typeof this.onMUCMessageModified == "function") this.onMUCMessageModified(room, priv, mid, timestamp, from, resource, msg);
      },
      "handleMUCMessageModifyError": function (room, priv, mid, code) {
        trace("handleMUCMessageModifyError room " + room + " priv " + priv + " mid " + mid + " with code " + code);
        if (typeof this.onMUCMessageModificationError == "function") this.onMUCMessageModificationError(room, priv, mid, code);
      },
      "handleMUCMessageRemoved": function (room, priv, mid, timestamp, from, resource) {
        trace("handleMUCMessageRemoved room " + room + " priv " + priv + " mid " + mid + " timestamp " + timestamp + " from " + from + " resource " + resource);
        if (typeof this.onMUCMessageRemoved == "function") this.onMUCMessageRemoved(room, priv, mid, timestamp, from, resource);
      },
      "handleMUCChatState": function (room, from, resource, state) {
        trace("handleMUCChatState room " + room + " from " + from + " resource " + resource + " state " + state);
        if (typeof this.onMUCChatState == "function") this.onMUCChatState(room, from, resource, state);
      },
      "handleHistory": function (uri, mid, list) {
        trace("handleHistory uri " + uri + " mid " + mid + " list " + list);
        if (typeof this.onHistory == "function") this.onHistory(uri, mid, list);
      },
      "handlePreFlightCheckResult": function (mic, net, rtp) {
        if (this.onCheckComplete)
          this.onCheckComplete(mic, net, rtp);
      },
      "handleVoicemail": function (propmptURL) {
        if (this.onVoicemail)
          this.onVoicemail(propmptURL);
      },
      "__connectionFailed": function () {
        if (state != VI_WEBRTC_STATE_IDLE) {
          this.disconnect();
        }
      }
    };

    var onWSDataReceived = function (data) {
      var receivedObject = JSON.parse(data.data);
      var functionName = receivedObject.name;
      var params = receivedObject.params;

      trace("Called local function " + functionName + " with params " + JSON.stringify(params));

      if (typeof rpcHandlers[functionName] == "function") {
        rpcHandlers[functionName].apply(this, params);
      }
      else {
        log("Unknown function called: " + functionName);
      }

      traceState();

    }.bind(this); // onWSDataReceived

    var createSDP = function (type, sdpString) {
      if (typeof mozRTCSessionDescription == "function")
        return new mozRTCSessionDescription({"type": type, "sdp": sdpString});
      else
        return new RTCSessionDescription({"type": type, "sdp": sdpString});
    }; // createSDP

    var Call = function (id, state, remoteParty, displayName, wired) {

      var _id = id;
      var _state = state;
      var _remoteParty = remoteParty;
      var _displayName = displayName;
      var _pc;
      var _headers;
      var _wired = wired;

      var streamsActive = true;

      this.id = function () {
        return _id;
      };

      this.getRemoteParty = function () {
        return _remoteParty;
      };

      this.getState = function () {
        return _state;
      };

      this.setState = function (state) {
        _state = state;
      };

      this.getDisplayName = function () {
        return _displayName;
      };

      this.wired = function () {
        return _wired;
      }

      this.setPeerConnection = function (pc) {
        BXIM.webrtc.phoneLog("Set peer connection: " + pc);
        _pc = pc;
      };

      this.getPeerConnection = function () {
        BXIM.webrtc.phoneLog("Get peer connection: " + _pc);
        return _pc;
      };

      this.setHeaders = function (headers) {
        _headers = headers;
      };

      this.getHeaders = function () {
        return _headers;
      };

      this.streamsAreActive = function () {
        return streamsActive;
      };

      this.setStreamsActive = function (b) {
        streamsActive = b;
        if (peerConnections[_id]) {
          peerConnections[_id].setStreamsActive(b);
        }
      };

      this.getVideoElementId = function () {
        return _pc.getVideoElementId();
      };

      this.getAudioElementId = function () {
        return _pc.getAudioElementId();
      };

      this.canStartSendingCandidates = function () {
        _pc.canStartSendingCandidates();

      };

      this.notifyICETimeout = function () {
        _notifyCallICETimeout(_id);
      };

    }; // Call


    var PeerConnectionWrapper2 = function (id, direct, wiredRemote) {

      //Timer to check for ICE timeout (in case of network failure or https://code.google.com/p/webrtc/issues/detail?id=4710)
      var iceTimer = null;
      var renegotiationInProgress = false;
      var htmlmedia = (wiredRemote === false ? false : true);

      var cfg = {gatherPolicy: "all", iceServers: [], rtcpMuxPolicy: "negotiate"};
      if (direct){
          cfg = ice_config;
          cfg.rtcpMuxPolicy = "negotiate";
      }


      var pc = new RTCPeerConnection(cfg, {'optional': [{'DtlsSrtpKeyAgreement': 'true'}]});
      if (isFirefox) {
        pc.removeStream = function (rtpSenders) {
          rtpSenders.forEach(function (sender) {
            pc.removeTrack(sender);
          });
        }
      }

      var _id = id;
      var _direct = direct;

      this.isDirect = function () {
        return _direct;
      };

      var pcLog = function (message) {
        log("PC [" + _id + "]: " + message);
      };
      var pcTrace = function (message) {
        trace("PC [" + _id + "]: " + message);
      };
      var pcError = function (message) {
        log("PC [" + _id + "] ERROR: " + message);
      };

      var _call;
      this.setCall = function (__call) {
        _call = __call;
      };
      this.getCall = function () {
        return _call;
      };

      var statsTimer = null;

      var audioElement;
      var videoElement;

      audioElement = getAudioElement();
      audioElement.id = "vi_audio_" + _id;
      //audioElement.autoplay = "true";
      audioElement.volume = playbackVolume;

      document.body.appendChild(audioElement);

      if (videoSupport) {
        videoElement = getVideoElement();
        videoElement.id = "vi_video_" + _id;
        //videoElement.autoplay = "true";
        videoElement.width = 400;
        videoElement.height = 300;
        //videoElement.style.display = "none";
        videoElement.volume = playbackVolume;

        var container = remoteSinksContainerId ? document.getElementById(remoteSinksContainerId) : document.body;
        container.appendChild(videoElement);
      }

      var activeRemoteSD = null;
      var activeLocalSD = null;

      var localRTCPMux = false;
      var answerSent = false;

      var remoteAudioStream = null;
      var remoteVideoStream = null;
      var localStream = null;
      var iceComplete = false;

      var closed = false;

      var streamsActive = true;

      //local stream for screen sharing
      var ssStream = null;


      var onICETimeout = function () {
        if (_call) {
          _call.notifyICETimeout();
        }
      }.bind(this);

      this.getStats = function (callback) {
        var localStreamLocalStats;
        var localStreamRemoteStats;
        var remoteStreamLocalStats;
        var remoteStreamRemoteStats;

        if (!!localStream && !!remoteAudioStream) {
          pc.getStats(function (statsReport) {
            var x = statsReport.result();
            for (var i in x) {
              if (x[i].type == "ssrc") {
//									BXIM.webrtc.phoneLog("Stat "+i);
//									BXIM.webrtc.phoneLog("Names: "+x[i].names());
//									BXIM.webrtc.phoneLog("Type: "+x[i].type);
                if (x[i].local == x[i] || !x[i].remote) {

//										BXIM.webrtc.phoneLog("Local");

                }
                if (x[i].remote == x[i] || !x[i].local) {
//										BXIM.webrtc.phoneLog("remote");
                }

              }
            }
          });
        }
      };

      this.getRTCPeerConnection = function () {
        return pc;
      };

      this.getRemoteAudioStream = function () {
        return remoteAudioStream;
      };
      this.getRemoteVideoStream = function () {
        return remoteVideoStream;
      };

      this.getVideoElementId = function () {
        return videoElement ? videoElement.id : null;
      };

      this.getAudioElementId = function () {
        return audioElement ? audioElement.id : null;
      };

      this.setLocalStream = function (newLocalStream) {
        if (closed)
          return;

        if (localStream) {
          if (isFirefox) pc.removeStream(rtpSenders);
          else pc.removeStream(localStream);
          localStream = null;
        }

        if (newLocalStream) {
          localStream = newLocalStream;
          var audioTracks = typeof newLocalStream.getAudioTracks == "undefined" ? newLocalStream.audioTracks : newLocalStream.getAudioTracks();
          var videoTracks = typeof newLocalStream.getVideoTracks == "undefined" ? newLocalStream.videoTracks : newLocalStream.getVideoTracks();
          localStream = newLocalStream;
          if (isFirefox) {
            localStream.getTracks().forEach(function (track) {
              rtpSenders.push(pc.addTrack(track, localStream));
            });
          }
          else pc.addStream(localStream);
          enableTracks(localStream.getAudioTracks(), !microphoneMuted);
          enableTracks(localStream.getVideoTracks(), videoSent);
        }
      };

      var addNewScreenSharing = function (stream) {
        if (stream) {
          ssStream = stream;
          pc.addStream(ssStream);
        }
      };

      this.setScreenSharingStream = function (stream) {
        ssStream = stream;
      };

      this.addScreenSharing = function (stream) {
        if (closed)
          return;
        if (ssStream) {
          pc.removeStream(ssStream);
          ssStream = null;
        }
        addNewScreenSharing(stream);
      };

      this.onIncomingCall = function (sdp) {
        if (closed)
          return;

        activeRemoteSD = createSDP("offer", sdp);
        pc.setRemoteDescription(activeRemoteSD, function () {
        }, pcError);
      };

      this.onConnectionConnected = function (sdp) {
        if (closed)
          return;


        if (!_direct)
          return;

        activeRemoteSD = createSDP("answer", sdp);
        pc.setRemoteDescription(activeRemoteSD, function () {
          iceTimer = setTimeout(onICETimeout, ICE_TIMEOUT);
        }, pcError);
      };

      this.outgoingCall = function () {
        if (closed)
          return;

        pc.createOffer(function (sdpOffer) {
          addBandwidthParams(sdpOffer);
          activeLocalSD = sdpOffer;
          pc.setLocalDescription(createSDP("offer", sdpOffer.sdp), function () {
            sendOffer();
          }, pcError);
        }, pcError, mediaConstraints);
      };


      var sendOffer = function () {
        if (closed)
          return;
        callRemoteFunction("createCall", [-1, _call.getRemoteParty(), false, id, null, null, _call.getHeaders(), "", activeLocalSD.sdp]);
      };

      this.createAnswer = function () {
        if (closed)
          return;

        pcTrace("Calling createAnswer");
        pc.createAnswer(onAnswerCreated, pcError);
      };

      var onAnswerCreated = function (sdp) {
        if (closed) {
          return;
        }
        addBandwidthParams(sdp);

        localRTCPMux = sdp.sdp.indexOf("a=rtcp-mux") != -1;
        pc.setLocalDescription(sdp, function () {
          activeLocalSD = sdp;
//                    if (sdp.sdp.indexOf("candidate") != -1) {
          answerSent = true;
          sendAnswer();
          //                   }
        }, pcError);
      }.bind(this);

      var sendAnswer = function () {
        if (closed) {
          return;
        }

        iceTimer = setTimeout(onICETimeout, ICE_TIMEOUT);

        pcLog("Sending local answer");
        pcTrace("Local answer: " + activeLocalSD.sdp);

        if (_direct)
          callRemoteFunction("acceptCall", [_id, cleanHeaders(_call.getHeaders()), activeLocalSD.sdp]);
        else
          callRemoteFunction("__confirmPC", [_id, activeLocalSD.sdp]);
      };

      var candidatesEnded = false;
      var onCandidatesEnded = function () {
        if (candidatesEnded)
          return;
        candidatesEnded = true;
        // if (activeRemoteSD && (activeRemoteSD.type == "offer")) {
        //        if(!answerSent) {
        //            answerSent = true;
        //            if (_direct)
        //                sendAnswer();
        //            else {
        //                pc.createAnswer(function(sdp){
        //                    activeLocalSD = sdp;
        //                    sendAnswer();
        //                }, function (err) {

        //                	log("ERROR: "+err);
        //                });
        //            }
        //        }
        //    }

        //    if (activeLocalSD && (activeLocalSD.type == "offer")) {
        //        sendOffer();
        //    }

      };


      var rtpCandidateGenerated = false;
      var rtpVideoCandidateGenerated = false;
      var rtcpCandidateGenerated = false;
      var rtcpVideoCandidateGenerated = false;

      var canSendCandidates = false;
      var candidateSendTimer = null;
      var pendingCandidates = [];
      var remoteSSStreamId = null;
      var remoteSSStream = null;
      var remoteSSVideoElement = null;

      function startCandidateSendTimer() {
        if (candidateSendTimer === null)
          candidateSendTimer = setTimeout(function () {
            candidateSendTimer = null;
            if (pendingCandidates.length > 0) {
              __sendSIPInfo(_id, "voximplant", "sdpfrag", JSON.stringify(pendingCandidates), {});
            }
            pendingCandidates = [];
          }, 100);
      }

      //Send generated ICE candidates in SIP INFO. Accumulate them and send every 100ms to reduce overhead


      function addCandidateToSend(attrString, mLineIndex) {
        pendingCandidates.push([mLineIndex, attrString]);
        if (canSendCandidates)
          startCandidateSendTimer();

      }

      this.canStartSendingCandidates = function () {

        canSendCandidates = true;
        startCandidateSendTimer();
      };


      this.addRemoteCandidate = function (mediaId, attrString) {

        pc.addIceCandidate(new myRTCIceCandidate({
          candidate: attrString.substring(2),
          sdpMLineIndex: mediaId
        }), function () {
          BXIM.webrtc.phoneLog("Added ice candidate");
        }, function (err) {
//					pc.addIceCandidate(new myRTCIceCandidate({candidate: attrString, sdpMid: mediaId}), function() {BXIM.webrtc.phoneLog("Added ice candidate");}, function(err) {
          pcTrace("error adding ice candidate " + err);
//					});
        });
      };

      var onIceCandidate = function (event) {
        if (closed)
          return;

        if (event.candidate) {
          pcTrace("ICE candidate: " + event.candidate.candidate);
          var cand = event.candidate.candidate;

          var candidateAttr = cand;
          if (candidateAttr.indexOf('a=') == -1)  candidateAttr = "a=" + candidateAttr;
          if (isChrome && activeLocalSD) {

            activeLocalSD.sdp += candidateAttr;
            activeLocalSD.sdp += "\r\n";
          }
          if (!_direct)
            callRemoteFunction("__addCandidate", [_id, candidateAttr, event.candidate.sdpMLineIndex]);
          else
            addCandidateToSend(candidateAttr, event.candidate.sdpMLineIndex);

          if (!_direct) {
            /*	                    if(cand.toLowerCase().indexOf('1 udp')!=-1) 
             {
             if ((event.candidate.sdpMid && event.candidate.sdpMid.toLowerCase()=="audio") || event.candidate.sdpMLineIndex == 0)
             {
             rtpCandidateGenerated = true;
             }
             else
             {
             rtpVideoCandidateGenerated = true;				
             }
             }
             if(cand.toLowerCase().indexOf('2 udp')!=-1) 
             {
             if ((event.candidate.sdpMid && event.candidate.sdpMid.toLowerCase()=="audio") || event.candidate.sdpMLineIndex == 0)
             {
             rtcpCandidateGenerated = true;
             }
             else
             {
             rtcpVideoCandidateGenerated = true;				
             }
             }
             if (rtpCandidateGenerated && (rtcpCandidateGenerated || localRTCPMux)
             && ((rtpVideoCandidateGenerated && (rtcpVideoCandidateGenerated || localRTCPMux)) || !videoSupport)

             )
             {
             onCandidatesEnded();    	    		  	      
             }
             */

          }


        } else {
          pcLog("End of candidates.");
          onCandidatesEnded();
        }
      };

      this.setRemoteSDP = function (isOffer, sdpString, screenSharingStreamId) {
        if (typeof screenSharingStreamId == "string")
          remoteSSStreamId = screenSharingStreamId;
        pc.setRemoteDescription(createSDP(isOffer ? "offer" : "answer", sdpString), function () {

          if (isOffer) {
            pc.createAnswer(function (newAnswer) {
              addBandwidthParams(newAnswer);

              pc.setLocalDescription(newAnswer,
                function () {
                  __sendSIPInfo(_id, "vi", "sdpanswer", newAnswer.sdp, null);
                  renegotiationInProgress = false;
                },
                function (err) {

                });
            }, function (err) {
              pcLog("Error: " + err);
            })
          } else {
            renegotiationInProgress = false;
          }
        }, function (err) {
          pcLog("Error: " + err);
        })
      };

      var onRenegotiate = function () {


        if (closed) {
          pcLog("Renegotiation requested on closed PeerConnection");
          return;
        }
        if (activeLocalSD === null) {
          pcLog("Renegotiation needed, but no local SD, skipping");
          return;
        }

        if (pc.iceConnectionState != "connected" && pc.iceConnectionState != "completed") {
          pcLog("Renegotiation requested while ice state is " + pc.iceConnectionState + ". Postponing");
          setTimeout(onRenegotiate, 100);
          return;
        }
        if (renegotiationInProgress) {
          pcLog("Renegotiation in progress. Queueing");
          return;
        }
        else {
          pcLog("Renegotiation started");
        }
        renegotiationInProgress = true;
        if (_direct) {
          pc.createOffer(function (newOffer) {
            addBandwidthParams(newOffer);
            pcLog("New SDP: " + newOffer.sdp);

            pc.setLocalDescription(newOffer, function () {
              var headers = {};
              if (ssStream) {
                headers["X-ScreenStreamId"] = ssStream.id;
              }
              __sendSIPInfo(_id, "vi", "sdpoffer", newOffer.sdp, headers);
            }, function (err) {
              pcLog("Error: " + err);

            });

          }, function (error) {
            pcLog("ERROR: " + error);
          }, mediaConstraints);


        } else {

          pc.setRemoteDescription(activeRemoteSD,
            function () {
              if (closed) {
                renegotiationInProgress = false;
                return;
              }

              pc.createAnswer(function (sdpOffer) {
                if (closed) {
                  renegotiationInProgress = false;

                  return;
                }
                addBandwidthParams(sdpOffer);

                activeLocalSD = sdpOffer;
                pc.setLocalDescription(activeLocalSD, function () {
                    renegotiationInProgress = false;

                    pcLog("Renegotiation successful");
                  },


                  function (e) {
                    renegotiationInProgress = false;

                    pcLog("ERROR: " + e);
                  });
              }, function (e) {
                renegotiationInProgress = false;

                pcLog("ERROR: " + e);
              });

            },
            function (e) {
              renegotiationInProgress = false;

              pcLog("ERROR: " + e);
            }
          );

        }
      };

      var getPCStats = function (pc, callback) {
        if (!!navigator.mozGetUserMedia) {
          pc.getStats(null,
            function (res) {
              pcLog(res);
              var items = [];
              res.forEach(function (result) {
                if (result.type == "inboundrtp" || result.type == "outboundrtp")
                  items.push(result);
              });
              if (items.length > 0)
                callback(items);
            },
            function (err) {
              pcLog("ERROR: " + e);
            }
          );
        } else {
          pc.getStats(function (res) {
            pcLog(res);
            var items = [];
            res.result().forEach(function (result) {
              var item = {};
              result.names().forEach(function (name) {
                item[name] = result.stat(name);
              });
              item.id = result.id;
              item.type = result.type;
              item.timestamp = result.timestamp;

              if (item.type == "ssrc")
                items.push(item);

            });
            callback(items);
          });
        }
      };

      var remoteStreamAdded = function (e) {
        var isVideoStream = getVideoTracks(e.stream).length > 0;

        var isScreenSharingStream = e.stream.id == remoteSSStreamId;

        pcLog("Remote stream added " + e.stream.id + " " + (isVideoStream ? "video" : "audio"));
        if (isScreenSharingStream) {
          if (remoteSSVideoElement == null) {
            remoteSSVideoElement = getVideoElement();

            remoteSSVideoElement.id = "vi_ss_" + _id;
            //videoElement.autoplay = "true";
            remoteSSVideoElement.width = 400;
            remoteSSVideoElement.height = 300;
            //videoElement.style.display = "none";
            remoteSSVideoElement.volume = 0;


            var container = remoteSinksContainerId ? document.getElementById(remoteSinksContainerId) : document.body;
            container.appendChild(remoteSSVideoElement);

          }
          remoteSSStream = e.stream;
          attachMediaStream(remoteSSVideoElement, e.stream);
          notifyScreenCaptureStarted(_id, remoteSSVideoElement.id);

        } else {
          if (isVideoStream) {
            remoteVideoStream = e.stream;
          } else {
            remoteAudioStream = e.stream;
          }

          if (isVideoStream) {
            if (videoElement) {
              if (htmlmedia) attachMediaStream(videoElement, e.stream);
            }
          } else {
            if (htmlmedia) attachMediaStream(audioElement, e.stream);
          }

          //collect stats for audio stream only
          if (!isVideoStream && !statsTimer)
            statsTimer = setInterval(function () {


              getPCStats(pc, function (s) {
                notifyRTCStatsCollected(_id, s);
              });


            }, RTC_STATS_COLLECTION_INTERVAL);
          this.setStreamsActive(streamsActive);
        }
      };

      var remoteStreamRemoved = function (e) {
      };

      var onSignalingStateChange = function (e) {
        if (pc.signalingState == "stable") {
          if (ssStream != null) {
            var localStreams = pc.getLocalStreams();
            for (var s in localStreams) {
              if (localStreams[s] == ssStream) {
                return;
              }
            }

            //Add existing screen sharing to new call
            addNewScreenSharing(ssStream);
          }
        }
      };

      this.setPlaybackVolume = function (vol) {

        if (audioElement) {
          audioElement.volume = vol;
        }
        if (videoElement) {
          videoElement.volume = vol;
        }
      };


      this.streamsAreActive = function () {
        return streamsActive;
      };

      this.setStreamsActive = function (b) {
        streamsActive = b;
        enableTracks(getAudioTracks(remoteAudioStream), b && !speakerMuted);
        enableTracks(getAudioTracks(remoteVideoStream), b && !speakerMuted);
        enableTracks(getVideoTracks(remoteVideoStream), b);
        // if (isFirefox) {
        callRemoteFunction("__muteLocal", [_id, !b]);
        // } else {
        // 	enableTracks(getAudioTracks(localStream), b  && !microphoneMuted);
        // }

      }.bind(this);

      this.updateMicrophoneMuteState = function () {
        // if (isFirefox) {

        // } else {
        // 	enableTracks(getAudioTracks(localStream), streamsActive  && !microphoneMuted);
        // }
      };

      this.updateSpeakerMuteState = function () {

        if (remoteAudioStream) {
          enableTracks(getAudioTracks(remoteAudioStream), streamsActive && !speakerMuted);
        }
      };

      this.activateStreams = function () {
        if (closed) {
          return;
        }

        this.setStreamsActive(true);
      };
      this.deactivateStreams = function () {
        if (closed) {
          return;
        }

        this.setStreamsActive(false);
      };

      pc.onicecandidate = onIceCandidate.bind(this);
      pc.onaddstream = remoteStreamAdded.bind(this);
      pc.onnegotiationneeded = onRenegotiate.bind(this);
      pc.onremovestream = remoteStreamRemoved.bind(this);
      pc.onsignalingstatechange = onSignalingStateChange.bind(this);

      pc.oniceconnectionstatechange = function () {

        if (pc.iceConnectionState == "completed" || pc.iceConnectionState == "connected" && iceTimer) {
          clearTimeout(iceTimer);
          iceTimer = null;
        }
      }.bind(this);

      this.close = function () {
        closed = true;
        if (statsTimer)
          clearInterval(statsTimer);
        statsTimer = null;
        pc.close();
        if (audioElement) {
          // for Adapter
          if (typeof AdapterJS != "undefined") {
            document.getElementById(audioElement.id).parentNode.removeChild(document.getElementById(audioElement.id));
          } else audioElement.parentNode.removeChild(audioElement);
          releaseAudioElement(audioElement);
        }
        if (videoElement) {
          // for Adapter
          if (typeof AdapterJS != "undefined") {
            document.getElementById(videoElement.id).parentNode.removeChild(document.getElementById(videoElement.id));
          } else videoElement.parentNode.removeChild(videoElement);
          releaseVideoElement(videoElement);
        }
        if (remoteSSVideoElement) {
          remoteSSVideoElement.parentNode.removeChild(remoteSSVideoElement);
          releaseVideoElement(remoteSSVideoElement);
        }
      };

      this.start = function (sdpOffer) {
        if (closed)
          return;

        activeRemoteSD = createSDP("offer", sdpOffer);
        pc.setRemoteDescription(activeRemoteSD, function () {
          pc.createAnswer(function (sdp) {
            addBandwidthParams(sdp);
            pcLog("Local answer created: " + sdp.sdp);
            localRTCPMux = sdp.sdp.indexOf("a=rtcp-mux") != -1;
            pc.setLocalDescription(sdp, function () {
              activeLocalSD = sdp;
//                            if (sdp.sdp.indexOf("candidate") != -1) {
              answerSent = true;
              sendAnswer();
//                            }
            }, pcError);

          }, pcError);
        }, pcError);
      };
    }; // PeerConnectionWrapper2

    var callRemoteFunction = function (name, params) {
      trace("Called remote function " + name + " with params " + JSON.stringify(params));
      if (sock) {
        sock.send(JSON.stringify({"name": name, "params": params}));
      }
      else {
        log("ERROR: can't call remote function when not connected");
      }
      traceState();
    }; // callRemoteFunction
  }; // VoxImplant.ZingayaAPI
})(VoxImplant);
/**
* @namespace
* @name VoxImplant
*/
(function (VoxImplant, undefined) {
/**
* VoxImplant Configuration
* @class
* @property {Boolean} [useFlashOnly=false] Force VoxImplant to use Flash (WebRTC is used if available by default).
* @property {Boolean} [useRTCOnly=false] Force VoxImplant to use WebRTC (WebRTC is used if available by default). Error will be thrown if WebRTC in unavailable.
* @property {Boolean} [micRequired=false] If set to true microphone access dialog will be shown and all functions will become available only after user allowed access.
* @property {Boolean} [videoSupport=false] Video support.
* @property {Boolean} [swfContainer=null] Id of HTMLElement that will be used as container for Flash component of SDK (Mic/cam access dialog will appear in the container). If micRequired set to true element should have size not less than 215x138 (px) for access dialog to be shown.
* @property {String} [progressToneCountry=US] Country code for progress tone generated automatically if <a href="VoxImplant.Config.html#progressTone">progressTone</a> set to true.
* @property {Boolean} [progressTone=false] Automatically plays progress tone by means of SDK according to specified <a href="VoxImplant.Config.html#progressToneCountry">progressToneCountry</a>.
* @property {Boolean} [showDebugInfo=true] Show debug info in console.
* @property {Boolean} [showFlashSettings=false] Show Flash Settings panel instead of standard Allow/Deny dialog (in Flash mode)
* @property {Boolean} [imXSSprotection=true] XSS protection for inbound instant messages that can contain HTML content
* @property {Boolean} [imAutoReconnect=true] Reconnect in case if connection to Instant Messaging subsystem was closed
* @property {Number} [imReconnectInterval=3000] Time interval (in milliseconds) after which SDK tries to re-establish connection with IM subsystem (if imAutoReconnect set to true)
* @property {Boolean} [showWarnings=true] Show warnings for developers
* @property {VoxImplant.VideoSettings} [videoConstraints] Default constraints that will be applied while the next <a href="VoxImplant.Client.html#attachRecordingDevice">attachRecordingDevice</a> function call or if <a href="VoxImplant.Config.html#micRequired">micRequired</a> set to true
* @property {String} [videoContainerId] Id of HTMLElement that will be used as a default container for remote video elements. Remote videos are appended to the body element by default.
* @property {Boolean} [connectivityCheck=true] If set to false no UDP connection check to be done while the connection (WebRTC only)
*/
VoxImplant.Config = {};

/**
* Flash Video Settings
* @class
* @property {String} [profile=2.1] H.264 video codec profile 
* @property {String} [level=base] H.264 video codec level 
* @property {Number} [width=320] Width in pixels (should be set together with height)
* @property {Number} [height=240] Height in pixels (should be set together with width)
* @property {Number} [fps=15] The maximum rate at which the camera can capture data, in frames per second.
* @property {Number} [bandwidth=65535] The maximum amount of bandwidth the current outgoing video feed can use, in bytes
* @property {Number} [quality=0] The required level of picture quality, as determined by the amount of compression being applied to each video frame. Acceptable quality values range from 1 (lowest quality, maximum compression) to 100 (highest quality, no compression). The default value is 0, which means that picture quality can vary as needed to avoid exceeding available bandwidth.
* @property {Number} [keyframeInterval=30] Keyframe interval (seconds)
*/
VoxImplant.FlashVideoSettings = {};

/**
* WebRTC Video Settings (aka Constraints). Can be set to false.
* @class
* @property {Number|Object} [width] The width or width range, in pixels
* @property {Number|Object} [height] The height or height range, in pixels
* @property {Number} [aspectRatio] The exact aspect ratio (width in pixels divided by height in pixels, represented as a double rounded to the tenth decimal place) or aspect ratio range
* @property {Number} [frameRate] The exact frame rate (frames per second) or frame rate range
* @property {String} [facingMode] user/environment/left/right
* @property {String} [deviceId] The origin-unique identifier for the source of the MediaStreamTrack
* @property {String} [groupId] The origin-unique group identifier for the source of the MediaStreamTrack. Two devices have the same group identifier if they belong to the same physical device
* @property {Object} [mandatory] Mandatory constraints object (deprecated)
* @property {Object} [optional] Optional constraints object (deprecated)
*/
VoxImplant.VideoSettings = {};

/**
* Audio recording device info
* @class
* @property {Number|String} id Device id that can be used to choose audio recording device, see <a href="VoxImplant.Client.html#useAudioSource">VoxImplant.Client#useAudioSource</a>
* @property {String} name Device name , in WebRTC mode populated with real data only when app has been opened using HTTPS protocol
*/
VoxImplant.AudioSourceInfo = {};


/**
* Call disconnecting flags
* @class
* @property {Boolean} answeredElsewhere Set true, when call answered elsewhere
*/
VoxImplant.DisconnectingFlags ={};

/**
* Video recording device info
* @class
* @property {Number|String} id Device id that can be used to choose video recording device, see <a href="VoxImplant.Client.html#useVideoSource">VoxImplant.Client#useVideoSource</a>
* @property {String} name Device name , in WebRTC mode populated with real data only when app has been opened using HTTPS protocol
*/
VoxImplant.VideoSourceInfo = {};

/**
* Audio playback device info
* @class
* @property {Number|String} id Device id that can be used to choose audio playback device, see <a href="VoxImplant.Call.html#useAudioOutput">VoxImplant.Client#useAudioOutput</a>
* @property {String} name Device name , in WebRTC mode populated with real data only when app has been opened using HTTPS protocol
*/
VoxImplant.AudioOutputInfo = {};

/**
* Network information
* @class
* @property {Number} [packetLoss] Packet loss percentage
*/
VoxImplant.NetworkInfo = {};

/**
* Enum for subscription 
* @namespace
* @name VoxImplant.SubscriptionRequestType
* @group IM/Presence
*/
VoxImplant.SubscriptionRequestType = {
	/**
	* @const
	* @name VoxImplant.SubscriptionRequestType.Subscribe
	* User is asking for permission to add you into his roster
	* @memberof VoxImplant.SubscriptionRequestType
	* @static
	*/
	Subscribe: 0,
	/**
	* @const
	* @name VoxImplant.SubscriptionRequestType.Unsubscribe
	* User removed you from his roster
	* @memberof VoxImplant.SubscriptionRequestType
	* @static
	*/
    Unsubscribe: 1
};

/**
* Enum for chat states
* @namespace
* @name VoxImplant.ChatStateType
* @group IM/Presence
*/
VoxImplant.ChatStateType = {
	/**
	* @const
	* @name VoxImplant.ChatStateType.Active
	* User is actively participating in the chat session
	* @memberof VoxImplant.ChatStateType
	* @static
	*/
	Active:  	1,
	/**
	* @const
	* @name VoxImplant.ChatStateType.Composing
	* User is composing a message
	* @memberof VoxImplant.ChatStateType
	* @static
	*/
    Composing: 	2,
    /**
	* @const
	* @name VoxImplant.ChatStateType.Paused
	* User had been composing but now has stopped
	* @memberof VoxImplant.ChatStateType
	* @static
	*/
    Paused: 	4,
    /**
	* @const
	* @name VoxImplant.ChatStateType.Inactive
	* User has not been actively participating in the chat session
	* @memberof VoxImplant.ChatStateType
	* @static
	*/
    Inactive: 	8,
    /**
	* @const
	* @name VoxImplant.ChatStateType.Gone
	* User has effectively ended their participation in the chat session
	* @memberof VoxImplant.ChatStateType
	* @static
	*/
    Gone: 		16,
    /**
	* @const
	* @name VoxImplant.ChatStateType.Invalid
	* Invalid type
	* @memberof VoxImplant.ChatStateType
	* @static
	*/
    Invalid: 	32
};

/**
* Enum for message events
* @namespace
* @name VoxImplant.MessageEventType
* @group IM/Presence
*/
VoxImplant.MessageEventType = {
	/**
	* @const
	* @name VoxImplant.MessageEventType.Offline
	* Indicates that the message has been stored offline by the intended recipient's server
	* @memberof VoxImplant.MessageEventType
	* @static
	*/
	Offline: 1,
	/**
	* @const
	* @name VoxImplant.MessageEventType.Delivered
	* Indicates that the message has been delivered to the recipient
	* @memberof VoxImplant.MessageEventType
	* @static
	*/
    Delivered: 2,
    /**
	* @const
	* @name VoxImplant.MessageEventType.Displayed
	* Indicates that the message has been displayed
	* @memberof VoxImplant.MessageEventType
	* @static
	*/
   	Displayed: 4,
   	/**
	* @const
	* @name VoxImplant.MessageEventType.Composing
	* Indicates that a reply is being composed
	* @memberof VoxImplant.MessageEventType
	* @static
	*/
    Composing: 8,
    /**
	* @const
	* @name VoxImplant.MessageEventType.Invalid
	* Invalid type
	* @memberof VoxImplant.MessageEventType
	* @static
	*/
    Invalid: 16,
    /**
	* @const
	* @name VoxImplant.MessageEventType.Cancel
	* Cancels the 'Composing' event
	* @memberof VoxImplant.MessageEventType
	* @static
	*/
    Cancel: 32
};

/**
* Enum for roster item events
* @namespace
* @name VoxImplant.RosterItemEvent
* @group IM/Presence
*/
VoxImplant.RosterItemEvent = {
	/**
	* @const
	* @name VoxImplant.RosterItemEvent.Added
	* Roster item added
	* @memberof VoxImplant.RosterItemEvent
	* @static
	*/
    Added: 0,
    /**
	* @const
	* @name VoxImplant.RosterItemEvent.Removed
	* Roster item removed
	* @memberof VoxImplant.RosterItemEvent
	* @static
	*/
    Removed: 1,
    /**
	* @const
	* @name VoxImplant.RosterItemEvent.Updated
	* Roster item updated
	* @memberof VoxImplant.RosterItemEvent
	* @static
	*/
    Updated: 2,
    /**
	* @const
	* @name VoxImplant.RosterItemEvent.Subscribed
	* User subscribed on your status updates (authorized the request)
	* @memberof VoxImplant.RosterItemEvent
	* @static
	*/
    Subscribed: 3,
    /**
	* @const
	* @name VoxImplant.RosterItemEvent.Unsubscribed
	* User unsubscribed from your status updates (didn't authorize the request)
	* @memberof VoxImplant.RosterItemEvent
	* @static
	*/
    Unsubscribed: 4
};

/**
* Enum for user presence statuses
* @namespace
* @name VoxImplant.UserStatuses
* @group IM/Presence
*/
VoxImplant.UserStatuses = {
	/**
	* @const
	* @name VoxImplant.UserStatuses.Online
	* User is online
	* @memberof VoxImplant.UserStatuses
	* @static
	*/
	Online: 0,
	/**
	* @const
	* @name VoxImplant.UserStatuses.Chat
	* User is available for chat
	* @memberof VoxImplant.UserStatuses
	* @static
	*/
	Chat: 1,
	/**
	* @const
	* @name VoxImplant.UserStatuses.Away
	* User is away
	* @memberof VoxImplant.UserStatuses
	* @static
	*/
	Away: 2,
	/**
	* @const
	* @name VoxImplant.UserStatuses.DND
	* User is in DND state (Do Not Disturbed)
	* @memberof VoxImplant.UserStatuses
	* @static
	*/
	DND: 3,
	/**
	* @const
	* @name VoxImplant.UserStatuses.XA
	* User is in XA state (eXtended Away)
	* @memberof VoxImplant.UserStatuses
	* @static
	*/
	XA: 4,
	/**
	* @const
	* @name VoxImplant.UserStatuses.Offline
	* User if offline
	* @memberof VoxImplant.UserStatuses
	* @static
	*/
	Offline: 5
};

/**
* Enum for ACD statuses, use <a href="VoxImplant.Client.html#setOperatorACDStatus">VoxImplant.Client#setOperatorACDStatus</a> to set the status.
* @namespace
* @name VoxImplant.OperatorACDStatuses
*/
VoxImplant.OperatorACDStatuses = {
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.Offline
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	Offline: "OFFLINE",
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.Online
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	Online: "ONLINE",
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.Ready
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	Ready: "READY",
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.InService
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	InService: "IN_SERVICE",
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.AfterService
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	AfterService: "AFTER_SERVICE",
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.Timeout
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	Timeout: "TIMEOUT", 
	/**
	* @const
	* @name VoxImplant.OperatorACDStatuses.DND
	* @memberof VoxImplant.OperatorACDStatuses
	* @static
	*/
	DND: "DND"
};

/**
* Enum for IMError error types
* @namespace
* @name VoxImplant.IMErrorType
* @group IM/Presence
*/
VoxImplant.IMErrorType = {
	/**
	* @const
	* @name VoxImplant.IMErrorType.RemoteFunctionError
	* @memberof VoxImplant.IMErrorType
	* @static
	*/
	RemoteFunctionError: "RemoteFunctionError",
	/**
	* @const
	* @name VoxImplant.IMErrorType.Error
	* @memberof VoxImplant.IMErrorType
	* @static
	*/
	Error: "Error",
	/**
	* @const
	* @name VoxImplant.IMErrorType.RosterError
	* @memberof VoxImplant.IMErrorType
	* @static
	*/
	RosterError: "RosterError"
};

/**
* VoxImplant login options
* @class
* @property {Boolean} [receiveCalls=true] If set to false Web SDK can be used only for ACD status management
* @property {Boolean} [serverPresenceControl=false] If set to true user presence will be changed automatically while a call
*/
VoxImplant.LoginOptions = {};

/**
* VoxImplant roster item
* @class
* @property {Array} groups Groups this roster item belongs to
* @property {String} id User id
* @property {String} name User display name
* @property {Array} resources Resources
* @property {Number} subscription_type Subscription type
* @group IM/Presence
*/
VoxImplant.RosterItem = {};

/**
* Chat room
* @class
* @property {String} id Chat room id
* @property {String} pass Chat room password
* @group IM/Presence
*/
VoxImplant.ChatRoom = {};

/**
* Chat room info
* @class
* @property {String} description Room description
* @property {Number} occupants Number of chat room participants
* @property {String} subject Room's name / subject
* @property {String} creationdate Creation date
* @group IM/Presence
*/
VoxImplant.ChatRoomInfo = {};

/**
* Chat room participant
* @class
* @property {String} name User display name
* @property {String} id User id
* @property {Boolean} [owner] True if the user is owner/admin of the room
* @group IM/Presence
*/
VoxImplant.ChatRoomParticipant = {};

/**
* Chat room operations
* @namespace
* @name VoxImplant.ChatRoomOperationType
* @group IM/Presence
*/
VoxImplant.ChatRoomOperationType = {
	/**
	* @const
	* @name VoxImplant.ChatRoomOperationType.Ban
	* Chat room participant banned
	* @memberof VoxImplant.ChatRoomOperationType
	* @static
	*/
	Ban: 13,
	/**
	* @const
	* @name VoxImplant.ChatRoomOperationType.Unban
	* Chat room participant unbanned
	* @memberof VoxImplant.ChatRoomOperationType
	* @static
	*/
    Unban: 12
};

/**
* Participant info
* @class
* @property {Number} affiliation The participant's affiliation with the room
* @property {Number} flags Indicate conditions like: user has been kicked or banned from the room
* @property {String} id User id
* @property {String} reason Optional text string set after user is banned or kicked
* @property {String} resource Resource name
* @property {Number} role The participant's role with the room
* @group IM/Presence
*/
VoxImplant.ParticipantInfo = {};

/**
* Message received from history
* @class
* @property {String} body Message body
* @property {String} id Message id
* @property {String} from User id - author of the message
* @property {String} time Message creation time
* @group IM/Presence
*/
VoxImplant.IMHistoryMessage = {};

})(VoxImplant);

/**
* @namespace
* @name VoxImplant.Events
*/
(function (VoxImplant, undefined) {
/**
* Events dispatched by <a href="VoxImplant.Client.html">VoxImplant.Client</a> instance. See <a href="VoxImplant.html#VoxImplant_getInstance">VoxImplant.getInstance</a>.
* @namespace
* @name VoxImplant.Events
*/
VoxImplant.Events = {
	/**
	* @class
	* @name VoxImplant.Events.SDKReady
	* @#event
	* Event dispatched after SDK was successfully initialized after <a href="VoxImplant.Client.html#init">init</a> function call
	* @property {String} version SDK version
	*/
	SDKReady: "SDKReady",
	/**
	* @class
	* @name VoxImplant.Events.ConnectionEstablished
	* @#event
	* Event dispatched after connection to VoxImplant Cloud was established successfully. See <a href="VoxImplant.Client.html#connect">connect</a> function.
	*/
	ConnectionEstablished: "ConnectionEstablished",
	/**
	* @class
	* @name VoxImplant.Events.ConnectionFailed
	* @#event
	* Event dispatched if connection to VoxImplant Cloud couldn't be established. See <a href="VoxImplant.Client.html#connect">connect</a> function.
	* @property {String} message Failure reason description
	*/
	ConnectionFailed: "ConnectionFailed",
	/**
	* @class
	* @name VoxImplant.Events.ConnectionClosed
	* @#event
	* Event dispatched if connection to VoxImplant Cloud was closed because of network problems. See <a href="VoxImplant.Client.html#connect">connect</a> function.
	*/
	ConnectionClosed: "ConnectionClosed",
	/**
	* @class
	* @name VoxImplant.Events.AuthResult
	* @#event
	* Event dispatched after <a href="VoxImplant.Client.html#login">login</a> , <a href="VoxImplant.Client.html#loginWithOneTimeKey">loginWithOneTimeKey</a>, <a href="VoxImplant.Client.html#requestOneTimeLoginKey">requestOneTimeLoginKey</a> or <a href="VoxImplant.Client.html#loginWithCode">loginWithCode</a> function call.
	* @property {Boolean} result True in case of successful authorization, false - otherwise.
	* @property {Number} [code] Auth error code, possible values are: 301 -  code for 'code' auth type was sent, 302 - key for 'onetimekey' auth type received, 401 - invalid password, 404 - invalid username, 403 - user account is frozen, 500 - internal error.
	* @property {String} [key] This parameter is used to calculate hash parameter for <a href="VoxImplant.Client.html#loginWithOneTimeKey">loginWithOneTimeKey</a> method. AuthEvent with the key dispatched after <a href="VoxImplant.Client.html#requestOneTimeLoginKey">requestOneTimeLoginKey</a> method was called. 
	* @property {String} [displayName] Authorized user's display name
	* @property {Object} [options] Application options
	*/
	AuthResult: "AuthResult",
	/**
	* @class
	* @name VoxImplant.Events.PlaybackFinished
	* @#event
	* Event dispatched after sound playback was stopped. See <a href="VoxImplant.Client.html#playToneScript">playToneScript</a> and <a href="VoxImplant.Client.html#stopPlayback">stopPlayback</a> functions.
	*/
	PlaybackFinished: "PlaybackFinished",
	/**
	* @class
	* @name VoxImplant.Events.MicAccessResult
	* @#event
	* Event dispatched after user interacted with mic access dialog.
	* @property {Boolean} result True is access was allowed, false - otherwise.
	* @property {MediaStream} [stream] MediaStream object (WebRTC only)
	*/
	MicAccessResult: "MicAccessResult",
	/**
	* @class
	* @name VoxImplant.Events.IncomingCall
	* @#event
	* Event dispatched when there is a new incoming call to current user.
	* @property {VoxImplant.Call} call Incoming call instance. See <a href="VoxImplant.Call.html">VoxImplant.Call</a> for details.
	* @property {Object} [headers] Optional SIP headers received with the message.
	*/
	IncomingCall: "IncomingCall",
	/**
	* @class
	* @name VoxImplant.Events.SourcesInfoUpdated
	* @#event
	* Event dispatched when the info about available audio and video recording / playback devices received. See <a href="VoxImplant.Client.html#audioSources">audioSources</a>, <a href="VoxImplant.Client.html#videoSources">videoSources</a> and <a href="VoxImplant.Client.html#audioOutputs">audioOutputs</a> for details.
	*/
	SourcesInfoUpdated: "SourcesInfoUpdated",
	/**
	* @class
	* @name VoxImplant.Events.NetStatsReceived
	* @#event
	* Event dispatched when packet loss data received from VoxImplant servers
	* @property {VoxImplant.NetworkInfo} stats Network info object
	*/
	NetStatsReceived: "NetStatsReceived"	
};

/**
* Events dispatched by <a href="VoxImplant.Call.html">VoxImplant.Call</a> instance
* @namespace 
* @name VoxImplant.CallEvents
*/
VoxImplant.CallEvents = {
	/**
	* @class
	* @name VoxImplant.CallEvents.Connected
	* @#event
	* Event dispatched after call was connected
	* @property {VoxImplant.Call} call Call that dispatched the event
	* @property {Object} [headers] Optional SIP headers received with the message
	*/
	Connected: "Connected",
	/**
	* @class
	* @name VoxImplant.CallEvents.Disconnected
	* @#event
	* Event dispatched after call was disconnected
	* @property {VoxImplant.Call} call Call that dispatched the event
	* @property {Object} [headers] Optional SIP headers received with the message
  * @property {VoxImplant.DisconnectingFlags} [params] Optional disconnecting flags
	*/
	Disconnected: "Disconnected",
	/**
	* @class
	* @name VoxImplant.CallEvents.Failed
	* @#event
	* Event dispatched after if call failed.
	* @property {Number} code Status code of the call (i.e. 486)
	* @property {String} reason Status message of call failure (i.e. Busy Here)
	* @property {VoxImplant.Call} call Call that dispatched the event
	* @property {Object} [headers] Optional SIP headers received with the message.
	* Most frequent status codes:<br/>
	* <table class="b-list" style="margin-top:10px">
	* <thead><tr><th>Code</th><th>Description</th></tr></thead>
	* <tbody>
	* <tr><td>486</td><td>Destination number is busy</td></tr>
	* <tr><td>487</td><td>Request terminated</td></tr>
	* <tr><td>603</td><td>Call was rejected</td></tr>
	* <tr><td>404</td><td>Invalid number</td></tr>
	* <tr><td>480</td><td>Destination number is unavailable</td></tr>
	* <tr><td>402</td><td>Insufficient funds</td></tr>
	* </tbody>
	* </table>
	*/
	Failed: "Failed",
	/**
	* @class
	* @name VoxImplant.CallEvents.ProgressToneStart
	* @#event
	* Event dispatched when progress tone playback starts.
	* @property {VoxImplant.Call} call Call that dispatched the event
	*/
	ProgressToneStart: "ProgressToneStart",
	/**
	* @class
	* @name VoxImplant.CallEvents.ProgressToneStop
	* @#event
	* Event dispatched when progress tone playback stops.
	* @property {VoxImplant.Call} call Call that dispatched the event
	*/
	ProgressToneStop: "ProgressToneStop",
	/**
	* @class
	* @name VoxImplant.CallEvents.MessageReceived
	* @#event
	* Event dispatched when text message is received.
	* @property {String} text Content of the message.
	* @property {VoxImplant.Call} call Call that dispatched the event.
	*/
	MessageReceived: "MessageReceived",
	/**
	* @class
	* @name VoxImplant.CallEvents.InfoReceived
	* @#event
	* Event dispatched when INFO message is received.
	* @property {String} mimeType MIME type of INFO message.
	* @property {String} body Content of the message.
	* @property {Object} [headers] Optional SIP headers received with the message.
	* @property {VoxImplant.Call} call Call that dispatched the event.
	*/
	InfoReceived: "InfoReceived",
	/**
	* @class
	* @name VoxImplant.CallEvents.TransferComplete
	* @#event
	* Event dispatched when call has been transferred successfully.
	* @property {VoxImplant.Call} call Call that dispatched the event.
	*/
	TransferComplete: "TransferComplete",
	/**
	* @class
	* @name VoxImplant.CallEvents.TransferFailed
	* @#event
	* Event dispatched when call transfer failed.
	* @property {VoxImplant.Call} call Call that dispatched the event.
	*/
	TransferFailed: "TransferFailed",
	/**
	* @class
	* @name VoxImplant.CallEvents.RemoteScreenCaptureStarted
	* @#event
	* Event dispatched when screen sharing started on remote end
	* @property {VoxImplant.Call} call Call that dispatched the event.
	* @property {String} videoElementId Video element id (DOM).
	* @ignore
	*/
	RemoteScreenCaptureStarted: 'RemoteScreenCaptureStarted',
	/**
	* @class
	* @name VoxImplant.CallEvents.ICETimeout
	* @#event
	* Event dispatched in case of network connection problem between 2 peers
	* @property {VoxImplant.Call} call Call that dispatched the event.
	*/
	ICETimeout: 'ICETimeout',
	/**
	* @class
	* @name VoxImplant.Events.RTCStatsReceived
	* @#event
	* Event dispatched to notify about WebRTC stats collected by browser
	* @property {Object} stats RTC stats object
	*/
	RTCStatsReceived: "RTCStatsReceived"
};

/**
* Instant Messaging Events
* @namespace 
* @name VoxImplant.IMEvents
* @group IM/Presence
*/
VoxImplant.IMEvents = {
	/**
	* @class
	* @name VoxImplant.IMEvents.RosterReceived
	* @#event
	* Event dispatched when roster data received
	* @property {Array} roster Array contains <a href="VoxImplant.RosterItem.html">VoxImplant.RosterItem</a> elements
	*/
	RosterReceived: "RosterReceived",
	/**
	* @class
	* @name VoxImplant.IMEvents.RosterItemChange
	* @#event
	* Event dispatched when roster item changed
	* @property {String} id User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {Number} type Roster item event type. See <a href="VoxImplant.RosterItemEvent.html">VoxImplant.RosterItemEvent</a> enum
	* @property {String} displayName User display name
	* @property {Array} groups Roster item groups
	*/
	RosterItemChange: "RosterItemChange",
	/**
	* @class
	* @name VoxImplant.IMEvents.RosterPresenceUpdate
	* @#event
	* Event dispatched when roster item presence update happened
	* @property {String} id User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {Number} presence Current presence status 
	* @property {String} message Status message
	*/
	RosterPresenceUpdate: "RosterPresenceUpdate",
	/**
	* @class
	* @name VoxImplant.IMEvents.PresenceUpdate
	* @#event
	* Event dispatched when self presence updated
	* @property {String} id User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {Number} presence Current presence status 
	* @property {String} message Status message
	*/
	PresenceUpdate: "PresenceUpdate",
	/**
	* @class 
	* @name VoxImplant.IMEvents.MessageReceived
	* @#event
	* Event dispatched when instant message received
	* @property {String} id User id (of the user who sent the message) 
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {String} content Message content
	* @property {String} message_id Message id
	* @property {String} to User id (of the user to whom the message was sent)
	*/
	MessageReceived: "MessageReceived",
	/**
	* @class 
	* @name VoxImplant.IMEvents.MessageModified
	* @#event
	* Event dispatched when instant message was modified by user
	* @property {String} id User id (of the user who sent the message) 
	* @property {String} message_id Message id
	* @property {String} content Message content
	* @property {String} to User id (of the user to whom the message was sent)
	*/
	MessageModified: "MessageModified",
	/**
	* @class 
	* @name VoxImplant.IMEvents.MessageNotModified
	* @#event
	* Event dispatched if error happened during instant message modification
	* @property {String} to User id (of the user to whom the message was sent)
	* @property {String} message_id Message id
	* @property {Number} code Error code
	*/
	MessageNotModified: "MessageNotModified",
	/**
	* @class 
	* @name VoxImplant.IMEvents.MessageRemoved
	* @#event
	* Event dispatched when instant message was removed by user
	* @property {String} id User id (of the user who sent the message) 
	* @property {String} message_id Message id
	* @property {String} to User id (of the user to whom the message was sent)
	*/
	MessageRemoved: "MessageRemoved",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatStateUpdate
	* @#event
	* Event dispatched when chat session state updated
	* @property {String} id User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {Number} state Current chat session state. See <a href="VoxImplant.ChatStateType.html">VoxImplant.ChatStateType</a> enum
	*/
	ChatStateUpdate: "ChatStateUpdate",
	/**
	* @class
	* @name VoxImplant.IMEvents.MessageStatus
	* @#event
	* Event dispatched when sent message status changed
	* @property {String} id User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {VoxImplant.MessageEventType} type Message event type
	* @property {String} message_id Message id
	* @property {Number} [code] Error code in case of error
	*/
	MessageStatus: "MessageStatus",
	/**
	* @class
	* @name VoxImplant.IMEvents.SubscriptionRequest
	* @#event
	* Event dispatched when some user tries to add current user into his roster. Current user can confirm or reject the subscription, then <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a> will be dispatched on for user that made the request  
	* @property {String} id User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {VoxImplant.SubscriptionRequestType} type Subscription request type
	* @property {String} message Optional message
	*/
	SubscriptionRequest: "SubscriptionRequest",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatHistoryReceived
	* @#event
	* Event dispatched when chat history received
	* @property {String} id User id
	* @property {String} message_id Message id specified for getInstantMessagingHistory method
	* @property {Array} messages List of messages. See <a href="VoxImplant.IMHistoryMessage.html">VoxImplant.IMHistoryMessage</a>
	*/
	ChatHistoryReceived: "ChatHistoryReceived",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomCreated
	* @#event
	* Event dispatched if chat room was created successfully
	* @property {String} room Room id
	*/
	ChatRoomCreated: "ChatRoomCreated",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomSubjectChange
	* @#event
	* Event dispatched if chat room subject was changed
	* @property {String} room Room id
	* @property {String} id User who changed the subject
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {String} subject New subject
	*/
	ChatRoomSubjectChange: "ChatRoomSubjectChange",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomInfo
	* @#event
	* Event dispatched when user joins chat room
	* @property {String} room Room id
	* @property {Number} features Room features
	* @property {String} room_name Room name
	* @property {VoxImplant.ChatRoomInfo} info Room info object. See <a href="VoxImplant.ChatRoomInfo.html">VoxImplant.ChatRoomInfo</a>
	*/
	ChatRoomInfo: "ChatRoomInfo",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomMessageReceived
	* @#event
	* Event dispatched when instant message was sent to chat room
	* @property {String} room Room id
	* @property {String} message_id Message id
	* @property {Boolean} private_message Private/public message
	* @property {Number} timestamp Message timestamp
	* @property {String} from User id 
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {String} content Message content
	*/
	ChatRoomMessageReceived: "ChatRoomMessageReceived",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomInvitation
	* @#event
	* Event dispatched when invitation to chat room received
	* @property {String} room Room id
	* @property {String} from User id (inviter)
	* @property {String} reason A reason of the invitation
	* @property {String} body The body of the message
	* @property {String} [password] Password for the room
	*/
	ChatRoomInvitation: "ChatRoomInvitation",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomInviteDeclined
	* @#event
	* Event dispatched if an invitation to chat room was declined by the invitee
	* @property {String} room Room id
	* @property {String} invitee User id (invitee)
	* @property {String} [reason] A reason of the invitation
	*/
	ChatRoomInviteDeclined: "ChatRoomInviteDeclined",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomPresenceUpdate
	* @#event
	* Event dispatched if chat room participant presence status was updated
	* @property {String} room Room id
	* @property {VoxImplant.ParticipantInfo} participant Participant info
	* @property {VoxImplant.UserStatuses} presence Current presence status
	* @property {String} [message] Optional presence message
	*/
	ChatRoomPresenceUpdate: "ChatRoomPresenceUpdate",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomNewParticipant
	* @#event
	* Event dispatched when new participant joined the chat room
	* @property {String} room Room id
	* @property {String} participant User id
	* @property {String} displayName User display name
	*/
	ChatRoomNewParticipant: "ChatRoomNewParticipant",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomParticipantExit
	* @#event
	* Event dispatched when participant left the chat room
	* @property {String} room Room id
	* @property {String} participant User id	
	*/
	ChatRoomParticipantExit: "ChatRoomParticipantExit",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomsDataReceived
	* @#event
	* Event dispatched when information about chat rooms where user participates received
	* @property {Array} rooms Rooms list. See <a href="VoxImplant.ChatRoom.html">VoxImplant.ChatRoom</a>
	*/
	ChatRoomsDataReceived: "ChatRoomsDataReceived",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomParticipants
	* @#event
	* Event dispatched when info about chat room participants received
	* @property {String} room Room id
	* @property {Array} participants Participants list. See <a href="VoxImplant.ChatRoomParticipant.html">VoxImplant.ChatRoomParticipant</a>
	*/
	ChatRoomParticipants: "ChatRoomParticipants",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomBanList
	* @#event
	* Event dispatched when info about banned chat room participants received
	* @property {String} room Room id
	* @property {Array} participants Participants list. See <a href="VoxImplant.ChatRoomParticipant.html">VoxImplant.ChatRoomParticipant</a>
	*/
	ChatRoomBanList: "ChatRoomBanList",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomHistoryReceived
	* @#event
	* Event dispatched when chat room history received
	* @property {String} room Room id
	* @property {String} message_id Message id specified for getChatRoomHistory method
	* @property {Array} messages List of messages. See <a href="VoxImplant.IMHistoryMessage.html">VoxImplant.IMHistoryMessage</a>
	*/
	ChatRoomHistoryReceived: "ChatRoomHistoryReceived",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomMessageModified
	* @#event
	* Event dispatched when chat room message modified
	* @property {String} room Room id
	* @property {Boolean} private_message Private/public message flag
	* @property {String} message_id Modified message id
	* @property {Number} timestamp Message timestamp
	* @property {String} from User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {String} content New message content
	*/
	ChatRoomMessageModified: "ChatRoomMessageModified",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomMessageNotModified
	* @#event
	* Event dispatched in case of error during chat room message modification
	* @property {String} room Room id
	* @property {Boolean} private_message Private/public message flag
	* @property {String} message_id Modified message id
	* @property {Number} code Error code
	*/
	ChatRoomMessageNotModified: "ChatRoomMessageNotModified",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomMessageRemoved
	* @#event
	* Event dispatched when chat room message removed room, private_message, message_id, timestamp, from
	* @property {String} room Room id
	* @property {Boolean} private_message Private/public message flag
	* @property {String} message_id Deleted message id
	* @property {Number} timestamp Message timestamp
	* @property {String} from User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	*/
	ChatRoomMessageRemoved: "ChatRoomMessageRemoved",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomStateUpdate
	* @#event
	* Event dispatched when chat session state updated
	* @property {String} room Room id
	* @property {String} from User id
	* @property {String} resource Allows to distinguish same user who logged in from different devices
	* @property {VoxImplant.ChatStateType} state Current chat session state. See <a href="VoxImplant.ChatStateType.html">VoxImplant.ChatStateType</a> enum
	*/
	ChatRoomStateUpdate: "ChatRoomStateUpdate",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomError
	* @#event
	* Event dispatched in case of error while chat room operation
	* @property {String} room Room id
	* @property {String} operation Operation name
	* @property {String} code Error code
	* @property {String} text Error description
	*/
	ChatRoomError: "ChatRoomError",
	/**
	* @class
	* @name VoxImplant.IMEvents.ChatRoomOperation
	* @#event
	* Event dispatched as a result of ban/unban
	* @property {String} room Room id
	* @property {VoxImplant.ChatRoomOperationType} operation Operation type
	* @property {Boolean} result true/false - success/failure
	*/
	ChatRoomOperation: "ChatRoomOperation",
	/**
	* @class
	* @name VoxImplant.IMEvents.SystemError
	* @#event
	* Event dispatched in case of instant messaging subsystem error
	* @property {VoxImplant.IMErrorType} errorType Error type
	* @property {Object} errorData Error data object, contains the error details
	*/
	SystemError: "IMError",
	/**
	* @class
	* @name VoxImplant.IMEvents.UCConnected
	* @#event
	* Event dispatched when instant messaging and presence subsystems (UC) are online
	*/
	UCConnected: "UCConnected",
	/**
	* @class
	* @name VoxImplant.IMEvents.UCDisconnected
	* @#event
	* Event dispatched when instant messaging and presence subsystems (UC) are offline. See <a href="http://voximplant.com/docs/references/websdk/VoxImplant.Config.html#imAutoReconnect">imAutoReconnect</a>
	*/
	UCDisconnected: "UCDisconnected"
};

})(VoxImplant);


/**
* @namespace
* @name VoxImplant
*/
(function (VoxImplant, undefined) {
/**
* @class Call class lets you control call using its functions
*/
VoxImplant.Call = function(c, n, dn, headers, RTC, api) {
	/** 
	* @type {Boolean} 
	* @ignore
	*/
	var _RTC = RTC;
	/** 
	* @type {String|ZingayaCall} 
	* @ignore
	*/
	var _call = c;
	/** 
	* @type {String} 
	* @ignore
	*/
	var _num = n;
	/** 
	* @type {String} 
	* @ignore
	*/
	var _displayName = dn;
	/** 
	* @type {String} 
	* @ignore
	*/
	var _headers = headers;
	/** 
	* @type {ZingayaAPI} 
	* @ignore
	*/
	var _zingayaAPI = api;
	/** 
	* @type {Object} 
	* @ignore
	*/
	this.eventListeners = {};
	
	/**
	* @param {String} pid Call id 
	* @returns {String}
	* @ignore
	*/
	this.call = function(pid) {
		if (typeof pid == 'undefined') return _call;
		else _call = pid; 
	};
	
	/** @ignore */
	this.__number = function(pnum) {
		if (typeof pnum == 'undefined') return _num;
		else _num = pnum;
	};

	/** @ignore */
	this.__displayName = function() {
		return _displayName;
	};

	/** @ignore */
	this.__headers = function() {
		return _headers;
	};
	
	/**
	* @returns {Boolean}
	* @ignore
	*/
	this.RTC = function() {
		return _RTC;
	};
	
	/**
	* @returns {ZingayaAPI}
	* @ignore
	*/
	this.zingayaAPI = function() {
		return _zingayaAPI;
	};
};


VoxImplant.Call.prototype = {
	/**
	* Returns call id
	* @name VoxImplant.Call.id
	* @function
	* @returns {String}
	*/
	id: function() {
		return this.call();
	},
	
	/**
	* Returns dialed number or caller id
	* @name VoxImplant.Call.number
	* @function
	* @returns {String}
	*/
	number: function() {
		return this.__number();
	},

	/**
	* Returns display name
	* @name VoxImplant.Call.displayName
	* @function
	* @returns {String}
	*/
	displayName: function() {
		return this.__displayName();
	},

	/**
	* Returns headers 
	* @name VoxImplant.Call.headers
	* @function
	* @returns {Object}
	*/
	headers: function() {
		return this.__headers();
	},

	/**
	* Returns information about the call's media state (active/inactive)
	* @name VoxImplant.Call.active
	* @function
	* @returns {Boolean}
	*/
	active: function() {
		return this.RTC()?this.zingayaAPI().isCallActive(this.call()):VoxImplant.Utils.swfMovie("voximplantSWF").isCallActive(this.call());
	},

	/**
	* Get call's current state
	* @name VoxImplant.Call.state
	* @function
	* @returns {String}
	*/
	state: function() {
		if (this.RTC()) return this.zingayaAPI().getCallState(this.call());
		else {
			var state = VoxImplant.Utils.swfMovie("voximplantSWF").getCallState(this.call()).toUpperCase();
			switch (state) {
				case "CONNECTING":
					state = VoxImplant.VI_CALL_STATE_ALERTING;
				break;
				case "CONNECTED_ON_HOLD":
					state = VoxImplant.VI_CALL_STATE_CONNECTED;
				break;
				case "DISCONNECTED":
				case "FAILED":
					state = VoxImplant.VI_CALL_STATE_ENDED;
				break;
			}
			return state;
		}
	},

	/**
	* Answer on incoming call
	* @name VoxImplant.Call.answer
	* @param {String} [customData] Set custom string associated with call session. It can be later obtained from Call History using HTTP API
	* @param {Object} [extraHeaders] Optional custom parameters (SIP headers) that should be sent after accepting incoming call. Parameter names must start with "X-" to be processed by application 
	* @function
	*/
	answer: function(customData, extraHeaders) {
		if (typeof customData != 'undefined') {
			if (typeof extraHeaders == 'undefined') extraHeaders = {};
			extraHeaders["VI-CallData"] = customData;
		}
		if (this.RTC()) { 
			if (this.zingayaAPI().getCallState(this.call()) != VoxImplant.VI_CALL_STATE_ALERTING) throw new Error("NO_INCOMING_CALL");
			this.zingayaAPI().answerCall(this.call(), extraHeaders);
		}
		else {
			extraHeaders = JSON.stringify(extraHeaders);
			BXIM.webrtc.phoneLog('Accepting call, id '+this.call());
			VoxImplant.Utils.swfMovie('voximplantSWF').accept(this.call(), extraHeaders);
		}
	},

	/**
	* @name VoxImplant.Call.decline
	* Reject incoming call
	* @param {Object} [extraHeaders] Optional custom parameters (SIP headers) that should be sent after rejecting incoming call. Parameter names must start with "X-" to be processed by application
	* @deprecated Since version 2. You should now use reject.
	* @function
	*/
	decline: function(extraHeaders) {
		if (this.RTC()) {
			if (this.zingayaAPI().getCallState(this.call()) != VoxImplant.VI_CALL_STATE_ALERTING) throw new Error("NO_INCOMING_CALL");
			this.zingayaAPI().rejectCall(this.call(), 486, extraHeaders);
		}
		else {
			extraHeaders = VoxImplant.Utils.stringifyExtraHeaders(extraHeaders);
			VoxImplant.Utils.swfMovie('voximplantSWF').reject(this.call(), extraHeaders);
		}
	},

	/**
	* @name VoxImplant.Call.reject
	* Reject incoming call
	* @param {Object} [extraHeaders] Optional custom parameters (SIP headers) that should be sent after rejecting incoming call. Parameter names must start with "X-" to be processed by application
	* @function
	*/
	reject: function(extraHeaders) {
		this.decline(extraHeaders);
	},

	/**
	* @name VoxImplant.Call.hangup
	* Hangup call
	* @param {Object} [extraHeaders] Optional custom parameters (SIP headers) that should be sent after disconnecting/cancelling call. Parameter names must start with "X-" to be processed by application 
	* @function
	*/
	hangup: function(extraHeaders) {
		if (this.RTC()) { 
			if (this.zingayaAPI().getCallState(this.call()) == VoxImplant.VI_CALL_STATE_CONNECTED || 
				this.zingayaAPI().getCallState(this.call()) == VoxImplant.VI_CALL_STATE_PROGRESSING) this.zingayaAPI().hangupCall(this.call(), extraHeaders); 
			else throw new Error("WRONG_CALL_STATE");
		}
		else {
			extraHeaders = VoxImplant.Utils.stringifyExtraHeaders(extraHeaders);
			VoxImplant.Utils.swfMovie('voximplantSWF').disconnectCall(this.call(), extraHeaders);
		}
	},

	/**
	* Send tone (DTMF)
	* @name VoxImplant.Call.sendTone
	* @param {String} key Send tone according to pressed key: 0-9 , * , #
	* @function
	*/
	sendTone: function(key) {
		if (key == '*') key = 10;
		else if (key == '#') key = 11; 
		else {
			key = parseInt(key);
			if (key < 0 || key > 9) throw new Error("WRONG_TONE_INPUT");
		}
		if (this.RTC()) { 
			if (this.zingayaAPI().getCallState(this.call()) != VoxImplant.VI_CALL_STATE_CONNECTED) throw new Error("CALL_NOT_CONNECTED");
			this.zingayaAPI().sendDigit(this.call(), key); 
		}
		else {
			VoxImplant.Utils.swfMovie('voximplantSWF').sendDTMF(key, this.call());
		}
	},

	/**
	* Mute sound
	* @name VoxImplant.Call.mutePlayback
	* @function
	*/
	mutePlayback: function() {
		if (this.RTC()) { 
			this.zingayaAPI().mutePlayback(true);
		}
		else {
			VoxImplant.Utils.swfMovie('voximplantSWF').muteIncomingAudio(this.call());
		}
	},

	/**
	* Mute microphone
	* @name VoxImplant.Call.muteMicrophone
	* @function
	*/
	muteMicrophone: function() {
		if (this.RTC()) { 
			this.zingayaAPI().muteMicrophone(true);
		}
		else {
			VoxImplant.Utils.swfMovie('voximplantSWF').muteOutgoingAudio(this.call());
		}
	},

	/**
	* Unmute sound
	* @name VoxImplant.Call.unmutePlayback
	* @function
	*/
	unmutePlayback: function() {
		if (this.RTC()) { 
			this.zingayaAPI().mutePlayback(false);
		}
		else {
			VoxImplant.Utils.swfMovie('voximplantSWF').unmuteIncomingAudio(this.call());
		}
	},

	/**
	* Unmute microphone
	* @name VoxImplant.Call.unmuteMicrophone
	* @function
	*/
	unmuteMicrophone: function() {
		if (this.RTC()) { 
			this.zingayaAPI().muteMicrophone(false);
		} else {
			VoxImplant.Utils.swfMovie('voximplantSWF').unmuteOutgoingAudio(this.call());
		}
	},

	sendVideo: function(flag) {
		
	},

	/**
	* Show/hide remote party video
	* @name VoxImplant.Call.showRemoteVideo
	* @param {Boolean} [flag=true] Show/hide - true/false
	* @function
	*/
	showRemoteVideo: function(flag) {
		if (typeof flag == "undefined") flag = true;
		if (this.RTC()) { 			
			document.getElementById(this.zingayaAPI().getVideoElementId(this.call())).style.display = (flag?"block":"none");
		} else {
			VoxImplant.Utils.swfMovie('voximplantSWF').showRemoteVideo(this.call(), flag);
		}
	},

	/**
	* Set remote video position
	* @name VoxImplant.Call.setRemoteVideoPosition
	* @param {Number} x Horizontal position (px)
	* @param {Number} y Vertical position (px)
	* @function
	*/
	setRemoteVideoPosition: function(x, y) {
		if (this.RTC()) { 
			throw new Error("Please use CSS to position '#voximplantcontainer' element");
		} else {
			VoxImplant.Utils.swfMovie('voximplantSWF').setRemoteViewPosition(this.call(), x, y);
		}
	},

	/**
	* Set remote video size
	* @name VoxImplant.Call.setRemoteVideoSize
	* @param {Number} width Width in pixels
	* @param {Number} height Height in pixels
	* @function
	*/
	setRemoteVideoSize: function(width, height) {
		if (this.RTC()) { 
			throw new Error("Please use CSS to set size of '#voximplantcontainer' element");
		} else {
			VoxImplant.Utils.swfMovie('voximplantSWF').setRemoteViewSize(this.call(), width, height);
		}
	},

	/**
	* Send Info (SIP INFO) message inside the call
	* @name VoxImplant.Call.sendInfo
	* @param {String} mimeType MIME type of the message
	* @param {String} body Message content
	* @param {Object} [extraHeaders] Optional headers to be passed with the message
	* @function
	*/
	sendInfo: function(mimeType, body, extraHeaders) {
		var type, subtype, i = mimeType.indexOf('/');
		if (i == -1) {
			type = "application";
			subtype = mimeType;
		} else {
			type = mimeType.substring(0, i);
			subtype = mimeType.substring(i+1);
		}
		if (this.RTC()) { 
			if (this.zingayaAPI().getCallState(this.call()) != VoxImplant.VI_CALL_STATE_CONNECTED) throw new Error("CALL_NOT_CONNECTED");
			this.zingayaAPI().sendSIPInfo(this.call(), type, subtype, body, extraHeaders); 	
		} else {
			extraHeaders = VoxImplant.Utils.stringifyExtraHeaders(extraHeaders);
			VoxImplant.Utils.swfMovie('voximplantSWF').sendSIPInfo(this.call(), type, subtype, body, extraHeaders);
		}
	},

	/**
	* Send text message
	* @name VoxImplant.Call.sendMessage
	* @param {String} msg Message text
	* @function
	*/
	sendMessage: function(msg) { 
		if (this.RTC()) { 
			if (this.zingayaAPI().getCallState(this.call()) != VoxImplant.VI_CALL_STATE_CONNECTED) throw new Error("CALL_NOT_CONNECTED");
			this.zingayaAPI().sendInstantMessage(this.call(), msg);
		} else {
			VoxImplant.Utils.swfMovie('voximplantSWF').sendMessage(this.call(), msg);
		}
	},

	/**
	* Set video settings
	* @name VoxImplant.Call.setVideoSettings
	* @param {VoxImplant.VideoSettings|VoxImplant.FlashVideoSettings} settings Video settings for current call
	* @param {Function} [successCallback] Called in WebRTC mode if video settings were applied successfully
	* @param {Function} [failedCallback] Called in WebRTC mode if video settings couldn't be applied
	* @function
	*/
	setVideoSettings: function(settings, successCallback, failedCallback) {
		if (this.RTC()) { 
			this.zingayaAPI().setConstraints(settings, successCallback, failedCallback, true);
		}
		else if (!this.useRTCOnly) {
			if (Object.prototype.toString.call(settings) == '[object Object]') settings = JSON.stringify(settings);
			VoxImplant.Utils.swfMovie('voximplantSWF').setVideoSettings(settings, this.call());
		}
	},

	/** @ignore */
	getIncomingStreamInfo: function() {
		if (this.RTC()) { 
			// make WebRTC stats interface
		}
		else if (!this.useRTCOnly) {
			return JSON.parse(VoxImplant.Utils.swfMovie('voximplantSWF').getIncomingStreamInfo(this.call()));
		}
	},

	/** @ignore */
	getOutgoingStreamInfo: function() {
		if (this.RTC()) { 
			// make WebRTC stats interface
		}
		else if (!this.useRTCOnly) {
			return JSON.parse(VoxImplant.Utils.swfMovie('voximplantSWF').getOutgoingStreamInfo(this.call()));
		}
	},

	/**
	* Return remote audio stream associated with the call (WebRTC only)
	* @name VoxImplant.Call.getRemoteAudioStream
	* @function
	* @ignore
	*/
	getRemoteAudioStream: function() {
		return this.zingayaAPI().getPeerConnection(this.call()).getRemoteAudioStream();
	},

	/**
	* Return remote video stream associated with the call (WebRTC only)
	* @name VoxImplant.Call.getRemoteVideoStream
	* @function
	* @ignore
	*/
	getRemoteVideoStream: function() {
		return this.zingayaAPI().getPeerConnection(this.call()).getRemoteVideoStream();
	},

	/**
	* Set local stream for the call (WebRTC only)
	* @name VoxImplant.Call.setLocalStream
	* @param {MediaStream} stream Media stream that will be sent to the remote party
	* @function
	* @ignore
	*/
	setLocalStream: function(stream) {
		this.zingayaAPI().getPeerConnection(this.call()).setLocalStream(stream);
	},

	/**
	* Return RTCPeerConnection associated with the call (WebRTC only)
	* @name VoxImplant.Call.getRTCPeerConnection
	* @function
	* @ignore
	*/
	getRTCPeerConnection: function() {
		return this.zingayaAPI().getPeerConnection(this.call()).getRTCPeerConnection();
	},

	/**
	* Returns HTML video element's id for the video call (WebRTC mode)
	* @name VoxImplant.Call.getVideoElementId
	* @function
	*/
	getVideoElementId: function() {
		if (this.RTC()) { 
			return this.zingayaAPI().getVideoElementId(this.call());
		}
	},

	/**
	* Returns HTML audio element's id for the audio call (WebRTC mode)
	* @name VoxImplant.Call.getAudioElementId
	* @function
	*/
	getAudioElementId: function() {
		if (this.RTC()) {
			return this.zingayaAPI().getAudioElementId(this.call());
		}
	},

	/**
	* Use specified audio recording device for call audio playback, use <a href="VoxImplant.Client.html#audioOutputs">audioOutputs</a> to get the list of available audio playback devices
	* @param {Number|String} id Id of the audio output 
	* @name VoxImplant.Call.useAudioOutput
	* @function
	*/
	useAudioOutput: function(id) {
		if (this.RTC()) { 
			var videoElementId = this.zingayaAPI().getVideoElementId(this.call()),
				audioElementId = this.zingayaAPI().getAudioElementId(this.call());

			if (document.getElementById(audioElementId).currentTime > 0) {
				document.getElementById(audioElementId).setSinkId(id);
			} else if (document.getElementById(videoElementId).currentTime > 0) {
				document.getElementById(videoElementId).setSinkId(id);
			} 
		}
	}
};


/**
* Register handler for specified event
* @param {Function} event Event class (i.e. {@link VoxImplant.CallEvents.Connected}). See {@link VoxImplant.CallEvents}
* @param {Function} handler Handler function. A single parameter is passed - object with event information
* @function
*/
VoxImplant.Call.prototype.addEventListener = function(event, handler) {
	if (typeof(this.eventListeners[event]) == 'undefined') this.eventListeners[event] = [];
	this.eventListeners[event].push(handler);
};

/**
* Remove handler for specified event
* @param {Function} event Event class (i.e. {@link VoxImplant.Events.SDKReady}). See {@link VoxImplant.Events}
* @param {Function} handler Handler function
* @function
*/
VoxImplant.Call.prototype.removeEventListener = function(event, handler) {
	if (typeof(this.eventListeners[event]) == 'undefined') return;
	for (var i=0;i<this.eventListeners[event].length;i++)
	{
		if (this.eventListeners[event][i] == handler)
		{
			this.eventListeners[event].splice(i,1);
			break;
		}
	}
};

})(VoxImplant);

/**
* @namespace
* @name VoxImplant
*/
(function (VoxImplant, undefined) {
/**
* Client class used to control platform functions. Can't be instantiatied directly (singleton), please use <a href="VoxImplant.html#VoxImplant_getInstance">VoxImplant.getInstance</a> to get the class instance.
* @class
* @name VoxImplant.Client
* @group default,IM/Presence
*/
VoxImplant.Client = function() {
	
	this.config = null;
	this.calls = [];
	var Call = VoxImplant.Call;
	delete VoxImplant.Call;
	var _connected = false;
	this.eventListeners = {};
	this.progressToneScript = {
		US: "440@-19,480@-19;*(2/4/1+2)",
		RU: "425@-19;*(1/3/1)"
	};
	this.playingNow = false;	
	this.serversList = [];
	var _vol = 100;
	this.audioSourcesList = [];
	this.videoSourcesList = [];
	this.audioOutputsList = [];

	/**
	* @ignore
	*/
	this.deviceEnumAPI = function() {
		if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) navigator.mediaDevices.enumerateDevices().then(this.gotSources);
		else if ((typeof MediaStreamTrack != "undefined") && (typeof MediaStreamTrack.getSources != "undefined")) MediaStreamTrack.getSources(this.gotSources);
	},
	/**
	 @ignore 
	 */
	this.gotSources = function(sourceInfos) {
		if (this.audioSourcesList.length !== 0) this.audioSourcesList = [];
		if (this.videoSourcesList.length !== 0) this.videoSourcesList = [];
		if (this.audioOutputsList.length !== 0) this.audioOutputsList = [];
		var v = 0, a = 0, p = 0;
		for (var i = 0; i != sourceInfos.length; ++i) {
		    var sourceInfo = sourceInfos[i];
		    if (sourceInfo.kind === 'audio' || sourceInfo.kind === 'audioinput') {
		    	a++;
		    	this.audioSourcesList.push({id: sourceInfo.id || sourceInfo.deviceId, name: sourceInfo.label || 'Audio recording device ' + a});
		    } else if (sourceInfo.kind === 'video' || sourceInfo.kind === 'videoinput') {
		    	v++;
		    	this.videoSourcesList.push({id: sourceInfo.id || sourceInfo.deviceId, name: sourceInfo.label || 'Video recording device ' + v});
		    } else if (sourceInfo.kind === 'audiooutput') {
		    	p++;
		    	this.audioOutputsList.push({id: sourceInfo.id || sourceInfo.deviceId, name: sourceInfo.label || 'Audio playback device ' + p});
		    }
		}
		this.dispatchEvent({name:'SourcesInfoUpdated'});
	}.bind(this),

	/** 
	@ignore 
	*/
	this.__init = function(config) {
		if (this.config !== null) throw("VoxImplant.Client has been already initialized");
		this.config = typeof config !== 'undefined' ? config : {};
		if (this.config.useFlashOnly === true) this.useFlashOnly = true;
		else this.useFlashOnly = false;
		if (this.config.useRTCOnly === true) this.useRTCOnly = true;
		else this.useRTCOnly = false;
		this.RTCsupported = false;
		if (this.config.micRequired !== false) this.micRequired = true;
		else this.micRequired = false;
		if (this.config.videoSupport !== true) this.videoSupport = false;
		else this.videoSupport = true;
		if (typeof this.config.videoConstraints != 'undefined') this.videoConstraints = this.config.videoConstraints;
		else this.videoConstraints = null;
		if (typeof this.config.swfContainer != 'undefined') this.swfContainer = this.config.swfContainer;
		else this.swfContainer = null;
		if (typeof this.config.progressToneCountry != 'undefined') this.progressToneCountry = this.config.progressToneCountry;
		else this.progressToneCountry = "US";
		if (this.config.progressTone !== true) this.progressTone = false;
		else this.progressTone = true;
		if (this.config.showFlashSettings === true) this.showFlashSettings = true;
		else this.showFlashSettings = false;
		if (typeof this.config.serverIp != 'undefined') this.serverIp = this.config.serverIp;
		if (typeof this.config.swfURL != 'undefined') this.swfURL = this.config.swfURL;
		if (typeof this.config.showDebugInfo != "undefined") this.showDebugInfo = this.config.showDebugInfo;
		else this.showDebugInfo = true;
		if (typeof this.config.imXSSprotection != "undefined") this.imXSSprotection = this.config.imXSSprotection;
		else this.imXSSprotection = true;
		if (typeof this.config.imAutoReconnect != "undefined") this.imAutoReconnect = this.config.imAutoReconnect;
		else this.imAutoReconnect = true;
		if (typeof this.config.imReconnectInterval != "undefined") this.imReconnectInterval = (this.config.imReconnectInterval>=2000?this.config.imReconnectInterval:2000);
		else this.imReconnectInterval = 3000;
		this.imReconnectTs = 0;
		if (this.config.showWarnings !== false) this.showWarnings = true;
		else this.showWarnings = false;
		if (typeof this.config.videoContainerId === "string") this.videoContainerId = this.config.videoContainerId;
		if (this.config.connectivityCheck === false) this.connectivityCheck = false;
		else this.connectivityCheck = true;

		// Show warning about getUserMedia w/o https
		if (window.location.hostname != "127.0.0.1" && window.location.hostname != "localhost" && window.location.protocol != "https:") {
			if (typeof console.error != "undefined" && this.showWarnings) console.error("WARNING: getUserMedia() is deprecated on insecure origins, and support will be removed in the future. You should consider switching your application to a secure origin, such as HTTPS. See https://goo.gl/rStTGz for more details.");
		} 
		
		/* Check if WebRTC is supported */
		if (typeof(webkitRTCPeerConnection) != 'undefined' || (typeof(mozRTCPeerConnection) != 'undefined') || (typeof(RTCPeerConnection) != 'undefined')) {
			if (typeof(mozRTCPeerConnection) != 'undefined') {
				try {
					var testPC = new mozRTCPeerConnection({"iceServers": []});
					this.RTCsupported = true;
				} catch (e) { /* not enabled */ }
			} else this.RTCsupported = true;
		}
		
		var ts;
		if (this.RTCsupported && !this.useFlashOnly) {
			if (window.location.href.match(/^file\:\/{3}.*$/g) != null) {
				if (typeof console.error != "undefined" && this.showWarnings) console.error("WebRTC requires application to be loaded from a web server");
			}
			this.zingayaAPI = new VoxImplant.ZingayaAPI(this.videoSupport, this.micRequired);
			delete VoxImplant.ZingayaAPI;

			this.zingayaAPI.setRemoteSinksContainerId(this.videoContainerId);
		
			this.zingayaAPI.onConnectionEstablished = function() {
				this.connectionState(true);
				this.dispatchEvent({name:'ConnectionEstablished'});
			}.bind(this);
		
			this.zingayaAPI.onConnectionFailed = function(msg) {
				this.connectionState(false);
				if (this.serversList.length > 1 && typeof(this.serverIp) == "undefined") {
					this.serversList.splice(0, 1);
					this.connectTo(this.serversList[0], true);
				} else this.dispatchEvent({name:'ConnectionFailed', message:msg});
			}.bind(this);
		
			this.zingayaAPI.onConnectionClosed = function() {
				this.connectionState(false);
				this.__cleanup();
				this.dispatchEvent({name:'ConnectionClosed'});
				if (this.progressTone) this.stopProgressTone();
			}.bind(this);
		
			this.zingayaAPI.onLoginSuccessful = function(displayName, options) {
				this.dispatchEvent({name:'AuthResult', result: true, displayName: displayName, options: options});
			}.bind(this);
		
			this.zingayaAPI.onLoginFailed = function(obj) {
				this.dispatchEvent({name:'AuthResult', result: false, code:obj.errorCode, key:obj.oneTimeKey});
			}.bind(this);
		
			this.zingayaAPI.onCallConnected = function(call_id, headers) {
				this.getCall(call_id).dispatchEvent({name:'Connected', call: this.getCall(call_id), headers: headers});
				if (this.progressTone) this.stopProgressTone();
			}.bind(this);
		
			this.zingayaAPI.onCallEnded = function(call_id, headers) {
				this.getCall(call_id).dispatchEvent({name:'Disconnected', call: this.getCall(call_id), headers: headers});
				this.removeCall(call_id);
				if (this.progressTone) this.stopProgressTone();
			}.bind(this);
		
			this.zingayaAPI.onCallFailed = function(call_id, code, reason, headers) {

				this.getCall(call_id).dispatchEvent({name:'Failed', call: this.getCall(call_id), code:code, reason:reason, headers: headers});
				this.removeCall(call_id);
				if (this.progressTone) this.stopProgressTone();
			}.bind(this);
		
			this.zingayaAPI.onMediaAccessGranted = function(stream) {
				this.deviceEnumAPI();
				if (this.micRequired) this.zingayaAPI.connectTo(this.host, "platform", null, null, this.connectivityCheck); 
				this.dispatchEvent({name:'MicAccessResult', result: true, stream: stream});
			}.bind(this);
		
			this.zingayaAPI.onMediaAccessRejected = function(error) {
				this.dispatchEvent({name:'MicAccessResult', result: false, reason: error});
			}.bind(this);
		
			this.zingayaAPI.onIncomingCall = function(call_id, remoteUserName, remoteDisplayName, headers) {
				var newCall = new Call(call_id, remoteUserName, remoteDisplayName, headers, true, this.zingayaAPI);
				if (this.calls.length > 0) this.zingayaAPI.setCallActive(call_id, false);
				this.calls.push(newCall);
				this.dispatchEvent({name:'IncomingCall', call: newCall, headers: headers});	
			}.bind(this);
		
			this.zingayaAPI.onCallRinging = function(call_id) {
				this.getCall(call_id).dispatchEvent({name:'ProgressToneStart', call: this.getCall(call_id)});
				if (this.progressTone) this.playProgressTone();
			}.bind(this);
		
			this.zingayaAPI.onCallMediaStarted = function(call_id) {
				this.getCall(call_id).dispatchEvent({name:'ProgressToneStop', call: this.getCall(call_id)});
				if (this.progressTone) this.stopProgressTone();	
			}.bind(this); 

			this.zingayaAPI.onRemoteScreenCaptureStarted = function(call_id, element_id) {
				this.getCall(call_id).dispatchEvent({name:'RemoteScreenCaptureStarted', call: this.getCall(call_id), videoElementId: element_id});
			}.bind(this);
		
			this.zingayaAPI.onInstantMessageReceived = function(call_id, body) {
				this.getCall(call_id).dispatchEvent({name:'MessageReceived', call: this.getCall(call_id), text:body});	
			}.bind(this);
		
			this.zingayaAPI.onSIPInfoReceived = function(call_id, mime, body, headers) {
				this.getCall(call_id).dispatchEvent({name:'InfoReceived', call: this.getCall(call_id), mimeType:mime, body:body, headers:headers});
			}.bind(this);

			this.zingayaAPI.onTransferComplete = function(call_id) {
				this.getCall(call_id).dispatchEvent({name:'TransferComplete', call: this.getCall(call_id)});
			}.bind(this);

			this.zingayaAPI.onTransferFailed = function(call_id) {
				this.getCall(call_id).dispatchEvent({name:'TransferFailed', call: this.getCall(call_id)});
			}.bind(this);

			this.zingayaAPI.onNetStatsReceived = function(stats) {
				this.dispatchEvent({name:'NetStatsReceived', stats: stats});
			}.bind(this);

			this.zingayaAPI.onRTCStatsCollected = function(call_id, stats) {
				if (this.getCall(call_id) != null) this.getCall(call_id).dispatchEvent({name:'RTCStatsReceived', stats: stats});
			}.bind(this);

			this.zingayaAPI.onHandleRoster = function(roster) {
				this.dispatchEvent({name:'RosterReceived', roster: roster});
			}.bind(this);

			this.zingayaAPI.onHandleRosterItem = function(id, resource, type, displayName, groups) {
				this.dispatchEvent({name:'RosterItemChange', id: id, resource: resource, type: type, displayName: displayName, groups: groups});
			}.bind(this);			

			this.zingayaAPI.onHandleRosterPresence = function(id, resource, presence, message) {
				this.dispatchEvent({name:'RosterPresenceUpdate', id: id, resource: resource, presence: presence, message: message});
			}.bind(this);

			this.zingayaAPI.onHandleMessage = function(id, resource, content, message_id, to) {
				if (this.imXSSprotection) content = VoxImplant.Utils.filterXSS(content);
				this.dispatchEvent({name:'MessageReceived', id: id, resource: resource, content: content, message_id: message_id, to: to});
			}.bind(this);

			this.zingayaAPI.onHandleSelfPresence = function(id, resource, presence, message) {
				this.dispatchEvent({name:'PresenceUpdate', id: id, resource: resource, presence: presence, message: message});
			}.bind(this);

			this.zingayaAPI.onHandleChatState = function(id, resource, state) {
				this.dispatchEvent({name:'ChatStateUpdate', id: id, resource: resource, state: state});
			}.bind(this);

			this.zingayaAPI.onHandleMessageEvent = function(id, resource, type, message_id) {
				this.dispatchEvent({name:'MessageStatus', id: id, resource: resource, type: type, message_id: message_id});
			}.bind(this);

			this.zingayaAPI.onHandleMessageModified = function(id, message_id, content, to) {
				if (this.imXSSprotection) content = VoxImplant.Utils.filterXSS(content);
				this.dispatchEvent({name:'MessageModified', id: id, message_id: message_id, content: content, to: to});
			}.bind(this);

			this.zingayaAPI.onHandleMessageModificationError = function(to, message_id, code) {
				this.dispatchEvent({name:'MessageNotModified', to: to, message_id: message_id, code: code});
			}.bind(this);

			this.zingayaAPI.onHandleMessageRemoved = function(id, message_id, to) {
				this.dispatchEvent({name:'MessageRemoved', id: id, message_id: message_id, to: to});
			}.bind(this);		

			this.zingayaAPI.onHandleSubscription = function(id, resource, type, message) {
				this.dispatchEvent({name:'SubscriptionRequest', id: id, resource: resource, type: type, message: message});
			}.bind(this);			

			this.zingayaAPI.onCallRemoteFunctionError = function(method, params, code, description) {
				this.dispatchEvent({name:'IMError', errorType:'RemoteFunctionError', errorData: {method: method, params: params, code: code, description: description} });
			}.bind(this);

			this.zingayaAPI.onIMError = function(type, code, description) {
				this.dispatchEvent({name:'IMError', errorType:'Error', errorData: {type: type, code: code, description: description} });
			}.bind(this);

			this.zingayaAPI.onUCConnected = function(id) {
				if (this.imAutoReconnect === true) clearInterval(this.imReconnectTs);
				this.dispatchEvent({name:'UCConnected', id: id});
			}.bind(this);

			this.zingayaAPI.onUCDisconnected = function() {				
				if (this.imAutoReconnect === true) {
					clearInterval(this.imReconnectTs);
        			this.imReconnectTs = setInterval(function() {
        				this.zingayaAPI.ucReconnect();        				
        			}.bind(this), this.imReconnectInterval);
        		}
        		this.dispatchEvent({name:'UCDisconnected'});
			}.bind(this);

			this.zingayaAPI.onIMRosterError = function(code) {
				this.dispatchEvent({name:'IMError', errorType:'RosterError', errorData: {code: code} });
			}.bind(this);

			this.zingayaAPI.onMUCError = function(room, operation, code, text) {
				this.dispatchEvent({name:'ChatRoomError', room: room, operation: operation, code: code, text: text});
			}.bind(this);

			this.zingayaAPI.onMUCRoomCreation = function(room) {
				this.dispatchEvent({name:'ChatRoomCreated', room: room});
			}.bind(this);

			this.zingayaAPI.onMUCSubject = function(room, id, resource, subject) {
				this.dispatchEvent({name:'ChatRoomSubjectChange', room: room, id: id, resource: resource, subject: subject});
			}.bind(this);

			this.zingayaAPI.onMUCInfo = function(room, features, name, info) {
				this.dispatchEvent({name:'ChatRoomInfo', room: room, features: features, room_name: name, info: info});
			}.bind(this);

			this.zingayaAPI.onMUCMessage = function(room, message_id, private_message, timestamp, from, resource, content) {
				if (this.imXSSprotection) content = VoxImplant.Utils.filterXSS(content);
				this.dispatchEvent({name:'ChatRoomMessageReceived', room: room, message_id: message_id, private_message: private_message, timestamp: timestamp, from: from, resource: resource, content: content});
			}.bind(this);

			this.zingayaAPI.onMUCInvitation = function(room, from, reason, body, password, cont) {
				this.dispatchEvent({name:'ChatRoomInvitation', room: room, from: from, reason: reason, body: body, password: password, cont: cont});
			}.bind(this);

			this.zingayaAPI.onMUCInviteDecline = function(room, invitee, reason) {
				this.dispatchEvent({name:'ChatRoomInviteDeclined', room: room, invitee: invitee, reason: reason});
			}.bind(this);

			this.zingayaAPI.onMUCParticipantPresence = function(room, participant, presence, message) {
				this.dispatchEvent({name:'ChatRoomPresenceUpdate', room: room, participant: participant, presence: presence, message: message});
			}.bind(this);

			this.zingayaAPI.onMUCNewParticipant = function(room, participant, displayName) {
				this.dispatchEvent({name:'ChatRoomNewParticipant', room: room, participant: participant, displayName: displayName});
			}.bind(this);

			this.zingayaAPI.onMUCParticipantExit = function(room, participant) {
				this.dispatchEvent({name:'ChatRoomParticipantExit', room: room, participant: participant});
			}.bind(this);

			this.zingayaAPI.onMUCOperationResult = function(room, operation, result) {
				this.dispatchEvent({name:'ChatRoomOperation', room: room, operation: operation, result: result});
			}.bind(this);

			this.zingayaAPI.onMUCRooms = function(rooms) {
				this.dispatchEvent({name:'ChatRoomsDataReceived', rooms: rooms});				
			}.bind(this);   

			this.zingayaAPI.onMUCParticipants = function(room, list) {
				this.dispatchEvent({name:'ChatRoomParticipants', room: room, participants: list});
			}.bind(this);

			this.zingayaAPI.onMUCBanList = function(room, list) {
				this.dispatchEvent({name:'ChatRoomBanList', room: room, participants: list});
			}.bind(this);

			this.zingayaAPI.onMUCHistory = function(room, message_id, list) {
				if (this.imXSSprotection) {
					list.forEach(function(message){
						message.body = VoxImplant.Utils.filterXSS(message.body);
					});					
				}
				this.dispatchEvent({name:'ChatRoomHistoryReceived', room: room, message_id: message_id, messages: list});
			}.bind(this);

			this.zingayaAPI.onMUCMessageModified = function(room, private_message, message_id, timestamp, from, resource, content) {
				if (this.imXSSprotection) content = VoxImplant.Utils.filterXSS(content);
				this.dispatchEvent({name:'ChatRoomMessageModified', room: room, private_message: private_message, message_id: message_id, timestamp: timestamp, from: from, resource: resource, content: content});
			}.bind(this);

			this.zingayaAPI.onMUCMessageModificationError = function(room, private_message, message_id, code) {
				this.dispatchEvent({name:'ChatRoomMessageNotModified', room: room, private_message: private_message, message_id: message_id, code: code});
			}.bind(this);

			this.zingayaAPI.onMUCMessageRemoved = function(room, private_message, message_id, timestamp, from, resource) {
				this.dispatchEvent({name:'ChatRoomMessageRemoved', room: room, private_message: private_message, message_id: message_id, timestamp: timestamp, from: from, resource: resource});
			}.bind(this);

			this.zingayaAPI.onMUCChatState = function(room, from, resource, state) {
				this.dispatchEvent({name:'ChatRoomStateUpdate', room: room, from: from, resource: resource, state: state});				
			}.bind(this);

			this.zingayaAPI.onHistory = function(id, message_id, messages) {
				if (this.imXSSprotection) {
					messages.forEach(function(message){
						message.body = VoxImplant.Utils.filterXSS(message.body);
					});					
				}
				this.dispatchEvent({name:'ChatHistoryReceived', id: id, message_id: message_id, messages: messages});
			}.bind(this);

			this.zingayaAPI.onCallICETimeout = function(call_id) {
				if(typeof(this.getCall(call_id))!="undefined"&&this.getCall(call_id)!=null)
					this.getCall(call_id).dispatchEvent({name:'ICETimeout', call: this.getCall(call_id)});
				else
					BXIM.webrtc.phoneLog('ICETimeout on ended call '+call_id);
			}.bind(this);

			this.zingayaAPI.writeLog = function(message) {
				if (typeof this.writeLog == "function") this.writeLog(message);
				else {
          var dateTimeOptions = {year: "numeric", month: "numeric", day: "numeric", timeZone: "UTC"};
					if (this.showDebugInfo) BXIM.webrtc.phoneLog("VI WebRTC: "+new Date().toLocaleTimeString("en-US", dateTimeOptions) + " "+message);
				}
			}.bind(this);

			this.zingayaAPI.writeTrace = function(message) {
				if (typeof this.writeTrace == "function") this.writeTrace(message);
				else {
          var dateTimeOptions = {year: "numeric", month: "numeric", day: "numeric", timeZone: "UTC"};
					if (this.showDebugInfo) BXIM.webrtc.phoneLog("VI WebRTC: "+new Date().toLocaleTimeString("en-US", dateTimeOptions) + " "+message);
				}
			}.bind(this);
 		
			checkDOMReady = function() {
				if (typeof document != 'undefined') {
					clearInterval(ts);
					this.dispatchEvent({name:'SDKReady', version: VoxImplant.version});
					this.deviceEnumAPI();
				}
			};
		
			ts = setInterval(checkDOMReady.bind(this), 100);
		} else if (!this.useRTCOnly) {
			// initialize VoxImplant Flash API
			/* EMBED MINIFIED SWFOBJECT */ 
			var swfobject=function(){var D="undefined",r="object",S="Shockwave Flash",W="ShockwaveFlash.ShockwaveFlash",q="application/x-shockwave-flash",R="SWFObjectExprInst",x="onreadystatechange",O=window,j=document,t=navigator,T=false,U=[h],o=[],N=[],I=[],l,Q,E,B,J=false,a=false,n,G,m=true,M=function(){var aa=typeof j.getElementById!=D&&typeof j.getElementsByTagName!=D&&typeof j.createElement!=D,ah=t.userAgent.toLowerCase(),Y=t.platform.toLowerCase(),ae=Y?/win/.test(Y):/win/.test(ah),ac=Y?/mac/.test(Y):/mac/.test(ah),af=/webkit/.test(ah)?parseFloat(ah.replace(/^.*webkit\/(\d+(\.\d+)?).*$/,"$1")):false,X=!+"\v1",ag=[0,0,0],ab=null;if(typeof t.plugins!=D&&typeof t.plugins[S]==r){ab=t.plugins[S].description;if(ab&&!(typeof t.mimeTypes!=D&&t.mimeTypes[q]&&!t.mimeTypes[q].enabledPlugin)){T=true;X=false;ab=ab.replace(/^.*\s+(\S+\s+\S+$)/,"$1");ag[0]=parseInt(ab.replace(/^(.*)\..*$/,"$1"),10);ag[1]=parseInt(ab.replace(/^.*\.(.*)\s.*$/,"$1"),10);ag[2]=/[a-zA-Z]/.test(ab)?parseInt(ab.replace(/^.*[a-zA-Z]+(.*)$/,"$1"),10):0}}else{if(typeof O.ActiveXObject!=D){try{var ad=new ActiveXObject(W);if(ad){ab=ad.GetVariable("$version");if(ab){X=true;ab=ab.split(" ")[1].split(",");ag=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}}catch(Z){}}}return{w3:aa,pv:ag,wk:af,ie:X,win:ae,mac:ac}}(),k=function(){if(!M.w3){return}if((typeof j.readyState!=D&&j.readyState=="complete")||(typeof j.readyState==D&&(j.getElementsByTagName("body")[0]||j.body))){f()}if(!J){if(typeof j.addEventListener!=D){j.addEventListener("DOMContentLoaded",f,false)}if(M.ie&&M.win){j.attachEvent(x,function(){if(j.readyState=="complete"){j.detachEvent(x,arguments.callee);f()}});if(O==top){(function(){if(J){return}try{j.documentElement.doScroll("left")}catch(X){setTimeout(arguments.callee,0);return}f()})()}}if(M.wk){(function(){if(J){return}if(!/loaded|complete/.test(j.readyState)){setTimeout(arguments.callee,0);return}f()})()}s(f)}}();function f(){if(J){return}try{var Z=j.getElementsByTagName("body")[0].appendChild(C("span"));Z.parentNode.removeChild(Z)}catch(aa){return}J=true;var X=U.length;for(var Y=0;Y<X;Y++){U[Y]()}}function K(X){if(J){X()}else{U[U.length]=X}}function s(Y){if(typeof O.addEventListener!=D){O.addEventListener("load",Y,false)}else{if(typeof j.addEventListener!=D){j.addEventListener("load",Y,false)}else{if(typeof O.attachEvent!=D){i(O,"onload",Y)}else{if(typeof O.onload=="function"){var X=O.onload;O.onload=function(){X();Y()}}else{O.onload=Y}}}}}function h(){if(T){V()}else{H()}}function V(){var X=j.getElementsByTagName("body")[0];var aa=C(r);aa.setAttribute("type",q);var Z=X.appendChild(aa);if(Z){var Y=0;(function(){if(typeof Z.GetVariable!=D){var ab=Z.GetVariable("$version");if(ab){ab=ab.split(" ")[1].split(",");M.pv=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}else{if(Y<10){Y++;setTimeout(arguments.callee,10);return}}X.removeChild(aa);Z=null;H()})()}else{H()}}function H(){var ag=o.length;if(ag>0){for(var af=0;af<ag;af++){var Y=o[af].id;var ab=o[af].callbackFn;var aa={success:false,id:Y};if(M.pv[0]>0){var ae=c(Y);if(ae){if(F(o[af].swfVersion)&&!(M.wk&&M.wk<312)){w(Y,true);if(ab){aa.success=true;aa.ref=z(Y);ab(aa)}}else{if(o[af].expressInstall&&A()){var ai={};ai.data=o[af].expressInstall;ai.width=ae.getAttribute("width")||"0";ai.height=ae.getAttribute("height")||"0";if(ae.getAttribute("class")){ai.styleclass=ae.getAttribute("class")}if(ae.getAttribute("align")){ai.align=ae.getAttribute("align")}var ah={};var X=ae.getElementsByTagName("param");var ac=X.length;for(var ad=0;ad<ac;ad++){if(X[ad].getAttribute("name").toLowerCase()!="movie"){ah[X[ad].getAttribute("name")]=X[ad].getAttribute("value")}}P(ai,ah,Y,ab)}else{p(ae);if(ab){ab(aa)}}}}}else{w(Y,true);if(ab){var Z=z(Y);if(Z&&typeof Z.SetVariable!=D){aa.success=true;aa.ref=Z}ab(aa)}}}}}function z(aa){var X=null;var Y=c(aa);if(Y&&Y.nodeName=="OBJECT"){if(typeof Y.SetVariable!=D){X=Y}else{var Z=Y.getElementsByTagName(r)[0];if(Z){X=Z}}}return X}function A(){return !a&&F("6.0.65")&&(M.win||M.mac)&&!(M.wk&&M.wk<312)}function P(aa,ab,X,Z){a=true;E=Z||null;B={success:false,id:X};var ae=c(X);if(ae){if(ae.nodeName=="OBJECT"){l=g(ae);Q=null}else{l=ae;Q=X}aa.id=R;if(typeof aa.width==D||(!/%$/.test(aa.width)&&parseInt(aa.width,10)<310)){aa.width="310"}if(typeof aa.height==D||(!/%$/.test(aa.height)&&parseInt(aa.height,10)<137)){aa.height="137"}j.title=j.title.slice(0,47)+" - Flash Player Installation";var ad=M.ie&&M.win?"ActiveX":"PlugIn",ac="MMredirectURL="+O.location.toString().replace(/&/g,"%26")+"&MMplayerType="+ad+"&MMdoctitle="+j.title;if(typeof ab.flashvars!=D){ab.flashvars+="&"+ac}else{ab.flashvars=ac}if(M.ie&&M.win&&ae.readyState!=4){var Y=C("div");X+="SWFObjectNew";Y.setAttribute("id",X);ae.parentNode.insertBefore(Y,ae);ae.style.display="none";(function(){if(ae.readyState==4){ae.parentNode.removeChild(ae)}else{setTimeout(arguments.callee,10)}})()}u(aa,ab,X)}}function p(Y){if(M.ie&&M.win&&Y.readyState!=4){var X=C("div");Y.parentNode.insertBefore(X,Y);X.parentNode.replaceChild(g(Y),X);Y.style.display="none";(function(){if(Y.readyState==4){Y.parentNode.removeChild(Y)}else{setTimeout(arguments.callee,10)}})()}else{Y.parentNode.replaceChild(g(Y),Y)}}function g(ab){var aa=C("div");if(M.win&&M.ie){aa.innerHTML=ab.innerHTML}else{var Y=ab.getElementsByTagName(r)[0];if(Y){var ad=Y.childNodes;if(ad){var X=ad.length;for(var Z=0;Z<X;Z++){if(!(ad[Z].nodeType==1&&ad[Z].nodeName=="PARAM")&&!(ad[Z].nodeType==8)){aa.appendChild(ad[Z].cloneNode(true))}}}}}return aa}function u(ai,ag,Y){var X,aa=c(Y);if(M.wk&&M.wk<312){return X}if(aa){if(typeof ai.id==D){ai.id=Y}if(M.ie&&M.win){var ah="";for(var ae in ai){if(ai[ae]!=Object.prototype[ae]){if(ae.toLowerCase()=="data"){ag.movie=ai[ae]}else{if(ae.toLowerCase()=="styleclass"){ah+=' class="'+ai[ae]+'"'}else{if(ae.toLowerCase()!="classid"){ah+=" "+ae+'="'+ai[ae]+'"'}}}}}var af="";for(var ad in ag){if(ag[ad]!=Object.prototype[ad]){af+='<param name="'+ad+'" value="'+ag[ad]+'" />'}}aa.outerHTML='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'+ah+">"+af+"</object>";N[N.length]=ai.id;X=c(ai.id)}else{var Z=C(r);Z.setAttribute("type",q);for(var ac in ai){if(ai[ac]!=Object.prototype[ac]){if(ac.toLowerCase()=="styleclass"){Z.setAttribute("class",ai[ac])}else{if(ac.toLowerCase()!="classid"){Z.setAttribute(ac,ai[ac])}}}}for(var ab in ag){if(ag[ab]!=Object.prototype[ab]&&ab.toLowerCase()!="movie"){e(Z,ab,ag[ab])}}aa.parentNode.replaceChild(Z,aa);X=Z}}return X}function e(Z,X,Y){var aa=C("param");aa.setAttribute("name",X);aa.setAttribute("value",Y);Z.appendChild(aa)}function y(Y){var X=c(Y);if(X&&X.nodeName=="OBJECT"){if(M.ie&&M.win){X.style.display="none";(function(){if(X.readyState==4){b(Y)}else{setTimeout(arguments.callee,10)}})()}else{X.parentNode.removeChild(X)}}}function b(Z){var Y=c(Z);if(Y){for(var X in Y){if(typeof Y[X]=="function"){Y[X]=null}}Y.parentNode.removeChild(Y)}}function c(Z){var X=null;try{X=j.getElementById(Z)}catch(Y){}return X}function C(X){return j.createElement(X)}function i(Z,X,Y){Z.attachEvent(X,Y);I[I.length]=[Z,X,Y]}function F(Z){var Y=M.pv,X=Z.split(".");X[0]=parseInt(X[0],10);X[1]=parseInt(X[1],10)||0;X[2]=parseInt(X[2],10)||0;return(Y[0]>X[0]||(Y[0]==X[0]&&Y[1]>X[1])||(Y[0]==X[0]&&Y[1]==X[1]&&Y[2]>=X[2]))?true:false}function v(ac,Y,ad,ab){if(M.ie&&M.mac){return}var aa=j.getElementsByTagName("head")[0];if(!aa){return}var X=(ad&&typeof ad=="string")?ad:"screen";if(ab){n=null;G=null}if(!n||G!=X){var Z=C("style");Z.setAttribute("type","text/css");Z.setAttribute("media",X);n=aa.appendChild(Z);if(M.ie&&M.win&&typeof j.styleSheets!=D&&j.styleSheets.length>0){n=j.styleSheets[j.styleSheets.length-1]}G=X}if(M.ie&&M.win){if(n&&typeof n.addRule==r){n.addRule(ac,Y)}}else{if(n&&typeof j.createTextNode!=D){n.appendChild(j.createTextNode(ac+" {"+Y+"}"))}}}function w(Z,X){if(!m){return}var Y=X?"visible":"hidden";if(J&&c(Z)){c(Z).style.visibility=Y}else{v("#"+Z,"visibility:"+Y)}}function L(Y){var Z=/[\\\"<>\.;]/;var X=Z.exec(Y)!=null;return X&&typeof encodeURIComponent!=D?encodeURIComponent(Y):Y}var d=function(){if(M.ie&&M.win){window.attachEvent("onunload",function(){var ac=I.length;for(var ab=0;ab<ac;ab++){I[ab][0].detachEvent(I[ab][1],I[ab][2])}var Z=N.length;for(var aa=0;aa<Z;aa++){y(N[aa])}for(var Y in M){M[Y]=null}M=null;for(var X in swfobject){swfobject[X]=null}swfobject=null})}}();return{registerObject:function(ab,X,aa,Z){if(M.w3&&ab&&X){var Y={};Y.id=ab;Y.swfVersion=X;Y.expressInstall=aa;Y.callbackFn=Z;o[o.length]=Y;w(ab,false)}else{if(Z){Z({success:false,id:ab})}}},getObjectById:function(X){if(M.w3){return z(X)}},embedSWF:function(ab,ah,ae,ag,Y,aa,Z,ad,af,ac){var X={success:false,id:ah};if(M.w3&&!(M.wk&&M.wk<312)&&ab&&ah&&ae&&ag&&Y){w(ah,false);K(function(){ae+="";ag+="";var aj={};if(af&&typeof af===r){for(var al in af){aj[al]=af[al]}}aj.data=ab;aj.width=ae;aj.height=ag;var am={};if(ad&&typeof ad===r){for(var ak in ad){am[ak]=ad[ak]}}if(Z&&typeof Z===r){for(var ai in Z){if(typeof am.flashvars!=D){am.flashvars+="&"+ai+"="+Z[ai]}else{am.flashvars=ai+"="+Z[ai]}}}if(F(Y)){var an=u(aj,am,ah);if(aj.id==ah){w(ah,true)}X.success=true;X.ref=an}else{if(aa&&A()){aj.data=aa;P(aj,am,ah,ac);return}else{w(ah,true)}}if(ac){ac(X)}})}else{if(ac){ac(X)}}},switchOffAutoHideShow:function(){m=false},ua:M,getFlashPlayerVersion:function(){return{major:M.pv[0],minor:M.pv[1],release:M.pv[2]}},hasFlashPlayerVersion:F,createSWF:function(Z,Y,X){if(M.w3){return u(Z,Y,X)}else{return undefined}},showExpressInstall:function(Z,aa,X,Y){if(M.w3&&A()){P(Z,aa,X,Y)}},removeSWF:function(X){if(M.w3){y(X)}},createCSS:function(aa,Z,Y,X){if(M.w3){v(aa,Z,Y,X)}},addDomLoadEvent:K,addLoadEvent:s,getQueryParamValue:function(aa){var Z=j.location.search||j.location.hash;if(Z){if(/\?/.test(Z)){Z=Z.split("?")[1]}if(aa==null){return L(Z)}var Y=Z.split("&");for(var X=0;X<Y.length;X++){if(Y[X].substring(0,Y[X].indexOf("="))==aa){return L(Y[X].substring((Y[X].indexOf("=")+1)))}}}return""},expressInstallCallback:function(){if(a){var X=c(R);if(X&&l){X.parentNode.replaceChild(l,X);if(Q){w(Q,true);if(M.ie&&M.win){l.style.display="block"}}if(E){E(B)}}a=false}}}}();
		
			createContainer = function() {
				if (typeof document != 'undefined') {
					var div;
					clearInterval(ts);
					if (this.swfContainer !== null) {
						div = document.getElementById(this.swfContainer);
						if (div === null) throw new Error("NO_SWF_CONTAINER");
						if (div.offsetWidth < 215) div.style.minWidth = div.style.width = 215+'px';
						if (div.offsetHeight < 138) div.style.minHeight = div.style.height = 138+'px';
					} else {
						div = document.createElement('div');
					    this.swfContainer = div.id = 'voximplantcontainer';
						div.style.minWidth = div.style.width = 215+'px';
						div.style.minHeight = div.style.height = 138+'px';
					    if (document.body.firstChild) document.body.insertBefore(div, document.body.firstChild);
					    else document.body.appendChild(div);				
					}
					// Specify bigger width to show 'Plug-in blocked for this website' message in Safari
					if (navigator.userAgent.indexOf('Safari')!=-1) div.style.minWidth = div.style.width = 310+'px';
					var div2 = document.createElement('div');
					div2.id = 'voximplantcontainerSWF';			
					div.appendChild(div2);
				
				
					var attributes  = { id: "voximplantSWF", name: "voximplantSWF" };
					var flashvars = false;
					var params = { allowScriptAccess: "always", wmode: "window", allowFullScreen: "true" };					
					
					window.voxImplantFlashAPIReady = swfLoaded.bind(this);
					var swfPath = ('https:' == document.location.protocol ? 'https://' : 'http://') + "cdn.voximplant.com/VoxImplant-3.1.swf?ver=200316";
					if (typeof this.swfURL != 'undefined') swfPath = this.swfURL;
					swfobject.embedSWF(swfPath, "voximplantcontainerSWF", "100%", "100%", "11.3", "http://cdn.voximplant.com/expressInstall.swf", flashvars, params, attributes);
				}
			};
			if (!swfobject.hasFlashPlayerVersion("11.3")) throw new Error("OLD_FLASH_VERSION");
			ts = setInterval(createContainer.bind(this), 100);	
		} else {
			throw new Error("NO_WEBRTC_SUPPORT");
		}
	
		function swfLoaded() {			
			this.dispatchEvent({name:'SDKReady', version: VoxImplant.version});
			if (navigator.userAgent.indexOf('Safari')!=-1) {
				var div = document.getElementById(this.swfContainer);
				if (div !== null) div.style.minWidth = div.style.width = 215+'px';
			}
			var aSources = JSON.parse(VoxImplant.Utils.swfMovie('voximplantSWF').audioSources());
			var vSources = JSON.parse(VoxImplant.Utils.swfMovie('voximplantSWF').videoSources());
			for (var i=0;i<aSources.length;i++) this.audioSourcesList.push({id: i, name: aSources[i]});
			for (i=0;i<vSources.length;i++) this.videoSourcesList.push({id: i, name: vSources[i]});
			this.dispatchEvent({name:'SourcesInfoUpdated'});
		}

		window.VILog = function(message) {
			if (typeof this.writeLog == 'function') this.writeLog(message);
			else {
        var d = new Date();
        var tz = "UTC";
        if(typeof(d.getTimezoneOffset())!="undefined")
          tz = "UTC "+d.getTimezoneOffset/60;
        var dateTimeOptions = {year: "numeric", month: "numeric", day: "numeric", timeZone: tz};
				if (this.showDebugInfo && typeof console != 'undefined') BXIM.webrtc.phoneLog('VI FLASH: '+d.toLocaleTimeString('en-US',dateTimeOptions)+' '+message);
			}
		}.bind(this);
	
		window.VIConnectionEstablished = function() {
			this.connectionState(true);
			this.dispatchEvent({name:'ConnectionEstablished'});
		}.bind(this);
	
		window.VIConnectionFailed = function() {
			if (this.serversList.length > 1 && typeof(this.serverIp) == "undefined") {
				this.serversList.splice(0, 1);
				this.connectTo(this.serversList[0], true);
			} else {
				this.dispatchEvent({name:'ConnectionFailed'});
			}
		}.bind(this);
	
		window.VIConnectionClosed = function() {
			this.connectionState(false);
			this.__cleanup();
			this.dispatchEvent({name:'ConnectionClosed'});
			if (this.progressTone) this.stopProgressTone();
		}.bind(this);
	
		window.VIAuthFailed = function(code, key) {			
			this.dispatchEvent({name:'AuthResult', result:false, code:code, key:key });
		}.bind(this);

		window.VIAuthSuccessful = function(displayName, options) {
			if (typeof options == 'string') options = JSON.parse(options);
			this.dispatchEvent({name:'AuthResult', result:true, displayName:displayName, options:options });
		}.bind(this);
	
		window.VICallConnected = function(id, headers) {
			this.getCall(id).dispatchEvent({name:'Connected', call: this.getCall(id), headers: headers!==null?JSON.parse(headers):{} });
			if (this.progressTone) this.stopProgressTone();
		}.bind(this);
	
		window.VICallDisconnected = function(id, headers) {
			this.getCall(id).dispatchEvent({name:'Disconnected', call: this.getCall(id), headers: headers!==null?JSON.parse(headers):{} });
			this.removeCall(id);
			if (this.progressTone) this.stopProgressTone();
		}.bind(this);
	
		window.VICallFailed = function(id, code, reason, headers) {
			this.getCall(id).dispatchEvent({name:'Failed', call: this.getCall(id), code:code, reason:reason, headers: headers!==null?JSON.parse(headers):{} });
			this.removeCall(id);
			if (this.progressTone) this.stopProgressTone();
		}.bind(this);
	
		window.VIMicAccessResult = function(res) {
			this.dispatchEvent({name:'MicAccessResult', result:res});
		}.bind(this);
	
		window.VIProgressToneStart = function(id) {
			this.getCall(id).dispatchEvent({name:'ProgressToneStart', call: this.getCall(id)});
			if (this.progressTone) this.playProgressTone();
		}.bind(this);
	
		window.VIProgressToneStop = function(id) {
			this.getCall(id).dispatchEvent({name:'ProgressToneStop', call: this.getCall(id)});
			if (this.progressTone) this.stopProgressTone();
		}.bind(this);
	
		window.VIIncomingCall = function(id, num, displayName, headers) {
			var newCall = new Call(id, num, displayName, headers!==null?JSON.parse(headers):{}, false);
			if (this.calls.length > 0) this.zingayaAPI.setCallActive(id, false);
			this.calls.push(newCall);
			this.dispatchEvent({name:'IncomingCall', call:newCall, headers: headers!==null?JSON.parse(headers):{} });
		}.bind(this);
	
		window.VISIPInfoReceived = function(id, type, subtype, body, headers) {
			if (type =="application" && subtype == "zingaya-im") {
				this.getCall(id).dispatchEvent({name:'MessageReceived', call: this.getCall(id), text:body});
			}
			else {
				if (headers !== null) headers = JSON.parse(headers);
				this.getCall(id).dispatchEvent({name:'InfoReceived', call: this.getCall(id), mimeType:type+"/"+subtype, body:body, headers:headers});
			}
		}.bind(this);
		
		window.VIToneScriptPlaybackStop = function() {
			this.dispatchEvent({name:'PlaybackFinished'});
		}.bind(this);

		window.VITransferComplete = function(id) {
			this.getCall(id).dispatchEvent({name:'TransferComplete', call: this.getCall(id)});
		}.bind(this);

		window.VITransferFailed = function(id) {
			this.getCall(id).dispatchEvent({name:'TransferFailed', call: this.getCall(id)});
		}.bind(this);

		window.VIPacketLossInfo = function(val) {
			this.dispatchEvent({name:'NetStatsReceived', stats: {packetLoss: val}});
		}.bind(this);

		window.VIHandleRoster = function(id, roster) {
			this.dispatchEvent({name:'RosterReceived', id: id, roster: JSON.parse(roster)});
		}.bind(this);

		window.VIHandleRosterPresence = function(id, resource, presence, message) {
			this.dispatchEvent({name:'RosterPresenceUpdate', id: id, resource: resource, presence: presence, message: message});
		}.bind(this);

		window.VIHandleMessage = function(id, resource, content, message_id) {
			if (this.imXSSprotection) {
				var div = document.createElement("div");
	    		div.appendChild(document.createTextNode(content));
	    		content = div.innerHTML;
			}
			this.dispatchEvent({name:'MessageReceived', id: id, resource: resource, content: content, message_id: message_id});
		}.bind(this);

		window.VIHandlePresence = function(id, resource, presence, message) {
			this.dispatchEvent({name:'PresenceUpdate', id: id, resource: resource, presence: presence, message: message});
		}.bind(this);

		window.VIHandleChateState = function(id, resource, state) {
			this.dispatchEvent({name:'ChatStateUpdate', id: id, resource: resource, state: state});
		}.bind(this);

		window.VIHandleMessageEvent = function(id, resource, type, message_id) {
			this.dispatchEvent({name:'MessageStatus', id: id, resource: resource, type: type, message_id: message_id });
		}.bind(this);

		window.VIHandleRosterItem = function(id, resource, type, msg, displayName) {
			this.dispatchEvent({name:'RosterItemChange', id: id, resource: resource, type: type, displayName: displayName});
		}.bind(this);

		window.VIHandleSubscription = function(id, resource, type, message) {
			this.dispatchEvent({name:'SubscriptionRequest', id: id, resource: resource, type: type, message: message});
		}.bind(this);

		window.VIHandleRemoteFunctionError = function(method, params, code, description) {
			this.dispatchEvent({name:'IMError', errorType:'RemoteFunctionError', errorData: {method: method, params: JSON.parse(params), code: code, description: description} });
		}.bind(this);

		window.VIHandleIMError = function(type, code, description) {
			this.dispatchEvent({name:'IMError', errorType:'Error', errorData: {type: type, code: code, description: description} });
		}.bind(this);

		window.VIHandleIMRosterError = function(code) {
			this.dispatchEvent({name:'IMError', errorType:'RosterError', errorData: {code: code} });
		}.bind(this);

	},
	
	/**
	* @ignore 
	*/

	this.connectionState = function(b) {
		if (typeof b == 'undefined') return _connected;
		else _connected = b;
	};
	
	/** 
	* Find call in calls array
	* @param {string} call_id Call id
	* @returns {Call}
	* @ignore
	*/ 
	this.getCall = function(call_id) {
		for (var i=0; i < this.calls.length; i++) {
			if (this.calls[i].call() == call_id) return this.calls[i];
		}
		return null;
	};
	
	/** 
	* Remove call from calls array
	* @param {string} call_id Call id
	* @ignore
	*/
	this.removeCall = function(call_id) {
		var newCallsArray = [];
		for (var i=0; i < this.calls.length; i++) {
			if (this.calls[i].call() != call_id) newCallsArray.push(this.calls[i]);
			else delete this.calls[i];
		}
		this.calls = newCallsArray;		
	};
	
	/**
	* Plays progress tone according to specified country in config.progressToneCountry
	* @ignore
	*/
	this.playProgressTone = function() {
		if (this.progressToneScript[this.progressToneCountry] !== null) {
			if (!this.playingNow) this.playToneScript(this.progressToneScript[this.progressToneCountry]);
			this.playingNow = true;
		}
	};
	
	/**
	* Stop progress tone
	* @ignore
	*/
	this.stopProgressTone = function() {
		if (this.playingNow) {
			this.stopPlayback();
			this.playingNow = false;
		}
	};
	
	/**
	* @ignore 
	*/
	this.__call = function(num, useVideo, customData, extraHeaders) {
		if (typeof num == "object") {
			var useVideo = (typeof num["video"] == "undefined"?false:num["video"]),
				customData = num["customData"],
				extraHeaders = num["extraHeaders"],
				wiredLocal = (typeof num["wiredLocal"] == "undefined"?true:num["wiredLocal"]),
				wiredRemote = (typeof num["wiredRemote"] == "undefined"?true:num["wiredRemote"]),
				num = num["number"];
		}
		if (typeof customData != 'undefined') {
			if (typeof extraHeaders == 'undefined') extraHeaders = {};
			extraHeaders["VI-CallData"] = customData;
		}
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		var call_id, newCall;
		if (this.RTCsupported && !this.useFlashOnly) { 
			call_id = this.zingayaAPI.callTo(num, useVideo, extraHeaders, { wiredRemote: wiredRemote }); 			
			newCall = new Call(call_id, num, "", extraHeaders, true, this.zingayaAPI);
			if (this.calls.length > 0) this.zingayaAPI.setCallActive(call_id, false);
		} else if (!this.useRTCOnly) {
			extraHeaders = JSON.stringify(extraHeaders);
			call_id = VoxImplant.Utils.swfMovie('voximplantSWF').call(num, useVideo, null, extraHeaders);
			newCall = new Call(call_id, num, "", extraHeaders, false);
			if (this.calls.length > 0) VoxImplant.Utils.swfMovie('voximplantSWF').setCallActive(call_id, false);			
		}
		this.calls.push(newCall);
		return newCall;
	};
	
	/**
	* @ignore 
	*/
	this.__volume = function(vol) {
		if (typeof vol == 'undefined') return _vol;
		else _vol = vol;
	};

	/**
	* @ignore 
	*/
	this.__cleanup = function() {
		if (this.calls.length > 0) {
			var callIds = [], i;
			for (i in this.calls) {
				callIds.push(this.calls[i].id());
				if (this.connectionState()) this.calls[i].hangup();								
			}
			for (i in callIds) this.removeCall(callIds[i]);
		}
	};
	
};

VoxImplant.Client.prototype = {
	
	/**
	* Create call
	* @name VoxImplant.Client.call
	* @param {String} num The number to call. For SIP compatibility reasons it should be a non-empty string even if the number itself is not used by a Voximplant cloud scenario.
	* @param {Boolean} [useVideo=false] Tells if video should be supported for the call
	* @param {String} [customData] Custom string associated with the call session. It can be later obtained from Call History using HTTP API
	* @param {Object} [extraHeaders] Optional custom parameters (SIP headers) that should be passed with call (INVITE) message. Parameter names must start with "X-" to be processed by application. IMPORTANT: Headers size limit is 200 bytes.
	* @function
	* @returns {VoxImplant.Call}
	*/
	call: function(num, useVideo, customData, extraHeaders) {
		return this.__call(num, useVideo, customData, extraHeaders);
	},
	
	/**
	* Get current config
	* @function
	* @name VoxImplant.Client.config
	* @returns {VoxImplant.Config}
	*/
	config: function() {
		return this.config;
	},

	/**
	* Connect to VoxImplant Cloud
	* @name VoxImplant.Client.connect
	* @function
	*/
	connect: function(connectivityCheck) {	
		if (typeof connectivityCheck == "boolean") this.connectivityCheck = connectivityCheck;
		if (typeof this.serverIp != 'undefined') {
			host = this.serverIp;
			this.connectTo(host);
		} else {
			balancerResult = function(data) {
				var ind = String(data).indexOf(";");
				if (ind == -1) {
					// one IP available
					host = data;
				} else {
					this.serversList = data.split(";");
					host = this.serversList[0];
				}
				this.connectTo(host);
			};
			VoxImplant.Utils.getServers(balancerResult.bind(this), false, this);
		}		
	},
	
	/**
	* Connect to specific VoxImplant Cloud host
	* @name VoxImplant.Client.connectTo
	* @ignore
	*/
	connectTo: function(host, omitMicDetection, connectivityCheck) {
		if (typeof connectivityCheck == "boolean") this.connectivityCheck = connectivityCheck;
		if (this.connectionState()) {
			throw new Error("ALREADY_CONNECTED_TO_VOXIMPLANT");
		}
		this.host = host;
        if (this.RTCsupported && !this.useFlashOnly) {
            if (!this.micRequired || omitMicDetection === true) this.zingayaAPI.connectTo(host, "platform", null, null, this.connectivityCheck);
            else {
				if (this.videoSupport) this.zingayaAPI.setConstraints(this.videoConstraints, null, null, false);
				this.zingayaAPI.requestMedia(this.videoSupport, this.zingayaAPI.onMediaAccessGranted, this.zingayaAPI.onMediaAccessRejected);
			}
		} else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').connect(host, omitMicDetection===true?false:this.micRequired, this.micRequired&&this.showFlashSettings);
		}
	},

	/**
	* Disconnect from VoxImplant Cloud
	* @name VoxImplant.Client.disconnect
	* @function
	*/
	disconnect: function() {
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		this.__cleanup();
		if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.destroy(); }
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').disconnect();
		}
	},
	
	/**
	* Initialize SDK. SDKReady event will be dispatched after succesful SDK initialization. SDK can't be used until it's initialized
	* @param {VoxImplant.Config} [config] Client configuration options
	* @function
	* @name VoxImplant.Client.init
	*/
	init: function(config) {
		this.__init(config);
	},

	/**
	* Set ACD status
	* @param {String} status Presence status string, see <a href="VoxImplant.OperatorACDStatuses.html">VoxImplant.OperatorACDStatuses</a>
	* @function
	* @name VoxImplant.Client.setOperatorACDStatus
	*/
	setOperatorACDStatus: function(status) {
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.setOperatorACDStatus(status); }
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setOperatorACDStatus(status);
		}
	},

	/**
	* Login into application
	* @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
	* @param {String} password
	* @param {VoxImplant.LoginOptions} [options]
	* @function
	* @name VoxImplant.Client.login
	*/
	login: function(username, password, options) {
		options = typeof options !== 'undefined' ? options : {};
		options = VoxImplant.Utils.extend({}, options);
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.login(username, password, options); }
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').login(username, password, JSON.stringify(options));
		}
	},
	
	/**
	* Login into application using 'code' auth method
	* @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
	* @param {String} code
	* @param {VoxImplant.LoginOptions} [options]
	* @function
	* @name VoxImplant.Client.loginWithCode
 	*/
	loginWithCode: function(username, code, options) {
		options = typeof options !== 'undefined' ? options : {};
		options = VoxImplant.Utils.extend({ serverPresenceControl: false }, options);
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.loginStage2(username, code, options); }
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').loginStage2(username, code, JSON.stringify(options));
		}
	},
	
	/**
	* Request a key for 'onetimekey' auth method
	* Server will send the key in AuthResult event with code 302
	* @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
	* @function
	* @name VoxImplant.Client.requestOneTimeLoginKey
 	*/
	requestOneTimeLoginKey: function(username) {
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.loginGenerateOneTimeKey(username); }
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').requestOneTimeLoginKey(username);
		}
	},
	
	/**
	* Login into application using 'onetimekey' auth method
	* Hash should be calculated with the key received in AuthResult event.
  * See <a href="http://voximplant.com/docs/quickstart/24/automated-login/">"Automated Login"</a> tutorial for details
	* @param {String} username Fully-qualified username that includes Voximplant user, application and account names. The format is: "username@appname.accname.voximplant.com".
	* @param {String} hash
	* @param {VoxImplant.LoginOptions} [options]
	* @function
	* @name VoxImplant.Client.loginWithOneTimeKey
 	*/
	loginWithOneTimeKey: function(username, hash, options) {
		options = typeof options !== 'undefined' ? options : {};
		options = VoxImplant.Utils.extend({ serverPresenceControl: false }, options);
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.loginUsingOneTimeKey(username, hash, options); }
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').loginUsingOneTimeKey(username, hash, JSON.stringify(options));
		}
	},

	/**
	* Check if connected to VoxImplant Cloud
	* @function
	* @returns {Boolean} True if connected
	* @name VoxImplant.Client.connected
	*/
	connected: function() {
		return this.connectionState();
	},

	/**
	* Show/hide local video
	* @param {Boolean} [flag=true] Show/hide - true/false
	* @function
	* @name VoxImplant.Client.showLocalVideo
	*/
	showLocalVideo: function(flag) {
		if (typeof flag == "undefined") flag = true;
		if (this.RTCsupported && !this.useFlashOnly) { 
			if (flag) {
				if (document.getElementById("voximplantlocalvideo") === null) {
					var element = document.createElement('video');
				    element.id = 'voximplantlocalvideo';
					element.autoplay = "autoplay";
					element.muted = "true";
					if (document.body.firstChild) document.body.insertBefore(element, document.body.firstChild);
				    else document.body.appendChild(element);
					this.zingayaAPI.setLocalVideoSink(element);
				} else document.getElementById("voximplantlocalvideo").style.display = "block";
			} else {
				document.getElementById("voximplantlocalvideo").style.display = "none";
				//(elem=document.getElementById('voximplantlocalvideo')).parentNode.removeChild(elem);
			}
		}
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').showLocalVideo(flag);
		}	
	},

	/**
	* Set local video position
	* @param {Number} x Horizontal position (px)
	* @param {Number} y Vertical position (px)
	* @function
	* @name VoxImplant.Client.setLocalVideoPosition
	*/
	setLocalVideoPosition: function(x, y) {
		if (this.RTCsupported && !this.useFlashOnly) { 
			throw new Error("Please use CSS to position '#voximplantlocalvideo' element");
		} else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setSelfViewPosition(x, y);
		}
	},

	/**
	* Set local video size
	* @param {Number} width Width in pixels
	* @param {Number} height Height in pixels
	* @function
	* @name VoxImplant.Client.setLocalVideoSize
	*/
	setLocalVideoSize: function(width, height) {
		if (this.RTCsupported && !this.useFlashOnly) { 
			throw new Error("Please use CSS to set size of '#voximplantlocalvideo' element");
		} else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setSelfViewSize(width, height);
		}
	},

	/**
	* Set video settings globally. This settings will be used for the next call.
	* @param {VoxImplant.VideoSettings|VoxImplant.FlashVideoSettings} settings Video settings
	* @param {Function} [successCallback] Success callback function (WebRTC: has MediaStream object as its argument)
	* @param {Function} [failedCallback] Failed callback function
	* @function
	* @name VoxImplant.Client.setVideoSettings
	*/
	setVideoSettings: function(settings, successCallback, failedCallback, apply) {		
		if (this.RTCsupported && !this.useFlashOnly) { 
			this.zingayaAPI.setConstraints(settings, function(stream) {
				if (document.getElementById("voximplantlocalvideo") !== null) this.zingayaAPI.setLocalVideoSink(document.getElementById("voximplantlocalvideo"));
				this.videoConstraints = settings;
				if (typeof successCallback == "function") successCallback(stream);
			}.bind(this), 
				function(err) { if (typeof failedCallback == "function") failedCallback(err) }, true);		
		} 
		else if (!this.useRTCOnly) {
			if (Object.prototype.toString.call(settings) == '[object Object]') settings = JSON.stringify(settings);
			try {
				VoxImplant.Utils.swfMovie('voximplantSWF').setVideoSettings(settings);
			} catch(e) {
				if (typeof failedCallback == "function") failedCallback();
			} 			
			if (typeof successCallback == "function") successCallback();
		}
	},

	/**
	* Set bandwidth limit for video calls. Currently supported by Chrome/Chromium. (WebRTC mode only). The limit will be applied for the next call.
	* @param {Number} bandwidth Bandwidth limit in kilobits per second (kbps)
	* @function
	* @name VoxImplant.Client.setVideoBandwidth
	*/
	setVideoBandwidth: function(bandwidth) {
		if (this.RTCsupported && !this.useFlashOnly) { 
			this.zingayaAPI.setVideoBandwidth(bandwidth);
			this.zingayaAPI.setDesiredVideoBandwidth(bandwidth);
		}
	},
	
	/**
	* Play ToneScript using WebAudio API
	* @param {String} script Tonescript string
	* @param {Boolean} [loop=false] Loop playback if true
	* @function
	* @name VoxImplant.Client.playToneScript
	*/
	playToneScript: function(script, loop) {
		if (this.RTCsupported && !this.useFlashOnly) { 
			VoxImplant.Utils.playToneScript(script, loop);
		} 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').playToneScript(script, loop);
		}
	},
	
	/**
	* Stop playing ToneScript using WebAudio API
	* @function
	* @name VoxImplant.Client.stopPlayback
	*/
	stopPlayback: function() {
		if (this.RTCsupported && !this.useFlashOnly) { 
			if (VoxImplant.Utils.stopPlayback()) this.dispatchEvent({name:'PlaybackFinished'});
		} 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').stopPlayback();
		}
	},
	
	/**
	* Get current global sound volume
	* @function
	* @ignore
	* @returns {Number}
	*//**
	* Change current global sound volume
	* @param {Number} vol New sound volume value between 0 and 100
	* @function
	* @ignore
	*/
	volume: function(vol) {
		if (typeof vol == 'undefined') {
			return this.__volume();
		} else {
			if (vol > 100) vol = 100;
			if (vol < 0) vol = 0;
			if (this.RTCsupported && !this.useFlashOnly) { 
				this.zingayaAPI.setPlaybackVolume(vol/100);
			}
			else if (!this.useRTCOnly) {
				VoxImplant.Utils.swfMovie('voximplantSWF').changeIncomingAudioVolume(vol);
			}
			this.__volume(vol);	
		}
	},

	/**
	* Get a list of all currently available audio sources / microphones
	* @function
	* @name VoxImplant.Client.audioSources
	* @returns {Array} Array of {VoxImplant.AudioSourceInfo} objects
	*/
	audioSources: function() {
		if (this.RTCsupported && !this.useFlashOnly) { 
			if (!this.deviceEnumAPI) throw new Error("NOT_SUPPORTED: enumerateDevices");
		}
		return this.audioSourcesList;
	},

	/**
	* Get a list of all currently available video sources / cameras
	* @function
	* @name VoxImplant.Client.videoSources
	* @returns {Array} Array of {VoxImplant.VideoSourceInfo} objects
	*/
	videoSources: function() {
		if (this.RTCsupported && !this.useFlashOnly) { 
			if (!this.deviceEnumAPI) throw new Error("NOT_SUPPORTED: enumerateDevices");
		}
		return this.videoSourcesList;
	},

	/**
	* Get a list of all currently available audio playback devices
	* @function
	* @name VoxImplant.Client.audioOutputs
	* @returns {Array} Array of {VoxImplant.AudioOutputInfo} objects
	*/
	audioOutputs: function() {
		if (this.RTCsupported && !this.useFlashOnly) { 
			if (!this.deviceEnumAPI) throw new Error("NOT_SUPPORTED: enumerateDevices");
		}
		return this.audioOutputsList;
	},

	/**
	* Use specified audio source , use <a href="VoxImplant.Client.html#audioSources">audioSources</a> to get the list of available audio sources
	* @param {Number|String} id Id of the audio source 
	* @param {Function} [successCallback] Called in WebRTC mode if audio source changed successfully (WebRTC: has MediaStream object as its argument)
	* @param {Function} [failedCallback] Called in WebRTC mode if audio source couldn't changed successfully
	* @name VoxImplant.Client.useAudioSource
	* @function
	*/
	useAudioSource: function(id, successCallback, failedCallback) {
		if (this.RTCsupported && !this.useFlashOnly) { 
			this.zingayaAPI.useAudioSource(id, 
				function(stream) { if (typeof successCallback == "function") successCallback(stream) }, 
				function(err) { if (typeof failedCallback == "function") failedCallback(err) });
		} 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setAudioSource(id);
		}
	},

	/**
	* Use specified video source , use <a href="VoxImplant.Client.html#videoSources">videoSources</a> to get the list of available video sources
	* @param {Number|String} id Id of the video source 
	* @param {Function} [successCallback] Called in WebRTC mode if video source changed successfully (WebRTC: has MediaStream object as its argument)
	* @param {Function} [failedCallback] Called in WebRTC mode if video source couldn't changed successfully
	* @name VoxImplant.Client.useVideoSource
	* @function
	*/
	useVideoSource: function(id, successCallback, failedCallback) {
		if (this.RTCsupported && !this.useFlashOnly) { 
			this.zingayaAPI.useVideoSource(id, function(stream) {
				if (document.getElementById("voximplantlocalvideo") !== null) this.zingayaAPI.setLocalVideoSink(document.getElementById("voximplantlocalvideo"));
				if (typeof successCallback == "function") successCallback(stream);
			}.bind(this), function(err) { 
				if (typeof failedCallback == "function") failedCallback(err);
			});
		} 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setVideoSource(id);
		}
	},

	/**
	* Enable microphone/camera if micRequired in <a href="VoxImplant.Config.html">VoxImplant.Config</a> was set to false (WebRTC mode only)
	* @param {Function} [successCallback] Called if selected recording devices were attached successfully (WebRTC: has MediaStream object as its argument)
	* @param {Function} [failedCallback] Called if selected recording devices couldn't be attached
	* @name VoxImplant.Client.attachRecordingDevice
	* @function
	*/
	attachRecordingDevice: function(successCallback, failedCallback) {
		if (this.RTCsupported && !this.useFlashOnly && !this.micRequired) {
			this.zingayaAPI.requestMedia(this.videoSupport, 
				function(stream) { if (typeof successCallback == "function") successCallback(stream) }, 
				function(err) { if (typeof failedCallback == "function") failedCallback(err) });
		}
	},

	/**
	* Disable microphone/camera if micRequired in <a href="VoxImplant.Config.html">VoxImplant.Config</a> was set to false (WebRTC mode only)
	* @name VoxImplant.Client.detachRecordingDevice
	* @function
	*/
	detachRecordingDevice: function() {
		if (this.RTCsupported && !this.useFlashOnly && !this.micRequired) {
			this.zingayaAPI.stopLocalStream();
		}
	},

	/**
	* Show flash settings panel
	* @param {String} [panel=default] Settings type - default/microphone/camera/etc as described in SecurityPanel class 
	* @name VoxImplant.Client.showFlashSettingsPanel
	* @function
	*/
	showFlashSettingsPanel: function(panel) {
		if (typeof panel == "undefined") panel = "default";
		if (this.RTCsupported && !this.useFlashOnly) { 
			// function for Flash mode - do nothing in WebRTC
		} 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').showFlashSettings(panel);
		}	
	},

	/**
	* Set active call
	* @name VoxImplant.Client.setCallActive
	* @param {VoxImplant.Call} call VoxImplant call instance
	* @param {Boolean} [active=true] If true make call active, otherwise make call inactive
	* @function
	*/
	setCallActive: function(call, active) {
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) { 
			this.zingayaAPI.setCallActive(call.call(), active);			
		} 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setCallActive(call.call(), active);
		}	
	},

	/**
	* Start/stop sending local video to remote party/parties
	* @name VoxImplant.Client.sendVideo
	* @param {Boolean} [flag=true] Start/stop - true/false
	* @function
	*/
	sendVideo: function(flag) {
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (typeof flag == "undefined") flag = true;
		if (this.RTCsupported && !this.useFlashOnly) {
			this.zingayaAPI.sendVideo(flag);
		} else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').sendVideo(flag);
		}
	},

	/**
	* Check if WebRTC support is available
	* @name VoxImplant.Client.isRTCsupported
	* @function
	* @returns {Boolean}
	*/
	isRTCsupported: function() {
		return this.RTCsupported;
	},

	/**
	* Transfer call, depending on the result <a href="VoxImplant.CallEvents.html#VoxImplant_CallEvents_TransferComplete">VoxImplant.CallEvents.TransferComplete</a> or <a href="VoxImplant.CallEvents.html#VoxImplant_CallEvents_TransferFailed">VoxImplant.CallEvents.TransferFailed</a> event will be dispatched.
	* @param {VoxImplant.Call} call1 Call which will be transferred
	* @param {VoxImplant.Call} call2 Call where call1 will be transferred
	* @function
	* @name VoxImplant.Client.transferCall
	*/
	transferCall: function(call1, call2) {
		if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
		if (this.RTCsupported && !this.useFlashOnly) {
			this.zingayaAPI.transferCall(call1.call(), call2.call());
		} else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').transferCall(call1.call(), call2.call());
		}
	},

	/**
	* Set background color of flash app (only for Flash mode)
	* @param {String} color Color in web format (i.e. #000000 for black)
	* @function
	* @name VoxImplant.Client.setSwfColor
	*/
	setSwfColor: function(color) {
		if (this.RTCsupported && !this.useFlashOnly) { /* do nothing */ } 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setStageColor(color);
		}	
	},

	/**
	* @ignore
	* @name VoxImplant.Client.setCodecPayload
	*/
	setCodecPayload: function(payload) {
		if (this.RTCsupported && !this.useFlashOnly) { /* do nothing */ } 
		else if (!this.useRTCOnly) {
			VoxImplant.Utils.swfMovie('voximplantSWF').setCodecPayload(payload);
		}	
	},

	/**
	* @ignore
	*/
	startScreenSharing: function() {
		if (this.RTCsupported && !this.useFlashOnly) {
			this.zingayaAPI.shareScreen();
		}
	}
};

/**
* Register handler for specified event
* @param {Function} event Event class (i.e. <a href="VoxImplant.Events.html#VoxImplant_Events_SDKReady">VoxImplant.Events.SDKReady</a>). See <a href="VoxImplant.Events.html">VoxImplant.Events</a>
* @param {Function} handler Handler function. A single parameter is passed - object with event information
* @function
*/
VoxImplant.Client.prototype.addEventListener = function(event, handler) {
	if (typeof(this.eventListeners[event]) == 'undefined') this.eventListeners[event] = [];
	this.eventListeners[event].push(handler);
};

/**
* Remove handler for specified event
* @param {Function} event Event class (i.e. <a href="VoxImplant.Events.html#VoxImplant_Events_SDKReady">VoxImplant.Events.SDKReady</a>). See <a href="VoxImplant.Events.html">VoxImplant.Events</a>
* @param {Function} handler Handler function
* @function
*/
VoxImplant.Client.prototype.removeEventListener = function(event, handler) {
	if (typeof(this.eventListeners[event]) == 'undefined') return;
	for (var i=0;i<this.eventListeners[event].length;i++)
	{
		if (this.eventListeners[event][i] == handler)
		{
			this.eventListeners[event].splice(i,1);
			break;
		}
	}
};



/** 
* @ignore 
*/
VoxImplant.Client.prototype.dispatchEvent = VoxImplant.Call.prototype.dispatchEvent = function(e) {
	var event = e.name;
	if (typeof this.eventListeners[event] != 'undefined') {
		for (var i=0;i<this.eventListeners[event].length;i++) {
			if (typeof this.eventListeners[event][i] == "function") {
				this.eventListeners[event][i](e);
			}
		}
	}
};
})(VoxImplant);

/**
* @namespace
* @name VoxImplant.Client
*/
(function (VoxImplant, undefined) {
/**
* Add roster item (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a>
* @param {String} uri User id
* @param {String} name Display name
* @param {String} [group=""] User group
* @param {String} [message] Intro message for the user
* @function
* @name VoxImplant.Client.addRosterItem
* @group IM Functions
*/
VoxImplant.Client.prototype.addRosterItem = function(uri, name, group, message) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (typeof group == "undefined") group = "";
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.addRoster(uri, name, group, message);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').addRosterItem(uri, name, group);
	}	
};

/**
* Remove roster item (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a>
* @param {String} uri User id
* @function
* @name VoxImplant.Client.removeRosterItem
* @group IM Functions
*/
VoxImplant.Client.prototype.removeRosterItem = function(uri) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.removeRoster(uri);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').removeRosterItem(uri);
	}	
};

/**
* Rename roster item (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a>
* @param {String} uri User id
* @param {String} name New display name
* @function
* @name VoxImplant.Client.renameRosterItem
* @group IM Functions
*/
VoxImplant.Client.prototype.renameRosterItem = function(uri, name) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.renameRosterItem(uri, name);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').renameRosterItem(uri, name);
	}
};

/**
* Add roster item group (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a>
* @param {String} uri User id
* @param {String} group Group name
* @function
* @name VoxImplant.Client.addRosterItemGroup
* @group IM Functions
*/
VoxImplant.Client.prototype.addRosterItemGroup = function(uri, group) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.addRosterItemGroup(uri, group);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').addRosterItemGroup(uri, group);
	}	
};

/**
* Remove roster item group (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a>
* @param {String} uri User id
* @param {String} group Group name
* @function
* @name VoxImplant.Client.removeRosterItemGroup
* @group IM Functions
*/
VoxImplant.Client.prototype.removeRosterItemGroup = function(uri, group) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.delRosterItemGroup(uri, group);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').delRosterItemGroup(uri, group);
	}	
};

/**
* Move roster item group (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_RosterItemChange">VoxImplant.IMEvents.RosterItemChange</a>
* @param {String} uri User id
* @param {String} groupSrc Group name (source)
* @param {String} groupDst Group name (destination)
* @function
* @name VoxImplant.Client.moveRosterItemGroup
* @group IM Functions
*/
VoxImplant.Client.prototype.moveRosterItemGroup = function(uri, groupSrc, groupDst) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.moveRosterItemGroup(uri, groupSrc, groupDst);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').moveRosterItemGroup(uri, groupSrc, groupDst);
	}	
};

/**
* Authorize the user to let him see your presence and send you messages (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_SubscriptionRequest">VoxImplant.IMEvents.SubscriptionRequest</a>
* @param {String} uri User id
* @function
* @name VoxImplant.Client.acceptSubscription
* @group IM Functions
*/
VoxImplant.Client.prototype.acceptSubscription = function(uri) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.replySubscriptionRequest(uri, true);
	}	
};

/**
* Don't let the user see your presence and send you messages (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_SubscriptionRequest">VoxImplant.IMEvents.SubscriptionRequest</a>
* @param {String} uri User id
* @function
* @name VoxImplant.Client.rejectSubscription
* @group IM Functions
*/
VoxImplant.Client.prototype.rejectSubscription = function(uri) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.replySubscriptionRequest(uri, false);
	}	
};

/**
* Send message to user (IM). See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_MessageStatus">VoxImplant.IMEvents.MessageStatus</a>
* @param {String} uri User id
* @param {String} content Message content
* @function
* @returns {String} Sent message id
* @name VoxImplant.Client.sendInstantMessage
* @group IM Functions
*/
VoxImplant.Client.prototype.sendInstantMessage = function(uri, content) {
	var messageId = VoxImplant.Utils.generateUUID();
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { 
		this.zingayaAPI.sendTextMessage(uri, content, messageId); 
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').sendInstantMessage(uri, content, messageId);
	}
	return messageId;
};

/**
* Edit message sent to user. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_MessageModified">VoxImplant.IMEvents.MessageModified</a> 
* and <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_MessageNotModified">VoxImplant.IMEvents.MessageNotModified</a>
* @param {String} uri User id 
* @param {String} message_id Message id
* @param {String} msg New message content
* @function
* @name VoxImplant.Client.editInstantMessage
* @group IM Functions
*/
VoxImplant.Client.prototype.editInstantMessage = function(uri, message_id, msg) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.editTextMessage(uri, message_id, msg); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').editTextMessage(uri, message_id, msg);
	}
};

/**
* Remove message sent to user. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_MessageRemoved">VoxImplant.IMEvents.MessageRemoved</a> 
* @param {String} uri User id 
* @param {String} message_id Message id
* @function
* @name VoxImplant.Client.removeInstantMessage
* @group IM Functions
*/
VoxImplant.Client.prototype.removeInstantMessage = function(uri, message_id) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.removeTextMessage(uri, message_id); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').removeTextMessage(uri, message_id);
	}
};

/**
* Set chat session state info. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatStateUpdate">VoxImplant.IMEvents.ChatStateUpdate</a> 
* @param {String} uri User id
* @param {String} status Chat session status. See <a href="VoxImplant.ChatStateType.html">VoxImplant.ChatStateType</a> enum
* @function
* @name VoxImplant.Client.setChatState
* @group IM Functions
*/
VoxImplant.Client.prototype.setChatState = function(uri, status) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.sendChatState(uri, status);
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').sendChatState(uri, status);
	}
};

/**
* Set message(s) status. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_MessageStatus">VoxImplant.IMEvents.MessageStatus</a> 
* @param {String} uri User id
* @param {Number} type Message event type: <a href="VoxImplant.MessageEventType.html#VoxImplant_MessageEventType_Delivered">VoxImplant.MessageEventType.Delivered</a> or <a href="VoxImplant.MessageEventType.html#VoxImplant_MessageEventType_Displayed">VoxImplant.MessageEventType.Displayed</a>. See <a href="VoxImplant.MessageEventType.html">VoxImplant.MessageEventType</a> enum 
* @param {Array} message_id Message id(s)
* @function
* @name VoxImplant.Client.setMessageStatus
* @group IM Functions
*/
VoxImplant.Client.prototype.setMessageStatus = function(uri, type, message_id) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (typeof message_id === 'string') message_id = [message_id];
	else if (!Array.isArray(message_id)) {
		throw new Error("message_id should be string or array");
	}
	if (this.RTCsupported && !this.useFlashOnly) {
		this.zingayaAPI.raiseMessageEvent(uri, type, JSON.stringify(message_id));
	} else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').raiseMessageEvent(uri, type, JSON.stringify(message_id));
	} 
};

/**
* Set presence. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_PresenceUpdate">VoxImplant.IMEvents.PresenceUpdate</a> 
* @param {Number} status Presence status from <a href="VoxImplant.UserStatuses.html">VoxImplant.UserStatuses</a>
* @param {Number} msg Presence text message
* @function
* @name VoxImplant.Client.setPresenceStatus
* @group IM Functions
*/
VoxImplant.Client.prototype.setPresenceStatus = function(status, msg) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.sendStatus(status, msg); } 
	else if (!this.useRTCOnly) {
		VoxImplant.Utils.swfMovie('voximplantSWF').sendStatus(status, msg);
	}
};

/**
* Create multi-user chat room, join it and return room id. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomCreated">VoxImplant.IMEvents.ChatRoomCreated</a> 
* @param {String} [pass] Password for room access
* @param {Array} [users] User ids of the invited users to the chat room 
* @function
* @name VoxImplant.Client.createChatRoom
* @returns {String} Created room id
* @group IM Functions
*/
VoxImplant.Client.prototype.createChatRoom = function(pass, users) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	var room = VoxImplant.Utils.generateUUID();
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.joinMUC(room, pass, users); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').joinMUC(room, pass);
	}
	return room;
};


/**
* Join multi-user chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomInfo">VoxImplant.IMEvents.ChatRoomInfo</a> 
* @param {String} room Room id 
* @param {String} [pass] Password for room access
* @function
* @name VoxImplant.Client.joinChatRoom
* @group IM Functions
*/
VoxImplant.Client.prototype.joinChatRoom = function(room, pass) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.joinMUC(room, pass); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').joinMUC(room, pass);
	}
};

/**
* Accept invitation to join chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomInfo">VoxImplant.IMEvents.ChatRoomInfo</a> 
* @param {String} room Room id 
* @param {String} [pass] Password for room access
* @function
* @name VoxImplant.Client.acceptChatRoomInvite
* @group IM Functions
*/
VoxImplant.Client.prototype.acceptChatRoomInvite = VoxImplant.Client.prototype.joinChatRoom;

/**
* Leave multi-user chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomParticipantExit">VoxImplant.IMEvents.ChatRoomParticipantExit</a> 
* @param {String} room Room id 
* @param {String} [msg] Message for other participants
* @function
* @name VoxImplant.Client.leaveChatRoom
* @group IM Functions
*/
VoxImplant.Client.prototype.leaveChatRoom = function(room, msg) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.leaveMUC(room, msg); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').leaveMUC(room, msg);
	}
};

/**
* Send message to chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomMessageReceived">VoxImplant.IMEvents.ChatRoomMessageReceived</a> 
* @param {String} room Room id 
* @param {String} msg Message for other participants
* @function
* @returns {String} Sent message id
* @name VoxImplant.Client.sendChatRoomMessage
* @group IM Functions
*/
VoxImplant.Client.prototype.sendChatRoomMessage = function(room, msg) {
	var messageId = VoxImplant.Utils.generateUUID();
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.sendMUCMessage(room, msg, messageId); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').sendMUCMessage(room, msg, id);
	}
	return messageId;
};

/**
* Edit message in the chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomMessageModified">VoxImplant.IMEvents.ChatRoomMessageModified</a> or <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomMessageNotModified">VoxImplant.IMEvents.ChatRoomMessageNotModified</a> 
* @param {String} room Room id 
* @param {String} message_id Message id
* @param {String} msg New message content
* @function
* @name VoxImplant.Client.editChatRoomMessage
* @group IM Functions
*/
VoxImplant.Client.prototype.editChatRoomMessage = function(room, message_id, msg) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.editMUCMessage(room, message_id, msg); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').editChatRoomMessage(room, message_id, msg);
	}
};

/**
* Remove message in the chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomMessageRemoved">VoxImplant.IMEvents.ChatRoomMessageRemoved</a> 
* @param {String} room Room id 
* @param {String} message_id Message id
* @function
* @name VoxImplant.Client.removeChatRoomMessage
* @group IM Functions
*/
VoxImplant.Client.prototype.removeChatRoomMessage = function(room, message_id) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.removeMUCMessage(room, message_id); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').removeMUCMessage(room, message_id);
	}
};

/**
* Invite user to join chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomInvitation">VoxImplant.IMEvents.ChatRoomInvitation</a> 
* @param {String} room Room id 
* @param {String} uri User id (invitee)
* @param {String} [reason] User-supplied reason for the invitation 
* @function
* @name VoxImplant.Client.inviteToChatRoom
* @group IM Functions
*/
VoxImplant.Client.prototype.inviteToChatRoom = function(room, uri, reason, thread) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.inviteMUC(room, uri, reason, thread); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').inviteMUC(room, uri, reason, thread);
	}
};

/**
* Decline invitation to join chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomInviteDeclined">VoxImplant.IMEvents.ChatRoomInviteDeclined</a> 
* @param {String} room Room id 
* @param {String} uri User id (inviter)
* @param {String} [reason] User-supplied decline reason
* @function
* @name VoxImplant.Client.declineChatRoomInvite
* @group IM Functions
*/
VoxImplant.Client.prototype.declineChatRoomInvite = function(room, uri, reason) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.declineMUCinvitation(room, uri, reason); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').declineMUCinvitation(room, uri, reason);
	}
};

/**
* Set new chat room subject. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomSubjectChange">VoxImplant.IMEvents.ChatRoomSubjectChange</a> 
* @param {String} room Room id
* @param {String} subject New subject
* @function
* @name VoxImplant.Client.setChatRoomSubject
* @group IM Functions
*/
VoxImplant.Client.prototype.setChatRoomSubject = function(room, subject) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.setSubject(room, subject); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').setSubject(room, subject);
	}
};

/**
* Remove user from the chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomParticipantExit">VoxImplant.IMEvents.ChatRoomParticipantExit</a> 
* @param {String} room Room id
* @param {String} uri User id
* @param {String} [reason] Reason
* @function
* @name VoxImplant.Client.removeChatRoomUser
* @group IM Functions
*/
VoxImplant.Client.prototype.removeChatRoomUser = function(room, uri, reason) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.kickMUCUser(room, uri, reason); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').kickMUCUser(room, uri, reason);
	}
};

/**
* Ban user from the chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomParticipantExit">VoxImplant.IMEvents.ChatRoomParticipantExit</a> 
* @param {String} room Room id
* @param {String} uri User id
* @param {String} [reason] Reason
* @function
* @name VoxImplant.Client.banChatRoomUser
* @group IM Functions
*/
VoxImplant.Client.prototype.banChatRoomUser = function(room, uri, reason) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.banMUCUser(room, uri, reason); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').banMUCUser(room, uri, reason);
	}
};

/**
* Remove a ban on a user in the chat room. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomNewParticipant">VoxImplant.IMEvents.ChatRoomNewParticipant</a> 
* @param {String} room Room id
* @param {String} uri User id
* @param {String} [reason] Reason
* @function
* @name VoxImplant.Client.unbanChatRoomUser
* @group IM Functions
*/
VoxImplant.Client.prototype.unbanChatRoomUser = function(room, uri, reason) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.unbanMUCUser(room, uri, reason); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').unbanMUCUser(room, uri, reason);
	}
};

/**
* Request instant messaging history in a conversation with particular user. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatHistoryReceived">VoxImplant.IMEvents.ChatHistoryReceived</a> 
* @param {String} uri User id
* @param {String} [message_id] Message id (to get messages sent before/after the message) 
* @param {String} [direction=false] False/true to get messages older/newer than the message with specified id
* @param {Number} [count=100] Number of messages
* @function
* @name VoxImplant.Client.getInstantMessagingHistory
* @group IM Functions
*/
VoxImplant.Client.prototype.getInstantMessagingHistory = function(uri, message_id, direction, count) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.requestHistory(uri, message_id, direction, count); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').requestHistory(uri, message_id, direction, count);
	}
};

/**
* Request chat room history. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomHistoryReceived">VoxImplant.IMEvents.ChatRoomHistoryReceived</a> 
* @param {String} room Room id
* @param {String} [message_id] Message id (to get messages sent before/after the message) 
* @param {String} [direction=false] False/true to get messages older/newer than the message with specified id
* @param {Number} [count=100] Number of messages
* @function
* @name VoxImplant.Client.getChatRoomHistory
* @group IM Functions
*/
VoxImplant.Client.prototype.getChatRoomHistory = function(room, message_id, direction, count) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.requestMUCHistory(room, message_id, direction, count); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').requestMUCHistory(room, message_id, direction, count);
	}
};

/**
* Set chat room session state info. See also <a href="VoxImplant.IMEvents.html#VoxImplant_IMEvents_ChatRoomStateUpdate">VoxImplant.IMEvents.ChatRoomStateUpdate</a> 
* @param {String} room Room id
* @param {String} status Chat session status. See <a href="VoxImplant.ChatStateType.html">VoxImplant.ChatStateType</a> enum
* @function
* @name VoxImplant.Client.setChatRoomState
* @group IM Functions
*/
VoxImplant.Client.prototype.setChatRoomState = function(room, state) {
	if (!this.connectionState()) throw new Error("NOT_CONNECTED_TO_VOXIMPLANT");
	if (this.RTCsupported && !this.useFlashOnly) { this.zingayaAPI.sendMUCChatState(room, state); } 
	else if (!this.useRTCOnly) {
		//VoxImplant.Utils.swfMovie('voximplantSWF').sendMUCChatState(room, message_id, direction, count);
	}
};

})(VoxImplant);
if (!Function.prototype.bind) {
  /** 
  * @ignore 
  */
  Function.prototype.bind = function (oThis) {
    if (typeof this !== "function") {
      // closest thing possible to the ECMAScript 5 internal IsCallable function
      throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
    }

    var aArgs = Array.prototype.slice.call(arguments, 1), 
        fToBind = this, 
        fNOP = function () {},
        fBound = function () {
          return fToBind.apply(this instanceof fNOP && oThis? this:oThis, aArgs.concat(Array.prototype.slice.call(arguments)));
        };

    fNOP.prototype = this.prototype;
    fBound.prototype = new fNOP(); 

    return fBound;
  };
}

if (!window.JSON) {
  /**
   * @ignore 
   */
  throw new Error('Unsupported browser');
}

/**
* @namespace
* @name VoxImplant
*/
(function (VoxImplant, undefined) {	
/**
* Different utilities used by other classes
* @ignore
*/
VoxImplant.Utils = {
	source: null,
	/**
	* @param objects Objects for merging
	* @ignore
	* @returns {Object}
	*/
	extend: function ( objects ) {
    	var extended = {};
	    var merge = function (obj) {
	        for (var prop in obj) {
	            if (Object.prototype.hasOwnProperty.call(obj, prop)) {
	                extended[prop] = obj[prop];
	            }
	        }
	    };
	    merge(arguments[0]);
	    for (var i = 1; i < arguments.length; i++) {
	        var obj = arguments[i];
	        merge(obj);
	    }
	    return extended;
	},
	/**
	* @param {String} movieName SWF movie name
	* @ignore
	* @returns {HTMLElement}
	*/
	swfMovie: function(movieName) {
	    if (navigator.appName.indexOf("Microsoft") != -1) return window[movieName];
	    else return document[movieName];
	},
	
	/**
	* Convert <tt>headersObj</tt> to string
	* @param {Object} headersObj Object contains headers (as properties) to stringify
	* @returns {String}
	* @ignore
	*/
	stringifyExtraHeaders: function(headersObj) {
		if (Object.prototype.toString.call(headersObj) == '[object Object]') headersObj = JSON.stringify(headersObj);
		else headersObj = null;
		return headersObj;
	},
	
	/**
	* Parse cadence sections
	* @param {String} script
	* @retruns {Object}
	* @ignore
	*/
	cadScript: function(script) {
	  var cads = script.split(';');

	  return cads.map(function (cad) {
	    if (cad.length === 0) {
	      return;
	    }
	    var matchParens = cad.match(/\([0-9\/\.,\*\+]*\)$/),
	        ringLength = cad.substring(0, matchParens.index),
	        segments = matchParens.pop();

	    if (matchParens.length) {
	      throw new Error(
	        'cadence script should be of the form `%f(%f/%f[,%f/%f])`'
	      );
	    }

	    ringLength = (ringLength === '*') ? Infinity : parseFloat(ringLength);

	    if (isNaN(ringLength)) {
	      throw new Error('cadence length should be of the form `%f`');
	    }

	    segments = segments
	      .slice(1, segments.length - 1)
	      .split(',')
	      .map(function (segment) {
	        try {
	          var onOff = segment
	            .split('/')
	          ;
	          if (onOff.length > 3) {
	            throw new Error();
	          }
	          onOff = onOff.map(function (string, i) {
	            if (i === 2) {
	              // Special rules for frequencies
	              var freqs = string
	                .split('+')
	                .map(function (f) {
	                  var integer = parseInt(f, 10);
	                  if (isNaN(integer)) {
	                    throw new Error();
	                  }
	                  return integer - 1;
	                })
	              ;
	              return freqs;
	            }

	            var flt;
	            // Special rules for Infinity;
	            if (string == '*') {
	              flt = Infinity;
	            }
	            flt = flt ? flt : parseFloat(string, 10);
	            if (isNaN(flt)) {
	              throw new Error();
	            }
	            return flt;
	          });

	          return {
	            on: onOff[0],
	            off: onOff[1],
	            // frequency is an extension for full toneScript.
	            frequencies: onOff[2]
	          };
	        }
	        catch (err) {
	          throw new Error(
	            'cadence segments should be of the form `%f/%f[%d[+%d]]`'
	          );
	        }
	      })
	    ;

	    return {
	      duration: ringLength,
	      sections: segments
	    };
	  });
	},
	
	/**
	* Parse frequency sections
	* @param {String} script
	* @returns {Object}
	* @ignore
	*/
	freqScript: function(script) {
	  var freqs = script.split(',');
	  return freqs.map(function (freq) {
	    try {
			  var tonePair = freq.split('@'),
			      frequency = parseInt(tonePair.shift()),
			      dB = parseFloat(tonePair.shift());

			  if (tonePair.length) {
			    throw Error();
			  }

	      return {
	        frequency: frequency,
	        decibels: dB
	      };
	    }
	    catch (err) {
	      throw new Error(
	        'freqScript pairs are expected to be of the form `%d@%f[,%d@%f]`'
	      );
	    }
	  });
	},
	
	/**
	* Parse full tonescripts
	* @param {String} script Tonescript string
	* @returns {Object} Object with frequencies and cadences properties
	* @ignore
	*/
	toneScript: function(script) {
	  var sections = script.split(';'),
	      frequencies = VoxImplant.Utils.freqScript(sections.shift()),
	      cadences = VoxImplant.Utils.cadScript(sections.join(';'));

	  return {
	    frequencies: frequencies,
	    cadences: cadences
	  };
	},
	
	/**
	* Plays tonescript using WebAudio API
	* @param {String} script Tonescript string to be parsed and played
	* @param {Boolean} [loop=false] Plays tonescript audio in a loop if true
	* @ignore
	*/
	playToneScript: function(script, loop) {
		if(typeof window.AudioContext != 'undefined' || typeof window.webkitAudioContext != 'undefined') {
			window.AudioContext = window.AudioContext||window.webkitAudioContext;
		    var context = new AudioContext(),
			parsedToneScript = VoxImplant.Utils.toneScript(script),
			samples = [],
			fullDuration = 0;
			
			processCadence = function(cadence) {
				if (cadence.duration != Infinity) fullDuration += cadence.duration;
				else fullDuration += 20;
				for (var i=0;i<cadence.sections.length;i++) {
					processSection(cadence.sections[i], cadence.duration);
				}
			};
			
			processSection = function(section, duration) {
				if (duration != Infinity) t = duration;
				else t = duration = 20;
				if (section.off !== 0 && section.off != Infinity) {
					while(t > 0) {
						addSound(section.frequencies, section.on);
						t -= section.on;
						addSilence(section.off);
						t -= section.off;
						t = parseInt((t)*10)/10;
					}
				} else {
					addSound(section.frequencies, duration);
				}
			};
			
			addSilence = function(sec) {
				for (var t=0; t < context.sampleRate * sec; t++) samples.push(0);
			};
			
			addSound = function(freq, sec) {
				for (var t=0; t < context.sampleRate * sec; t++) {
					var sample = 0;
					for (var f = 0; f < freq.length; f++) {
						sample += Math.pow(10, parsedToneScript.frequencies[freq[f]].decibels/20) * Math.sin((samples.length + t) * (3.14159265359 / context.sampleRate) * parsedToneScript.frequencies[freq[f]].frequency);
						if (t < 10) sample *= (t/10);
						if (t > (context.sampleRate * sec - 10)) sample *= (context.sampleRate * sec - t) / 10;
					}
					samples.push(sample);
				}
			};		
			
			this.source = context.createBufferSource();
			for (var k=0;k<parsedToneScript.cadences.length;k++) {
				if (parsedToneScript.cadences[k].duration == Infinity) this.source.loop = true;
				processCadence(parsedToneScript.cadences[k]);	
			}
			this.source.connect(context.destination);
			
			sndBuffer = context.createBuffer( 1, fullDuration * context.sampleRate, context.sampleRate );
			bufferData = sndBuffer.getChannelData(0);
			for (var i=0;i<fullDuration * context.sampleRate; i++) {
				bufferData[i] = samples[i];
			}
			samples = null;
			this.source.buffer = sndBuffer;
			if (loop===true) this.source.loop = true;
			this.source.start(0);

		}
	},
	
	/**
	* Stops tonescript audio playback
	* @returns {Boolean} True if audio playback was stopped
	* @ignore
	*/
	stopPlayback: function() {
		if (this.source !== null) {
			this.source.stop(0);
			this.source = null;
			return true;
		}
		return false;
	},
	
	/**
	* Makes cross-browser XmlHttpRequest 
	* @param {String} url URL for HTTP request
	* @param {Function} [callback] Function to be called on completion
	* @param {Function} [error] Function to be called in case of error
	* @param {String} [postData] Data to be sent with POST request
	* @ignore
	*/
	sendRequest: function(url,callback,error,postData) {
		var xdr = false;
		var createXMLHTTPObject = function() {

			var XMLHttpFactories = [
				function () {return new XDomainRequest();},
			    function () {return new XMLHttpRequest();},
			    function () {return new ActiveXObject("Msxml2.XMLHTTP");},
			    function () {return new ActiveXObject("Msxml3.XMLHTTP");},
			    function () {return new ActiveXObject("Microsoft.XMLHTTP");}
			];

		    var xmlhttp = false;
		    for (var i=0;i<XMLHttpFactories.length;i++) {
		        try {
		            xmlhttp = XMLHttpFactories[i]();
		            if (i===0) xdr = true;
		        }
		        catch (e) {
		            continue;
		        }
		        break;
		    }
		    return xmlhttp;
		};
		
	    var req = createXMLHTTPObject();
	    if (!req) return;
	    var method = (postData) ? "POST" : "GET";
	    if (!xdr) {
		    req.open(method,url,true);
		    if (postData) req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		    req.onreadystatechange = function () {
		        if (req.readyState != 4) return;
		        if (req.status != 200 && req.status != 304) {
	         		error(req);
		            return;
		        }
		        callback(req);
		    };
		    if (req.readyState == 4) return;
		    req.send(postData);
		} else {
			req.onerror = function(){error(req);};
    		req.ontimeout = function(){error(req);};
    		req.onload = function() {callback(req);};
    		req.open(method, url);
    		req.timeout = 5000;
    		req.send();
		}
	},
	
	/**
	* Makes request to VoxImplant Load Balancer to get media gateway IP address
	* @param {Function} callback Function to be called on completion
	* @param {Boolean} [reservedBalancer=false] Try reserved balancer if true
	* @ignore
	*/
	getServers: function(callback, reservedBalancer, vi) {
		var protocol = ('https:' == document.location.protocol ? 'https://' : 'http://');
		if (reservedBalancer === true) balancer_url = protocol + "balancer.voximplant.com/getNearestHost";
		else balancer_url = protocol + "balancer.voximplant.com/getNearestHost";
		VoxImplant.Utils.sendRequest(balancer_url, function(XHR) {balancerComplete(XHR.responseText);}, function(XHR) { balancerComplete(null); });
		function balancerComplete(data) {
			if (data !== null) callback(data);
			else if (reservedBalancer !== true) VoxImplant.Utils.getServers(callback, true, vi);
			else vi.dispatchEvent({name:'ConnectionFailed', message: "VoxImplant Cloud is unavailable"});
		}
	},

	/**
	 * @ignore
	 * The simplest function to get an UUID string.
	 * @returns {string} A version 4 UUID string.
	 */
	generateUUID: function() {
		var rand = VoxImplant.Utils._gri, hex = VoxImplant.Utils._ha;
		return  hex(rand(32), 8) + 
			"-" + 
		    hex(rand(16), 4) +
		    "-" +
		    hex(0x4000 | rand(12), 4) +
		    "-" +
		    hex(0x8000 | rand(14), 4) +
		    "-" +
		    hex(rand(48), 12);
	},

	/**
	 * Returns an unsigned x-bit random integer.
	 * @ignore
	 * @param {int} x A positive integer ranging from 0 to 53, inclusive.
	 * @returns {int} An unsigned x-bit random integer (0 <= f(x) < 2^x).
	 */
	_gri: function(x) { // _getRandomInt
		if (x <   0) return NaN;
		if (x <= 30) return (0 | Math.random() * (1 << x));
		if (x <= 53) return (0 | Math.random() * (1 << 30)) + (0 | Math.random() * (1 << x - 30)) * (1 << 30);
		return NaN;
	},

	/**
	 * Converts an integer to a zero-filled hexadecimal string.
	 * @ignore
	 * @param {int} num
	 * @param {int} length
	 * @returns {string}
	 */
	_ha: function(num, length) {  // _hexAligner
		var str = num.toString(16), i = length - str.length, z = "0";
		for (; i > 0; i >>>= 1, z += z) { if (i & 1) { str = z + str; } }
		return str;
	},

	filterXSS: function(content) {
		var div = document.createElement("div");
		div.appendChild(document.createTextNode(content));
		content = div.innerHTML;
		return content;
	}
};

/**
* Get <a href="VoxImplant.Client.html">VoxImplant.Client</a> instance to use platform functions
* @function
* @returns {VoxImplant.Client}
*/
VoxImplant.getInstance = function() {
	return VoxImplant._clientInstance;
};

/**
* VoxImplant Web SDK lib version
* @function
* @returns {String} SDK lib version number
*/
VoxImplant.version = VoxImplant.Client.prototype.version = "3.6.374";

if (!VoxImplant._clientInstance) {
	VoxImplant._clientInstance = new VoxImplant.Client();
	delete VoxImplant.Client;
}

})(VoxImplant);