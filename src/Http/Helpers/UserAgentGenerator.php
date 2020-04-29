<?php

namespace Pretorien\RequestBundle\Http\Helpers;

/**
 * User Agent Generator
 * @link https://github.com/Dreyer/random-UAgent
 * @author Dreyer
 */

class UserAgentGenerator
{
    // General token that says the browser is Mozilla compatible,
    // and is common to almost every browser today.
    const MOZILLA = 'Mozilla/5.0 ';

    /**
     * Processors by Arch.
     */
    public static $processors = array(
        'lin' => array('i686', 'x86_64'),
        'mac' => array('Intel', 'PPC', 'U; Intel', 'U; PPC'),
        'win' => array('foo')
    );

    /**
     * Browsers
     *
     * Weighting is based on market share to determine frequency.
     */
    public static $browsers = array(
        34 => array(
            89 => array('chrome', 'win'),
            9  => array('chrome', 'mac'),
            2  => array('chrome', 'lin')
        ),
        32 => array(
            100 => array('iexplorer', 'win')
        ),
        25 => array(
            83 => array('firefox', 'win'),
            16 => array('firefox', 'mac'),
            1  => array('firefox', 'lin')
        ),
        7 => array(
            95 => array('safari', 'mac'),
            4  => array('safari', 'win'),
            1  => array('safari', 'lin')
        ),
        2 => array(
            91 => array('opera', 'win'),
            6  => array('opera', 'lin'),
            3  => array('opera', 'mac')
        )
    );

    /**
     * List of Lanuge Culture Codes (ISO 639-1)
     *
     * @see: http://msdn.microsoft.com/en-gb/library/ee825488(v=cs.20).aspx
     */
    public static $languages = array(
        'af-ZA', 'ar-AE', 'ar-BH', 'ar-DZ', 'ar-EG',
        'ar-IQ', 'ar-JO', 'ar-KW', 'ar-LB',
        'ar-LY', 'ar-MA', 'ar-OM', 'ar-QA', 'ar-SA',
        'ar-SY', 'ar-TN', 'ar-YE', 'be-BY',
        'bg-BG', 'ca-ES', 'cs-CZ', 'Cy-az-AZ',
        'Cy-sr-SP', 'Cy-uz-UZ', 'da-DK', 'de-AT',
        'de-CH', 'de-DE', 'de-LI', 'de-LU',
        'div-MV', 'el-GR', 'en-AU', 'en-BZ', 'en-CA',
        'en-CB', 'en-GB', 'en-IE', 'en-JM',
        'en-NZ', 'en-PH', 'en-TT', 'en-US', 'en-ZA',
        'en-ZW', 'es-AR', 'es-BO', 'es-CL',
        'es-CO',  'es-CR', 'es-DO', 'es-EC', 'es-ES',
        'es-GT', 'es-HN', 'es-MX', 'es-NI',
        'es-PA', 'es-PE', 'es-PR', 'es-PY', 'es-SV',
        'es-UY', 'es-VE', 'et-EE', 'eu-ES',
        'fa-IR', 'fi-FI', 'fo-FO', 'fr-BE', 'fr-CA',
        'fr-CH', 'fr-FR', 'fr-LU', 'fr-MC',
        'gl-ES', 'gu-IN', 'he-IL', 'hi-IN', 'hr-HR',
        'hu-HU', 'hy-AM', 'id-ID', 'is-IS',
        'it-CH', 'it-IT', 'ja-JP', 'ka-GE', 'kk-KZ',
        'kn-IN', 'kok-IN', 'ko-KR', 'ky-KZ',
        'Lt-az-AZ', 'lt-LT', 'Lt-sr-SP', 'Lt-uz-UZ',
        'lv-LV', 'mk-MK', 'mn-MN', 'mr-IN',
        'ms-BN', 'ms-MY', 'nb-NO', 'nl-BE', 'nl-NL',
        'nn-NO', 'pa-IN', 'pl-PL', 'pt-BR',
        'pt-PT', 'ro-RO', 'ru-RU', 'sa-IN', 'sk-SK',
        'sl-SI', 'sq-AL', 'sv-FI', 'sv-SE',
        'sw-KE', 'syr-SY', 'ta-IN', 'te-IN', 'th-TH',
        'tr-TR', 'tt-RU', 'uk-UA', 'ur-PK',
        'vi-VN', 'zh-CHS', 'zh-CHT', 'zh-CN', 'zh-HK',
        'zh-MO', 'zh-SG', 'zh-TW',
    );

