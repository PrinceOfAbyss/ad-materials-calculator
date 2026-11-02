<?php
/** Balcony and terrace preset */

ad_materials_calc_register_area_preset( 'balcony', function () {

    return [
        'dimensions_note'      => __( 'Measure the longest length and width of each space - rough feet are fine. We calculate the rest.', 'ad-materials-calculator' ),
        'known_area_note'      => __( 'Enter the combined area you plan to finish. We apply the material allowance automatically.', 'ad-materials-calculator' ),
        'protection_note'      => __( 'Every balcony finishes matte - sealed, then closed with the certified Anti-Slip R-11 coat: sure footing in rain, sun and open air. Maximum simply adds one further coat of sealer - a deeper shield for harder-working terraces.', 'ad-materials-calculator' ),
        'dimension_mode_label' => __( 'Use balcony dimensions', 'ad-materials-calculator' ),
        'heading'              => __( 'Price your complete balcony system', 'ad-materials-calculator' ),
        'known_mode_label'     => __( 'I know my total sq ft', 'ad-materials-calculator' ),
        'title'                => __( 'Your balcony kit', 'ad-materials-calculator' ), 'steps' => 4, 'route' => 'exterior',
        'sheens'               => ['Matte'], 'default_sheen'                                 => 'Matte',
        'standard_copy'        => __( 'For private balconies and terraces - everyday use, weather included.', 'ad-materials-calculator' ),
        'maximum_copy'         => __( 'For rooftops, shared terraces and commercial decks - one further coat of sealer, a deeper shield.', 'ad-materials-calculator' ),
        'finishes'             => [
            ['value' => 'Terrazzo Granitech Micromosaic', 'label' => __( 'Micromosaic', 'ad-materials-calculator' ), 'description' => __( 'Finely scattered mosaic aggregate - a delicate terrazzo field underfoot, tintable in any of our colors', 'ad-materials-calculator' )],
            ['value' => 'Terrazzo Granitech Monolith', 'label' => __( 'Monolith', 'ad-materials-calculator' ), 'description' => __( 'The exterior-grade build - one dense, monolithic body engineered for open air and weather', 'ad-materials-calculator' )],
        ],
        'surfaces'             => [
            'main'      => [
                'summary_label' => __( 'Balcony', 'ad-materials-calculator' ),
                'note'          => __( 'The full slab, wall to rail.', 'ad-materials-calculator' ),
                'label'         => __( 'Balcony or terrace', 'ad-materials-calculator' ), 'checked' => true,
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 12 ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 6 )],
                'terms'         => [[1, 'length', 'width']],
            ],
            'connected' => [
                'summary_label' => __( 'Second balcony / connected terrace', 'ad-materials-calculator' ),
                'note'          => __( 'Doing both balconies, or a terrace that turns a corner? One kit, one continuous surface.', 'ad-materials-calculator' ),
                'label'         => __( 'Second balcony or connected terrace', 'ad-materials-calculator' ), 'checked' => false,
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ) )],
                'terms'         => [[1, 'length', 'width']],
            ],
        ],
    ];
} );

function ad_materials_calc_build_balcony_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'balcony', $payload );
}