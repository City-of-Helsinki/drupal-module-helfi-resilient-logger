<?php

namespace Drupal\helfi_resilient_logger\Submitter;

use ResilientLogger\Facade\AbstractLogFacade;
use ResilientLogger\Submitter\AbstractSubmitter;
use \Psr\Log\LoggerInterface;

class DrupalProxySubmitter extends AbstractSubmitter {
  private LoggerInterface $logger;

  public function __construct(array $options) {
    parent::__construct($options);
    $this->logger = \Drupal::logger('resilient_logger.submitter');
  }

  protected function _submitEntry(AbstractLogFacade $entry): ?string {
    $submitId = bin2hex(random_bytes(32));
    $context = $entry->getContext();
    $context["submit_id"] = $submitId;

    $this->logger->log($entry->getLevel(), $entry->getMessage(), $context);
    
    return $submitId;
  }
}