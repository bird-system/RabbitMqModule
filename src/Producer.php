<?php

declare(strict_types=1);

namespace RabbitMqModule;

use PhpAmqpLib\Message\AMQPMessage;

class Producer extends BaseAmqp implements ProducerInterface
{
    /**
     * @var string
     */
    protected $contentType = 'text/plain';

    /**
     * @var int
     */
    protected $deliveryMode = 2;

    /** @var bool */
    private $alreadySetup = false;

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @return int
     */
    public function getDeliveryMode(): int
    {
        return $this->deliveryMode;
    }

    /**
     * @param int $deliveryMode
     */
    public function setDeliveryMode(int $deliveryMode): void
    {
        $this->deliveryMode = $deliveryMode;
    }

    /**
     * @param string $body
     * @param string $routingKey
     * @param array  $properties
     */
    public function publish(string $body, string $routingKey = '', array $properties = []): void
    {
        if (false === $this->getConnection()->isConnected()) {
            $this->reconnect();
        }

        $properties = array_merge(
            ['content_type' => $this->getContentType(), 'delivery_mode' => $this->getDeliveryMode()],
            $properties
        );
        $message = new AMQPMessage($body, $properties);

        if (false === $this->alreadySetup && $this->isAutoSetupFabricEnabled()) {
            $this->setupFabric();
            $this->alreadySetup = true;
        }

        $this->getChannel()->basic_publish(
            $message,
            $this->getExchangeOptions()->getName(),
            $routingKey
        );
    }
}
