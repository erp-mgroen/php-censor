<?php

namespace PHPCensor\Helper;

use b8\Cache;
use b8\Config;
use b8\HttpClient;

/**
 * The Github Helper class provides some Github API call functionality.
 */
class Github
{
    /**
     * Make a request to the Github API.
     * @param $url
     * @param $params
     * @return mixed
     */
    public function makeRequest($url, $params)
    {
        $http = new HttpClient('https://api.github.com');
        $res = $http->get($url, $params);

        return $res['body'];
    }

    /**
     * Make all GitHub requests following the Link HTTP headers.
     *
     * @param string $url
     * @param mixed $params
     * @param array $results
     *
     * @return array
     */
    public function makeRecursiveRequest($url, $params, $results = [])
    {
        $http = new HttpClient('https://api.github.com');
        $res = $http->get($url, $params);

        foreach ($res['body'] as $item) {
            $results[] = $item;
        }

        foreach ($res['headers'] as $header) {
            if (preg_match('/^Link: <([^>]+)>; rel="next"/', $header, $r)) {
                $host = parse_url($r[1]);

                parse_str($host['query'], $params);
                $results = $this->makeRecursiveRequest($host['path'], $params, $results);

                break;
            }
        }

        return $results;
    }

    /**
     * Get an array of repositories from Github's API.
     */
    public function getRepositories()
    {
        $token = Config::getInstance()->get('php-censor.github.token');

        if (!$token) {
            return null;
        }

        $cache = Cache::getCache(Cache::TYPE_APC);
        $rtn = $cache->get('php-censor-github-repos');

        if (!$rtn) {
            $orgs = $this->makeRequest('/user/orgs', ['access_token' => $token]);

            $params = ['type' => 'all', 'access_token' => $token];
            $repos  = ['user' => []];
            $repos['user'] = $this->makeRecursiveRequest('/user/repos', $params);

            foreach ($orgs as $org) {
                $repos[$org['login']] = $this->makeRecursiveRequest('/orgs/'.$org['login'].'/repos', $params);
            }

            $rtn = [];
            foreach ($repos as $repoGroup) {
                foreach ($repoGroup as $repo) {
                    $rtn['repos'][] = $repo['full_name'];
                }
            }

            $cache->set('php-censor-github-repos', $rtn);
        }

        return $rtn;
    }

    /**
     * Create a comment on a specific file (and commit) in a Github Pull Request.
     * @param $repo
     * @param $pullId
     * @param $commitId
     * @param $file
     * @param $line
     * @param $comment
     * @return null
     */
    public function createPullRequestComment($repo, $pullId, $commitId, $file, $line, $comment)
    {
        $token = Config::getInstance()->get('php-censor.github.token');

        if (!$token) {
            return null;
        }

        $url = '/repos/' . strtolower($repo) . '/pulls/' . $pullId . '/comments';

        $params = [
            'body'      => $comment,
            'commit_id' => $commitId,
            'path'      => $file,
            'position'  => $line,
        ];

        $http = new HttpClient('https://api.github.com');
        $http->setHeaders([
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($token . ':x-oauth-basic'),
        ]);

        $http->post($url, json_encode($params));
    }

    /**
     * Create a comment on a Github commit.
     * @param $repo
     * @param $commitId
     * @param $file
     * @param $line
     * @param $comment
     * @return null
     */
    public function createCommitComment($repo, $commitId, $file, $line, $comment)
    {
        $token = Config::getInstance()->get('php-censor.github.token');

        if (!$token) {
            return null;
        }

        $url = '/repos/' . strtolower($repo) . '/commits/' . $commitId . '/comments';

        $params = [
            'body'     => $comment,
            'path'     => $file,
            'position' => $line,
        ];

        $http = new HttpClient('https://api.github.com');
        $http->setHeaders([
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($token . ':x-oauth-basic'),
        ]);

        $http->post($url, json_encode($params));
    }
}
