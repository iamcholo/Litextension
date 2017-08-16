MAGENTO CONNECTOR INSTALLATION GUIDE

Notice: this does not apply for Shopify, Bigcommerce and Volusion stores.

1. Download the connector: http://demo3.litextension.com/magento_connector.zip

2. Upload the whole folder to your Source store root directory, so that we can get this exact link:
http://your-store.com/magento_connector/connector.php
(If your store is put in a inner directory, please just make sure to upload the file to correct location accordingly, for example, your source store is located at http://your-domain.com/shop/, please upload the connector at http://your-domain.com/shop/magento_connector/connector.php )
You can test this link by entering it into your browser, if you have placed the file correctly, you should see a message:
"Connector installed"

3. Check your PHP version by entering this page: http://your-store.com/magento_connector/info.php
- If php version is 5.x, then you are good to go.
- If php version is 4.x, please delete connector.php and rename "connector_php4.php" to "connector.php".
- If php version is 7.x, please delete connector.php and rename "connector_mysqli.php" to "connector.php".

4. For security, please open connector.php, find this very first line: define('LECM_TOKEN', '123456');
And change "123456" to another string, this will be used to enter to migration form and act like "password" to to prevent unauthorized access to your store.

5. Log on Magento store with Cart Migration module installed, enter the following info in migration form:
- Cart type: your source cart type, example: osCommerce
- Cart url: http://your-store.com/ (please enter source store's root url only)
- Token: 123456 ( or any thing else if you have changed the default token)

Click on "Next". It should take you to the next step.

MAGENTO CART MIGRATION FULL GUIDE

http://litextension.com/docs/magento-cart-migration-guide/

HELP & SUPPORT

For any question, please drop us a message at: http://litextension.com/contacts, or email us: contact@litextension.com
We are striving to response within 24 hours.

