<?php
/**
 * Loads the code that exists only in wordpress-code-ai, not in Easy MCP AI.
 *
 * tools/rename-upstream.py adds the line that requires this file to the main
 * plugin file. Everything in fork/ is kept as it is when a new Easy MCP AI
 * release is brought in, and the script leaves it untouched.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-updater.php';
require_once __DIR__ . '/class-browser-test.php';

wordpress_code_ai\Fork\Updater::register();
wordpress_code_ai\Fork\Browser_Test::register();
