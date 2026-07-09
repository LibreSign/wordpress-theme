== libresign ==

Contributors: LibreCode
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 5.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

Tema WordPress do LibreSign.

== Site fragment integration ==

This theme can render the shared header and footer published by the static
LibreSign site.

Configure it in:

- `Appearance > Customize > Site fragment integration`

Use the theme settings to configure:

- `GitHub webhook secret`
- `Static site origin`
- `GitHub repository`
- `Monitored workflow name`

Priority:

1. GitHub sends a `workflow_run` webhook after the production deploy finishes
2. The theme fetches the latest `/fragments/header` and `/fragments/footer` artifacts from the static site
3. The theme renders the last stored local artifacts
4. If nothing was stored yet, the theme falls back to the original header/footer template parts

Webhook endpoint:

- `/wp-json/libresign/v1/site-deploy-webhook`

Recommended repository webhook configuration on `LibreSign/site`:

- Event: `workflow_run`
- Branch/workflow validation happens inside WordPress
- Use the same secret configured in the theme settings

== Local smoke test ==

- `bash tests/github-site-webhook-smoke.sh`

== Development notes ==

- Site fragment sync/render bootstrap: `inc/footer-fragment.php`
- GitHub webhook receiver: `inc/github-site-webhook.php`
- Main theme bootstrap: `functions.php`
- Static site fragment source paths: `/fragments/header/` and `/fragments/footer/`


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

