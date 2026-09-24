<?php

/*
|--------------------------------------------------------------------------
| COA Template Configuration  —  schema v2 (baseline coordinates)
|--------------------------------------------------------------------------
|
| One entry per Certificate of Analysis template:
|
|   pdf          blank template in public/assets/pdf/
|   certificate_pages
|                pages printed on MGRC certificate paper (inside the gold
|                border). Every other page prints at its designed size, centred
|                on plain A4. Also drives the Printing note in the editor.
|   editable     fields QC may fill in, in display order
|   coordinates  where each value is drawn
|   products     product names this template applies to (the live mapping
|                lives in the products.coa_template column; this list only
|                drives the one-off backfill)
|
| ---------------------------------------------------------------------------
| COORDINATE KEYS
| ---------------------------------------------------------------------------
|
|   x          left edge, % of page width      (align 'left')
|   cx         centre,    % of page width      (align 'center')
|   right      right edge, % of page width     (align 'right')
|   y          BASELINE,  % of page height     (always)
|   font_size  points, at the template's own 540 x 780 scale
|   font       'Calibri', 'Calibri-Bold', or 'HerrVonMuellerhoff' for the
|              signature. That font is bundled in public/assets/fonts. Mistral,
|              used before, is an Office font that cannot be shipped, so it
|              only ever rendered on PCs that happened to have Office.
|   align      'left' (default), 'center' or 'right'
|   color      text colour, default black. The signature block is navy
|              #17375E, the same as the printed labels around it.
|   dx, dy     fine nudge in POINTS. dx + is right, dy + is down. Use these to
|              settle a value rather than re-measuring a whole template.
|   max_w      optional width budget, % of page. If the value is wider the font
|              is stepped down until it fits. Set on coa_number (label and
|              number together) and on the signature.
|   suffix, suffix_sup
|              drawn after the value: viable_cell_count prints '25 x 10' with a
|              superscript '6' or '9', so QC types only the number. The
|              superscript size and lift match the one QC's templates printed.
|
| coa_number is drawn as a group: the bold navy 'label', a 'gap' in points,
| then the number, right-aligned so the number always ends at 'right'. This
| matches QC's right-aligned text box. The label used to be printed on the
| PDF, which could not move with the length of the number, so it was removed
| from every base PDF.
|
| signatory_name is the printed name under the signature rule. Both it and
| the signature come from the name stored when the COA is submitted.
|
| morphology_slot keys:
|
|   x, y, w, h  the frame the micrograph fills, % of page
|   fit         'cover'   fill the frame completely, cropping the overhang
|                         (what QC asked for: no white gutter)
|               'contain' fit the whole image inside, leaving white space
|   align       horizontal anchor of the crop: 'left' | 'center' | 'right'
|   valign      vertical anchor of the crop:   'top'  | 'middle' | 'bottom'
|
| ---------------------------------------------------------------------------
| VARIANT GROUPS
| ---------------------------------------------------------------------------
|
| Two templates that differ only in wording — MSC P2 with and without the
| patient's name — are two certificates but one product. A shared
| variant_group collapses them into a single choice on the product form and
| lets QC pick the wording per order from the COA editor. Quality staff may
| switch within a group; moving an order to another group stays superadmin.
|
| Coordinates were measured from the blank PDFs (text baselines read with
| pdfplumber). If a template is revised, re-measure rather than nudging by
| eye; dx/dy are there for small settling only.
*/

