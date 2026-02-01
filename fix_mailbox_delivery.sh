#!/bin/bash
# Fix Postfix mailbox delivery for heissa.de
# Problem: Mail für gh@heissa.de loops back weil keine lokale Zustellung konfiguriert ist

echo "=== Postfix Mailbox-Zustellung konfigurieren ==="

# 1. heissa.de zu mydestination hinzufügen
echo "1. heissa.de zu mydestination hinzufügen..."
postconf -e "mydestination = \$myhostname, heissa.de, localhost.localdomain, localhost"

# 2. Home-Maildir konfigurieren
echo "2. Home-Maildir auf Maildir/ setzen..."
postconf -e "home_mailbox = Maildir/"

# 3. myhostname auf heissa.de setzen
echo "3. myhostname auf mail.heissa.de setzen..."
postconf -e "myhostname = mail.heissa.de"

# 4. /etc/mailname erstellen
echo "4. /etc/mailname erstellen..."
echo "mail.heissa.de" > /etc/mailname

# 5. Lokalen Benutzer gh hinzufügen (falls nicht vorhanden)
if ! id gh &>/dev/null; then
    echo "5. Benutzer gh anlegen..."
    useradd -m -s /bin/bash gh
    # Maildir-Struktur erstellen falls nicht vorhanden
    [ ! -d /home/gh/Maildir ] && mkdir -p /home/gh/Maildir/{new,cur,tmp}
    chown -R gh:gh /home/gh/Maildir
    chmod -R 700 /home/gh/Maildir
else
    echo "5. Benutzer gh existiert bereits"
fi

# 6. Postfix neu laden
echo "6. Postfix neu laden..."
postfix reload

echo ""
echo "=== Konfiguration abgeschlossen ==="
echo ""
echo "Test mit:"
echo "  echo 'Test' | mail -s 'Test' gh@heissa.de"
echo "  mailq"
echo "  ls -la /home/gh/Maildir/new/"
