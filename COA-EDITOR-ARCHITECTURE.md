# COA Editor - System Architecture

## 🏗️ System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     MGRC Order Tracking System                   │
│                     COA Editor Integration                       │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│   Browser    │◄────►│   Laravel    │◄────►│   Database   │
│              │      │   Backend    │      │              │
│  • PDF.js    │      │  • Routes    │      │  • Orders    │
│  • Fields    │      │  • Controller│      │  • Products  │
│  • Editor    │      │  • Models    │      │  • Pivot     │
└──────────────┘      └──────────────┘      └──────────────┘
```

---

## 📊 Data Flow Diagram

### Loading COA Editor

```
User Action: Clicks "COA" button
                    ↓
         Route: /orders/{order}/coa/{product}
                    ↓
         Controller: OrderController@showCOA
                    ↓
         Loads: order, product, orderProduct data
                    ↓
         Renders: coa-editor.blade.php (unified view)
                    ↓
         Browser: Loads PDF.js library
                    ↓
         PDF.js: Fetches PDF from /assets/pdf/
                    ↓
         Renders: PDF on canvas element
                    ↓
         JavaScript: Creates editable field overlays
                    ↓
         User sees: Interactive PDF with toggle edit mode
```

### Saving COA Data

```
User Action: Clicks "Save" or presses Ctrl+S
                    ↓
         JavaScript: Collects all field values
                    ↓
         AJAX POST: /orders/{order}/coa/{product}/save
                    ↓
         Headers: CSRF Token, Content-Type: JSON
                    ↓
         Controller: OrderController@saveCOA
                    ↓
         Validation: Check order, product exists
                    ↓
         Database: Update order_product pivot table
                    ↓
         Response: JSON {success: true/false}
                    ↓
         JavaScript: Shows success/error message
                    ↓
         User sees: "COA data saved successfully!"
```

---

## 🗂️ Component Breakdown

### Frontend Components

```
┌─────────────────────────────────────────────────────────┐
│              coa-editor.blade.php (Frontend)             │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 1. Header Section                                 │  │
│  │    • Page title                                   │  │
│  │    • Breadcrumb navigation                        │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 2. Controls Section (Sticky)                      │  │
│  │    • Back button                                  │  │
│  │    • Edit mode toggle                             │  │
│  │    • Save button                                  │  │
│  │    • Print button                                 │  │
│  │    • Download button                              │  │
│  │    • Page navigation (Previous/Next)              │  │
│  │    • Zoom controls (+/-)                          │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 3. PDF Container                                  │  │
│  │    ┌────────────────────────────────────────┐    │  │
│  │    │ Canvas Element (PDF Rendering)         │    │  │
│  │    │  • Rendered by PDF.js                  │    │  │
│  │    │  • Responsive sizing                   │    │  │
│  │    └────────────────────────────────────────┘    │  │
│  │    ┌────────────────────────────────────────┐    │  │
│  │    │ Overlay: Editable Fields               │    │  │
│  │    │  • Position: absolute                  │    │  │
│  │    │  • Z-index: 10                         │    │  │
│  │    │  • Positioned by x,y coordinates       │    │  │
│  │    └────────────────────────────────────────┘    │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 4. JavaScript Section                             │  │
│  │    • PDF.js initialization                        │  │
│  │    • Field rendering logic                        │  │
│  │    • Event handlers                               │  │
│  │    • AJAX save function                           │  │
│  │    • Print/download functions                     │  │
│  │    • Navigation functions                         │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Backend Components

