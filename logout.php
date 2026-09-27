<?php
/**
 * Logout
 */
require_once __DIR__ . '/includes/auth.php';

logout_user();
redirect(app_url('index.php'));
