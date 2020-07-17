<?php

/**
* * INVITE: https://discord.com/oauth2/authorize?client_id={CLIENT_ID}&scope=bot&permissions=1
*
* NOTES:
* * You may need to enable developer mode in Discord in order to get an option to copy the ID's required for this tool to interact with the API with.
* * API rate limit is 5 requests per 10 seconds. If this is exceeded, you will be timed out for 10 seconds (handler below deals with this plus displays remaining time).
* * If you wish to use reCAPTCHA, please generate your secret and public key and use them.
* * This code is for PHP 7 and above. This can be made supported on 5.6, but it is advised to not use EOF versions of software.
* * cURL PHP module is a requirement.
*/

class core {

    public function __construct() {
        global $token, $domain, $guild, $channel, $uri, $expiry, $maxUses, $tempMem, $defaultIcon, $userAgent, $grcUri, $grcSecKey, $ip, $cTimeOut, $torChk;
        // Bot token
        $this->token = '';
        // Domain
        $this->domain = 'canary.discord.com';
        // Channel ID - invites user to specific channel (e.g. rules)
        $this->channel = '';
        // API path
        $this->uri = 'https://' . $this->domain . '/api/v6/channels/' . $this->channel . '/invites';

        // Invite expiry
        $this->expiry = 60;
        // Invite uses
        $this->maxUses = 1;
        // Invite temporary membership
        $this->tempMem = false;

        // Fallback icon for if the guild has no custom icon set
        $this->defaultIcon = 'assets/default.png';

        // User agent
        $this->userAgent = 'DiscordBot (InviteBot, 1.0)';

        // GRC (Google reCAPTCHA) API URL
        $this->grcUri = 'https://www.google.com/recaptcha/api/siteverify';
        // Secret key for the GRC API
        $this->grcSecKey = '';

        // Get user IP
        $this->ip = $_SERVER['REMOTE_ADDR'];

        // Request timeout in seconds for cURL (all external backends)
        $this->cTimeOut = 5; 

        // Check if user is using a TOR exit node
        $this->torChk = true;
    }
    
    /**
     * Sets JSON response header.
     * @param mixed $result String or array containing data for response.
     * @param bool $bypassEnc Toggle JSON encoding.
     * 
     * @return string Returns JSON encoded response.
     */
    public function jsonRes($result = '', $bypassEnc = false) {
        // JSONify the content
        header('Content-Type: application/json; charset=utf-8');
        if (!$bypassEnc) $result = json_encode($result);
        // Halts script where JSON encoded response *needs* to kick in
		exit($result);
    }

    // Set to true if you need to bypass ALL server-side protection and see Discord's API JSON response only (should only be used in non-prod)
    public const DEBUG = false;

}

$core = new core;

