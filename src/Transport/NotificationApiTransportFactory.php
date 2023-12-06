<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Transport;

use Fagforbundet\NotificationApiClientBundle\Client\NotificationApiClientInterface;
use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationApiTransportFactory extends AbstractTransportFactory {
  public const SCHEME = 'notification-api';

  public function __construct(
    private readonly NotificationApiClientInterface $notificationApiClient,
    EventDispatcherInterface $dispatcher = null,
    HttpClientInterface $client = null
  ) {
    parent::__construct($dispatcher, $client);
  }

  /**
   * @inheritDoc
   */
  protected function getSupportedSchemes(): array {
    return [self::SCHEME];
  }

  /**
   * @inheritDoc
   */
  public function create(Dsn $dsn): TransportInterface {
    $scheme = $dsn->getScheme();

    if (self::SCHEME !== $scheme) {
      throw new UnsupportedSchemeException($dsn, self::SCHEME, $this->getSupportedSchemes());
    }

    $defaultRegion = $dsn->getOption('defaultRegion');
    $allowUnicode = $dsn->getOption('allowUnicode') === 'true';
    $transliterate = $dsn->getOption('transliterate') === 'true';
    if ($dsn->getHost() !== 'default') {
      throw new IncompleteDsnException('Host must be set to default. Use Notification API client configuration to set host');
    }

    return (new NotificationApiTransport($this->notificationApiClient, $defaultRegion, $this->client, $this->dispatcher))
      ->setAllowUnicode($allowUnicode)
      ->setTransliterate($transliterate);
  }

}
