<?php

namespace Crowdin;

use Crowdin\Api\ApiInterface;
use Crowdin\Api\BranchApi;
use Crowdin\Api\DirectoryApi;
use Crowdin\Api\FileApi;
use Crowdin\Api\GlossaryApi;
use Crowdin\Api\GroupApi;
use Crowdin\Api\LanguageApi;
use Crowdin\Api\MachineTranslationEngineApi;
use Crowdin\Api\ProjectApi;
use Crowdin\Api\ReportApi;
use Crowdin\Api\ScreenshotApi;
use Crowdin\Api\SourceStringApi;
use Crowdin\Api\StorageApi;
use Crowdin\Api\StringTranslationApi;
use Crowdin\Api\StringTranslationApprovalApi;
use Crowdin\Api\TaskApi;
use Crowdin\Api\TranslationApi;
use Crowdin\Api\TranslationMemoryApi;
use Crowdin\Api\UserApi;
use Crowdin\Api\VendorApi;
use Crowdin\Api\VoteApi;
use Crowdin\Api\WebhookApi;
use Crowdin\Api\WorkflowTemplateApi;
use Crowdin\Http\Client\CrowdinHttpClientFactory;
use Crowdin\Http\Client\CrowdinHttpClientInterface;
use Crowdin\Http\ResponseDecorator\ResponseDecoratorInterface;
use Crowdin\Http\ResponseErrorHandlerFactory;
use Crowdin\Http\ResponseErrorHandlerInterface;
use UnexpectedValueException;

/**
 * Class Crowdin
 * @package Crowdin
 *
 * @property StorageApi storage
 * @property LanguageApi language
 * @property GroupApi group
 * @property ProjectApi project
 * @property BranchApi branch
 * @property TaskApi task
 * @property ScreenshotApi screenshot
 * @property DirectoryApi directory
 * @property GlossaryApi glossary
 * @property StringTranslationApi stringTranslation
 * @property StringTranslationApprovalApi stringTranslationApproval
 * @property VoteApi vote
 * @property UserApi user
 * @property VendorApi vendor
 * @property WorkflowTemplateApi workflowTemplate
 * @property FileApi file
 * @property MachineTranslationEngineApi machineTranslationEngine
 * @property ReportApi report
 * @property SourceStringApi sourceString
 * @property TranslationMemoryApi translationMemory
 * @property WebhookApi webhook
 * @property TranslationApi translation
 */
class Crowdin
{
    /**
     * @var CrowdinHttpClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @return CrowdinHttpClientInterface
     */
    public function getClient(): CrowdinHttpClientInterface
    {
        return $this->client;
    }

    /**
     * @param CrowdinHttpClientInterface $client
     */
    public function setClient(CrowdinHttpClientInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return ResponseErrorHandlerInterface
     */
    public function getResponseErrorHandler(): ResponseErrorHandlerInterface
    {
        return $this->responseErrorHandler;
    }

    /**
     * @param ResponseErrorHandlerInterface $responseErrorHandler
     */
    public function setResponseErrorHandler(ResponseErrorHandlerInterface $responseErrorHandler): void
    {
        $this->responseErrorHandler = $responseErrorHandler;
    }

    /**
     * @var array
     */
    protected $apis = [];

    /**
     * @var string
     */
    protected $baseUri = 'https://api.crowdin.com/api/v2';

    /**
     * @var ResponseErrorHandlerInterface
     */
    protected $responseErrorHandler;

    protected $services = [
        'storage',
        'language',
        'group',
        'project',
        'task',
        'branch',
        'glossary',
        'stringTranslation',
        'stringTranslationApproval',
        'directory',
        'vote',
        'vendor',
        'user',
        'screenshot',
        'workflowTemplate',
        'file',
        'machineTranslationEngine',
        'report',
        'sourceString',
        'translationMemory',
        'webhook',
        'translation',
    ];

    public function __construct(array $config)
    {
        $config = array_merge([
            'http_client_handler' => null,
            'response_error_handler' => null,
            'access_token' => null,
            'base_uri' => $this->baseUri
        ], $config);

        $this->accessToken = $config['access_token'];
        $this->baseUri = $config['base_uri'];

        $this->client = CrowdinHttpClientFactory::make($config['http_client_handler']);
        $this->responseErrorHandler = ResponseErrorHandlerFactory::make($config['response_error_handler']);
    }

    public function request(string $method, string $uri, array $options = [])
    {
        $options['body'] = $options['body'] ?? null;

        $options['headers'] = array_merge([
            'Authorization' => 'Bearer ' . $this->accessToken,
          //  'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);

        $response = $this->client->request($method, $uri, $options);

        return $response;
    }

    public function apiRequest(string $method, string $path, ResponseDecoratorInterface $decorator = null, array $options = [])
    {
        $response = $this->request($method, $this->getFullUrl($path), $options);

        $response = json_decode($response, true);

        $this->responseErrorHandler->check($response);

        if ($decorator instanceof ResponseDecoratorInterface) {
            if (isset($response['data'])) {
                $response = $decorator->decorate($response['data']);
            } else {
                $response = $decorator->decorate($response);
            }
        }

        return $response;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFullUrl(string $path): string
    {
        return $this->baseUri . '/' . ltrim($path);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, $this->services)) {
            return $this->getApi($name);
        }

        throw new UnexpectedValueException(sprintf('Invalid property: %s', $name));
    }

    /**
     * @param string $name
     * @return ApiInterface
     */
    public function getApi(string $name): ApiInterface
    {
        $class = '\Crowdin\\Api\\' . ucfirst($name) . 'Api';

        if (!array_key_exists($class, $this->apis)) {
            $this->apis[$class] = new $class($this);
        }

        return $this->apis[$class];
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }
}
