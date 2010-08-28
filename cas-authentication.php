<?php
/*
Plugin Name: CAS Authentication
Version: 2.3.1
Plugin URI: http://github.com/sakuraiyuta/wordpress-cas
Description: This plugin is a modification of <a href="http://wordpress.org/extend/plugins/cas-authentication/">"CAS Authentication plugin" written by candrews, sms225</a>.
Author: Yuta Sakurai
Author URI: http://github.com/sakuraiyuta/wordpress-cas
License: GPLv2
 */

/* Copyright (C) 2010 Yuta Sakurai <sakurai.yuta@gmail.com>

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA */


add_action('admin_menu', 'cas_authentication_add_options_page');

$cas_authentication_opt = get_option('cas_authentication_options');

$cas_configured = true;

// try to configure the phpCAS client
if ($cas_authentication_opt['include_path'] == '' ||
    (include_once $cas_authentication_opt['include_path']) != true)
    $cas_configured = false;

if ($cas_authentication_opt['server_hostname'] == '' ||
    $cas_authentication_opt['server_path'] == '' ||
    intval($cas_authentication_opt['server_port']) == 0)
    $cas_configured = false;

if ($cas_configured) {
    phpCAS::client($cas_authentication_opt['cas_version'], 
        $cas_authentication_opt['server_hostname'], 
        intval($cas_authentication_opt['server_port']), 
        $cas_authentication_opt['server_path']);

    // function added in phpCAS v. 0.6.0
    // checking for static method existance is frustrating in php4
    $phpCas = new phpCas();
    if (method_exists($phpCas, 'setNoCasServerValidation'))
        phpCAS::setNoCasServerValidation();
    unset($phpCas);
    // if you want to set a cert, replace the above few lines
}

// for wp_create_user function on line 120
require_once (ABSPATH . WPINC . '/registration.php');

// plugin hooks into authentication system
add_action('wp_authenticate', array('CASAuthentication', 'authenticate'), 10, 2);
add_action('wp_logout', array('CASAuthentication', 'logout'));
add_action('lost_password', array('CASAuthentication', 'disable_function'));
add_action('retrieve_password', array('CASAuthentication', 'disable_function'));
add_action('password_reset', array('CASAuthentication', 'disable_function'));
add_filter('show_password_fields', array('CASAuthentication', 'show_password_fields'));
add_filter('login_url', array('CASAuthentication', 'bypass_reauth'));

if (!class_exists('CASAuthentication')) {
    class CASAuthentication {

        // password used by the plugin
        function passwordRoot() {
            return 'Authenticated through CAS';
        }    

    /*
     We call phpCAS to authenticate the user at the appropriate time 
     (the script dies there if login was unsuccessful)
     If the user has not logged in previously, we create an accout for them
     */
        function authenticate(&$username, &$password) {
            global $using_cookie, $cas_authentication_opt, $cas_configured;

            if (!$cas_configured)
                die("cas-authentication plugin not configured");

            // Reset values from input ($_POST and $_COOKIE)
            $username = $password = '';		

            phpCAS::forceAuthentication();

            // might as well be paranoid
            if (!phpCAS::isAuthenticated())
                exit();

            $username = phpCAS::getUser();
            $password = md5(CASAuthentication::passwordRoot());

            // Craig Andrews's ldap interface code
      /*
      if (function_exists('ldap_userupdate')) {
        $wpldap = new WpLdapWrapper();
        if($wpldap->userExists($username)){
          return ldap_userupdate($username,$password);
    }else{
          return false;
        }
      }
       */

            if (!function_exists('get_userdatabylogin'))
                die("Could not load user data");
            $user = get_userdatabylogin($username);

            if ($user)
                // user already exists
                return true;

            else {
                // first time logging in

                if ($cas_authentication_opt['new_user'] == 1) {
                    // auto-registration is enabled

                    // User is not in the WordPress database
                    // they passed CAS and so are authorized
                    // add them to the database

                    $user_email = '';
                    if ($cas_authentication_opt['email_suffix'] != '')
                        $user_email = $username . '@' . $cas_authentication_opt['email_suffix'];

                    $user_info = array();
                    $user_info['user_login'] = $username;
                    $user_info['user_pass'] = $password;
                    $user_info['user_email'] = $user_email;
                    wp_insert_user($user_info);
                }

                else {
                    // auto-registration is disabled

                    $error = sprintf(__('<p><strong>ERROR</strong>: %s is not registered with this blog. Please contact the <a href="mailto:%s">blog administrator</a> to create a new account!</p>'), $username, get_option('admin_email'));
                    $errors['registerfail'] = $error;
                    print($error);
                    print('<p><a href="/wp-login.php?action=logout">Log out</a> of CAS.</p>');
                    exit();
                }
            }
        }


    /*
     We use the provided logout method
     */
        function logout() {
            global $cas_configured;

            if (!$cas_configured)
                die("cas-authentication not configured");

            phpCAS::logoutWithUrl(get_settings('siteurl'));
            exit();
        }

        /*
         * Remove the reauth=1 parameter from the login URL, if applicable. This allows
         * us to transparently bypass the mucking about with cookies that happens in
         * wp-login.php immediately after wp_signon when a user e.g. navigates directly
         * to wp-admin.
         */
        function bypass_reauth($login_url) {
            $login_url = remove_query_arg('reauth', $login_url);
            return $login_url;
        }

    /*
     Don't show password fields on user profile page.
     */
        function show_password_fields($show_password_fields) {
            return false;
        }


        function disable_function() {
            die('Disabled');
        }

    }
}

