#!/usr/bin/env python3
"""
DKIM Test Script - Sendet Datum/Uhrzeit an gh@gerontec.de via SMTP Port 25
OpenDKIM fügt automatisch die DKIM-Signatur hinzu
"""

import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime
import sys

def send_dkim_test_email():
    """Sendet eine Test-Email mit Datum/Uhrzeit"""

    # Email-Konfiguration
    smtp_server = "localhost"
    smtp_port = 25
    from_email = "gh@heissa.de"
    to_email = "gh@gerontec.de"

    # Aktuelles Datum und Uhrzeit
    now = datetime.now()
    timestamp = now.strftime("%Y-%m-%d %H:%M:%S")

    # Email erstellen
    msg = MIMEMultipart('alternative')
    msg['Subject'] = f'DKIM Test - {timestamp}'
    msg['From'] = from_email
    msg['To'] = to_email
    msg['Date'] = now.strftime("%a, %d %b %Y %H:%M:%S %z")

    # Text-Version
    text_body = f"""
DKIM Test Email
===============

Gesendet am: {timestamp}
Von: {from_email}
An: {to_email}

Diese Email wurde via SMTP Port 25 gesendet.
OpenDKIM sollte die DKIM-Signatur automatisch hinzufügen.

Server: {smtp_server}:{smtp_port}
"""

    # HTML-Version
    html_body = f"""
<html>
  <head></head>
  <body>
    <h2>DKIM Test Email</h2>
    <table border="1" cellpadding="5">
      <tr><th>Feld</th><th>Wert</th></tr>
      <tr><td>Gesendet am</td><td><strong>{timestamp}</strong></td></tr>
      <tr><td>Von</td><td>{from_email}</td></tr>
      <tr><td>An</td><td>{to_email}</td></tr>
      <tr><td>Server</td><td>{smtp_server}:{smtp_port}</td></tr>
    </table>
    <p>Diese Email wurde via SMTP Port 25 gesendet.<br>
    OpenDKIM sollte die DKIM-Signatur automatisch hinzufügen.</p>
  </body>
</html>
"""

    # Beide Versionen anhängen
    part_text = MIMEText(text_body, 'plain', 'utf-8')
    part_html = MIMEText(html_body, 'html', 'utf-8')
    msg.attach(part_text)
    msg.attach(part_html)

    try:
        # Verbindung zu SMTP Server
        print(f"Verbinde zu {smtp_server}:{smtp_port}...")
        server = smtplib.SMTP(smtp_server, smtp_port)
        server.set_debuglevel(0)  # 1 für Debug-Ausgabe

        # Email senden
        print(f"Sende Email von {from_email} an {to_email}...")
        server.sendmail(from_email, to_email, msg.as_string())

        # Verbindung schließen
        server.quit()

        print(f"✓ Email erfolgreich gesendet: {timestamp}")
        print(f"\nPrüfe Logs mit:")
        print(f"  tail -20 /var/log/mail.log | grep -E '(opendkim|DKIM)'")
        print(f"\nPrüfe Mailbox:")
        print(f"  ls -lt /home/gh/Maildir/new/ | head -5")

        return True

    except Exception as e:
        print(f"✗ Fehler beim Senden: {e}", file=sys.stderr)
        return False

if __name__ == "__main__":
    success = send_dkim_test_email()
    sys.exit(0 if success else 1)
