<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class RichText {
  private const ALLOWED_FONTS = [
    'arial' => "'Arial', sans-serif",
    'helvetica' => "'Helvetica', sans-serif",
    'times new roman' => "'Times New Roman', serif",
  ];

  private const ALLOWED_STYLES = ['font-weight', 'font-style', 'font-family', 'font-size', 'text-decoration'];

  public static function sanitize($value) {
    if (!is_string($value)) {
      return '';
    }

    $value = trim($value);
    if ($value === '') {
      return '';
    }

    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/<div\b[^>]*>/', '<p>', $value);
    $value = str_replace('</div>', '</p>', $value);

    $value = self::normalize_font_tags($value);

    $allowed = [
      'p'      => ['style' => true],
      'br'     => [],
      'strong' => [],
      'em'     => [],
      'ul'     => ['style' => true],
      'ol'     => ['style' => true],
      'li'     => ['style' => true],
      'span'   => ['style' => true],
    ];

    $value = wp_kses($value, $allowed);
    if ($value === '') {
      return '';
    }

    $value = preg_replace_callback('/style="([^"]*)"/i', function ($matches) {
      $sanitized = self::sanitize_style_attribute($matches[1]);
      return $sanitized === '' ? '' : 'style="' . esc_attr($sanitized) . '"';
    }, $value);

    $value = preg_replace('/<(p|span|li)[^>]*>\s*<\/\1>/', '', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
  }

  private static function normalize_font_tags($value) {
    return preg_replace_callback('/<font([^>]*)>(.*?)<\/font>/is', function ($matches) {
      $attributes = $matches[1] ?? '';
      $inner      = $matches[2] ?? '';
      $styles     = [];

      if (preg_match('/face="([^"]+)"/i', $attributes, $faceMatch)) {
        $font = strtolower(trim($faceMatch[1]));
        if (isset(self::ALLOWED_FONTS[$font])) {
          $styles[] = 'font-family: ' . self::ALLOWED_FONTS[$font];
        }
      }

      if (preg_match('/size="([0-9]+)"/i', $attributes, $sizeMatch)) {
        $size = intval($sizeMatch[1]);
        $px   = 12 + (($size - 3) * 2);
        if ($px >= 10 && $px <= 48) {
          $styles[] = 'font-size: ' . $px . 'px';
        }
      }

      $styleAttr = $styles ? ' style="' . implode('; ', $styles) . '"' : '';

      return '<span' . $styleAttr . '>' . $inner . '</span>';
    }, $value);
  }

  private static function sanitize_style_attribute($style) {
    if (!is_string($style) || trim($style) === '') {
      return '';
    }

    $pairs = array_filter(array_map('trim', explode(';', $style)));
    $allowed = [];

    foreach ($pairs as $pair) {
      [$prop, $rawValue] = array_map('trim', array_pad(explode(':', $pair, 2), 2, ''));
      if ($prop === '' || $rawValue === '') {
        continue;
      }

      $propLower = strtolower($prop);
      if (!in_array($propLower, self::ALLOWED_STYLES, true)) {
        continue;
      }

      switch ($propLower) {
        case 'font-weight':
          $value = strtolower(html_entity_decode($rawValue, ENT_QUOTES, 'UTF-8'));
          if (in_array($value, ['normal', 'bold', '600', '700', '500'], true)) {
            $allowed[] = 'font-weight: ' . $value;
          }
          break;
        case 'font-style':
          $value = strtolower(html_entity_decode($rawValue, ENT_QUOTES, 'UTF-8'));
          if (in_array($value, ['normal', 'italic'], true)) {
            $allowed[] = 'font-style: ' . $value;
          }
          break;
        case 'text-decoration':
          $value = strtolower(html_entity_decode($rawValue, ENT_QUOTES, 'UTF-8'));
          if (in_array($value, ['none', 'underline', 'line-through'], true)) {
            $allowed[] = 'text-decoration: ' . $value;
          }
          break;
        case 'font-family':
          $value = strtolower(html_entity_decode($rawValue, ENT_QUOTES, 'UTF-8'));
          if (isset(self::ALLOWED_FONTS[$value])) {
            $allowed[] = 'font-family: ' . self::ALLOWED_FONTS[$value];
          }
          break;
        case 'font-size':
          $decoded = html_entity_decode($rawValue, ENT_QUOTES, 'UTF-8');
          if (preg_match('/^([0-9]+)px$/i', $decoded, $pxMatch)) {
            $size = intval($pxMatch[1]);
            if ($size >= 10 && $size <= 48) {
              $allowed[] = 'font-size: ' . $size . 'px';
            }
          }
          break;
      }
    }

    return implode('; ', $allowed);
  }
}
