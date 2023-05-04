<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Options;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class NotificationApiOptions implements MessageOptionsInterface {
  const OPTION_NAME = 'name';
  const OPTION_EXTERNAL_REFERENCE = 'external_reference';
  const OPTION_QUEUE_NAME = 'queue_name';

  /**
   * NotificationApiOptions constructor.
   */
  public function __construct(private array $options = []) {
  }

  /**
   * @return string|null
   */
  public function getName(): ?string {
    return $this->options[self::OPTION_NAME] ?? null;
  }

  /**
   * @param string|null $name
   *
   * @return $this
   */
  public function setName(?string $name): self {
    $this->options[self::OPTION_NAME] = $name;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getExternalReference(): ?string {
    return $this->options[self::OPTION_EXTERNAL_REFERENCE] ?? null;
  }

  /**
   * @param string|null $externalReference
   *
   * @return $this
   */
  public function setExternalReference(?string $externalReference): self {
    $this->options[self::OPTION_EXTERNAL_REFERENCE] = $externalReference;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getQueueName(): ?string {
    return $this->options[self::OPTION_QUEUE_NAME] ?? null;
  }

  /**
   * @param string|null $queueName
   *
   * @return $this
   */
  public function setQueueName(?string $queueName): self {
    $this->options[self::OPTION_QUEUE_NAME] = $queueName;
    return $this;
  }

  public function toArray(): array {
    return $this->options;
  }

  public function getRecipientId(): ?string {
    return null;
  }
}
