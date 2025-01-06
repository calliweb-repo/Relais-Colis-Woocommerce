<?php

namespace RelaisWoocommerce\API;

defined('ABSPATH') || exit;

/**
 * Class AbstractApi
 *
 * @author Team Calliweb dev@calliweb.fr
 * @copyright Copyright © 2024 Calliweb (https://www.calliweb.fr)
 * @package AbstractApi
 */
abstract class AbstractApi
{
    /** @var string $baseUrl */
    protected string $baseUrl;
    /** @var string $environment */
    protected string $environment;
    /** @var string $activationKey */
    protected string $activationKey;
    /** @var array $defaultBody */
    protected array $defaultBody;
    /** @var string */
    const LIVE_MODE = 'production';
    /** @var string */
    const TEST_MODE = 'preproduction';
    /** @var string */
    const ENDPOINT = '';
    /** @var string[] */
    const REST_URLS = [
        self::LIVE_MODE => 'https://ws-modules.relaiscolis.com/',
        self::TEST_MODE => 'https://preprod-ws-modules.relaiscolis.com/'
    ];

    /**
     * @param string|null $environment
     * @param string|null $activationKey
     */
    public function __construct(string $environment = null, string $activationKey = null)
    {

        $this->environment = $environment;

        if (!$this->environment) {
            $this->environment = $this->getModeBySettings();
        }

        $this->activationKey = $activationKey;

        if (!$this->activationKey) {
            $this->activationKey = get_option('INSERT_ACTIVATION_KEY_CONFIG');
        }

        $this->baseUrl = self::REST_URLS[$environment];
        $this->setDefaultBody([
            'activation_key' => $this->activationKey,
            'moduleName' => 'relais colis',
            'moduleVersion' => $this->getPluginVersion(),
            'cmsName' => 'woocommerce',
            'cmsVersion' => $this->getWooCommerceVersion()
        ]);
    }

    /**
     * @return string
     */
    protected function getPluginVersion(): string
    {
        $plugin_data = get_plugin_data(plugin_dir_path(__DIR__) . 'relais-colis-woocommerce.php');
        return $plugin_data['Version'];
    }

    /**
     * @return string
     */
    protected function getWooCommerceVersion(): string
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return defined('WC_VERSION') ? WC_VERSION : 'WooCommerce not installed';
    }

    /**
     * @return string
     */
    protected function getModeBySettings(): string
    {
        $options = get_option('INSERT_MODE_CONFIG');
        return $options['environment'] ?? self::TEST_MODE;
    }

    /**
     * @return string
     */
    protected function getEndpoint(): string
    {
        return static::ENDPOINT;
    }

    /**
     * @return array
     */
    protected function getDefaultBody(): array
    {
        return $this->defaultBody;
    }

    /**
     * @param $body
     * @return void
     */
    protected function setDefaultBody($body): void
    {
        $this->defaultBody = $body;
    }

    /**
     * @param $additionalBody
     * @return array
     */
    protected function mergeBody($additionalBody): array
    {
        return array_merge($this->getDefaultBody(), $additionalBody);
    }

    /**
     * @param array $additionalBody
     * @return mixed|null
     */
    public function fetchData(array $additionalBody = []): mixed
    {
        $url = $this->baseUrl . $this->getEndpoint();
        $body = $this->mergeBody($additionalBody);
        $response = wp_remote_post($url, [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $responseBody = wp_remote_retrieve_body($response);
        return json_decode($responseBody, true);
    }
}
