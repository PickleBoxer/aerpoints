# AerPoints Rules System Documentation

## Overview

The AerPoints Rules System is a simplified, module-based loyalty points promotion engine that allows store administrators to create flexible rules for awarding bonus points or multiplying points based on cart conditions.

**Key Features:**
- ✅ **Self-contained**: All code within the module (no core overrides)
- ✅ **Simple**: Focused on points only (no discounts, shipping, or gifts)
- ✅ **Flexible**: Multiple condition types with AND logic
- ✅ **Stackable**: Bonus rules add together, multipliers use the highest value
- ✅ **Limited**: Per-customer and total usage limits
- ✅ **Scheduled**: Date range validity
- ✅ **Prioritized**: Control rule application order

## Architecture

### Database Tables

#### 1. `ps_aerpoints_rules` (Main Configuration)
Stores the rule definition and action.

| Column | Type | Description |
|--------|------|-------------|
| `id_aerpoints_rule` | INT | Primary key |
| `name` | VARCHAR(255) | Admin-friendly rule name |
| `description` | TEXT | Optional admin notes |
| `action_type` | ENUM | 'bonus' or 'multiplier' |
| `action_value` | DECIMAL(10,2) | Points amount or multiplier |
| `priority` | INT | Higher values applied first |
| `date_from` | DATETIME | Rule valid from date |
| `date_to` | DATETIME | Rule valid until date |
| `quantity` | INT | Total usage limit (0 = unlimited) |
| `quantity_per_user` | INT | Per customer limit (0 = unlimited) |
| `active` | TINYINT(1) | Enable/disable toggle |
| `date_add` | DATETIME | Creation timestamp |
| `date_upd` | DATETIME | Last update timestamp |

#### 2. `ps_aerpoints_rules_conditions` (Flexible Conditions)
Stores conditions that must be met for rule to apply.

| Column | Type | Description |
|--------|------|-------------|
| `id_condition` | INT | Primary key |
| `id_aerpoints_rule` | INT | Foreign key to rule |
| `condition_type` | VARCHAR(50) | Type: 'cart_amount', 'product', 'category', etc. |
| `condition_value` | TEXT | Condition data (JSON or CSV) |
| `condition_operator` | VARCHAR(20) | Operator: 'gte', 'in', 'equals', etc. |

#### 3. `ps_aerpoints_rules_usage` (Usage Tracking)
Tracks rule applications for limit enforcement.

| Column | Type | Description |
|--------|------|-------------|
| `id_usage` | INT | Primary key |
| `id_aerpoints_rule` | INT | Foreign key to rule |
| `id_customer` | INT | Customer who used rule |
| `id_order` | INT | Order where rule was applied |
| `points_awarded` | INT | Actual points given |
| `date_add` | DATETIME | When rule was used |

## Rule Types

### Bonus Points (Fixed Amount)
Awards a fixed number of points when conditions are met.

**Example:**
```
Name: "High Value Order Bonus"
Action: Bonus Points
Value: 500
Condition: Cart amount >= 100€
Result: Customer gets base product points + 500 bonus points
```

### Points Multiplier (Percentage Increase)
Multiplies base product points by a factor when conditions are met.

**Example:**
```
Name: "Weekend Double Points"
Action: Points Multiplier
Value: 2.0
Condition: Order placed on Saturday/Sunday
Result: Customer gets base product points × 2
```

## Condition Types

### 1. Minimum Cart Amount
**Type:** `cart_amount`  
**Operator:** `gte` (greater than or equal)  
**Value:** `100.00` (amount in default currency, tax-included)

```php
// Customer cart must be at least 100€
condition_type: 'cart_amount'
condition_operator: 'gte'
condition_value: '100.00'
```

### 2. Specific Products
**Type:** `product`  
**Operator:** `in` (cart contains any) or `all` (cart contains all)  
**Value:** `5,12,45` (comma-separated product IDs)

```php
// Cart must contain at least one of these products
condition_type: 'product'
condition_operator: 'in'
condition_value: '5,12,45'
```

### 3. Specific Categories
**Type:** `category`  
**Operator:** `in` (cart contains products from these categories)  
**Value:** `3,8,15` (comma-separated category IDs)

```php
// Cart must contain products from electronics, books, or toys
condition_type: 'category'
condition_operator: 'in'
condition_value: '3,8,15'
```

