<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger\Targets;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;
use \Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ProxyLogTarget extends AbstractLogTarget {
  private const OPTION_LOGGER_NAME = "name";
  private const DEFAULT_LOGGER_NAME = "resilient_logger";

  private LoggerInterface $logger;

  public function __construct(array $options) {
    parent::__construct($options);

    $name = array_key_exists(self::OPTION_LOGGER_NAME, $options)
      ? $options[self::OPTION_LOGGER_NAME]
      : self::DEFAULT_LOGGER_NAME;
    
    $this->logger = \Drupal::logger($name);
  }

  public function submit(AbstractLogSource $entry): bool {
    $document = $entry->getDocument();
    $auditEvent = $document["audit_event"];
    $actor = $auditEvent["actor"] ?? "unknown";
    $operation = $auditEvent["operation"] ?? "MANUAL";
    $target = $auditEvent["target"] ?? "unknown";
    $message = $auditEvent["message"];
    $level = $auditEvent["level"] ?? LogLevel::INFO;
    $extra = $auditEvent["extra"] ?? [];

    $context = array_merge($extra, [
      "actor" => $actor,
      "operation" => $operation,
      "target" => $target
    ]);

    $this->logger->log($level, $message, $context);
    return true;
  }
}