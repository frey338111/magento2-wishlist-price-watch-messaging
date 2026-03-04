# Hmh_WishlistPriceWatchMessaging

Magento 2 module that listens for product price drops and dispatches customer notifications for wishlist items.

## Features

- Observes `catalog_product_save_after` and publishes price-drop payload to message queue.
- Consumes queue message and matches rows in `hmh_wishlist_price_watch`.
- Supports notification strategy by config:
  - Internal message
  - Email
- Supports multiple notification strategies via Admin multiselect.
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

## Add New Notification Strategy

To add a new notification channel, register it in DI and it will appear in Admin `notification_type` multiselect automatically.

1. Create a strategy class implementing:
   - `Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\NotificationStrategyInterface`
2. Add strategy mapping in:
   - `app/code/Hmh/WishlistPriceWatchMessaging/etc/di.xml`

Example:

```xml
<type name="Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationDispatcher">
    <arguments>
        <argument name="notificationStrategies" xsi:type="array">
            <item name="internal_message" xsi:type="object">Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\InternalMessageNotificationStrategy</item>
            <item name="email_notification" xsi:type="object">Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\EmailNotificationStrategy</item>
            <item name="sms_notification" xsi:type="object">Vendor\Module\Model\Notification\Strategy\SmsNotificationStrategy</item>
        </argument>
    </arguments>
</type>
```

`item name` is the strategy code stored in config and used by the dispatcher.

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
