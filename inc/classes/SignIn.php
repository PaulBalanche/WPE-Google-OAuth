<?php

namespace WpeGoogleOauth;

/**
 *
 */
class SignIn {

    private static $_instance;

    private $client,
        $signin_password = 'googleoauth';



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

        // Hook google_oauth action in order to create or logged user
        add_action( 'admin_post_nopriv_google_oauth', array($this, 'sign_client') );

        // Add Google Signin button on login admin page
        add_action( 'login_footer', array($this, 'display_link_signin') );
    }



    /**
     * Init Google client
     * 
     */
    public function init_google_client() {

        $this->client = new \Google_Client();

        // Set application name
        $this->client->setApplicationName( 'Application name' );

        // Define scope
        $this->client->addScope( 'email' );
        $this->client->addScope( 'profile' );

        // Add JSON auth config file
        $this->client->setAuthConfig( PLUGIN_DIR_PATH . 'config/client_secret_34262581282-d6l8d4fefiopks0dvj8vocl7mfn99blq.apps.googleusercontent.com.json' );

        // Set redirect URL
        $redirecturi = add_query_arg( [ 'action' => 'google_oauth' ], admin_url( 'admin-post.php' ) );
        $this->client->setRedirectUri($redirecturi);
    }
    


    /**
     * Hook google_oauth action in order to create or logged user
     * 
     */
    static public function sign_client() {

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

                // Check if user_name and email already exists or not
                if ( ! username_exists($user_name) && ! email_exists($google_account_info->email) ) {

                    $user_id = wp_insert_user( [
                        'user_email'    => $google_account_info->email,
                        'user_login'    => $user_name,
                        'user_pass'     => $this->user_password(),
                        'first_name'    => $google_account_info->givenName,
                        'last_name'     => $google_account_info->familyName,
                        'display_name'  => $google_account_info->name
                    ] );
                    // $google_account_info->name->picture
                }

                // Now authenticates the user             
                $user = wp_signon( [
                    'user_login'    => $google_account_info->email,
                    'user_password' => $this->user_password(),
                    'remember'      => false
                ], false );

                // Display errors if there are
                if ( is_wp_error( $user ) ) {
                    echo $user->get_error_message();
                    exit;
                }

                // Redirect admin page
                wp_safe_redirect( admin_url() );
                exit;
            }
        }
    }



    /**
     * Add Google Signin button on login admin page
     * 
     */
    static public function display_link_signin() {

        // Init Google client
        $this->init_google_client();

        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();
        echo '<div style="width: 240px;margin: auto;margin-top: 2rem;text-align: center;"><a href="' . $authUrl . '" class="button button-hero">Login with Google</a></div>';
    }
    


    /**
     * Get user secured password
     */
    public function user_password() {

        return md5( $this->signin_password );
    }



}