    /**
     * Generate Device Platform
     *
     * Uses a random result with a weighting related to frequencies.
     */
    public static function generatePlatform()
    {
        $rand = mt_rand(1, 100);
        $sum = 0;

        foreach (self::$browsers as $share => $freq_os) {
            $sum += $share;

            if ($rand <= $sum) {
                $rand = mt_rand(1, 100);
                $sum = 0;

                foreach ($freq_os as $share => $choice) {
                    $sum += $share;

                    if ($rand <= $sum) {
                        return $choice;
                    }
                }
            }
        }

        throw new \Exception('Sum of $browsers frequency is not 100.');
    }

    /**
     *
     * @param array $array
     *
     * @return void
     */
    private static function _arrayRandom(array $array)
    {
        $i = array_rand($array, 1);

        return $array[$i];
    }

    /**
     *
     * @param array $lang
     *
     * @return void
     */
    private static function _getLanguage(array $lang = array())
    {
        return self::_arrayRandom(empty($lang) ? self::$languages : $lang);
    }

    /**
     *
     * @param string $os
     *
     * @return void
     */
    private static function _getProcessor($os)
    {
        return self::_arrayRandom(self::$processors[$os]);
    }

    /**
     *
     * @return string
     */
    private static function _getVersionNt(): string
    {
        // Win2k (5.0) to Win 7 (6.1).
        return mt_rand(5, 6) . '.' . mt_rand(0, 1);
    }

    /**
     *
     * @return string
     */
    private static function _getVersionOsx(): string
    {
        return '10_' . mt_rand(5, 7) . '_' . mt_rand(0, 9);
    }

    /**
     *
     * @return string
     */
    private static function _getVersionWebkit(): string
    {
        return mt_rand(531, 536) . mt_rand(0, 2);
    }

    /**
     *
     * @return string
     */
    private static function _getVersionChrome(): string
    {
        return mt_rand(13, 15) . '.0.' . mt_rand(800, 899) . '.0';
    }

    /**
     *
     * @return string
     */
    private static function _getVersionGecko(): string
    {
        return mt_rand(17, 31) . '.0';
    }

    /**
     *
     * @return string
     */
    private static function _getVersionIe(): string
    {
        return '9.0';
    }

    /**
     *
     * @return string
     */
    private static function _getVersionTrident(): string
    {
        return '7.0';
    }

    /**
     *
     * @return string
     */
    private static function _getVersionNet(): string
    {
        // generic .NET Framework common language run time (CLR) version numbers.
        $frameworks = array(
            '2.0.50727',
            '3.0.4506',
            '3.5.30729',
        );

        $rev = '.' . mt_rand(26, 648);

        return self::_arrayRandom($frameworks) . $rev;
    }

    /**
     *
     * @return string
     */
    private static function _getVersionSafari(): string
    {
        if (mt_rand(0, 1) == 0) {
            $ver = mt_rand(4, 5) . '.' . mt_rand(0, 1);
        } else {
            $ver = mt_rand(4, 5) . '.0.' . mt_rand(1, 5);
        }

        return $ver;
    }

    /**
     *
     * @return string
     */
    private static function _getVersionOpera(): string
    {
        return mt_rand(15, 19) . '.0.' . mt_rand(1147, 1284) . mt_rand(49, 100);
    }

    /**
     * Opera
     *
     * @see: http://dev.opera.com/blog/opera-user-agent-strings-opera-15-and-beyond/
     */
    public static function opera($arch)
    {
        $opera = ' OPR/' . self::_getVersionOpera();

        // WebKit Rendering Engine (WebKit = Backend, Safari = Frontend).
        $engine = self::_getVersionWebkit();
        $webkit = ' AppleWebKit/' . $engine . ' (KHTML, like Gecko)';
        $chrome = ' Chrome/' . self::_getVersionChrome();
        $safari = ' Safari/' . $engine;

        switch ($arch) {
        case 'lin':
            return
                '(X11; Linux {proc}) ' .
                $webkit . $chrome . $safari . $opera;
        case 'mac':
            $osx = self::_getVersionOsx();
            return
                '(Macintosh; U; {proc} Mac OS X ' . $osx . ')' .
                $webkit . $chrome . $safari . $opera;
        case 'win':
            // fall through.
        default:
            $nt = self::_getVersionNt();
            return
                '(Windows NT ' . $nt . '; WOW64) ' .
                $webkit . $chrome . $safari . $opera;
        }
    }

