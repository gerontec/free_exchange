#!/usr/bin/python3
import pymysql
import sys

# Konfiguration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'gh',
    'password': 'a12345',
    'database': 'wagodb',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def drop_backup_tables():
    try:
        connection = pymysql.connect(**DB_CONFIG)

        with connection.cursor() as cursor:
            # Finde alle Tabellen mit "backup" im Namen
            sql = """
                SELECT TABLE_NAME, TABLE_ROWS,
                       DATA_LENGTH, INDEX_LENGTH,
                       (DATA_LENGTH + INDEX_LENGTH) AS TOTAL_SIZE
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME LIKE '%%backup%%'
                  AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            """

            cursor.execute(sql, (DB_CONFIG['database'],))
            backup_tables = cursor.fetchall()

            if not backup_tables:
                print("✓ Keine Backup-Tabellen gefunden.")
                return

            print(f"\n{'='*80}")
            print(f"GEFUNDENE BACKUP-TABELLEN ({len(backup_tables)})")
            print(f"{'='*80}")

            total_size = 0
            for table in backup_tables:
                name = table['TABLE_NAME']
                rows = table['TABLE_ROWS'] or 0
                size = table['TOTAL_SIZE'] or 0
                total_size += size

                size_mb = size / (1024 * 1024)
                print(f"  {name:<45} {rows:>12,} rows  {size_mb:>10.2f} MB")

            print(f"{'='*80}")
            print(f"GESAMT: {len(backup_tables)} Tabellen, {total_size/(1024*1024):.2f} MB")
            print(f"{'='*80}\n")

            # Bestätigung
            confirm = input("Möchten Sie diese Tabellen wirklich löschen? (ja/nein): ").strip().lower()

            if confirm not in ['ja', 'j', 'yes', 'y']:
                print("❌ Abgebrochen.")
                return

            # Tabellen löschen
            print("\n🗑️  Lösche Backup-Tabellen...\n")
            dropped_count = 0

            for table in backup_tables:
                table_name = table['TABLE_NAME']
                try:
                    cursor.execute(f"DROP TABLE `{table_name}`")
                    print(f"✓ Gelöscht: {table_name}")
                    dropped_count += 1
                except Exception as e:
                    print(f"❌ Fehler beim Löschen von {table_name}: {e}")

            connection.commit()

            print(f"\n{'='*80}")
            print(f"✅ {dropped_count} von {len(backup_tables)} Tabellen erfolgreich gelöscht.")
            print(f"💾 {total_size/(1024*1024):.2f} MB freigegeben.")
            print(f"{'='*80}")

    except Exception as e:
        print(f"❌ Ein Fehler ist aufgetreten: {e}")
        import traceback
        traceback.print_exc()
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == "__main__":
    drop_backup_tables()
