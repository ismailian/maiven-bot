<?php

namespace TeleBot\App\Helpers;

use TeleBot\System\Types\InlineKeyboard;

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
     * @param string $userId
     * @param string $mediaId
     * @param string $coverUrl
     * @return string
     */
    public static function getCover(string $userId, string $mediaId, string $coverUrl): string
    {
        $coverPath = "tmp/cover_{$userId}_{$mediaId}.jpg";
        if (!file_exists($coverPath) || filesize($coverPath) == 0) {
            array_map('unlink', glob("tmp/cover_{$userId}_*"));

            /** check if media cover exists on other sessions */
            $results = glob("tmp/cover_*_{$mediaId}.jpg");
            if (!empty($results)) {
                if (copy($results[0], $coverPath)) {
                    return $coverPath;
                }
            }

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

    /**
     * get back navigation button
     *
     * @param array $data
     * @return array
     */
    public static function getBackButton(array $data): array
    {
        return (new InlineKeyboard(1))
            ->addButton('⬅ Back', $data, InlineKeyboard::CALLBACK_DATA)
            ->toArray();
    }

    /**
     * get episodes pager
     *
     * @param int $sIndex
     * @param int $eIndex
     * @param array $episodes
     * @return array
     */
    public static function getEpisodePager(int $sIndex, int $eIndex, array $episodes): array
    {
        $pager = (new InlineKeyboard());
        $hasPrev = ($eIndex - 1) >= 0;
        $hasNext = (count($episodes) - 1) >= ($eIndex + 1);

        $prevEp = 'E' . Utils::padLeft($eIndex);
        $nextEp = 'E' . Utils::padLeft($eIndex + 2);

        if ($hasPrev) $pager->addButton($prevEp, [
            's' => $sIndex, 'episode' => $eIndex - 1
        ], InlineKeyboard::CALLBACK_DATA);
        if ($hasNext) $pager->addButton($nextEp, [
            's' => $sIndex, 'episode' => $eIndex + 1
        ], InlineKeyboard::CALLBACK_DATA);

        return $pager->toArray();
    }

    /**
     * prepare compress button
     *
     * @param int $sIndex
     * @return array
     */
    public static function getPrepareButton(int $sIndex): array
    {
        return (new InlineKeyboard())
            ->addButton('Compress Season', ['season:prepare' => $sIndex], InlineKeyboard::CALLBACK_DATA)
            ->toArray();
    }

}
