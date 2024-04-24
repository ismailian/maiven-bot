<?php

namespace TeleBot\App\Helpers;

class Utils
{

    /**
     * convert n/5 rating to stars
     *
     * @param int $value
     * @return string
     */
    public static function r2s(int $value): string
    {
        $stars = [];
        while (count($stars) < $value) {
            $stars[] = '⭐';
        }

        return join('', $stars);
    }

    /**
     * pad left side of a number
     *
     * @param int $value
     * @param int $length
     * @return string
     */
    public static function padLeft(int $value, int $length = 2): string
    {
        return str_pad($value, $length, 0, STR_PAD_LEFT);
    }

    /**
     * shorten a long text
     *
     * @param string $value
     * @param int $max
     * @return string
     */
    public static function shorten(string $value, int $max = 256): string
    {
        return mb_substr($value, 0, 256) . '...';
    }

}