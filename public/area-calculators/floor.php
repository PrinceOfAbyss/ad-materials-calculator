<?php
/** Interior and exterior floor preset */

ad_materials_calc_register_area_preset( 'floor', function () {

    return [
        'dimensions_note'             => __( 'Measure the longest length and width of each space - rough feet are fine. We calculate the rest.', 'ad-materials-calculator' ),
        'known_area_note'             => __( 'Enter the combined area you plan to finish. We apply the material allowance automatically.', 'ad-materials-calculator' ),
        'sheen_note'                  => __( 'Choosing Matte? Every matte build starts with Armourcoat Satin as its base coat - the satin base is still in your kit, so you can keep a satin sheen if you change your mind.', 'ad-materials-calculator' ),
        'protection_note'             => __( 'The level sets the thickness of the build: Maximum simply adds more material and sealer to your kit for harder-working surfaces. Nothing else changes.', 'ad-materials-calculator' ),
        'exterior_note'               => __( 'Exterior floors finish matte, always - sealed and topped with the certified Anti-Slip R-11 coat for sure footing in the open air.', 'ad-materials-calculator' ),
        'micromosaic_protection_note' => __( 'Sheen is always matte - sealed and finished with the certified Anti-Slip R-11 topcoat.', 'ad-materials-calculator' ),
        'location_copy'               => ['interior' => __( 'Living rooms, kitchens, hallways, bathrooms - any interior room, in matte or satin.', 'ad-materials-calculator' ), 'exterior' => __( 'Terraces, balconies, patios and pool surrounds - always finished matte with the Anti-Slip R-11 topcoat.', 'ad-materials-calculator' )],
        'sheen_copy'                  => ['Matte' => __( 'Quiet, low reflection - over a satin base', 'ad-materials-calculator' ), 'Satin' => __( 'Softly reflective - the interior standard', 'ad-materials-calculator' )],
        'dimension_mode_label'        => __( 'Use floor dimensions', 'ad-materials-calculator' ),
        'heading'                     => __( 'Price your complete floor system', 'ad-materials-calculator' ),
        'known_mode_label'            => __( 'I know my total sq ft', 'ad-materials-calculator' ),
        'title'                       => __( 'Your floor kit', 'ad-materials-calculator' ), 'steps' => 5, 'route' => 'floor',
        'locations'                   => ['interior' => __( 'Interior', 'ad-materials-calculator' ), 'exterior' => __( 'Exterior', 'ad-materials-calculator' )],
        'sheens'                      => ['Matte', 'Satin'], 'default_sheen'                      => 'Satin',
        'standard_copy'               => __( 'For residential living - bedrooms, living rooms, kitchens and everyday foot traffic.', 'ad-materials-calculator' ),
        'maximum_copy'                => __( 'For hard-working floors - retail, restaurants, offices and garages. More build material and sealer for a thicker armor.', 'ad-materials-calculator' ),
        'finishes'                    => [
            ['value' => 'Terrazzo Granitech Micromosaic', 'label' => __( 'Micromosaic', 'ad-materials-calculator' ), 'description' => __( 'Finely scattered mosaic aggregate - a delicate terrazzo field underfoot', 'ad-materials-calculator' )],
            ['value' => 'Terrazzo Granitech Monolith', 'label' => __( 'Monolith', 'ad-materials-calculator' ), 'description' => __( 'The exterior-grade build - one dense, monolithic body engineered for open air and weather', 'ad-materials-calculator' )],
        ],
        'surfaces'                    => [
            'main'      => [
                'summary_label'              => __( 'Floor', 'ad-materials-calculator' ),
                'summary_labels_by_location' => ['interior' => __( 'Interior floor', 'ad-materials-calculator' ), 'exterior' => __( 'Exterior floor', 'ad-materials-calculator' )],
                'note'                       => __( 'The largest rectangle of the space - an open-plan room, a terrace, a garage bay.', 'ad-materials-calculator' ),
                'label'                      => __( 'Main floor area', 'ad-materials-calculator' ), 'checked' => true,
                'fields'                     => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ), 15 ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 12 )],
                'terms'                      => [[1, 'length', 'width']],
            ],
            'connected' => [
                'summary_label' => __( 'Connected floor area', 'ad-materials-calculator' ),
                'note'          => __( 'Run the same floor through connecting spaces - one continuous plane, no thresholds, no transitions.', 'ad-materials-calculator' ),
                'label'         => __( 'Connected area - hallway, kitchen, landing', 'ad-materials-calculator' ), 'checked' => false,
                'fields'        => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ) )],
                'terms'         => [[1, 'length', 'width']],
            ],
        ],
    ];
} );

function ad_materials_calc_build_floor_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'floor', $payload );
}