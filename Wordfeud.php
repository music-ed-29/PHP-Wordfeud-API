<?php

/**
 * Wordfeud API client
 *
 * @author Arno Moonen <info@arnom.nl>
 * @author Timmy Sjöstedt <me at iostream dot se>
 * @version 0.2 2011-10-14
 */
class Wordfeud
{
    // Rule sets
    const RuleSetAmerican = 0;
    const RuleSetNorwegian = 1;
    const RuleSetDutch = 2;
    const RuleSetDanish = 3;
    const RuleSetSwedish = 4;
    const RuleSetEnglish = 5;
    const RuleSetSpanish = 6;
    const RuleSetFrench = 7;

    // Board types
    const BoardNormal = 0;
    const BoardRandom = 1;

    /**
     * @var string Wordfeud Session ID
     */
    private $sessionId;

    /**
     * @var boolean Debug Mode
     */
    private $debugMode;

    /**
     * @var boolean Send Accept-Encoding headers
     */
    private $acceptEncoding;

    /**
     * Init a new Wordfeud object.
     * Notice that all the parameters are optional.
     *
     * @param string $session_id Wordfeud Session ID
     * @param boolean $accept_encoding Set to false to disable any encoding of the HTTP response
     * @param boolean $debug_mode Set to true to output debug information on each request
     */
    public function __construct($session_id = NULL, $accept_encoding = true, $debug_mode = false)
    {
        if (is_string($session_id) && strlen($session_id) > 0) {
            $this->sessionId = $session_id;
        }

        if (is_null($accept_encoding) || $accept_encoding == true) {
            $this->acceptEncoding = true;
        } else {
            $this->acceptEncoding = false;
        }

        $this->debugMode = $debug_mode;
    }

    /**
     * Log in to Wordfeud using an email address and password
     *
     * @param string $email Email address
     * @param string $password Plain text password
     * @throws WordfuedLogInException If login fails
     */
    public function logInUsingEmail($email, $password)
    {
        $url = 'user/login/email';
        $data = array(
            'email' => $email,
            'password' => $this->getHash($password)
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudLogInException($res["content"]["type"]);
        }
    }

    /**
     * Log in to Wordfeud using an User ID and password
     *
     * @param type $id User ID
     * @param type $password Plain text password
     * @throws WordfuedLogInException If login fails
     */
    public function logInUsingId($id, $password)
    {
        $url = 'user/login/id';
        $data = array(
            'id' => intval($id),
            'password' => $this->getHash($password),
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudLogInException($res["content"]["type"]);
        }
    }

