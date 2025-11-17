# Gift Catalog Feature - Technical Documentation

## Overview
The Gift Catalog feature extends the AerPoints loyalty system by allowing customers to redeem their points for physical and digital rewards (e.g., Amazon gift cards, vacuum cleaners, electronics) instead of only cart discounts. This provides an alternative redemption path that encourages point accumulation and enhances customer engagement.

## Version Information
- **Introduced in:** AerPoints v1.3.0
- **PrestaShop Compatibility:** 1.6.x
- **PHP Compatibility:** 7.4+

## Core Principles
- **Standalone System:** Completely separate from PrestaShop products and orders
- **Manual Fulfillment:** Admin-managed workflow with status tracking
- **Minimal Complexity:** Simple CRUD operations with basic inventory management
- **Customer Empowerment:** Customers can browse and redeem gifts, cancel pending orders

## Database Schema

### 1. Gift Catalog (`ps_aerpoints_gift`)
Stores available gifts that customers can redeem with points.

```sql
CREATE TABLE IF NOT EXISTS `{prefix}aerpoints_gift` (
  `id_aerpoints_gift` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `points_cost` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_aerpoints_gift`),
  KEY `active_position` (`active`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Fields:**
- `name`: Gift display name (e.g., "Amazon €50 Gift Card", "Dyson V11 Vacuum")
- `description`: Full description with features, terms, or usage instructions
- `points_cost`: Fixed point price (e.g., 5000 points)
- `quantity`: Available stock (0 = out of stock)
- `image`: Filename with extension stored in `/modules/aerpoints/views/img/gifts/`
- `active`: Whether gift is visible in catalog (1 = active, 0 = hidden)
- `position`: Display order in catalog (lower number = higher priority)

### 2. Gift Orders (`ps_aerpoints_gift_order`)
Tracks customer gift redemptions and fulfillment status.

```sql
CREATE TABLE IF NOT EXISTS `{prefix}aerpoints_gift_order` (
  `id_aerpoints_gift_order` int(11) NOT NULL AUTO_INCREMENT,
  `id_customer` int(11) NOT NULL,
  `id_aerpoints_gift` int(11) NOT NULL,
  `gift_name` varchar(255) NOT NULL,
  `points_spent` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `customer_notes` text,
  `admin_notes` text,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_aerpoints_gift_order`),
  KEY `customer_idx` (`id_customer`),
  KEY `status_idx` (`status`),
  KEY `date_idx` (`date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Fields:**
- `id_customer`: Foreign key to PrestaShop customer
- `id_aerpoints_gift`: Reference to redeemed gift (preserved even if gift deleted)
- `gift_name`: Snapshot of gift name at redemption time (preserves history)
- `points_spent`: Points deducted from customer balance
- `status`: Order fulfillment status (see constants below)
- `customer_notes`: Optional delivery instructions or preferences
- `admin_notes`: Internal notes for fulfillment team
- `date_add`: Redemption timestamp
- `date_upd`: Last status update timestamp

## PHP Constants & Enums

### Gift Order Status (AerpointsGiftOrder class)
```php
class AerpointsGiftOrder extends ObjectModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    public static function getValidStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ];
    }
    
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }
}
```

**Status Workflow:**
1. **Pending**: Customer redeemed gift, awaiting admin action
2. **Processing**: Admin is preparing/shipping the gift
3. **Completed**: Gift delivered to customer
4. **Cancelled**: Order cancelled (points & stock auto-restored)

### History Transaction Type
Extended `AerpointsHistory` class with new type:

```php
const TYPE_GIFT_REDEEMED = 'gift_redeemed';
```

Added to `getValidTypes()` and `getTypeLabels()` methods.

## Core Classes

### AerpointsGift
Manages gift catalog and inventory.

**Properties:**
```php
public $id_aerpoints_gift;
public $name;
public $description;
public $points_cost;
public $quantity;
public $image;
public $active;
public $position;
public $date_add;
public $date_upd;
```

**Key Methods:**
- `getActiveGifts($id_lang = null, $order_by = 'position', $order_way = 'ASC')`: Returns array of active gifts with stock
- `isAvailable()`: Checks if gift is active and in stock
- `decrementStock()`: Reduces quantity by 1 (transaction-safe)
- `incrementStock()`: Increases quantity by 1 (used on cancellation)
- `getImagePath()`: Returns full image URL or fallback to `no-image.png`

### AerpointsGiftOrder
Manages customer gift redemptions.

**Properties:**
```php
public $id_aerpoints_gift_order;
public $id_customer;
public $id_aerpoints_gift;
public $gift_name;
public $points_spent;
public $status;
public $customer_notes;
public $admin_notes;
public $date_add;
public $date_upd;
```

**Key Methods:**
- `getCustomerOrders($id_customer)`: Returns customer's gift order history
- `getAllOrders($filters = [])`: Admin method with status/date filtering
- `hasActiveOrders($id_gift)`: Checks if gift has pending/processing orders
- `updateStatus($new_status)`: Changes status with auto refund on cancellation
- `canBeCancelledByCustomer()`: Returns true only if status is pending

## Workflows

### 1. Gift Redemption Flow
```
1. Customer browses gift catalog page
2. Selects gift and clicks "Redeem Now"
3. System validates:
   - Customer is logged in
   - Gift is active and in stock
   - Customer has sufficient points
