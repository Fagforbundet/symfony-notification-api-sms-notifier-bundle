<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Transport;

use Fagforbundet\NotificationApiClientBundle\Client\NotificationApiClientInterface;
use Fagforbundet\NotificationApiClientBundle\Notification\Notification;
use Fagforbundet\NotificationApiClientBundle\Notification\QueueName;
use Fagforbundet\NotificationApiClientBundle\Notification\Sms\SmsMessage;
use Fagforbundet\NotificationApiClientBundle\Notification\Sms\SmsRecipient;
use Fagforbundet\NotificationApiSmsNotifierBundle\Message\NotificationApiSentMessage;
use Fagforbundet\NotificationApiSmsNotifierBundle\Options\NotificationApiOptions;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage as SymfonySmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationApiTransport extends AbstractTransport {
  protected const HOST = 'api.meldinger.fagforbundet.no';

  private const HEADER_DEV_RECIPIENTS = 'X-Dev-Recipient-Overrides';
  private const HEADER_FORCE_USE_RECIPIENTS = 'X-Force-Use-Recipients';

  private bool $allowUnicode = false;
  private bool $transliterate = false;

  /**
   * NotificationApiTransport constructor.
   */
  public function __construct(
    private readonly NotificationApiClientInterface $notificationApiClient,
    private readonly ?string                        $defaultRegion = null,
    HttpClientInterface                             $client = null,
    EventDispatcherInterface                        $dispatcher = null
  ) {
    parent::__construct($client, $dispatcher);
  }

  protected function doSend(MessageInterface $message): SentMessage {
    if (!$message instanceof SymfonySmsMessage) {
      throw new UnsupportedMessageTypeException(__CLASS__, SymfonySmsMessage::class, $message);
    }

    $options = $message->getOptions() ?? new NotificationApiOptions();
    if (!$options instanceof NotificationApiOptions) {
      throw new LogicException(\sprintf('options passed to "%s", must be instance of "%s"', __CLASS__, NotificationApiOptions::class));
    }

    $smsRecipient = SmsRecipient::create($message->getPhone(), $this->defaultRegion);
    $smsMessage = new SmsMessage(
      $message->getSubject(),
      [$smsRecipient],
      (new Notification())
        ->setName($options->getName())
        ->setExternalReference($options->getExternalReference())
        ->setQueueName($options->getQueueName() ? QueueName::from($options->getQueueName()) : null)
    );

    $sentSmsMessage = $this->notificationApiClient->sendSmsMessage(
      $smsMessage,
      $options->isForceUseRecipients(),
      $options->isDevRecipient() ? [$smsRecipient] : [],
      $this->allowUnicode,
      $this->transliterate
    );

    $sentMessage = (new NotificationApiSentMessage($message, (string)$this))
      ->setTextPartCount($sentSmsMessage->getTextPartCount());
    $sentMessage->setMessageId((string) $sentSmsMessage->getUuid());
    return $sentMessage;
  }

  public function supports(MessageInterface $message): bool {
    return $message instanceof SymfonySmsMessage && (null === $message->getOptions() || $message->getOptions() instanceof NotificationApiOptions);
  }

  public function __toString(): string {
    return 'notification-api://default';
  }

  /**
   * @param bool $allowUnicode
   *
   * @return static
   */
  public function setAllowUnicode(bool $allowUnicode): static {
    $this->allowUnicode = $allowUnicode;
    return $this;
  }

  /**
   * @param bool $transliterate
   *
   * @return static
   */
  public function setTransliterate(bool $transliterate): static {
    $this->transliterate = $transliterate;
    return $this;
  }

}
