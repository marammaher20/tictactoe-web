<?php

// تولّد توكن عشوائي مرة وحدة لكل Session (لازم session_start قبلها)
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// تتحقق من التوكن بمقارنة آمنة ضد هجمات التوقيت
function csrf_valid($token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}