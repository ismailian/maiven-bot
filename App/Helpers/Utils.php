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
     * @param string|null $season
     * @return string
     */
    public static function getCaption(array $details, string $season = null): string
    {
        $reply = "Title: {$details['title']}\n";
        $reply .= "Type: {$details['type']}\n";
        $reply .= "Released In: {$details['released']}\n";
        $reply .= "Rating: " . self::r2s($details['rating']) . "\n";
        if ($season) {
            $reply .= "Season: {$season}\n";
        }

        return $reply;
    }

    /**
     * download media cover
     *
     * @param string $mediaId
     * @param string $coverUrl
     * @return string
     */
    public static function getCover(string $mediaId, string $coverUrl): string
    {
        $coverPath = "tmp/cover_$mediaId.jpg";
        if (!file_exists($coverPath)) {
            if (!file_put_contents($coverPath, file_get_contents($coverUrl))) {
                $coverPath = 'tmp/default.png';
            }
        }

        return $coverPath;
    }

    /**
     * get best format
     *
     * @param array $formats
     * @param int $highest
     * @return array|null
     */
    public static function getBestFormat(array $formats, int $highest = 720): ?array
    {
        if (empty($formats)) return null;

        $index = 0;
        usort($formats, fn($a, $b) => $a['format'] > $b['format']);
        while (isset($formats[$index])) {
            if (!isset($formats[$index + 1]) || $formats[$index + 1]['format'] > $highest)
                break;
            $index++;
        }

        return $formats[$index];
    }

    /**
     * prepare download files
     *
     * @param string $userId
     * @param string $title
     * @param int $season
     * @param array $episodes
     * @return array
     */
    public static function getFiles(string $userId, string $title, int $season, array $episodes): array
    {
        $formats = [];
        foreach ($episodes as $episode) {
            foreach ($episode['formats'] as $format) {
                $formats[$format['format']][] = $format['url'];
            }
        }

        $files = [];
        uksort($formats, fn($a, $b) => ($a == $b) ? 0 : (($a < $b) ? 1 : -1));
        foreach ($formats as $format => $links) {
            $fileName = str_replace(' ', '_', strtolower($title));
            $filePath = "tmp/{$fileName}_S{$season}_{$userId}_{$format}p.txt";
            if (file_put_contents($filePath, join(PHP_EOL, $links))) {
                $files[] = $filePath;
            }
        }

        return $files;
    }

}
