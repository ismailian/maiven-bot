<?php

namespace TeleBot\App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Show365
{

    /** @var Client|null $client */
    protected static ?Client $client;

    /**
     * get featured movies/series
     *
     * @return array
     */
    public static function getFeatured(): array
    {
        return [];
    }

    /**
     * search for movies/series
     *
     * @param string $query
     * @param int $limit
     * @return array|null
     * @throws GuzzleException
     */
    public static function search(string $query, int $limit = 10): ?array
    {
        try {
            $uri = "/api/MobileV3/Show/SearchShows?showName=" . str_replace(' ', '+', $query);
            $response = self::getClient()->get($uri);
            $response = json_decode($response->getBody(), true);
            if ($response['error']) return null;

            $round = fn($val) => $val > 0 ? round($val / 2) : 0;
            return array_slice(array_map(function ($result) use ($round) {
                return (object)[
                    'id' => $result['id'],
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'released' => $result['year'],
                    'rating' => $round(floatval($result['rate'])),
                    'type' => $result['isMovie'] ? 'movie' : 'series',
                    'cover' => $result['photoUrl'] ?? null,
                ];
            }, $response['data']), 0, $limit);
        } catch (\Exception $ex) {}
        return null;
    }

    /**
     * get http client
     *
     * @return Client
     */
    private static function getClient(): Client
    {
        if (empty(self::$client)) {
            self::$client = new Client([
                'verify' => false,
                'base_uri' => 'https://365ar.show',
                'headers' => [
                    'User-Agent' => getenv('USER_AGENT', true),
                ],
            ]);
        }

        return self::$client;
    }

    /**
     * get show details
     *
     * @param string $id show uuid
     * @param bool $isMovie
     * @return array|null
     * @throws GuzzleException
     */
    public static function getShow(string $id, bool $isMovie = false): ?array
    {
        try {
            $response = self::getClient()->get("/api/MobileV3/Show/GetShow?id=$id");
            $response = json_decode($response->getBody(), true);
            if ($response['error']) return null;

            if ($isMovie) {
                return array_map(fn($f) => (object)[
                    'id' => $f['id'],
                    'format' => $f['resolution'],
                    'url' => $f['path']
                ], $response['data']['files']);
            } else {
                return array_map(function ($result) {
                    return (object)[
                        'id' => $result['id'],
                        'number' => $result['seasonNumber'],
                        'episodes' => $result['episodesCount'],
                    ];
                }, $response['data']['seasons']);
            }
        } catch (\Exception $ex) {}
        return null;
    }

    /**
     * get season episodes
     *
     * @param string $id season uuid
     * @return array|null
     * @throws GuzzleException
     */
    public static function getEpisodes(string $id): ?array
    {
        try {
            $response = self::getClient()->get("/api/MobileV3/Series/GetEpisodes?pageNumber=1&sessionId=$id");
            $response = json_decode($response->getBody(), true);
            if ($response['error']) return null;

            return array_map(function ($result) {
                return (object)[
                    'id' => $result['id'],
                    'number' => $result['episodeNumber'],
                    'formats' => array_map(fn($f) => (object)[
                        'id' => $f['id'],
                        'format' => $f['resolution'],
                        'url' => $f['path']
                    ], $result['episodeFiles'])
                ];
            }, $response['data']);
        } catch (\Exception $ex) {}
        return null;
    }
}