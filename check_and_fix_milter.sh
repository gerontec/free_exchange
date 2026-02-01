#!/bin/bash
# Diagnose und Fix für OpenDKIM Milter Integration
# Auf Produktionsserver heissa ausführen!

set -e

echo "=== OpenDKIM Milter Diagnose ==="
echo ""

cd /etc/postfix

# 1. Prüfe aktuelle content_filter Konfiguration
echo "1. Aktuelle content_filter Konfiguration:"
postconf content_filter || echo "   Kein content_filter konfiguriert"

# 2. Prüfe ob Amavis läuft
echo ""
echo "2. Amavis Status:"
if systemctl is-active --quiet amavis 2>/dev/null; then
    echo "   ✓ Amavis läuft"
    AMAVIS_RUNNING=true
else
    echo "   ✗ Amavis läuft nicht"
    AMAVIS_RUNNING=false
fi

# 3. Prüfe master.cf für Re-Injection Services
echo ""
echo "3. Re-Injection Services in master.cf:"
grep -E "^127\.0\.0\.1:(10024|10025|10026|10027)" master.cf || echo "   Keine gefunden"

# 4. Prüfe OpenDKIM Status
echo ""
echo "4. OpenDKIM Status:"
if systemctl is-active --quiet opendkim; then
    echo "   ✓ OpenDKIM läuft"
    if netstat -tapn | grep -q ":8891"; then
        echo "   ✓ Port 8891 lauscht"
    else
        echo "   ✗ Port 8891 lauscht NICHT!"
    fi
else
    echo "   ✗ OpenDKIM läuft nicht"
fi

# 5. Prüfe milter Konfiguration
echo ""
echo "5. Milter Konfiguration:"
echo "   smtpd_milters: $(postconf -h smtpd_milters)"
echo "   non_smtpd_milters: $(postconf -h non_smtpd_milters)"
echo "   milter_default_action: $(postconf -h milter_default_action)"

echo ""
echo "=== Diagnose abgeschlossen ==="
echo ""
echo "EMPFEHLUNG:"
if [ "$AMAVIS_RUNNING" = true ]; then
    echo "  Amavis läuft bereits und verwendet wahrscheinlich 10024/10025/10026."
    echo "  OpenDKIM sollte VOR Amavis laufen (als smtpd_milter)."
    echo "  Die aktuelle Konfiguration sollte funktionieren für SMTP (Port 25/587/465)."
    echo ""
    echo "  Problem: pickup (lokale Mails) nutzen keine milter."
    echo "  Lösung: Mail über SMTP senden statt über pickup:"
    echo "    echo 'Test' | sendmail -f root@heissa.de gh@gerontec.de"
    echo "  Oder: Content-Filter hinzufügen (komplexer, kann mit Amavis kollidieren)"
else
    echo "  Amavis läuft nicht. Content-Filter kann sicher hinzugefügt werden."
    echo "  Führen Sie fix_pickup_milter.sh aus."
fi

echo ""
echo "Test für SMTP (sollte DKIM signieren):"
echo "  telnet localhost 25"
echo "  EHLO heissa.de"
echo "  MAIL FROM: <test@heissa.de>"
echo "  RCPT TO: <gh@gerontec.de>"
echo "  DATA"
echo "  Subject: DKIM Test via SMTP"
echo "  "
echo "  Test message"
echo "  ."
echo "  QUIT"