```
┌─────────────────────────────────────────────────────────┐
│          OrderController.php (Backend)                   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ showCOA(Order $order, Product $product)          │  │
│  │ editCOA(Order $order, Product $product)          │  │
│  │ (Both use the same unified view)                 │  │
│  │                                                   │  │
│  │  1. Load relationships:                          │  │
│  │     $order->load(['customer', 'user', 'products'])│ │
│  │                                                   │  │
│  │  2. Get pivot data:                              │  │
│  │     $orderProduct = $order->products()           │  │
│  │         ->where('product_id', $product->id)      │  │
│  │         ->first()                                 │  │
│  │                                                   │  │
│  │  3. Validate:                                    │  │
│  │     • Product exists in order                    │  │
│  │     • COA is required for product                │  │
│  │                                                   │  │
│  │  4. Return view:                                 │  │
│  │     return view('orders.coa-editor', compact())  │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ saveCOA(Request $request, ...)                   │  │
│  │                                                   │  │
│  │  1. Get order product:                           │  │
│  │     $orderProduct = $order->products()           │  │
│  │         ->where('product_id', $product->id)      │  │
│  │         ->first()                                 │  │
│  │                                                   │  │
│  │  2. Update pivot table:                          │  │
│  │     $order->products()->updateExistingPivot(     │  │
│  │         $product->id,                             │  │
│  │         [                                         │  │
│  │             'batch_number' => $request->input(), │  │
│  │             'prepared_by' => $request->input(),  │  │
│  │             'qc_document_number' => ...          │  │
│  │         ]                                         │  │
│  │     )                                             │  │
│  │                                                   │  │
│  │  3. Return JSON response:                        │  │
│  │     return response()->json([                    │  │
│  │         'success' => true,                        │  │
│  │         'message' => '...'                        │  │
│  │     ])                                            │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Database Schema

```
┌────────────────────────────────────────────────────────┐
│                   order_product (Pivot)                 │
├──────────────────┬─────────────────────────────────────┤
│ Column           │ Type                                │
├──────────────────┼─────────────────────────────────────┤
│ id               │ bigint (PK)                         │
│ order_id         │ bigint (FK → orders)                │
│ product_id       │ bigint (FK → products)              │
│ quantity         │ integer                             │
│ batch_number     │ varchar(255) nullable  ◄── Saved   │
│ patient_name     │ varchar(255) nullable               │
│ qc_document_num  │ varchar(255) nullable  ◄── Saved   │
│ prepared_by      │ varchar(255) nullable  ◄── Saved   │
│ coa_required     │ boolean default(false)              │
│ is_ready         │ boolean default(false)              │
│ created_at       │ timestamp                           │
│ updated_at       │ timestamp                           │
└──────────────────┴─────────────────────────────────────┘
```

---

## 🔄 State Management

### Application States

```
┌─────────────────────────────────────────────────────┐
│                 Editor State Machine                 │
└─────────────────────────────────────────────────────┘

    [Initial Load]
          ↓
    [Loading PDF]
          ↓
    [PDF Rendered] ←─────────────┐
          ↓                       │
    [View Mode] ←→ [Edit Mode]   │
          ↓              ↓        │
    [Navigate] ←→ [Save Changes] │
          │              │        │
          └──────┬───────┘        │
                 ↓                │
            [Zoom Changed]        │
                 └────────────────┘

Edit Mode Enabled:
  • Fields become interactive
  • Background highlights visible
  • Can type in fields
  • Save button active

Edit Mode Disabled:
  • Fields read-only
  • Background transparent
  • Keyboard navigation enabled
  • Print-ready view
```

### JavaScript State Variables

```javascript
// Global State
let pdfDoc = null;           // PDF document object
let pageNum = 1;             // Current page number
let pageRendering = false;   // Rendering flag
let pageNumPending = null;   // Queued page to render
let scale = 1.5;             // Current zoom scale
let canvas = ...;            // Canvas element
let ctx = ...;               // Canvas context
let editMode = false;        // Edit mode state

// Field Configuration (Per Page)
const editableFields = {
    1: [...],  // Page 1 fields
    2: [...],  // Page 2 fields
    3: [...]   // Page 3 fields
};
```

---

## 🎨 Rendering Pipeline

### PDF Rendering Process

```
Step 1: Load PDF
┌──────────────────────────────────┐
│ pdfjsLib.getDocument(url)        │
│   .promise                        │
│   .then(pdf => pdfDoc = pdf)     │
└──────────────────────────────────┘
               ↓

Step 2: Get Page
┌──────────────────────────────────┐
│ pdfDoc.getPage(pageNum)          │
│   .then(page => {...})           │
└──────────────────────────────────┘
               ↓

Step 3: Calculate Viewport
┌──────────────────────────────────┐
│ const viewport =                 │
│   page.getViewport({scale})      │
│ canvas.height = viewport.height  │
│ canvas.width = viewport.width    │
└──────────────────────────────────┘
               ↓

Step 4: Render to Canvas
┌──────────────────────────────────┐
│ const renderContext = {          │
│   canvasContext: ctx,            │
│   viewport: viewport             │
│ }                                 │
│ page.render(renderContext)       │
└──────────────────────────────────┘
               ↓

