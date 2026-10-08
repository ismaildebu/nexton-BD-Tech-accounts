<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when every account code of an account type's range is used up
 * (for example Asset accounts 1000-1999).
 */
class AccountCodeRangeExceededException extends Exception
{
    //
}
