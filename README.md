# Hmh_WishlistPriceWatchMessaging

Magento 2 module that listens for product price drops and dispatches customer notifications for wishlist items.

## Features

- Observes `catalog_product_save_after` and publishes price-drop payload to message queue.
- Consumes queue message and matches rows in `hmh_wishlist_price_watch`.
- Supports notification strategy by config:
  - Internal message
  - Email
  - Both
- Creates internal messages through `Hmh_InternalMessage`.
- Sends transactional emails using template `hmh_wishlist_price_drop_email_template`.
- Updates `updated_price` in `hmh_wishlist_price_watch` after successful drop handling.

## Dependencies

- `Hmh_WishlistPriceWatch`
- `Hmh_InternalMessage`

## Configuration

Admin path:

- `Stores > Configuration > HMH > Wishlist Price Watch Messaging > General`

Config nodes:

- `hmh_wishlistpricewatchmessaging/general/enabled`
- `hmh_wishlistpricewatchmessaging/general/notification_type`

## Email Template

- Template ID: `hmh_wishlist_price_drop_email_template`
- File: `view/frontend/email/wishlist_price_drop_notification.html`

## Installation

1. Enable module and run upgrade:
   - `bin/magento module:enable Hmh_WishlistPriceWatchMessaging`
   - `bin/magento setup:upgrade`
2. Flush cache:
   - `bin/magento cache:flush`
3. Run queue consumer:
   - `bin/magento queue:consumers:start hmh.wishlist.price.drop.consumer`
