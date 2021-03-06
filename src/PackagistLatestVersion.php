<?php

namespace ahinkle\PackagistLatestVersion;

use Exception;
use GuzzleHttp\Client;
use Spatie\Packagist\PackagistClient;
use Spatie\Packagist\PackagistUrlGenerator;

class PackagistLatestVersion
{
    /**
     * The Guzzle Client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The Packagist API.
     *
     * @var \Spatie\Packagist\Packagist|PackagistClient
     */
    protected $packagist;

    /**
     * Release tags that are considered `developmental` releases.
     *
     * @var array
     */
    protected $developmentalTags = [
        'alpha',
        'beta',
        'dev',
        'develop',
        'development',
        'master',
        'rc',
        'untagged',
        'wip',
    ];

    /**
     * @param \GuzzleHttp\Client $client
     * @param string             $baseUrl
     */
    public function __construct(Client $client, $baseUrl = 'https://packagist.org')
    {
        $this->client = $client;

        if (class_exists(PackagistClient::class)) {
            $this->packagist = new PackagistClient($client, new PackagistUrlGenerator($baseUrl));
        } else {
            $this->packagist = \Spatie\Packagist\Packagist($client, $baseUrl);
        }
    }

    /**
     * The latest release of the specified package.
     *
     * @param string $vendor
     *
     * @return string|null
     */
    public function getLatestRelease($package)
    {
        if ($package === '') {
            throw new Exception('You must pass a package value');
        }

        $metadata = $this->packagist->getPackageMetaData($package);

        if (! isset($metadata['packages'][$package])) {
            return;
        }

        return $this->resolveLatestRelease($metadata['packages'][$package]);
    }

    /**
     * Resolves the latest release by the provided array.
     *
     * @param  array  $releases
     * @return array|null
     */
    public function resolveLatestRelease($releases)
    {
        if (empty($releases)) {
            return;
        }

        $latestVersion = null;

        foreach ($releases as $release) {
            if ($this->isDevelopmentalRelease($release['version_normalized'])) {
                continue;
            }

            if ($latestVersion) {
                if (version_compare($release['version_normalized'], $latestVersion['version_normalized'], '>')) {
                    $latestVersion = $release;
                }
            } else {
                $latestVersion = $release;
            }
        }

        return $latestVersion;
    }

    /**
     * If the the release tag is a developmental release.
     *
     * @param  string  $release
     * @return bool
     */
    public function isDevelopmentalRelease($release)
    {
        foreach ($this->developmentalTags as $developmentalTag) {
            if (stripos($release, $developmentalTag) !== false) {
                return true;
            }
        }

        return false;
    }
}
