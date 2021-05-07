
# Two Factor - PGP

You may require two factor authentication against any session by simply calling the `requireTwoFactor`()` method on the session, for example:

~~~php
use Apex\Armor\Armor;

// Get session
$armor = new Armor();
if (!$session = $armor->checkAuth()) { 
    die("You are not logged in.");
}

// Require 2FA
$session->requireTwoFactor();

// Code below this line will only be executed after the request has been authenticated.
~~~


## Part One - Initiate 2FA

Once 2FA via PGP has been initiated, a random 24 character string will be encrypted to the user's public PGP key, which must be shown on a template for the user to decrypt.  Upon initiation, the `AdapterInterface::handleSessionStatus()` method will be called with a status of "pgp", at which time you must retrieve the PGP message from redis and template a template to the user.

For example, within your adapter class:

~~~php
public function handleSessionStatus(AuthSession $session, string $status):void
{

    // Check for PGP 2FA
    if ($status == 'pgp') { 

        // Get PGP message
        $redis = Di::get(redis::class);
        $pgp_message = $redis->get('redis:pgp:' . $session->getUuid());

        // Display template showing $pgp_message
    }

}
~~~

Display a template showing that PGP message from redis, and ask the user to decrypt the message and enter the resulting 24 character string.


## Part Two - Verify Code

Your application must obtain the confirmation code input by the user, and call the `Apex\Armor\PGP\TwoFactorPGP::verify()` method, passing the `$armor_code` hash to it.  This method will either call the [AdapterInterface::handleTwoFactorAuthorized()](./adapter/handleTwoFactorAuthorized.md) method, or return null on failure.

For example:

~~~php
use Apex\Armor\Armor
use Apex\Armor\PGP\TwoFactorPGP;

// Get user
$armor = new Armor();
if (!$session = $armor->checkAuth()) { 
    die("You are not logged in");
}
$user = $session->getUser();

// Verify hash
$verifier = new TwoFactorPGP($armor);
if (!$uuid = $verifier($user, $_POST['code'])) { 
    die("Invalid code.  Please try again.");
}

/**
 * AdapterInterface::handleTwoFactorAuthorized() will be called here, which should parse the PSR-7 ServerRequest 
 * Accordingly and perform the request as normal.
 */
~~~

That's it.  With the above two steps in place, PGP 2FA is fully implemented and working.




