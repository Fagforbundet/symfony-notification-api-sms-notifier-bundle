<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Message;

interface DevRecipientAwareSmsMessageInterface {

  /**
   * @return bool
   */
  public function isRecipientDevRecipient(): bool;

}
