<?php

namespace WpeGoogleSignIn;

use \Wpextend\Package\AdminNotice;
use \Wpextend\Package\RenderAdminHtml;
use \Wpextend\Package\TypeField;

/**
*
*/
class Options {

    private static $_instance,
        $name_admin_post_options_update = 'wpe_google_singin_options_update';
    
    public static $prefix_name_database = 'wpe_google_signin_';
    
    static public $admin_url = '',
        $json_file_name = 'client_secret.json';



	/**
	*
	*
	*/
	public static function getInstance() {

        if (is_null(self::$_instance)) {
             self::$_instance = new Options();
        }

        return self::$_instance;
   }



   /**
	* The constructor.
	*
	* @return void
	*/
	private function __construct() {

		// Configure hooks
        $this->create_hooks();
    }



    /**
	* Register some Hooks
	*
	* @return void
	*/
	public function create_hooks() {

        add_action( 'admin_menu', array($this, 'define_admin_menu') );
        add_action( 'admin_post_' . self::$name_admin_post_options_update, array($this, 'update') ); 
    }
    


    /**
     * Add sub-menu page into WPExtend menu
     * 
     */
    public function define_admin_menu() {

        // Base 64 encoded SVG image.
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( PLUGIN_DIR_PATH . 'assets/img/icon.svg' ) );
        add_menu_page(PLUGIN_NAME, PLUGIN_NAME, 'manage_options', 'wpe_google_signin_options', array( $this, 'render_admin_page' ), $icon_svg );
    }



   /**
	* Render HTML admin page
	*
	* @return string
	*/
	public function render_admin_page() {

        // Header page & open form
		$retour_html = RenderAdminHtml::header(PLUGIN_NAME . ' options');

        $retour_html .= '<div class="mt-1 white">';
        $retour_html .= RenderAdminHtml::form_open( admin_url( 'admin-post.php' ), self::$name_admin_post_options_update );
        
        $retour_html .= RenderAdminHtml::table_edit_open();
        $retour_html .= TypeField::render_input_radio( 'Allow new users' , 'allow_new_users', [ 'false' => 'No, accept only users already registred.', 'true' => 'Yes' ], $this->allow_new_users() );
        $retour_html .= TypeField::render_input_textarea( 'JSON credentials file', 'json_client_secret', '', false, 'Leave empty to not update.<br /><br />Paste your file content here.<br />For more information, see the <a href="https://console.developers.google.com/apis/" target="_blank">authentication documentation</a>', false );
        $retour_html .= TypeField::render_disable_input_text( 'Authorized redirect URIs', '', SignIn::get_redirect_uri(),  'Add this path in your OAuth 2.0 client ID <a href="https://console.developers.google.com/apis/dashboard" target="_blank">configuration</a>' );
        $retour_html .= RenderAdminHtml::table_edit_close();

        $retour_html .= RenderAdminHtml::form_close( 'Save', true );
        $retour_html .= '</div>';

		// return
		echo $retour_html;
    }



    /**
     * Save options
     * 
     */
    public function update() {

        check_admin_referer($_POST['action']);

        // Allow new users
        if( isset($_POST['allow_new_users']) ) {
            if( $_POST['allow_new_users'] == 'true' )
                update_option( self::$prefix_name_database . 'allow_new_users', true);
            else
                update_option( self::$prefix_name_database . 'allow_new_users', false);

            AdminNotice::add_notice( 'WpeGoogleSignin-0', 'Options saved.', 'success', true, true, PLUGIN_NAME );
        }

        // JSON credentials file
        if( isset($_POST['json_client_secret']) && ! empty($_POST['json_client_secret']) ) {

            try {
                $json_client_secret = stripslashes($_POST['json_client_secret']);

                json_decode( $json_client_secret, false, 512, JSON_THROW_ON_ERROR );

                if( file_put_contents( PLUGIN_DIR_PATH . 'config/' . self::$json_file_name, $json_client_secret ) )
                    AdminNotice::add_notice( 'WpeGoogleSignin-1', 'JSON credentials file successfully added.', 'success', true, true, PLUGIN_NAME );

            } catch (\Exception $e) {
                AdminNotice::add_notice( 'WpeGoogleSignin-2', 'Invalid JSON...', 'error', true, true, PLUGIN_NAME );
            }
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }



    /**
     * Get JSON credentials file
     * 
     */
    public function get_json_client_secret() {

        if( file_exists( PLUGIN_DIR_PATH . 'config/' . self::$json_file_name ) ) {
            try {
                json_decode( file_get_contents( PLUGIN_DIR_PATH . 'config/' . self::$json_file_name ), false, 512, JSON_THROW_ON_ERROR );
                return PLUGIN_DIR_PATH . 'config/' . self::$json_file_name;
            } catch (\Exception $e) {
            }
        }

        return false;
    }



    /**
     * Return if plugin allows new users or not
     * 
     */
    public function allow_new_users() {

        return ( get_option(self::$prefix_name_database . 'allow_new_users') ) ? true : false;
    }



}

