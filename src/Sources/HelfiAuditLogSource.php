<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger\Sources;

use Drupal\Core\Database\Database;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Sources\Types;
use ResilientLogger\Utils\Helpers;

const TABLE_NAME = 'helfi_audit_logs';

/**
 * @phpstan-import-type LogSourceConfig from Types
 * @phpstan-import-type AuditLogDocument from Types
 */
class HelfiAuditLogSource implements AbstractLogSource {
    private int $id;

    /** @var LogSourceConfig $config */
    private static array $config;

    public function __construct(int $id) {
        $this->id = $id;
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * @return AuditLogDocument
     */
    public function getDocument(): array {
        $result = Database::getConnection()
            ->select(TABLE_NAME, 'h')
            ->fields('h')
            ->condition('id', $this->id)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        $timestamp = strtotime($result['created_at']);
        $createdAt = (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');

        $message = json_decode($result['message'], TRUE);
        $auditEvent = $message["audit_event"];

        return [
            "@timestamp" => $createdAt,
            "audit_event" => [
                "actor" => Helpers::valueAsArray($auditEvent["actor"]),
                "date_time" => $auditEvent["date_time"],
                "operation" => $auditEvent["operation"],
                "origin" => self::$config["origin"],
                "target" => Helpers::valueAsArray($auditEvent["target"]),
                "environment" => self::$config["environment"],
                "message" => $auditEvent["status"],
                "level" => 0,
                "extra" => [
                    "status" => $auditEvent["status"],
                    "source" => $auditEvent["source"],
                    "source_pk" => $this->id,
                    "date_time_epoch" => $auditEvent["date_time_epoch"],
                    "original_origin" => $auditEvent["origin"]
                ],
            ],
        ];
    }

    public function isSent(): bool {
        $result = Database::getConnection()
            ->select(TABLE_NAME, 'h')
            ->fields('h', ['is_sent'])
            ->condition('id', $this->id)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return (bool) $result['is_sent'];
    }

    public function markSent(): void {
        Database::getConnection()
            ->update(TABLE_NAME)
            ->fields(['is_sent' => 1])
            ->condition('id', $this->id)
            ->execute();
    }

    public static function configure(mixed $config): void {
        self::$config = $config;
    }

    public static function create(int $level, mixed $message, array $context = []): AbstractLogSource {
        throw new \LogicException(sprintf('%s does not support create().', static::class));
    }
    
    /** @return \Generator<AbstractLogSource> */
    public static function getUnsentEntries(int $chunkSize): \Generator {
      $results = Database::getConnection()
          ->select(TABLE_NAME, 'h')
          ->fields('h', ['id'])
          ->condition('is_sent', 0)
          ->range(0, $chunkSize)
          ->orderBy('id', 'ASC')
          ->execute()
          ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            yield new HelfiAuditLogSource(intval($result['id']));
        }
    }

    public static function clearSentEntries(int $daysToKeep): void {
        $olderThan = gmdate('Y-m-d H:i:s', time() - ($daysToKeep * 86400));
        Database::getConnection()
            ->delete(TABLE_NAME)
            ->condition('is_sent', 1)
            ->condition('created_at', $olderThan, '<=')
            ->execute();
    }
}
?>
