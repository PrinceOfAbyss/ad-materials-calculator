<?php

/**
 * Shared coverage schema and label helpers.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 */

class Ad_Materials_Calculator_Coverage_Schema {

    /**
     * Build the full coverage settings schema.
     *
     * @return array
     */
    public static function get_coverages_settings_schema() {

        return [
            'case_1'  => [
                'title'       => 'Case 1. Interior / Exterior Walls',
                'description' => 'Grouped by substrate path, sheen, and duty level.',
                'sections'    => ['medium', 'fine', 'microterrazzo'],
                'clauses'     => [
                    'no_fibermesh_matte_standard'       => self::build_clause_schema( 'No fibermesh / Matte / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (X MATTE WALL Varnish)', ['', '', ''] ),
                    ] ),
                    'no_fibermesh_matte_max'            => self::build_clause_schema( 'No fibermesh / Matte / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (X MATTE WALL Varnish)', ['', '', ''] ),
                    ] ),
                    'no_fibermesh_semigloss_standard'   => self::build_clause_schema( 'No fibermesh / Semi-Gloss / Standard Duty', [
                        'primer'           => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_semigloss' => self::build_material_schema( 'Sealer (Monopro Semi-gloss Wall Sealer)', ['', '', ''] ),
                    ] ),
                    'no_fibermesh_semigloss_max'        => self::build_clause_schema( 'No fibermesh / Semi-Gloss / Max Duty', [
                        'primer'           => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_semigloss' => self::build_material_schema( 'Sealer (Monopro Semi-gloss Wall Sealer)', ['', '', ''] ),
                    ] ),
                    'no_fibermesh_satin_standard'       => self::build_clause_schema( 'No fibermesh / Satin / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'no_fibermesh_satin_max'            => self::build_clause_schema( 'No fibermesh / Satin / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'with_fibermesh_matte_standard'     => self::build_clause_schema( 'With fibermesh / Matte / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (X MATTE WALL Varnish)', ['', '', ''] ),
                    ] ),
                    'with_fibermesh_matte_max'          => self::build_clause_schema( 'With fibermesh / Matte / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (X MATTE WALL Varnish)', ['', '', ''] ),
                    ] ),
                    'with_fibermesh_semigloss_standard' => self::build_clause_schema( 'With fibermesh / Semi-Gloss / Standard Duty', [
                        'primer'           => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_semigloss' => self::build_material_schema( 'Sealer (Monopro Semi-gloss Wall Sealer)', ['', '', ''] ),
                    ] ),
                    'with_fibermesh_semigloss_max'      => self::build_clause_schema( 'With fibermesh / Semi-Gloss / Max Duty', [
                        'primer'           => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_semigloss' => self::build_material_schema( 'Sealer (Monopro Semi-gloss Wall Sealer)', ['', '', ''] ),
                    ] ),
                    'with_fibermesh_satin_standard'     => self::build_clause_schema( 'With fibermesh / Satin / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'with_fibermesh_satin_max'          => self::build_clause_schema( 'With fibermesh / Satin / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_2'  => [
                'title'       => 'Case 2. Wet Area Walls',
                'description' => 'Grouped by sheen and duty level.',
                'sections'    => ['medium', 'fine', 'microterrazzo'],
                'clauses'     => [
                    'matte_dual_standard'   => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_dual_max'        => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_single_standard' => self::build_clause_schema( 'Matte / Single-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'satin_minimal'         => self::build_clause_schema( 'Satin / Single-Layer / Minimal Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_standard'        => self::build_clause_schema( 'Satin / Single-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'             => self::build_clause_schema( 'Satin / Single-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_3'  => [
                'title'       => 'Case 3. Interior Floor',
                'description' => 'Grouped by moisture path, sheen, and duty level.',
                'sections'    => ['medium', 'fine', 'microterrazzo'],
                'clauses'     => [
                    'wet_matte_standard' => self::build_clause_schema( 'Wet Area / Matte / Dual-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'wet_matte_max'      => self::build_clause_schema( 'Wet Area / Matte / Dual-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'dry_matte_standard' => self::build_clause_schema( 'Dry Area / Matte / Dual-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'dry_matte_max'      => self::build_clause_schema( 'Dry Area / Matte / Dual-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'dry_satin_standard' => self::build_clause_schema( 'Dry Area / Satin / Single-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'dry_satin_max'      => self::build_clause_schema( 'Dry Area / Satin / Single-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_4'  => [
                'title'       => 'Case 4. Exterior Floor (incl. Pool Decks)',
                'description' => 'Grouped by usage level.',
                'sections'    => ['medium', 'fine', 'microterrazzo'],
                'clauses'     => [
                    'standard' => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair' => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'system_medium'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'      => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'      => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'      => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'max'      => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair' => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'system_medium'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'      => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'      => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'      => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_5'  => [
                'title'       => 'Case 5. Pool Interior',
                'description' => 'Single fixed clause, included here so the editor covers the full project.',
                'sections'    => ['medium', 'fine', 'microterrazzo'],
                'clauses'     => [
                    'pool_interior_fixed' => self::build_clause_schema( 'Pool Interior fixed clause', [
                        'epoxy_primer_pair' => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'system_medium'     => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'      => self::build_material_schema( 'System', ['', '', ''] ),
                        'pool_sealer_pair'  => self::build_material_schema( 'Sealer (Pool Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_6'  => [
                'title'       => 'Case 6. Countertop / Vanity',
                'description' => 'Grouped by sheen and duty level.',
                'sections'    => ['medium', 'fine', 'microterrazzo'],
                'clauses'     => [
                    'matte_standard' => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_max'      => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'    => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'satin_standard' => self::build_clause_schema( 'Satin / Single-Layer / Standard Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'      => self::build_clause_schema( 'Satin / Single-Layer / Max Duty', [
                        'primer'          => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_medium'   => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_fine_top' => self::build_material_schema( 'System', ['', '', ''] ),
                        'system_micro'    => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin'    => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_7'  => [
                'title'       => 'Case 7. Interior / Pre-Painted Drywall',
                'description' => 'Grouped by sheen and duty level.',
                'sections'    => ['gaia'],
                'clauses'     => [
                    'matte_standard'     => self::build_clause_schema( 'Matte / Standard Duty', [
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte' => self::build_material_schema( 'Sealer (X MATTE WALL Varnish)', ['', '', ''] ),
                    ] ),
                    'matte_max'          => self::build_clause_schema( 'Matte / Max Duty', [
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte' => self::build_material_schema( 'Sealer (X MATTE WALL Varnish)', ['', '', ''] ),
                    ] ),
                    'semigloss_standard' => self::build_clause_schema( 'Semi-Gloss / Standard Duty', [
                        'system_gaia'      => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_semigloss' => self::build_material_schema( 'Sealer (Monopro Semi-gloss Wall Sealer)', ['', '', ''] ),
                    ] ),
                    'semigloss_max'      => self::build_clause_schema( 'Semi-Gloss / Max Duty', [
                        'system_gaia'      => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_semigloss' => self::build_material_schema( 'Sealer (Monopro Semi-gloss Wall Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_standard'     => self::build_clause_schema( 'Satin / Standard Duty', [
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'          => self::build_clause_schema( 'Satin / Max Duty', [
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_8'  => [
                'title'       => 'Case 8. Wet Areas & Exterior Walls',
                'description' => 'Grouped by sheen and duty level.',
                'sections'    => ['gaia'],
                'clauses'     => [
                    'matte_dual_standard'   => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'primer'       => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte' => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_dual_max'        => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'primer'       => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte' => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_single_standard' => self::build_clause_schema( 'Matte / Single-Layer / Standard Duty', [
                        'primer'       => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_matte' => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'satin_minimal'         => self::build_clause_schema( 'Satin / Single-Layer / Minimal Duty', [
                        'primer'       => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_standard'        => self::build_clause_schema( 'Satin / Single-Layer / Standard Duty', [
                        'primer'       => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'             => self::build_clause_schema( 'Satin / Single-Layer / Max Duty', [
                        'primer'       => self::build_material_schema( 'Primer (Eco Grip Quartz Primer)', ['', '', ''] ),
                        'system_gaia'  => self::build_material_schema( 'System', ['', '', ''] ),
                        'sealer_satin' => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_9'  => [
                'title'       => 'Case 9. Exterior Floor / Foot Traffic (Walkways, Patios)',
                'description' => 'Grouped by optional sealer protection and sheen.',
                'sections'    => ['monolith'],
                'clauses'     => [
                    'no_sealer'      => self::build_clause_schema( 'No Sealer', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                    ] ),
                    'matte_standard' => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_max'      => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'satin_standard' => self::build_clause_schema( 'Satin / Single-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'      => self::build_clause_schema( 'Satin / Single-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_10' => [
                'title'       => 'Case 10. Exterior Floor / Car Traffic (Driveways)',
                'description' => 'Grouped by optional sealer protection and sheen.',
                'sections'    => ['monolith'],
                'clauses'     => [
                    'no_sealer'      => self::build_clause_schema( 'No Sealer', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                    ] ),
                    'matte_standard' => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_max'      => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'satin_standard' => self::build_clause_schema( 'Satin / Single-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'      => self::build_clause_schema( 'Satin / Single-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_11' => [
                'title'       => 'Case 11. Exterior Floor / Car Traffic with Turning Wheels (Heavy Vehicles, Tight Turns)',
                'description' => 'Grouped by optional sealer protection and sheen.',
                'sections'    => ['monolith'],
                'clauses'     => [
                    'no_sealer'      => self::build_clause_schema( 'No Sealer', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                    ] ),
                    'matte_standard' => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'matte_max'      => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'satin_standard' => self::build_clause_schema( 'Satin / Single-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'satin_max'      => self::build_clause_schema( 'Satin / Single-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_12' => [
                'title'       => 'Case 12. Interior Floor',
                'description' => 'Grouped by moisture exposure, sheen, and duty level.',
                'sections'    => ['micromosaic'],
                'clauses'     => [
                    'wet_matte_standard' => self::build_clause_schema( 'Wet Area / Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'wet_matte_max'      => self::build_clause_schema( 'Wet Area / Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'dry_matte_standard' => self::build_clause_schema( 'Dry Area / Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'dry_matte_max'      => self::build_clause_schema( 'Dry Area / Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'dry_satin_standard' => self::build_clause_schema( 'Dry Area / Satin / Single-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                    'dry_satin_max'      => self::build_clause_schema( 'Dry Area / Satin / Single-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                    ] ),
                ],
            ],
            'case_13' => [
                'title'       => 'Case 13. Exterior Floor',
                'description' => 'Locked Matte Anti-Slip finish, grouped by duty level.',
                'sections'    => ['micromosaic'],
                'clauses'     => [
                    'standard' => self::build_clause_schema( 'Matte / Dual-Layer / Standard Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                    'max'      => self::build_clause_schema( 'Matte / Dual-Layer / Max Duty', [
                        'epoxy_primer_pair'       => self::build_material_schema( 'Primer (Hydro Stop Epoxy Primer)', ['', '', ''] ),
                        'monolith_stone'          => self::build_material_schema( 'Stone', ['', '', ''] ),
                        'monolith_resin_ratio'    => self::build_material_schema( 'Stone : resin ratio', ['', '', ''] ),
                        'monolith_flowtech_ratio' => self::build_material_schema( 'FLOWTECH ratio', ['', '', ''] ),
                        'monolith_silica'         => self::build_material_schema( 'Silica sand', ['', '', ''] ),
                        'micromosaic_fine'        => self::build_material_schema( 'System top-layer (Terrazzo Granitech Fine)', ['', '', ''] ),
                        'sealer_satin'            => self::build_material_schema( 'Sealer (Armour Coat Satin PU Sealer)', ['', '', ''] ),
                        'sealer_matte'            => self::build_material_schema( 'Sealer (Anti-Slip Matte PU)', ['', '', ''] ),
                    ] ),
                ],
            ],
        ];
    }

    /**
     * Filter the full schema to one system section.
     *
     * @param  array  $schema Full schema.
     * @param  string $section Active system section.
     * @return array
     */
    public static function filter_schema_for_system_section( array $schema, $section ) {

        $filtered = [];

        foreach ( $schema as $case_key => $case_config ) {
            if ( empty( $case_config['sections'] ) || ! in_array( $section, $case_config['sections'], true ) ) {
                continue;
            }

            $materials_for_case = [];

            foreach ( $case_config['clauses'] as $clause_key => $clause_config ) {
                $filtered_materials = [];

                foreach ( $clause_config['materials'] as $material_key => $material_config ) {
                    if ( ! self::material_belongs_to_system_section( $material_key, $section ) ) {
                        continue;
                    }

                    $material_config['label']          = self::get_system_specific_material_label( $material_key, $material_config['label'], $section );
                    $filtered_materials[$material_key] = $material_config;
                }

                if ( empty( $filtered_materials ) ) {
                    continue;
                }

                $materials_for_case[$clause_key] = [
                    'title'       => $clause_config['title'],
                    'description' => $clause_config['description'],
                    'materials'   => $filtered_materials,
                ];
            }

            if ( empty( $materials_for_case ) ) {
                continue;
            }

            $filtered[$case_key] = [
                'title'       => $case_config['title'],
                'description' => $case_config['description'],
                'clauses'     => $materials_for_case,
            ];
        }

        return $filtered;
    }

    /**
     * Resolve the exact material label for a case/clause/section/material tuple.
     *
     * @param  string $case_key Case key.
     * @param  string $clause_key Clause key.
     * @param  string $section Section slug.
     * @param  string $material_key Material key.
     * @return string
     */
    public static function get_material_label( $case_key, $clause_key, $section, $material_key ) {

        $schema = self::filter_schema_for_system_section( self::get_coverages_settings_schema(), $section );

        if ( isset( $schema[$case_key]['clauses'][$clause_key]['materials'][$material_key]['label'] ) ) {
            return $schema[$case_key]['clauses'][$clause_key]['materials'][$material_key]['label'];
        }

        return $material_key;
    }

    /**
     * Determine whether a material row belongs in a given system section.
     *
     * @param  string $material_key Material key.
     * @param  string $section Active system section.
     * @return bool
     */
    private static function material_belongs_to_system_section( $material_key, $section ) {

        if ( in_array( $material_key, ['primer', 'epoxy_primer_pair', 'sealer_matte', 'sealer_satin', 'sealer_semigloss', 'pool_sealer_pair'], true ) ) {
            return true;
        }

        if ( $section === 'medium' ) {
            return $material_key === 'system_medium';
        }

        if ( $section === 'fine' ) {
            return in_array( $material_key, ['system_medium', 'system_fine_top'], true );
        }

        if ( $section === 'microterrazzo' ) {
            return $material_key === 'system_micro';
        }

        if ( $section === 'gaia' ) {
            return $material_key === 'system_gaia';
        }

        if ( $section === 'monolith' ) {
            return in_array( $material_key, ['monolith_stone', 'monolith_resin_ratio', 'monolith_flowtech_ratio', 'monolith_silica'], true );
        }

        if ( $section === 'micromosaic' ) {
            return in_array( $material_key, ['monolith_stone', 'monolith_resin_ratio', 'monolith_flowtech_ratio', 'monolith_silica', 'micromosaic_fine'], true );
        }

        return false;
    }

    /**
     * Adjust material labels so they read naturally inside a system-specific section.
     *
     * @param  string $material_key Material key.
     * @param  string $default_label Default label.
     * @param  string $section Active system section.
     * @return string
     */
    private static function get_system_specific_material_label( $material_key, $default_label, $section ) {

        if ( $material_key === 'system_medium' && $section === 'fine' ) {
            return 'System under-layer (Terrazzo Granitech Medium)';
        }

        if ( $material_key === 'system_medium' && $section === 'medium' ) {
            return 'System (Terrazzo Granitech Medium)';
        }

        if ( $material_key === 'system_fine_top' ) {
            return 'System top-layer (Terrazzo Granitech Fine)';
        }

        if ( $material_key === 'system_micro' ) {
            return 'System (Terrazzo Granitech Microterrazzo)';
        }

        if ( $material_key === 'system_gaia' ) {
            return 'System (Terrazzo Granitech Gaia)';
        }

        return $default_label;
    }

    /**
     * Build a clause node for the settings schema.
     *
     * @param  string $title Clause title.
     * @param  array  $materials Material rows.
     * @param  string $description Optional description.
     * @return array
     */
    private static function build_clause_schema( $title, array $materials, $description = '' ) {

        return [
            'title'       => $title,
            'description' => $description,
            'materials'   => $materials,
        ];
    }

    /**
     * Build one material row for the settings schema.
     *
     * @param  string $label Material label.
     * @param  array  $values Up to three coverage values.
     * @param  string $hint Optional row hint.
     * @return array
     */
    private static function build_material_schema( $label, array $values, $hint = '' ) {

        return [
            'label'  => $label,
            'values' => $values,
            'hint'   => $hint,
        ];
    }
}