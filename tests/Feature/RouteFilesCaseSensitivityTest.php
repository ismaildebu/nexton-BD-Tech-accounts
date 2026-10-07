<?php

declare(strict_types=1);

/**
 * Linux file systems are case-sensitive. A require of "Media.php" works on
 * Windows/macOS but is a fatal error in production when the file is "media.php".
 */
it('requires only route files whose names match the file system exactly', function () {
    $web = file_get_contents(base_path('routes/web.php'));

    preg_match_all("#require\s+__DIR__\s*\.\s*'/([^']+)'#", $web, $matches);

    $actual = array_map('basename', glob(base_path('routes/*.php')));

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $file) {
        expect($actual)->toContain($file);
    }
});
