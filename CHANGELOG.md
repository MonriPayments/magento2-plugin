### 1.5.0
- Added additional payment methods for Monri WebPay Redirect
- Added option to allow installments for Monri components
- Increased timeout time to 10 seconds for Monri APIs
- Fixed issue on Monri Components where user could click Place Order button more than once

### 1.4.0
- WSPay tokenization
- WSPay capture improvements
- Bugfix WSPay won't redirect in newer Magento due to missing CSP rules

### 1.3.1
- Bugfix Magento 2.4.7 won't redirect due to missing CSP rules
- currency validator for WSPay 

### 1.3.0
- Monri WSPay implementation
- Monri WSPay transaction APIs for refund, capture from administration
- Monri WSPay callback
- Bugfix Components reading wrong debug configuration
- Bugfix Components checkout issues with HR symbols

### 1.2.1
- bump version because of Adobe Marketplace review
- fix PHP 8.2 deprecation errors

### 1.2.0
- Feature: Dynamically set callback, success and cancel URLs
  - Removed information panel showing which URLs to set in Monri dashboard
  - URLs set in Monri dashboard are now ignored
- Bugfix incorrect currency being used for order total
- Bugfix for order emails being sent at incorrect time
- Added support for locking orders in order to prevent race conditions during redirect

### 1.1.1
- Bugfix for Components using the wrong configuration

### 1.1.0
- Monri Components implementation
- PHP 7.1 minimum requirement (forced by Magento Marketplace)
- multiple functionality improvements

### 1.0.0 
- Initial module
