# COA Editor System - Documentation

## Overview

The COA (Certificate of Analysis) Editor system allows you to:

1. **Display a PDF** in the browser using PDF.js
2. **Overlay editable text fields** at specific positions on the PDF
3. **Edit the fields** directly in the browser
4. **Save the changes** to the database
5. **Print or download** the final document with edits

## Features

✅ **PDF Rendering** - Uses PDF.js to render PDFs in the browser  
✅ **Editable Overlays** - HTML input fields positioned over specific areas  
✅ **Multi-page Support** - Navigate through PDF pages with editable fields on each  
✅ **Zoom Controls** - Zoom in/out to view PDF details  
✅ **Edit Mode** - Toggle between viewing and editing modes  
✅ **Auto-save** - Save field values to the database  
✅ **Print** - Print the document with edits (Ctrl+P)  
✅ **Download** - Export as image with edits included  
✅ **Keyboard Shortcuts** - Navigate efficiently with keyboard  

## How to Use

### Accessing the COA Editor

1. Navigate to **Orders** → **Order Details** → **COA** button
2. Click the **"Edit COA"** button in the COA view
3. The editor will load with the PDF and overlay fields

### Editing Fields

1. Click **"Enable Edit Mode"** button (or it may be enabled by default)
2. Click on any yellow-highlighted field to edit
3. Type your changes
4. Fields will auto-highlight on hover and focus

### Saving Changes

- Click the **"Save"** button (or press `Ctrl+S`)
- Data is saved to the database via AJAX
- A success/error message will appear

### Printing

- Click the **"Print"** button (or press `Ctrl+P`)
- Edit mode will automatically disable before printing
- Use your browser's print dialog to print or save as PDF

### Downloading

- Click the **"Download PDF"** button
- The current page with edits will be captured and downloaded as an image
- File will be named: `COA_Order{id}_{product}_{timestamp}.png`

### Navigation

- Use **Previous/Next** buttons to navigate between pages
- Use **keyboard arrows** (Left/Right) when not in edit mode
- Each page can have its own set of editable fields

## Customizing Field Positions

### Where to Edit

Edit the `editableFields` configuration in:
```
resources/views/orders/coa-editor.blade.php
```

### Field Configuration Format

```javascript
const editableFields = {
    1: [ // Page number
        {
            id: 'field_name',        // Unique identifier
            x: 450,                  // X position (pixels from left)
            y: 240,                  // Y position (pixels from top)
            width: 250,              // Field width in pixels
            height: 30,              // Field height in pixels
            value: 'default value',  // Pre-filled value (can use Laravel variables)
            label: 'Field Label'     // Placeholder text
        }
    ]
};
```

### Finding the Right Coordinates

#### Method 1: Browser Developer Tools

1. Open the COA editor
2. Press `F12` to open Developer Tools
3. Right-click on the PDF canvas and select "Inspect"
4. In the Console tab, type:
```javascript
document.getElementById('pdf-canvas').addEventListener('click', function(e) {
    const rect = this.getBoundingClientRect();
    const x = Math.round((e.clientX - rect.left) * (1.5 / scale));
    const y = Math.round((e.clientY - rect.top) * (1.5 / scale));
    console.log(`x: ${x}, y: ${y}`);
});
```
5. Click on the PDF where you want the field to appear
6. Copy the coordinates from the console

#### Method 2: Trial and Error

1. Add a field with estimated coordinates
2. Refresh the page
3. Adjust the `x` and `y` values until positioned correctly
4. The scale factor is `1.5` by default (100% zoom)

### Example Field Configurations

```javascript
const editableFields = {
    1: [ // Page 1
        {
            id: 'batch_number',
            x: 450, y: 240, width: 250, height: 30,
            value: '{{ $orderProduct->pivot->batch_number ?? "" }}',
            label: 'Batch Number'
        },
        {
            id: 'mfg_date',
            x: 450, y: 280, width: 250, height: 30,
            value: '{{ $order->order_date ? $order->order_date->format("F Y") : "" }}',
            label: 'Manufacturing Date'
        },
        {
            id: 'expiry_date',
            x: 450, y: 320, width: 250, height: 30,
            value: '{{ $order->order_date ? $order->order_date->addYears(3)->format("F Y") : "" }}',
            label: 'Expiry Date'
        }
    ],
    2: [ // Page 2
        {
            id: 'test_result_1',
            x: 300, y: 400, width: 200, height: 25,
            value: 'PASSED',
            label: 'Test Result'
        }
    ],
    3: [ // Page 3
        // Add fields for page 3 as needed
    ]
};
```

## Database Integration

### Fields Saved to Database

The following fields are saved to the `order_product` pivot table:

