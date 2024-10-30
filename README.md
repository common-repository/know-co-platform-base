=== Know - Base ===
Requires at least: 4.6
Tested up to: 4.9.8
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allow your website to natively communicate with the Know Platform. Utilize the Platform API and integrate with the front end of your business.

== Description ==

This plugin provides the base for any Know communication between your website an the Platform. Your custom integrations to communicate securely through this plugin without any additional authentication. Simply include your Platform URL and API key to being!

Aside from backend functionality, this plugin does nothing other than communicate with the Know Platform. Upon successful installation, custom plugins designed for your website to utilize the Platform will be able to send and receive data.

You must have a valid Know Platform subscription for this plugin to function properly.

== Installation ==

Install this plugin first. Then activate custom plugins. Easy!

To install, simply: 

1. Upload the plugin files to the `/wp-content/plugins/know-co-platform-base` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the `Settings->Know Platform` screen to configure your Platform URL and API Key

== Frequently Asked Questions ==

= Does this plugin do anything without a subscription to the Know Platform? =

Nope! It's a simple way for our clients to build API integrations without recreating the wheel!

= Ok, How do I become a client? And what's this all about? =

Know was built to help business owners run their companies more efficiently. We provide solutions to help with everything from Web Forms to a Phone System, and build custom applications for each business use case. Check out https://getknow.co to learn more!

== Changelog ==

= 1.0 =
* Initial release

= 1.0.1 =
* Bug fixes and stability improvements

= 1.0.2 =
* Added API Keys and Secrets

== Useful hints ==

Use the `know--target-session` shortcode to allow targeted sessions from the platform. Include the following parameters:
* server - (optional) the URL of your org
* redirect - where do you want to go once the user is logged in?