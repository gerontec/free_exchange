#!/bin/bash
# Fix pickup service to use milter via content_filter
# Auf Produktionsserver heissa ausführen!

set -e

echo "=== Pickup Service für Milter konfigurieren ==="

cd /etc/postfix

# 1. Backup
cp master.cf master.cf.before_pickup_milter

# 2. Prüfen ob bereits ein Re-Injection Service existiert
if grep -q "127.0.0.1:10027" master.cf; then
    echo "Re-Injection Service 127.0.0.1:10027 existiert bereits"
else
    echo "Füge Re-Injection Service hinzu..."

    # Re-Injection Service zu master.cf hinzufügen
    cat >> master.cf << 'EOF'

# Re-Injection für OpenDKIM (pickup → milter)
127.0.0.1:10027 inet n - n - - smtpd
  -o syslog_name=postfix/10027/reinjection
  -o content_filter=
  -o smtpd_milters=inet:localhost:8891
  -o non_smtpd_milters=
  -o mynetworks=127.0.0.0/8
  -o smtpd_helo_restrictions=
  -o smtpd_client_restrictions=
  -o smtpd_sender_restrictions=
  -o smtpd_recipient_restrictions=permit_mynetworks,reject
  -o smtpd_data_restrictions=
  -o receive_override_options=no_unknown_recipient_checks
EOF
fi

# 3. Content Filter für pickup in main.cf setzen
echo "Setze content_filter für lokale Mails..."
postconf -e "content_filter = smtp:[127.0.0.1]:10027"

# 4. Konfiguration testen
echo "Teste Postfix Konfiguration..."
postfix check

# 5. Postfix neu laden
echo "Lade Postfix neu..."
postfix reload

echo ""
echo "=== Konfiguration abgeschlossen ==="
echo ""
echo "Jetzt sollten auch lokale Mails (pickup) durch OpenDKIM laufen:"
echo "  pickup → cleanup → content_filter(10027) → smtpd+milter → cleanup → delivery"
echo ""
echo "Test:"
echo "  echo 'DKIM Test' | mail -s 'Pickup Milter Test' gh@gerontec.de"
echo "  tail -f /var/log/mail.log | grep -E '(opendkim|10027)'"
