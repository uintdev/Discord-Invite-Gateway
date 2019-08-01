<?php
/**
* CONFIG FOR FRONTEND
*/

define('UI_TITLE', 'Discord Invite Gateway'); // Page title
define('UI_ICON', '/img/favicon.png'); // Icon for page (favicon, tab icon)
define('UI_STYLE_PATH', 'assets/style.css'); // Path to stylesheet to use
define('UI_THEME_COLOUR', '#1f1f1f'); // Google Chrome's theme colour (mobile)
define('RECAPTCHA_SITE_KEY', ''); // Google reCAPTCHA site key
define('JQUERY_PATH', 'https://code.jquery.com/jquery-3.2.0.min.js'); // jQuery library
define('JQUERY_SRI_INTEGRITY', 'sha384-o9KO9jVK1Q4ybtHgJCCHfgQrTRNlkT6SL3j/qMuBMlDw3MmFrgrOHCOaIMJWGgK5'); // Sub-resource integrity hash (https://www.srihash.org/)
define('JQUERY_SRI_CROSSORIGIN', 'anonymous'); // Sub-resource integrity cross-origin
define('LIFETIME_SAFE', 30); // If visited the very last second, have there still be an invite page with spare time to join -- add on the amount of seconds mentioned set to the actual expiry

/*
/ Configuration for the invite script can be found in invite.php within the 'core' class. You'll know it when you see it.
/ Refer to README.md for more details.
*/