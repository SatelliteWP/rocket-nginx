Rocket-Nginx
============

Rocket-Nginx is a [Nginx](https://nginx.org) configuration for the [WordPress](https://wordpress.org) cache plugin [WP-Rocket](https://wp-rocket.me). It enables Nginx to serve directly previously cached files without calling WordPress or any PHP. It also adds headers to cache CSS, JS and medias in order to leverage browser's cache by reducing request to your web server.

This project is sponsored by SatelliteWP, a [WordPress maintenance service](https://www.satellitewp.com/en) located near Montreal, Canada. Our service is offered in both English and French. SatelliteWP fait de l'[entretien de sites WordPress](https://www.satellitewp.com/?utm_source=rocket-nginx).

[![SatelliteWP - WordPress Maintenance](https://cdn.satellitewp.com/wp-content/uploads/2019/03/logo.png "SatelliteWP - WordPress Maintenance")](https://www.satellitewp.com/en?utm_source=rocket-nginx)

## <a name='toc'>Table of Contents</a>

  1. [Contributors](#contributors)
  1. [Before You Start](#before)
  1. [Installation](#installation)
  1. [Configuration](#configuration)
  1. [Debug](#debug)
  1. [FAQ](#css)
  1. [License](#license)

## <a name='contributors'>Contributors</a>

The configuration was created by [Maxime Jobin](https://www.maximejobin.com) ([@maximejobin](https://github.com/maximejobin)) and is now maintained by [SatelliteWP](https://www.satellitewp.com/en?utm_source=rocket-nginx). 

## <a name='before'>Before You Start</a>
As the configuration's goal is to serve cached files directly without having to execute any PHP from WordPress, this may cause your scheduled jobs to not be called.  As you may already know, WP-Cron jobs are not real cron jobs and are executed only when you have visits on your site.

In order to make sure your scheduled tasks run when they should, it is strongly suggested to disable WordPress cron jobs and create a real cron job.

To disable WordPress cron job, add the following line to your `wp-config.php`:
`define('DISABLE_WP_CRON', true);`

Then, manually a cron job every 15 minutes (it should be enough for most websites):

`*/15 * * * * wget -q -O - http://www.website.com/wp-cron.php?doing_wp_cron &>/dev/null`

or

`*/15 * * * * curl http://www.website.com/wp-cron.php?doing_wp_cron &>/dev/null`

or

`*/15 * * * * cd /home/user/public_html; php wp-cron.php &>/dev/null`

Make sure you test that your tasks still run after this change!

## <a name='installation'>Installation</a>

In order to use the script, you must include it in your actual configuration.  If your WordPress website is not yet configured to run with Nginx, you can check the [Nginx configuration for WordPress](https://github.com/satellitewp/rocket-nginx/wiki/Nginx-configuration-for-WordPress) documentation.

Only one instance of Rocket-Nginx is needed for all your WordPress websites using WP-Rocket. That said, you can generate as many configuration files as needed.

You can create a folder `rocket-nginx` directory in your Nginx configuration directory. If you are using Ubuntu, your Nginx configuration (nginx.conf) should be found in: `/etc/nginx/`.

To install, you can:
  ```
  cd /etc/nginx
  git clone https://github.com/satellitewp/rocket-nginx.git
  ```

Since version 2.0, the configuration must be generated. To generate the default configuration, you must rename the disabled ini file and run the configuration parser:
```
cd rocket-nginx
cp rocket-nginx.ini.disabled rocket-nginx.ini
php rocket-parser.php
```
This will generate the `default.conf` configuration that can be included for all websites.  If you need to alter the default configuration, you can edit the ini file and add another section at the bottom of the file.

Then, in your configuration file, you must [include](https://nginx.org/en/docs/ngx_core_module.html#include) the configuration. If your websites configurations are in `/etc/nginx/sites-available`, you need to alter your configuration:

```
server {
  ...
  
  # Rocket-Nginx configuration
  include rocket-nginx/default.conf;
  
  ...
}
```

Before you reload your configuration, make sure you test it:
`nginx -t`

Once your test is done, you must reload your configuration.
`service nginx reload`

That's it.

## <a name='configuration'>Configuration</a>
There is no configuration to do.  It will work out of the box.  But, you can edit a couple of things...

Just open the `rocket-nginx.ini` file and see all the options in it.

You can add a new section based on the default configuration like this:
```
# This creates the new section and will generate a new configuration
[example.com : default]

# This will add a new header named "Is-NewSection" with the value "Yes" to the medias section
media_header[Is-NewSection] = "Yes"
```

Once you edit the ini file, you must regenerate your Nginx configuration file by running the parser:

```
php rocket-parser.php
```

Then, newly added or modified sections will generate update configuration file (*.conf).

Finally, **each time** you generate (or regenerate) the configurations files, you have to:

1. Test it to make sure it did not produce any error:

    `nginx -t`
    
1. Reload the configuration:

    `service nginx reload`


## <a name='debug'>Debug</a>
You may want to check if your files are served directly by Nginx and not calling any PHP. To do that, open the `rocket-nginx.ini` file and change the debug value from:

`debug = false`

To:

`debug = true`

The following header is present no matter if debug is set to 0 or 1:
  * **X-Rocket-Nginx-Serving-Static**: Did the configuration served the cached file directly (did it bypass WordPress): Yes or No.

This will add the following headers to your response request:
  * **X-Rocket-Nginx-Reason**: If Bypass is set to "No", what is the reason for calling WordPress.  If "Yes", what is the file used (URL).
  * **X-Rocket-Nginx-File**: If "Yes", what is the file used (path on disk).


Reasons for not serving a cached file:
  * **Post request**: The request to the web server was a POST. That means data was sent and the answer may need to be different from the cached file (e.g. when a comment is sent).
  * **Arguments found**: One or more argument was found in the request (e.g. ?page=2).
  * **Maintenance mode**: The .maintenance file was found. Therefore, let's WordPress handle what should be displayed.
  * **Cookie**: A specific cookie was found and tells to not serve the cached page (e.g. user is logged in, post with password).
  * **Specific mobile cache activated**: If you activated specific cache (one for mobile and one for desktop) in WP-Rocket, HTML files (pages, posts, ...) won't be served directly because Rocket-Nginx cannot know if the request was made by mobile or desktop device.
  * **File not cached**: No cached file was found for that request.

## <a name='faq'>FAQ</a>

**<a name='faq_benchmark'>Do you have any benchmark about the project ?</a>**

No. People love benchmark as much as they hate them. All benchmarks have people claiming that X or Y or Z could have been done to improve the outcome.  In this project, the benchmark would depend on how many plugins you have that are affecting the page even if the output is in cache (e.g. WP-Rocket executes PHP even when a file is in cache). What we can say though is that you will go from **NGINX &#8594; PHP-FPM &#8594; PHP &#8594; Static file** to **NGINX &#8594; Static file**. In other words, you are serving the static file directly from NGINX instead of passing the request to FPM then to PHP (for WP-Rocket... at least) before serving the static file.

**<a name='faq_ssl'>Will Rocket-Nginx work if my website uses a SSL certificate (https) ?</a>**

Yes! Rocket-Nginx will detect if the request was made through HTTP or HTTPS and serve the right file depending on the request type.  Both protocols are handled automagically since version 1.0.

## <a name='license'>License</a>
Released under the MIT License. See the license file for details.
