# Resilient Logger

A resilient logger module for Drupal, maintained by the City of Helsinki.

> This module wraps the [City of Helsinki PHP Resilient Logger](https://github.com/City-of-Helsinki/php-resilient-logger) library and provides Drupal-specific abstractions on top.  
> It integrates resilient logging into Drupal's service container, configuration system, and cron.

---

## Table of Contents

- [Features](#features)  
- [Requirements](#requirements)  
- [Installation](#installation)  
- [Configuration](#configuration)  
- [Usage](#usage)  
- [How it works](#how-it-works)  
- [Development & Testing](#development--testing)  

---

## Features

- Reliable logging: entries are stored in a local database table and delivered only during cron runs  
- No log loss if targets are unavailable (retry on next cron)  
- Supports multiple **sources** and **targets**  
- Configurable **batch size**, **chunk size**, and **retention period**  
- Drupal-specific integration: `services.yml`, `settings.php`, and cron hooks  
- Powered by the [php-resilient-logger](https://github.com/City-of-Helsinki/php-resilient-logger) library  

---

## Requirements

- Drupal (see `composer.json` for compatibility)  
- PHP (compatible with your Drupal version)  
- Optional: Elasticsearch (if configured as a target)  

---

## Installation

1. Add the module to your project:  
   ```bash
   composer require drupal/helfi_resilient_logger
   ```

2. Enable it via Drush or the Drupal admin UI:  
   ```bash
   drush en helfi_resilient_logger
   drush cr
   ```

3. Configure it in your `settings.php` and `services.yml`.  

---

## Configuration

The module requires settings in both `settings.php` and `services.yml`.

### `settings.php`

Define sources, targets, and general options:

```php
$settings["resilient_logger"] = [
  "sources" => [
    [
      "class" => 'Drupal\helfi_resilient_logger\Sources\ResilientLogSource'
    ]
  ],
  "targets" => [
    [
      "class" => 'ResilientLogger\Targets\ElasticsearchLogTarget',
      "es_host" => '<elastic_host>',
      "es_port" => 9200,
      "es_scheme" => 'https',
      "es_username" => '<username>',
      "es_password" => '<password>',
      "es_index" => '<index_name>'
    ],
    [
      "class" => 'Drupal\helfi_resilient_logger\Targets\ProxyLogTarget'
    ]
  ],
  "environment" => "<env>",
  "origin" => "<origin>",
  "store_old_entries_days" => 30,
  "batch_limit" => 5000,
  "chunk_size" => 500,
  "schedule_submit_unsent_entries" => "+15min",
  "schedule_clear_sent_entries" => "first day of next month midnight",
];
```

### `services.yml`

Register the logger service and cron task offsets:

```yaml
services:
  resilient_logger.service:
    class: ResilientLogger\ResilientLogger
    factory: ['Drupal\helfi_resilient_logger\ResilientLogger', 'createFromSettings']
    arguments:
      - '@settings'
```

Register resilient logger as monolog channel handler in monolog.services.yml:

```yaml
parameters:
  monolog.channel_handlers:
    resilient: ['resilient_logger']
  ...
services:
  monolog.handler.resilient_logger:
    class: ResilientLogger\Handler\ResilientLogHandler
    arguments: ['Drupal\helfi_resilient_logger\Sources\ResilientLogSource']
  ...
```

### Configuration options

- **sources**: Log sources (e.g. `ResilientLogSource`)  
- **targets**: Log destinations (Elasticsearch, proxy, custom targets)  
- **environment**: Environment identifier (`dev`, `staging`, `production`)  
- **origin**: System/site identifier  
- **store_old_entries_days**: Retention period for delivered entries  
- **batch_limit**: Max entries processed during one cron run  
- **chunk_size**: Number of entries fetched per DB query (prevents memory issues)  
- **schedule_submit_unsent_entries**: Schedule for sending unsent entries
- **schedule_clear_sent_entries**: Schedule for clearing old entries  

---

## Usage

Use Drupal's logger API as usual:

```php
\Drupal::logger('resilient')->warning('Something odd happened: @detail', [
  '@detail' => $some_detail,
]);
```

Entries are stored in the resilient logger's database table.  
They are **not sent immediately** — they are delivered to configured targets when cron runs.

---

## How it works

The heavy lifting (buffering, retrying, targets) is handled by the [php-resilient-logger](https://github.com/City-of-Helsinki/php-resilient-logger) library.  
This Drupal module wires it into Drupal's environment.

### Flow

1. A module logs a message with Drupal's logger API.  
2. The entry is written to a dedicated database table.  
3. On cron:  
   - Entries are read in **chunks** (`chunk_size` entries per query).  
   - Multiple sources can be read from. For example ResilientLogger itself and custom AuditLogSource.
   - Up to **`batch_limit`** entries are processed in a single run.  
   - Each entry is delivered to the configured targets.
   - Successfully delivered entries are marked as sent.  
   - Sent entries (older than `store_old_entries_days`) are purged.
4. If delivery fails, the entry remains in the DB and will be retried on the next cron run.  

---

## Development & Testing

- Clone this repository  
- Run `composer install`  
- Enable module in your local Drupal site  
- Configure via `settings.php` and `services.yml`  
- Run cron to flush logs:  
  ```bash
  drush cron
  ```  
- Run tests with PHPUnit (if provided)  

---