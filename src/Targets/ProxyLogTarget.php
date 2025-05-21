<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger\Targets;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;
use \Psr\Log\LoggerInterface;

class ProxyLogTarget extends AbstractLogTarget {
  private const DEFAULT_LOGGER_NAME = "resilient_logger";

  private LoggerInterface $logger;

  public function __construct(array $options) {
    parent::__construct($options);

    $name = array_key_exists("name", $options)
      ? $options["name"]
      : self::DEFAULT_LOGGER_NAME;
    
    $this->logger = \Drupal::logger($name);
  }

  public function submit(AbstractLogSource $entry): bool {
    $this->logger->log($entry->getLevel(), $entry->getMessage(), $entry->getContext());
    return true;
  }
}