<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger\Handlers;

use Drupal\helfi_resilient_logger\Sources\ResilientLogSource;
use ResilientLogger\Handler\ResilientLogHandler as ResilientLogHandlerBase;

class ResilientLogHandler extends ResilientLogHandlerBase {
  /**
   * Same as base ResilientLogHandler but ResilientLogSource passed in by default.
   *
   * @param array<string> $requiredFields
   *   List of required context fields to be passed for the logger. 
   */
  public function __construct(array $requiredFields) {
    parent::__construct(ResilientLogSource::class, $requiredFields);
  }
}