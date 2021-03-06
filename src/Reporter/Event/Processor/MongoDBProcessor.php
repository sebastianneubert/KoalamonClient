<?php

namespace Koalamon\Client\Reporter\Event\Processor;

use Koalamon\Client\Reporter\Event;
use MongoDB\Client;

class MongoDBProcessor implements Processor
{
    private $collection;
    private $publicHost;

    public function __construct($host, $databaseName, $publicHost)
    {
        $this->collection = (new Client($host))->$databaseName->users;
        $this->publicHost = $publicHost;
    }

    public function process(Event $event)
    {
        $attributes = array();

        foreach ($event->getAttributes() as $attribute) {
            if ($attribute->isIsStorable()) {
                $attributes[$attribute->getKey()] = $this->persistValue($attribute->getValue());
            } else {
                $attributes[$attribute->getKey()] = $attribute->getValue();
            }
        }

        return array("identifier" => $event->getIdentifier(),
            "system" => $event->getSystem(),
            "status" => $event->getStatus(),
            "message" => $event->getMessage(),
            "type" => $event->getTool(),
            "value" => $event->getValue(),
            "componentId" => $event->getComponentId(),
            "url" => $event->getUrl(),
            'attributes' => $attributes);
    }

    private function persistValue($value)
    {
        $insertOneResult = $this->collection->insertOne([
            'value' => $value,
            'created' => time(),
        ]);

        $id = $insertOneResult->getInsertedId();
        return 'storage:' . $this->publicHost . '/storage/' . $id;
    }
}