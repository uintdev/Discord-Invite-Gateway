<?php
/**
* CONFIG FOR FRONTEND
*/

// Page title
define('UI_TITLE', 'Discord Invite Gateway');
// Icon for page (favicon, tab icon)
define('UI_ICON', '/img/favicon.png');
// Path to stylesheet to use
define('UI_STYLE_PATH', 'assets/style.css');
// Google Chrome's theme colour (mobile)
define('UI_THEME_COLOUR', '#1f1f1f');
// Google reCAPTCHA site key
define('RECAPTCHA_SITE_KEY', '');
// jQuery library
define('JQUERY_PATH', 'https://code.jquery.com/jquery-3.2.0.min.js');
// Sub-resource integrity hash (https://www.srihash.org/)
define('JQUERY_SRI_INTEGRITY', 'sha384-o9KO9jVK1Q4ybtHgJCCHfgQrTRNlkT6SL3j/qMuBMlDw3MmFrgrOHCOaIMJWGgK5');
// Sub-resource integrity cross-origin
define('JQUERY_SRI_CROSSORIGIN', 'anonymous');
// If visited the very last second, have there still be an invite page with spare time to join -- add on the amount of seconds mentioned set to the actual expiry
define('LIFETIME_SAFE', 30);

/*
/ Configuration for the invite script can be found in invite.php within the 'core' class. You'll know it when you see it.
/ Refer to README.md for more details.
*/
