<?php
require_once 'vendor/autoload.php';

use GeoIp2\Database\Reader;

$urls = [
    "mytongdy" => [
        "ios" => "/app/id1473098643",
        "android" => "",
        "apk" => "MT-Handy_2.1.9.apk",
    ],
    "bhand" => [
        "ios" => "/app/id6449812443",
        "android" => "com.tongdy.tdbleconfig",
        "apk" => "BHand_1.0.2.apk",
    ],
    "tdwifiservice" => [
        "ios" => "/app/id1497890956",
        "android" => "",
        "apk" => "",
    ]
];

class Hands {
    const AppleAppStore = "itms-apps://apps.apple.com/"; // +cn+
    const GooglePlayStore = "https://play.google.com/store/apps/details?id=";
}

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
        /* json_encode($record):
    {
        "country": {
            "iso_code": "US"
        },
        "traits": {
            "ip_address": "128.101.101.101",
            "prefix_len": 16
        }
    }
    */
        return strtoupper($record->country->isoCode);
    }

    function getRealIP(): string {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        return $ip;
    }

    function notFound() {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $lang = substr($lang, 0, 2);
        $info = '';
        if ($lang == "zh") {
            $info = '找不到要下载的程序，可能是这个程序目前不支持当前设备。';
        } elseif ($lang == "es") {
            $info = 'No se pudo encontrar el programa para descargar. Es posible que el programa no sea actualmente compatible con el dispositivo actual.';
        } else {
            $info = 'The program to download could not be found. It may be that the program does not currently support the current device.';
        }
        header("HTTP/1.1 404 Not Found");
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0,minimal-ui:ios'><title>Not Found</title></head><body>$info</body></html>";
        exit;
    }

    function getDLURL(): string {
        $dlAPP = $_GET['app'] ?? $_GET['APP'] ?? $_POST['app'] ?? $_POST['APP'] ?? "";
        $dlAPP = strtolower($dlAPP);
        global $urls;
        if (empty($dlAPP) || !isset($urls[$dlAPP])) {
            $this->notFound();
        }
        $ip = $this->getRealIP();
        $country = $this->getCountry($ip);
        $deviceType = $this->getDeviceType();
        $url = "";
        if ($deviceType == DeviceType::IOS) {
            $url = Hands::AppleAppStore . strtolower($country) . $urls[$dlAPP]["ios"];
        } elseif ($deviceType == DeviceType::ANDROID) {
            if (!isset($urls[$dlAPP]["android"]) && !isset($urls[$dlAPP]["apk"])) {
                $this->notFound();
            }
            if ($country == "CN") {
                $url = $urls[$dlAPP]["apk"];
                if (empty($url)) {
                    $url = Hands::GooglePlayStore . $urls[$dlAPP]["android"];
                }
            } else {
                $url = Hands::GooglePlayStore . $urls[$dlAPP]["android"];
            }
        }
        if (empty($url)) {
            if (isset($urls[$dlAPP]["apk"]) && !empty($urls[$dlAPP]["apk"])) {
                $url = $urls[$dlAPP]["apk"];
            } else {
                $this->notFound();
            }
        }
        return $url;
    }

    function showHMTL(): string {
        $url = $this->getDLURL();
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $lang = substr($lang, 0, 2);
        $info = '';
        if ($lang == "zh") {
            $info = '正在下载';
        } elseif ($lang == "es") {
            $info = 'Descargando';
        } else {
            $info = 'Downloading';
        }
        return "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0,minimal-ui:ios'><meta http-equiv='refresh' content='1;url=DL/$url'><title>$info...</title></head><body>$info <a href='DL/$url'>$url</a> ...</body></html>";
    }
}

$gotoAppStore = new gotoAppStore();
echo $gotoAppStore->showHMTL();
