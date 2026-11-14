#!/usr/bin/env python3
"""Builds the asset catalog from the web design system.

Colours come from the `--ui-*` tokens in resources/css/app.css (light and dark), flags from
resources/images/flags and the app icon from the logo geometry in
resources/views/components/ui/logo.blade.php. Run it after the tokens, flags or logo change:

    python3 ios/scripts/generate-assets.py
"""

from __future__ import annotations

import json
import math
import re
import shutil
import sys
from pathlib import Path

REPO = Path(__file__).resolve().parents[2]
CSS = REPO / "resources/css/app.css"
FLAGS = REPO / "resources/images/flags"
RESOURCES = REPO / "ios/EUVATInfo/Resources"
CATALOG = RESOURCES / "Assets.xcassets"

# Token in app.css -> colour name in the catalog (and the generated Swift symbol).
TOKENS = {
    "workspace": "workspace",
    "surface": "surface",
    "surface-subtle": "surfaceSubtle",
    "surface-muted": "surfaceMuted",
    "line": "line",
    "line-strong": "lineStrong",
    "ink": "ink",
    "ink-muted": "inkMuted",
    "ink-quiet": "inkQuiet",
    "action": "action",
    "action-deep": "actionDeep",
    "action-soft": "actionSoft",
    "button": "button",
    "button-edge": "buttonEdge",
    "key-face": "keyFace",
    "key-edge": "keyEdge",
    "success": "success",
    "success-soft": "successSoft",
    "warning": "warning",
    "warning-soft": "warningSoft",
    "danger": "danger",
    "danger-soft": "dangerSoft",
}

INFO = {"author": "xcode", "version": 1}


def block(css: str, selector: str) -> str:
    start = css.index(selector)
    open_brace = css.index("{", start)
    return css[open_brace + 1 : css.index("\n}", open_brace)]


def tokens(body: str) -> dict[str, tuple[float, float, float]]:
    found = re.findall(r"--ui-([a-z0-9-]+):\s*oklch\(([\d.]+)\s+([\d.]+)\s+([\d.]+)\)", body)
    return {name: (float(l), float(c), float(h)) for name, l, c, h in found}


def oklch_to_srgb(lightness: float, chroma: float, hue: float) -> tuple[float, float, float]:
    a = chroma * math.cos(math.radians(hue))
    b = chroma * math.sin(math.radians(hue))
    l = (lightness + 0.3963377774 * a + 0.2158037573 * b) ** 3
    m = (lightness - 0.1055613458 * a - 0.0638541728 * b) ** 3
    s = (lightness - 0.0894841775 * a - 1.2914855480 * b) ** 3
    linear = (
        4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
        -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
        -0.0041960863 * l - 0.7034186147 * m + 1.7076147010 * s,
    )

    def encode(value: float) -> float:
        value = min(max(value, 0.0), 1.0)
        return 12.92 * value if value <= 0.0031308 else 1.055 * value ** (1 / 2.4) - 0.055

    return tuple(encode(value) for value in linear)  # type: ignore[return-value]


def component(rgb: tuple[float, float, float]) -> dict:
    red, green, blue = (f"{value:.4f}" for value in rgb)
    return {"color-space": "srgb", "components": {"red": red, "green": green, "blue": blue, "alpha": "1.000"}}


def write_json(path: Path, data: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, indent=2) + "\n")


def write_colorset(name: str, light: tuple[float, float, float], dark: tuple[float, float, float]) -> None:
    write_json(
        CATALOG / "Colors" / f"{name}.colorset" / "Contents.json",
        {
            "colors": [
                {"idiom": "universal", "color": component(light)},
                {
                    "idiom": "universal",
                    "appearances": [{"appearance": "luminosity", "value": "dark"}],
                    "color": component(dark),
                },
            ],
            "info": INFO,
        },
    )