### 4. Customer Groups
**Type:** `customer_group`  
**Operator:** `in` (customer belongs to one of these groups)  
**Value:** `1,3` (comma-separated group IDs)

```php
// Only VIP and wholesale customers
condition_type: 'customer_group'
condition_operator: 'in'
condition_value: '1,3'
```

### 5. First Order Only
**Type:** `first_order`  
**Operator:** `equals`  
**Value:** `1` (boolean)

```php
// Only customer's first order
condition_type: 'first_order'
condition_operator: 'equals'
condition_value: '1'
```

### 6. Specific Customers
**Type:** `customer`  
**Operator:** `in` (customer ID is in list)  
**Value:** `15,23,89` (comma-separated customer IDs)

```php
// Only these specific customers
condition_type: 'customer'
condition_operator: 'in'
condition_value: '15,23,89'
```

## Rule Processing Logic

### Execution Flow

1. **Order Placement**: Customer completes checkout
2. **Hook Trigger**: `hookActionValidateOrder` fires
3. **Rule Retrieval**: Get all active rules within date range
4. **Priority Sort**: Order rules by priority (DESC)
5. **Condition Check**: For each rule, validate all conditions (AND logic)
6. **Action Application**:
   - **Bonus**: Add to bonus_points accumulator
   - **Multiplier**: Track highest multiplier
7. **Points Calculation**: `(base_points × multiplier) + bonus_points`
8. **Usage Recording**: Log rule application for limit tracking
9. **Award Points**: Credit customer account

### Stacking Behavior

#### Bonus Points: **Stack (Additive)**
```
Rule A: +500 points (cart > 100€)
Rule B: +200 points (electronics category)
Cart: 150€ with electronics

Result: base_points + 500 + 200 = base_points + 700
```

#### Multipliers: **Highest Wins**
```
Rule A: 2.0x multiplier (VIP customers)
Rule B: 1.5x multiplier (weekend orders)
Customer: VIP, ordering on Sunday

Result: base_points × 2.0 (not 3.0 or 3.5)
```

#### Combined: **Multiply First, Then Add Bonus**
```
Base points: 300
Multiplier: 2.0x
Bonus: +500

Calculation: (300 × 2.0) + 500 = 1,100 points
```

### Priority System

Rules are processed in priority order (higher numbers first):

```
Priority 10: VIP 2x multiplier     ← Checked first
Priority 5:  Weekend 1.5x          ← Checked second
Priority 3:  High value +500 bonus ← Checked third
Priority 1:  First order +1000     ← Checked last
```

**Use Case**: Set high priority for restrictive rules, low priority for general promotions.

### Usage Limits

#### Total Quantity
Limit total applications across all customers.

```
quantity: 100
→ Rule applies to first 100 orders only
```

#### Per Customer Quantity
Limit applications per individual customer.

```
quantity_per_user: 1
→ Each customer can benefit once
```

**Example: First Order Bonus**
```
Name: "Welcome Bonus"
Action: Bonus +1000 points
Condition: First order
Limit: 1 per customer
→ Each new customer gets 1000 points on first order only
```

## Admin Interface

### Navigation
```
AerPoints Module → Points Rules Tab
```

### List View

| Name | Type | Value | Conditions | Usage | Dates | Status | Actions |
|------|------|-------|------------|-------|-------|--------|---------|
| High Value Bonus | Bonus | 500 ★ | Cart ≥ 100€ | 45/100 | Nov 1-30 | ✅ | Edit / Delete |
| VIP Multiplier | Multiplier | 2.0× | Group: VIP | 12/∞ | Always | ✅ | Edit / Delete |

### Add/Edit Form

#### General Tab
- **Name**: Admin reference name (required)
- **Description**: Optional notes for team
- **Active**: Enable/disable toggle
- **Priority**: 1-100, higher = applied first
- **Valid From**: Start date/time
- **Valid To**: End date/time

#### Conditions Tab
- **Minimum Cart Amount**: Enter amount in default currency
- **Specific Products**: Search and select products
- **Specific Categories**: Select from category tree
- **Customer Groups**: Select groups (Guest, Customer, VIP, etc.)
- **First Order Only**: Checkbox
- **Specific Customers**: Search and select customers

**Note**: All checked conditions must be met (AND logic)

#### Actions Tab
- **⚪ Bonus Points**: Fixed amount to award
- **⚪ Points Multiplier**: Multiply base points by this value

**Choose one action type per rule**

