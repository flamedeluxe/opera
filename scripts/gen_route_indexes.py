#!/usr/bin/env python3
"""Создаёт …/index.php с вызовом uuopera_dispatch_from_script() для сохранения ЧПУ как на uuopera.ru."""
from __future__ import annotations

import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
WWW = ROOT / "www"
TPL = ROOT / "www" / "local" / "templates" / "uuopera"

BOILERPLATE = """<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_dispatch.php';
uuopera_dispatch_from_script();
"""

# Уже есть свои index.php — не перезаписываем
SKIP_EXACT = frozenset(
    {
        "afisha",
        "missiya-i-cennosti",
        "contacts",
        "services",
        "projects",
        "category/news",
    }
)


def collect_paths() -> list[str]:
    r = subprocess.run(
        [
            "grep",
            "-rhoE",
            r'href="/[^"?#]+',
            str(TPL),
            "--include=*.html",
            "--include=*.php",
        ],
        capture_output=True,
        text=True,
        check=True,
    )
    out: set[str] = set()
    for line in r.stdout.splitlines():
        raw = line.removeprefix('href="').split("?")[0].strip()
        if not raw.startswith("/"):
            continue
        rel = raw.strip("/")
        if not rel or rel.startswith("http"):
            continue
        out.add(rel)
    return sorted(out)


def main() -> None:
    paths = collect_paths()
    for rel in paths:
        if rel in SKIP_EXACT:
            continue
        if not rel or rel.startswith("http"):
            continue
        target = WWW.joinpath(*rel.split("/"), "index.php")
        target.parent.mkdir(parents=True, exist_ok=True)
        if target.exists():
            text = target.read_text(encoding="utf-8")
            if "uuopera_dispatch_from_script" in text:
                continue
            if "uuopera_page" in text and "uuopera_dispatch" not in text:
                continue
        target.write_text(BOILERPLATE, encoding="utf-8")
        print("write", target.relative_to(ROOT))


if __name__ == "__main__":
    main()
