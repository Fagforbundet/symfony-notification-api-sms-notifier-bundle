<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Token;

use Symfony\Component\Notifier\Transport\Dsn;

interface BearerTokenProviderInterface {

  /**
   * @param Dsn $dsn
   *
   * @return \Closure(): string
   */
  public function getBearerTokenCb(Dsn $dsn): \Closure;

}
