<?php

/*
|--------------------------------------------------------------------------
| COA Template Configuration
|--------------------------------------------------------------------------
|
| One entry per Certificate of Analysis template. Each entry records:
|
|   pdf          the blank template in public/assets/pdf/
|   editable     which fields QC may fill in, in display order
|   coordinates  where each value is drawn, as a percentage of page
|                width/height so the overlay is resolution-independent
|   products     product names this template applies to (used by the
|                one-off backfill; the live mapping lives in the
|                products.coa_template column)
|
| Coordinates were measured directly from the approved blank PDFs, where
| every editable value is present as invisible text in exactly the spot the
| live value must occupy. Do not hand-edit: if a template is revised,
| re-run the measurement script and regenerate this file.
|
| Note on viable_cell_count: on NK/NKT only the multiplier is editable
| (the "x 10^9" suffix is printed on the template). On MSC the whole
| value is printed and is therefore not listed as editable.
|
| Optional keys on a text coordinate:
|
|   font    'Calibri', 'Calibri-Bold', 'Mistral'. The weight is honoured, so
|           a value sitting beside a bold printed label matches it.
|   dx, dy  fine alignment nudge in PDF POINTS (same unit as font_size).
|           dx positive moves right, dy positive moves down. Use these to
|           settle a value onto the baseline of the label printed next to it
|           rather than re-measuring the whole template. They are applied in
|           the preview, the printout and the download identically.
|
| Optional keys on morphology_slot:
|
|   align   'left' (default), 'center', 'right' — horizontal anchor inside
|           the frame. The micrograph is 4:3 and the frame is wider than
|           that, so centring left a white gutter down the left-hand side;
|           left keeps the image flush with the column's text margin.
|   valign  'top', 'middle' (default), 'bottom'.
|
| Variant groups
|
| Two templates that differ only in wording — MSC P2 with and without the
| patient's name — are two certificates, but one product. Tagging both with
| the same variant_group collapses them into a single choice on the product
| form, and lets QC pick the wording per order from the COA editor:
|
|   variant_group        shared id, e.g. 'msc_p2'
|   variant_group_label  what the product form shows, e.g. 'MSC P2'
|   variant_label        what the COA editor's toggle shows
|   variant_default      true on the member a product defaults to
|
| Quality staff may switch between members of the same group. Moving an order
| to a template in a DIFFERENT group stays superadmin-only.
|
*/

