<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger;

use Psr\Log\LoggerInterface;
use ResilientLogger\ResilientLogger;
use Drupal\Core\State\StateInterface;

class ResilientLoggerTasks {
  private const PARAMETER_NAME = 'resilient_logger.tasks';
  private const LOGGER_CHANNEL = 'resilient_logger.tasks';
  private const SERVICE_NAME = 'resilient_logger.service';

  private const DEFAULT_NEXT_SUBMIT = "+15min";
  private const DEFAULT_NEXT_CLEAR = "first day of next month midnight";

  private const STATE_KEY_PREV_SUBMIT = "resilient_logger.prev_submit_unsent";
  private const STATE_KEY_PREV_CLEAR = "resilient_logger.prev_clear_sent";

  private string $nextSubmitOffset;
  private string $nextClearOffset;
  private StateInterface $state;
  private LoggerInterface $logger;
  private ResilientLogger $resilientLogger;

  protected function __construct(string $nextSubmitOffset, string $nextClearOffset) {
    $this->nextSubmitOffset = $nextSubmitOffset;
    $this->nextClearOffset = $nextClearOffset;

    $this->state = \Drupal::state();
    $this->logger = \Drupal::logger(self::LOGGER_CHANNEL);
    $this->resilientLogger = \Drupal::service(self::SERVICE_NAME);
  }

  public static function create() {
    $container = \Drupal::getContainer();

    /**
     * @var array{
     *   next_submit: string,
     *   next_clear: string,
     * } $options
     */
    $options = [
      "next_submit" => self::DEFAULT_NEXT_SUBMIT,
      "next_clear" => self::DEFAULT_NEXT_CLEAR,
    ];
    
    if ($container->hasParameter(self::PARAMETER_NAME)) {
      $params = $container->getParameter(self::PARAMETER_NAME);

      if (is_array($params)) {
        $options = $params;
      }
    }
  
    return new static(
      $options["next_submit"],
      $options["next_clear"]
    );
  }
  
  public function handleCronTasks() {
    $now = time();
    $this->handleSubmitEntries($now);
    $this->clearSentEntries($now);
  }

  public function handleSubmitEntries(int $now): void {
    if ($this->nextSubmitOffset == null) {
      $this->logger->info("Skipping submit unsent entries");
    } else if ($this->shouldTrigger(self::STATE_KEY_PREV_SUBMIT, $this->nextSubmitOffset, $now)) {
      $this->logger->info("Submitting unsent entries");
      $this->resilientLogger->submitUnsentEntries();
      $this->state->set(self::STATE_KEY_PREV_SUBMIT, $now);
    }
  }

  public function clearSentEntries(int $now): void {
    if ($this->nextClearOffset == null) {
      $this->logger->info("Skipping clear sent entries");
    } else if ($this->shouldTrigger(self::STATE_KEY_PREV_CLEAR, $this->nextClearOffset, $now)) {
      $this->logger->info("Clearing sent entries");
      $this->resilientLogger->clearSentEntries();
      $this->state->set(self::STATE_KEY_PREV_CLEAR, $now);
    }
  }

  public function shouldTrigger(string $stateKey, string $offset, int $now): bool {
    $prevTriggerAt = $this->state->get($stateKey, 0);
    $nextTriggerAt = strtotime($offset, $prevTriggerAt);
    return ($nextTriggerAt < $now);
  }
}

?>