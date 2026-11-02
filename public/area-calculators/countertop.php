<?php
/**
 * Countertop, vanity and table preset
 *
 * @package Ad_Materials_Calculator
 */

ad_materials_calc_register_area_preset( 'countertop', function () {

    return [
        'dimensions_note'      => __( 'Price it piece by piece - the counter, the vanity, the table. Check what you\'re making; the rest stays priced out.', 'ad-materials-calculator' ),
        'known_area_note'      => __( 'Enter the combined area you plan to finish. We apply the material allowance automatically.', 'ad-materials-calculator' ),
        'sheen_note'           => __( 'Choosing Matte? Every matte build starts with Armourcoat Satin as its base coat - the satin base is still in your kit, so you can keep a satin sheen if you change your mind.', 'ad-materials-calculator' ),
        'protection_note'      => __( 'The level sets the thickness of the build: Maximum simply adds more material and sealer to your kit for harder-working surfaces. Nothing else changes.', 'ad-materials-calculator' ),
        'heading'              => __( 'Price your countertop, vanity or table', 'ad-materials-calculator' ),
        'known_mode_label'     => __( 'I know my total sq ft', 'ad-materials-calculator' ),
        'title'                => __( 'Your surface kit', 'ad-materials-calculator' ), 'project_label'     => __( 'Countertops', 'ad-materials-calculator' ),
        'steps'                => 5, 'route'                                                             => 'counter',
        'route_labels'         => ['counter' => __( 'Countertops and special surfaces', 'ad-materials-calculator' )],
        'dimension_mode_label' => __( 'Use surface dimensions', 'ad-materials-calculator' ), 'known_label' => __( 'Total surface area', 'ad-materials-calculator' ),
        'sheens'               => ['Matte', 'Satin'], 'default_sheen'                                    => 'Satin',
        'sheen_copy'           => ['Matte' => __( 'Quiet, low reflection - over a satin base', 'ad-materials-calculator' ), 'Satin' => __( 'Softly reflective - the countertop standard', 'ad-materials-calculator' )],
        'standard_copy'        => __( 'For home counters, vanities and tables - daily use, daily cleaning.', 'ad-materials-calculator' ),
        'maximum_copy'         => __( 'For bars, restaurants and retail counters - more build material and sealer for a thicker armor.', 'ad-materials-calculator' ),
        'mesh_option'          => true,
        'mesh_copy'            => __( 'Include fiberglass mesh - needed over tile; smooth concrete, marble, or porcelain tops can skip it', 'ad-materials-calculator' ),
        'surfaces'             => [
            'counter' => [
                'summary_label' => __( 'Countertop', 'ad-materials-calculator' ),
                'note'          => __( 'Measure along the front edge - standard counters run about 24 inches deep.', 'ad-materials-calculator' ),
                'label'         => __( 'Countertop', 'ad-materials-calculator' ), 'checked' => true,
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Total run length', 'ad-materials-calculator' ), 18 ), ad_materials_calc_preset_field( 'depth', __( 'Depth', 'ad-materials-calculator' ), 24, 'in' )],
                'terms'         => [[1 / 12, 'length', 'depth']],
                'extra'         => [
                    'summary_label' => __( 'Island / second run', 'ad-materials-calculator' ),
                    'label'         => __( '+ Add an island or second run', 'ad-materials-calculator' ),
                    'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Island length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'depth', __( 'Island width', 'ad-materials-calculator' ) )],
                    'terms'         => [[1, 'length', 'depth']],
                ],
            ],
            'vanity'  => [
                'summary_label' => __( 'Vanity top', 'ad-materials-calculator' ),
                'note'          => __( 'Wet-area sealed - basin surrounds and splash included in the same surface.', 'ad-materials-calculator' ),
                'label'         => __( 'Vanity top', 'ad-materials-calculator' ), 'checked' => false,
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Width', 'ad-materials-calculator' ), 48, 'in' ), ad_materials_calc_preset_field( 'depth', __( 'Depth', 'ad-materials-calculator' ), 22, 'in' )],
                'terms'         => [[1 / 144, 'length', 'depth']],
                'extra'         => [
                    'summary_label' => __( 'Second vanity', 'ad-materials-calculator' ),
                    'label'         => __( '+ Add a second vanity', 'ad-materials-calculator' ),
                    'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Second vanity width', 'ad-materials-calculator' ), '', 'in' ), ad_materials_calc_preset_field( 'depth', __( 'Second vanity depth', 'ad-materials-calculator' ), '', 'in' )],
                    'terms'         => [[1 / 144, 'length', 'depth']],
                ],
            ],
            'table'   => [
                'summary_label' => __( 'Table / special surface', 'ad-materials-calculator' ),
                'note'          => __( 'Dining tables, desks, retail counters, furniture - wood and other stable substrates take the same system.', 'ad-materials-calculator' ),
                'label'         => __( 'Table or special surface', 'ad-materials-calculator' ), 'checked' => false,
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 72, 'in' ), ad_materials_calc_preset_field( 'depth', __( 'Width', 'ad-materials-calculator' ), 40, 'in' )],
                'terms'         => [[1 / 144, 'length', 'depth']],
                'extra'         => [
                    'summary_label' => __( 'Additional piece', 'ad-materials-calculator' ),
                    'label'         => __( '+ Add another piece', 'ad-materials-calculator' ),
                    'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Second piece length', 'ad-materials-calculator' ), '', 'in' ), ad_materials_calc_preset_field( 'depth', __( 'Second piece width', 'ad-materials-calculator' ), '', 'in' )],
                    'terms'         => [[1 / 144, 'length', 'depth']],
                ],
            ],
        ],
    ];
} );

function ad_materials_calc_build_countertop_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'countertop', $payload );
}