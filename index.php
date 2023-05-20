<!DOCTYPE html><html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0,minimal-ui:ios">
<?php
require_once 'vendor/autoload.php';
require_once 'lang.php';
require_once 'config.php';

use GeoIp2\Database\Reader;

class DeviceType {
    const OTHER = 0;
    const ANDROID = 1;
    const IOS = 2;
}

class gotoAppStore {
    /**
     * @description: 獲取系統型別
     * @return int [DeviceType] 系統型別
     */
    function getDeviceType(): int {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
            return DeviceType::IOS;
        }
        if (strpos($agent, 'android')) {
            return DeviceType::ANDROID;
        }
        return DeviceType::OTHER;
    }

    /**
     * @description: 獲取該地址所在國家
     * @param String ip IP 地址
     * @param String geofile GeoLite2-Country.mmdb 檔案路徑
     * @param Bool isCity 是否返回城市
     * @return String 國家代碼
     */
    function getCountry(string $ip, string $geofile = 'Country.mmdb', bool $isCity = false): string {
        $cityDbReader = new Reader($geofile);
        $record = $isCity ? $cityDbReader->city($ip) : $cityDbReader->country($ip);
        return strtoupper($record->country->isoCode);
    }

    /**
     * @description: 獲取客戶端 IP 地址
     * @return String IP 地址
     */
    function getRealIP(): string {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        return $ip;
    }

    /**
     * @description: 返回 404 頁面
     */
    function notFound() {
        header("HTTP/1.1 404 Not Found", true, 404);
        echo Lang::l("Application not found");
        exit('</body></html>');
    }

    /**
     * @description: 獲取下載地址
     * @return String 下載地址
     */
    function getDLURL(): string {
        $dlAPP = $_GET['app'] ?? $_GET['APP'] ?? $_POST['app'] ?? $_POST['APP'] ?? "";
        $dlAPP = strtolower($dlAPP);
        if (empty($dlAPP) || !isset(URLs::l[$dlAPP])) {
            $this->notFound();
            return '';
        }
        $ip = $this->getRealIP();
        $country = $this->getCountry($ip);
        $deviceType = $this->getDeviceType();
        $url = "";
        if ($deviceType == DeviceType::IOS) {
            $url = Hands::AppleAppStore . strtolower($country) . URLs::l[$dlAPP]["ios"];
        } elseif ($deviceType == DeviceType::ANDROID) {
            if (!isset(URLs::l[$dlAPP]["android"]) && !isset(URLs::l[$dlAPP]["apk"])) {
                $this->showAllDL($dlAPP, $country);
                return '';
            }
            if ($country == "CN") {
                $url = URLs::l[$dlAPP]["apk"];
                if (empty($url)) {
                    $url = Hands::GooglePlayStore . URLs::l[$dlAPP]["android"];
                }
            } else {
                $url = Hands::GooglePlayStore . URLs::l[$dlAPP]["android"];
            }
        }
        if (empty($url)) {
            $this->showAllDL($dlAPP, $country);
            return '';
        }
        return $url;
    }

    /**
     * @description: 輸出用於跳轉到目標地址的網頁
     * @return String HTML
     */
    function showHMTL() {
        $url = $this->getDLURL();
        if (empty($url)) {
            return;
        }
        $txt = Lang::l('Redirecting');
        echo "<title>$txt...</title></head><body><p>$txt...</p><p><a href=\"$url\">$url</a></p>";
        header('Location: ' . $url, true, 302);
    }

    /**
     * @description: 輸出包含所有下載地址的網頁
     * @param String dlAPP APP 名稱
     * @param String country 國家代碼
     */
    function showAllDL(string $dlAPP, string $country = "US") {
        $syss = URLs::l[$dlAPP];
        $name = Lang::l($syss["name"]);
        $qrimg = 'qrcode/' . $dlAPP . '.png';
        $qrinfo = Lang::l('Use the "Camera" app to scan the QR code, automatically identify the system and download');
        $txt = Lang::l('APP Download');
        $html = "<title>$name $txt</title></head><body style=\"text-align:center;\"><h1>$name</h1><img src=\"$qrimg\" title=\"$qrinfo\" alt=\"$name\" /><br/><div style=\"font-size:small;\">$qrinfo</div><br/>";
        $txt = Lang::l('Download on the App Store');
        if (isset($syss["ios"]) && !empty($syss["ios"])) {
            $html .= '<p><a title="' . $txt . '" href="' . str_replace('itms-apps', 'https', Hands::AppleAppStore) . strtolower($country) . $syss["ios"] . '" target="_blank"><img src="' . Images::AppleAppStore . '" alt="' . $txt . '" /></a></p>';
        }
        $txt = Lang::l('Get it on Google Play');
        if (isset($syss["android"]) && !empty($syss["android"])) {
            $html .= '<p><a title="' . $txt . '" href="' . Hands::GooglePlayStore . $syss["android"] . '" target="_blank"><img src="' . Images::GooglePlayStore . '" alt="' . $txt . '" /></a></p>';
        }
        $txt = Lang::l('Download Android APK');
        if (isset($syss["apk"]) && !empty($syss["apk"])) {
            $html .= '<p><a title="' . $txt . '" href="' . $syss["apk"] . '" target="_blank"><img src="' . Images::APK . '" alt="' . $txt . '" /></a></p>';
        }
        echo $html;
    }
}

$gotoAppStore = new gotoAppStore();
$gotoAppStore->showHMTL();
?>
<br/><p style="font-size:small;"><a href="https://beian.miit.gov.cn/" target="_blank">京ICP备18012125号-1</a></p>
</body></html>