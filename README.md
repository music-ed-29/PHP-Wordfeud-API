PHP Wordfeud API
================
The Wordfeud-class helps you communicate with the Wordfeud API servers. I haven't tested all the methods yet and I'm sure there are some API methods I haven't discovered yet.

**Please keep in mind that this is still a work in progress!**

Example
-------
```php
<?php
require_once("Wordfeud.php");
//Change this lines to the email and password you want
define("TEST_EMAIL","some_email@some_server.com");
define ("TESTPASSWORD","RAnd0mjunK");
//
DEFINE ("HTML_EOL","<br />".PHP_EOL);

$WF = new Wordfeud();

try {
    // Log in with an existing account
    $WF->logInUsingEmail(TEST_EMAIL, TEST_PASSWORD);

    // Show your Wordfeud Session ID
    echo "Session ID: " . $WF->getSessionId() . "<br />";

    // Search for a user by username or email address
    $searchResults = $WF->searchUser("RandomUser");

    // Check search results
    if (count($searchResults) > 0) {
        $usr = $searchResults[0];
        echo "Found a user called <b>" . $usr['username'] . "</b> ";
        echo "(user id: " . $usr['user_id'] . ").<br />";
    } else {
        echo "User not found!<br />";
    }

    // Request game with a random opponent
    /*
    * Don't activate these lines if you don't want to play random games
    * They will do real requests
    *
    $request = $WF->inviteRandomOpponent(Wordfeud::RuleSetDutch, Wordfeud::BoardRandom);
    echo "Request sent!<br /><pre>";
    var_dump($request);
    echo "</pre>";
    */

    // Log out (not really necessary)
    $WF->logOut();
}
catch (WordfeudLogInException $ex) {
    echo "Authentication failed!";
}
catch (WordfeudHttpException $ex) {
    echo "Server did respond with HTTP status code 200 (OK)";
}
catch (WordfeudJsonException $ex) {
    echo "Could not decode JSON data received from the server";
}
catch (WordfeudException $ex) {
    echo "The following error occured: " . $ex->getMessage();
}
```

*Please see the PHPdoc for more information.*