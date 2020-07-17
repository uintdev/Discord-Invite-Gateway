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
        global $token, $domain, $guild, $channel, $uri, $expiry, $maxuses, $tempmem, $defaulticon, $cliver, $ua, $grcuri, $grcseckey, $ip, $ctimeout;
        // Bot token
        $this->token = '';
        // Domain
        $this->domain = 'canary.discord.com';
        // Channel ID - invites user to specific channel (e.g. rules)
        $this->channel = '';
        // API path
        $this->uri = 'https://'.$this->domain.'/api/v6/channels/'.$this->channel.'/invites';

        // Invite expiry
        $this->expiry = 60;
        // Invite uses
        $this->maxuses = 1;
        // Invite temporary membership
        $this->tempmem = false;

        // Fallback icon for if the guild has no custom icon set
        $this->defaulticon = 'assets/default.png';

        // User agent
        $this->ua = 'DiscordBot (InviteBot, 1.0)';

        // GRC (Google reCAPTCHA) API URL
        $this->grcuri = 'https://www.google.com/recaptcha/api/siteverify';
        // Secret key for the GRC API
        $this->grcseckey = '';

        // Get user IP
        $this->ip = $_SERVER['REMOTE_ADDR'];

        // Request timeout in seconds for cURL (all external backends)
        $this->ctimeout = 5; 

        // Check if user is using a TOR exit node
        $this->torchk = true;
    }
    
    /**
     * Sets JSON response header.
     * @param mixed $result String or array containing data for response.
     * @param bool $bypassenc Toggle JSON encoding.
     * 
     * @return string Returns JSON encoded response.
     */
    public function jsonres($result = '', $bypassenc = false) {
        // JSONify the content
        header('Content-Type: application/json; charset=utf-8');
        if (!$bypassenc) $result = json_encode($result);
        // Halts script where JSON encoded response *needs* to kick in
		exit($result);
    }

    // Set to true if you need to bypass ALL server-side protection and see Discord's API JSON response only (should only be used in non-prod)
    public const DEBUG = false;

}

$core = new core;

class Initrequest extends core {

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
            $grcpres = $_POST['g-recaptcha-response'];
        } else {
            $grcpres = '';
        }

        $data = [
            'secret' => $this->grcseckey,
            'response' => $grcpres,
            'remoteip' => $this->ip
        ];

        $curlconfig = [
            CURLOPT_URL => $this->grcuri,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_CONNECTTIMEOUT => $this->ctimeout
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $curlconfig);
        $grcres = curl_exec($ch);
        curl_close($ch);

        $grcdata = json_decode($grcres);
        

        if (!isset($grcdata->success) || $grcdata->success !== true) {
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
        if (!$this->torchk) return false;
        
        $ipformat = strpos($this->ip, ':');

        if (!$ipformat) {
            $fields = [
                'QueryIP' => $this->ip
            ];

            $curlconfig = [
                CURLOPT_URL => 'https://torstatus.blutmagie.de/tor_exit_query.php',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_CONNECTTIMEOUT => $this->ctimeout
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $curlconfig);
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
        $postparams = [
            'max_age' => $this->expiry,
            'max_uses' => $this->maxuses,
            'temporary' => $this->tempmem,
            'unique' => true
        ];
        
        // Encode to JSON
        $postparamsjson = json_encode($postparams);

        $curlconfig = [
            CURLOPT_URL => $this->uri,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postparamsjson,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bot '.$this->token,
                'User-Agent: '.$this->ua
            ],
            CURLOPT_CONNECTTIMEOUT => $this->ctimeout
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $curlconfig);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}

$initrequest = new Initrequest;

# Protection - APIs

// Google reCAPTCHA
if (!$core::DEBUG && !$initrequest->reCaptcha()) {
    $result = [
        'type' => 'err',
        'msg' => 'reCAPTCHA unsuccessful'
    ];
    $core->jsonres($result);
} else if (!$core::DEBUG && $initrequest->torExitNode()) {
    // TOR exit node check
    $result = [
        'type' => 'err',
        'msg' => 'TOR prohibited'
    ];
    $core->jsonres($result);
}

$req = $initrequest->createInvite();

if (!$core::DEBUG && !$req) {
	$result = [
        'type' => 'err',
        'msg' => 'Unable to contact Discord API' // Request failure
    ];
	$core->jsonres($result);
} else if (!$core::DEBUG) {
    $fbdat = [
        'type' => 'err',
        'msg' => 'Malformed data received from Discord API'
    ];
    $req = json_decode($req) ?? $core->jsonres($fbdat); // Attempt to decode JSON and on failure throw out error
}


# DEBUGGER
if ($core::DEBUG) {
    // Display Discord API response in a more easily debuggable format
    $core->jsonres($req, true);
}

# API RESPONSE HANDLING
if ($req != false && !isset($req->guild->name)) {
    if (isset($req->retry_after) && isset($req->message) && $req->message == 'You are being rate limited.') {
        // Convert milliseconds to seconds
        $remaincount = ceil($req->retry_after / 1000);
        // Rate limited
        $rmsg = 'Rate limited. Try again in '. $remaincount .' second(s)';
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
    $core->jsonres($result);
}

# Successful response

if ($req->guild->icon) {
    // Guild icon
    $icon = 'https://cdn.discordapp.com/icons/'.$req->guild->id.'/'.$req->guild->icon.'.png';
} else {
    $icon = $core->defaulticon; // Fallback icon
}

$result = [
    'type' => 'info',
    'guild_icon' => $icon,
    'guild_name' => htmlentities($req->guild->name, ENT_QUOTES),
    'guild_channel' => htmlentities($req->channel->name, ENT_QUOTES),
    'invite_uri' => 'https://discord.gg/'.$req->code,
    'lifetime' => $req->max_age
];

$core->jsonres($result);
