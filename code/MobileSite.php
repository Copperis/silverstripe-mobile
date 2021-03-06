<?php

/**
 * Helper class for detecting known mobile agents and various other related purposes.
 * This is a flawed approach to begin with, since there's no reliable way
 * to detect "mobile" device characteristics through the user agent string.
 *
 * CAUTION: Does NOT detect Windows 8 tablets, since there's no user-agent distinction between
 * tablets and desktops in Windows 8.
 *
 * @package mobile
 */
class MobileSite extends Object {
    
    protected static $detector_backend;

    public static function __callStatic($name, $arguments) {
            // turn static function name convention into method names
            // not sure if that is a best practice, we have to rethink this approach        
            $name = strtolower(str_replace('_', '', $name));
            return call_user_func_array(array(static::get_detector_backend(), $name), $arguments);
    }
    
    public static function set_detector_backend($backend) {
            static::$detector_backend = $backend;
    }

    public static function get_detector_backend() {
            if (!static::$detector_backend) {
                    static::$detector_backend = new Mobile_Detect();
            }
            return static::$detector_backend;
    }
    
    public static function is_mobile() {
            return static::get_detector_backend()->isMobile() && !static::get_detector_backend()->isTablet();
    }

    public static function getMobileTheme() {
        $config = SiteConfig::current_site_config();
        $theme = $config->MobileTheme;

        if (is_null($theme)) {
            $theme = self::config()->default_themes[0];
        }

        return $theme;
    }

}