4. Transaction begins:
   a. Deduct points via AerpointsCustomer::removePoints()
   b. Create AerpointsGiftOrder with STATUS_PENDING
   c. Decrement gift stock
   d. Log to AerpointsHistory with TYPE_GIFT_REDEEMED
5. Customer receives confirmation message
6. Order appears in "My Gift Orders" with pending status
```

### 2. Admin Fulfillment Flow
```
1. Admin views gift orders in backend (AdminAerpointsGiftOrders)
2. Filters by status = "Pending"
3. Reviews customer details and notes
4. Updates status to "Processing" (begins fulfillment)
5. Ships/delivers gift to customer
6. Updates status to "Completed"
7. Adds admin notes for internal tracking
```

### 3. Order Cancellation Flow
```
Customer Cancellation (Pending Only):
1. Customer views "My Gift Orders"
2. Clicks "Cancel Order" button (only visible if pending)
3. Confirms cancellation in modal dialog
4. System automatically:
   a. Updates status to STATUS_CANCELLED
   b. Refunds points via AerpointsCustomer::addPoints()
   c. Restores gift stock via incrementStock()
   d. Logs refund to AerpointsHistory with TYPE_REFUND

Admin Cancellation (Any Status):
1. Admin opens gift order details
2. Changes status dropdown to "Cancelled"
3. Adds cancellation reason in admin notes
4. Same auto-refund and stock restore as above
```

### 4. Gift Deletion Protection
```
1. Admin attempts to delete gift from catalog
2. System checks hasActiveOrders($id_gift)
3. If pending/processing orders exist:
   - Deletion blocked with error message
   - Admin must complete/cancel orders first
4. If no active orders:
   - Gift deleted (soft delete recommended)
   - Historical orders preserve gift_name
```

## File Structure

```
/modules/aerpoints/
├── classes/
│   ├── AerpointsGift.php           # Gift catalog model
│   └── AerpointsGiftOrder.php      # Gift order model
├── controllers/
│   ├── admin/
│   │   ├── AdminAerpointsGiftsController.php       # Gift CRUD management
│   │   └── AdminAerpointsGiftOrdersController.php  # Order fulfillment interface
│   └── front/
│       └── gifts.php                # Customer gift catalog page
├── sql/
│   └── install.php                  # Updated with new tables
├── upgrade/
│   └── upgrade-1.3.0.php           # Migration script for v1.3.0
├── views/
│   ├── img/
│   │   └── gifts/                   # Gift images storage
│   │       └── no-image.png         # Placeholder image
│   ├── templates/
│   │   ├── admin/
│   │   │   ├── gifts/               # Gift management templates
│   │   │   └── gift_orders/         # Order management templates
│   │   ├── front/
│   │   │   └── gifts.tpl            # Gift catalog display
│   │   └── hook/
│   │       └── customer_account.tpl # Updated with gift link
│   ├── css/
│   │   ├── front.css               # Updated with gift styles
│   │   └── back.css                # Updated with admin styles
│   └── js/
│       ├── front.js                # Updated with gift JS
│       └── back.js                 # Updated with admin JS
└── docs/
    └── GIFTS_FEATURE.md            # This document
