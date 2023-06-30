<?php

namespace WpeGoogleSignIn;

/**
 *
 */
class SignIn {

    private static $_instance,
        $name_admin_post_action_signin = 'google_oauth_signin';

    private $client;



	/**
    * Static method which instance main class
    */
    public static function getInstance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new SignIn();
        }
        return self::$_instance;
    }



	/**
    * Construct
    */
    private function __construct() {

        // Create hooks
        $this->create_hooks();
    }



	/**
	 * Register some Hooks
	 *
	 * @return void
	 */
	public function create_hooks() {

        // Enqueue styles & scripts
        add_action('login_enqueue_scripts', array( $this, 'login_enqueue_scripts' ) );

        // Hook google_oauth action in order to create or logged user
        add_action( 'admin_post_nopriv_' . self::$name_admin_post_action_signin, array($this, 'sign_client') );

        // Add Google Signin button on login admin page
        add_action( 'login_message', array($this, 'display_link_signin') );
        add_filter( 'wp_login_errors', array($this, 'wp_login_errors') );

        add_filter( 'get_avatar_data', array($this, 'get_avatar_data'), 10, 2 );
        add_filter( 'user_profile_picture_description', array($this, 'user_profile_picture_description'), 10, 2 );
    }



    /**
	 * Wordpress Enqueues functions
	 *
	 */
	public function login_enqueue_scripts() {

		wp_enqueue_style( 'wpegoogleoauth_admin_style', PLUGIN_ASSETS_URL . 'style/admin/style.min.css', false, true );
	}



    /**
     * Init Google client
     * 
     */
    public function init_google_client() {

        $this->client = new \Google_Client();

        // Define scope
        $this->client->addScope( 'email' );
        $this->client->addScope( 'profile' );

        // Add JSON auth config file
        $this->client->setAuthConfig( Options::getInstance()->get_json_client_secret() );

        // Set redirect URL
        $this->client->setRedirectUri( self::get_redirect_uri() );
    }
    


    /**
     * Hook google_oauth action in order to create or logged user
     * 
     */
    public function sign_client() {

        if( isset($_GET['code']) ) {

            // Init Google client
            $this->init_google_client();

            $token = $this->client->fetchAccessTokenWithAuthCode( $_GET['code'] );
            if( is_array($token) && ! array_key_exists('error', $token) ) {

                 // Get profile info
                $google_oauth = new \Google_Service_Oauth2($this->client);
                $google_account_info = $google_oauth->userinfo->get();

                // Create username
                $user_name = strtolower($google_account_info->givenName) . '.' . strtolower($google_account_info->familyName);
                $user_id_from_email = email_exists($google_account_info->email);

                // Check if email already exists or not
                if( $user_id_from_email ) {
                    $user_id = wp_insert_user( [
                        'ID'            => $user_id_from_email,
                        'user_email'    => $google_account_info->email,
                        'user_login'    => $user_name,
                        'first_name'    => $google_account_info->givenName,
                        'last_name'     => $google_account_info->familyName,
                        'display_name'  => $google_account_info->name
                    ] );
                }
                elseif ( Options::getInstance()->allow_new_users() ) {

                    $user_id = wp_insert_user( [
                        'user_email'    => $google_account_info->email,
                        'user_login'    => $user_name,
                        'user_pass'     => wp_generate_password(),
                        'first_name'    => $google_account_info->givenName,
                        'last_name'     => $google_account_info->familyName,
                        'display_name'  => $google_account_info->name
                    ] );
                }
                else {
                    $error_signin = 'Please contact us to create an access.';
                }
                
                // Log in user by setting authentication cookies.
                if( isset($user_id) ) {
                    
                    if( is_wp_error( $user_id ) )
                        $error_signin = $user_id->get_error_message();
                    else {

                        update_user_meta( $user_id, '_' . Options::$prefix_name_database . 'signin', true );
                        update_user_meta( $user_id, '_' . Options::$prefix_name_database . 'picture', $google_account_info->picture );

                        wp_set_auth_cookie( $user_id );
                    }
                        
                }

                // Redirect admin page
                $url_redirect = ( ! empty($error_signin) ) ? add_query_arg( 'error', urlencode($error_signin), wp_login_url()) : admin_url();
                wp_safe_redirect($url_redirect);
                exit;
            }
        }
    }



    /**
     * Add Google Signin button on login admin page
     * 
     */
    public function display_link_signin( $message ) {

        // Init Google client
        $this->init_google_client();

        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();

        $message .= '<div id="wpe_google_oauth_signin_container"><a href="' . $authUrl . '"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="LgbsSe-Bz112c"><g><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path><path fill="none" d="M0 0h48v48H0z"></path></g></svg>Sign In with Google</a></div>';

        return $message;
    }



    /**
     * Hook wp_login_errors in order to display error during Google Signin
     * 
     */
    public function wp_login_errors( $errors ) {

        if( isset($_GET['error']) && ! empty($_GET['error']) )
            $errors->add( '', stripslashes(urldecode($_GET['error'])), 'message' );
        
            return $errors;
    }



    /**
     * Hook get_avatar_data in order to return Google account photo
     * 
     */
    public function get_avatar_data( $args, $id_or_email ) {

        if( is_object($id_or_email) ) {

            if( isset($id_or_email->user_id) )
                $id_or_email = $id_or_email->user_id;
        }

        if( is_numeric($id_or_email) ) {
            $picture = get_user_meta( $id_or_email, '_' . Options::$prefix_name_database . 'picture', true );        
            if( $picture && ! empty($picture) && ! is_null($picture) )
                $args['url'] = $picture;
        }

        return $args;
    }



    /**
     * Hook user_profile_picture_description in order to return specific description
     * 
     */
    public function user_profile_picture_description( $description, $profileuser ) {

        $picture = get_user_meta( $profileuser->ID, '_' . Options::$prefix_name_database . 'picture', true );        
        if( $picture && ! empty($picture) && ! is_null($picture) )
            $description = 'This is your Google account photo.';

        return $description;
    }



    /**
     * Return Google sign-in redirect URI
     * 
     */
    public static function get_redirect_uri() {
        
        return add_query_arg( [ 'action' => self::$name_admin_post_action_signin ], admin_url( 'admin-post.php' ) );
    }



}