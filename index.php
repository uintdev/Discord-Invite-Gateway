<?php
if (@!include('clientconfig.php')) exit('[ERR] File "clientconfig.php" not accessible -- missing?'); // If config file for the frontend to utilise is missing then halt execution

## ERROR HANDLING START ##
$err = false;

// Expected/required constants
$expectedConst = [
    'UI_TITLE',
    'UI_ICON',
    'UI_STYLE_PATH',
    'UI_THEME_COLOUR',
    'RECAPTCHA_SITE_KEY',
    'JQUERY_PATH',
    'JQUERY_SRI_INTEGRITY',
    'JQUERY_SRI_CROSSORIGIN'
];

// Missing constants container
$missingConst = [];
    
foreach ($expectedConst as $const) {
    // If the constant doesn't exist
    if (!isset(get_defined_constants(true)['user'][$const])) {
        array_push($missingConst, $const); // Add to array
        $err = true; // All constants must exist so halt execution later on
    }
}

// Joining the array with missing constants -- for the error message
$remainingConst = implode(', ', $missingConst);

// Missing constants? Halt
if ($err) exit('[ERR] Missing config: '.$remainingConst);

## ERROR HANDLING END ##
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php echo UI_TITLE ?></title>
<link rel="icon" href="<?php echo UI_ICON ?>">
<link rel="apple-touch-icon-precomposed" href="<?php echo UI_ICON ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="stylesheet" type="text/css" property="stylesheet" href="<?php echo UI_STYLE_PATH ?>" async>
<meta charset="utf-8">
<meta name="theme-color" content="<?php echo UI_THEME_COLOUR ?>">
<script src="<?php echo JQUERY_PATH ?>" integrity="<?php echo JQUERY_SRI_INTEGRITY ?>" crossorigin="<?php echo JQUERY_SRI_CROSSORIGIN ?>"></script>
</head>
<body>
<div class="header">
<?php echo UI_TITLE ?>
</div>
<div class="body">
<div class="initinfo show">
Press the button below to verify.
</div>
<div class="info">
<span class="infohead"></span>
<br>
<span class="infotxt"></span>
</div>
<div class="maininfoblk">
<div class="maininfo">Verifying ...</div>
</div>
<form class="verifyform" method="post">
<div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="gverify" data-theme="dark"></div>
<div class="buttoncontainer">
<input type="submit" class="verify" value="Join">
</div>
</form>
</div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script type="text/javascript">
var expiration = 0;
var expirationExcess = 0;
var ticker = 0;
var safeLifeTime = <?php echo LIFETIME_SAFE ?>;
function checkjson(jsonin) {
    try {
        var jsonVA = window.JSON.parse(jsonin);
        if (jsonVA && typeof jsonVA === "object" && jsonVA !== null) {
            return true;
        }
    } catch (e) {}
    return false;
}
function submitdata() {
    if ($('.initinfo').hasClass('show')) {
        $('.initinfo').html('');
        $('.initinfo').removeClass('show');
    }
    if ($('.info').hasClass('show')) {
        $('.infohead').html('');
        $('.infotxt').html('');
        $('.info').removeClass('show');
    }
    $('.verify').prop('disabled', true);
    $('.maininfoblk').addClass('showf');
	$('.verify').prop('value', '...');
    $.post('invite.php', $('.verifyform').serialize(), function (data, formdata) {
        if (data != '') {
            if (checkjson(data) === true) {
                jsonBit = window.JSON.parse(data);
				jsonType = jsonBit.type;
				if (jsonType == 'info') {
					jsonGuildIcon = jsonBit.guild_icon;
                    jsonGuildName = jsonBit.guild_name;
                    jsonGuildChannel = jsonBit.guild_channel;
					jsonInviteUri = jsonBit.invite_uri;
                    jsonLifeTime = jsonBit.lifetime;

                    jsonLifeTime = jsonLifeTime - safeLifeTime;

                    var htmlResponse = '';
                    htmlResponse += '<img src="' + jsonGuildIcon + '" class="guildicon">';
                    htmlResponse += '<br><br>';
                    htmlResponse += '<span class="guildname">' + jsonGuildName + '</span>';
                    htmlResponse += '<br>';
                    htmlResponse += '<span class="guildchannel">#' + jsonGuildChannel + '</span>';
                    htmlResponse += '<br><br>';
                    htmlResponse += '<span class="guildmsg guildmsglocked">True expiration: <span class="excessenrollife">' + safeLifeTime + 's</span></span>';
                    htmlResponse += '<br><br>';
                    htmlResponse += '<input type="button" class="invite" value="Enrol (' + jsonLifeTime + 's)" onclick="window.open(\'' + jsonInviteUri + '\', \'_blank\');">';
                    expiration = jsonLifeTime;
                    expirationExcess = safeLifeTime;

                    $('.maininfo').html(htmlResponse);
                    $('.verify').prop('value', 'Join');
                    $('.verify').prop('disabled', true);

                    inviteExpiration();

				} else if (jsonType == 'err') {
                    jsonMsg = jsonBit.msg;
                    $('.infohead').html('ERROR:');
                    $('.infotxt').html(jsonMsg);
                    $('.maininfoblk').removeClass('showf');
					$('.info').addClass('show');
                    $('.verify').prop('value', 'Join');
                    $('.verify').prop('disabled', false);
				} else {
					$('.infohead').html('DEBUG:');
                    $('.infotxt').html(data);
                    $('.maininfoblk').removeClass('showf');
                    $('.info').addClass('show');
                    $('.verify').prop('value', 'Join');
                    $('.verify').prop('disabled', false);
				}
            } else {
                $('.infohead').html('ERROR:');
                $('.infotxt').html('Malformed response.');
                $('.maininfoblk').removeClass('showf');
                $('.info').addClass('show');
                // malformed response
				$('.verify').prop('value', 'Join');
                $('.verify').prop('disabled', false);
            }
        } else {
            $('.infohead').html('ERROR:');
            $('.infotxt').html('Blank response.');
            $('.maininfoblk').removeClass('showf');
            $('.info').addClass('show');
			$('.verify').prop('value', 'Join');
            $('.verify').prop('disabled', false);
        }
    }, 'text')
    .fail(function() {
        $('.infohead').html('ERROR:');
        $('.infotxt').html('Communication with backend failed.');
        $('.info').addClass('show');
        $('.maininfoblk').removeClass('showf');
		$('.verify').prop('value', 'Join');
        $('.verify').prop('disabled', false);
    })
    grecaptcha.reset();
}
function gverify() {
    submitdata();
}
$(document).on('submit', '.verifyform', function(e) {
    grecaptcha.execute();
    return false;
});
function expirationTicker() {
    if (expiration > -1) {
        $('.invite').prop('value', 'Enrol (' + expiration + 's)');
        --expiration;
    } else {
        $('.invite').prop('value', 'Expired');
        $('.invite').prop('disabled', true);
        $('.guildmsg').removeClass('guildmsglocked');
        if (expirationExcess > -1) {
            $('.excessenrollife').html(expirationExcess + 's');
            --expirationExcess;
        } else {
            clearInterval(ticker);
            $('.guildmsg').html('To retry, use the \'join\' button.');
            $('.verify').prop('disabled', false);
        }
    }
}
function inviteExpiration() {
    expirationTicker();
    ticker = setInterval(expirationTicker, 1000);
}
</script>
</body>
</html>