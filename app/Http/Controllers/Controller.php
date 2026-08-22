<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * AuthorizesRequests is added here (previously unused anywhere in the
 * app) so the Media Business Module controllers can call
 * $this->authorize(...) against the registered Policies
 * (ModulePolicy). This is purely additive — no existing controller
 * currently calls $this->authorize(), so nothing else changes
 * behavior.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
