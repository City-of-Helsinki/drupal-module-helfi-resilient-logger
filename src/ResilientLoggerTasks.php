<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger;

use Psr\Log\LoggerInterface;
use ResilientLogger\ResilientLogger;
use Drupal\Core\State\StateInterface;

class ResilientLoggerTasks {
  private const SETTINGS_NAME = "resilient_logger";
  private const LOGGER_CHANNEL = "resilient_logger.tasks";
  private const SERVICE_NAME = "resilient_logger.service";

  private const DEFAULT_OFFSET_SUBMIT = "+15min";
  private const DEFAULT_OFFSET_CLEAR = "first day of next month midnight";

  private const PARAM_KEY_OFFSET_SUBMIT = "schedule_submit_unsent_entries";
  private const PARAM_KEY_OFFSET_CLEAR = "schedule_clear_sent_entries";

  private const STATE_KEY_PREV_SUBMIT = "resilient_logger.prev_submit_unsent";
  private const STATE_KEY_PREV_CLEAR = "resilient_logger.prev_clear_sent";

  private string $submitDateOffset;
  private string $clearDateOffset;
  private StateInterface $state;
  private LoggerInterface $logger;
  private ResilientLogger $service;

  /**
   * Drupal-specific helper for scheduling ResilientLogger tasks.
   * Tasks are not executed immediately but are evaluated during Drupal's cron runs.
   * Each task runs conditionally based on time offsets and the last execution time,
   * not on every cron trigger.
   * @see https://www.php.net/manual/en/function.strtotime.php
   * 
   * @param string $submitDateOffset
   *   String representation of the next time submit unsent entries task should run.
   *   Defaults to +15min from previous one.
   * @param string $clearDateOffset
   *   String representation of the next time clear old entries task should run.
   *   Defaults to first day of the next month at 00:00.
   * 
   */
  public function __construct(
    ?string $submitDateOffset,
    ?string $clearDateOffset
  ) {
    $this->submitDateOffset = $submitDateOffset;
    $this->clearDateOffset = $clearDateOffset;

    $this->state = \Drupal::state();
    $this->logger = \Drupal::logger(self::LOGGER_CHANNEL);
    $this->service = \Drupal::service(self::SERVICE_NAME);
  }

  /**
   * Factory method to construct ResilientLoggerTasks instance from optional
   * parameters from Drupal configuration. 
   * 
   * This method looks for parameter block "resilient_logger.tasks" for values 
   * of "offset_submit" and "offset_clear". If these are not found, defaults
   * will be used instead.
   */
  public static function create() {
    /** @var \Drupal\Core\Site\Settings $settings */
    $settings = \Drupal::service('settings');

    // Retrieve your resilient_logger settings.
    $config = $settings->get(self::SETTINGS_NAME, []);
    $submitDateOffset = $config[self::PARAM_KEY_OFFSET_SUBMIT] ?? self::DEFAULT_OFFSET_SUBMIT;
    $clearDateOffset = $config[self::PARAM_KEY_OFFSET_CLEAR] ?? self::DEFAULT_OFFSET_CLEAR;

    return new static($submitDateOffset, $clearDateOffset);
  }
  
  public function handleTasks(int $currentTime) {
    $this->handleSubmitUnsentEntries($currentTime);
    $this->handleClearSentEntries($currentTime);
  }

  public function handleSubmitUnsentEntries(int $currentTime) {
    $shouldSubmitUnsent = $this->isTaskDue(
      self::STATE_KEY_PREV_SUBMIT,
      $this->submitDateOffset,
      $currentTime
    );

    if ($shouldSubmitUnsent) {
      $this->logger->info("Submitting unsent entries");
      $this->service->submitUnsentEntries();
      $this->state->set(self::STATE_KEY_PREV_SUBMIT, $currentTime);
    }
  }

  public function handleClearSentEntries(int $currentTime) {
    $shouldClearSent = $this->isTaskDue(
      self::STATE_KEY_PREV_CLEAR,
      $this->clearDateOffset,
      $currentTime
    );

    if ($shouldClearSent) {
      $this->logger->info("Clearing sent entries");
      $this->service->submitUnsentEntries();
      $this->state->set(self::STATE_KEY_PREV_CLEAR, $currentTime);
    }
  }
  
  public function isTaskDue(
    string $stateKey,
    string $dateOffset,
    int $currentTime
  ): bool {
    if ($dateOffset == null) {
      return false;
    }

    $prevTriggerAt = $this->state->get($stateKey, 0);
    $nextTriggerAt = strtotime($dateOffset, $prevTriggerAt);

    if ($nextTriggerAt === false) {
      return false;
    }

    return $nextTriggerAt < $currentTime;
  }
}

?>