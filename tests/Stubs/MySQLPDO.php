<?php
namespace Abdulelahragih\QueryBuilder\Tests\Stubs;

use PDO;

class MySQLPDO extends PDO
{
    public function __construct()
    {
        parent::__construct('sqlite::memory:');
    }

    public function getAttribute($attribute)
    {
        if ($attribute === PDO::ATTR_DRIVER_NAME) {
            return 'mysql';
        }
        if ($attribute === PDO::ATTR_SERVER_VERSION) {
            return '8.0.0';
        }
        return parent::getAttribute($attribute);
    }
}
