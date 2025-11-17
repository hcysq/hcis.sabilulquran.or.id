"""Utility to verify color contrast ratios for the YSQ theme palette."""
from __future__ import annotations

from typing import Dict, Iterable, Tuple

PALETTE: Dict[str, str] = {
    "primary": "#0f4c81",
    "primary_accent": "#0a75c2",
    "primary_hover": "#0f4470",
    "text": "#0f172a",
    "muted": "#475569",
    "muted_soft": "#64748b",
    "surface": "#ffffff",
    "surface_alt": "#f5f7fb",
    "chip": "#e3ebf1",
}

PAIRINGS: Iterable[Tuple[str, str]] = (
    ("text", "surface"),
    ("text", "surface_alt"),
    ("primary", "surface"),
    ("primary_accent", "surface"),
    ("primary_hover", "surface"),
    ("primary", "chip"),
    ("muted", "surface"),
    ("muted_soft", "surface"),
)


def _relative_luminance(hex_color: str) -> float:
    hex_color = hex_color.lstrip("#")
    red = int(hex_color[0:2], 16) / 255
    green = int(hex_color[2:4], 16) / 255
    blue = int(hex_color[4:6], 16) / 255

    def adjust(channel: float) -> float:
        if channel <= 0.03928:
            return channel / 12.92
        return ((channel + 0.055) / 1.055) ** 2.4

    red, green, blue = (adjust(red), adjust(green), adjust(blue))
    return 0.2126 * red + 0.7152 * green + 0.0722 * blue


def _contrast_ratio(color_a: str, color_b: str) -> float:
    luminance_a = _relative_luminance(color_a)
    luminance_b = _relative_luminance(color_b)
    lighter, darker = max(luminance_a, luminance_b), min(luminance_a, luminance_b)
    return (lighter + 0.05) / (darker + 0.05)


def main() -> None:
    print("YSQ color palette contrast check\n")
    for foreground, background in PAIRINGS:
        color_a = PALETTE[foreground]
        color_b = PALETTE[background]
        ratio = _contrast_ratio(color_a, color_b)
        status = "PASS" if ratio >= 4.5 else "FAIL"
        print(f"{foreground} on {background}: {ratio:.2f} ({status})")


if __name__ == "__main__":
    main()