return [
    'msc_p3' => [
        'label' => 'MSC P3',
        'pdf' => 'COA_MSC_P3.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'patient_name', 'batch_number', 'product_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'patient_name' => 'Patient Name',
            'batch_number' => 'Batch Number',
            'product_date' => 'Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['MSC 50M (P3)', 'MSC 100M (P3)', 'MSC 150M (P3)'],
        'coordinates' => [
            'page1' => [
                'patient_name' => [
                    'x' => 42.492,
                    'y' => 22.529,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'batch_number' => [
                    'x' => 42.492,
                    'y' => 25.014,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'product_date' => [
                    'x' => 42.492,
                    'y' => 27.499,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 71.679,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.833,
                    'y' => 77.339,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 46.819,
                    'y' => 84.194,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 74.063,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'immuno_results' => [
                    [
                        'x' => 62.754,
                        'y' => 31.194,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 70.058,
                        'y' => 31.194,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 76.611,
                        'y' => 31.194,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 87.596,
                        'y' => 31.194,
                        'font_size' => 8.0,
                    ],
                ],
                'morphology_slot' => [
                    'x' => 6.850,
                    'y' => 16.845,
                    'w' => 39.600,
                    'h' => 15.054,
                    'align' => 'left',
                    'valign' => 'middle',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 37.243,
            'y' => 80.289,
            'w' => 26.25,
        ],
    ],

    'msc_p2_name' => [
        'label' => 'MSC P2 (with patient name)',
        'variant_group' => 'msc_p2',
        'variant_group_label' => 'MSC P2',
        'variant_label' => 'With patient name',
        'variant_default' => true,
        'pdf' => 'COA_MSC_P2_with_Name.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'patient_name', 'batch_number', 'product_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'patient_name' => 'Patient Name',
            'batch_number' => 'Batch Number',
            'product_date' => 'Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['MSC 5M', 'MSC 10M', 'MSC 15M', 'MSC 20M', 'MSC 25M', 'MSC 50M', 'MSC 100M', 'MSC 120M', 'MSC 125M', 'MSC 150M', 'MSC 200M'],
        'coordinates' => [
            'page1' => [
                'patient_name' => [
                    'x' => 42.492,
                    'y' => 22.908,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'batch_number' => [
                    'x' => 42.492,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'product_date' => [
                    'x' => 42.492,
                    'y' => 27.878,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 71.773,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.578,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 73.975,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'immuno_results' => [
                    [
                        'x' => 16.972,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 23.721,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 30.829,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 41.814,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                ],
                'morphology_slot' => [
                    'x' => 6.850,
                    'y' => 16.845,
                    'w' => 39.600,
                    'h' => 15.054,
                    'align' => 'left',
                    'valign' => 'middle',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

    'msc_p2_noname' => [
        'label' => 'MSC P2 (without patient name)',
        'variant_group' => 'msc_p2',
        'variant_group_label' => 'MSC P2',
        'variant_label' => 'Without patient name',
        'pdf' => 'COA_MSC_P2_without_Name.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'batch_number', 'product_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'batch_number' => 'Batch Number',
            'product_date' => 'Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => [],
        'coordinates' => [
            'page1' => [
                'batch_number' => [
                    'x' => 42.492,
                    'y' => 22.908,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'product_date' => [
                    'x' => 42.492,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 71.773,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.578,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 73.975,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'immuno_results' => [
                    [
                        'x' => 16.972,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 23.721,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 30.829,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                    [
                        'x' => 41.814,
                        'y' => 74.992,
                        'font_size' => 8.0,
                    ],
                ],
                'morphology_slot' => [
                    'x' => 6.850,
                    'y' => 16.845,
                    'w' => 39.600,
                    'h' => 15.054,
                    'align' => 'left',
                    'valign' => 'middle',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

    'nk' => [
        'label' => 'NK',
        'pdf' => 'COA_NK.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'patient_name', 'product_date', 'viable_cell_count', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'patient_name' => 'Patient Name',
            'product_date' => 'Date',
            'viable_cell_count' => 'Viable Cell Count',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['NK Cells 2B', 'NK Cells 5B (Ready Product For Infusion)'],
        'coordinates' => [
            'page1' => [
                'patient_name' => [
                    'x' => 41.854,
                    'y' => 22.908,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'product_date' => [
                    'x' => 41.854,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'viable_cell_count' => [
                    'x' => 41.854,
                    'y' => 33.348,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                    'dy' => 3.0,
                ],
                'coa_number' => [
                    'x' => 80.751,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 41.851,
                    'y' => 85.191,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 48.071,
                    'y' => 92.106,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 82.952,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'morphology_slot' => [
                    'x' => 6.850,
                    'y' => 16.299,
                    'w' => 39.600,
                    'h' => 15.022,
                    'align' => 'left',
                    'valign' => 'middle',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 37.815,
            'y' => 88.201,
            'w' => 26.25,
        ],
    ],

    'nkt' => [
        'label' => 'NKT',
        'pdf' => 'COA_NKT.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'patient_name', 'product_date', 'viable_cell_count', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'patient_name' => 'Patient Name',
            'product_date' => 'Date',
            'viable_cell_count' => 'Viable Cell Count',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['NKT Cells 10B (Ready Product For Infusion)'],
        'coordinates' => [
            'page1' => [
                'patient_name' => [
                    'x' => 42.492,
                    'y' => 22.908,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'product_date' => [
                    'x' => 42.492,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'viable_cell_count' => [
                    'x' => 42.492,
                    'y' => 33.348,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                    'dy' => 3.0,
                ],
                'coa_number' => [
                    'x' => 80.928,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.578,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 83.13,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'morphology_slot' => [
                    'x' => 6.850,
                    'y' => 16.299,
                    'w' => 39.600,
                    'h' => 15.022,
                    'align' => 'left',
                    'valign' => 'middle',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

    'exo_general' => [
        'label' => 'General Exosome',
        'pdf' => 'COA_Exosome_General.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'batch_number', 'mfg_date', 'expiry_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'batch_number' => 'Batch Number',
            'mfg_date' => 'Manufacturing Date',
            'expiry_date' => 'Expiry Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['General Exosome'],
        'coordinates' => [
            'page1' => [
                'batch_number' => [
                    'x' => 42.492,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'mfg_date' => [
                    'x' => 42.492,
                    'y' => 27.605,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'expiry_date' => [
                    'x' => 42.492,
                    'y' => 30.09,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 78.351,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.666,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 79.241,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

    'exo_wellness' => [
        'label' => 'Exosomes Wellness',
        'pdf' => 'COA_Exosome_Wellness.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'batch_number', 'mfg_date', 'expiry_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'batch_number' => 'Batch Number',
            'mfg_date' => 'Manufacturing Date',
            'expiry_date' => 'Expiry Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['Exosomes Wellness'],
        'coordinates' => [
            'page1' => [
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'mfg_date' => [
                    'x' => 42.493,
                    'y' => 27.604,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'expiry_date' => [
                    'x' => 42.493,
                    'y' => 30.089,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 78.128,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.733,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 80.463,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

    'exo_cardio' => [
        'label' => 'Exosomes Cardio',
        'pdf' => 'COA_Exosome_Cardio.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'batch_number', 'mfg_date', 'expiry_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'batch_number' => 'Batch Number',
            'mfg_date' => 'Manufacturing Date',
            'expiry_date' => 'Expiry Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['Exosomes Cardio'],
        'coordinates' => [
            'page1' => [
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'mfg_date' => [
                    'x' => 42.493,
                    'y' => 27.604,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'expiry_date' => [
                    'x' => 42.493,
                    'y' => 30.089,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 77.039,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.733,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 79.063,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

    'secretome' => [
        'label' => 'Secretome',
        'pdf' => 'COA_Secretome.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'editable' => ['coa_number', 'batch_number', 'mfg_date', 'expiry_date', 'signature_date'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'batch_number' => 'Batch Number',
            'mfg_date' => 'Manufacturing Date',
            'expiry_date' => 'Expiry Date',
            'signature_date' => 'Signature Date',
        ],
        'products' => ['Secretome'],
        'coordinates' => [
            'page1' => [
                'batch_number' => [
                    'x' => 42.492,
                    'y' => 25.393,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'mfg_date' => [
                    'x' => 42.492,
                    'y' => 27.605,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'expiry_date' => [
                    'x' => 42.492,
                    'y' => 30.09,
                    'font_size' => 10.0,
                    'font' => 'Calibri',
                ],
                'coa_number' => [
                    'x' => 80.173,
                    'y' => 17.157,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
                'signature' => [
                    'x' => 40.637,
                    'y' => 84.658,
                    'font_size' => 20.0,
                    'font' => 'Mistral',
                ],
                'signature_date' => [
                    'x' => 45.733,
                    'y' => 91.604,
                    'font_size' => 11.0,
                    'font' => 'Calibri-Bold',
                    'dx' => 2.0,
                    'dy' => 1.5,
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'x' => 82.374,
                    'y' => 7.165,
                    'font_size' => 7.0,
                    'font' => 'Calibri',
                ],
            ],
        ],
        'signature_rule' => [
            'x' => 36.601,
            'y' => 87.699,
            'w' => 26.25,
        ],
    ],

];