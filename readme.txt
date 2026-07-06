== libresign ==

Contributors: LibreCode
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 5.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

Tema WordPress do LibreSign.

== Footer integration ==

This theme can render the shared footer published by the static LibreSign site.

Configure it in:

- `Appearance > Customize > Footer integration`

Use the theme settings to configure:

- `Footer webhook secret`
- `Allowed webhook IPs` (optional)

Priority:

1. Static site build pushes footer artifacts to the webhook endpoint
2. Theme renders the last stored local artifact
3. If nothing was stored yet, the theme falls back to the original footer template part

Webhook endpoint:

- `/wp-json/libresign/v1/footer-fragment`

The static site build must provide:

- `LIBRESIGN_FOOTER_WEBHOOK_URL`
- `LIBRESIGN_FOOTER_WEBHOOK_SECRET`

== Development notes ==

- Footer integration bootstrap: `inc/footer-fragment.php`
- Main theme bootstrap: `functions.php`
- Static site fragment source path: `/fragments/footer/`


== Changelog ==

= 1.0.0 =
* Initial release


== Copyright ==

libresign WordPress Theme, (C) 2024 LibreCode
libresign is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.


libresign is based on Twenty Twenty-Four (https://wordpress.org/themes/twentytwentyfour/), (C) the WordPress team, [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)