#### Limits Tab
- **Total Usage**: Maximum applications (0 = unlimited)
- **Per Customer**: Maximum per customer (0 = unlimited)

### Quick Actions
- **Toggle Status**: Enable/disable without editing
- **Duplicate**: Copy rule to create similar promotion
- **Delete**: Remove rule (usage history preserved)

## Frontend Display

### Cart Summary

When applicable rules exist, customers see a breakdown:

```
┌─────────────────────────────────────┐
│ ★ Points You'll Earn                │
├─────────────────────────────────────┤
│ Product Points:              250 ★  │
│ Multiplier Bonus (2x):      +250 ★  │
│ Bonus Points:               +500 ★  │
├─────────────────────────────────────┤
│ Total Points:              1,000 ★  │
│                                     │
│ Active promotions:                  │
│ • VIP Double Points                 │
│ • High Value Order Bonus            │
└─────────────────────────────────────┘
```

### Order Confirmation

Final points breakdown shown after order completion.

## Use Cases & Examples

### Use Case 1: Welcome Bonus
**Goal**: Attract new customers with first purchase reward

```yaml
Name: "Welcome Bonus"
Action: Bonus Points → 1000
Conditions:
  - First Order Only: ✓
Limits:
  - Per Customer: 1
Dates: Ongoing
```

**Result**: Every new customer gets 1000 bonus points on first order

---

### Use Case 2: High Value Incentive
**Goal**: Encourage larger cart values

```yaml
Name: "Spend More, Earn More"
Action: Bonus Points → 500
Conditions:
  - Minimum Cart Amount: 100€
Limits:
  - Total: 0 (unlimited)
  - Per Customer: 0 (unlimited)
Dates: Ongoing
```

**Result**: Every order over 100€ gets +500 bonus points

---

### Use Case 3: Category Promotion
**Goal**: Boost sales in specific category

```yaml
Name: "Electronics Double Points"
Action: Points Multiplier → 2.0
Conditions:
  - Categories: Electronics (ID: 5)
Limits: None
Dates: Black Friday Week
```

**Result**: Electronics purchases earn 2x points during event

---

### Use Case 4: VIP Perks
**Goal**: Reward loyal customers continuously

```yaml
Name: "VIP 1.5x Multiplier"
Action: Points Multiplier → 1.5
Conditions:
  - Customer Group: VIP (ID: 3)
Limits: None
Dates: Ongoing
```

**Result**: VIP customers always earn 50% more points

---

### Use Case 5: Flash Sale
**Goal**: Limited-time high-value promotion

```yaml
Name: "Weekend Flash: 1000 Points"
Action: Bonus Points → 1000
Conditions:
  - Minimum Cart Amount: 50€
Limits:
  - Total: 100 uses
  - Per Customer: 1
Dates: This Weekend Only
```

**Result**: First 100 customers with 50€+ carts get 1000 bonus points

---

### Use Case 6: Product Launch
**Goal**: Promote specific new product

```yaml
Name: "New Product Launch Bonus"
Action: Bonus Points → 300
Conditions:
  - Specific Products: iPhone 15 (ID: 142)
Limits:
  - Total: 50
  - Per Customer: 1
Dates: Launch Week
```

**Result**: First 50 customers buying iPhone 15 get 300 bonus points

## Technical Implementation

### Class Structure

#### AerpointsRule
```php
class AerpointsRule extends ObjectModel
{
    public static function getActiveRules($date = null);
    public function checkConditions($cart, $customer);
    public function calculatePoints($cart, $base_points);
    public function getRemainingUses($id_customer = null);
    public function isValid($cart, $customer);
}
```

#### AerpointsRuleCondition
```php
class AerpointsRuleCondition
{
    public static function checkCartAmount($cart, $operator, $value);
    public static function checkProducts($cart, $operator, $product_ids);
    public static function checkCategories($cart, $operator, $category_ids);
    public static function checkCustomerGroup($customer, $group_ids);
    public static function checkFirstOrder($customer);
    public static function checkSpecificCustomers($customer, $customer_ids);
}
```

### Hooks Used

- `hookActionValidateOrder`: Apply rules and award points
- `hookDisplayShoppingCartFooter`: Show points preview in cart
- `hookDisplayBackOfficeHeader`: Load admin CSS/JS

### Performance Considerations

1. **Indexed Queries**: Date and status fields indexed
2. **Caching**: Active rules cached per request
3. **Early Exit**: Inactive rules skipped immediately
4. **Condition Short-Circuit**: Failed condition stops checking
5. **Batch Recording**: Usage records inserted efficiently

