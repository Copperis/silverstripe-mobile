<?php
/**
 * Extension to {@link ContentController} which handles
 * redirection from main site to mobile.
 *
 * @package mobile
 */
class MobileSiteControllerExtension extends Extension {
	/**
	 * The expiration time of a cookie set for full site requests
	 * from the mobile site. Default is 1 day
	 * @var int
	 */
	public static $cookie_expire_time = 1;
	/**
	 * Stores state information as to which site is currently served.
	 */
	private static $is_mobile = false;
        
        private static $desktop_theme;
        
	/**
	 * Override the default behavior to ensure that if this is a mobile device
	 * or if they are on the configured mobile domain then they receive the mobile site.
	 */
	public function onBeforeInit() {
		self::$is_mobile = false;
		$config = SiteConfig::current_site_config();
		$request = $this->owner->getRequest();

		// If we've accessed the homepage as /home/, then we redirect to / and don't want to double redirect here
		if ($this->owner->redirectedTo()) {
			return;
		}
                
                // Save desktop theme name
                self::$desktop_theme = Config::inst()->get('SSViewer', 'theme');

		// Enforce the site (cookie expires in 1 day)
		$fullSite = $request->getVar('fullSite');

		if (is_numeric($fullSite)) {
			$fullSiteCookie = (int)$fullSite;
			Cookie::set('fullSite', $fullSiteCookie);

			// use the host of the desktop version of the site to set cross-(sub)domain cookie
			$domain = $config->FullSiteDomainNormalized;

			if (!empty($domain)) {
				Cookie::set('fullSite', $fullSite, self::$cookie_expire_time, null, '.' . parse_url($domain, PHP_URL_HOST));
			} else { // otherwise just use a normal cookie with the default domain
				Cookie::set('fullSite', $fullSite, self::$cookie_expire_time);
			}
		} else {
			$fullSiteCookie = Cookie::get('fullSite');
		}

		if (is_numeric($fullSiteCookie)) {
			// Full site requested
			if ($fullSiteCookie) {
				if ($this->onMobileDomain() && $config->MobileSiteType == 'RedirectToDomain') {
					return $this->owner->redirect($config->FullSiteDomainNormalized, 301);
				}

				return;
			} // Mobile site requested
			else {
				if (!$this->onMobileDomain() && $config->MobileSiteType == 'RedirectToDomain') {
					return $this->owner->redirect($config->MobileDomainNormalized, 301);
				}

				Config::inst()->update('SSViewer', 'theme', MobileSite::getMobileTheme());
				self::$is_mobile = true;
				return;
			}
		}

		// If the user requested the mobile domain, set the right theme
		if ($this->onMobileDomain()) {
			Config::inst()->update('SSViewer', 'theme', MobileSite::getMobileTheme());
			self::$is_mobile = true;
		}

		// User just wants to see a theme, but no redirect occurs
		if (MobileSite::is_mobile() && $config->MobileSiteType == 'MobileThemeOnly') {
			Config::inst()->update('SSViewer', 'theme', MobileSite::getMobileTheme());
			self::$is_mobile = true;
		}

		// If on a mobile device, but not on the mobile domain and has been setup for redirection
		if (!$this->onMobileDomain() && MobileSite::is_mobile() && $config->MobileSiteType == 'RedirectToDomain') {
			return $this->owner->redirect($config->MobileDomainNormalized, 301);
		}
	}

	/**
	 * Provide state information. We can't always rely on current theme,
	 * as the user may elect to use the same theme for both sites.
	 *
	 * Useful for example for template conditionals.
	 */
	static public function is_mobile() {
		return self::$is_mobile;
	}
        
	static public function get_desktop_theme() {
		return self::$desktop_theme;
	}

	/**
	 * Return whether the user is requesting the mobile site - either by query string
	 * or by saved cookie. Falls back to browser detection for first time visitors
	 *
	 * @return boolean
	 */
	public function requestedMobileSite() {
		$request = $this->owner->getRequest();
		$fullSite = $request->getVar('fullSite');
		if (is_numeric($fullSite)) {
			return ($fullSite == 0);
		}

		$fullSiteCookie = Cookie::get('fullSite');
		if (is_numeric($fullSiteCookie)) {
			return ($fullSiteCookie == 0);
		}

		return MobileSite::is_mobile();
	}

	/**
	 * Return whether the user is on the mobile version of the website.
	 * Caution: This only has an effect when "MobileSiteType" is configured as "RedirectToDomain".
	 *
	 * @return boolean
	 */
	public function onMobileDomain() {
		$config = SiteConfig::current_site_config();
		$domains = explode(',', $config->MobileDomain);
		foreach ($domains as $domain) {
			if (!parse_url($domain, PHP_URL_SCHEME)) $domain = Director::protocol() . $domain; // Normalize URL
			$parts = parse_url($domain);
			$compare = @$parts['host'];
			if (@$parts['port']) $compare .= ':' . $parts['port'];
			if ($compare && $compare == $_SERVER['HTTP_HOST']) return true;
		}

		return false;
	}

	/**
	 * @return boolean
	 */
	public function isMobile() {
		return MobileSiteControllerExtension::$is_mobile;
	}
        
	/**
	 * @return string
	 */        
	public function DesktopTheme() {
		return MobileSiteControllerExtension::$desktop_theme;
	}
        
	/**
	 * @return mixed
	 */ 
	public function DesktopThemeDir($subtheme = '') {
		if(
			$theme = MobileSiteControllerExtension::$desktop_theme
		) {
			return THEMES_DIR . "/$theme" . ($subtheme ? "_$subtheme" : null);
		}
                
		return false;
	}
        
	/**
	 * Return a link to the full site.
	 *
	 * @return string
	 */
	public function FullSiteLink() {
		return Controller::join_links($this->owner->Link(), '?fullSite=1');
	}

	/**
	 * @return string
	 */
	public function MobileSiteLink() {
		return Controller::join_links($this->owner->Link(), '?fullSite=0');
	}

}