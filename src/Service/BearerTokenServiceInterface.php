<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Service;

use Fagforbundet\NotificationApiSmsNotifierBundle\Exception\UnauthorizedException;

interface BearerTokenServiceInterface {

  /**
   * @return string
   * @throws UnauthorizedException
   */
  public function getBearerToken(): string;

}