```

## Admin Interface

### Gift Management (AdminAerpointsGiftsController)
**Location:** Catalog → AerPoints Gifts

**Features:**
- List view with columns: Image, Name, Points Cost, Quantity, Active, Position
- Add/Edit form with fields:
  - Name (required, max 255 chars)
  - Description (WYSIWYG editor)
  - Points Cost (required, min 1)
  - Quantity (required, min 0)
  - Image (file upload: .jpg, .png, .gif, .webp)
  - Active (toggle switch)
  - Position (numeric for sorting)
- Bulk actions: Enable, Disable, Delete (with protection)
- Drag-and-drop position sorting
- Image preview thumbnails (50px)
- Color-coded quantity (red if < 5)

### Gift Order Management (AdminAerpointsGiftOrdersController)
**Location:** Orders → Gift Orders

**Features:**
- List view with columns: Order ID, Customer, Gift Name, Points, Status, Date
- Status badges with color coding:
  - Yellow (Pending)
  - Blue (Processing)
  - Green (Completed)
  - Gray (Cancelled)
- Filters:
  - Status dropdown
  - Date range picker
  - Customer search
- Detail view showing:
  - Customer information with link to profile
  - Gift details (name, points, redemption date)
  - Customer notes (readonly)
  - Admin notes (editable textarea)
  - Status update dropdown
  - Status change history timeline
- AJAX status updates without page reload
- Default sort: Most recent first

## Frontend User Experience

### Gift Catalog Page (`/module/aerpoints/gifts`)
**Access:** Customer Account → Gift Catalog link

**Features:**
- Responsive grid layout (3-4 columns desktop, 1 column mobile)
- Gift cards displaying:
  - Image (300x200px, object-fit: cover)
  - Name (truncated to 2 lines)
  - Description (truncated to 100 chars with "Read more")
  - Points cost badge with ★ icon
  - Stock status indicator
  - "Redeem Now" button
- Available points displayed at top of page
- Button states:
  - **Enabled**: Sufficient points + in stock
  - **Disabled (gray)**: Insufficient points (tooltip: "Need X more points")
  - **Disabled (red)**: Out of stock (tooltip: "Currently unavailable")
- AJAX redemption with confirmation modal
- Success message with order number
- Error handling with user-friendly messages

### My Gift Orders Tab (Customer Account)
**Location:** Customer Account → My Points → My Gift Orders tab

**Features:**
- Table view with columns:
  - Gift Image (60px thumbnail)
  - Gift Name
  - Points Spent (with ★ icon)
  - Status (color badge)
  - Redemption Date
  - Actions
- "Cancel Order" button:
  - Only visible for pending orders
  - Confirmation dialog: "Are you sure? Points will be refunded."
  - AJAX cancellation with instant feedback
- Empty state message: "You haven't redeemed any gifts yet. Browse catalog."
- Pagination for orders > 20

### Customer Account Link
**Location:** Customer Account menu

**Display:**
```
My Account
├── Order history
├── My credit slips
├── My addresses
├── My personal info
├── My Points ← Existing
└── Gift Catalog ← New (icon: 🎁)
```

### Points History Integration
Gift redemptions appear in main history with:
- Icon: 🎁 (gift emoji)
- Description: "Gift Redeemed: [Gift Name]"
- Points: Negative value (e.g., -5000)
- Type: gift_redeemed
- Date: Redemption timestamp

## Image Management

### Storage
- **Path:** `/modules/aerpoints/views/img/gifts/`
- **Naming:** `{id_aerpoints_gift}.{ext}` (e.g., `42.jpg`, `15.png`)
- **Formats:** JPG, PNG, GIF, WebP
- **Max Size:** 2MB (configurable)
- **Dimensions:** No fixed size (responsive CSS handles display)

### Placeholder
- **File:** `no-image.png` (400x300px neutral gray with "No Image" text)
- **Usage:** Automatic fallback when gift has no uploaded image
- **CSS:** `.gift-image { object-fit: cover; width: 100%; height: 200px; }`

### Upload Process
1. Admin selects image file in form
2. Server validates extension and size
3. Image moved to gifts directory with ID-based name
4. Database updated with filename + extension
5. Thumbnail generated for list view (50px)

## Security Considerations

### Input Validation
- Gift name: XSS prevention with `Tools::htmlentitiesUTF8()`
- Points cost: Integer validation, min 1, max 999999999
- Quantity: Integer validation, min 0
- Image: Extension whitelist, MIME type check, size limit
- Status: Validated against `getValidStatuses()` array

### Access Control
- **Frontend:** Customer must be logged in for gift catalog and redemption
- **Admin:** Standard PrestaShop admin authentication
- **Order Ownership:** Customers can only view/cancel their own orders
- **Deletion Protection:** Gifts with active orders cannot be deleted

### Data Integrity
- **Transaction Safety:** Redemption uses DB transaction (points + stock + order)
- **Rollback:** Any failure restores all changes
- **Idempotency:** Duplicate redemption clicks prevented via button disable
- **History Preservation:** `gift_name` field preserves name even if gift deleted
- **Stock Management:** Atomic decrement/increment prevents race conditions

### Error Handling
- Insufficient points: User-friendly message with exact shortage
- Out of stock: Clear unavailable indicator
- Gift not found: Graceful 404 redirect
- Database errors: Logged to PrestaShop error log
- Image upload failures: Keep existing image or use placeholder

## Performance Considerations

### Database Optimization
- Indexes on frequently queried fields:
  - `active + position` (catalog display)
  - `id_customer` (customer orders)
  - `status` (admin filtering)
  - `date_add` (sorting)
- Pagination for large datasets (20 items per page)
- JOIN optimization for order list queries

### Caching Strategy
- Active gifts cached for 5 minutes (catalog page)
- Customer point balance cached per session
- Admin lists use PrestaShop's built-in cache
- Image paths cached via CSS sprites consideration

### Frontend Performance
- Lazy loading for gift images (IntersectionObserver)
- CSS grid for responsive layout (no JS required)
- AJAX calls debounced (prevent double-clicks)
- Minimal JavaScript dependencies
- Compressed images (WebP preferred)

## Configuration Options

No new global configuration required. Feature uses existing AerPoints settings:
- `AERPOINTS_ENABLED`: Master switch (disables gifts if false)
- Points are managed via existing `AerpointsCustomer` class

Future consideration: Add optional settings
- `AERPOINTS_GIFT_MIN_POINTS`: Minimum points to access catalog
- `AERPOINTS_GIFT_CANCELLATION_HOURS`: Time limit for customer cancellation

## Migration & Upgrade

### Fresh Installation (v1.3.0+)
Tables created automatically via `sql/install.php` during module install.

### Upgrade from v1.1.0-1.2.x
Automatic upgrade via `upgrade/upgrade-1.3.0.php`:
1. Checks if tables already exist
2. Creates `ps_aerpoints_gift` table
3. Creates `ps_aerpoints_gift_order` table
4. Adds `TYPE_GIFT_REDEEMED` to history (no migration needed, constants only)
5. Creates `/views/img/gifts/` directory
6. Copies `no-image.png` placeholder
7. Installs admin tabs
8. Returns success/failure status

**Rollback:** Manual table drop if needed (no data loss, new feature only)

## Testing Checklist

### Unit Tests
- [ ] AerpointsGift::isAvailable() with various states
- [ ] AerpointsGift::decrementStock() edge cases (0 quantity)
- [ ] AerpointsGiftOrder::canBeCancelledByCustomer() all statuses
- [ ] AerpointsGiftOrder::updateStatus() with cancellation auto-refund
- [ ] Image upload validation (format, size, extension)

### Integration Tests
- [ ] Complete redemption flow (points deducted, stock decremented, order created)
- [ ] Cancellation refund flow (points restored, stock restored)
- [ ] Admin status update workflow
- [ ] Gift deletion protection with active orders
- [ ] Concurrent redemption attempts (race condition testing)

### User Acceptance Tests
- [ ] Customer can browse catalog and see available gifts
- [ ] Customer cannot redeem with insufficient points
- [ ] Customer cannot redeem out-of-stock gifts
- [ ] Customer can cancel pending order
- [ ] Customer cannot cancel processing/completed order
- [ ] Admin can add/edit/delete gifts
- [ ] Admin can update order status
- [ ] Admin cannot delete gift with active orders
- [ ] Points and stock correctly restored on cancellation
- [ ] History shows gift redemptions with correct icon

## Future Enhancements

### Phase 1.4 (Short Term)
- Email notifications for status changes
- Gift categories/tags for filtering
- Customer wishlist/favorites
- Gift popularity tracking

### Phase 2.0 (Long Term)
- Multi-image gallery per gift
- Gift availability scheduling (start/end dates)
- Customer reviews/ratings for redeemed gifts
- CSV bulk import for gift catalog
- Admin dashboard widget for pending orders
- Variable point pricing (discount tiers)
- Gift bundles (multiple items in one redemption)

## Support & Troubleshooting

### Common Issues

**Q: Gift images not displaying**
- Check `/views/img/gifts/` directory permissions (755)
- Verify image file exists with correct extension
- Clear browser cache
- Check for placeholder fallback

**Q: Customer cannot cancel order**
- Verify order status is "Pending" (only pending can be cancelled)
- Check JavaScript console for errors
- Verify customer ownership

**Q: Points not refunded on cancellation**
- Check PrestaShop error logs
- Verify `AerpointsCustomer::addPoints()` method exists
- Test in admin panel (admin cancellation)

**Q: Admin cannot delete gift**
- Check for pending/processing orders using that gift
- Complete or cancel active orders first
- Or use inactive toggle instead of deletion

### Debug Mode
Enable detailed logging by setting in `aerpoints.php`:
```php
const DEBUG_GIFTS = true;
```

Logs written to `/modules/aerpoints/logs/gifts_debug.log`

## License & Credits
- **Module:** AerPoints v1.3.0
- **Author:** AerDigital
- **License:** Proprietary
- **Documentation:** November 2025
