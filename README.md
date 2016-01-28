Rocket-Nginx
============

Rocket-Nginx is a [Nginx](http://nginx.org) configuration for the [WordPress](http://wordpress.org) cache plugin [WP-Rocket](http://wp-rocket.me). It enables Nginx to serve directly previously cached files without calling WordPress or any PHP. It also adds headers to cache CSS, JS and medias in order to leverage browser's cache by reducing request to your web server.

## <a name='toc'>Table of Contents</a>

  1. [Contributors](#contributors)
  1. [Before You Start](#before)
  1. [Installation](#installation)
  1. [Configuration](#configuration)
  1. [Debug](#debug)
  1. [FAQ](#css)
  1. [License](#license)

## <a name='contributors'>Contributors</a>

The configuration was created and is maintained by [Maxime Jobin](http://www.maximejobin.com) ([@maximejobin](http://twitter.com/maximejobin)).

## <a name='before'>Before You Start</a>
As the configuration's goal is to serve cached files directly without having to execute any PHP from WordPress, this may cause your scheduled jobs to not be called.  As you may already know, WP-Cron jobs are not real cron jobs and are executed only when you have visits on your site.

In order to make sure your scheduled tasks run when they should, it is strongly suggested to disable WordPress cron jobs and create a real cron job.

To disable WordPress cron job, add the following line to your `wp-config.php`:
`define('DISABLE_WP_CRON', true);`

Then, manually a cron job every 15 minutes (it should be enough for most websites):

`*/15 * * * * wget -q -O - http://www.website.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

or

`*/15 * * * * curl http://www.website.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1`

or

`*/15 * * * * cd /home/user/public_html; php wp-cron.php > /dev/null 2>&1`

Make sure you test that your tasks still run after this change!

## <a name='installation'>Installation</a>

In order to use the script, you must include it in your actual configuration.  If your WordPress website is not yet configured to run with Nginx, you can check the [Nginx configuration for WordPress](https://github.com/maximejobin/rocket-nginx/wiki/Nginx-configuration-for-WordPress) documentation.

Only one instance of Rocket-Nginx is needed for all your WordPress websites using WP-Rocket.

You can create a folder `rocket-nginx` directory in your Nginx configuration directory. If you are using Ubuntu, your Nginx configuration (nginx.conf) should be found in: `/etc/nginx/`.

To install, you can:
  ```
  cd /etc/nginx
  git clone https://github.com/maximejobin/rocket-nginx.git
  ```

Then, in your configuration file, you must [include](http://nginx.org/en/docs/ngx_core_module.html#include) the configuration. If your websites configurations are in `/etc/nginx/sites-available`, you need to alter your configuration:

```
server {
  ...
  
  # Rocket-Nginx configuration
  include rocket-nginx/rocket-nginx.conf;
  
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

#### Cache expiration
By default, files such as CSS, JS and medias (images, fonts, ...) are cached until December 31st, 2037 (`expires max;`). As of the latest Nginx stable version, it is not possible to set these values into variables. You can manually change the values if needed.

#### HTTP Strict Transport Security (HSTS)
By default, HSTS (see [HTTP Strict Transport Security](https://developer.mozilla.org/en-US/docs/Web/Security/HTTP_strict_transport_security)) is enabled by default for all subdomains and the cache will expire after 1 year. If you want to overwrite the default value, you can simply insert your desired value between the quotes of the `$rocket_hsts_value` variable at the top of the `rocket-nginx.conf` file.

If you leave the variable as-is:

    set $rocket_hsts_value "";

Rocket-Nginx will display the default HSTS header:

    Strict-Transport-Security: max-age=16070400; includeSubDomains

If you change the variable value with another value:

    set $rocket_hsts_value "my-value-here";

Rocket-Nginx will display the following HSTS header:

    Strict-Transport-Security: my-value-here
    

## <a name='debug'>Debug</a>
You may want to check if your files are served directly by Nginx and not calling any PHP. To do that, open the `rocket-nginx.conf` file and change the debug value from:

`set $rocket_debug 0;`

To:

`set $rocket_debug 1;`

The following header is present no matter if debug is set to 0 or 1:
  * **X-Rocket-Nginx-Bypass**: Did the configuration served the cached file directly (did it bypass WordPress): Yes or No.

This will add the following headers to your response request:
  * **X-Rocket-Nginx-Reason**: If Bypass is set to "No", what is the reason for calling WordPress.  If "Yes", what is the file used (URL).
  * **X-Rocket-Nginx-File**: If "Yes", what is the file used (path on disk).


Reasons for not serving a cached file:
  * **Post request**: The request to the web server was a POST. That means data was sent and the answer may need to be different from the cached file (e.g. when a comment is sent).
  * **Arguments found**: One or more argument was found in the request (e.g. ?page=2).
  * **Maintenance mode**: The .maintenance file was found. Therefore, let's WordPress handle what should be displayed.
  * **Cookie**: A specific cookie was found and tells to not serve the cached page (e.g. user is logged in, post with password).
  * **File not cached**: No cached file was found for that request.

## <a name='faq'>FAQ</a>

**<a name='faq_benchmark'>Do you have any benchmark about the project ?</a>**

No. People love benchmark as much as they hate them. All benchmarks have people claiming that X or Y or Z could have been done to improve the outcome.  In this project, the benchmark would depend on how many plugins you have that are affecting the page even if the output is in cache (e.g. WP-Rocket executes PHP even when a file is in cache). What we can say though is that you will go from **NGINX &#8594; PHP-FPM &#8594; PHP &#8594; Static file** to **NGINX &#8594; Static file**. In other words, you are serving the static file directly from NGINX instead of passing the request to FPM then to PHP (for WP-Rocket... at least) before serving the static file.

**<a name='faq_ssl'>Will Rocket-Nginx work if my website uses a SSL certificate (https) ?</a>**

Yes! Rocket-Nginx will detect if the request was made through HTTP or HTTPS and serve the right file depending on the request type.  Both protocols are handled automagically since version 1.0.

## <a name='license'>License</a>
Released under the [GPL](http://www.gnu.org/licenses/gpl.html). See the link for details.
