<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Config {
  private const MAP = [
    'wa_token' => [
      'option'    => 'hcisysq_wa_token',
      'constants' => ['HCISYSQ_SS_KEY'],
      'env'       => ['HCISYSQ_WA_TOKEN', 'HCISYSQ_SS_KEY'],
      'label'     => 'WhatsApp API token',
    ],
  ];

  public static function init() {
    if (defined('WP_CLI') && WP_CLI) {
      \WP_CLI::add_command('hcisysq migrate-secrets', [__CLASS__, 'cli_migrate_secrets']);
    }
  }

  public static function get(string $key, string $mode = 'auto'): string {
    $map = self::MAP[$key] ?? null;
    if (!$map) {
      return '';
    }

    switch ($mode) {
      case 'option':
        return self::from_option($map['option']);
      case 'constant':
        return self::from_constants($map['constants']) ?? '';
      case 'env':
        return self::from_env($map['env']) ?? '';
      case 'auto':
      default:
        $value = self::from_constants($map['constants']);
        if ($value !== null) {
          return $value;
        }
        $value = self::from_env($map['env']);
        if ($value !== null) {
          return $value;
        }
        return self::from_option($map['option']);
    }
  }

  public static function get_source(string $key): string {
    $map = self::MAP[$key] ?? null;
    if (!$map) {
      return 'none';
    }

    if (self::from_constants($map['constants']) !== null) {
      return 'constant';
    }
    if (self::from_env($map['env']) !== null) {
      return 'env';
    }
    if (self::from_option($map['option']) !== '') {
      return 'option';
    }

    return 'none';
  }

  public static function describe_override(string $key): ?string {
    $map = self::MAP[$key] ?? null;
    if (!$map) {
      return null;
    }

    $source = self::get_source($key);
    if ($source === 'constant') {
      $constant = $map['constants'][0] ?? '';
      if ($constant) {
        return sprintf('Nilai runtime dikendalikan oleh konstanta <code>%s</code>.', \esc_html($constant));
      }
    }
    if ($source === 'env') {
      $env = $map['env'][0] ?? '';
      if ($env) {
        return sprintf('Nilai runtime dibaca dari variabel lingkungan <code>%s</code>.', \esc_html($env));
      }
    }

    return null;
  }

  public static function migrate_constants_to_options(bool $force = false): array {
    $results = [
      'updated' => [],
      'skipped' => [],
      'errors'  => [],
    ];

    foreach (self::MAP as $key => $map) {
      $label = $map['label'] ?? $key;
      $option = $map['option'];
      $constantValue = self::from_constants($map['constants']);

      if ($constantValue === null || $constantValue === '') {
        $results['skipped'][] = sprintf('%s: tidak ada konstanta legacy yang terdefinisi.', $label);
        continue;
      }

      $current = self::from_option($option);
      if (!$force && $current !== '') {
        $results['skipped'][] = sprintf('%s: opsi WordPress sudah terisi, gunakan --force untuk menimpa.', $label);
        continue;
      }

      if (!update_option($option, $constantValue, false)) {
        // update_option returns false when value is unchanged, so treat as success if identical.
        if ($current === $constantValue) {
          $results['skipped'][] = sprintf('%s: opsi sudah memiliki nilai yang sama.', $label);
          continue;
        }
      }

      $results['updated'][] = sprintf('%s: berhasil dipindahkan ke opsi WordPress.', $label);
    }

    return $results;
  }

  public static function cli_migrate_secrets(array $args, array $assoc_args) {
    $force = isset($assoc_args['force']);
    $results = self::migrate_constants_to_options($force);

    foreach ($results['skipped'] as $message) {
      \WP_CLI::log("SKIP: $message");
    }

    foreach ($results['updated'] as $message) {
      \WP_CLI::log("OK: $message");
    }

    if (!empty($results['updated'])) {
      \WP_CLI::success('Migrasi kredensial selesai.');
    } else {
      \WP_CLI::warning('Tidak ada perubahan yang dilakukan.');
    }
  }

  private static function from_option(string $option): string {
    $value = get_option($option, '');
    if (!is_string($value)) {
      return '';
    }
    return trim($value);
  }

  private static function from_constants(array $constants): ?string {
    foreach ($constants as $constant) {
      if (defined($constant)) {
        $value = trim((string) constant($constant));
        if ($value !== '') {
          return $value;
        }
      }
    }

    return null;
  }

  private static function from_env(array $keys): ?string {
    foreach ($keys as $key) {
      $value = getenv($key);
      if ($value === false) {
        continue;
      }
      $value = trim((string) $value);
      if ($value !== '') {
        return $value;
      }
    }

    return null;
  }
}
