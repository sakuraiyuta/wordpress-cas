=== Plugin Name ===
Contributors: Yuta Sakurai <sakurai.yuta@gmail.com>
Tags: authentication, CAS, phpCAS
Requires at least: 3.0.1
Tested up to: 3.0.1
Stable tag: 2.3.1

Authenticate users using the phpCAS package (http://www.ja-sig.org/wiki/display/CASC/phpCAS).

== Description ==

This plugin is a modification of <a href="http://wordpress.org/extend/plugins/cas-authentication/">"CAS Authentication plugin" written by candrews, sms225</a>.

Changes from original-source are below:

1. "Auto-register new users" function modified for using on wordpress 3.0.1.
2. a few changes of code formatting. (no effects on all functions, maybe.)

== Installation ==

see: wordpress-cas/readme.original.txt

== ChangeLog ==

2.3.1:
- based on CAS Authentication plugin 2.3.
- changed array-index of arguments for wp_insert_user().
