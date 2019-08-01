<?php

/**
* * INVITE: https://discordapp.com/oauth2/authorize?client_id=336631991849975808&scope=bot&permissions=1
* * INVITE: https://discordapp.com/oauth2/authorize?client_id={CLIENT_ID}&scope=bot&permissions=1
*
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
        $this->token = ''; // Bot token
        $this->domain = 'canary.discordapp.com'; // Domain
        $this->channel = ''; // Channel ID - invites user to specific channel (e.g. rules)
        $this->uri = 'https://'.$this->domain.'/api/v6/channels/'.$this->channel.'/invites'; // API path

        $this->expiry = 60; // Invite expiry
        $this->maxuses = 1; // Invite uses
        $this->tempmem = false; // Invite temporary membership

        $this->defaulticon = 'assets/default.png'; // Fallback icon for if the guild has no custom icon set

        $this->ua = 'DiscordBot (InviteBot, 1.0)'; // User agent

        $this->grcuri = 'https://www.google.com/recaptcha/api/siteverify'; // GRC (Google reCAPTCHA) API URL
        $this->grcseckey = ''; // Secret key for the GRC API

        $this->ip = $_SERVER['REMOTE_ADDR']; // Get user IP

        $this->ctimeout = 5; // Request timeout in seconds for cURL (all external backends) 

        $this->torchk = true; // Check if user is using a TOR exit node
    }
    
    /**
     * Sets JSON response header.
     * @param mixed $result String or array containing data for response.
     * @param bool $bypassenc Toggle JSON encoding.
     * 
     * @return string Returns JSON encoded response.
     */
    public function jsonres($result = '', $bypassenc = false) {
        # Halts script where JSON encoded response *needs* to kick in
        header('Content-Type: application/json; charset=utf-8'); // JSONify all the things!
        if (!$bypassenc) $result = json_encode($result);
		exit($result);
    }

    public const DEBUG = false; // Set to true if you need to bypass ALL server-side protection and see Discord's API JSON response only (should only be used in non-prod)

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
        # Verifies Google reCaptcha
        if (isset($_POST['g-recaptcha-response'])) {
            $grcpres = $_POST['g-recaptcha-response']; // get recaptcha response from form
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
            return strstr($html, 'The IP Address you entered matches one or more active Tor servers'); // If the IP is listed then it's true else it's false
        } else {
            return true; // If using IPv6 then bypass check
        }
    }

    /**
     * Create a Discord guild invite.
     *
     * @return string Response from the Discord API.
     */
    public function createInvite() {
		
		# Sends off a request to the API in attempt to create a new guild invite and fetches the response

        $postparams = [
            'max_age' => $this->expiry,
            'max_uses' => $this->maxuses,
            'temporary' => $this->tempmem,
            'unique' => true
        ]; // POST parameters
		
        $postparamsjson = json_encode($postparams); // Encode to JSON

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
if (!$core::DEBUG && !$initrequest->reCaptcha()) {
    $result = [
        'type' => 'err',
        'msg' => 'reCAPTCHA unsuccessful'
    ]; // Google reCAPTCHA
    $core->jsonres($result);
} else if (!$core::DEBUG && $initrequest->torExitNode()) {
    $result = [
        'type' => 'err',
        'msg' => 'TOR prohibited'
    ]; // TOR exit node check
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
    $core->jsonres($req, true); // Display Discord API response in a more easily debuggable format
}

# API RESPONSE HANDLING
if ($req != false && !isset($req->guild->name)) {
    if (isset($req->retry_after) && isset($req->message) && $req->message == 'You are being rate limited.') {
        # Rate limit handler
        $remaincount = ceil($req->retry_after / 1000); // Convert milliseconds to seconds
        $rmsg = 'Rate limited. Try again in '. $remaincount .' second(s)'; // Rate limited
    } elseif (isset($req->code) && $req->code == 0 && isset($req->message) && $req->message == '401: Unauthorized') {
        $rmsg = 'Bad token or API restricted'; // Invalid token or API restricted
    } elseif (isset($req->code) && $req->code == 0) {
        $rmsg = 'Error - '. $req->message; // Forgot what this one was
    } elseif (isset($req->code) && $req->code == 10003) {
        $rmsg = 'Invalid channel'; // Channel does not exist
    } elseif (isset($req->code) && $req->code == 50013) {
        $rmsg = 'Insufficient permissions. Is the bot on the guild with the "CREATE_INSTANT_INVITE" permission enabled?'; // Account not on guild or missing required permission
    } else {
        $rmsg = 'An unhandled error has occurred.'; // Fallback
    }
    $result = [
        'type' => 'err',
        'msg' => $rmsg
    ];
    $core->jsonres($result);
}

# Successful response

if ($req->guild->icon) {
    $icon = 'https://cdn.discordapp.com/icons/'.$req->guild->id.'/'.$req->guild->icon.'.png';
} else {
    $icon = $core->defaulticon; // fallback icon
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
