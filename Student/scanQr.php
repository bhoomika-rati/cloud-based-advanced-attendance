<?php
require_once __DIR__ . '/Includes/student_context.php';
$pageTitle = 'Scan QR Code';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading">
    <div><h1>Scan attendance QR</h1><p>Use your camera to scan the active session QR displayed by faculty.</p></div>
    <a class="student-action secondary" href="student_attendance.php"><i class="fas fa-history"></i> Attendance history</a>
</header>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <section class="student-card">
            <div class="camera-frame mb-3" style="overflow:hidden;min-height:300px;border-radius:14px;background:#192b49">
                <video id="camera-view" playsinline muted style="display:block;width:100%;min-height:300px;object-fit:cover"></video>
            </div>
            <div id="scanner-message" class="student-alert warning mb-3" role="status" aria-live="polite">Start your camera and point it at the active attendance QR.</div>
            <div class="d-flex flex-wrap" style="gap:8px">
                <button type="button" class="student-action" id="start-camera"><i class="fas fa-camera"></i> Start camera</button>
                <button type="button" class="student-action secondary" id="stop-camera" disabled>Stop camera</button>
            </div>
            <p class="small student-muted mt-3 mb-0">Camera access requires HTTPS and browser permission. If your browser cannot scan QR codes, use your phone camera to open the faculty QR link.</p>
        </section>
    </div>
</div>
<script>
(function () {
    var video = document.getElementById('camera-view');
    var message = document.getElementById('scanner-message');
    var startButton = document.getElementById('start-camera');
    var stopButton = document.getElementById('stop-camera');
    var stream = null;
    var detector = null;
    var scanning = false;
    var frameRequest = null;

    function setMessage(text, kind) {
        message.textContent = text;
        message.className = 'student-alert ' + kind + ' mb-3';
    }

    function stopCamera() {
        scanning = false;
        if (frameRequest !== null) window.cancelAnimationFrame(frameRequest);
        frameRequest = null;
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        video.srcObject = null;
        startButton.disabled = false;
        stopButton.disabled = true;
    }

    function visitAttendanceUrl(rawValue) {
        var scannedUrl;
        try {
            scannedUrl = new URL(rawValue);
        } catch (error) {
            return false;
        }
        var expectedPath = window.location.pathname.replace(/scanQr\.php$/, 'markAttendance.php');
        var token = scannedUrl.searchParams.get('token') || '';
        if (scannedUrl.origin !== window.location.origin || scannedUrl.pathname !== expectedPath
            || !/^[a-f0-9]{64}$/i.test(token)) {
            return false;
        }
        stopCamera();
        window.location.assign(scannedUrl.href);
        return true;
    }

    function scanFrame() {
        if (!scanning || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            if (scanning) frameRequest = window.requestAnimationFrame(scanFrame);
            return;
        }
        detector.detect(video).then(function (codes) {
            for (var index = 0; index < codes.length; index++) {
                if (visitAttendanceUrl(codes[index].rawValue)) return;
            }
            if (scanning) frameRequest = window.requestAnimationFrame(scanFrame);
        }).catch(function () {
            stopCamera();
            setMessage('Unable to read a QR code with this camera. Try again or use your phone camera app.', 'warning');
        });
    }

    startButton.addEventListener('click', function () {
        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setMessage('Camera access needs HTTPS and a supported browser. Use your phone camera app to scan the QR instead.', 'warning');
            return;
        }
        if (!('BarcodeDetector' in window)) {
            setMessage('This browser does not support in-page QR scanning. Use your phone camera app to scan the attendance QR.', 'warning');
            return;
        }
        startButton.disabled = true;
        setMessage('Requesting camera access…', 'info');
        window.BarcodeDetector.getSupportedFormats().then(function (formats) {
            if (formats.indexOf('qr_code') === -1) throw new Error('QR scanning is not supported by this browser.');
            detector = new window.BarcodeDetector({formats: ['qr_code']});
            return navigator.mediaDevices.getUserMedia({video: {facingMode: {ideal: 'environment'}}, audio: false});
        }).then(function (cameraStream) {
            stream = cameraStream;
            video.srcObject = stream;
            return video.play();
        }).then(function () {
            scanning = true;
            stopButton.disabled = false;
            setMessage('Camera is on. Point it at the active faculty QR.', 'success');
            scanFrame();
        }).catch(function (error) {
            stopCamera();
            setMessage(error && error.name === 'NotAllowedError'
                ? 'Camera permission was denied. Allow camera access or use your phone camera app.'
                : 'Unable to start QR scanning in this browser. Use your phone camera app instead.', 'warning');
        });
    });

    stopButton.addEventListener('click', function () {
        stopCamera();
        setMessage('Camera stopped.', 'warning');
    });
    window.addEventListener('pagehide', stopCamera);
}());
</script>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
