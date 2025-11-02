<?php
// Headers de sécurité pour toutes les pages

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'");

// Protection XSS
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Référrer policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Permissions policy
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
