<?php

namespace Drupal\helfi_resilient_logger\Facade;

use Drupal\helfi_resilient_logger\Entity\ResilientLogEntry;
use ResilientLogger\Facade\AbstractLogFacade;

class ResilientLogFacade implements AbstractLogFacade {
    private ResilientLogEntry $log;

    public function __construct(ResilientLogEntry $log) {
        $this->log = $log;
    }

    public function getId(): int {
        return $this->log->get('id')->value;
    }

    public function getLevel(): int {
        return $this->log->get('level')->value;
    }

    public function getMessage(): mixed {
        return json_decode($this->log->get('message')->value);
    }

    public function getContext(): array {
        return json_decode($this->log->get('context')->value, true);
    }

    public function isSent(): bool {
        return $this->log->get('is_sent')->value;
    }

    public function markSent(): void {
        $this->log->set('is_sent', TRUE);
        $this->log->save();
    }

    public static function create(int $level, mixed $message, array $context = []): AbstractLogFacade {
        $payload = [
            "level" => $level,
            "message" => json_encode($message),
            "context" => json_encode($context),
        ];
        
        $entry = ResilientLogEntry::create($payload);
        $entry->save();

        return new ResilientLogFacade($entry);
    }
    
    /** @return \Generator<AbstractLogFacade> */
    public static function getUnsentEntries(int $chunkSize): \Generator {
        $storage = \Drupal::entityTypeManager()->getStorage("resilient_log_entry");
        $query = $storage->getQuery();
        
        $query->accessCheck(TRUE);
        $query->condition('is_sent', FALSE);
        $query->range(0, $chunkSize);
        $nids = $query->execute();
        
        foreach ($nids as $nid) {
            $entry = ResilientLogEntry::load($nid);
            yield new ResilientLogFacade($entry);
        }
    }

    public static function clearSentEntries(int $daysToKeep): void {
        $olderThan = strtotime(sprintf("%d days ago", $daysToKeep));
        $storage = \Drupal::entityTypeManager()->getStorage("resilient_log_entry");
        $query = $storage->getQuery();

        $query->accessCheck(TRUE);
        $query->condition('is_sent', TRUE);
        $query->condition('created_at', $olderThan, '<=');
        $nids = $query->execute();
        
        foreach (array_chunk($nids, 50) as $chunk) {
            $entries = $storage->loadMultiple($chunk);
            $storage->delete($entries); 
        }
    }
}
?>