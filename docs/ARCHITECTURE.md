# AerPoints Module - Architecture & Design Document

## Overview
AerPoints is a loyalty points system module for PrestaShop 1.6 that allows customers to earn and redeem points on products. The system is product-centric, meaning each product has its own predefined point values for earning and purchasing.

## Module Information
- **Name:** aerpoints
- **Version:** 1.1.0
- **Author:** AerDigital
- **PrestaShop Compatibility:** 1.6.x
- **PHP Compatibility:** 7.4+

## Core Principles
- **Minimal Code:** Simple, clean implementation without unnecessary complexity
- **Product-Centric:** Points are configured per product, not category-based
- **Order State Management:** Points are pending until order completion
- **Flexible Configuration:** Admin can configure point values and redemption rules
- **Ratio-Based Calculation:** Support both fixed points and ratio-based dynamic calculation

## Database Schema

### 1. Customer Points Balance (`ps_aerpoints_customer`)
Stores the current point balance for each customer.

```sql
CREATE TABLE IF NOT EXISTS `{prefix}aerpoints_customer` (
  `id_aerpoints_customer` int(11) NOT NULL AUTO_INCREMENT,
  `id_customer` int(11) NOT NULL,
  `available_points` int(11) NOT NULL DEFAULT 0,
  `total_earned` int(11) NOT NULL DEFAULT 0,
  `total_redeemed` int(11) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_aerpoints_customer`),
  UNIQUE KEY `id_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Fields:**
- `available_points`: Current points available for redemption
- `total_earned`: Lifetime total points earned (for reporting)
- `total_redeemed`: Lifetime total points redeemed (for reporting)

### 2. Product Points Configuration (`ps_aerpoints_product`)
Defines point earning values for each product (fixed or ratio-based).

```sql
CREATE TABLE IF NOT EXISTS `{prefix}aerpoints_product` (
  `id_aerpoints_product` int(11) NOT NULL AUTO_INCREMENT,
  `id_product` int(11) NOT NULL,
  `points_earn` int(11) NOT NULL DEFAULT 0,
  `points_ratio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_aerpoints_product`),
  UNIQUE KEY `id_product` (`id_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Fields:**
- `points_earn`: Fixed points customer earns (overrides ratio if > 0)
- `points_ratio`: Points multiplier per €1 (tax-excluded). Used when points_earn = 0
- `active`: Whether points system is active for this product

**Calculation Logic:**
- If `points_earn > 0`: Use fixed points (e.g., 50 points per purchase)
- Else if `points_ratio > 0`: Calculate dynamically (e.g., 2.5× means €10 product = 25 points)
- Else: No points earned

**Formula:** `points = floor(price_tax_excl × points_ratio × quantity)`

### 3. Pending Points (`ps_aerpoints_pending`)
Manages points that are pending until order completion.

```sql
CREATE TABLE IF NOT EXISTS `{prefix}aerpoints_pending` (
  `id_aerpoints_pending` int(11) NOT NULL AUTO_INCREMENT,
  `id_customer` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `points_to_earn` int(11) NOT NULL DEFAULT 0,
  `points_redeemed` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_aerpoints_pending`),
  UNIQUE KEY `id_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Fields:**
- `points_to_earn`: Points that will be awarded when order completes
- `points_redeemed`: Points that were used for discount in this order
- `status`: Current status of the pending points (handled by PHP constants)

### 4. Points History (`ps_aerpoints_history`)
Complete transaction log of all point activities.

```sql
CREATE TABLE IF NOT EXISTS `{prefix}aerpoints_history` (
  `id_aerpoints_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_customer` int(11) NOT NULL,
  `id_order` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_add` datetime NOT NULL,
  PRIMARY KEY (`id_aerpoints_history`),
  KEY `id_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Transaction Types (handled by PHP constants):**
- `earned`: Points awarded for purchases
- `redeemed`: Points used for discounts
- `manual_add`: Admin manually added points
- `manual_remove`: Admin manually removed points
- `refund`: Points restored from cancelled/refunded orders

## Points Calculation System

### Overview
The module supports two calculation methods that can be configured per product:

1. **Fixed Points** - Static point value (e.g., "Earn 50 points")
2. **Ratio-Based** - Dynamic calculation based on price (e.g., "2.5 points per €1")

### Priority Logic
Fixed points **always override** ratio-based calculation:

```
if (points_earn > 0):
    return points_earn × quantity
else if (points_ratio > 0):
    return floor(price_tax_excl × points_ratio × quantity)
else:
    return 0 (no points)
```

### Ratio-Based Calculation

**Key Features:**
- Uses **tax-excluded price** as the base
- Ratio is a decimal multiplier (e.g., 2.5 = 2.5 points per €1)
- Result is **floored** to nearest integer (no partial points)
- Max ratio value: 100 (validated in admin and JS)

**Examples:**

| Fixed Points | Ratio | Price (tax-excl) | Qty | Calculation | Result |
|--------------|-------|------------------|-----|-------------|---------|
| 50 | 2.5 | €10.00 | 1 | Fixed override | **50 pts** |
| 0 | 2.5 | €10.00 | 1 | floor(10 × 2.5 × 1) | **25 pts** |
| 0 | 2.5 | €10.50 | 2 | floor(10.5 × 2.5 × 2) | **52 pts** |
| 0 | 0.5 | €10.00 | 1 | floor(10 × 0.5 × 1) | **5 pts** |
| 0 | 10 | €5.99 | 3 | floor(5.99 × 10 × 3) | **179 pts** |
| 0 | 0 | €10.00 | 1 | Both zero | **0 pts** |

**Implementation:**
```php
AerpointsProduct::calculateProductPoints($id_product, $price_tax_excl, $quantity);
```

### Admin Configuration

**Product Edit Page Fields:**
- **Fixed Points (Override)** - Integer, min: 0, no max
- **Points Ratio (per €1)** - Decimal, min: 0.00, max: 100.00, step: 0.01
- **Active** - Boolean toggle

**Real-time Preview:**
Shows calculation mode and example result for €10 product

**Product List Display:**
- Fixed: `50 ★ (fixed)`
- Ratio: `2.5× (ratio)`
- Disabled: `Disabled`
- None: `-`

## Module Configuration

### Global Settings
Stored in PrestaShop's `ps_configuration` table:

- **`AERPOINTS_ENABLED`** (boolean): Master enable/disable switch
- **`AERPOINTS_POINT_VALUE`** (int): How many points equal 1 euro (default: 100)
- **`AERPOINTS_MIN_REDEMPTION`** (int): Minimum points required for redemption (default: 100)
- **`AERPOINTS_PARTIAL_PAYMENT`** (boolean): Allow partial point payments (default: true)

## PrestaShop Hooks Integration

### 1. Order Management Hooks
- **`actionValidateOrder`**: Creates pending points entry when order is placed
- **`actionOrderStatusPostUpdate`**: Processes points when order status changes to completed/cancelled

### 2. Display Hooks
- **`displayProductButtons`**: Shows point information under product price
- **`displayShoppingCartFooter`**: Shows point redemption options in shopping cart
- **`displayCustomerAccount`**: Adds "My Points" link to customer account menu

### 3. Asset Hooks
- **`displayBackOfficeHeader`**: Loads admin CSS/JS files
- **`header`**: Loads frontend CSS/JS files

## File Structure

```
/modules/aerpoints/
├── aerpoints.php                    # Main module file
├── config.xml                       # Module configuration
├── index.php                        # Security file
├── ARCHITECTURE.md                  # This document
├── classes/
│   ├── AerpointsCustomer.php       # Customer points management
│   ├── AerpointsProduct.php        # Product points configuration
│   ├── AerpointsPending.php        # Pending points management
│   └── AerpointsHistory.php        # Points history management
├── controllers/
│   ├── admin/
│   │   ├── AdminAerpointsController.php        # Customer points admin
│   │   └── AdminAerpointsProductController.php # Product points admin
│   └── front/
│       └── account.php              # Customer points page
├── sql/
│   ├── install.php                  # Database installation
│   └── uninstall.php               # Database cleanup
├── views/
│   ├── templates/
│   │   ├── admin/
│   │   │   ├── configure.tpl        # Module configuration
│   │   │   ├── customer_points.tpl  # Customer points management
│   │   │   └── product_points.tpl   # Product points configuration
│   │   ├── front/
│   │   │   ├── account.tpl          # Customer points page
│   │   │   └── cart_points.tpl      # Cart redemption interface
│   │   └── hook/
│   │       ├── product_points.tpl   # Product page points display
│   │       └── cart_redemption.tpl  # Cart redemption form
│   ├── css/
│   │   ├── front.css               # Frontend styles
│   │   └── back.css                # Admin styles
│   └── js/
│       ├── front.js                # Frontend JavaScript
│       └── back.js                 # Admin JavaScript
└── translations/                    # Language files
```

## Core Classes

### PHP Enum Constants
Business logic constants defined in PHP classes instead of database enums:

#### Pending Points Status (AerpointsPending class)
```php
class AerpointsPending extends ObjectModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    public static function getValidStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ];
    }
    
    public function isValidStatus($status)
    {
        return in_array($status, self::getValidStatuses());
    }
}
```

#### Points History Types (AerpointsHistory class)
```php
class AerpointsHistory extends ObjectModel
{
    const TYPE_EARNED = 'earned';
    const TYPE_REDEEMED = 'redeemed';
    const TYPE_MANUAL_ADD = 'manual_add';
    const TYPE_MANUAL_REMOVE = 'manual_remove';
    const TYPE_REFUND = 'refund';
    