- `batch_number` - Product batch number
- `prepared_by` - Name of person who prepared the COA
- `qc_document_number` - Quality control document reference

### Adding More Saveable Fields

1. **Add column to migration** (if needed):
```php
Schema::table('order_product', function (Blueprint $table) {
    $table->string('your_new_field')->nullable();
});
```

2. **Update the `saveCOA` method** in `OrderController.php`:
```php
$order->products()->updateExistingPivot($product->id, [
    'batch_number' => $request->input('batch_number'),
    'prepared_by' => $request->input('prepared_by'),
    'qc_document_number' => $request->input('qc_document_number'),
    'your_new_field' => $request->input('your_new_field'), // Add this line
]);
```

3. **Add field to JavaScript** in the `saveCOA()` function if needed (it auto-collects all `.editable-field` inputs)

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+P` or `Cmd+P` | Print |
| `Ctrl+S` or `Cmd+S` | Save |
| `←` Left Arrow | Previous page (when not in edit mode) |
| `→` Right Arrow | Next page (when not in edit mode) |

## File Structure

```
├── resources/views/orders/
│   ├── coa-editor.blade.php    # Unified COA view (view + edit with toggle)
│   └── orderdetails.blade.php  # Order details page
├── app/Http/Controllers/
│   └── OrderController.php     # showCOA, editCOA, saveCOA methods
├── routes/
│   └── web.php                 # COA routes
└── public/assets/pdf/
    └── [your-pdf-file].pdf     # COA PDF template
```

## Routes

```php
// Display COA (uses unified coa-editor view)
GET /orders/{order}/coa/{product}

// Display editable COA (same view as above with edit mode)
GET /orders/{order}/coa/{product}/edit

// Save COA data
POST /orders/{order}/coa/{product}/save
```

## Technologies Used

- **PDF.js** (v3.11.174) - Mozilla's PDF rendering library
- **html2canvas** (v1.4.1) - For capturing edited PDF as image
- **Laravel Blade** - Templating engine
- **JavaScript** - Client-side interactivity
- **CSS3** - Styling and positioning

## Troubleshooting

### PDF Not Loading

1. Check the PDF file path in the blade template:
```javascript
const url = '{{ asset("assets/pdf/YOUR_PDF_FILE.pdf") }}';
```
2. Ensure the PDF file exists in `public/assets/pdf/`
3. Check browser console for errors (Press F12)

### Fields Not Appearing

1. Verify you're on the correct page number
2. Check that the page has fields defined in `editableFields[pageNum]`
3. Try adjusting coordinates - they may be off-screen

### Fields Not Saving

1. Check browser console for AJAX errors
2. Verify the route exists: `php artisan route:list | grep coa`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Ensure CSRF token is present

### Position Issues After Zoom

- Field positions are automatically scaled with zoom
- If positions seem off, adjust the base scale factor (default 1.5):
```javascript
let scale = 1.5; // Try different values like 1.0, 2.0, etc.
```

## Advanced Customization

### Changing Field Styles

Edit the `.editable-field` CSS class:
```css
.editable-field {
    background: rgba(255, 255, 100, 0.3); /* Yellow highlight */
    border: 2px solid #ff6b6b;             /* Red border */
    padding: 5px;
    font-family: Arial, sans-serif;
    font-size: 14px;
    /* Add your custom styles */
}
```

### Using Dropdown Fields

Replace `<input type="text">` with `<select>` in the `renderEditableFields()` function:
```javascript
const select = document.createElement('select');
select.className = 'editable-field';
// Add options
const option1 = document.createElement('option');
option1.value = 'value1';
option1.textContent = 'Option 1';
select.appendChild(option1);
```

### Adding Multi-line Text Fields

Use `<textarea>` instead of `<input>`:
```javascript
const textarea = document.createElement('textarea');
textarea.className = 'editable-field';
// Set other properties
```

## Browser Compatibility

✅ Chrome 90+  
✅ Firefox 88+  
✅ Safari 14+  
✅ Edge 90+  

## Performance Notes

- PDF.js can be slow on large PDFs (>10 MB)
- Consider optimizing your PDF files
- Each page renders independently for better performance
- html2canvas capture can take 2-3 seconds for high-quality output

## Security Considerations

- Only authenticated users can access the COA editor
- CSRF protection is enabled on save operations
- Validate user permissions in controller methods
- Sanitize user input before saving to database

## Future Enhancements

- [ ] Server-side PDF generation with edits
- [ ] Digital signature support
- [ ] Multi-user collaboration
- [ ] Field validation rules
- [ ] Auto-save draft functionality
- [ ] Version history/audit trail

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check browser console (F12)
- Refer to PDF.js documentation: https://mozilla.github.io/pdf.js/

---

**Last Updated:** October 2025  
**Version:** 1.0


