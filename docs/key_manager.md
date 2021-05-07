
# Key Management

All PGP keys can be managed through the `Apex\Armor\PGP\KeyManager` class, which contains the following methods:

* `bool import(string $uuid, string $pgp_key, string $password = '')` - Password is not recommended, and only required if importing a private PGP key and you wish to have decrypt functionality.
* `string getKey(string $uuid)` - Get the public PGP key of uuid.
* `string getFingerprint(string $uuid)` - Get fingerprint of a usr's PGP key.
* `void importAll()` - Upon transferring to a new server, call this method once to import all PGP keys from database to gnupg.


## Example

~~~php
use Apex\Armor\Armor;
use Apex\Armor\PGP\KeyManager;

// Init Armor
$armor = new Armor();

// Import PGP key to u:195
$pgp_key = file_get_contents('mykey.asc');
$manager = new KeyManager($armor);
$manager->import('u:195', $pgp_key);

// Get PGP key
$key = $manager->getKey('u:195');

// Get fingerprint of key
$fingerprint = $manager->getFingerprint('u:195');
~~~





