<?php
/**
 * Helper class for detecting known mobile agents.
 * This is a flawed approach to begin with, since there's no reliable way
 * to detect "mobile" device characteristics through the user agent string.
 *
 * CAUTION: Does NOT detect Windows 8 tablets, since there's no user-agent distinction between
 * tablets and desktops in Windows 8.
 *
 * @package mobile
 */
class MobileBrowserDetector {
	protected static $detector_backend;

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

	public static function is_opera_mini() {
		Deprecation::module_version_overrides();
		Deprecation::notice('3.0', 'is_opera_mini is no longer supported, use ::is_opera() instead');
		return static::is_opera();
	}

	public static function is_windows() {
		Deprecation::notice('3.0', 'is_windows is no longer supported, use ::is_IE() ::is_windows_mobile_OS() and/or ::is_windows_phone_OS() instead');
		return static::is_IE() || static::is_windows_mobile_OS() || static::is_windows_phone_OS();
	}

	public static function is_win_phone() {
		Deprecation::notice('3.0', 'is_win_phone is no longer supported, use ::is_IE() ::is_windows_mobile_OS() and/or ::is_windows_phone_OS() instead');
		return static::is_windows_mobile_OS() || static::is_windows_phone_OS();
	}

	public static function is_android() {
		Deprecation::notice('3.0', 'is_android is no longer supported, use ::is_Android_OS() instead');
		return static::is_Android_OS();
	}

	public static function __callStatic($name, $arguments) {
		// turn static function name convention into method names
		// not sure if that is a best practice, we have to rethink this approach
		$name = strtolower(str_replace('_', '', $name));
		return call_user_func_array(array(static::get_detector_backend(), $name), $arguments);
	}
}