Step 5: Overlay Fields
┌──────────────────────────────────┐
│ renderEditableFields(pageNum)    │
│   • Create input elements        │
│   • Position with absolute CSS   │
│   • Scale coordinates            │
│   • Append to wrapper            │
└──────────────────────────────────┘
```

### Field Positioning Algorithm

```javascript
// User specifies coordinates at scale 1.5 (base)
const field = {
    x: 450,  // Base coordinates
    y: 240,
    width: 250,
    height: 30
};

// Current scale (e.g., user zoomed to 2.0)
const currentScale = scale; // e.g., 2.0

// Calculate actual position
const actualX = (field.x * currentScale) / 1.5;
const actualY = (field.y * currentScale) / 1.5;
const actualWidth = (field.width * currentScale) / 1.5;
const actualHeight = (field.height * currentScale) / 1.5;

// Apply to element
input.style.left = actualX + 'px';
input.style.top = actualY + 'px';
input.style.width = actualWidth + 'px';
input.style.height = actualHeight + 'px';
```

---

## 🔐 Security Architecture

```
┌─────────────────────────────────────────────────────┐
│              Security Layers                         │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Layer 1: Authentication                            │
│  ├─ Middleware: ['auth']                            │
│  ├─ All routes require login                        │
│  └─ Session-based authentication                    │
│                                                      │
│  Layer 2: Authorization                             │
│  ├─ User must have access to order                  │
│  ├─ Product must be in order                        │
│  └─ COA must be required for product                │
│                                                      │
│  Layer 3: CSRF Protection                           │
│  ├─ Token generated: {{ csrf_token() }}             │
│  ├─ Included in AJAX headers                        │
│  └─ Validated by Laravel middleware                 │
│                                                      │
│  Layer 4: Input Validation                          │
│  ├─ Backend validation in controller                │
│  ├─ Database constraints                            │
│  └─ Type checking                                   │
│                                                      │
│  Layer 5: Error Handling                            │
│  ├─ Try-catch blocks                                │
│  ├─ Logging to storage/logs/laravel.log             │
│  └─ User-friendly error messages                    │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 📱 Responsive Design

```
┌─────────────────────────────────────────────────────┐
│              Breakpoint Strategy                     │
├─────────────────────────────────────────────────────┤
│                                                      │
│  Desktop (> 768px)                                  │
│  ┌──────────────────────────────────────────────┐  │
│  │ Controls: Horizontal layout                   │  │
│  │ PDF: Full width with margins                  │  │
│  │ Fields: Full size                             │  │
│  └──────────────────────────────────────────────┘  │
│                                                      │
│  Tablet (768px - 1024px)                            │
│  ┌──────────────────────────────────────────────┐  │
│  │ Controls: Stacked buttons                     │  │
│  │ PDF: Scaled to fit                            │  │
│  │ Fields: Proportionally scaled                 │  │
│  └──────────────────────────────────────────────┘  │
│                                                      │
│  Mobile (< 768px)                                   │
│  ┌──────────────────────────────────────────────┐  │
│  │ Controls: Full-width stacked                  │  │
│  │ PDF: Scrollable container                     │  │
│  │ Fields: Touch-optimized sizing                │  │
│  └──────────────────────────────────────────────┘  │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 🚀 Performance Optimizations

### Implemented

```
✓ Lazy Loading
  • PDF loads only when page is accessed
  • Fields render only for current page
  
✓ Event Delegation
  • Single event listener for all fields
  • Reduces memory footprint
  
✓ Debouncing
  • Zoom changes debounced
  • Prevents excessive re-renders
  
✓ Caching
  • PDF document cached after initial load
  • No re-fetch on page navigation
  
✓ Conditional Rendering
  • Fields only render when edit mode enabled
  • Markers removed when not needed
```

### Potential Enhancements

```
○ Web Workers
  • Offload PDF rendering to worker thread
  • Keep UI responsive during processing
  
○ Progressive Loading
  • Load PDF in chunks
  • Display pages as they become available
  
○ IndexedDB Caching
  • Cache PDFs in browser database
  • Offline access capability
  
○ Virtual Scrolling
  • Only render visible pages
  • Improve performance for large documents
