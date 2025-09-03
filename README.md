# AerPoints - PrestaShop 1.6 Loyalty Points Module

A minimal and simple product-based loyalty points system for PrestaShop 1.6 that encourages customer engagement through product purchases.

## Features

- **Product-based points system**: Each product can have custom point earning/redemption values
- **Customer points management**: Track customer points balance and history
- **Point redemption**: Customers can redeem points for discounts during checkout
- **Admin configuration**: Easy-to-use admin interface for managing points settings
- **Customer account integration**: "My Points" page accessible from customer account
- **Order integration**: Points earned/redeemed automatically with order processing
- **Minimal and clean**: Simple codebase designed for PHP 7.4 compatibility

## Installation

1. **Upload Module**: Copy the `aerpoints` folder to `/modules/` directory in your PrestaShop installation
2. **Install Module**: Go to Admin Panel > Modules > Add New Module, find "AerPoints" and click Install
3. **Configure**: After installation, configure the module settings in the module configuration page

## Database Tables

The module creates 4 database tables:

- `ps_aerpoints_customer`: Customer points balances
- `ps_aerpoints_product`: Product-specific point configurations  
- `ps_aerpoints_pending`: Pending points awaiting order confirmation
- `ps_aerpoints_history`: Complete points transaction history

## Module Structure

```
aerpoints/
├── aerpoints.php                    # Main module file
├── ARCHITECTURE.md                  # Detailed architecture documentation
├── ajax.php                         # AJAX handler for cart redemption
├── test_module.php                  # Test file for basic functionality
├── classes/                         # Core business logic classes
│   ├── AerpointsCustomer.php       # Customer points management
│   ├── AerpointsProduct.php        # Product points configuration
│   ├── AerpointsPending.php        # Pending points logic
│   └── AerpointsHistory.php        # Points transaction history
├── controllers/front/              # Frontend controllers
│   └── customerpoints.php         # Customer points page controller
├── sql/                            # Database scripts
│   ├── install.php                 # Creates database tables
│   └── uninstall.php              # Removes database tables
└── views/                          # Templates and assets
    ├── templates/admin/
    │   └── configure.tpl           # Admin configuration page
    ├── templates/hook/
    │   ├── product_points.tpl      # Product points display
    │   ├── customer_account.tpl    # Customer account link
    │   └── cart_redemption.tpl     # Cart redemption interface
    ├── templates/front/
    │   └── customer_points.tpl     # Customer points history page
    └── css/
        ├── front.css               # Frontend styling
        └── back.css                # Admin styling
```

## Configuration

After installation, configure the module in Admin Panel > Modules > AerPoints:

- **Enable/Disable Module**: Turn the points system on/off
- **Point Value**: How much 1 point is worth in currency (default: 1.00)
- **Minimum Redemption**: Minimum points required for redemption (default: 100)
- **Allow Partial Payment**: Whether points can be used for partial order payment

## Product Configuration

For each product that should earn/cost points:

1. Go to Admin Panel > Catalog > Products
2. Edit the product
3. **Note**: Product point configuration will be added via admin interface in future versions
4. **Current**: Points must be configured via direct database insertion or future admin tools

Example product points configuration:
```sql
INSERT INTO ps_aerpoints_product (id_product, points_earn, points_buy, date_add, date_upd) 
VALUES (1, 10, 50, NOW(), NOW());
```

## Customer Experience

### Earning Points
- Customers earn points automatically when purchasing products with point values
- Points are awarded after order confirmation (when payment is received)
- Points appear in customer's "My Points" section

### Redeeming Points  
- During checkout, customers can redeem available points for discounts
- 1 point = 1 currency unit discount (configurable)
- Points are deducted immediately upon order placement
- Redemption history is tracked in customer's point history

### My Points Page
- Accessible from customer account menu
- Shows current points balance and value
- Displays complete points transaction history
- Explains how to earn more points

## Technical Details

### PHP Version
- Designed for PHP 7.4 compatibility
- Uses minimal dependencies and simple code structure
- Follows PrestaShop 1.6 coding standards

### Hooks Used
- `actionValidateOrder`: Process points earning/redemption on order
- `actionOrderStatusPostUpdate`: Complete/cancel pending points
- `displayProductButtons`: Show points info on product pages
- `displayShoppingCartFooter`: Show redemption options in cart
- `displayCustomerAccount`: Add "My Points" link to account menu
- `displayBackOfficeHeader`: Load admin CSS/JS
- `header`: Load frontend CSS/JS

### Security Features
- Customer authentication required for all point operations
- Input validation and sanitization
- Protected against SQL injection
- Session-based cart redemption handling

## Troubleshooting

### Module Not Installing
- Check PHP syntax with: `php -l modules/aerpoints/aerpoints.php`
- Verify all required files are present
- Check PrestaShop error logs

### Points Not Appearing
- Verify module is enabled in configuration
- Check if products have point values configured
- Ensure customer orders are confirmed/paid

### Database Issues
- Check if all 4 tables were created during installation
- Verify database user has CREATE/INSERT permissions
- Check PrestaShop database prefix matches module expectations

### Testing
Run the test file to verify basic functionality:
```bash
cd modules/aerpoints/
php test_module.php
```

## Customization

The module is designed to be easily customizable:

- **Templates**: Modify `.tpl` files in `views/templates/` for custom UI
- **Styling**: Update CSS files in `views/css/` for custom appearance  
- **Business Logic**: Extend classes in `classes/` directory for custom functionality
- **Hooks**: Add new hooks in main `aerpoints.php` file for additional integration points

## Support

For technical support or customization requests, refer to the ARCHITECTURE.md file for detailed implementation information.

## License

Academic Free License (AFL 3.0) - Same as PrestaShop core.
