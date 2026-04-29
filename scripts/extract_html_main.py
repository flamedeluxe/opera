#!/usr/bin/env python3
"""Вырезает из экспорта WordPress блок между мегаменю и <footer>."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "www" / "local" / "templates" / "uuopera" / "includes"

# Имя файла -> имя выходного include
PAGES = [
    ("afisha.html", "page_afisha.php"),
    ("about.html", "page_about.php"),
    ("contacts.html", "page_contacts.php"),
    ("projects.html", "page_projects.php"),
    ("services.html", "page_services.php"),
    ("news.html", "page_news.php"),
    ("news_item.html", "page_news_item.php"),
    ("afisha_item.html", "page_afisha_item.php"),
]


def extract_main(html: str) -> str:
    if "<footer class=\"footer" not in html:
        raise ValueError("no footer marker")
    prefix, _, _ = html.partition("<footer class=\"footer")
    lines = prefix.splitlines(keepends=True)

    # Ищем с конца тройку закрывающих </div> подряд, после которой идёт основной контент
    for i in range(len(lines) - 3, -1, -1):
        if (
            lines[i].strip() == "</div>"
            and lines[i + 1].strip() == "</div>"
            and lines[i + 2].strip() == "</div>"
        ):
            rest = "".join(lines[i + 3 :]).lstrip("\n")
            if rest.startswith("<div") or rest.startswith("<main"):
                return rest.rstrip()
    raise ValueError("could not find menu/content boundary")


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    for src_name, out_name in PAGES:
        src = ROOT / src_name
        if not src.is_file():
            raise SystemExit(f"missing {src}")
        body = extract_main(src.read_text(encoding="utf-8"))
        (OUT / out_name).write_text(body, encoding="utf-8")
        print(f"OK {src_name} -> includes/{out_name} ({len(body)} bytes)")


if __name__ == "__main__":
    main()
