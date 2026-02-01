#!/bin/bash
# Add OpenDKIM milter configuration to Postfix main.cf
# Auf Produktionsserver heissa ausführen!

set -e

echo "=== OpenDKIM zu Postfix hinzufügen ==="
echo ""

cd /etc/postfix

# 1. Backup
echo "1. Backup der aktuellen main.cf..."
cp main.cf main.cf.before_opendkim_$(date +%Y%m%d_%H%M%S)

# 2. Prüfen ob OpenDKIM läuft
echo "2. Prüfe OpenDKIM Status..."
if ! systemctl is-active --quiet opendkim; then
    echo "   OpenDKIM läuft nicht - starte Service..."
    systemctl start opendkim
fi

# 3. Prüfe ob Port 8891 lauscht
echo "3. Prüfe OpenDKIM Port 8891..."
if lsof -i :8891 >/dev/null 2>&1; then
    echo "   ✓ OpenDKIM lauscht auf Port 8891"
else
    echo "   ✗ FEHLER: OpenDKIM lauscht nicht auf Port 8891!"
    echo "   Prüfen Sie /etc/opendkim.conf"
    exit 1
fi

# 4. Milter-Konfiguration hinzufügen
echo "4. Füge OpenDKIM Milter zu main.cf hinzu..."
postconf -e "milter_default_action = accept"
postconf -e "milter_protocol = 6"
postconf -e "smtpd_milters = inet:localhost:8891"
postconf -e "non_smtpd_milters = inet:localhost:8891"

# 5. Konfiguration testen
echo "5. Teste Postfix Konfiguration..."
postfix check

# 6. Postfix neu laden
echo "6. Lade Postfix neu..."
postfix reload

echo ""
echo "=== OpenDKIM erfolgreich aktiviert ==="
echo ""
echo "Konfiguration:"
postconf | grep milter | grep -E "(smtpd_milters|non_smtpd_milters|milter_default_action|milter_protocol)"
echo ""
echo "Test mit:"
echo "  echo 'DKIM Test' | mail -s 'DKIM Signatur Test' gh@gerontec.de"
echo "  tail -f /var/log/mail.log | grep -E '(opendkim|DKIM)'"
echo ""
echo "In der E-Mail-Header sollte erscheinen:"
echo "  DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple; d=heissa.de; s=mail; ..."
