<?php

namespace Abdulelahragih\QueryBuilder\Data;

use Exception;

class QueryBuilderException extends Exception
{
    /**
     * Error codes
     * 0 - 999: Database errors
     * 1000 - 1999: Types and Validation errors
     * 2000 - 2999: Query and Query data errors
     */
    const EXECUTE_ERROR = 0;
    const INVALID_ORDER_TYPE = 1000;
    const INVALID_JOIN_TYPE = 1001;
    const MISSING_TABLE = 2000;
    const MISSING_COLUMNS = 2001;
    const DANGEROUS_QUERY = 2002;
    const INVALID_QUERY = 2004;

    public function __construct(int $errorCode, string $message = '')
    {
        parent::__construct($message);
    }
}