class initRequest extends core {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Requests Google reCAPTCHA with user response for validation.
     *
     * @return bool Indicate if user has passed or failed the reCAPTCHA.
     */
    public function reCaptcha() {
        if (isset($_POST['g-recaptcha-response'])) {
            // Get reCaptcha response from form
            $grcPRes = $_POST['g-recaptcha-response'];
        } else {
            $grcPRes = '';
        }

        $data = [
            'secret' => $this->grcSecKey,
            'response' => $grcPRes,
            'remoteip' => $this->ip
        ];

        $curlConfig = [
            CURLOPT_URL => $this->grcUri,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_CONNECTTIMEOUT => $this->cTimeOut
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $curlConfig);
        $grcRes = curl_exec($ch);
        curl_close($ch);

        $grcData = json_decode($grcRes);
        
        if (!isset($grcData->success) || $grcData->success !== true) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determine if the client is using a tor exit node.
     *
     * @return bool Indication of if the user is using a tor exit node.
     */
    public function torExitNode() {
        if (!$this->torChk) return false;
        
        $ipFormat = strpos($this->ip, ':');

        if (!$ipFormat) {
            $fields = [
                'QueryIP' => $this->ip
            ];

            $curlConfig = [
                CURLOPT_URL => 'https://torstatus.blutmagie.de/tor_exit_query.php',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_CONNECTTIMEOUT => $this->cTimeOut
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $curlConfig);
            $html = curl_exec($ch);
            curl_close($ch);

            // If the IP is listed then it's true else it's false
            return strstr($html, 'The IP Address you entered matches one or more active Tor servers');
        } else {
            // If using IPv6 then bypass check
            return true;
        }
    }

    /**
     * Create a Discord guild invite.
     *
     * @return string Response from the Discord API.
     */
    public function createInvite() {
		
		# Sends off a request to the API in attempt to create a new guild invite and fetches the response

        // POST parameters
        $postParams = [
            'max_age' => $this->expiry,
            'max_uses' => $this->maxUses,
            'temporary' => $this->tempMem,
            'unique' => true
        ];
        
        // Encode to JSON
        $postParamsJson = json_encode($postParams);

        $curlConfig = [
            CURLOPT_URL => $this->uri,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postParamsJson,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bot '.$this->token,
                'User-Agent: '.$this->userAgent
            ],
            CURLOPT_CONNECTTIMEOUT => $this->cTimeOut
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}

$initRequest = new initRequest;

# Protection - APIs

// Google reCAPTCHA
if (!$core::DEBUG && !$initRequest->reCaptcha()) {
    $result = [
        'type' => 'err',
        'msg' => 'reCAPTCHA unsuccessful'
    ];
    $core->jsonRes($result);
} else if (!$core::DEBUG && $initRequest->torExitNode()) {
    // TOR exit node check
    $result = [
        'type' => 'err',
        'msg' => 'TOR prohibited'
    ];
    $core->jsonRes($result);
}

$req = $initRequest->createInvite();

if (!$core::DEBUG && !$req) {
	$result = [
        'type' => 'err',
        'msg' => 'Unable to contact Discord API' // Request failure
    ];
	$core->jsonRes($result);
} else if (!$core::DEBUG) {
    $fbdat = [
        'type' => 'err',
        'msg' => 'Malformed data received from Discord API'
    ];
    $req = json_decode($req) ?? $core->jsonRes($fbdat); // Attempt to decode JSON and on failure throw out error
}

# DEBUGGER
if ($core::DEBUG) {
    // Display Discord API response in a more easily debuggable format
    $core->jsonRes($req, true);
}

# API RESPONSE HANDLING
if ($req != false && !isset($req->guild->name)) {
    if (isset($req->retry_after) && isset($req->message) && $req->message == 'You are being rate limited.') {
        // Convert milliseconds to seconds
        $remainCount = ceil($req->retry_after / 1000);
        // Rate limited
        $rmsg = 'Rate limited. Try again in '. $remainCount .' second(s)';
    } elseif (isset($req->code) && $req->code == 0 && isset($req->message) && $req->message == '401: Unauthorized') {
        // Invalid token or API restricted
        $rmsg = 'Bad token or API restricted';
    } elseif (isset($req->code) && $req->code == 0) {
        // Forgot what this one was
        $rmsg = 'Error - '. $req->message;
    } elseif (isset($req->code) && $req->code == 10003) {
        // Channel does not exist
        $rmsg = 'Invalid channel';
    } elseif (isset($req->code) && $req->code == 50013) {
        // Account not on guild or missing required permission
        $rmsg = 'Insufficient permissions. Is the bot on the guild with the "CREATE_INSTANT_INVITE" permission enabled?';
    } else {
        // Fallback
        $rmsg = 'An unhandled error has occurred.';
    }
    $result = [
        'type' => 'err',
        'msg' => $rmsg
    ];
    $core->jsonRes($result);
}

# Successful response

if ($req->guild->icon) {
    // Guild icon
    $icon = 'https://cdn.discordapp.com/icons/'.$req->guild->id.'/'.$req->guild->icon.'.png';
} else {
    $icon = $core->defaultIcon; // Fallback icon
}

$result = [
    'type' => 'info',
    'guild_icon' => $icon,
    'guild_name' => htmlentities($req->guild->name, ENT_QUOTES),
    'guild_channel' => htmlentities($req->channel->name, ENT_QUOTES),
    'invite_uri' => 'https://discord.gg/'.$req->code,
    'lifetime' => $req->max_age
];

$core->jsonRes($result);
