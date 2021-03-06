<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CookieHelper.
 */
class CookieHelper
{
    const SAME_SITE       = '; SameSite=';
    const SAME_SITE_VALUE = 'None';
    private $path         = null;
    private $domain       = null;
    private $secure       = false;
    private $httponly     = false;
    private $request      = null;

    /**
     * CookieHelper constructor.
     *
     * @param              $cookiePath
     * @param              $cookieDomain
     * @param              $cookieSecure
     * @param              $cookieHttp
     * @param RequestStack $requestStack
     */
    public function __construct($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, RequestStack $requestStack)
    {
        $this->path     = $cookiePath;
        $this->domain   = $cookieDomain;
        $this->secure   = $cookieSecure;
        $this->httponly = $cookieHttp;

        $this->request = $requestStack->getCurrentRequest();
        if (('' === $this->secure || null === $this->secure) && $this->request) {
            $this->secure = filter_var($requestStack->getCurrentRequest()->server->get('HTTPS', false), FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getCookie($key, $default = null)
    {
        if ($this->request === null) {
            return $default;
        }

        return $this->request->cookies->get($key, $default);
    }

    /**
     * @param      $name
     * @param      $value
     * @param int  $expire
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param bool $httponly
     */
    public function setCookie($name, $value, $expire = 1800, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if ($this->request == null || (defined('MAUTIC_TEST_ENV') && MAUTIC_TEST_ENV)) {
            return true;
        }

        // If https, SameSite equals None
        $sameSiteNoneText             = '';
        $sameSiteNoneTextGreaterPhp73 = null;
        if ($secure === true or ($secure === null and $this->secure === true)) {
            $sameSiteNoneText             = self::SAME_SITE.self::SAME_SITE_VALUE;
            $sameSiteNoneTextGreaterPhp73 = self::SAME_SITE_VALUE;
        }

        if (version_compare(phpversion(), '7.3', '>=')) {
            setcookie(
                $name,
                $value,
                [
                    'expires'  => ($expire) ? (int) (time() + $expire) : null,
                    'path'     => (($path == null) ? $this->path : $path),
                    'domain'   => ($domain == null) ? $this->domain : $domain,
                    'secure'   => ($secure == null) ? $this->secure : $secure,
                    'httponly' => ($httponly == null) ? $this->httponly : $httponly,
                    'samesite' => $sameSiteNoneTextGreaterPhp73,
                ]
            );
        } else {
            setcookie(
                $name,
                $value,
                ($expire) ? (int) (time() + $expire) : null,
                (($path == null) ? $this->path : $path).$sameSiteNoneText,
                ($domain == null) ? $this->domain : $domain,
                ($secure == null) ? $this->secure : $secure,
                ($httponly == null) ? $this->httponly : $httponly
            );
        }
    }

    /**
     * Deletes a cookie by expiring it.
     *
     * @param           $name
     * @param null      $path
     * @param null      $domain
     * @param null      $secure
     * @param bool|true $httponly
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->setCookie($name, '', -86400, $path, $domain, $secure, $httponly);
    }
}
