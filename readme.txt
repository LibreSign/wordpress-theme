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

Use `Footer fragment origins` to define one or more origins.

Priority:

1. Theme configuration in the Customizer
2. Environment variables
3. Default fallback: `https://libresign.coop`

== Development notes ==

- Footer integration bootstrap: `inc/footer-fragment.php`
- Main theme bootstrap: `functions.php`
- Static site fragment path: `/fragments/footer/`


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

