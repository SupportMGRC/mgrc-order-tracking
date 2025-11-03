# COA Editor - Quick Start Guide

## 🚀 What You Just Got

A complete PDF editing system with:
- ✅ PDF.js integration for rendering PDFs in the browser
- ✅ Editable text fields overlaid on specific PDF coordinates
- ✅ Save functionality to database
- ✅ Print and download features
- ✅ Multi-page support with navigation
- ✅ Visual position finder tool

## 📁 Files Created

```
✓ resources/views/orders/coa-editor.blade.php  - Unified COA view (view + edit)
✓ routes/web.php                               - Added 3 COA routes
✓ app/Http/Controllers/OrderController.php     - Added 3 COA methods
✓ public/coa-position-finder.html              - Helper tool
✓ README-COA-EDITOR.md                         - Full documentation
✓ COA-EDITOR-QUICKSTART.md                     - This file
```

## 🎯 How to Use (3 Steps)

### Step 1: Update Your PDF Path

Edit `resources/views/orders/coa-editor.blade.php` line ~272:

```javascript
const url = '{{ asset("assets/pdf/YOUR_PDF_FILENAME.pdf") }}';
```

Replace `YOUR_PDF_FILENAME.pdf` with your actual PDF filename.

### Step 2: Find Field Coordinates

#### Option A: Use the Position Finder Tool (Recommended)

1. Open in browser: `http://localhost/coa-position-finder.html`
2. Upload your PDF
3. Click on locations where you want editable fields
4. Fill in field details
5. Copy the generated code
6. Paste into `coa-editor.blade.php` at line ~242 (replace `editableFields` object)

#### Option B: Manual Method

1. Open browser console (F12) on the COA editor page
2. Paste this code:
```javascript
document.getElementById('pdf-canvas').addEventListener('click', function(e) {
    const rect = this.getBoundingClientRect();
    const x = Math.round((e.clientX - rect.left) * (1.5 / scale));
    const y = Math.round((e.clientY - rect.top) * (1.5 / scale));
    console.log(`x: ${x}, y: ${y}`);
});
```
3. Click on the PDF where you want fields
4. Copy coordinates from console

### Step 3: Test It Out

1. Go to any order details page
2. Click on a COA button
3. Click "Enable Edit Mode" to edit fields
4. Your editable PDF should appear with toggle edit capability!

## 🔧 Customizing Fields

Edit the `editableFields` object in `coa-editor.blade.php`:

```javascript
const editableFields = {
    1: [ // Page 1
        {
            id: 'batch_number',      // Unique ID
            x: 450,                   // X position (pixels from left)
            y: 240,                   // Y position (pixels from top)
            width: 250,               // Field width
            height: 30,               // Field height
            value: 'default',         // Pre-filled value
            label: 'Batch Number'     // Placeholder text
        }
        // Add more fields...
    ],
    2: [ // Page 2
        // Fields for page 2...
    ]
};
```

## 💾 What Gets Saved

These fields are saved to `order_product` table:
- `batch_number`
- `prepared_by`
- `qc_document_number`

Want to save more fields? See "Adding More Saveable Fields" in README-COA-EDITOR.md

## 🎨 Features Available

| Feature | How to Use |
|---------|-----------|
| **Edit Mode** | Click "Enable Edit Mode" button |
| **Save** | Click "Save" or press Ctrl+S |
| **Print** | Click "Print" or press Ctrl+P |
| **Download** | Click "Download PDF" button |
| **Navigate** | Use Previous/Next buttons or arrow keys |
| **Zoom** | Use Zoom +/- buttons |
| **Clear Fields** | Disable edit mode |

## 🔗 New Routes Added

```php
// View COA (unified view with toggle edit mode)
GET /orders/{order}/coa/{product}

// Edit COA (same unified view)
GET /orders/{order}/coa/{product}/edit

// Save COA data
POST /orders/{order}/coa/{product}/save
```

## 📝 Common Customizations

### Change Field Background Color
In `coa-editor.blade.php`, edit CSS:
```css
.editable-field {
    background: rgba(255, 255, 100, 0.3); /* Yellow */
    /* Change to: rgba(100, 200, 255, 0.3) for blue */
}
```

### Change Default Zoom
In `coa-editor.blade.php`, line ~229:
```javascript
let scale = 1.5; // Try 1.0 for smaller, 2.0 for larger
```

### Add More Fields to Save
Edit `OrderController.php`, `saveCOA` method:
```php
$order->products()->updateExistingPivot($product->id, [
    'batch_number' => $request->input('batch_number'),
    'prepared_by' => $request->input('prepared_by'),
    'qc_document_number' => $request->input('qc_document_number'),
    'your_field' => $request->input('your_field'), // Add this
]);
```

## ❓ Troubleshooting

### PDF Not Loading
- Check file path is correct
- Ensure PDF exists in `public/assets/pdf/`
- Check browser console (F12) for errors

### Fields Not Appearing
- Verify you're on the correct page number
- Check coordinates are within PDF bounds
- Try refreshing the page

### Save Not Working
- Check CSRF token is present
- Verify route exists: `php artisan route:list | grep coa`
- Check Laravel logs: `storage/logs/laravel.log`

### Fields in Wrong Position
- Use the position finder tool to get accurate coordinates
- Try adjusting the `scale` factor
- Verify you're looking at the correct page

## 🎓 Learn More

- Full Documentation: `README-COA-EDITOR.md`
- Position Finder Tool: `http://localhost/coa-position-finder.html`
- PDF.js Docs: https://mozilla.github.io/pdf.js/

## 🚦 Next Steps

1. ✅ Update PDF path in `coa-editor.blade.php`
2. ✅ Use position finder tool to get coordinates
3. ✅ Add fields to `editableFields` configuration
4. ✅ Test the editor on an order
5. ✅ Customize styling if needed
6. ✅ Add more saveable fields if needed

## 💡 Pro Tips

- Use the position finder tool - it's much faster than manual positioning
- Start with a few fields and test before adding many
- Fields scale automatically with zoom - base coordinates are at scale 1.5
- You can have different fields on each page
- Test printing before finalizing field positions

## 🎉 You're Ready!

Your COA editor is now set up. Just configure your PDF path and field positions, and you're good to go!

Need help? Check the full README-COA-EDITOR.md for detailed documentation.

---

**Created:** October 2025  
**For:** MGRC Order Tracking System


