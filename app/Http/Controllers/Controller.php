<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponds;

/**
 * Base application controller.
 *
 * Controllers must remain thin. Delegate business logic to services
 * and data access to repositories.
 */
abstract class Controller
{
    use ApiResponds;
}
