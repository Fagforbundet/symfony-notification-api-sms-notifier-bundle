<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Options;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class NotificationApiOptions implements MessageOptionsInterface {
  const OPTION_ACCESS_TOKEN = 'access_token';

  /**
   * NotificationApiOptions constructor.
   */
  public function __construct(private array $options = []) {
  }

  /**
   * @return string|null
   */
  public function getAccessToken(): ?string {
    return $this->options[self::OPTION_ACCESS_TOKEN] ?? null;
  }

  /**
   * @param string $accessToken
   *
   * @return $this
   */
  public function setAccessToken(string $accessToken): self {
    $this->options[self::OPTION_ACCESS_TOKEN] = $accessToken;
    return $this;
  }

  public function toArray(): array {
    return $this->options;
  }

  public function getRecipientId(): ?string {
    return null;
  }
}
