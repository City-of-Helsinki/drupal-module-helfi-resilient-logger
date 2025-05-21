<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger\Handlers;

use Drupal\helfi_resilient_logger\Sources\ResilientLogSource;
use ResilientLogger\Handler\ResilientLogHandler as ResilientLogHandlerBase;

class ResilientLogHandler extends ResilientLogHandlerBase {
  /**
   * Same as base ResilientLogHandler but ResilientLogSource passed in by default.
   */
  public function __construct(protected string $logSource = ResilientLogSource::class) {
    parent::__construct($logSource);
  }
}