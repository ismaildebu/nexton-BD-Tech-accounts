<?php

declare(strict_types=1);

/**
 * Linux file systems are case-sensitive. A require of "media.php" works on
 * Windows/macOS even when git tracks the file as "Media.php", but it is a
 * fatal error in production (a git checkout on Linux).
 *
 * The check therefore compares against the names git TRACKS, not against the
 * names on the local disk (which a case-insensitive file system can hide).
 */
it('requires only route files whose git-tracked names match exactly', function () {
    $tracked = trim((string) shell_exec(
        'git -C ' . escapeshellarg(base_path()) . ' ls-files routes'
    ));

    if ($tracked === '') {
        $this->markTestSkipped('The git index is not available in this environment.');
    }

    $trackedNames = array_map('basename', explode("\n", $tracked));

    $web = file_get_contents(base_path('routes/web.php'));

    preg_match_all("#require\s+__DIR__\s*\.\s*'/([^']+)'#", $web, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $file) {
        expect($trackedNames)->toContain($file);
    }
});
