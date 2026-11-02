<?php
/** Walls and ceilings preset */

ad_materials_calc_register_area_preset( 'wall_ceiling', function () {

    return [
        'dimensions_note'      => __( 'We automatically calculate all four walls. Rough measurements to the nearest foot are fine.', 'ad-materials-calculator' ),
        'ceiling_note'         => __( 'Uncheck if the ceiling will remain as-is.', 'ad-materials-calculator' ),
        'measurement_note'     => __( 'Enter only the walls you plan to finish. Standard doors and windows do not need to be deducted. For large openings or full glass walls, enter the surrounding walls individually.', 'ad-materials-calculator' ),
        'rooms_note'           => __( 'Planning more than one room? Add each space and we\'ll calculate one combined project kit.', 'ad-materials-calculator' ),
        'multiroom_note'       => __( 'All rooms use the selections below. For a different finish or color, build a separate kit.', 'ad-materials-calculator' ),
        'known_area_note'      => __( 'Enter the combined area you plan to finish. If you are leaving the ceiling as-is, enter only the walls. Enter the measured area only - we apply the material allowance automatically.', 'ad-materials-calculator' ),
        'sheen_copy'           => ['Matte' => __( 'Quiet, low reflection', 'ad-materials-calculator' ), 'Satin' => __( 'Softly reflective', 'ad-materials-calculator' ), 'Semi-Gloss' => __( 'More polished', 'ad-materials-calculator' )],
        'dimension_mode_label' => __( 'Use room dimensions', 'ad-materials-calculator' ),
        'heading'              => __( 'Price your complete wall & ceiling system', 'ad-materials-calculator' ),
        'known_mode_label'     => __( 'I know my wall + ceiling sq ft', 'ad-materials-calculator' ),
        'title'                => __( 'Your wall & ceiling kit', 'ad-materials-calculator' ), 'steps' => 5, 'route' => 'wall', 'walls_layout' => true,
        'sheens'               => ['Matte', 'Satin', 'Semi-Gloss'], 'default_sheen'                 => 'Matte',
        'standard_copy'        => __( 'Recommended for ceilings and most residential walls.', 'ad-materials-calculator' ),
        'maximum_copy'         => __( 'Reinforced system for corridors, hospitality, retail and high-contact walls.', 'ad-materials-calculator' ),
        'finishes'             => [['value' => 'Terrazzo Granitech Gaia', 'label' => __( 'Gaia', 'ad-materials-calculator' ), 'description' => __( 'Brush- or trowel-applied limewash alternative - any interior wall; bathroom-ready with the right sealers', 'ad-materials-calculator' )]],
        'surfaces'             => [
            'room' => [
                'summary_label'                 => __( 'Room walls + ceiling', 'ad-materials-calculator' ),
                'summary_label_without_ceiling' => __( 'Room walls', 'ad-materials-calculator' ),
                'label'                         => __( 'Walls + ceiling', 'ad-materials-calculator' ), 'checked' => true, 'ceiling' => true,
                'fields'                        => [ad_materials_calc_preset_field( 'length', __( 'Room length', 'ad-materials-calculator' ), 12 ), ad_materials_calc_preset_field( 'width', __( 'Room width', 'ad-materials-calculator' ), 10 ), ad_materials_calc_preset_field( 'height', __( 'Wall height', 'ad-materials-calculator' ), 9 )],
                'terms'                         => [[2, 'length', 'height'], [2, 'width', 'height']],
            ],
        ],
    ];
} );

function ad_materials_calc_build_wall_ceiling_result( array $payload ) {

    return ad_materials_calc_build_preset_result( 'wall_ceiling', $payload );
}