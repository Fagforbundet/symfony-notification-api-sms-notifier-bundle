<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Token;

use Fagforbundet\NotificationApiSmsNotifierBundle\Exception\UnauthorizedException;
use HalloVerden\Oidc\ClientBundle\Entity\Grant\ClientCredentialsGrant;
use HalloVerden\Oidc\ClientBundle\Exception\InvalidTokenException;
use HalloVerden\Oidc\ClientBundle\Exception\ProviderException;
use HalloVerden\Oidc\ClientBundle\Interfaces\OidcRawTokenInterface;
use HalloVerden\Oidc\ClientBundle\Interfaces\OpenIdProviderRegistryServiceInterface;
use HalloVerden\Oidc\ClientBundle\Interfaces\OpenIdProviderServiceInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class BearerTokenProvider implements BearerTokenProviderInterface {
  public const CACHE_KEY = 'notification_api_client_bearer_token';
  public const SCOPE = 'notification.post:/v1/notifications/sms';

  /**
   * BearerTokenService constructor.
   */
  public function __construct(
    private OpenIdProviderRegistryServiceInterface $openIdProviderRegistryService,
    private CacheInterface $cache = new ArrayAdapter(),
    private string $cacheKey = self::CACHE_KEY,
    private ?string $scope = self::SCOPE
  ) {
  }

  /**
   * @inheritDoc
   */
  public function getBearerTokenCb(Dsn $dsn): \Closure {
    $openIdProviderService = $this->openIdProviderRegistryService->getOpenIdProviderServiceByKey($dsn->getUser() ?? throw new IncompleteDsnException('User is not set.', $dsn->getOriginalDsn()));
    return fn() => $this->cache->get($this->cacheKey, fn(ItemInterface $item) => $this->getBearerToken($openIdProviderService, $item));
  }

  /**
   * @param OpenIdProviderServiceInterface $openIdProviderService
   * @param ItemInterface                  $item
   *
   * @return string
   * @throws UnauthorizedException
   */
  private function getBearerToken(OpenIdProviderServiceInterface $openIdProviderService, ItemInterface $item): string {
    try {
      $accessToken = $openIdProviderService->getTokenResponse(new ClientCredentialsGrant(\explode(' ', $this->scope)))->getAccessToken();
    } catch (InvalidTokenException|ProviderException $e) {
      throw new UnauthorizedException(previous: $e);
    }

    if (!$accessToken instanceof OidcRawTokenInterface) {
      throw new \LogicException(sprintf('$accessToken is not instance of %s', OidcRawTokenInterface::class));
    }

    $item->expiresAfter(\max($accessToken->getExp() - 300, 0));

    return $accessToken->getRawToken();
  }

}
