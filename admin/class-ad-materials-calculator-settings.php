<?php

/**
 * The WooCommerce settings functionality for the plugin.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 */

/**
 * Handles the Materials Calculator settings tab and nested coverage editor.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Settings {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $version;

    /**
     * Option name used to persist the coverage settings JSON blob.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $coverages_option_name = 'ad_materials_calculator_coverages_json';

    /**
     * Option name used to persist the page ID that renders the calculator WPForms form.
     *
     * @since    1.0.7
     * @access   private
     * @var      string
     */
    private $wpforms_page_id_option_name = 'ad_materials_calculator_wpforms_page_id';

    /**
     * Option name used to persist the calculator WPForms form ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $wpforms_form_id_option_name = 'ad_materials_calculator_wpforms_form_id';

    /**
     * Option name used to persist the applicator registration WPForms form ID.
     *
     * @since    1.0.5
     * @access   private
     * @var      string
     */
    private $applicator_registration_form_id_option_name = 'ad_materials_calculator_applicator_registration_form_id';

    /**
     * Option name used to persist the distributor registration WPForms form ID.
     *
     * @since    1.0.5
     * @access   private
     * @var      string
     */
    private $distributor_registration_form_id_option_name = 'ad_materials_calculator_distributor_registration_form_id';

    /**
     * Option name used to persist the user IDs with extended calculator privileges.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $privileged_user_ids_option_name = 'ad_materials_calculator_privileged_user_ids';

    /**
     * Option name used to persist the B2BKing applicator group ID.
     *
     * @since    1.0.4
     * @access   private
     * @var      string
     */
    private $b2bking_applicator_group_id_option_name = 'ad_materials_calculator_b2bking_applicator_group_id';

    /**
     * Option name used to persist the B2BKing distributor group ID.
     *
     * @since    1.0.4
     * @access   private
     * @var      string
     */
    private $b2bking_distributor_group_id_option_name = 'ad_materials_calculator_b2bking_distributor_group_id';

    /**
     * Option name used to persist the applicator minimum project size.
     *
     * @since    1.0.4
     * @access   private
     * @var      string
     */
    private $applicator_min_sqft_option_name = 'ad_materials_calculator_applicator_min_sqft';

    /**
     * Option name used to persist the distributor minimum project size.
     *
     * @since    1.0.4
     * @access   private
     * @var      string
     */
    private $distributor_min_sqft_option_name = 'ad_materials_calculator_distributor_min_sqft';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string $plugin_name The name of this plugin.
     * @param    string $version The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Add the Materials Calculator tab to WooCommerce settings.
     *
     * @since    1.0.0
     * @param    array $tabs Existing WooCommerce settings tabs.
     * @return   array
     */
    public function add_woocommerce_settings_tab( $tabs ) {

        $tabs['materials-calculator'] = __( 'Materials Calculator', 'ad-materials-calculator' );

        return $tabs;
    }

    /**
     * Render the custom Materials Calculator WooCommerce settings tab.
     *
     * @since    1.0.0
     */
    public function render_woocommerce_settings_tab() {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage WooCommerce settings.', 'ad-materials-calculator' ) );
        }

        $active_section = $this->get_active_system_section();
        $all_schema     = Ad_Materials_Calculator_Coverage_Schema::get_coverages_settings_schema();
        $schema         = Ad_Materials_Calculator_Coverage_Schema::filter_schema_for_system_section( $all_schema, $active_section );
        $stored_values  = $this->get_saved_coverages_settings();

        echo '<div class="ad-mc-settings-page">';
        echo '<h2>' . esc_html__( 'Materials Calculator Coverage Settings', 'ad-materials-calculator' ) . '</h2>';
        $this->render_system_sections_nav( $active_section );

        foreach ( $schema as $case_key => $case_config ) {
            echo '<section class="ad-mc-settings-case">';
            echo '<header class="ad-mc-settings-case__header">';
            echo '<h3>' . esc_html( $case_config['title'] ) . '</h3>';
            echo '</header>';

            $show_clause_headings = count( $case_config['clauses'] ) > 1;

            foreach ( $case_config['clauses'] as $clause_key => $clause_config ) {
                echo '<div class="ad-mc-settings-clause">';
                if ( $show_clause_headings ) {
                    echo '<div class="ad-mc-settings-clause__heading">';
                    echo '<h4>' . esc_html( $clause_config['title'] ) . '</h4>';
                    if ( ! empty( $clause_config['description'] ) ) {
                        echo '<p>' . esc_html( $clause_config['description'] ) . '</p>';
                    }
                    echo '</div>';
                }

                echo '<table class="widefat striped ad-mc-settings-table">';
                echo '<thead><tr>';
                echo '<th>' . esc_html__( 'Material', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Coverage 1', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Coverage 2', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Coverage 3', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Total', 'ad-materials-calculator' ) . '</th>';
                echo '</tr></thead>';
                echo '<tbody>';

                foreach ( $clause_config['materials'] as $material_key => $material_config ) {
                    $row_values = $this->get_material_row_values(
                        $stored_values,
                        $case_key,
                        $clause_key,
                        $active_section,
                        $material_key,
                        $material_config['values']
                    );

                    echo '<tr>';
                    echo '<td class="ad-mc-settings-table__label">';
                    echo '<span class="ad-mc-settings-table__material-name">' . esc_html( $material_config['label'] ) . '</span>';
                    if ( ! empty( $material_config['hint'] ) ) {
                        echo '<span class="ad-mc-settings-table__material-hint">' . esc_html( $material_config['hint'] ) . '</span>';
                    }
                    echo '</td>';

                    for ( $i = 0; $i < 3; $i++ ) {
                        $field_name  = sprintf( 'ad_materials_calculator_coverages[%s][%s][%s][%s][%d]', $case_key, $clause_key, $active_section, $material_key, $i );
                        $field_value = isset( $row_values[$i] ) ? $row_values[$i] : '';

                        echo '<td>';
                        printf(
                            '<input type="number" step="0.001" min="0" class="small-text ad-mc-settings-decimal" name="%1$s" value="%2$s" />',
                            esc_attr( $field_name ),
                            esc_attr( $field_value )
                        );
                        echo '</td>';
                    }

                    echo '<td class="ad-mc-settings-table__sum" data-row-sum>' . esc_html( $this->get_material_row_sum( $row_values ) ) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
                echo '</div>';
            }

            echo '</section>';
        }

        echo '<h2>' . esc_html__( 'WPForms Settings', 'ad-materials-calculator' ) . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-wpforms-page-id">' . esc_html__( 'Calculator Page ID', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="1" step="1" class="small-text" id="ad-materials-calculator-wpforms-page-id" name="ad_materials_calculator_wpforms_page_id" value="%1$s" />',
            esc_attr( $this->get_saved_wpforms_page_id() > 0 ? (string) $this->get_saved_wpforms_page_id() : '' )
        );
        echo '<p class="description">' . esc_html__( 'Set the page ID that renders the calculator form.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr class="ad-mc-settings-separated-row">';
        echo '<th scope="row"><label for="ad-materials-calculator-wpforms-form-id">' . esc_html__( 'Materials Calculator Form ID', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="1" step="1" class="small-text" id="ad-materials-calculator-wpforms-form-id" name="ad_materials_calculator_wpforms_form_id" value="%1$s" />',
            esc_attr( $this->get_saved_wpforms_form_id() > 0 ? (string) $this->get_saved_wpforms_form_id() : '' )
        );
        echo '<p class="description">' . esc_html__( 'Set the WPForms form ID used by the materials calculator integration. If empty, the integration stays inactive.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-applicator-registration-form-id">' . esc_html__( 'Applicator Registration Form ID', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="1" step="1" class="small-text" id="ad-materials-calculator-applicator-registration-form-id" name="ad_materials_calculator_applicator_registration_form_id" value="%1$s" />',
            esc_attr( $this->get_saved_applicator_registration_form_id() > 0 ? (string) $this->get_saved_applicator_registration_form_id() : '' )
        );
        echo '<p class="description">' . esc_html__( 'Set the WPForms form ID used by the applicator registration to B2BKing sync. If empty, this sync stays inactive.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-distributor-registration-form-id">' . esc_html__( 'Distributor Registration Form ID', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="1" step="1" class="small-text" id="ad-materials-calculator-distributor-registration-form-id" name="ad_materials_calculator_distributor_registration_form_id" value="%1$s" />',
            esc_attr( $this->get_saved_distributor_registration_form_id() > 0 ? (string) $this->get_saved_distributor_registration_form_id() : '' )
        );
        echo '<p class="description">' . esc_html__( 'Set the WPForms form ID used by the distributor registration to B2BKing sync. If empty, this sync stays inactive.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody></table>';

        $privileged_user_ids = $this->get_saved_privileged_user_ids();
        $users               = get_users(
            [
                'orderby' => 'display_name',
                'order'   => 'ASC',
            ]
        );

        echo '<h2>' . esc_html__( 'Extended Privileges', 'ad-materials-calculator' ) . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-privileged-user-ids">' . esc_html__( 'Privileged Staff Members', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        echo '<select id="ad-materials-calculator-privileged-user-ids" name="ad_materials_calculator_privileged_user_ids[]" class="ad-mc-settings-user-select" multiple="multiple" data-placeholder="' . esc_attr__( 'Select staff members...', 'ad-materials-calculator' ) . '">';

        foreach ( $users as $user ) {
            printf(
                '<option value="%1$d"%2$s>%3$s</option>',
                absint( $user->ID ),
                selected( in_array( (int) $user->ID, $privileged_user_ids, true ), true, false ),
                esc_html( sprintf( '%s (%s)', $user->display_name, $user->user_login ) )
            );
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Selected users can apply discounts and create share links for calculated materials. If empty, these extended privileges stay inactive.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody></table>';

        echo '<h2>' . esc_html__( 'Calculator Pricing Rules', 'ad-materials-calculator' ) . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-b2bking-applicator-group-id">' . esc_html__( 'Applicator Group ID', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="1" step="1" class="small-text" id="ad-materials-calculator-b2bking-applicator-group-id" name="ad_materials_calculator_b2bking_applicator_group_id" value="%1$s" />',
            esc_attr( $this->get_saved_b2bking_applicator_group_id() > 0 ? (string) $this->get_saved_b2bking_applicator_group_id() : '' )
        );
        echo '<p class="description">' . esc_html__( 'B2BKing group ID used to identify users eligible for calculator applicator pricing.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-b2bking-distributor-group-id">' . esc_html__( 'Distributor Group ID', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="1" step="1" class="small-text" id="ad-materials-calculator-b2bking-distributor-group-id" name="ad_materials_calculator_b2bking_distributor_group_id" value="%1$s" />',
            esc_attr( $this->get_saved_b2bking_distributor_group_id() > 0 ? (string) $this->get_saved_b2bking_distributor_group_id() : '' )
        );
        echo '<p class="description">' . esc_html__( 'B2BKing group ID used to identify users eligible for calculator distributor pricing.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-applicator-min-sqft">' . esc_html__( 'Applicator Minimum sq. ft.', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="0.001" step="0.001" class="small-text" id="ad-materials-calculator-applicator-min-sqft" name="ad_materials_calculator_applicator_min_sqft" value="%1$s" />',
            esc_attr( $this->get_saved_applicator_min_sqft() )
        );
        echo '<p class="description">' . esc_html__( 'Minimum project size required before applicator or distributor users receive special applicator prices. If empty or invalid, applicator pricing stays inactive.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="ad-materials-calculator-distributor-min-sqft">' . esc_html__( 'Distributor Minimum sq. ft.', 'ad-materials-calculator' ) . '</label></th>';
        echo '<td>';
        printf(
            '<input type="number" min="0.001" step="0.001" class="small-text" id="ad-materials-calculator-distributor-min-sqft" name="ad_materials_calculator_distributor_min_sqft" value="%1$s" />',
            esc_attr( $this->get_saved_distributor_min_sqft() )
        );
        echo '<p class="description">' . esc_html__( 'Minimum project size required before applicator or distributor users receive special distributor prices. If empty or invalid, distributor pricing stays inactive.', 'ad-materials-calculator' ) . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody></table>';

        echo '<input type="hidden" name="ad_materials_calculator_active_section" value="' . esc_attr( $active_section ) . '" />';
        echo '</div>';
    }

    /**
     * Save the custom Materials Calculator WooCommerce settings tab.
     *
     * @since    1.0.0
     */
    public function save_woocommerce_settings_tab() {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $active_section  = $this->get_active_system_section();
        $submitted       = isset( $_POST['ad_materials_calculator_coverages'] ) ? wp_unslash( $_POST['ad_materials_calculator_coverages'] ) : [];
        $all_schema      = Ad_Materials_Calculator_Coverage_Schema::get_coverages_settings_schema();
        $section_schema  = Ad_Materials_Calculator_Coverage_Schema::filter_schema_for_system_section( $all_schema, $active_section );
        $sanitized       = $this->sanitize_coverages_settings( $submitted, $section_schema, $active_section );
        $existing        = $this->get_saved_coverages_settings();
        $merged          = $this->merge_coverages_settings( $existing, $sanitized );
        $wpforms_page_id = $this->get_submitted_wpforms_page_id();
        $wpforms_form_id = $this->get_submitted_wpforms_form_id();
        $app_form_id     = $this->get_submitted_applicator_registration_form_id();
        $dist_form_id    = $this->get_submitted_distributor_registration_form_id();
        $privileged_ids  = $this->get_submitted_privileged_user_ids();
        $applicator_id   = $this->get_submitted_b2bking_applicator_group_id();
        $distributor_id  = $this->get_submitted_b2bking_distributor_group_id();
        $applicator_min  = $this->get_submitted_applicator_min_sqft();
        $distributor_min = $this->get_submitted_distributor_min_sqft();

        update_option( $this->coverages_option_name, wp_json_encode( $merged ) );

        if ( $wpforms_page_id > 0 ) {
            update_option( $this->wpforms_page_id_option_name, $wpforms_page_id );
        } else {
            delete_option( $this->wpforms_page_id_option_name );
        }

        if ( $wpforms_form_id > 0 ) {
            update_option( $this->wpforms_form_id_option_name, $wpforms_form_id );
        } else {
            delete_option( $this->wpforms_form_id_option_name );
        }

        if ( $app_form_id > 0 ) {
            update_option( $this->applicator_registration_form_id_option_name, $app_form_id );
        } else {
            delete_option( $this->applicator_registration_form_id_option_name );
        }

        if ( $dist_form_id > 0 ) {
            update_option( $this->distributor_registration_form_id_option_name, $dist_form_id );
        } else {
            delete_option( $this->distributor_registration_form_id_option_name );
        }

        if ( ! empty( $privileged_ids ) ) {
            update_option( $this->privileged_user_ids_option_name, $privileged_ids );
        } else {
            delete_option( $this->privileged_user_ids_option_name );
        }

        if ( $applicator_id > 0 ) {
            update_option( $this->b2bking_applicator_group_id_option_name, $applicator_id );
        } else {
            delete_option( $this->b2bking_applicator_group_id_option_name );
        }

        if ( $distributor_id > 0 ) {
            update_option( $this->b2bking_distributor_group_id_option_name, $distributor_id );
        } else {
            delete_option( $this->b2bking_distributor_group_id_option_name );
        }

        if ( '' !== $applicator_min ) {
            update_option( $this->applicator_min_sqft_option_name, $applicator_min );
        } else {
            delete_option( $this->applicator_min_sqft_option_name );
        }

        if ( '' !== $distributor_min ) {
            update_option( $this->distributor_min_sqft_option_name, $distributor_min );
        } else {
            delete_option( $this->distributor_min_sqft_option_name );
        }
    }

    /**
     * Return the saved coverage settings as a decoded array.
     *
     * @since    1.0.0
     * @return   array
     */
    private function get_saved_coverages_settings() {

        $raw = get_option( $this->coverages_option_name, '' );

        if ( ! is_string( $raw ) || $raw === '' ) {
            return [];
        }

        $decoded = json_decode( $raw, true );

        return is_array( $decoded ) ? $decoded : [];
    }

    /**
     * Return the saved calculator WPForms page ID.
     *
     * @since    1.0.7
     * @return   int
     */
    private function get_saved_wpforms_page_id() {

        return absint( get_option( $this->wpforms_page_id_option_name, 0 ) );
    }

    /**
     * Return the saved calculator WPForms form ID.
     *
     * @since    1.0.0
     * @return   int
     */
    private function get_saved_wpforms_form_id() {

        return absint( get_option( $this->wpforms_form_id_option_name, 0 ) );
    }

    /**
     * Return the saved applicator registration WPForms form ID.
     *
     * @since    1.0.5
     * @return   int
     */
    private function get_saved_applicator_registration_form_id() {

        return absint( get_option( $this->applicator_registration_form_id_option_name, 0 ) );
    }

    /**
     * Return the saved distributor registration WPForms form ID.
     *
     * @since    1.0.5
     * @return   int
     */
    private function get_saved_distributor_registration_form_id() {

        return absint( get_option( $this->distributor_registration_form_id_option_name, 0 ) );
    }

    /**
     * Return the saved user IDs with extended calculator privileges.
     *
     * @since    1.0.0
     * @return   array
     */
    private function get_saved_privileged_user_ids() {

        $saved_ids = get_option( $this->privileged_user_ids_option_name, [] );

        if ( ! is_array( $saved_ids ) ) {
            return [];
        }

        return array_filter( array_map( 'absint', $saved_ids ) );
    }

    /**
     * Return the saved B2BKing applicator group ID.
     *
     * @since    1.0.4
     * @return   int
     */
    private function get_saved_b2bking_applicator_group_id() {

        return absint( get_option( $this->b2bking_applicator_group_id_option_name, 0 ) );
    }

    /**
     * Return the saved B2BKing distributor group ID.
     *
     * @since    1.0.4
     * @return   int
     */
    private function get_saved_b2bking_distributor_group_id() {

        return absint( get_option( $this->b2bking_distributor_group_id_option_name, 0 ) );
    }

    /**
     * Return the saved applicator minimum project size.
     *
     * @since    1.0.4
     * @return   string
     */
    private function get_saved_applicator_min_sqft() {

        return $this->get_saved_positive_decimal_option( $this->applicator_min_sqft_option_name );
    }

    /**
     * Return the saved distributor minimum project size.
     *
     * @since    1.0.4
     * @return   string
     */
    private function get_saved_distributor_min_sqft() {

        return $this->get_saved_positive_decimal_option( $this->distributor_min_sqft_option_name );
    }

    /**
     * Read and sanitize the submitted calculator WPForms page ID.
     *
     * @since    1.0.7
     * @return   int
     */
    private function get_submitted_wpforms_page_id() {

        if ( ! isset( $_POST['ad_materials_calculator_wpforms_page_id'] ) ) {
            return $this->get_saved_wpforms_page_id();
        }

        $submitted = absint( wp_unslash( $_POST['ad_materials_calculator_wpforms_page_id'] ) );

        return $submitted > 0 ? $submitted : 0;
    }

    /**
     * Read and sanitize the submitted calculator WPForms form ID.
     *
     * @since    1.0.0
     * @return   int
     */
    private function get_submitted_wpforms_form_id() {

        if ( ! isset( $_POST['ad_materials_calculator_wpforms_form_id'] ) ) {
            return $this->get_saved_wpforms_form_id();
        }

        $submitted = absint( wp_unslash( $_POST['ad_materials_calculator_wpforms_form_id'] ) );

        return $submitted > 0 ? $submitted : 0;
    }

    /**
     * Read and sanitize the submitted applicator registration WPForms form ID.
     *
     * @since    1.0.5
     * @return   int
     */
    private function get_submitted_applicator_registration_form_id() {

        if ( ! isset( $_POST['ad_materials_calculator_applicator_registration_form_id'] ) ) {
            return $this->get_saved_applicator_registration_form_id();
        }

        $submitted = absint( wp_unslash( $_POST['ad_materials_calculator_applicator_registration_form_id'] ) );

        return $submitted > 0 ? $submitted : 0;
    }

    /**
     * Read and sanitize the submitted distributor registration WPForms form ID.
     *
     * @since    1.0.5
     * @return   int
     */
    private function get_submitted_distributor_registration_form_id() {

        if ( ! isset( $_POST['ad_materials_calculator_distributor_registration_form_id'] ) ) {
            return $this->get_saved_distributor_registration_form_id();
        }

        $submitted = absint( wp_unslash( $_POST['ad_materials_calculator_distributor_registration_form_id'] ) );

        return $submitted > 0 ? $submitted : 0;
    }

    /**
     * Read and sanitize the submitted privileged user IDs.
     *
     * @since    1.0.0
     * @return   array
     */
    private function get_submitted_privileged_user_ids() {

        if ( ! isset( $_POST['ad_materials_calculator_privileged_user_ids'] ) || ! is_array( $_POST['ad_materials_calculator_privileged_user_ids'] ) ) {
            return [];
        }

        $submitted_ids = wp_unslash( $_POST['ad_materials_calculator_privileged_user_ids'] );

        return array_filter( array_map( 'absint', $submitted_ids ) );
    }

    /**
     * Read and sanitize the submitted B2BKing applicator group ID.
     *
     * @since    1.0.4
     * @return   int
     */
    private function get_submitted_b2bking_applicator_group_id() {

        if ( ! isset( $_POST['ad_materials_calculator_b2bking_applicator_group_id'] ) ) {
            return $this->get_saved_b2bking_applicator_group_id();
        }

        return absint( wp_unslash( $_POST['ad_materials_calculator_b2bking_applicator_group_id'] ) );
    }

    /**
     * Read and sanitize the submitted B2BKing distributor group ID.
     *
     * @since    1.0.4
     * @return   int
     */
    private function get_submitted_b2bking_distributor_group_id() {

        if ( ! isset( $_POST['ad_materials_calculator_b2bking_distributor_group_id'] ) ) {
            return $this->get_saved_b2bking_distributor_group_id();
        }

        return absint( wp_unslash( $_POST['ad_materials_calculator_b2bking_distributor_group_id'] ) );
    }

    /**
     * Read and sanitize the submitted applicator minimum project size.
     *
     * @since    1.0.4
     * @return   string
     */
    private function get_submitted_applicator_min_sqft() {

        if ( ! isset( $_POST['ad_materials_calculator_applicator_min_sqft'] ) ) {
            return $this->get_saved_applicator_min_sqft();
        }

        return $this->sanitize_positive_decimal_string( wp_unslash( $_POST['ad_materials_calculator_applicator_min_sqft'] ) );
    }

    /**
     * Read and sanitize the submitted distributor minimum project size.
     *
     * @since    1.0.4
     * @return   string
     */
    private function get_submitted_distributor_min_sqft() {

        if ( ! isset( $_POST['ad_materials_calculator_distributor_min_sqft'] ) ) {
            return $this->get_saved_distributor_min_sqft();
        }

        return $this->sanitize_positive_decimal_string( wp_unslash( $_POST['ad_materials_calculator_distributor_min_sqft'] ) );
    }

    /**
     * Pick the stored or fallback values for one material row.
     *
     * @since    1.0.0
     * @param    array  $stored_values Saved option values.
     * @param    string $case_key Case key.
     * @param    string $clause_key Clause key.
     * @param    string $section Current system section.
     * @param    string $material_key Material key.
     * @param    array  $default_values Default row values.
     * @return   array
     */
    private function get_material_row_values( array $stored_values, $case_key, $clause_key, $section, $material_key, array $default_values ) {

        $stored_row = [];

        if ( isset( $stored_values[$case_key][$clause_key][$section][$material_key] ) && is_array( $stored_values[$case_key][$clause_key][$section][$material_key] ) ) {
            $stored_row = $stored_values[$case_key][$clause_key][$section][$material_key];
        }

        $row_values = [];

        for ( $i = 0; $i < 3; $i++ ) {
            if ( array_key_exists( $i, $stored_row ) ) {
                $row_values[$i] = $stored_row[$i];
            } elseif ( array_key_exists( $i, $default_values ) ) {
                $row_values[$i] = $default_values[$i];
            } else {
                $row_values[$i] = '';
            }
        }

        return $row_values;
    }

    /**
     * Build the display value for the Total column of one material row.
     *
     * The total is shown when at least one coverage field is filled.
     *
     * @since    1.0.0
     * @param    array $row_values Coverage row values.
     * @return   string
     */
    private function get_material_row_sum( array $row_values ) {

        $filled_values = [];

        foreach ( $row_values as $value ) {
            if ( $value === '' || $value === null ) {
                continue;
            }

            $filled_values[] = (float) $value;
        }

        if ( empty( $filled_values ) ) {
            return '';
        }

        return number_format( array_sum( $filled_values ), 3, '.', '' );
    }

    /**
     * Sanitize the nested coverage settings structure before saving.
     *
     * @since    1.0.0
     * @param    array $submitted Submitted values.
     * @param    array  $schema Settings schema.
     * @param    string $section Current system section.
     * @return   array
     */
    private function sanitize_coverages_settings( array $submitted, array $schema, $section ) {

        $sanitized = [];

        foreach ( $schema as $case_key => $case_config ) {
            foreach ( $case_config['clauses'] as $clause_key => $clause_config ) {
                $sanitized[$case_key][$clause_key][$section] = [];

                foreach ( $clause_config['materials'] as $material_key => $material_config ) {
                    $material_values = isset( $submitted[$case_key][$clause_key][$section][$material_key] ) && is_array( $submitted[$case_key][$clause_key][$section][$material_key] )
                    ? $submitted[$case_key][$clause_key][$section][$material_key]
                    : [];

                    $sanitized_row = [];

                    for ( $i = 0; $i < 3; $i++ ) {
                        $value = isset( $material_values[$i] ) ? trim( (string) $material_values[$i] ) : '';

                        if ( $value === '' ) {
                            $sanitized_row[] = '';
                            continue;
                        }

                        $sanitized_row[] = $this->sanitize_decimal_string( $value );
                    }

                    $sanitized[$case_key][$clause_key][$section][$material_key] = $sanitized_row;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Normalize a decimal string to a simple 3-decimal format.
     *
     * @since    1.0.0
     * @param    string $value Raw value.
     * @return   string
     */
    private function sanitize_decimal_string( $value ) {

        $normalized = str_replace( ',', '.', $value );
        $number     = (float) $normalized;

        return number_format( $number, 3, '.', '' );
    }

    /**
     * Return a saved positive decimal option.
     *
     * @since    1.0.4
     * @param    string $option_name Option name.
     * @return   string
     */
    private function get_saved_positive_decimal_option( $option_name ) {

        $value = get_option( $option_name, '' );

        return $this->sanitize_positive_decimal_string( $value );
    }

    /**
     * Sanitize a positive decimal setting.
     *
     * @since    1.0.4
     * @param    mixed $value Raw value.
     * @return   string
     */
    private function sanitize_positive_decimal_string( $value ) {

        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $value = trim( str_replace( ',', '.', (string) $value ) );

        if ( '' === $value || ! is_numeric( $value ) ) {
            return '';
        }

        $number = (float) $value;

        if ( $number <= 0 ) {
            return '';
        }

        return rtrim( rtrim( number_format( $number, 3, '.', '' ), '0' ), '.' );
    }

    /**
     * Get the currently active system section.
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_active_system_section() {

        $section = '';

        if ( isset( $_POST['ad_materials_calculator_active_section'] ) ) {
            $section = sanitize_key( wp_unslash( $_POST['ad_materials_calculator_active_section'] ) );
        } elseif ( isset( $_GET['section'] ) ) {
            $section = sanitize_key( wp_unslash( $_GET['section'] ) );
        }

        if ( ! in_array( $section, ['medium', 'fine', 'microterrazzo'], true ) ) {
            $section = 'medium';
        }

        return $section;
    }

    /**
     * Render the system sub-navigation using WooCommerce's native section style.
     *
     * @since    1.0.0
     * @param    string $active_section Active section slug.
     */
    private function render_system_sections_nav( $active_section ) {

        $sections = [
            'medium'        => __( 'Medium', 'ad-materials-calculator' ),
            'fine'          => __( 'Fine', 'ad-materials-calculator' ),
            'microterrazzo' => __( 'Microterrazzo', 'ad-materials-calculator' ),
        ];

        echo '<ul class="subsubsub ad-mc-settings-sections">';

        $section_links = [];

        foreach ( $sections as $section_key => $section_label ) {
            $url             = admin_url( 'admin.php?page=wc-settings&tab=materials-calculator&section=' . $section_key );
            $current_class   = $active_section === $section_key ? 'current' : '';
            $section_links[] = sprintf(
                '<li><a href="%1$s" class="%2$s">%3$s</a></li>',
                esc_url( $url ),
                esc_attr( $current_class ),
                esc_html( $section_label )
            );
        }

        echo implode( ' | ', $section_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</ul>';
        echo '<br class="clear" />';
    }

    /**
     * Merge saved section values back into the full JSON structure.
     *
     * @since    1.0.0
     * @param    array $existing Previously saved values.
     * @param    array $updates Current section updates.
     * @return   array
     */
    private function merge_coverages_settings( array $existing, array $updates ) {

        foreach ( $updates as $case_key => $case_values ) {
            if ( ! isset( $existing[$case_key] ) || ! is_array( $existing[$case_key] ) ) {
                $existing[$case_key] = [];
            }

            foreach ( $case_values as $clause_key => $clause_values ) {
                if ( ! isset( $existing[$case_key][$clause_key] ) || ! is_array( $existing[$case_key][$clause_key] ) ) {
                    $existing[$case_key][$clause_key] = [];
                }

                foreach ( $clause_values as $section_key => $section_values ) {
                    if ( ! isset( $existing[$case_key][$clause_key][$section_key] ) || ! is_array( $existing[$case_key][$clause_key][$section_key] ) ) {
                        $existing[$case_key][$clause_key][$section_key] = [];
                    }

                    foreach ( $section_values as $material_key => $material_values ) {
                        $existing[$case_key][$clause_key][$section_key][$material_key] = $material_values;
                    }
                }
            }
        }

        return $existing;
    }

}