/**
 * Face-API.js integration for enrollment and verification.
 * Loads face-api.js from local /js/ first, then CDN fallbacks. Models from CDN.
 */
(function () {
  'use strict';

  var LOCAL_SCRIPT = '/js/face-api.min.js';
  var CDN_SCRIPT_URLS = [
    'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/dist/face-api.min.js',
    'https://unpkg.com/face-api.js@0.22.2/build/face-api.min.js'
  ];
  var MODELS_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights/';
  var scriptLoadPromise = null;
  var modelsLoaded = false;

  function loadOneScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.async = false;
      s.onload = function () {
        if (typeof faceapi !== 'undefined') resolve();
        else reject(new Error('faceapi absent'));
      };
      s.onerror = function () { reject(new Error('load failed')); };
      document.head.appendChild(s);
    });
  }

  function loadScript() {
    if (typeof faceapi !== 'undefined') return Promise.resolve();
    if (scriptLoadPromise) return scriptLoadPromise;
    var urls = [LOCAL_SCRIPT].concat(CDN_SCRIPT_URLS);
    scriptLoadPromise = urls.reduce(function (promise, url) {
      return promise.catch(function () { return loadOneScript(url); });
    }, Promise.reject()).catch(function () {
      scriptLoadPromise = null;
      return Promise.reject(new Error('Impossible de charger face-api.js (local et CDN). Vérifiez que public/js/face-api.min.js existe.'));
    });
    return scriptLoadPromise;
  }

  window.FaceAuth = {
    init: function () {
      return loadScript().then(function () {
        if (modelsLoaded) return Promise.resolve();
        return Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
          faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL),
          faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL)
        ]).then(function () {
          modelsLoaded = true;
        });
      });
    },

    waitForVideoReady: function (videoEl) {
      return new Promise(function (resolve) {
        window._faceDebug && window._faceDebug('waitForVideoReady start, readyState=' + videoEl.readyState + ' videoWidth=' + videoEl.videoWidth);
        if (videoEl.readyState >= 2 && videoEl.videoWidth > 0) {
          window._faceDebug && window._faceDebug('waitForVideoReady OK (already ready)');
          resolve();
          return;
        }
        function check() {
          if (videoEl.readyState >= 2 && videoEl.videoWidth > 0) {
            videoEl.removeEventListener('loadeddata', check);
            videoEl.removeEventListener('playing', check);
            window._faceDebug && window._faceDebug('waitForVideoReady OK (event)');
            resolve();
          }
        }
        videoEl.addEventListener('loadeddata', check);
        videoEl.addEventListener('playing', check);
        check();
        setTimeout(function () {
          videoEl.removeEventListener('loadeddata', check);
          videoEl.removeEventListener('playing', check);
          window._faceDebug && window._faceDebug('waitForVideoReady OK (timeout 3s)');
          resolve();
        }, 3000);
      });
    },

    getDescriptorFromVideo: function (videoEl, timeoutMs) {
      var self = this;
      timeoutMs = timeoutMs || 20000;
      window._faceDebug && window._faceDebug('getDescriptorFromVideo START timeout=' + timeoutMs + 'ms');
      if (!modelsLoaded) {
        window._faceDebug && window._faceDebug('getDescriptorFromVideo REJECT models not loaded');
        return Promise.reject(new Error('Modèles non chargés. Appelez FaceAuth.init() d\'abord.'));
      }
      var detectionPromise = self.waitForVideoReady(videoEl).then(function () {
        window._faceDebug && window._faceDebug('getDescriptorFromVideo: video ready, calling detectSingleFace+landmarks+descriptor...');
        var opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.35 });
        return faceapi
          .detectSingleFace(videoEl, opts)
          .withFaceLandmarks()
          .withFaceDescriptor()
          .then(function (result) {
            window._faceDebug && window._faceDebug('getDescriptorFromVideo: face-api DONE result=' + (result ? 'descriptor length ' + (result.descriptor && result.descriptor.length) : 'null'));
            return result ? result.descriptor : null;
          })
          .catch(function (err) {
            window._faceDebug && window._faceDebug('getDescriptorFromVideo: face-api CATCH ' + (err && (err.message || err)));
            return Promise.reject(err && err.message ? err : new Error('Erreur lors du calcul du visage.'));
          });
      }).catch(function (err) {
        window._faceDebug && window._faceDebug('getDescriptorFromVideo: outer CATCH ' + (err && (err.message || err)));
        return Promise.reject(err && err.message ? err : new Error('Erreur détection.'));
      });
      var timeoutPromise = new Promise(function (_, reject) {
        setTimeout(function () {
          window._faceDebug && window._faceDebug('getDescriptorFromVideo: TIMEOUT after ' + timeoutMs + 'ms');
          reject(new Error('Trop long. Réessayez (fermez d\'autres onglets si besoin).'));
        }, timeoutMs);
      });
      return Promise.race([detectionPromise, timeoutPromise]);
    },

    /** Quick check: is a face visible? Call every 1s for live feedback. No descriptor. */
    isFaceVisible: function (videoEl) {
      if (!modelsLoaded) return Promise.resolve(false);
      var opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.35 });
      return faceapi.detectSingleFace(videoEl, opts).then(function (d) { return !!d; });
    },

    startCamera: function (videoEl) {
      return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } }).then(function (stream) {
        videoEl.srcObject = stream;
        videoEl.muted = true;
        videoEl.playsInline = true;
        return videoEl.play().then(function () { return stream; }, function () { return stream; });
      });
    },

    stopCamera: function (stream) {
      if (stream && stream.getTracks) {
        stream.getTracks().forEach(function (t) {
          t.stop();
        });
      }
    }
  };
})();
