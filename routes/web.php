<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Routes
require __DIR__ . '/public.php';

// Auth Routes
require __DIR__ . '/auth.php';

// Dashboard
require __DIR__ . '/dashboard.php';

// System Management
require __DIR__ . '/system.php';

// Company Management
require __DIR__ . '/company.php';

// Company-based modules (need 'company' middleware)
require __DIR__ . '/accounting.php';
require __DIR__ . '/sales.php';
require __DIR__ . '/purchase.php';
require __DIR__ . '/inventory.php';
require __DIR__ . '/hr.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/media.php';