return [

    'msc_p3' => [
        'label' => 'MSC P3',
        'pdf' => 'COA_MSC_P3.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [1],
        'editable' => ['coa_number', 'patient_name', 'batch_number', 'product_date', 'viable_cell_count', 'signature_date', 'immuno_cd73', 'immuno_cd90', 'immuno_cd105', 'immuno_negative'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'patient_name' => 'Patient Name',
            'batch_number' => 'Batch Number',
            'product_date' => 'Date',
            'viable_cell_count' => 'Viable Cell Count',
            'signature_date' => 'Signature Date',
            'immuno_cd73' => 'CD73+ (%)',
            'immuno_cd90' => 'CD90+ (%)',
            'immuno_cd105' => 'CD105+ (%)',
            'immuno_negative' => 'Negative markers (%)',
        ],
        'products' => ['MSC 50M (P3)', 'MSC 100M (P3)', 'MSC 150M (P3)'],
        'coordinates' => [
            'page1' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.37,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'patient_name' => [
                    'x' => 42.493,
                    'y' => 23.84,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 26.324,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'product_date' => [
                    'x' => 42.493,
                    'y' => 28.809,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'viable_cell_count' => [
                    'x' => 42.493,
                    'y' => 36.764,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                    'suffix' => ' x 10',
                    'suffix_sup' => '6',
                ],
                'signature_date' => [
                    'x' => 46.82,
                    'y' => 85.664,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                    'dx' => 1.5,
                ],
                'signature' => [
                    'cx' => 50.369,
                    'y' => 79.703,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 50.369,
                    'y' => 82.279,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
                'immuno_cd73' => [
                    'cx' => 64.459,
                    'y' => 32.251,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_cd90' => [
                    'cx' => 71.207,
                    'y' => 32.251,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_cd105' => [
                    'cx' => 78.32,
                    'y' => 32.251,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_negative' => [
                    'cx' => 88.92,
                    'y' => 32.251,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'morphology_slot' => [
                    'x' => 6.722,
                    'y' => 16.667,
                    'w' => 40.185,
                    'h' => 15.769,
                    'align' => 'center',
                    'valign' => 'middle',
                    'fit' => 'cover',
                ],
            ],
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
        'coord_schema' => 2,
        'certificate_pages' => [1],
        'editable' => ['coa_number', 'patient_name', 'batch_number', 'product_date', 'viable_cell_count', 'signature_date', 'immuno_cd73', 'immuno_cd90', 'immuno_cd105', 'immuno_negative'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'patient_name' => 'Patient Name',
            'batch_number' => 'Batch Number',
            'product_date' => 'Date',
            'viable_cell_count' => 'Viable Cell Count',
            'signature_date' => 'Signature Date',
            'immuno_cd73' => 'CD73+ (%)',
            'immuno_cd90' => 'CD90+ (%)',
            'immuno_cd105' => 'CD105+ (%)',
            'immuno_negative' => 'Negative markers (%)',
        ],
        'products' => ['MSC 5M', 'MSC 10M', 'MSC 15M', 'MSC 20M', 'MSC 25M', 'MSC 50M', 'MSC 100M', 'MSC 120M', 'MSC 125M', 'MSC 150M', 'MSC 200M'],
        'coordinates' => [
            'page1' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.11,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'patient_name' => [
                    'x' => 42.959,
                    'y' => 24.41,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'batch_number' => [
                    'x' => 42.959,
                    'y' => 26.896,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'product_date' => [
                    'x' => 42.959,
                    'y' => 29.379,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'viable_cell_count' => [
                    'x' => 42.959,
                    'y' => 37.336,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                    'suffix' => ' x 10',
                    'suffix_sup' => '6',
                ],
                // 'Date:' is drawn with the date and the pair centred under
                // the signature, as QC's centred text box does. The label was
                // printed centred on its own on this PDF, so a filled-in date
                // sat off to the right; it was removed from the PDF.
                'signature_date' => [
                    'align' => 'center',
                    'cx' => 49.731,
                    'y' => 93.09,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'color' => '#17375E',
                    'label' => 'Date:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 2.3,
                ],
                'signature' => [
                    'cx' => 49.725,
                    'y' => 87.036,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.725,
                    'y' => 89.705,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.037,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
                'immuno_cd73' => [
                    'cx' => 18.685,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_cd90' => [
                    'cx' => 25.581,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_cd105' => [
                    'cx' => 32.761,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_negative' => [
                    'cx' => 43.36,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'morphology_slot' => [
                    'x' => 6.724,
                    'y' => 16.667,
                    'w' => 40.235,
                    'h' => 15.769,
                    'align' => 'center',
                    'valign' => 'middle',
                    'fit' => 'cover',
                ],
            ],
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
        'coord_schema' => 2,
        'certificate_pages' => [1],
        'editable' => ['coa_number', 'batch_number', 'product_date', 'viable_cell_count', 'signature_date', 'immuno_cd73', 'immuno_cd90', 'immuno_cd105', 'immuno_negative'],
        'field_labels' => [
            'coa_number' => 'COA No.',
            'batch_number' => 'Batch Number',
            'product_date' => 'Date',
            'viable_cell_count' => 'Viable Cell Count',
            'signature_date' => 'Signature Date',
            'immuno_cd73' => 'CD73+ (%)',
            'immuno_cd90' => 'CD90+ (%)',
            'immuno_cd105' => 'CD105+ (%)',
            'immuno_negative' => 'Negative markers (%)',
        ],
        'products' => [],
        'coordinates' => [
            'page1' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.11,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'batch_number' => [
                    'x' => 42.959,
                    'y' => 24.41,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'product_date' => [
                    'x' => 42.959,
                    'y' => 26.895,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'viable_cell_count' => [
                    'x' => 42.959,
                    'y' => 35.109,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                    'suffix' => ' x 10',
                    'suffix_sup' => '6',
                ],
                // 'Date:' is drawn with the date and the pair centred under
                // the signature, as QC's centred text box does. The label was
                // printed centred on its own on this PDF, so a filled-in date
                // sat off to the right; it was removed from the PDF.
                'signature_date' => [
                    'align' => 'center',
                    'cx' => 49.731,
                    'y' => 93.09,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'color' => '#17375E',
                    'label' => 'Date:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 2.3,
                ],
                'signature' => [
                    'cx' => 49.725,
                    'y' => 87.036,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.725,
                    'y' => 89.705,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.037,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
                'immuno_cd73' => [
                    'cx' => 18.685,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_cd90' => [
                    'cx' => 25.581,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_cd105' => [
                    'cx' => 32.761,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'immuno_negative' => [
                    'cx' => 43.36,
                    'y' => 76.062,
                    'font_size' => 8.04,
                    'font' => 'Calibri',
                    'align' => 'center',
                ],
                'morphology_slot' => [
                    'x' => 6.724,
                    'y' => 16.667,
                    'w' => 40.235,
                    'h' => 15.769,
                    'align' => 'center',
                    'valign' => 'middle',
                    'fit' => 'cover',
                ],
            ],
        ],
    ],

    'nk' => [
        'label' => 'NK',
        'pdf' => 'COA_NK.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [1],
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
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'patient_name' => [
                    'x' => 41.854,
                    'y' => 24.218,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'product_date' => [
                    'x' => 41.854,
                    'y' => 26.704,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'viable_cell_count' => [
                    'x' => 41.854,
                    'y' => 34.659,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                    'suffix' => ' x 10',
                    'suffix_sup' => '9',
                ],
                'signature_date' => [
                    'x' => 48.067,
                    'y' => 93.577,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                ],
                'signature' => [
                    'cx' => 50.939,
                    'y' => 87.554,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 50.939,
                    'y' => 90.191,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
                'morphology_slot' => [
                    'x' => 6.183,
                    'y' => 16.154,
                    'w' => 40.961,
                    'h' => 15.641,
                    'align' => 'center',
                    'valign' => 'middle',
                    'fit' => 'cover',
                ],
            ],
        ],
    ],

    'nkt' => [
        'label' => 'NKT',
        'pdf' => 'COA_NKT.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [1],
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
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'patient_name' => [
                    'x' => 42.493,
                    'y' => 24.218,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'product_date' => [
                    'x' => 42.493,
                    'y' => 26.704,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'viable_cell_count' => [
                    'x' => 42.493,
                    'y' => 34.659,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                    'suffix' => ' x 10',
                    'suffix_sup' => '9',
                ],
                'signature_date' => [
                    'x' => 45.578,
                    'y' => 93.074,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                    'dx' => 1.5,
                ],
                'signature' => [
                    'cx' => 49.724,
                    'y' => 87.022,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.724,
                    'y' => 89.69,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
                'morphology_slot' => [
                    'x' => 6.183,
                    'y' => 16.154,
                    'w' => 40.961,
                    'h' => 15.641,
                    'align' => 'center',
                    'valign' => 'middle',
                    'fit' => 'cover',
                ],
            ],
        ],
    ],

    'exo_general' => [
        'label' => 'General Exosome',
        'pdf' => 'COA_Exosome_General.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [],
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
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 26.704,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'mfg_date' => [
                    'x' => 42.493,
                    'y' => 28.915,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'expiry_date' => [
                    'x' => 42.493,
                    'y' => 31.4,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'signature_date' => [
                    'x' => 45.667,
                    'y' => 93.074,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                    'dx' => 1.5,
                ],
                'signature' => [
                    'cx' => 49.724,
                    'y' => 87.022,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.724,
                    'y' => 89.69,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
            ],
        ],
    ],

    'exo_wellness' => [
        'label' => 'Exosomes Wellness',
        'pdf' => 'COA_Exosome_Wellness.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [],
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
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 26.704,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'mfg_date' => [
                    'x' => 42.493,
                    'y' => 28.915,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'expiry_date' => [
                    'x' => 42.493,
                    'y' => 31.4,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'signature_date' => [
                    'x' => 45.733,
                    'y' => 93.074,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                    'dx' => 1.5,
                ],
                'signature' => [
                    'cx' => 49.724,
                    'y' => 87.022,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.724,
                    'y' => 89.69,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
            ],
        ],
    ],

    'exo_cardio' => [
        'label' => 'Exosomes Cardio',
        'pdf' => 'COA_Exosome_Cardio.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [],
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
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 26.704,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'mfg_date' => [
                    'x' => 42.493,
                    'y' => 28.915,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'expiry_date' => [
                    'x' => 42.493,
                    'y' => 31.4,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'signature_date' => [
                    'x' => 45.733,
                    'y' => 93.074,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                    'dx' => 1.5,
                ],
                'signature' => [
                    'cx' => 49.724,
                    'y' => 87.022,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.724,
                    'y' => 89.69,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
            ],
        ],
    ],

    'secretome' => [
        'label' => 'Secretome',
        'pdf' => 'COA_Secretome.pdf',
        'pages' => 2,
        'page_width' => 540.0,
        'page_height' => 780.0,
        'coord_schema' => 2,
        'certificate_pages' => [],
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
                'coa_number' => [
                    'align' => 'right',
                    'right' => 92.556,
                    'y' => 18.073,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 40.0,
                ],
                'batch_number' => [
                    'x' => 42.493,
                    'y' => 26.704,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'mfg_date' => [
                    'x' => 42.493,
                    'y' => 28.915,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'expiry_date' => [
                    'x' => 42.493,
                    'y' => 31.4,
                    'font_size' => 9.96,
                    'font' => 'Calibri',
                    'align' => 'left',
                ],
                'signature_date' => [
                    'x' => 45.733,
                    'y' => 93.074,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'left',
                    'color' => '#17375E',
                    'dx' => 1.5,
                ],
                'signature' => [
                    'cx' => 49.724,
                    'y' => 87.022,
                    'font_size' => 24.0,
                    'font' => 'HerrVonMuellerhoff',
                    'align' => 'center',
                    'color' => '#17375E',
                    'max_w' => 26.26,
                ],
                'signatory_name' => [
                    'cx' => 49.724,
                    'y' => 89.69,
                    'font_size' => 11.04,
                    'font' => 'Calibri-Bold',
                    'align' => 'center',
                    'color' => '#17375E',
                ],
            ],
            'page2' => [
                'coa_number' => [
                    'align' => 'right',
                    'right' => 94.759,
                    'y' => 8.081,
                    'font_size' => 6.96,
                    'font' => 'Calibri',
                    'label' => 'COA No:',
                    'label_font' => 'Calibri-Bold',
                    'label_color' => '#17375E',
                    'gap' => 6.5,
                    'max_w' => 45.0,
                ],
            ],
        ],
    ],

];