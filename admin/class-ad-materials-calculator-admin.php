<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		if ( ! $this->is_materials_calculator_admin_asset_page() ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ad-materials-calculator-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		if ( ! $this->is_materials_calculator_admin_asset_page() ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ad-materials-calculator-admin.js', array( 'jquery', 'selectWoo' ), $this->version, false );

	}

	/**
	 * Determine whether the current request needs Materials Calculator admin assets.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	private function is_materials_calculator_admin_asset_page() {

		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}

		$page = sanitize_key( wp_unslash( $_GET['page'] ) );

		if ( $page === 'materials-calculator-share-links' ) {
			return true;
		}

		return ( isset( $_GET['tab'] ) && 'wc-settings' === $page && 'materials-calculator' === sanitize_key( wp_unslash( $_GET['tab'] ) ) );
	}

	/**
	 * Add the calculator share links page under WooCommerce.
	 *
	 * @since    1.0.0
	 */
	public function add_share_links_admin_page() {

		$subpage = add_submenu_page(
			'woocommerce',
			esc_html__( 'Calculator Share Links', 'ad-materials-calculator' ),
			esc_html__( 'Calculator Share Links', 'ad-materials-calculator' ),
			'manage_woocommerce',
			'materials-calculator-share-links',
			[$this, 'render_share_links_admin_page']
		);

		add_action( 'load-' . $subpage, [$this, 'display_share_links_screen_options'] );
	}

	/**
	 * Register screen options for the calculator share links page.
	 *
	 * @since    1.0.0
	 */
	public function display_share_links_screen_options() {

		add_screen_option(
			'per_page',
			[
				'option' => 'ad_materials_calculator_share_links_per_page',
			]
		);

		$share_links_table = new Ad_Materials_Calculator_Share_Links_List_Table();
	}

	/**
	 * Save the user's share links per page setting.
	 *
	 * @since    1.0.0
	 * @param    bool|int $screen_option Existing screen option value.
	 * @param    string   $option Screen option name.
	 * @param    int      $value Submitted value.
	 * @return   bool|int
	 */
	public function save_share_links_screen_options( $screen_option, $option, $value ) {

		return $option === 'ad_materials_calculator_share_links_per_page' ? $value : $screen_option;
	}

	/**
	 * Render the calculator share links admin page.
	 *
	 * @since    1.0.0
	 */
	public function render_share_links_admin_page() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view calculator share links.', 'ad-materials-calculator' ) );
		}

		$share_links_table = new Ad_Materials_Calculator_Share_Links_List_Table();
		$share_links_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Calculator Share Links', 'ad-materials-calculator' ); ?></h1>
			<hr class="wp-header-end">
			<form method="get">
				<input type="hidden" name="page" value="materials-calculator-share-links" />
				<?php
				$share_links_table->search_box( esc_html__( 'Search Share Links', 'ad-materials-calculator' ), 'materials-calculator-share-links' );
				$share_links_table->display();
				?>
			</form>
			<div class="ad-mc-share-links-modal" hidden>
				<div class="ad-mc-share-links-modal__backdrop" data-ad-mc-share-links-close></div>
				<div class="ad-mc-share-links-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ad-mc-share-links-modal-title">
					<button type="button" class="ad-mc-share-links-modal__close" data-ad-mc-share-links-close aria-label="<?php esc_attr_e( 'Close share link details', 'ad-materials-calculator' ); ?>">&times;</button>
					<h2 id="ad-mc-share-links-modal-title"><?php esc_html_e( 'Share Link Details', 'ad-materials-calculator' ); ?></h2>
					<div class="ad-mc-share-links-modal__content"></div>
				</div>
			</div>
		</div>
		<?php
	}

}