    /**
     * Get the Wordfeud Session ID of the current authenticated user.
     *
     * @return string Wordfeud Session ID
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Change the Wordfeud Session ID, in other words:
     * switch to another user.
     *
     * @param string $session_id Wordfeud Session ID
     * @return boolean True if the internal value has been changed; false otherwise
     */
    public function setSessionId($session_id)
    {

        if (is_string($session_id) && strlen($session_id) > 0) {
            $this->sessionId = $session_id;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unsets the internal Wordfeud Session ID.
     * You'll no longer be able to do any authenticated
     * calls until you login again.
     */
    public function logOut()
    {
        $this->session_id = NULL;
    }

    /**
     * Search for a Wordfeud user
     *
     * @param string $query Username or email address
     * @return array Search results
     */
    public function searchUser($query)
    {
        $url = 'user/search';

        $data = array(
            "username_or_email" => $query,
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]["result"];
        }
    }

    /**
     * Retrieve a list of your friends (relationships)
     *
     * @return array List of friends
     */
    public function getFriends()
    {
        $url = 'user/relationships';

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]->relationships;
        }
    }

    /**
     * Add a user to your list of friends (relationships)
     *
     * @param int $user_id ID of the User you wish to add
     * @param int $type Unknown?
     * @return array
     */
    public function addFriend($user_id, $type=0)
    {
        $url = 'relationship/create';

        $data = array(
            "id" => intval($user_id),
            "type" => intval($type),
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"];
        }
    }

    /**
     * Remove a user from your list of friends
     *
     * @param int $user_id ID of the User your wish to 'unfriend'
     */
    public function deleteFriend($user_id)
    {
        $url = 'relationship/' . intval($user_id) . "/delete";

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        }
    }

    /**
     * Search for a random opponent to play a game with.
     *
     * @param int $ruleset Ruleset for the game
     * @param mixed $board_type Board Type
     * @return array
     */
    public function inviteRandomOpponent($ruleset, $board_type=self::BoardRandom)
    {
        $url = "random_request/create";

        // TODO Test if an integer can be passed to the API
        if ($board_type === self::BoardNormal) {
            $board_type = "normal";
        } elseif ($board_type === self::BoardRandom) {
            $board_type = "random";
        }

        $data = array(
            "ruleset" => intval($ruleset),
            "board_type" => $board_type,
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"];
        }
    }

    /**
     * Upload a new avatar
     *
     * @param string $image_data Still need to figure out what this string contains?
     */
    public function uploadAvatar($image_data)
    {
        $url = "user/avatar/upload";

        // TODO Figure out how to generate this image data from an actual image

        $data = array(
            "image_data" => $image_data,
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        }
    }

    /**
     * Get all of the chat messages from a specific game
     *
     * @param int $gameID Game ID
     * @return array
     */
    public function getChatMessages($gameID)
    {
        $url = "game/" . intval($gameID) . "/chat";

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]->messages;
        }
    }

    /**
     * Send a chat message in a specific game
     *
     * @param int $gameID Game ID
     * @param string $message The message you wish to send
     * @return array
     */
    public function sendChatMessage($gameID, $message)
    {
        $url = "game/" . intval($gameID) . "/chat/send";

        $data = array(
            "message" => trim($message),
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"];
        }
    }

    /**
     * Gets the URL of the User's avatar
     *
     * @param int $user_id ID of the User
     * @param int $size Size (sizes known to work: 40, 60)
     * @return string
     */
    public function getAvatarUrl($user_id, $size)
    {
        return "http://avatars.wordfeud.com/" . intval($size) . "/" . intval($user_id);
    }

    /**
     * Create an account
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return int Your new User ID if successful
     */
    public function createAccount($username, $email, $password)
    {
        $url = 'user/create';
        $data = array(
            'username' => $username,
            'email' => $email,
            'password' => $this->getHash($password)
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]["id"];
        }
    }

    /**
     * Gets notifications!
     *
     * @return array An array with notifications
     */
    public function getNotifications()
    {
        $url = 'user/notifications';

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]["entries"];
        }
    }

    /**
     * Gets status! (Pending invites, current games, etc)
     *
     * @return array An array with statuses
     */
    public function getStatus()
    {
        $url = 'user/status';

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"];
        }
    }

    /**
     * Get games!
     *
     * @return array An array with games
     */
    public function getGames()
    {
        $url = 'user/games';

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]["games"];
        }
    }

    /**
     * Get one game
     *
     * @param int $gameID Game ID
     * @return array An array with game data
     */
    public function getGame($gameID)
    {
        $url = 'game/' . $gameID;

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]["game"];
        }
    }

    /**
     * Get the layout of a board
     *
     * @param int $boardID
     * @return array
     */
    public function getBoard($boardID)
    {
        $url = 'board/' . $boardID;

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        } else {
            return $res["content"]["board"];
        }
    }

    /**
     * Place a word on the board. This should be much easier.
     *
     * @param int $gameID
     * @param array $gameID
     * @param array $ruleset
     * @param array $tiles
     * @param array $words
     * @return Object
     */
    public function place($gameID, $ruleset, $tiles, $words)
    {
        // 'illegal_word', 'illegal_tiles'
        // TODO Have a look at the response

        $url = 'game/' . $gameID . '/move';

        $data = array(
            'move' => $tiles,
            'ruleset' => $ruleset,
            'words' => array($words),
        );

        $res = $this->execute($url, $data);

        return $res;
    }

    // 'not_your_turn'
    public function pass($gameID)
    {
        $url = 'game/' . $gameID . '/pass';

        $res = $this->execute($url);

        return $res;
    }

    // 'not_your_turn', 'game_over'
    public function resign($gameID)
    {
        $url = 'game/' . $gameID . '/resign';

        $res = $this->execute($url);

        if ($res["status"] == "success") {
            return true;
        }

        return $res["content"]["type"];
    }

    /**
     * Invite somebody to a game
     *
     * @return true|string 'duplicate_invite', 'invalid_ruleset', 'invalid_board_type', 'user_not_found'
     */
    public function invite($username, $ruleset = 0, $board_type = self::BoardRandom)
    {
        $url = 'invite/new';

        if ($board_type === self::BoardNormal) {
            $board_type = "normal";
        } elseif ($board_type === self::BoardRandom) {
            $board_type = "random";
        }

        $data = array(
            'invitee' => $username,
            'ruleset' => $ruleset,
            'board_type' => $board_type
        );

        $res = $this->execute($url, $data);

        return $res;
    }

    /**
     * Accept an invite.
     *
     * @param int $inviteID Invite ID
     */
    public function acceptInvite($inviteID)
    {
        // 'access_denied'
        $url = 'invite/' . $inviteID . '/accept';

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        }
    }

    /**
     * Reject an invite.
     *
     * @param int $inviteID Invite ID
     */
    public function rejectInvite($inviteID)
    {
        // 'access_denied'
        $url = 'invite/' . $inviteID . '/reject';

        $res = $this->execute($url);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        }
    }

    /**
     * Change your password
     *
     * @param string $password New password (plain text)
     */
    public function changePassword($password)
    {
        $url = 'user/password/set';
        $data = array(
            'password' => $this->getHash($password),
        );

        $res = $this->execute($url, $data);

        if ($res["status"] != "success") {
            throw new WordfeudException($res["content"]["type"]);
        }
    }

    /**
     * Hash the password for use with the API.
     *
     * @param string $password Plain text password
     * @return stromg SHA1 hash of the password with added salt
     */
    private function getHash($password)
    {
        return sha1($password . 'JarJarBinks9');
    }

    private function execute($url, $data = array())
    {
        // Additional headers
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "User-Agent: PHP Wordfeud API 0.2"
        );

        // Do we have a session id available?
        if (isset($this->sessionId) && strlen($this->sessionId) > 0) {
            $headers[] = "Cookie: sessionid=" . $this->sessionId;
        }

        // cURL Options
        $a = array(
            CURLOPT_URL => "http://game06.wordfeud.com/wf/" . $url . "/",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
        );

        // Use encoding if possible?
        if ($this->acceptEncoding) {
            $a[CURLOPT_ENCODING] = "";
        }

        $this->debugLog("cURL Options", $a);

        // Do some cURL magic!
        $c = curl_init();
        curl_setopt_array($c, $a);
        $response = curl_exec($c);
        $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);

        // Did the HTTP request did OK?
        if ($http_code != 200) {
            throw new WordfeudHttpException($httpCode);
        }

        // Split response content from the headers
        list($headers, $json) = explode("\r\n\r\n", $response, 2);

        $this->debugLog("Headers", $headers);
        $this->debugLog("Response", $json);

        // Check for a sessionid cookie
        if (preg_match("/^Set-Cookie:.*sessionid=([^;]*).*;/mi", $headers, $cookies) > 0) {


            $this->debugLog("Cookies", $cookies);

            if (isset($cookies[1])) {
                // Found a session id; save it
                $this->sessionId = $cookies[1];
            }
        }

        // JSON Decode
        $res = json_decode($json, true);
        if (!is_array($res)) {
            throw new WordfeudJsonException("Could not decode JSON");
        }
        $this->debugLog("Decoded JSON", $res);

        return $res;
    }

    private function debugLog($title, $data)
    {
        if ($this->debugMode === true) {
            $title = trim($title);
            $output = "\n\n" . $title . "\n";
            for ($i = 0; $i < strlen($title); $i++) {
                $output .= "-";
            }
            $output .= "\n" . ((is_string($data)) ? $data : var_export($data, true)) . "\n\n";

            echo $output;
        }
    }

}

/**
 * General exception for the Wordfeud class.
 * Also functions as the parent of all the other
 * exceptions this class might throw, so you can
 * easily catch them.
 */
class WordfeudException extends Exception
{

}

/**
 * This exception is thrown when the log in failed
 * or when we tried to make an authenticated call
 * but failed because of invalid credentials or an
 * invalid session id.
 */
class WordfeudLogInException extends WordfeudException
{

}

/**
 * Exceptions of this type are thrown whenever
 * something goes wrong with the HTTP request
 * or the response we get.
 */
class WordfeudClientException extends WordfeudException
{

}

/**
 * This exception is thrown if we the Wordfeud
 * API server returns a HTTP status code other
 * thane 200 OK.
 */
class WordfeudHttpException extends WordfeudClientException
{

}

/**
 * This exception is thrown when PHP is
 * unable to decode the JSON data we
 * have received from the API server.
 */
class WordfeudJsonException extends WordfeudClientException
{

}