//----------------------------------------------------------------------------
//		ADMIN OPTION PAGE FUNCTIONS
//----------------------------------------------------------------------------

function cas_authentication_add_options_page() {
    if (function_exists('add_options_page')) {
        add_options_page('CAS Authentication', 'CAS Authentication', 8, basename(__FILE__), 'cas_authentication_options_page');
    }
} 

function cas_authentication_options_page() {
    global $wpdb;

    // Setup Default Options Array
    $optionarray_def = array(
        'new_user' => FALSE,
        'redirect_url' => '',
        'email_suffix' => 'yourschool.edu',
        'cas_version' => CAS_VERSION_1_0,
        'include_path' => '',
        'server_hostname' => 'yourschool.edu',
        'server_port' => '443',
        'server_path' => ''
    );

    if (isset($_POST['submit']) ) {    
        // Options Array Update
        $optionarray_update = array (
            'new_user' => $_POST['new_user'],
            'redirect_url' => $_POST['redirect_url'],
            'email_suffix' => $_POST['email_suffix'],
            'include_path' => $_POST['include_path'],
            'cas_version' => $_POST['cas_version'],
            'server_hostname' => $_POST['server_hostname'],
            'server_port' => $_POST['server_port'],
            'server_path' => $_POST['server_path']
        );


        update_option('cas_authentication_options', $optionarray_update);
    }

    // Get Options
    $optionarray_def = get_option('cas_authentication_options');

?>
    <div class="wrap">
    <h2>CAS Authentication Options</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
    <fieldset class="options">

     <h3>User registration options</h3>
    <table width="700px" cellspacing="2" cellpadding="5" class="editform">
       <tr>
       <td colspan="2">Checking <em>Auto-register new users</em> will automatically create a new user (with role of Subscriber) upon successful login of a new visitor to the site.</td>
       </tr>
       <tr valign="center"> 
       <th width="200px" scope="row">Auto-register new users?</th> 
       <td width="15px"><input name="new_user" type="checkbox" id="new_user_inp" value="1" checked="<?php checked('1', $optionarray_def['new_user']); ?>" /></td>
       </tr>
           <tr>
           <td colspan="2">If you know that the owner of the CAS authentication service issues email addresses based on their netids, you can predict your users' emails here</td>
           </tr>
       <tr valign="center">
       <th width="200px" scope="row">E-mail Suffix</th> 
       <td>netid@<input type="text" name="email_suffix" id="email_suffix_inp" value="<?php echo $optionarray_def['email_suffix']; ?>" size="35" /></td>
       </tr>
    </table>

    <h3>phpCAS options</h3>
    <p>Note: Once you fill in these options, wordpress authentication will happen through CAS, even if you misconfigure it. To avoid being locked out of Wordpress, use a second browser to check your settings before you end this session as administrator. If you get an error in the other browser, correct your settings here. If you can not resolve the issue, disable this plug-in.</p>

    <h4>php CAS include path</h4>
    <table width="700px" cellspacing="2" cellpadding="5" class="editform">
                <tr>
                <td colspan="2">Full absolute path to CAS.php script</td>
                </tr>
        <tr valign="center"> 
        <th width="300px" scope="row">CAS.php path</th> 
        <td><input type="text" name="include_path" id="include_path_inp" value="<?php echo $optionarray_def['include_path']; ?>" size="35" /></td>
        </tr>
    </table>    

    <h4>phpCAS::client() parameters</h4>
    <table width="700px" cellspacing="2" cellpadding="5" class="editform">
        <tr valign="center"> 
            <th width="300px" scope="row">CAS verions</th> 
            <td><select name="cas_version" id="cas_version_inp">
                <option value="2.0" <?php echo ($optionarray_def['cas_version'] == '2.0')?'selected':''; ?>>CAS_VERSION_2_0</option>
                <option value="1.0" <?php echo ($optionarray_def['cas_version'] == '1.0')?'selected':''; ?>>CAS_VERSION_1_0</option>
             </td>
        </tr>
        <tr valign="center"> 
            <th width="300px" scope="row">server hostname</th> 
            <td><input type="text" name="server_hostname" id="server_hostname_inp" value="<?php echo $optionarray_def['server_hostname']; ?>" size="35" /></td>
        </tr>
        <tr valign="center"> 
            <th width="300px" scope="row">server port</th> 
            <td><input type="text" name="server_port" id="server_port_inp" value="<?php echo $optionarray_def['server_port']; ?>" size="35" /></td>
        </tr>
        <tr valign="center"> 
            <th width="300px" scope="row">server path</th> 
            <td><input type="text" name="server_path" id="server_path_inp" value="<?php echo $optionarray_def['server_path']; ?>" size="35" /></td>
        </tr>
    </table>
    </fieldset>
    <p />
    <div class="submit">
        <input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
    </div>
    </form>
<?php
}
?>
