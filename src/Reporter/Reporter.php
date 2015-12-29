<?php

namespace Koalamon\Client\Reporter;

/**
 * Class Reporter
 *
 * This class can be used to report an event to the koalamon applicaton.
 *
 * @package Koalamon\EventReporter
 * @author Nils Langner <nils.langner@koalamon.com>
 */
class Reporter
{
    private $apiKey;
    private $project;

    /**
     * @var HttpAdapterInterface|null
     */
    private $httpClient;

    const ENDPOINT_WEBHOOK_DEFAULT = "http://www.koalamon.com/webhook/";
    const ENDPOINT_WEBHOOK_DEFAULT_DEBUG = "http://www.koalamon.com/app_dev.php/webhook/";

    const RESPONSE_STATUS_SUCCESS = "success";
    const RESPONSE_STATUS_FAILURE = "failure";

    /**
     * @param $project The project name you want to report the event for.
     * @param $apiKey  The api key can be found on the admin page of a project,
     *                 which can be seen if you are the project owner.
     * @param null $httpClient
     */
    public function __construct($project, $apiKey, HttpAdapterInterface $httpClient = null)
    {
        $this->project = $project;
        $this->apiKey = $apiKey;

        if (is_null($httpClient)) {
            $this->httpClient = new Client();
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * This function will send the given event to the koalamon default webhook
     *
     * @param Event $event
     * @param bool|false $debug
     */
    public function send(Event $event, bool $debug = false)
    {
        if ($debug) {
            $endpoint = self::ENDPOINT_WEBHOOK_DEFAULT_DEBUG;
        } else {
            $endpoint = self::ENDPOINT_WEBHOOK_DEFAULT;
        }

        $endpointWithApiKey = $endpoint . "?api_key=" . $this->apiKey;
        $response = $this->getJsonResponse($endpoint, $event);

        if ($response->status != self::RESPONSE_STATUS_SUCCESS) {
            throw new ServerException("Failed sending event (" . $response->message . ").", $response);
        }
    }

    /**
     * Returns the json answer of the web server.
     *
     * failure:
     * {
     *   status: "failure",
     *   message: "unknown api key"
     * }
     *
     * success:
     * {
     *   status: "success"
     * }
     */
    private function getJsonResponse($endpoint, Event $event)
    {
        $eventJson = json_encode($event);

        $request = new Request("POST", $endpoint, $eventJson);
        $response = $this->httpClient->sendRequest($request);

        $responseStatus = json_decode($response->getBody());

        return $responseStatus;
    }
}