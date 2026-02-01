# OpenDKIM Debug-Setup Dokumentation

## Status: ✅ Installiert und konfiguriert

### 1. Installation
```bash
apt-get install -y opendkim opendkim-tools
```

### 2. Konfiguration mit Debug-Logging

**Datei: /etc/opendkim.conf**
- `Syslog yes` - Logging aktiviert
- `SyslogSuccess yes` - Erfolgreiche Signaturen loggen
- `LogWhy yes` - Detaillierte Debug-Informationen
- `Socket inet:8891@localhost` - Milter auf Port 8891

### 3. DKIM-Schlüssel generiert

**Speicherort:** `/etc/opendkim/keys/heissa.de/`
- `mail.private` - Privater Schlüssel (600 opendkim:opendkim)
- `mail.txt` - Öffentlicher Schlüssel für DNS

### 4. DNS-Eintrag erforderlich

Fügen Sie folgenden TXT-Record hinzu:

```
mail._domainkey.heissa.de. IN TXT "v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAp0oSC10gqULMDy5zWSfPHDc/ErKVUlp9+BFBl/53+RfLRYlyymemsl4uXMGdvISCd3p5W9PBVhgJmIyiNLCLBBB/XFABuPGIapvKLS9SPbq/VIndn5tP1TB6ive9/XDajAgt1xQvgvJ3Spe5m5wof6h6cqqaPxAUfIHMx0rRBr7gqRxZ8PTJX5AoQYYu2YCssYEpa0WxgGrKdrbacQeY+hXM7o+jOxaXUDwhRuEqtnECLExqq5WG1OqRURSySVEIfZS/iqcJ3DeeuZGN1oIMPpAn4gcRrU46SjMdr1iO6mC7Py9O5ktlWzcjmGXRjZMHFkDhbzZoh8AQWl9f9wKj8wIDAQAB"
```

**Via nsupdate (lokaler BIND9):**
```bash
nsupdate << EOF
server 127.0.0.1
zone heissa.de
update delete mail._domainkey.heissa.de. TXT
update add mail._domainkey.heissa.de. 300 TXT "v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAp0oSC10gqULMDy5zWSfPHDc/ErKVUlp9+BFBl/53+RfLRYlyymemsl4uXMGdvISCd3p5W9PBVhgJmIyiNLCLBBB/XFABuPGIapvKLS9SPbq/VIndn5tP1TB6ive9/XDajAgt1xQvgvJ3Spe5m5wof6h6cqqaPxAUfIHMx0rRBr7gqRxZ8PTJX5AoQYYu2YCssYEpa0WxgGrKdrbacQeY+hXM7o+jOxaXUDwhRuEqtnECLExqq5WG1OqRURSySVEIfZS/iqcJ3DeeuZGN1oIMPpAn4gcRrU46SjMdr1iO6mC7Py9O5ktlWzcjmGXRjZMHFkDhbzZoh8AQWl9f9wKj8wIDAQAB"
send
EOF
```

### 5. Postfix-Integration

**Konfiguration in /etc/postfix/main.cf:**
```
milter_default_action = accept
milter_protocol = 6
smtpd_milters = inet:localhost:8891
non_smtpd_milters = inet:localhost:8891
```

### 6. Services starten

```bash
service opendkim start
service postfix restart
```

### 7. Status prüfen

```bash
# OpenDKIM läuft?
ps aux | grep opendkim

# Port 8891 offen?
lsof -i :8891

# Logs überwachen (auf Produktionssystem mit rsyslog)
tail -f /var/log/mail.log | grep opendkim
```

### 8. Test-E-Mail senden

```bash
php << 'EOF'
<?php
require '/var/www/web2/includes/config.php';
sendEmail('test@example.com', 'DKIM Test',
          getEmailTemplate('<h1>Test</h1><p>DKIM Signatur prüfen</p>'));
?>
EOF
```

### 9. Logs analysieren

**Erwartete Log-Einträge bei erfolgreicher Signatur:**
```
opendkim[PID]: DKIM-Signature field added (s=mail, d=heissa.de)
opendkim[PID]: message from heissa.de signed with key mail
```

**Bei Fehlern:**
```
opendkim[PID]: key data is not secure
opendkim[PID]: cannot load key
opendkim[PID]: signing failed
```

### 10. E-Mail-Header prüfen

Nach Empfang der Test-Mail, prüfen Sie die Header auf:

```
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=heissa.de;
    s=mail; t=...; bh=...; h=...; b=...
```

In Gmail: E-Mail öffnen → ⋮ → "Original anzeigen" → Suche nach "DKIM"

### Debug-Level Optionen

**Noch mehr Debug-Informationen (optional):**

In `/etc/opendkim.conf` hinzufügen:
```
# Noch mehr Debug-Output
LogResults yes
Diagnostics yes
```

Service neu starten:
```bash
service opendkim restart
```

---

**Erstellt:** 2026-02-01
**System:** Ubuntu 24.04
**OpenDKIM Version:** 2.11.0~beta2
