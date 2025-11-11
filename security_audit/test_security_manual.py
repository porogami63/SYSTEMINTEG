
"""
MediArchive security sanity tests (manual probes)

Runs simple XSS and SQL injection probes against public endpoints to verify
that common payloads are filtered and authentication is not bypassed.

Usage:
  python security_audit/test_security_manual.py [--target http://localhost/SYSTEMINTEG]
"""

import argparse
import re
import sys
from typing import List, Tuple

import requests
from requests import Session


def header(text: str) -> None:
	print("\n" + "=" * 68)
	print(text)
	print("=" * 68 + "\n")


def ok(msg: str) -> None:
	# Avoid non-ASCII symbols to prevent Windows console encoding errors
	print(f"PASS: {msg}")


def fail(msg: str) -> None:
	print(f"FAIL: {msg}")


def contains_unescaped(payload: str, html: str) -> bool:
	"""
	Detect if payload appears unescaped in html. We allow common encodings
	to pass (e.g., &lt;script&gt;) and only fail when raw characters appear.
	"""
	# If the raw payload appears, treat as unescaped
	if payload in html:
		return True
	# Heuristic: if the tag characters are present raw
	if "<script" in html.lower() or "onerror=" in html.lower() or "onload=" in html.lower():
		# If our token string appears inside a tag context, also treat as unescaped
		token = "XSS"
		if token.lower() in html.lower():
			return True
	return False


def test_xss(session: Session, base: str) -> List[Tuple[str, bool, str]]:
	results = []
	header("TESTING XSS PROTECTION")

	# Endpoints to try reflecting content
	targets = [
		# Validate endpoint echos user-provided cert_id when invalid/absent; should be escaped
		(f"{base}/api/validate.php", "cert_id"),
		# Try JSON endpoint; should not execute/reflect HTML
		(f"{base}/api/json.php", "cert_id"),
	]

	payloads = [
		"<script>alert('XSS')</script>",
		"<img src=x onerror=alert('XSS')>",
		"javascript:alert('XSS')",
		"<svg onload=alert('XSS')>",
		"</script><script>alert('XSS')</script>",
	]

	for url, param in targets:
		for p in payloads:
			try:
				resp = session.get(url, params={param: p}, timeout=10)
				html = resp.text
				print(f"Testing payload at {url} param={param}: {p!r}...")

				if resp.status_code >= 500:
					results.append((p, False, "Server error"))
					fail("XSS payload triggered server error")
					continue

				if contains_unescaped(p, html):
					results.append((p, False, "Payload reflected unescaped"))
					fail("XSS payload reflected/likely unescaped")
				else:
					results.append((p, True, "Filtered/sanitized"))
					ok("XSS payload is filtered/sanitized")
			except Exception as e:
				results.append((p, False, f"Request failed: {e}"))
				fail(f"Request failed: {e}")

	return results


def test_sql_injection(session: Session, base: str) -> List[Tuple[str, bool, str]]:
	results = []
	header("TESTING SQL INJECTION PROTECTION")

	login_url = f"{base}/views/login.php"

	# Fetch login page to get CSRF token if present
	try:
		resp = session.get(login_url, timeout=10)
	except Exception as e:
		fail(f"Unable to load login page: {e}")
		return [("load_login", False, str(e))]

	# Extract CSRF token from hidden field
	token_match = re.search(r'name="csrf_token"\s+value="([^"]+)"', resp.text, re.IGNORECASE)
	csrf_token = token_match.group(1) if token_match else ""

	payloads = [
		"admin' OR '1'='1",
		"' OR '1'='1",
		"admin' -- ",
		"' ; DROP TABLE users; --",
		"admin') OR ('1'='1",
	]

	for p in payloads:
		try:
			data = {
				"csrf_token": csrf_token,
				"username": p,
				"password": "invalidpassword",
			}
			resp = session.post(login_url, data=data, allow_redirects=False, timeout=10)
			print(f"Testing payload: {p!r}")

			
			redirected = resp.status_code in (301, 302, 303, 307, 308)
			location = resp.headers.get("Location", "")
			if redirected and "dashboard.php" in location:
				results.append((p, False, "Login bypassed via SQLi"))
				fail("SQL injection allowed login bypass (redirect to dashboard)")
				continue

			if resp.status_code >= 500:
				results.append((p, False, "Server error"))
				fail("SQL injection caused server error")
				continue

			# If we're still here, treat as prevented
			results.append((p, True, "Injection prevented (login failed)"))
			ok("PASS: SQL injection prevented (login failed)")
		except Exception as e:
			results.append((p, False, f"Request failed: {e}"))
			fail(f"Request failed: {e}")

	return results


def main() -> int:
	parser = argparse.ArgumentParser(description="Run simple security probes against MediArchive.")
	parser.add_argument("--target", default="http://localhost/SYSTEMINTEG", help="Base URL of the app")
	args = parser.parse_args()

	base = args.target.rstrip("/")
	print("Starting Security Tests...")
	print(f"Target: {base}\n")

	with requests.Session() as session:
		xss_results = test_xss(session, base)
		sqli_results = test_sql_injection(session, base)

	# Basic exit code: 0 if all pass, 1 otherwise
	all_pass = all(passed for _, passed, _ in xss_results + sqli_results)
	return 0 if all_pass else 1


if __name__ == "__main__":
	sys.exit(main())


