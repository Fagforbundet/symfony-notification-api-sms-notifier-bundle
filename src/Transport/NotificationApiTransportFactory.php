<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Transport;

use Fagforbundet\NotificationApiSmsNotifierBundle\Token\BearerTokenProviderInterface;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationApiTransportFactory extends AbstractTransportFactory {
  public const SCHEME = 'notification-api';

  public function __construct(
    private readonly BearerTokenProviderInterface $bearerTokenProvider,
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
    $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
    $port = $dsn->getPort();

    return (new NotificationApiTransport($this->bearerTokenProvider->getBearerTokenCb($dsn), $defaultRegion, $this->client, $this->dispatcher))
      ->setHost($host)
      ->setPort($port)
      ->setAllowUnicode($allowUnicode)
      ->setTransliterate($transliterate);
  }

}