    /**
     * Safari
     *
     */
    public static function safari($arch)
    {
        $version = ' Version/' . self::_getVersionSafari();

        // WebKit Rendering Engine (WebKit = Backend, Safari = Frontend).
        $engine = self::_getVersionWebkit();
        $webkit = ' AppleWebKit/' . $engine . ' (KHTML, like Gecko)';
        $safari = ' Safari/' . $engine;

        switch ($arch) {
        case 'mac':
            $osx = self::_getVersionOsx();
            return
                '(Macintosh; U; {proc} Mac OS X ' . $osx .
                '; {lang})' . $webkit . $version . $safari;
        case 'win':
            // fall through.
        default:
            $nt = self::_getVersionNt();
            return
                '(Windows; U; Windows NT ' . $nt . ')' .
                $webkit . $version . $safari;
        }
    }

    /**
     * Internet Explorer
     *
     * @see: http://msdn.microsoft.com/en-gb/library/ms537503(v=vs.85).aspx
     */
    public static function iexplorer($arch)
    {
        $nt = self::_getVersionNt();
        $ie = self::_getVersionIe();
        $trident = self::_getVersionTrident();
        $net = self::_getVersionNet();

        return '(compatible'
            . '; MSIE ' . $ie
            . '; Windows NT ' . $nt
            . '; WOW64'
            . '; Trident/' . $trident
            . '; .NET CLR ' . $net
            . ')';
    }

    /**
     * Firefox User-Agent
     *
     * @see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
     */
    public static function firefox($arch)
    {
        // The release version of Gecko.
        $gecko = self::_getVersionGecko();

        // On desktop, the gecko trail is fixed.
        $trail = '20100101';

        $release = 'rv:' . $gecko;
        $version = 'Gecko/' . $trail . ' Firefox/' . $gecko;

        switch ($arch) {
        case 'lin':
            return
                '(X11; Linux {proc}; ' . $release . ') ' .
                $version;
        case 'mac':
            $osx = self::_getVersionOsx();
            return
                '(Macintosh; {proc} Mac OS X ' . $osx . '; ' .
                $release . ') ' . $version;
        case 'win':
            // fall through.
        default:
            $nt = self::_getVersionNt();
            return
                '(Windows NT ' . $nt . '; {lang}; ' .
                $release . ') ' . $version;
        }
    }

    /**
     *
     * @return string
     */
    public static function chrome($arch): string
    {
        $chrome = ' Chrome/' . self::_getVersionChrome();

        // WebKit Rendering Engine (WebKit = Backend, Safari = Frontend).
        $engine = self::_getVersionWebkit();
        $webkit = ' AppleWebKit/' . $engine . ' (KHTML, like Gecko)';
        $safari = ' Safari/' . $engine;

        switch ($arch) {
        case 'lin':
            return
                '(X11; Linux {proc}) ' .
                 $webkit . $chrome . $safari;
        case 'mac':
            $osx = self::_getVersionOsx();
            return
                '(Macintosh; U; {proc} Mac OS X ' . $osx . ')' .
                 $webkit . $chrome . $safari;
        case 'win':
            // fall through.
        default:
            $nt = self::_getVersionNt();
            return
                '(Windows NT ' . $nt . ') ' .
                 $webkit . $chrome . $safari;
        }
    }

    /**
     *
     * @return string
     */
    public static function generate(
        $browser = 'chrome',
        $os = 'win',
        $lang = array('en-US')
    ): string {
        $ua = self::MOZILLA . call_user_func('self::' . $browser, $os);

        $tags = array(
            '{proc}' => self::_getProcessor($os),
            '{lang}' => self::_getLanguage($lang),
        );

        $ua = str_replace(array_keys($tags), array_values($tags), $ua);

        return $ua;
    }

    /**
     *
     * @return string
     */
    public static function random(
        $browser = null,
        $os = 'win',
        $lang = ['fr-FR']
    ): string {
        if (is_null($browser)) {
            $browsers = ["chrome", "safari", "firefox", "iexplorer"];
            $browser =  $browsers[mt_rand(0, count($browsers) - 1)];
        }
        return self::generate($browser, $os, $lang);
    }
}
