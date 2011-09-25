<?php
/**
 * Wordfeud API interface
 * 
 * @author Timmy Sjöstedt <me at iostream dot se>
 * @version 0.1 2011-09-25
 */
class Wordfeud
{
	// Rule sets
	const American	= 0;
	const Norwegian	= 1;
	const Dutch		= 2;
	const Danish	= 3;
	const Swedish	= 4;
	const English	= 5;
	const Spanish	= 6;
	const French	= 7;

	// Board types
	const Normal	= 0;
	const Random	= 1;

	/**
	 * A variable containing the outgoing headers from the latest request, for debug purposes or whatnot.
	 */
	public $header;

	private $cookiejar;

	public function __construct($cookiejar = NULL)
	{
		if (is_null($cookiejar))
		{
			$this->cookiejar = tmpfile();
		} else {
			$this->cookiejar = $cookiejar;
		}
	}

	/**
	 * Check if an email address is already taken
	 *
	 * @param string $email
	 * @return boolean|NULL TRUE/FALSE if the address exists or not, NULL if we're not sure (= some error type not implemented yet)
	 */
	public function EmailExists($email)
	{
		$res = $this->Login($email, 'herpderp');

		if ($res === TRUE)
		{
			return TRUE;
		}

		switch ($res)
		{
		case 'wrong_password':
			return TRUE;
		case 'unknown_email':
			return FALSE;
		}

		return NULL;
	}

	/**
	 * Log in to Wordfeud so you can do stuff.
	 *
	 * @param string $email
	 * @param string $password
	 * @return TRUE|string TRUE if successful, otherwise a string with error type - 'unknown_email','wrong_password'
	 */
	public function Login($email, $password)
	{
		$url = 'user/login/email';
		$data = array(
			'email'		=> $email,
			'password'	=> $this->getHash($email, $password)
		);

		$res = $this->execute($url, $data);

		if ($res->status == 'success')
		{
			return TRUE;
		}

		return $res->content->type;
	}

	/**
	 * Destroys your current session
	 */
	public function Logout()
	{
		$this->cookiejar = tmpfile();
	}

	/**
	 * Create an account
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $password
	 * @return int|string Your new User ID if successful, otherwise a string with some error type - 'email_taken'
	 */
	public function CreateAccount($username, $email, $password)
	{
		$url = 'user/create';
		$data = array(
			'username'	=> $username,
			'email'		=> $email,
			'password'	=> $this->getHash($email, $password)
		);

		$res = $this->execute($url, $data);

		if ($res->status == 'success')
		{
			return $res->content->id;
		}

		return $res->content->type;
	}

	/**
	 * Gets notifications!
	 *
	 * @return array|string An array with notifications or if unsuccessful a string with some error type
	 */
	public function GetNotifications()
	{
		$url = 'user/notifications';

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return $res->content->entries;
		}

		return $res->content->type;
	}

	/**
	 * Gets status! (Pending invites, current games, etc)
	 *
	 * @return array|string An array with statuses or if unsuccessful a string with some error type
	 */
	public function GetStatus()
	{
		$url = 'user/status';

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return $res->content;
		}

		return $res->content->type;
	}

	/**
	 * Get games!
	 *
	 * @return array|string An array with games or if unsuccessful a string with some error type
	 */
	public function GetGames()
	{
		$url = 'user/games';

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return $res->content->games;
		}

		return $res->content->type;
	}

	/**
	 * Get one game
	 *
	 * @param int $gameID The ID of the game, returned from for example GetGames()
	 * @return array|string An array with game data or if unsuccessful a string with some error type
	 */
	public function GetGame($gameID)
	{
		$url = 'game/'. $gameID;

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return $res->content->game;
		}

		return $res->content->type;
	}

	public function GetBoard($boardID)
	{
		$url = 'board/'. $boardID;

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return $res->content->board;
		}

		return $res->content->type;
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
	public function Place($gameID, $ruleset, $tiles, $words)
	{
		// 'illegal_word', 'illegal_tiles'

		$url = 'game/'. $gameID .'/move';

		$data = array(
			'move'		=> $tiles,
			'ruleset'	=> $ruleset,
			'words'		=> array($words),
		);

		$res = $this->execute($url, $data);

		return $res;
	}

	// 'not_your_turn'
	public function Pass($gameID)
	{
		$url = 'game/'. $gameID .'/pass';

		$res = $this->execute($url);

		return $res;
	}

	// 'not_your_turn', 'game_over'
	public function Resign($gameID)
	{
		$url = 'game/'. $gameID .'/resign';

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return TRUE;
		}

		return $res->content->type;
	}

	/**
	 * Invite somebody to a game
	 *
	 * @return TRUE|string 'duplicate_invite', 'invalid_ruleset', 'invalid_board_type', 'user_not_found'
	 */
	public function Invite($username, $ruleset = 0, $board_type = 'random')
	{
		$url = 'invite/new';
		$data = array(
			'invitee'		=> $username,
			'ruleset'		=> $ruleset,
			'board_type'	=> $board_type
		);
		
		$res = $this->execute($url, $data);

		return $res;
	}

	// 'access_denied'
	public function AcceptInvite($inviteID)
	{
		$url = 'invite/'. $inviteID .'/accept';

		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return TRUE;
		}

		return $res->content->type;
	}

	// 'access_denied'
	public function RejectInvite($inviteID)
	{
		$url = 'invite/'. $inviteID .'/reject';
		
		$res = $this->execute($url);

		if ($res->status == 'success')
		{
			return TRUE;
		}

		return $res->content->type;
	}

	public function ChangePassword($password)
	{
		$url = 'user/password/set';
		$data = array(
			'password' => sha1($password.'JarJarBinks9')
		);

		$res = $this->execute($url, $data);

		if ($res->status == 'success')
		{
			return TRUE;
		}

		return $res->content->type;
	}

	private function getHash($email, $password)
	{
		return sha1($password . 'JarJarBinks9');
	}

	private function execute($url, $data = array())
	{
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'User-Agent: PHP Wordfeud API 0.1'
		);

		$a = array(
			CURLOPT_HTTPHEADER		=> $headers,
			CURLOPT_COOKIEFILE		=> $this->cookiejar,
			CURLOPT_COOKIEJAR		=> $this->cookiejar,
			CURLINFO_HEADER_OUT 	=> TRUE,
			CURLOPT_RETURNTRANSFER	=> TRUE,
			CURLOPT_POSTFIELDS		=> json_encode($data)
		);

		$c = curl_init('http://game00.wordfeud.com/wf/'. $url .'/');
		curl_setopt_array($c, $a);

		$json = curl_exec($c);
		$this->header = curl_getinfo($c, CURLINFO_HEADER_OUT);
		curl_close($c);

		$res = json_decode($json);

		return $res;
	}
}
