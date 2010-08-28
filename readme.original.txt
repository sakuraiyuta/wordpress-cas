=== Plugin Name ===
Contributors: candrews, sms225
Tags: authentication, CAS, phpCAS
Requires at least: 2.5.1
Tested up to: 3.0.1
Stable tag: 2.3

Authenticate users using the phpCAS package (http://www.ja-sig.org/wiki/display/CASC/phpCAS).

== Description ==

Central Authentication System (CAS) is a single signon service provided by some universities.  <a href="http://www.ja-sig.org/wiki/display/CAS/Home">Read about it here.</a>

phpCAS is a client for the CAS service.  You can <a href="http://www.ja-sig.org/wiki/display/CASC/phpCAS">read about that here</a>.  You have to download it separately and point this script to it.

This plugin uses some hooks in WordPress's authentication system to bypass the normal login screen and authenticate over CAS instead.  Note that logged-in state is still maintained in cookies, and user entries are created in the local database.

== Installation ==

1. Download <a href="http://www.ja-sig.org/wiki/display/CASC/phpCAS">phpCAS</a> to a directory on your web server.  You'll want to comment out lines 62 - 104 of CAS.php unless you plan to configure and use the advanced PGT features.
2. Upload cas-authentication.php to the wp-content/plugins/ directory of your wordpress installation.
3. Log in as administrator and activate the plugin.  Go to the Options tab and configure the plugin.  STAY LOGGED IN to your original administrator account.  You won't be able to log back in once you log out.
4. Open a window in a different browser (i.e. Internet Explorer if you were using FireFox), or on another computer.  Log in to your blog to make sure that it works.
5. In the first browser window, make the newly created CAS user the new administrator.  You can log out now. (Alternately, you can change some entries in the wp_usermeta table to make a new user the admin)
6. Disable Options -> General -> Anyone can register (they won't be able to)

== Frequently Asked Questions ==

= Who made this? =

Thanks to <a href="http://dev.webadmin.ufl.edu/~dwc/2005/03/02/authentication-plugins/">this guy</a> for working to make WordPress's login system so flexible, and this plugin possible.

Thanks to Lou Rinaldi for early feedback and encouragement.

Thanks to Ioannis Yessios from Yale ITS for adding the administrator interface.

Thanks to Craig Andrews for providing phpCAS 0.6.0 support.

= What versions of phpCAS does this support? =

It has been tested with phpCAS 1.1.2, the most recent stable release.  We recommend that you use this version.

= What versions of PHP does this support? =

This plugin should work wtih PHP 4.4 through 5.3.

= Can this be used with an LDAP server to fill in user data? =

Yes.  There are several LDAP plugins for WordPress and this plugin doesn't attempt integration with any of them, but adding that feature is easy.

One option is <a href="http://www.kaneit.net/wordpress-plugins/ldap-plugin">Kane IT's plugin</a>.  Craig recommends installing that.  There's some commented-out code on line 102 that should help you interface.

