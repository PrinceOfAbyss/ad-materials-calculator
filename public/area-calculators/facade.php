<?php
/** Facade preset */

ad_materials_calc_register_area_preset( 'facade', function () {

    return [
        'dimensions_note'      => __( 'Rough measurements to the nearest foot are fine. Gable ends? Use the average height - eave height plus half the gable rise.', 'ad-materials-calculator' ),
        'individual_note'      => __( 'Check the walls you\'re finishing - each with its own width and height. The rest stay priced out.', 'ad-materials-calculator' ),
        'measurement_note'     => __( 'Enter only the elevations you plan to finish. Standard doors and windows do not need to be deducted. For large openings or glass frontages, enter the surrounding walls individually.', 'ad-materials-calculator' ),
        'known_area_note'      => __( 'Enter the combined area you plan to finish. If you are leaving the ceiling as-is, enter only the walls. Enter the measured area only - we apply the material allowance automatically.', 'ad-materials-calculator' ),
        'layout_copy'          => ['standard' => __( 'All four elevations from building length, width and height - the fastest path.', 'ad-materials-calculator' ), 'individual' => __( 'Only the elevations you\'re finishing - a front, a storefront, a garden wall - each with its own width and height.', 'ad-materials-calculator' )],
        'sheen_copy'           => ['Matte' => __( 'The facade standard - quiet, mineral, low reflection', 'ad-materials-calculator' ), 'Satin' => __( 'Softly reflective - sheds rain and dust', 'ad-materials-calculator' )],
        'dimension_mode_label' => __( 'Use building dimensions', 'ad-materials-calculator' ),
        'heading'              => __( 'Price your complete facade system', 'ad-materials-calculator' ),
        'known_mode_label'     => __( 'I know my facade sq ft', 'ad-materials-calculator' ),
        'title'                => __( 'Your facade kit', 'ad-materials-calculator' ), 'steps' => 5, 'route' => 'facade', 'walls_layout' => true, 'mesh_option' => true,
        'sheens'               => ['Matte', 'Satin'], 'default_sheen'                       => 'Matte',
        'standard_copy'        => __( 'For residential facades - sun, wind, rain and the seasons.', 'ad-materials-calculator' ),
        'maximum_copy'         => __( 'For coastal exposure, parapets and commercial frontages - more build and sealer for a thicker armor.', 'ad-materials-calculator' ),
        'surfaces'             => [
            'room' => [
                'summary_label'  => __( 'Facade', 'ad-materials-calculator' ),
                'summary_suffix' => __( 'four elevations', 'ad-materials-calculator' ),
                'label'          => __( 'Whole building - four elevations', 'ad-materials-calculator' ), 'checked' => true,
                'fields'         => [ad_materials_calc_preset_field( 'length', __( 'Building length', 'ad-materials-calculator' ), 40 ), ad_materials_calc_preset_field( 'width', __( 'Building width', 'ad-materials-calculator' ), 30 ), ad_materials_calc_preset_field( 'height', __( 'Wall height to eave', 'ad-materials-calculator' ), 10 )],
                'terms'          => [[2, 'length', 'height'], [2, 'width', 'height']],
            ],
        ],
    ];
} );

function ad_materials_calc_build_facade_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'facade', $payload );
}