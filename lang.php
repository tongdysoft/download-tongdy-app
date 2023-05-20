<?php
class Lang {
    const langData = [
        'Redirecting' => [
            'zh' => '正在跳转',
            'es' => 'Redirigiendo'
        ],
        'APP Download' => [
            'zh' => 'APP 下载',
            'es' => 'Descarga de APP'
        ],
        'Use the "Camera" app to scan the QR code, automatically identify the system and download' => [
            'zh' => '使用“相机”应用程序扫描二维码，自动识别系统并下载',
            'es' => 'Use la aplicación "Cámara" para escanear el código QR, identifique automáticamente el sistema y descargue'
        ],
        'Download on the App Store' => [
            'zh' => '从 App Store 下载',
            'es' => 'Descargar en App Store'
        ],
        'Get it on Google Play' => [
            'zh' => '从 Google Play 下载',
            'es' => 'Descargar en Google Play'
        ],
        'Download Android APK' => [
            'zh' => '下载安卓 APK',
            'es' => 'Descargar APK de Android'
        ],
        'BHand' => [
            'zh' => '蓝函',
        ],
        'Application not found' => [
            'zh' => '未找到应用',
            'es' => 'Aplicación no encontrada'
        ],
    ];
    static function l(string $info) {
        $lang = $_GET['lang'] ?? $_GET['LANG'] ?? $_POST['lang'] ?? $_POST['LANG'] ?? "";
        if (empty($lang)) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
        $lang = strtolower($lang);
        if (!isset(self::langData[$info][$lang])) {
            return $info;
        }
        return self::langData[$info][$lang];
    }
}
