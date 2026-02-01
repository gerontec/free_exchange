#!/bin/bash
# Fix Mail Loop Problem für gh@heissa.de
# ACHTUNG: Auf dem Produktionsserver (heissa) ausführen!

set -e

echo "=== Mail Loop Fix für heissa.de ==="
echo ""

# 1. Backup aktuelle Konfiguration
echo "1. Backup der aktuellen Konfiguration..."
cp /etc/postfix/main.cf /etc/postfix/main.cf.before_loop_fix
cp /etc/postfix/master.cf /etc/postfix/master.cf.before_loop_fix

# 2. Prüfen ob master.cf.backup existiert
if [ -f /etc/postfix/master.cf.backup ]; then
    echo "2. Stelle master.cf.backup wieder her (mit Amavis-Integration)..."
    cp /etc/postfix/master.cf.backup /etc/postfix/master.cf
else
    echo "2. WARNUNG: master.cf.backup nicht gefunden!"
    echo "   Aktuelle master.cf wird beibehalten."
fi

# 3. heissa.de zu mydestination hinzufügen
echo "3. Füge heissa.de zu mydestination hinzu..."
postconf -e "mydestination = \$myhostname, heissa.de, localhost.localdomain, localhost"

# 4. Home-Maildir konfigurieren
echo "4. Konfiguriere home_mailbox = Maildir/..."
postconf -e "home_mailbox = Maildir/"

# 5. myhostname setzen
echo "5. Setze myhostname = mail.heissa.de..."
postconf -e "myhostname = mail.heissa.de"

# 6. /etc/mailname erstellen
echo "6. Erstelle /etc/mailname..."
echo "mail.heissa.de" > /etc/mailname

# 7. Postfix Konfiguration testen
echo "7. Teste Postfix Konfiguration..."
postfix check

# 8. Postfix neu laden
echo "8. Lade Postfix neu..."
postfix reload

echo ""
echo "=== Konfiguration abgeschlossen ==="
echo ""
echo "Wichtige Änderungen:"
echo "  - heissa.de zu mydestination hinzugefügt (lokale Zustellung)"
echo "  - home_mailbox = Maildir/ (Zustellung nach /home/USER/Maildir/)"
echo "  - myhostname = mail.heissa.de"
echo ""
echo "Test mit:"
echo "  echo 'Test' | mail -s 'Loop-Test' gh@heissa.de"
echo "  tail -f /var/log/mail.log"
echo "  ls -la /home/gh/Maildir/new/"
echo ""
echo "Postfix Status:"
postconf mydestination
postconf home_mailbox
postconf myhostname
