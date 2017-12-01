<?php

namespace BST;

use BST\Device\BrowserStack;
use GuzzleHttp\Client;

class API
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var Client
     */
    protected $client;

    /**
     * API constructor.
     *
     * @param Bootstrap $bootstrap
     * @throws \Exception
     */
    public function __construct(Bootstrap $bootstrap)
    {
        if (!($username = getenv('BROWSERSTACK_USERNAME'))) {
            throw new \Exception('Missing required environment variable: BROWSERSTACK_USERNAME');
        }
        if (!($key = getenv('BROWSERSTACK_KEY'))) {
            throw new \Exception('Missing required environment variable: BROWSERSTACK_KEY');
        }

        $this->setBootstrap($bootstrap);
        $this->setUsername($username);
        $this->setKey($key);
    }

    /**
     * @return API
     * @throws \Exception
     */
    public function bootstrap(): API
    {
        $this->setClient(new Client([
            'base_uri' => 'https://www.browserstack.com/automate/',
            'auth' => [$this->getUsername(), $this->getKey(), 'Basic']
        ]));

        $this->getBootstrap()->getLogger()->log('Loading available browsers');
        $availableBrowsers = json_decode((string) $this->getClient()->get('browsers.json')->getBody(), true);
        if (!is_array($availableBrowsers)) {
            throw new \Exception('Could not fetch available BrowserStack browsers');
        }
        $availableBrowsers = BrowserStack::fromArray($availableBrowsers)->sort()->getBrowsers();

        $this->getBootstrap()->getLogger()->log('Validating requested browsers');
        $requestedBrowsers = $this->getBootstrap()->getConfig()->getBrowsers()->getBrowsers();
        $browsers = [];

        while (0 < count($requestedBrowsers)) {
            $browser = array_shift($requestedBrowsers);
            $found = false;

            foreach ($availableBrowsers as $availableBrowser) {
                // skip if browser names do not match
                if ($browser->getSimpleName() !== $availableBrowser->getSimpleName()) {
                    continue;
                }

                // skip if versions do not match, unless the requested version is null
                if (
                    null !== $browser->getVersion() &&
                    0 !== version_compare($browser->getSimpleVersion(), $availableBrowser->getSimpleVersion())
                ) {
                    continue;
                }

                // skip if os names do not match, unless the requested os is null
                if (
                    null !== $browser->getOs() &&
                    $browser->getOs()->getSimpleName() !== $availableBrowser->getOs()->getSimpleName()
                ) {
                    continue;
                }

                // skip if os versions do not match, unless the requested os or os version is null
                if (
                    null !== $browser->getOs() &&
                    null !== $browser->getOs()->getVersion() &&
                    0 !== version_compare(
                        $browser->getOs()->getSimpleVersion(),
                        $availableBrowser->getOs()->getSimpleVersion()
                    )
                ) {
                    continue;
                }

                $browsers[] = $availableBrowser;
                $found = true;

                break;
            }

            if ($found) {
                continue;
            }

            throw new \Exception(
                'Could not find browser corresponding to the requested annotation:' . PHP_EOL .
                '{' . PHP_EOL .
                '    "browser": ' . json_encode($browser->getName()) . ',' . PHP_EOL .
                '    "browser_version": ' . json_encode($browser->getVersion()) . ',' . PHP_EOL .
                '    "os": ' . json_encode($browser->getOs() ? $browser->getOs()->getName() : null) . ',' . PHP_EOL .
                '    "os_version": ' . json_encode($browser->getOs() ? $browser->getOs()->getVersion() : null) .
                PHP_EOL . '}'
            );
        }

        $this->getBootstrap()->getConfig()->setBrowsers(new BrowserStack($browsers));
        $this->getBootstrap()->getLogger()->log('Using ' . count($browsers) . ' browsers / workers with up to ' .
            $this->queryParallelLimit() . ' parallel sessions');

        return $this;
    }

    /**
     * @param string $session
     * @param bool $success
     * @param string $reason
     * @return API
     */
    public function notify(string $session, bool $success, string $reason = ''): API
    {
        $this->getClient()->put(
            'sessions/' . $session . '.json',
            [
                'json' => [
                    'status' => $success ? 'completed' : 'failed',
                    'reason' => $reason
                ]
            ]
        );

        return $this;
    }

    /**
     * @return int
     */
    public function queryParallelLimit(): int
    {
        $plan = json_decode((string) $this->getClient()->get('plan.json')->getBody(), true);
        if (!is_array($plan)) {
            return 0;
        }

        return ($plan['parallel_sessions_max_allowed'] - $plan['parallel_sessions_running']) +
            ($plan['queued_sessions_max_allowed'] - $plan['queued_sessions']);
    }

    /**
     * @return Bootstrap
     */
    public function getBootstrap(): Bootstrap
    {
        return $this->bootstrap;
    }

    /**
     * @param Bootstrap $bootstrap
     * @return API
     */
    public function setBootstrap(Bootstrap $bootstrap): API
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return API
     */
    public function setUsername(string $username): API
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return API
     */
    public function setKey(string $key): API
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return API
     */
    public function setClient(Client $client): API
    {
        $this->client = $client;
        return $this;
    }
}