```

---

## 🔌 Integration Points

### External Libraries

```
PDF.js (Mozilla)
├─ Version: 3.11.174
├─ Purpose: PDF rendering
├─ CDN: cdnjs.cloudflare.com
└─ Worker: pdf.worker.min.js

html2canvas
├─ Version: 1.4.1
├─ Purpose: Canvas to image conversion
├─ CDN: cdnjs.cloudflare.com
└─ Usage: Download functionality
```

### Laravel Integration

```
Models Used:
├─ Order
│  ├─ Relationships: products(), customer, user
│  └─ Attributes: id, order_date, status
├─ Product
│  ├─ Relationships: orders()
│  └─ Attributes: id, name, description
└─ Pivot: order_product
   ├─ Fields: batch_number, prepared_by, qc_document_number
   └─ Method: updateExistingPivot()

Routes:
├─ GET /orders/{order}/coa/{product}      (unified view)
├─ GET /orders/{order}/coa/{product}/edit (same unified view)
└─ POST /orders/{order}/coa/{product}/save

Controllers:
└─ OrderController
   ├─ showCOA()  (uses coa-editor view)
   ├─ editCOA()  (uses coa-editor view)
   └─ saveCOA()
```

---

## 🎯 Extension Points

### Easy to Add

```
1. New Field Types
   └─ Modify renderEditableFields() function
      • Add <select> for dropdowns
      • Add <textarea> for multiline
      • Add <input type="date"> for dates

2. New Save Fields
   └─ Modify saveCOA() in OrderController
      • Add field to updateExistingPivot()
      • Create migration if needed

3. Custom Validations
   └─ Add to saveCOA() method
      • Use Laravel validation rules
      • Return validation errors to frontend

4. Additional PDF Templates
   └─ Create new configuration sets
      • Different editableFields per template
      • Route parameter for template selection
```

---

## 🧪 Testing Architecture

### Test Coverage Points

```
Unit Tests:
├─ OrderController@showCOA
│  ├─ Valid order and product
│  ├─ Invalid order ID
│  ├─ Product not in order
│  └─ COA not required
├─ OrderController@editCOA
│  └─ Same tests as showCOA (uses unified view)
├─ OrderController@saveCOA
│  ├─ Valid data save
│  ├─ Invalid field values
│  ├─ Missing CSRF token
│  └─ Database error handling

Integration Tests:
├─ Full COA edit workflow
├─ Multiple page navigation
├─ Save and retrieve data
└─ Print/download functionality

Frontend Tests:
├─ PDF rendering
├─ Field positioning
├─ Edit mode toggle
├─ AJAX save
└─ Keyboard shortcuts
```

---

## 📊 Monitoring & Logging

### Log Points

```javascript
// Frontend (Console)
console.log('PDF loaded:', pdfDoc.numPages);
console.log('Page rendered:', pageNum);
console.log('Field values:', data);

// Backend (Laravel Log)
\Log::info('COA editor accessed', [
    'order_id' => $order->id,
    'product_id' => $product->id,
    'user_id' => auth()->id()
]);

\Log::error('Error saving COA data', [
    'order_id' => $order->id,
    'error' => $e->getMessage()
]);
```

---

## 🎨 Customization Map

```
Style Customization:
└─ coa-editor.blade.php
   ├─ .editable-field → Field appearance
   ├─ .controls → Button bar styling
   ├─ #pdf-container → PDF container styling
   └─ @media print → Print-specific styles

Configuration:
└─ coa-editor.blade.php
   ├─ const url → PDF file path
   ├─ const editableFields → Field positions
   ├─ let scale → Default zoom level
   └─ renderEditableFields() → Field rendering logic

Backend Logic:
└─ OrderController.php
   ├─ editCOA() → Access control
   ├─ saveCOA() → Save logic
   └─ Validation rules → Data validation
```

---

## ✅ System Health Checklist

```
✓ PDF.js library loaded
✓ Worker script accessible
✓ PDF file exists and readable
✓ Routes registered correctly
✓ Controller methods defined
✓ Database columns exist
✓ CSRF token present
✓ Authentication working
✓ Permissions configured
✓ Error logging enabled
✓ Browser compatibility tested
✓ Mobile responsive verified
```

---

**System Architecture Documentation**  
**Version:** 1.0  
**Date:** October 2025  
**Status:** Production Ready



