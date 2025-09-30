<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!isMobileDevice()) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اسکن QR Code - دیسکورد</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #36393f;
            color: white;
            text-align: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #7289da;
            margin-bottom: 10px;
        }
        
        .permission-request {
            background: #2f3136;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .permission-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .permission-text {
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.8;
        }
        
        .btn {
            background: #7289da;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s;
            font-weight: 500;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5b73c4;
            transform: translateY(-2px);
        }
        
        .btn-large {
            padding: 18px 36px;
            font-size: 18px;
        }
        
        .btn-secondary {
            background: #4f545c;
        }
        
        .btn-secondary:hover {
            background: #5d6269;
        }
        
        .btn-success {
            background: #3ba55c;
        }
        
        .btn-success:hover {
            background: #2d8c4a;
        }
        
        .btn:disabled {
            background: #4f545c;
            cursor: not-allowed;
            transform: none;
        }
        
        .scanner-section {
            background: #2f3136;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: none;
        }
        
        #video-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #video {
            width: 100%;
            height: 300px;
            background: #000;
            border-radius: 8px;
            object-fit: cover;
        }
        
        #scan-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #7289da;
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }
        
        .scan-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #7289da;
            animation: scan 2s linear infinite;
        }
        
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
        
        .controls {
            margin: 20px 0;
        }
        
        .status {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        
        .status.success {
            background: #3ba55c;
            color: white;
        }
        
        .status.error {
            background: #ed4245;
            color: white;
        }
        
        .status.info {
            background: #7289da;
            color: white;
        }
        
        .status.warning {
            background: #faa81a;
            color: white;
        }
        
        .instructions {
            background: #40444b;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: right;
        }
        
        .instructions ol {
            text-align: right;
            padding-right: 20px;
        }
        
        .instructions li {
            margin: 10px 0;
            line-height: 1.8;
        }
        
        .permission-steps {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .step {
            flex: 1;
            min-width: 200px;
            margin: 10px;
            padding: 20px;
            background: #40444b;
            border-radius: 8px;
        }
        
        .step-number {
            background: #7289da;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: bold;
        }
        
        .camera-access-info {
            background: #40444b;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: right;
        }
        
        .browser-support {
            margin-top: 20px;
            font-size: 14px;
            color: #b9bbbe;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>اسکن QR Code</h1>
            <p>برای ورود سریع در کامپیوتر، کد QR را اسکن کنید</p>
        </div>
        
        <!-- بخش درخواست دسترسی دوربین -->
        <div id="permission-request" class="permission-request">
            <div class="permission-icon">📷</div>
            <h2>برای اسکن QR Code به دوربین نیاز داریم</h2>
            
            <div class="permission-text">
                <p>برنامه دیسکورد برای اسکن کد QR نیاز به دسترسی به دوربین دستگاه شما دارد.</p>
                <p>این دسترسی فقط برای اسکن کد QR استفاده می‌شود و هیچ تصویری ذخیره نمی‌شود.</p>
            </div>
            
            <div class="permission-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <p>روی "اجازه دسترسی به دوربین" کلیک کنید</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <p>در پنجره مرورگر، گزینه "Allow" یا "اجازه" را انتخاب کنید</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <p>کد QR را در مرکز کادر قرار دهید</p>
                </div>
            </div>
            
            <div class="camera-access-info">
                <h4>💡 نکات مهم:</h4>
                <ul>
                    <li>مطمئن شوید که دوربین دستگاه شما فعال است</li>
                    <li>در محیطی با نور کافی قرار بگیرید</li>
                    <li>کد QR باید کاملاً درون کادر اسکن باشد</li>
                    <li>این دسترسی فقط برای همین صفحه کاربرد دارد</li>
                </ul>
            </div>
            
            <button id="request-permission-btn" class="btn btn-large">
                📷 اجازه دسترسی به دوربین
            </button>
            
            <div id="permission-status" class="status info" style="display: none;">
                در حال درخواست دسترسی...
            </div>
            
            <div class="browser-support">
                <p>پشتیبانی از: Chrome, Firefox, Safari, Edge (نسخه‌های جدید)</p>
            </div>
        </div>
        
        <!-- بخش اسکنر (در ابتدا مخفی) -->
        <div id="scanner-section" class="scanner-section">
            <div id="video-container">
                <video id="video" playsinline></video>
                <div id="scan-overlay">
                    <div class="scan-line"></div>
                </div>
            </div>
            
            <div class="controls">
                <button id="switch-camera" class="btn btn-secondary">🔄 تعویض دوربین</button>
                <button id="stop-scanner" class="btn btn-secondary">⏹ توقف اسکن</button>
            </div>
            
            <div id="scanner-status" class="status info">
                دوربین فعال است. کد QR را در مرکز قرار دهید.
            </div>
        </div>
        
        <!-- بخش راهنما -->
        <div class="instructions">
            <h3>📋 راهنمای اسکن QR Code</h3>
            <ol>
                <li>در کامپیوتر خود، به صفحه <strong>ورود به دیسکورد</strong> بروید</li>
                <li>روی گزینه <strong>"ورود با QR Code"</strong> کلیک کنید</li>
                <li>کد QR نمایش داده شده در کامپیوتر را در این صفحه اسکن کنید</li>
                <li>به طور خودکار در کامپیوتر وارد حساب کاربری خواهید شد</li>
            </ol>
        </div>

        <!-- راهنمای عیب‌یابی -->
        <div class="instructions">
            <h3>🔧 راهنمای عیب‌یابی</h3>
            <ul>
                <li>اگر دسترسی رد شد، <strong>تنظیمات حریم خصوصی مرورگر</strong> را بررسی کنید</li>
                <li>مطمئن شوید که <strong>دوربین دستگاه شما کار می‌کند</strong></li>
                <li>اگر دوربین روشن نمی‌شود، صفحه را <strong>رفرش</strong> کنید و مجدد تلاش کنید</li>
                <li>در محیطی با <strong>نور کافی</strong> اسکن کنید</li>
                <li>کد QR باید <strong>کاملاً درون کادر سبز رنگ</strong> باشد</li>
            </ul>
        </div>
    </div>

    <!-- کتابخانه jsQR -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    
    <script>
        // متغیرهای全局
        let videoStream = null;
        let currentFacingMode = 'environment';
        let isScanning = false;
        let scanAnimationFrame = null;
        
        // المنت‌ها
        const video = document.getElementById('video');
        const permissionRequest = document.getElementById('permission-request');
        const scannerSection = document.getElementById('scanner-section');
        const requestPermissionBtn = document.getElementById('request-permission-btn');
        const permissionStatus = document.getElementById('permission-status');
        const scannerStatus = document.getElementById('scanner-status');
        const switchCameraBtn = document.getElementById('switch-camera');
        const stopScannerBtn = document.getElementById('stop-scanner');

        // درخواست دسترسی دوربین (فقط در پاسخ به کلیک کاربر)
        async function requestCameraPermission() {
            try {
                console.log('درخواست دسترسی دوربین آغاز شد...');
                
                // نمایش وضعیت
                permissionStatus.style.display = 'block';
                permissionStatus.textContent = 'در حال درخواست دسترسی از مرورگر...';
                permissionStatus.className = 'status info';
                requestPermissionBtn.disabled = true;
                requestPermissionBtn.textContent = '⏳ در حال درخواست...';
                
                // بررسی پشتیبانی مرورگر
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('مرورگر شما از دسترسی به دوربین پشتیبانی نمی‌کند');
                }
                
                // درخواست دسترسی به دوربین
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: currentFacingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });
                
                console.log('دسترسی دوربین داده شد');
                
                // موفقیت‌آمیز
                permissionStatus.textContent = '✅ دسترسی داده شد! در حال راه‌اندازی دوربین...';
                permissionStatus.className = 'status success';
                
                // نمایش اسکنر
                setTimeout(() => {
                    permissionRequest.style.display = 'none';
                    scannerSection.style.display = 'block';
                    startCameraWithStream(stream);
                }, 1000);
                
            } catch (error) {
                console.error('خطا در دسترسی به دوربین:', error);
                requestPermissionBtn.disabled = false;
                requestPermissionBtn.textContent = '📷 اجازه دسترسی به دوربین';
                
                let errorMessage = 'خطا در دسترسی به دوربین: ';
                
                if (error.name === 'NotAllowedError') {
                    errorMessage = '❌ دسترسی به دوربین رد شد. لطفاً در تنظیمات مرورگر اجازه دسترسی را فعال کنید.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage = '❌ دوربین یافت نشد. مطمئن شوید دوربین شما کار می‌کند.';
                } else if (error.name === 'NotSupportedError') {
                    errorMessage = '❌ مرورگر شما از این قابلیت پشتیبانی نمی‌کند.';
                } else if (error.name === 'NotReadableError') {
                    errorMessage = '❌ دوربین در حال استفاده توسط برنامه دیگر است.';
                } else if (error.name === 'OverconstrainedError') {
                    errorMessage = '❌ دوربین مورد نظر پشتیبانی نمی‌شود.';
                } else {
                    errorMessage = '❌ خطای ناشناخته: ' + error.message;
                }
                
                permissionStatus.textContent = errorMessage;
                permissionStatus.className = 'status error';
                
                // نمایش راهنمای بیشتر
                setTimeout(() => {
                    const troubleshooting = document.createElement('div');
                    troubleshooting.className = 'instructions';
                    troubleshooting.innerHTML = `
                        <h4>راه‌حل‌های پیشنهادی:</h4>
                        <ul>
                            <li>صفحه را رفرش کنید و مجدد تلاش کنید</li>
                            <li>از مرورگرهای Chrome, Firefox, یا Edge استفاده کنید</li>
                            <li>مطمئن شوید که سایت از HTTPS استفاده می‌کند</li>
                            <li>در تنظیمات مرورگر، دسترسی دوربین را برای این سایت فعال کنید</li>
                        </ul>
                    `;
                    permissionStatus.after(troubleshooting);
                }, 1000);
            }
        }

        // شروع دوربین با stream داده شده
        function startCameraWithStream(stream) {
            videoStream = stream;
            video.srcObject = stream;
            
            video.onloadedmetadata = () => {
                video.play().then(() => {
                    console.log('دوربین فعال شد');
                    scannerStatus.textContent = '🔍 دوربین فعال است. در حال اسکن QR Code...';
                    scannerStatus.className = 'status success';
                    isScanning = true;
                    scanQRCode();
                }).catch(error => {
                    console.error('خطا در پخش ویدیو:', error);
                    scannerStatus.textContent = '❌ خطا در فعال‌سازی دوربین';
                    scannerStatus.className = 'status error';
                });
            };
        }

        // تابع اسکن QR Code
        function scanQRCode() {
            if (!isScanning) return;
            
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });
                
                if (code) {
                    console.log('QR Code شناسایی شد:', code.data);
                    try {
                        const qrData = JSON.parse(code.data);
                        
                        if (qrData.action === 'login' && qrData.token) {
                            scannerStatus.textContent = '✅ QR Code شناسایی شد! در حال ورود...';
                            scannerStatus.className = 'status success';
                            isScanning = false;
                            
                            processQRToken(qrData.token);
                            return;
                        }
                    } catch (e) {
                        console.error('خطا در پردازش QR Code:', e);
                    }
                }
            }
            
            if (isScanning) {
                scanAnimationFrame = requestAnimationFrame(scanQRCode);
            }
        }

        // تابع پردازش توکن QR
        async function processQRToken(token) {
            try {
                const response = await fetch('process_qr_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        qr_token: token,
                        user_id: <?= $_SESSION['user_id'] ?? 'null' ?>
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    scannerStatus.textContent = '✅ ورود موفق! در کامپیوتر وارد شدید.';
                    scannerStatus.className = 'status success';
                    
                    setTimeout(() => {
                        window.location.href = 'index.php?login=success';
                    }, 2000);
                    
                } else {
                    scannerStatus.textContent = '❌ خطا در ورود: ' + (result.message || 'خطای ناشناخته');
                    scannerStatus.className = 'status error';
                    // ادامه اسکن پس از 3 ثانیه
                    setTimeout(() => {
                        isScanning = true;
                        scanQRCode();
                    }, 3000);
                }
                
            } catch (error) {
                console.error('خطا در پردازش توکن:', error);
                scannerStatus.textContent = '❌ خطا در ارتباط با سرور';
                scannerStatus.className = 'status error';
                // ادامه اسکن پس از 3 ثانیه
                setTimeout(() => {
                    isScanning = true;
                    scanQRCode();
                }, 3000);
            }
        }

        // تعویض دوربین
        async function switchCamera() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
            
            if (scanAnimationFrame) {
                cancelAnimationFrame(scanAnimationFrame);
            }
            
            isScanning = false;
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
            
            // نمایش مجدد بخش درخواست دسترسی
            scannerSection.style.display = 'none';
            permissionRequest.style.display = 'block';
            permissionStatus.style.display = 'none';
            requestPermissionBtn.disabled = false;
            requestPermissionBtn.textContent = '📷 اجازه دسترسی به دوربین';
        }

        // توقف اسکنر
        function stopScanner() {
            isScanning = false;
            
            if (scanAnimationFrame) {
                cancelAnimationFrame(scanAnimationFrame);
            }
            
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            
            scannerSection.style.display = 'none';
            permissionRequest.style.display = 'block';
            requestPermissionBtn.disabled = false;
            requestPermissionBtn.textContent = '📷 اجازه دسترسی به دوربین';
            permissionStatus.style.display = 'none';
        }

        // event listeners
        requestPermissionBtn.addEventListener('click', requestCameraPermission);
        switchCameraBtn.addEventListener('click', switchCamera);
        stopScannerBtn.addEventListener('click', stopScanner);

        // بررسی اولیه پشتیبانی مرورگر
        document.addEventListener('DOMContentLoaded', function() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                permissionStatus.style.display = 'block';
                permissionStatus.textContent = '❌ مرورگر شما از دسترسی به دوربین پشتیبانی نمی‌کند. لطفاً از Chrome, Firefox, یا Edge استفاده کنید.';
                permissionStatus.className = 'status error';
                requestPermissionBtn.disabled = true;
            }
        });

        // مدیریت زمانی که کاربر تب را تغییر می‌دهد
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isScanning) {
                console.log('تب غیرفعال شد - توقف اسکن موقت');
                isScanning = false;
            } else if (!document.hidden && videoStream && !isScanning) {
                console.log('تب فعال شد - ادامه اسکن');
                isScanning = true;
                scanQRCode();
            }
        });
    </script>
</body>
</html>