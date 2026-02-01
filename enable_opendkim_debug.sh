#!/bin/bash
# Enable maximum debug logging for OpenDKIM
# Auf Produktionsserver heissa ausführen!

set -e

echo "=== OpenDKIM Debug-Level erhöhen ==="

# 1. Backup
cp /etc/opendkim.conf /etc/opendkim.conf.before_debug

# 2. Debug-Level auf Maximum setzen
cat > /etc/opendkim.conf << 'EOF'
# OpenDKIM Configuration - MAXIMUM DEBUG LOGGING

# Logging - MAXIMUM DEBUG
Syslog yes
SyslogSuccess yes
LogWhy yes
LogResults yes

# Socket für Postfix (Port 8891 auf localhost)
Socket inet:8891@localhost

# Modus: Signieren und Verifizieren
Mode sv

# Signatur-Algorithmus
SignatureAlgorithm rsa-sha256

# Canonicalization (relaxed/simple für beste Kompatibilität)
Canonicalization relaxed/simple

# Key-Tabellen
KeyTable /etc/opendkim/key_table
SigningTable refile:/etc/opendkim/signing_table
ExternalIgnoreList refile:/etc/opendkim/trusted_hosts
InternalHosts refile:/etc/opendkim/trusted_hosts

# User und Group
UserID opendkim:opendkim

# PID-Datei
PidFile /run/opendkim/opendkim.pid

# Temporäres Verzeichnis
TemporaryDirectory /var/tmp

# Umask
UMask 002
EOF

# 3. OpenDKIM neu starten
systemctl restart opendkim

# 4. Status prüfen
echo ""
echo "OpenDKIM Status:"
systemctl status opendkim --no-pager -l | head -20

echo ""
echo "Port 8891:"
netstat -tapn | grep 8891

echo ""
echo "=== Debug-Level erhöht ==="
echo "Sende jetzt eine Test-Mail und überwache /var/log/mail.log"
