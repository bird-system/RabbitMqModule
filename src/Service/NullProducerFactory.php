<?php

declare(strict_types=1);

namespace RabbitMqModule\Service;

use Psr\Container\ContainerInterface;
use RabbitMqModule\NullProducer;

class NullProducerFactory
{
    /**
     * Create NullProducer.
     *
     * @param ContainerInterface $container
     *
     * @return NullProducer
     */
    public function __invoke(ContainerInterface $container): NullProducer
    {
        return new NullProducer();
    }
}
