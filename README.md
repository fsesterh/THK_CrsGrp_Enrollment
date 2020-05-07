# Proctorio

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

**Table of Contents**

* [Requirements](#requirements)
* [Installation](#installation)
    * [Composer](#composer)
* [Know Issues](#known-issues)
* [License](#license)

## Requirements

* PHP: [![Minimum PHP Version](https://img.shields.io/badge/Minimum_PHP-7.2.x-blue.svg)](https://php.net/) [![Maximum PHP Version](https://img.shields.io/badge/Maximum_PHP-7.2.x-blue.svg)](https://php.net/)
* ILIAS: [![Minimum ILIAS Version](https://img.shields.io/badge/Minimum_ILIAS-5.4.0-orange.svg)](https://ilias.de/) [![Maximum ILIAS Version](https://img.shields.io/badge/Maximum_ILIAS-5.4.999-orange.svg)](https://ilias.de/)

## Installation

This plugin MUST be installed as a
[User Interface Plugin](https://www.ilias.de/docu/goto_docu_pg_39405_42.html).

The files MUST be saved in the following directory:

	<ILIAS>/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Proctorio

Correct file and folder permissions MUST be
ensured by the responsible system administrator.

### Composer

After the plugin files have been installed as described above,
please install the [`composer`](https://getcomposer.org/) dependencies:

```bash
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Proctorio
composer install --no-dev
```

### Known Issues

#### Same Site Cookie Policy

If the plugin is not working after the Proctorio pre-checks and the HTML
document does not show any progress, this might be caused by missing
cookies in the initial HTTP request when ILIAS is embedded in the Proctorio
document via an HTML `<iframe>`.

Please check your HTTP server (Nginx, Apache) logs and check if the ILIAS
cookies (primarily PHPSESSID and CLIENT_ID) are passed in the HTTP requests.
You should at least check all HTTP request where `TestLaunchAndReview.start`
is part of the HTTP request URL.

This is related to the [*SameSite*](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite)
cookie policy of modern browsers.

As long as the ILIAS core does not support the configuration of this cookie
flag you'll have to patch the code fragments where ILIAS sets cookie parameters:

\ilInitialisation::setSessionCookieParams:
```php
// [...]
$path = IL_COOKIE_PATH . '; samesite=None'; // With PHP >= 7.3 this could be done via the options array
session_set_cookie_params(
    IL_COOKIE_EXPIRE,
    $path,
    IL_COOKIE_DOMAIN,
    IL_COOKIE_SECURE,
    IL_COOKIE_HTTPONLY
);
// [...]
```

\ilUtil::setCookie:
```php
// [...]
$path = IL_COOKIE_PATH . '; samesite=None'; // With PHP >= 7.3 this could be done via the options array
setcookie(
    $a_cookie_name,
    $a_cookie_value,
    $expire,
    $path,
    IL_COOKIE_DOMAIN,
    $secure,
    IL_COOKIE_HTTPONLY
);
// [...]
```

With PHP >= 7.3 the *SameSite* flag could be passed in the options array instead, see:
* https://www.php.net/manual/en/function.setcookie.php
* https://www.php.net/manual/en/function.session-set-cookie-params

## License

See LICENSE file in this repository.