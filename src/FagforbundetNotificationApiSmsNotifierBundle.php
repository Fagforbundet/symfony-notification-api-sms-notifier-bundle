<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle;

use Fagforbundet\NotificationApiSmsNotifierBundle\Transport\NotificationApiTransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class FagforbundetNotificationApiSmsNotifierBundle extends AbstractBundle {

  /**
   * @inheritDoc
   */
  public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
    $alias = $this->getContainerExtension()->getAlias();

    $container->services()
      ->set($alias. '.transport_factory', NotificationApiTransportFactory::class)
      ->args([
        service('fagforbundet_notification_api_client.client'),
        service('event_dispatcher')->nullOnInvalid(),
        service('http_client')->nullOnInvalid()
      ])
      ->tag('texter.transport_factory')
    ;
  }

}