    public static function getValidTypes()
    {
        return [
            self::TYPE_EARNED,
            self::TYPE_REDEEMED,
            self::TYPE_MANUAL_ADD,
            self::TYPE_MANUAL_REMOVE,
            self::TYPE_REFUND
        ];
    }
    
    public function isValidType($type)
    {
        return in_array($type, self::getValidTypes());
    }
    
    public static function getTypeLabels()
    {
        return [
            self::TYPE_EARNED => 'Points Earned',
            self::TYPE_REDEEMED => 'Points Redeemed',
            self::TYPE_MANUAL_ADD => 'Manual Addition',
            self::TYPE_MANUAL_REMOVE => 'Manual Removal',
            self::TYPE_REFUND => 'Refund'
        ];
    }
}
```

### AerpointsCustomer
Manages customer point operations:
- `getPointBalance($id_customer)`: Get customer's available points
- `addPoints($id_customer, $points, $description)`: Add points to customer
- `removePoints($id_customer, $points, $description)`: Remove points from customer
- `getPointsHistory($id_customer)`: Get customer's point transaction history

### AerpointsProduct
Manages product point configuration:
- `getProductPoints($id_product)`: Get point configuration for product
- `setProductPoints($id_product, $earn, $buy)`: Configure points for product
- `getProductsWithPoints()`: Get all products with point configuration

### AerpointsPending
Manages pending points from orders:
- `createPendingEntry($id_order, $id_customer, $earn, $redeemed)`: Create pending points
- `completePendingPoints($id_order)`: Move pending points to customer balance
- `cancelPendingPoints($id_order)`: Handle cancelled order points
- `setStatus($status)`: Update status with validation using constants
- `getStatusLabel()`: Get human-readable status label

### AerpointsHistory
Manages point transaction history:
- `addHistoryEntry($id_customer, $points, $type, $description, $id_order)`: Log transaction
- `getCustomerHistory($id_customer)`: Get customer's history
- `getOrderHistory($id_order)`: Get points related to specific order
- `setType($type)`: Set transaction type with validation using constants
- `getTypeLabel()`: Get human-readable type label

## Point Workflow

### 1. Product Purchase Flow
```
1. Customer adds product to cart
2. Customer optionally applies points for discount
3. Order is placed → Points recorded in ps_aerpoints_pending
4. Order status changes to "Payment accepted" → Points moved to customer balance
5. Transaction logged in ps_aerpoints_history
```

### 2. Order Cancellation Flow
```
1. Order status changes to "Cancelled" or "Refunded"
2. Check ps_aerpoints_pending for this order
3. If points were redeemed, restore them to customer balance
4. If points were to be earned, remove from pending
5. Log refund transaction in history
```

### 3. Manual Point Adjustment
```
1. Admin accesses customer points management
2. Admin adds/removes points with description
3. Points balance updated immediately
4. Transaction logged in history
```

## Frontend User Experience

### Product Page
- Under product price: "🏆 Earn 50 points with this purchase"
- Only shown for products with configured points

### Shopping Cart
- Redemption section with input field
- "Apply X points for €Y discount"
- Real-time calculation of discount amount
- Display total points that will be earned from cart

### Customer Account
- "My Points" section showing:
  - Current available points
  - Total points earned/redeemed
  - Recent transactions
  - Pending points from recent orders

## Admin Interface

### Module Configuration
- Global enable/disable switch
- Point-to-currency conversion rate
- Minimum redemption amount
- Partial payment settings

### Product Points Management
- Bulk edit interface for setting points on multiple products
- Individual product point configuration
- Search and filter products by point configuration

### Customer Points Management
- View all customers with point balances
- Manually adjust customer points
- View detailed customer point history
- Export point data for reporting

## Security Considerations

### Input Validation
- All point values validated as positive integers
- Customer and product IDs validated against database
- Order state changes verified before processing points
- Status and type values validated against PHP constants before database operations

### Data Integrity
- Foreign key relationships maintained
- Transaction logging for audit trail
- Proper error handling for failed operations
- Enum-like validation through PHP constants ensures data consistency

### PHP Constants Approach Benefits
- **Flexibility**: Easy to add new statuses/types without database schema changes
- **Maintainability**: Constants defined in one place, easy to update
- **Type Safety**: Validation methods prevent invalid values
- **Localization**: Easy to provide translated labels for different languages
- **Database Agnostic**: Works with any database engine, not just MySQL enums

### Access Control
- Admin functions restricted to appropriate user roles
- Customer can only view their own points
- Point redemption limited to available balance

## Performance Considerations

### Database Optimization
- Indexed foreign keys for fast lookups
- Minimal queries using appropriate JOINs
- Efficient pagination for large datasets

### Caching Strategy
- Customer point balances cached during session
- Product point configuration cached
- History queries optimized with appropriate indexes

## Error Handling

### Common Scenarios
- Insufficient points for redemption
- Product without point configuration
- Order state conflicts
- Database connection issues

### Logging
- All errors logged to PrestaShop error log
- Point transactions logged for audit
- Failed operations logged with context

## Future Enhancement Possibilities

### Phase 2 Features (Not in Current Scope)
- Point expiration system
- Email notifications
- Category-based point rules
- Tier-based customer rewards
- Point transfer between customers
- API endpoints for external integrations

## Testing Strategy

### Unit Tests
- Test all class methods with various inputs
- Validate database operations
- Test point calculations

### Integration Tests
- Test complete order workflows
- Verify hook integrations
- Test admin interface operations

### User Acceptance Tests
- Customer point earning/redemption flows
- Admin configuration and management
- Edge cases and error scenarios

## Deployment Checklist

1. ✅ Database tables created
2. ✅ Module installed and configured
3. ✅ Hooks registered properly
4. ✅ Admin interface accessible
5. ✅ Frontend displays working
6. ✅ Point workflows tested
7. ✅ Error handling verified
8. ✅ Performance optimized

## Support and Maintenance

### Regular Tasks
- Monitor point transaction logs
- Review customer point balances
- Update product point configurations
- Performance monitoring

### Troubleshooting
- Check PrestaShop error logs
- Verify database table integrity
- Validate hook registrations
- Test point calculations

This architecture provides a solid foundation for a simple, effective loyalty points system that encourages customer engagement while remaining easy to manage and maintain.