def colours() -> dict[str, tuple[float, float, float]]:
    css = CSS.read_text()
    light = tokens(block(css, ":root,\n[data-theme='light']"))
    dark = tokens(block(css, "\n[data-theme='dark'] {"))
    shutil.rmtree(CATALOG / "Colors", ignore_errors=True)
    write_json(CATALOG / "Colors" / "Contents.json", {"info": INFO})

    for token, name in TOKENS.items():
        if token not in light or token not in dark:
            sys.exit(f"Token --ui-{token} is missing from {CSS}")
        write_colorset(name, oklch_to_srgb(*light[token]), oklch_to_srgb(*dark[token]))

    # The accent drives tint, selection and focus: cobalt in both themes.
    accent = CATALOG / "AccentColor.colorset" / "Contents.json"
    write_json(accent, json.loads((CATALOG / "Colors" / "action.colorset" / "Contents.json").read_text()))

    return {name: oklch_to_srgb(*light[token]) for token, name in TOKENS.items()}


def flags() -> int:
    shutil.rmtree(CATALOG / "Flags", ignore_errors=True)
    write_json(CATALOG / "Flags" / "Contents.json", {"info": INFO})
    count = 0

    for svg in sorted(FLAGS.glob("*.svg")):
        imageset = CATALOG / "Flags" / f"flag-{svg.stem}.imageset"
        imageset.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(svg, imageset / svg.name)
        write_json(
            imageset / "Contents.json",
            {
                "images": [{"idiom": "universal", "filename": svg.name}],
                "info": INFO,
                "properties": {"preserves-vector-representation": True},
            },
        )
        count += 1

    licence = RESOURCES / "Licenses"
    licence.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(FLAGS / "LICENSE", licence / "flag-icons-license.txt")

    return count


def icon(palette: dict[str, tuple[float, float, float]]) -> None:
    from PIL import Image, ImageDraw

    def rgb(colour: tuple[float, float, float]) -> tuple[int, int, int]:
        return tuple(round(value * 255) for value in colour)  # type: ignore[return-value]

    size, oversample = 1024, 4
    canvas = size * oversample
    image = Image.new("RGB", (canvas, canvas), rgb(palette["ink"]))
    draw = ImageDraw.Draw(image)

    # The logo is drawn on a 32-unit grid around (16, 16); the icon is full bleed, so only the percent mark is kept.
    scale = canvas / 32 * 1.5

    def point(x: float, y: float) -> tuple[float, float]:
        return (canvas / 2 + (x - 16) * scale, canvas / 2 + (y - 16) * scale)

    def disc(x: float, y: float, radius: float, colour: tuple[int, int, int]) -> None:
        cx, cy = point(x, y)
        r = radius * scale
        draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=colour)

    paper, cobalt = rgb(palette["workspace"]), rgb(palette["button"])
    stroke = 2.75
    draw.line((point(10.5, 21.5), point(21.5, 10.5)), fill=paper, width=round(stroke * scale))
    disc(10.5, 21.5, stroke / 2, paper)
    disc(21.5, 10.5, stroke / 2, paper)
    disc(11.25, 11.25, 3.25, paper)
    disc(20.75, 20.75, 3.25, cobalt)

    appicon = CATALOG / "AppIcon.appiconset"
    appicon.mkdir(parents=True, exist_ok=True)
    image.resize((size, size), Image.LANCZOS).save(appicon / "icon-1024.png", optimize=True)
    write_json(
        appicon / "Contents.json",
        {
            "images": [{"idiom": "universal", "platform": "ios", "size": "1024x1024", "filename": "icon-1024.png"}],
            "info": INFO,
        },
    )


def main() -> None:
    write_json(CATALOG / "Contents.json", {"info": INFO})
    palette = colours()
    count = flags()
    icon(palette)
    print(f"Wrote {len(TOKENS)} colours, {count} flags and the app icon to {CATALOG.relative_to(REPO)}")


if __name__ == "__main__":
    main()
