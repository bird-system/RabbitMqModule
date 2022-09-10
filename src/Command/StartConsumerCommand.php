<?php

namespace RabbitMqModule\Command;

use BadFunctionCallException;
use function extension_loaded;
use function function_exists;
use function pcntl_signal;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RabbitMqModule\Consumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartConsumerCommand extends ContainerAwareCommand
{
    /** @var string */
    protected static $defaultName = 'rabbitmq:consumers:start';

    /** @var string */
    protected static $defaultDescription = 'Start a consumer by name';

    /** @var Consumer */
    protected $consumer;

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The consumer name')
            ->addOption('without-signals', 'w', InputOption::VALUE_NONE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $consumerName */
        $consumerName = $input->getArgument('name');

        $output->writeln("<info>Starting consumer $consumerName</info>");

        $serviceName = "rabbitmq.consumer.$consumerName";

        if (! $this->container->has($serviceName)) {
            $output->writeln("<error>No consumer with name \"$consumerName\" found</error>");

            return Command::FAILURE;
        }

        $withoutSignals = $input->getOption('without-signals');

        $consumer = $this->container->get($serviceName);
        if (! $consumer instanceof Consumer) {
            $output->writeln(
                sprintf('<error>The %s must be instanceof %s</error>', $serviceName, Consumer::class)
            );

            return Command::FAILURE;
        }
        $this->consumer = $consumer;
        $this->consumer->setSignalsEnabled(! $withoutSignals);

        if ($withoutSignals) {
            define('AMQP_WITHOUT_SIGNALS', true);
        }

        // @codeCoverageIgnoreStart
        if (! $withoutSignals && extension_loaded('pcntl')) {
            if (! function_exists('pcntl_signal')) {
                throw new BadFunctionCallException(
                    'Function \'pcntl_signal\' is referenced in the php.ini \'disable_functions\' and can\'t be called.'
                );
            }

            pcntl_signal(SIGTERM, [$this, 'stopConsumer']);
            pcntl_signal(SIGINT, [$this, 'stopConsumer']);
        }
        // @codeCoverageIgnoreEnd

        $this->consumer->consume();

        return Command::SUCCESS;
    }

    public function stopConsumer(): void
    {
        if ($this->consumer instanceof Consumer) {
            $this->consumer->forceStopConsumer();
            try {
                $this->consumer->stopConsuming();
            } catch (AMQPTimeoutException $e) {
                // ignore
            }
        }
        $this->callExit(Command::SUCCESS);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function callExit(int $code): void
    {
        exit($code);
    }
}
