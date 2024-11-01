<?php
/**
 * Custom login
 *
 * Allows to define custom login, reset password email, etc.
 *
 * @package TheCartPress
 * @subpackage Modules
 */

/**
 * This file is part of TheCartPress.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'TCP_CustomLogin' ) ) :

class TCP_CustomLogin {

	/**
	 * Initializes the plugin.
	 *
	 * To keep the initialization fast, only add filter and action
	 * hooks in the constructor.
	 */
	public function __construct() {

		// Redirects
		add_action( 'login_form_login'	, array( $this, 'redirect_to_custom_login' ) );
		add_filter( 'authenticate'		, array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect'	, array( $this, 'redirect_after_login' ), 10, 3 );
		add_action( 'wp_logout'			, array( $this, 'redirect_after_logout' ) );

		add_action( 'login_form_register'		, array( $this, 'redirect_to_custom_register' ) );
		add_action( 'login_form_lostpassword'	, array( $this, 'redirect_to_custom_lostpassword' ) );
		//add_action( 'login_form_retrievepassword'	, array( $this, 'redirect_to_custom_retrievepassword' ) );

		add_action( 'login_form_rp'			, array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass'	, array( $this, 'redirect_to_custom_password_reset' ) );

		// fixes "Lost Password?" URLs on login page		
		add_filter( 'lostpassword_url', array( $this, 'lostpassword_url' ) , 10, 2 );

		// fixes other password reset related urls
		add_filter( 'network_site_url', array( $this, 'network_site_url' ), 10, 3 );

		// Login url
		add_filter( 'login_url', array( $this, 'login_url' ), 10, 3 );

		// Handlers for form posting actions
		add_action( 'login_form_register'		, array( $this, 'do_register_user' ) );
		add_action( 'login_form_lostpassword'	, array( $this, 'do_password_lost' ) );
		add_action( 'login_form_rp'				, array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass'		, array( $this, 'do_password_reset' ) );

		// Other customizations
		// Changes "From" email name
		add_filter( 'wp_mail_from_name', array( $this, 'wp_mail_from_name' ) );

		// Changes Subject
		// add_filter( 'retrieve_password_title', function( $title, $user_login, $user_data ) { return __( 'Password Recovery', 'tcp' ); }, 10, 3 );
		
		// Change email type to HTML
		add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type') );

		// Changes reset password content
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 );

		// Setup
		add_action( 'wp_print_footer_scripts'	, array( $this, 'add_captcha_js_to_footer' ) );
		add_filter( 'admin_init'				, array( $this, 'register_settings_fields' ) );

		// Adds shortcodes
		add_shortcode( 'tcp_my_account'					, array( $this, 'render_my_account' ) );
		add_shortcode( 'tcp-custom-login-form'			, array( $this, 'render_login_form' ) );
		add_shortcode( 'tcp-custom-register-form'		, array( $this, 'render_register_form' ) );
		add_shortcode( 'tcp-custom-password-lost-form'	, array( $this, 'render_password_lost_form' ) );
		add_shortcode( 'tcp-custom-password-reset-form'	, array( $this, 'render_password_reset_form' ) );

		// Checks for removed pages
		add_filter( 'tcp_check_the_plugin', array( $this, 'tcp_check_the_plugin' ) );

		// Fixes removes pages
		add_filter( 'tcp_checking_pages', array( $this, 'tcp_checking_pages' ) );
	}
	
	/**
	 * Checks for removed pages
	 */
	public function tcp_check_the_plugin( $warnings ) {

		// Gets information needed for check plugin's pages
		$page_definitions = self::get_page_definition();
		foreach ( $page_definitions as $page_key => $page_def ) {
			$page_id = get_option( $page_key . '_page_id' );
			if ( ! $page_id || ! get_page( $page_id ) ) {
				$warnings[] = sprintf( __( 'The <strong>%s page</strong> has been deleted.', 'tcp' ), $page_def['title'] );
			}
		}
		return $warnings;
	}
	
	/**
	 * Fixes removed pages
	 */
	public function tcp_checking_pages( $warnings_msg ) {

		// Gets information needed for check plugin's pages
		$page_definitions = self::get_page_definition();
		foreach ( $page_definitions as $page_key => $page_def ) {
			$page_id = get_option( $page_key . '_page_id' );
			if ( ! $page_id || ! get_page( $page_id ) ) {
				self::create_page( $page_key, $page_def );
				$warnings_msg[] = sprintf( __( '<strong>%s page</strong> has been created.', 'tcp' ), $page_def['title'] );
			}
		}
		return $warnings_msg;
	}

	public function lostpassword_url( $url, $redirect ) {
		$args = array( 'action' => 'lostpassword' );

		if ( !empty( $redirect ) ) {
			$args['redirect_to'] = $redirect;
		}
		return add_query_arg( $args, site_url('wp-login.php') );
	}

	public function network_site_url( $url, $path, $scheme ) {

		if ( stripos( $url, 'action=lostpassword') !== false ) {
			return site_url( 'wp-login.php?action=lostpassword', $scheme );
		}
		if ( stripos( $url, "action=resetpass" ) !== false ) {
			return site_url('wp-login.php?action=resetpass', $scheme );
		}

		return $url;
	}

	function login_url( $login_url, $redirect, $force_reauth ) {
		if ( self::page_exists( 'tcp_my_account' ) ) {
			return self::get_page_url( 'tcp_my_account' );
		} else {
			return $login_url;
		}
	}

	public function wp_mail_from_name( $name ) {
		return get_bloginfo( 'name' );
	}

	public function wp_mail_content_type( $content_type ) {
		return 'text/html';
	}

	/**
	 * Returns page definitions needed by the plugin
	 * 
	 * @uses apply_filters calling 'tcp_page_definitions'
	 */
	private static function get_page_definition() {
		$page_definitions = array(

			// tcp_my_account is creted in TheCartPress.class.php (it's in the menu)
			'tcp-member-login' => array(
				'title' => __( 'Sign In', 'tcp' ),
				'content' => '[tcp-custom-login-form]'
			),
			'tcp-member-register' => array(
				'title' => __( 'Register', 'tcp' ),
				'content' => '[tcp-custom-register-form]'
			),
			'tcp-member-password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'tcp' ),
				'content' => '[tcp-custom-password-lost-form]'
			),
			'tcp-member-password-reset' => array(
				'title' => __( 'Pick a New Password', 'tcp' ),
				'content' => '[tcp-custom-password-reset-form]'
			)
		);
		$page_definitions = apply_filters( 'tcp_page_definitions', $page_definitions );
		return $page_definitions;
	}

	/**
	 * Plugin activation hook.
	 *
	 * Creates all WordPress pages needed by the plugin.
	 */
	public static function tcp_activate_plugin() {

		// Gets information needed for creating the plugin's pages
		$page_definitions = self::get_page_definition();

		foreach ( $page_definitions as $page_key => $page_def ) {

			// Checks that the page doesn't exist already
			if ( !self::page_exists( $page_key ) ) {

				// Adds the page using the data from page definition
				self::create_page( $page_key, $page_def );
			}
		}
	}

	/**
	 * Creates the page and save the id in an option
	 * 
	 * @param $page_key, used to save the page id as an option
	 * @param $page_def page definition array (title' => '', 'content' => ''), ...
	 */
	public static function create_page( $page_key, $page_def ) {
		$page_id = wp_insert_post( array(
			'post_content'	 => $page_def['content'],
			'post_title'	 => $page_def['title'],
			'post_status'	 => 'publish',
			'post_type'		 => 'page',
			'ping_status'	 => 'closed',
			'comment_status' => 'closed',
		) );
		update_option( $page_key . '_page_id', $page_id );
		return $page_id;
	}
	/**
	 * Returns true if the page key exists
	 *
	 * @since 1.5
	 */
	public static function page_exists( $page_key ) {
		return self::get_page_url( $page_key ) !== false;
	}

	/**
	 * Returns page URL
	 *
	 * @since 1.5
	 */
	public static function get_page_url( $page_key ) {

		$page_id = get_option( $page_key . '_page_id' );

		if ( 'publish' != get_post_status( $page_id ) ) {
			return false;
		}

		$url = get_permalink( $page_id );
		if ( $url !== false ) {
			if ( self::endswith( $url, '__trashed/' ) ) {
				return false;
			}
		}
		return $url;
	}

	/**
	 * Returns true if the gicen string ends with $test
	 *
	 * @param $string where search for the test
	 * @param $test, string to search
	 * @return true if $String ends with $test
	 * @since 1.5
	 */
	static function endswith( $string, $test ) {
		$strlen = strlen( $string );
		$testlen = strlen( $test );
		if ( $testlen > $strlen ) {
			return false;
		}
		return substr_compare( $string, $test, $strlen - $testlen, $testlen ) === 0;
	}

	//
	// REDIRECT FUNCTIONS
	//

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function redirect_to_custom_login() {

		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}

			// The rest are redirected to the login page
			$login_url = self::get_page_url( 'tcp_my_account' );
			if ( $login_url === false ) {
				return;
			}

			if ( ! empty( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] != get_admin_url() ) {
				$login_url = add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $login_url );
			}

			if ( ! empty( $_REQUEST['checkemail'] ) ) {
				$login_url = add_query_arg( 'checkemail', $_REQUEST['checkemail'], $login_url );
			}

			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
	 * @param string            $username   The user name used to log in.
	 * @param string            $password   The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	public function maybe_redirect_at_authenticate( $user, $username, $password ) {

		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if ( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );

				//$login_url = self::get_page_url( 'tcp-member-login' );
				$login_url = self::get_page_url( 'tcp_my_account' );
				if ( $login_url === false ) {
					return;
				}
				$login_url = add_query_arg( 'login', $error_codes, $login_url );

				wp_redirect( $login_url );
				exit;
			}
		}

		return $user;
	}

	/**
	 * Returns the URL to which the user should be redirected after the (successful) login.
	 *
	 * @param string           $redirect_to           The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string Redirect URL
	 */
	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		$redirect_url = home_url();

		if ( ! isset( $user->ID ) ) {
			return $redirect_url;
		}

		if ( user_can( $user, 'manage_options' ) ) {

			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( $requested_redirect_to == '' ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $redirect_to;
			}
		} else {

			// Non-admin users always go to their account page after login
			$redirect_url = self::get_page_url( 'tcp_my_account' );
		}

		return wp_validate_redirect( $redirect_url, home_url() );
	}

	/**
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {
		//$login_url = self::get_page_url( 'tcp-member-login' );
		$login_url = self::get_page_url( 'tcp_my_account' );
		if ( $login_url === false ) {
			return;
		}
		$login_url = add_query_arg( 'logged_out', true, $login_url );
		wp_redirect( $login_url );
		exit;
	}

	/**
	 * Redirects the user to the custom registration page instead
	 * of wp-login.php?action=register.
	 */
	public function redirect_to_custom_register() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
			} else {
				wp_redirect( self::get_page_url( 'tcp-member-register' ) );
			}
			exit;
		}
	}

	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}

			$redirect_url = self::get_page_url( 'tcp-member-password-lost' );
			if ( $redirect_url === false ) {
				return;
			}
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Redirects the user to the custom "Retrieve your password?" page instead of
	 * wp-login.php?action=retrievepassword.
	 */
	public function redirect_to_custom_retrievepassword() {

		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( !is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}

			$redirect_url = self::get_page_url( 'tcp-member-password-reset' );
			if ( $redirect_url === false ) {
				return;
			}
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Redirects to the custom password reset page, or the login page
	 * if there are errors.
	 */
	public function redirect_to_custom_password_reset() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Verify key / login combo
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {

					$redirect_url = self::get_page_url( 'tcp_my_account' );
					if ( $redirect_url === false ) {
						return;
					}
					$redirect_url = add_query_arg( 'login', 'expiredkey', $redirect_url );

					wp_redirect( $redirect_url );
				} else {
					//$redirect_url = self::get_page_url( 'tcp-member-login' );
					$redirect_url = self::get_page_url( 'tcp_my_account' );
					if ( $redirect_url === false ) {
						return;
					}
					$redirect_url = add_query_arg( 'login', 'invalidkey', $redirect_url );

					wp_redirect( $redirect_url );
				}
				exit;
			}

			$redirect_url = self::get_page_url( 'tcp-member-password-reset' );
			if ( $redirect_url === false ) {
				return;
			}
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

			wp_redirect( $redirect_url );
			exit;
		}
	}


	//
	// FORM RENDERING SHORTCODES
	//

	/**
	 * Displays a login/register form.
	 * Used by tcp_my_account shortcode.
	 *
	 * @param array $atts, login => allow login form, register => allow registration form
	 */
	function render_my_account( $attributes, $content = null ) {


		$current_user = wp_get_current_user();
		if ( $current_user->ID > 0) {

			// Renders the author profile
			return $this->get_template_html( 'tcp_author_profile', $attributes );
		} else {

			// Renders login form
			return $this->render_login_form( $attributes, $content );
		}
	}

	/**
	 * A shortcode for rendering the login form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
     * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_login_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( isset( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] != get_admin_url() ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );

			foreach ( $error_codes as $code ) {
				$errors []= $this->get_error_message( $code );
			}
		}
		$attributes['errors'] = $errors;

		// Checks if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == true;

		// Checks if the user just registered
		$attributes['registered'] = isset( $_REQUEST['registered'] );

		// Checks if the user just requested a new password
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';

		// Checks if user just updated password
		$attributes['password_updated'] = isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed';

		// Renders the login form using an external template
		return $this->get_template_html( 'tcp_login_form', $attributes );
	}

	/**
	 * A shortcode for rendering the new user registration form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_register_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'tcp' );
		} elseif ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'tcp' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['register-errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['register-errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->get_error_message( $error_code );
				}
			}

			// Retrieve recaptcha key
			$attributes['recaptcha_site_key'] = get_option( 'personalize-login-recaptcha-site-key', null );

			return tcp_register_form( array( 'echo' => false ) );
			//return $this->get_template_html( 'tcp_register_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'tcp' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->get_error_message( $error_code );
				}
			}

			return $this->get_template_html( 'tcp_password_lost_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'tcp' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = $_REQUEST['login'];
				$attributes['key'] = $_REQUEST['key'];

				// Error messages
				$errors = array();
				if ( isset( $_REQUEST['error'] ) ) {
					$error_codes = explode( ',', $_REQUEST['error'] );

					foreach ( $error_codes as $code ) {
						$errors []= $this->get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;

				return $this->get_template_html( 'tcp_password_reset_form', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'tcp' );
			}
		}
	}

	/**
	 * An action function used to include the reCAPTCHA JavaScript file
	 * at the end of the page.
	 */
	public function add_captcha_js_to_footer( $lang = 'en' ) {
		echo "<script src='https://www.google.com/recaptcha/api.js?hl={$lang}'></script>";
	}

	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php)
	 * @param array  $attributes    The PHP variables for the template
	 *
	 * @return string               The contents of the template.
	 */
	private function get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		$template = $template_name . '.php';
		$located = locate_template( $template );
		if ( strlen( $located ) == 0 ) {
			$located = TCP_THEMES_TEMPLATES_FOLDER . $template;
		}
		ob_start();

		do_action( 'personalize_login_before_' . $template_name );

		require( $located );

		do_action( 'personalize_login_after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}


	//
	// ACTION HANDLERS FOR FORMS IN FLOW
	//

	/**
	 * Handles the registration of a new user.
	 *
	 * Used through the action hook "login_form_register" activated on wp-login.php
	 * when accessed through the registration action.
	 */
	public function do_register_user() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$redirect_url = self::get_page_url( 'tcp-member-register' );
			if ( $redirect_url === false ) {
				return;
			}

			if ( ! get_option( 'users_can_register' ) ) {
				// Registration closed, display error
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} elseif ( ! $this->verify_recaptcha() ) {
				// Recaptcha check failed, display error
				$redirect_url = add_query_arg( 'register-errors', 'captcha', $redirect_url );
			} else {
				$email = $_POST['email'];
				$first_name = sanitize_text_field( $_POST['first_name'] );
				$last_name = sanitize_text_field( $_POST['last_name'] );

				$result = $this->register_user( $email, $first_name, $last_name );

				if ( is_wp_error( $result ) ) {
					// Parse errors into a string and append as parameter to redirect
					$errors = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
				} else {
					// Success, redirect to login page.
					//$redirect_url = self::get_page_url( 'tcp-member-login' );
					$redirect_url = self::get_page_url( 'tcp_my_account' );
					if ( $redirect_url === false ) {
						return;
					}
					$redirect_url = add_query_arg( 'registered', $email, $redirect_url );
				}
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Initiates password reset.
	 */
	public function do_password_lost() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$errors = retrieve_password();
			if ( is_wp_error( $errors ) ) {
				// Errors found
				$redirect_url = self::get_page_url( 'tcp-member-password-lost' );
				if ( $redirect_url === false ) {
					return;
				}
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent
				//$redirect_url = self::get_page_url( 'tcp-member-login' );
				$redirect_url = self::get_page_url( 'tcp_my_account' );
				if ( $redirect_url === false ) {
					return;
				}
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
				if ( ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_url = $_REQUEST['redirect_to'];
				}
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function do_password_reset() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$rp_key = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];

			$user = check_password_reset_key( $rp_key, $rp_login );

			if ( ! $user || is_wp_error( $user ) ) {
				//$redirect_url = self::get_page_url( 'tcp-member-login' );
				$redirect_url = self::get_page_url( 'tcp_my_account' );
				if ( $redirect_url === false ) {
					return;
				}
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					$redirect_url = add_query_arg( 'login', 'expiredkey', $redirect_url );
					wp_redirect( $redirect_url );
				} else {
					$redirect_url = add_query_arg( 'login', 'invalidkey', $redirect_url );
					wp_redirect( $redirect_url );
				}
				exit;
			}

			if ( isset( $_POST['pass1'] ) ) {
				
				$redirect_url = self::get_page_url( 'tcp-member-password-reset' );
				if ( $redirect_url === false ) {
					return;
				}
				if ( $_POST['pass1'] != $_POST['pass2'] ) {

					// Passwords don't match
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
					wp_redirect( $redirect_url );
					exit;
				}

				if ( empty( $_POST['pass1'] ) ) {

					// Password is empty
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
					wp_redirect( $redirect_url );
					exit;

				}

				// Parameter checks OK, reset password
				reset_password( $user, $_POST['pass1'] );

				//$redirect_url = self::get_page_url( 'tcp-member-login' );
				$redirect_url = self::get_page_url( 'tcp_my_account' );
				if ( $redirect_url === false ) {
					return;
				}
				$redirect_url = add_query_arg( 'password', 'changed', $redirect_url );
				wp_redirect( $redirect_url );
			} else {
				echo __( 'Invalid request.', 'tcp' );
			}

			exit;
		}
	}

	//
	// OTHER CUSTOMIZATIONS
	//

	/**
	 * Returns the message body for the password reset mail.
	 * Called through the retrieve_password_message filter.
	 *
	 * @param string  $message    Default mail message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 *
	 * @return string   The mail message to send.
	 */
	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		// Create new message
		/*$msg  = __( 'Hello!', 'tcp' ) . "\r\n\r\n";
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'tcp' ), $user_login ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'tcp' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'tcp' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key={$key}&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'tcp' ) . "\r\n";

		return $msg;*/
		
		if ( strpos( $_POST['user_login'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
		} else {
			$login = trim( $_POST['user_login'] );
			$user_data = get_user_by( 'login', $login );
		}

		$user_login = $user_data->user_login;
		//$reset_url = network_site_url( "wp-login.php?action=rp&key={$key}&login=" . rawurlencode( $user_login ), 'login' );
		$reset_url = site_url( "wp-login.php?action=rp&key={$key}&login=" . rawurlencode( $user_login ), 'login' );

		$located = locate_template( 'tcp_reset_password.php' );
		if ( strlen( $located ) == 0 ) {
			$located = TCP_THEMES_TEMPLATES_FOLDER . 'tcp_reset_password.php';
		}

		ob_start();
		include( $located );
		$message = ob_get_clean();
		return $message;
	}


	//
	// HELPER FUNCTIONS
	//

	/**
	 * Validates and then completes the new user signup process if all went well.
	 *
	 * @param string $email         The new user's email address
	 * @param string $first_name    The new user's first name
	 * @param string $last_name     The new user's last name
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	private function register_user( $email, $first_name, $last_name ) {
		$errors = new WP_Error();

		// Email address is used as both username and email. It is also the only
		// parameter we need to validate
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists') );
			return $errors;
		}

		// Generate the password so that the subscriber will have to check email...
		$password = wp_generate_password( 12, false );

		$user_data = array(
			'user_login' => $email,
			'user_email' => $email,
			'user_pass'	 => $password,
			'first_name' => $first_name,
			'last_name'	 => $last_name,
			'nickname'	 => $first_name,
		);

		$user_id = wp_insert_user( $user_data );
		wp_new_user_notification( $user_id, $password );

		return $user_id;
	}

	/**
	 * Checks that the reCAPTCHA parameter sent with the registration
	 * request is valid.
	 *
	 * @return bool True if the CAPTCHA is OK, otherwise false.
	 */
	private function verify_recaptcha() {
		// This field is set by the recaptcha widget if check is successful
		if ( isset ( $_POST['g-recaptcha-response'] ) ) {
			$captcha_response = $_POST['g-recaptcha-response'];
		} else {
			return false;
		}

		// Verify the captcha response from Google
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret' => get_option( 'personalize-login-recaptcha-secret-key' ),
					'response' => $captcha_response
				)
			)
		);

		$success = false;
		if ( $response && is_array( $response ) ) {
			$decoded_response = json_decode( $response['body'] );
			$success = $decoded_response->success;
		}

		return $success;
	}

	/**
	 * Redirects the user to the correct page depending on whether he / she
	 * is an admin or not.
	 *
	 * @param string $redirect_to   An optional redirect_to URL for admin users
	 */
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		} else {
			$redirect_url = self::get_page_url( 'tcp_my_account' );
			if ( $redirect_url === false ) {
				return;
			}
			wp_redirect( $redirect_url );
		}
		exit;
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 *
	 * @return string               An error message.
	 */
	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			// Login errors

			case 'empty_username':
				return __( 'You do have an email address, right?', 'tcp' );

			case 'empty_password':
				return __( 'You need to enter a password to login.', 'tcp' );

			case 'invalid_username':
				return __( "We don't have any users with that email address. Maybe you used a different one when signing up?", 'tcp' );

			case 'incorrect_password':
				$err = __( "The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?", 'tcp' );
				return sprintf( $err, wp_lostpassword_url() );

			// Registration errors
			case 'email':
				return __( 'The email address you entered is not valid.', 'tcp' );

			case 'email_exists':
				return __( 'An account exists with this email address.', 'tcp' );

			case 'closed':
				return __( 'Registering new users is currently not allowed.', 'tcp' );

			case 'captcha':
				return __( 'The Google reCAPTCHA check failed. Are you a robot?', 'tcp' );

			// Lost password

			case 'empty_username':
				return __( 'You need to enter your email address to continue.', 'tcp' );

			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'tcp' );

			// Reset password

			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'tcp' );

			case 'password_reset_mismatch':
				return __( "The two passwords you entered don't match.", 'tcp' );

			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'tcp' );

			default:
				break;
		}

		return __( 'An unknown error occurred. Please try again later.', 'tcp' );
	}


	//
	// PLUGIN SETUP
	//

	/**
	 * Registers the settings fields needed by the plugin.
	 */
	public function register_settings_fields() {

		// Creates settings fields for the two keys used by reCAPTCHA
		register_setting( 'general', 'personalize-login-recaptcha-site-key' );
		register_setting( 'general', 'personalize-login-recaptcha-secret-key' );

		add_settings_field(
			'personalize-login-recaptcha-site-key',
			'<label for="personalize-login-recaptcha-site-key">' . __( 'reCAPTCHA site key' , 'tcp' ) . '</label>',
			array( $this, 'render_recaptcha_site_key_field' ),
			'general'
		);

		add_settings_field(
			'personalize-login-recaptcha-secret-key',
			'<label for="personalize-login-recaptcha-secret-key">' . __( 'reCAPTCHA secret key' , 'tcp' ) . '</label>',
			array( $this, 'render_recaptcha_secret_key_field' ),
			'general'
		);
	}

	public function render_recaptcha_site_key_field() {
		$value = get_option( 'personalize-login-recaptcha-site-key', '' );
		echo '<input type="text" id="personalize-login-recaptcha-site-key" name="personalize-login-recaptcha-site-key" value="' . esc_attr( $value ) . '" />';
	}

	public function render_recaptcha_secret_key_field() {
		$value = get_option( 'personalize-login-recaptcha-secret-key', '' );
		echo '<input type="text" id="personalize-login-recaptcha-secret-key" name="personalize-login-recaptcha-secret-key" value="' . esc_attr( $value ) . '" />';
	}

}

// Run at plugin activation
add_action( 'tcp_activate_plugin', array( 'TCP_CustomLogin', 'tcp_activate_plugin' ) );

$tcp_customLogin = new TCP_CustomLogin();

/**
 * Returns the url to the password reset page
 * 
 * @since 1.5
 */
function tcp_get_the_reset_password_url() {
	return TCP_CustomLogin::get_page_url( 'tcp-member-password-reset' );
}

	/**
	 * Outputs the url to the password reset page
	 * 
	 * @since 1.5
	 * @see tcp_get_the_reset_password_url
	 */
	function tcp_the_reset_password_url() {
		echo tcp_get_the_reset_password_url();
	}

endif; // class_exists check
