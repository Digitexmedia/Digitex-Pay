<?php

class ReferenceHelper
{
    /**
     * Generate unique transaction reference
     */
    public static function generate(string $prefix = "DP"): string
    {
        return $prefix . "_" . uniqid() . "_" . rand(100, 999);
    }
}
