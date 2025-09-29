<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger;

use ResilientLogger\ResilientLogger as ResilientLoggerBase;
use Drupal\Core\Site\Settings;
use ResilientLogger\Types;

/**
 * @phpstan-import-type ResilientLoggerOptions from Types
 */
class ResilientLogger extends ResilientLoggerBase {
  public static function createFromSettings(Settings $settings): self {
    /** @var ResilientLoggerOptions $options */
    $options = $settings->get('resilient_logger', []);

    return self::create($options);
  }
}

?>