## Migration & Upgrade

### Fresh Installation
Tables created automatically during module install.

### Existing Installation
Run upgrade to version 1.2.0:
1. Module detects new version
2. Executes `upgrade/upgrade-1.2.0.php`
3. Creates three new tables with safety checks
4. Installs new admin tab
5. No data loss, backwards compatible

### Rollback
If needed, simply:
1. Keep module at version 1.1.0
2. Rules feature won't be available
3. Existing points system continues working

## Best Practices

### Rule Naming
✅ **Good**: "Black Friday 2x Electronics", "New Customer Welcome 1000pts"  
❌ **Bad**: "Rule 1", "Test", "PROMO"

Use descriptive names that explain the promotion.

### Priority Assignment
- **90-100**: Exclusive VIP or time-sensitive rules
- **50-89**: Category or product-specific promotions
- **10-49**: General bonus campaigns
- **1-9**: Fallback or low-priority rules

### Date Ranges
- Use specific end dates for campaigns
- Set "far future" date for ongoing rules (e.g., 2030-12-31)
- Schedule seasonal promotions in advance

### Testing
1. Create test rule with specific customer condition (your test account)
2. Complete test order
3. Verify points calculation
4. Check usage tracking
5. Delete test rule when confirmed

### Limits
- **Flash Sales**: Set total limit (e.g., 100 uses)
- **Welcome Bonuses**: Set per-customer limit (1)
- **Ongoing VIP**: Set no limits
- **Budget Control**: Use total limit to cap promotion cost

## Troubleshooting

### Rule Not Applying

**Check:**
1. ✓ Rule is Active
2. ✓ Current date is within date range
3. ✓ All conditions are met
4. ✓ Usage limits not exceeded
5. ✓ Customer is logged in (for most conditions)

### Points Calculation Incorrect

**Verify:**
1. Base product points configured correctly
2. Multiple rules stacking as expected
3. Multiplier using highest (not adding)
4. Bonus points summing correctly
5. Check order history for applied rules

### Performance Issues

**Solutions:**
1. Limit active rules (< 20 recommended)
2. Use specific conditions (avoid "any customer")
3. Set reasonable date ranges
4. Archive old/expired rules
5. Monitor usage table size

## Security & Permissions

### Admin Access
Only employees with "AerPoints Rules" permission can:
- View rules list
- Create/edit rules
- Delete rules
- Toggle rule status

### Customer Visibility
Customers can see:
- Active promotions in cart (optional, configurable)
- Points breakdown at checkout
- Applied rules in order confirmation

Customers cannot:
- View rule conditions
- See total usage or limits
- Access admin rule details

## Future Enhancements

### Potential Features (Not Yet Implemented)
- [ ] Coupon code requirement
- [ ] Minimum product quantity condition
- [ ] Exclude sale products
- [ ] Time-of-day restrictions
- [ ] Combination logic (OR conditions)
- [ ] Rule templates
- [ ] A/B testing
- [ ] Export/import rules
- [ ] Rule performance analytics

## API Reference

### Get Active Rules
```php
$rules = AerpointsRule::getActiveRules();
// Returns array of AerpointsRule objects valid today
```

### Check Rule Validity
```php
$rule = new AerpointsRule($id_rule);
$cart = $context->cart;
$customer = $context->customer;

if ($rule->isValid($cart, $customer)) {
    // Rule applies
}
```

### Calculate Points with Rules
```php
$base_points = 300;
$bonus = 0;
$multiplier = 1.0;

foreach ($rules as $rule) {
    if ($rule->isValid($cart, $customer)) {
        if ($rule->action_type == 'bonus') {
            $bonus += $rule->action_value;
        } elseif ($rule->action_type == 'multiplier') {
            $multiplier = max($multiplier, $rule->action_value);
        }
    }
}

$total = ($base_points * $multiplier) + $bonus;
```

### Record Usage
```php
AerpointsRule::recordUsage(
    $id_rule,
    $id_customer,
    $id_order,
    $points_awarded
);
```

## Support & Contact

For questions or issues:
1. Check this documentation
2. Review ARCHITECTURE.md for system overview
3. Check module configuration
4. Contact AerDigital support

---

**Version**: 1.2.0  
**Last Updated**: November 2025  
**License**: Academic Free License (AFL 3.0)
