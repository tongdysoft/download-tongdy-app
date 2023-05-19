<?php
require_once 'vendor/autoload.php';

use GeoIp2\Database\Reader;

class URLs {
    const l = [
        'mytongdy' => [
            'name' => 'MyTongdy',
            'ios' => '/app/id1473098643',
            'android' => '',
            'apk' => 'DL/MT-Handy/MT-Handy_2.1.9.apk',
        ],
        'bhand' => [
            'name' => 'BHand',
            'ios' => '/app/id6449812443',
            'android' => 'com.tongdy.tdbleconfig',
            'apk' => 'DL/BHand/BHand_1.0.2.apk',
        ],
        'tdwifiservice' => [
            'name' => 'TDWifiService',
            'ios' => '/app/id1497890956',
            'android' => '',
            'apk' => '',
        ]
    ];
}

class Hands {
    const AppleAppStore = 'itms-apps://apps.apple.com/'; // +cn+
    const GooglePlayStore = 'https://play.google.com/store/apps/details?id=';
    const BaseURL = 'https://www.tongdy.com/app/?app=';
}

class Images {
    const AppleAppStore = ['img/appstore.png', 'Download on the App Store'];
    const GooglePlayStore = ['img/playstore.png', 'Get it on Google Play'];
    const APK = ['img/apk.png', 'Download APK'];
}

class DeviceType {
    const OTHER = 0;
    const ANDROID = 1;
    const IOS = 2;
}

class gotoAppStore {
    const htmlTemp = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0,minimal-ui:ios">'; //<title>Not Found</title></head><body></body></html>
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
        header("HTTP/1.1 404 Not Found");
        exit;
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
        }
        return $url;
    }

    /**
     * @description: 輸出用於跳轉到目標地址的網頁
     * @return String HTML
     */
    function showHMTL(): string {
        $url = $this->getDLURL();
        return $this::htmlTemp . "<meta http-equiv=\"refresh\" content=\"1;url=$url\"><title>Redirecting...</title></head><body>-> <a href=\"$url\">$url</a> ...</body></html>";
    }

    /**
     * @description: 輸出包含所有下載地址的網頁
     * @param String dlAPP APP 名稱
     * @param String country 國家代碼
     */
    function showAllDL(string $dlAPP, string $country = "US") {
        $syss = URLs::l[$dlAPP];
        $imagePrep = 'width="200"';
        $name = $syss["name"];
        $qrimg = 'qrcode/' . $dlAPP . '.png';
        $html = $this::htmlTemp . "<title>$name</title></head><body style=\"text-align:center;\">( ↓ ) APP DOWNLOAD<hr/><h1>$name</h1><img $imagePrep src=\"$qrimg\" alt=\"$name\" />";
        if (isset($syss["ios"]) && !empty($syss["ios"])) {
            $html .= '<p><a href="' . Hands::AppleAppStore . strtolower($country) . $syss["ios"] . '"><img ' . $imagePrep . ' src="' . Images::AppleAppStore[0] . '" alt="' . Images::AppleAppStore[1] . '" /></a></p>';
        }
        if (isset($syss["android"]) && !empty($syss["android"])) {
            $html .= '<p><a href="' . Hands::GooglePlayStore . $syss["android"] . '"><img ' . $imagePrep . ' src="' . Images::GooglePlayStore[0] . '" alt="' . Images::GooglePlayStore[1] . '" /></a></p>';
        }
        if (isset($syss["apk"]) && !empty($syss["apk"])) {
            $html .= '<p><a href="' . $syss["apk"] . '"><img ' . $imagePrep . ' src="' . Images::APK[0] . '" alt="' . Images::APK[1] . '" /></a></p>';
        }
        exit($html . '</body></html>');
    }
}

$gotoAppStore = new gotoAppStore();
exit($gotoAppStore->showHMTL());
