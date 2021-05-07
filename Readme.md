
# Armor - PGP Extension

An extension for the [Armor package](https://github.com/apexpl/armor/) that provides PGP functionality including key management, encrypt / decrypt data, and two factor authentication.

## Installation

Install via Composer with:

> `composer require apex/armor-pgp`


## Table of Contents

1. [Key Management](https://github.com/apexpl/armor-pgp/blob/master/docs/key_manager.md)
2. [Encrypt / Decrypt Data](https://github.com/apexpl/armor-pgp/blob/master/docs/encrypt.md)
3. [Two Factor Authentication](https://github.com/apexpl/armor-pgp/blob/master/docs/two_factor.md)


## Basic Usage

~~~php
use Apex\Armor\Armor;
use Apex\Armor\PGP\{KeyManager, EncryptPGP};

// Init Armor
$armor = new Armor();

// Import PGP key
$pgpkey = file_get_contents('mykey.asc');
$manager = new KeyManager($armor);
$manager->import('u:321', $pgpkey);

// Encrypt PGP message
$enc = new EncryptPGP($armor);
$pgp_message = $enc->toUuids('some secret message', ['u:321']);

echo "Encrypted Message:\n\n$pgp_message\n";
~~~

## Support

If you have any questions, issues or feedback, please feel free to drop a note on the <a href="https://reddit.com/r/apexpl/">ApexPl Reddit sub</a> for a prompt and helpful response.


## Follow Apex

Loads of good things coming in the near future including new quality open source packages, more advanced articles / tutorials that go over down to earth useful topics, et al.  Stay informed by joining the <a href="https://apexpl.io/">mailing list</a> on our web site, or follow along on Twitter at <a href="https://twitter.com/mdizak1">@mdizak1</a>.



