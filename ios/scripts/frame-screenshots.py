#!/usr/bin/env python3
"""Sets each App Store screenshot on an ink ground under one line that says what the screen is for.

It reads the plain screenshots in ios/AppStore/screenshots/raw (taken by store-screenshots.sh) and writes
the listing's images, at the same size, to ios/AppStore/screenshots/en-US:

    python3 ios/scripts/frame-screenshots.py

The colours come from the asset catalog, so they follow resources/css/app.css. The lines are English;
a listing in another language needs its own lines and its own folder.

App Store Connect requires the medium iPhone display (6.3 inch, 1206 x 2622) and takes the large one
(6.9 inch, 1320 x 2868) as an extra. The two have the same proportions to within two pixels, so the
medium images are framed from the large screenshots instead of running the UI tests a second time.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

IOS = Path(__file__).resolve().parents[1]
RAW = IOS / "AppStore/screenshots/raw"
OUT = IOS / "AppStore/screenshots/en-US"
COLOURS = IOS / "EUVATInfo/Resources/Assets.xcassets/Colors"
FONT = "/System/Library/Fonts/SFNS.ttf"

# The end of a screenshot's file name -> the line above it.
CAPTIONS = {
    "rates": "Every VAT rate in Europe",
    "calculator": "Add or remove VAT as you type",
    "country": "Each country in full",
    "compare": "Compare countries side by side",
    "history": "See when rates changed",
    "validator-ready": "Check a VAT number in VIES",
}

# Start of a screenshot's file name -> another size framed from the same screenshot: (prefix, (width, height)).
ALSO = {
    "iphone-69": ("iphone-63", (1206, 2622)),
}


def colour(name: str, dark: bool = False) -> tuple[int, int, int]:
    entries = json.loads((COLOURS / f"{name}.colorset/Contents.json").read_text())["colors"]
    components = entries[1 if dark else 0]["color"]["components"]

    return tuple(round(float(components[channel]) * 255) for channel in ("red", "green", "blue"))  # type: ignore[return-value]


def font(size: int) -> ImageFont.FreeTypeFont:
    face = ImageFont.truetype(FONT, size)

    try:
        face.set_variation_by_name("Semibold")
    except (OSError, ValueError):
        pass  # A system without the variable face still gets the regular weight.

    return face


def wrap(draw: ImageDraw.ImageDraw, text: str, face: ImageFont.FreeTypeFont, width: int) -> list[str]:
    lines: list[str] = []

    for word in text.split():
        candidate = f"{lines[-1]} {word}" if lines else word

        if lines and draw.textlength(candidate, font=face) <= width:
            lines[-1] = candidate
        else:
            lines.append(word)

    if len(lines) == 2:
        # Two lines read better when they are close in length, so try every break and keep the most even.
        words = text.split()
        breaks = [(" ".join(words[:index]), " ".join(words[index:])) for index in range(1, len(words))]
        fitting = [pair for pair in breaks if max(draw.textlength(part, font=face) for part in pair) <= width]

        if fitting:
            lines = list(min(fitting, key=lambda pair: abs(draw.textlength(pair[0], font=face) - draw.textlength(pair[1], font=face))))

    return lines


def frame(source: Path, caption: str, size: tuple[int, int] | None = None) -> Image.Image:
    shot = Image.open(source).convert("RGB")
    width, height = size or shot.size
    tablet = width / height > 0.6
    canvas = Image.new("RGB", (width, height), colour("ink"))
    draw = ImageDraw.Draw(canvas)

    margin = round(width * (0.06 if tablet else 0.075))
    face = font(round(width * (0.05 if tablet else 0.086)))
    leading = round(face.size * 1.1)
    top = round(height * (0.05 if tablet else 0.052))

    lines = wrap(draw, caption, face, width - 2 * margin)

    for index, line in enumerate(lines):
        draw.text((margin, top + index * leading), line, font=face, fill=colour("workspace"))

    # The screen sits under the line, as wide as the margins allow; what does not fit runs off the bottom.
    screen_top = top + len(lines) * leading + round(height * 0.035)
    screen_width = width - 2 * margin
    screen_height = round(screen_width * shot.height / shot.width)
    radius = round(screen_width * (0.035 if tablet else 0.1))
    screen = shot.resize((screen_width, screen_height), Image.LANCZOS)

    oversample = 4
    mask = Image.new("L", (screen_width * oversample, screen_height * oversample), 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, screen_width * oversample - 1, screen_height * oversample - 1), radius * oversample, fill=255)
    canvas.paste(screen, (margin, screen_top), mask.resize((screen_width, screen_height), Image.LANCZOS))

    # A hairline keeps a light screen from bleeding into the ground and a dark one from vanishing into it.
    edge = max(round(width * 0.002), 2)
    draw.rounded_rectangle(
        (margin - edge, screen_top - edge, margin + screen_width + edge - 1, screen_top + screen_height + edge - 1),
        radius + edge,
        outline=colour("lineStrong", dark=True),
        width=edge,
    )

    return canvas


def main() -> None:
    sources = sorted(RAW.glob("*.png"))

    if not sources:
        sys.exit(f"No screenshots in {RAW}. Run ios/scripts/store-screenshots.sh first.")

    OUT.mkdir(parents=True, exist_ok=True)

    for old in OUT.glob("*.png"):
        old.unlink()

    written = 0

    for source in sources:
        caption = next((text for name, text in CAPTIONS.items() if source.stem.endswith(name)), None)

        if caption is None:
            sys.exit(f"No line for {source.name}: add one to CAPTIONS.")

        frame(source, caption).save(OUT / source.name, optimize=True)
        written += 1

        for prefix, (other, size) in ALSO.items():
            if source.name.startswith(prefix):
                frame(source, caption, size).save(OUT / source.name.replace(prefix, other, 1), optimize=True)
                written += 1

    print(f"Framed {written} screenshots into {OUT.relative_to(IOS.parent)}")


if __name__ == "__main__":
    main()
