<?php

declare(strict_types=1);

namespace Drupal\helfi_resilient_logger\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the resilient_log_entry entity.
 * Context field is missing from below since it's optional field.
 *
 * @ingroup resilient_log_entry
 *
 * @ContentEntityType(
 *   id = "resilient_log_entry",
 *   label = @Translation("resilient_log_entry"),
 *   base_table = "resilient_log_entry",
 *   entity_keys = {
 *     "id" = "id",
 *     "is_sent" = "is_sent",
 *     "level" = "level",
 *     "message" = "message",
 *     "created_at" = "created_at",
 *   },
 * )
 */
class ResilientLogEntry extends ContentEntityBase implements ContentEntityInterface {
    public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
      $fields['id'] = BaseFieldDefinition::create('integer')
        ->setLabel(t('id'))
        ->setDescription(t('The ID of the entry.'))
        ->setReadOnly(TRUE);
  
      $fields['is_sent'] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('is_sent'))
        ->setDescription(t('Flag showing if the log entry was sent out.'))
        ->setDefaultValue(FALSE)
        ->setReadOnly(FALSE);

      $fields['level'] = BaseFieldDefinition::create('integer')
        ->setLabel(t('level'))
        ->setDescription(t('Level for this log message.'))
        ->setReadOnly(TRUE);

      $fields['message'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('message'))
        ->setDescription(t('Actual message of this log message.'))
        ->setReadOnly(TRUE);

      $fields['context'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('context'))
        ->setDescription(t('Context for this message.'))
        ->setReadOnly(TRUE);
  
      $fields['created_at'] = BaseFieldDefinition::create('created')
        ->setLabel(t('created_at'))
        ->setDescription(t('Timestamp when the resilient log entry was created.'))
        ->setReadOnly(TRUE);

      return $fields;
    }
}
?>