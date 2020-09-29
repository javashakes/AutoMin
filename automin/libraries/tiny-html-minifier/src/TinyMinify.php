<?php

// namespace Minifier;
// use Minifier\TinyHtmlMinifier;

require 'TinyHtmlMinifier.php';

class TinyMinify
{
    // PHP 7x
    // public static function html(string $html, array $options = []) : string
    public static function html($html, $options = [])
    {
        $minifier = new TinyHtmlMinifier($options);
        return $minifier->minify($html);
    }
}
