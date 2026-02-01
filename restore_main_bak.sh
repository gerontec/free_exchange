#!/bin/bash
# Restore main.bak and fix mydestination
# Auf Produktionsserver heissa ausführen!

set -e

echo "=== Restore main.bak und Fix Mail Loop ==="

cd /etc/postfix

# Backup current
cp main.cf main.cf.broken_$(date +%Y%m%d_%H%M%S)

# Restore main.bak
cp main.bak main.cf

# Fix: mydestination muss heissa.de enthalten
# main.bak hat: mydestination = $myhostname, localhost.de, localhost
# und myhostname = heissa.de
# Das sollte funktionieren, aber wir machen es explizit:
postconf -e "mydestination = heissa.de, localhost.de, localhost"

# Check
postconf mydestination
postfix check

# Reload
postfix reload

echo ""
echo "✅ main.bak wiederhergestellt und mydestination korrigiert"
echo "   mydestination = heissa.de, localhost.de, localhost"
echo ""
echo "Test:"
echo "  echo 'Test' | mail -s 'Loop Fix Test' gh@heissa.de"
echo "  tail -f /var/log/mail.log"
