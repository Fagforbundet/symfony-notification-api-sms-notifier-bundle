<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Message;

use Symfony\Component\Notifier\Message\SentMessage;

class NotificationApiSentMessage extends SentMessage {
  private ?int $textPartCount = null;

  /**
   * @return int|null
   */
  public function getTextPartCount(): ?int {
    return $this->textPartCount;
  }

  /**
   * @param int|null $textPartCount
   *
   * @return static
   */
  public function setTextPartCount(?int $textPartCount): static {
    $this->textPartCount = $textPartCount;
    return $this;
  }

}
