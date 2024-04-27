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

    /**
     * get compiled caption
     *
     * @param array $details
     * @return string
     */
    public static function getCaption(array $details): string
    {
        $reply = "Title: {$details['title']}\n";
        $reply .= "Type: {$details['type']}\n";
        $reply .= "Released In: {$details['released']}\n";
        $reply .= "Rating: " . self::r2s($details['rating']) . "\n";
        // $reply .= "Description: " . self::shorten($details['description']) . "\n";

        return $reply;
    }

    /**
     * download media cover
     *
     * @param string $userId
     * @param string $coverUrl
     * @return string
     */
    public static function getCover(string $userId, string $coverUrl): string
    {
        $coverPath = "tmp/cover_$userId.jpg";
        $buffer = file_put_contents($coverPath, file_get_contents($coverUrl));
        if (!$buffer) {
            $coverPath = 'tmp/default.png';
        }

        return $coverPath;
    }

}