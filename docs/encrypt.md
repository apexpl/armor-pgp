
# Encrypt / Decrypt Data

Although decrypt functionality is available, it is not recommended as it does require storing of private PGP keys.  Use at your own risk.  However, the password is encrypted to the user's personal RSA key.

## Encrypt PGP Message

You may encrypt a new PGP message by calling the `Apex\Armor\PGP\EncryptPGP::toUuids()` method, for example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\PGP\EncryptPGP;

// Init Armor
$armor = new Armor();

// ENcrypt message to uuid u:511
$enc = new EncryptPGP($armor);
$pgp_message = $enc->toUuids('contents of message', ['u:511']);

// $pgp_message will now be a ASCII encoded PGP message
~~~


## Decrypt PGP Messages

Although not recommended, you may decrypt PGP messages by calling the `Apex\Armor\PGP\DecryptPGP::fromUuid()` method.  However, this only works if you previously imported the private PGP key via the `KeyManager::import()` method and specified the correct PGP key password upon import.  For example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\PGP\DecryptPGP;

// Init Armor
$armor = new Armor();

// Decrypt
$pgp_message = '----PGP MESSAGE ---';
$dec = new DecryptPGP($armor);
$text = $dec->fromUuid($pgp_message, 'u:511', 'user_profile_password');

echo "Text id: $text\n";
~~~




