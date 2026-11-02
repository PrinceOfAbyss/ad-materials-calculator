<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/public
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->load_public_dependencies();

	}

	/**
	 * Load the public-facing calculator dependencies.
	 *
	 * @since    1.0.0
	 */
	private function load_public_dependencies() {

		require_once plugin_dir_path( __FILE__ ) . 'calculator/results-shortcode-and-helper-functions.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/add-to-cart-mechanism.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-1.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-2.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-3.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-4.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-5.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-6.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-7.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-8.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-9.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-10.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-11.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-12.php';
		require_once plugin_dir_path( __FILE__ ) . 'calculator/case-13.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/ajax-and-result-helpers.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/preset-rendering-and-calculation.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/bathroom-shower.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/kitchen.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/wall-ceiling.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/floor.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/pool.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/countertop.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/facade.php';
		require_once plugin_dir_path( __FILE__ ) . 'area-calculators/balcony.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/rewrite-wpforms-redirect-url.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/capture-submitted-fields-to-wc-session.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/preselect-finish-from-page.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/show-calculator-discount-smart-tag.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/render-secondary-submit-cta.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/dynamic-image-choices-pigment-color.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/dynamic-image-choices-microterrazzo-finish.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/dynamic-image-choices-monolith-stones.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/format-webhook-checkbox-values.php';
		require_once plugin_dir_path( __FILE__ ) . 'wpforms/sync-b2bking-registration-meta.php';

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ad_Materials_Calculator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ad_Materials_Calculator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ad-materials-calculator-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-wpforms', plugin_dir_url( __FILE__ ) . 'css/ad-materials-calculator-wpforms.css', array( 'wpforms-modern-full' ), $this->version, 'all' );
		wp_register_style( $this->plugin_name . '-areas', plugin_dir_url( __FILE__ ) . 'css/ad-materials-calculator-areas.css', array(), $this->version, 'all' );

		$wpforms_accent_color_css = $this->get_wpforms_accent_color_css();

		if ( $wpforms_accent_color_css !== '' ) {
			wp_add_inline_style( $this->plugin_name . '-wpforms', $wpforms_accent_color_css );
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ad_Materials_Calculator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ad_Materials_Calculator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ad-materials-calculator-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'adMaterialsCalculatorPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'modalCloseLabel'       => __( 'Close', 'ad-materials-calculator' ),
					'modalTitle'            => __( 'Share this cart', 'ad-materials-calculator' ),
					'modalDescription'      => __( 'Copy this link and send it to the customer. The link will add the calculated materials in this cart and take them to checkout.', 'ad-materials-calculator' ),
					'copyButtonLabel'       => __( 'Copy', 'ad-materials-calculator' ),
					'copiedMessage'         => __( 'Copied to clipboard.', 'ad-materials-calculator' ),
					'creatingLinkLabel'     => __( 'Creating link...', 'ad-materials-calculator' ),
					'createLinkError'       => __( 'The share link could not be created.', 'ad-materials-calculator' ),
				),
			)
		);
		wp_register_script( $this->plugin_name . '-areas', plugin_dir_url( __FILE__ ) . 'js/ad-materials-calculator-areas.js', array( 'jquery' ), $this->version, true );

		if ( $this->is_materials_calculator_wpforms_page() ) {
			wp_enqueue_script( $this->plugin_name . '-wpforms', plugin_dir_url( __FILE__ ) . 'js/ad-materials-calculator-wpforms.js', array( 'jquery' ), $this->version, false );
		}

	}

	/**
	 * Check whether the current page should load calculator WPForms behavior.
	 *
	 * @since    1.0.7
	 * @return   bool
	 */
	private function is_materials_calculator_wpforms_page() {

		$page_id = absint( get_option( 'ad_materials_calculator_wpforms_page_id', 0 ) );

		if ( $page_id > 0 ) {
			return is_page( $page_id );
		}

		return false;
	}

	/**
	 * Return the WPForms accent color CSS.
	 *
	 * @since    1.2.2
	 * @return   string
	 */
	private function get_wpforms_accent_color_css() {

		$color = sanitize_hex_color_no_hash( get_option( 'ad_materials_calculator_wpforms_accent_color', '' ) );

		if ( ! $color ) {
			return '';
		}

		return '.wpforms-form{--wpforms-button-background-color: #' . $color . ';}';
	}

	/**
	 * Add a page-state class when the calculator results flag is present.
	 *
	 * @since    1.3.2
	 * @param    array $classes Body classes.
	 * @return   array
	 */
	public function add_calculator_results_body_class( $classes ) {

		if ( isset( $_GET['mc'] ) && '1' === (string) wp_unslash( $_GET['mc'] ) ) {
			$classes[] = 'ad-materials-calc-has-results';
		}

		return $classes;
	}

	/**
	 * Redirect logged-out My Account visitors to the custom login page.
	 *
	 * @since    1.0.9
	 */
	public function redirect_logged_out_my_account_to_login() {

		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}

	/**
	 * Remove B2BKing endpoints from the WooCommerce account menu.
	 *
	 * @since    1.0.9
	 * @param    array $items Account menu items keyed by endpoint.
	 * @return   array
	 */
	public function remove_b2bking_account_menu_items( $items ) {

		unset(
			$items['conversations'],
			$items['offers'],
			$items['bulkorder'],
			$items['subaccounts'],
			$items['purchase-lists'],

			$items['downloads']
		);

		return $items;
	}

	/**
	 * Load the plugin cart template override when WooCommerce asks for it
	 *
	 * @since    1.0.0
	 */
	public function locate_woocommerce_template( $template, $template_name, $template_path ) {

		if ( $template_name !== 'cart/cart.php' ) {
			return $template;
		}

		$plugin_template = dirname( plugin_dir_path( __FILE__ ) ) . '/woocommerce/cart/cart.php';

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;

	}

}
