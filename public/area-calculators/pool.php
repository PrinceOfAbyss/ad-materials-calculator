<?php
/** Pool deck and submerged interior preset */

ad_materials_calc_register_area_preset( 'pool', function () {

    return [
        'dimensions_notes'     => ['deck' => __( 'Measure the paved surround - we subtract the water so you only buy what you coat.', 'ad-materials-calculator' ), 'pool' => __( 'Walls and floor beneath the water - calculated from length, width and average depth.', 'ad-materials-calculator' )],
        'known_area_note'      => __( 'Enter the combined area you plan to finish. We apply the material allowance automatically.', 'ad-materials-calculator' ),
        'protection_note'      => __( 'The level sets the thickness of the build: Maximum simply adds more material and sealer to your kit for harder-working surfaces. Nothing else changes.', 'ad-materials-calculator' ),
        'location_copy'        => ['deck' => __( 'The surround - sun deck, coping, barefoot paths. Always matte with the Anti-Slip R-11 topcoat.', 'ad-materials-calculator' ), 'pool' => __( 'The vessel - walls and floor beneath the water, one seamless surface sealed for permanent immersion.', 'ad-materials-calculator' )],
        'dimension_mode_label' => __( 'Use pool dimensions', 'ad-materials-calculator' ),
        'heading'              => __( 'Price your complete pool system', 'ad-materials-calculator' ),
        'known_mode_label'     => __( 'I know my total sq ft', 'ad-materials-calculator' ),
        'title'                => __( 'Your pool kit', 'ad-materials-calculator' ), 'steps' => 4, 'route' => 'exterior',
        'locations'            => ['deck' => __( 'Pool deck', 'ad-materials-calculator' ), 'pool' => __( 'Pool interior', 'ad-materials-calculator' )],
        'sheens'               => ['Matte'], 'default_sheen'                              => 'Matte',
        'standard_copy'        => __( 'For private pools - family use, weather and seasons included.', 'ad-materials-calculator' ),
        'maximum_copy'         => __( 'For resorts, hotels and busy pools - heavy bather loads, more build and sealer for a thicker armor.', 'ad-materials-calculator' ),
        'finishes'             => [['value' => 'Terrazzo Granitech Monolith', 'label' => __( 'Monolith', 'ad-materials-calculator' ), 'description' => __( 'The exterior-grade build - one dense, monolithic body engineered for sun, water and weather', 'ad-materials-calculator' )]],
        'surfaces'             => [
            'deck'     => [
                'summary_label' => __( 'Pool deck', 'ad-materials-calculator' ),
                'note'          => __( 'The overall paved area, pool included - the longest length and width, rough feet are fine.', 'ad-materials-calculator' ),
                'label'         => __( 'Deck footprint', 'ad-materials-calculator' ), 'checked' => true, 'location' => 'deck',
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 40 ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 25 )],
                'terms'         => [[1, 'length', 'width']],
            ],
            'subtract' => [
                'note'   => __( 'We remove the water\'s footprint from the deck math - freeform pools measure fine as rectangles.', 'ad-materials-calculator' ),
                'label'  => __( 'Subtract the pool', 'ad-materials-calculator' ), 'checked' => true, 'location' => 'deck',
                'fields' => [ad_materials_calc_preset_field( 'length', __( 'Pool length', 'ad-materials-calculator' ), 30 ), ad_materials_calc_preset_field( 'width', __( 'Pool width', 'ad-materials-calculator' ), 15 )],
                'terms'  => [[1, 'length', 'width']],
            ],
            'pool'     => [
                'summary_label' => __( 'Pool interior', 'ad-materials-calculator' ),
                'note'          => __( 'Floor plus all four walls at average depth - shallow-to-deep slopes average out on their own.', 'ad-materials-calculator' ),
                'label'         => __( 'Pool interior - walls + floor', 'ad-materials-calculator' ), 'checked' => true, 'location' => 'pool',
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 30 ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 15 ), ad_materials_calc_preset_field( 'depth', __( 'Average depth', 'ad-materials-calculator' ), 5 )],
                'terms'         => [[1, 'length', 'width'], [2, 'length', 'depth'], [2, 'width', 'depth']],
            ],
        ],
    ];
} );

function ad_materials_calc_build_pool_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'pool', $payload );
}