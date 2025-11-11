#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OWASP ZAP runner for MediArchive

Prereqs:
  - OWASP ZAP running (desktop or daemon) and listening on localhost:8080
  - Optional API key if your ZAP requires it
  - pip install -r security_audit/requirements.txt

Usage:
  python security_audit/zap.py --target http://localhost/SYSTEMINTEG --apikey <ZAP_API_KEY>

What it does:
  1) Initiates a spider crawl on the target
  2) Runs an active scan
  3) Exports an HTML report to security_audit/zap_report.html
     and a JSON report to security_audit/zap_report.json
"""

import argparse
import os
import sys
import time

from zapv2 import ZAPv2


def wait_for_spider(zap: ZAPv2, scan_id: str) -> None:
	while int(zap.spider.status(scan_id)) < 100:
		time.sleep(1)


def wait_for_ascan(zap: ZAPv2, scan_id: str) -> None:
	while int(zap.ascan.status(scan_id)) < 100:
		time.sleep(2)


def main() -> int:
	parser = argparse.ArgumentParser(description="Run OWASP ZAP spider+active scan and export report.")
	parser.add_argument("--target", default="http://localhost/SYSTEMINTEG", help="Target base URL")
	parser.add_argument("--zap-host", default="127.0.0.1", help="ZAP host")
	parser.add_argument("--zap-port", default="8080", help="ZAP port")
	parser.add_argument("--apikey", default=None, help="ZAP API key (if enabled)")
	parser.add_argument("--timeout", type=int, default=900, help="Overall timeout in seconds")
	args = parser.parse_args()

	target = args.target.rstrip("/")
	proxy = f"http://{args.zap_host}:{args.zap_port}"

	print(f"Connecting to ZAP at {proxy}")
	zap = ZAPv2(apikey=args.apikey, proxies={"http": proxy, "https": proxy})

	# Access target to add to the Sites tree
	print(f"Accessing target: {target}")
	zap.urlopen(target)  # type: ignore[attr-defined]
	time.sleep(2)

	# Spider
	print("Starting spider...")
	spider_id = zap.spider.scan(target)  # type: ignore[attr-defined]
	wait_for_spider(zap, spider_id)
	print("Spider completed.")

	# Active scan
	print("Starting active scan...")
	ascan_id = zap.ascan.scan(target)  # type: ignore[attr-defined]
	wait_for_ascan(zap, ascan_id)
	print("Active scan completed.")

	# Reports
	out_dir = os.path.dirname(__file__)
	html_path = os.path.join(out_dir, "zap_report.html")
	json_path = os.path.join(out_dir, "zap_report.json")

	print(f"Writing HTML report: {html_path}")
	with open(html_path, "w", encoding="utf-8") as f:
		f.write(zap.core.htmlreport())  # type: ignore[attr-defined]

	print(f"Writing JSON report: {json_path}")
	with open(json_path, "w", encoding="utf-8") as f:
		f.write(zap.core.jsonreport())  # type: ignore[attr-defined]

	print("ZAP scan complete.")
	print(f"- HTML: {html_path}")
	print(f"- JSON: {json_path}")

	return 0


if __name__ == "__main__":
	sys.exit(main())


