<?php

namespace Fagforbundet\NotificationApiSmsNotifierBundle\Transport;

use Fagforbundet\NotificationApiSmsNotifierBundle\Message\NotificationApiSentMessage;
use Fagforbundet\NotificationApiSmsNotifierBundle\Options\NotificationApiOptions;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
    private readonly \Closure $bearerTokenCb,
    private readonly ?string  $defaultRegion = null,
    HttpClientInterface       $client = null,
    EventDispatcherInterface  $dispatcher = null
  ) {
    parent::__construct($client, $dispatcher);
  }


  /**
   * @throws TransportExceptionInterface
   */
  protected function doSend(MessageInterface $message): SentMessage {
    if (!$message instanceof SmsMessage) {
      throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
    }

    $options = $message->getOptions() ?? new NotificationApiOptions();
    if (!$options instanceof NotificationApiOptions) {
      throw new LogicException(\sprintf('options passed to "%s", must be instance of "%s"', __CLASS__, NotificationApiOptions::class));
    }

    $phoneNumberUtil = PhoneNumberUtil::getInstance();

    try {
      $phoneNumber = $phoneNumberUtil->parse($message->getPhone(), $this->defaultRegion);
    } catch (NumberParseException $e) {
      throw new InvalidArgumentException(\sprintf('Unable to parse phone number (%s)', $message->getPhone()), previous: $e);
    }

    if (!$phoneNumberUtil->isValidNumber($phoneNumber)) {
      throw new InvalidArgumentException(\sprintf('The phone number (%s) is not valid', $message->getPhone()));
    }

    $formattedPhoneNumber = $phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::E164);

    $headers = [];
    if ($options->isDevRecipient()) {
      $headers[self::HEADER_DEV_RECIPIENTS] = $formattedPhoneNumber;
    }

    if ($options->isForceUseRecipients()) {
      $headers[self::HEADER_FORCE_USE_RECIPIENTS] = 'true';
    }

    $sms = [
      'text' => $message->getSubject(),
      'recipients' => [
        [
          'phoneNumber' => $formattedPhoneNumber
        ]
      ]
    ];

    if ($name = $options->getName()) {
      $sms['notification']['name'] = $name;
    }

    if ($externalReference = $options->getExternalReference()) {
      $sms['notification']['externalReference'] = $externalReference;
    }

    if ($queueName = $options->getQueueName()) {
      $sms['notification']['queueName'] = $queueName;
    }

    $response = $this->client->request(Request::METHOD_POST, \sprintf('https://%s/v1/notifications/sms', $this->getEndpoint()), [
      'headers' => $headers,
      'json' => ['sms' => $sms, 'allowUnicode' => $this->allowUnicode, 'transliterate' => $this->transliterate],
      'auth_bearer' => ($this->bearerTokenCb)($options)
    ]);

    try {
      $content = $response->toArray();
    } catch (ExceptionInterface $e) {
      throw new TransportException('Unable to send SMS (response status is not successful)', $response, previous: $e);
    }

    $sentMessage = (new NotificationApiSentMessage($message, (string) $this))
      ->setTextPartCount($content['sms']['textPartCount'] ?? null);
    $sentMessage->setMessageId($content['sms']['uuid']);
    return $sentMessage;
  }

  public function supports(MessageInterface $message): bool {
    return $message instanceof SmsMessage && (null === $message->getOptions() || $message->getOptions() instanceof NotificationApiOptions);
  }

  public function __toString(): string {
    return \sprintf('%s://%s', NotificationApiTransportFactory::SCHEME, $this->getEndpoint());
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
