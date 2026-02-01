#!/usr/bin/python3
import pymysql
from datetime import datetime, timedelta

# Konfiguration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'gh',
    'password': 'a12345',
    'database': 'wagodb',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

DAYS_INACTIVE = 7

def format_size(bytes_size):
    """Konvertiert Bytes in lesbare Größe (KB, MB, GB)"""
    if bytes_size is None:
        return "0 B"

    for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
        if bytes_size < 1024.0:
            return f"{bytes_size:.2f} {unit}"
        bytes_size /= 1024.0
    return f"{bytes_size:.2f} PB"

def find_inactive_tables():
    try:
        connection = pymysql.connect(**DB_CONFIG)

        with connection.cursor() as cursor:
            # Finde Tabellen ohne Aktivität in den letzten N Tagen
            sql = """
                SELECT
                    TABLE_NAME,
                    TABLE_ROWS,
                    UPDATE_TIME,
                    CREATE_TIME,
                    DATA_LENGTH,
                    INDEX_LENGTH,
                    (DATA_LENGTH + INDEX_LENGTH) AS TOTAL_SIZE,
                    DATEDIFF(NOW(), COALESCE(UPDATE_TIME, CREATE_TIME)) AS DAYS_INACTIVE
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_TYPE = 'BASE TABLE'
                  AND DATEDIFF(NOW(), COALESCE(UPDATE_TIME, CREATE_TIME)) > %s
                ORDER BY TOTAL_SIZE DESC
            """

            cursor.execute(sql, (DB_CONFIG['database'], DAYS_INACTIVE))
            inactive_tables = cursor.fetchall()

            if not inactive_tables:
                print(f"✓ Keine inaktiven Tabellen gefunden (älter als {DAYS_INACTIVE} Tage).")
                return

            print(f"\n{'='*120}")
            print(f"TABELLEN OHNE AKTIVITÄT IN DEN LETZTEN {DAYS_INACTIVE} TAGEN ({len(inactive_tables)} gefunden)")
            print(f"{'='*120}")
            print(f"{'TABELLENNAME':<40} {'ZEILEN':>12} {'INAKTIV':>10} {'LETZTES UPDATE':<20} {'GRÖSSE':>12}")
            print(f"{'='*120}")

            total_size = 0
            for table in inactive_tables:
                name = table['TABLE_NAME']
                rows = table['TABLE_ROWS'] or 0
                size = table['TOTAL_SIZE'] or 0
                days_inactive = table['DAYS_INACTIVE'] or 0
                last_update = table['UPDATE_TIME'] or table['CREATE_TIME']

                total_size += size

                if last_update:
                    last_update_str = last_update.strftime('%Y-%m-%d %H:%M:%S')
                else:
                    last_update_str = 'Unbekannt'

                print(f"{name:<40} {rows:>12,} {days_inactive:>8}d {last_update_str:<20} {format_size(size):>12}")

            print(f"{'='*120}")
            print(f"GESAMT: {len(inactive_tables)} Tabellen, {format_size(total_size)}")
            print(f"{'='*120}\n")

            # Gruppierung nach Aktivitätszeitraum
            categories = {
                '7-30 Tage': [],
                '30-90 Tage': [],
                '90-365 Tage': [],
                '1+ Jahr': []
            }

            for table in inactive_tables:
                days = table['DAYS_INACTIVE'] or 0
                size = table['TOTAL_SIZE'] or 0

                if days <= 30:
                    categories['7-30 Tage'].append((table['TABLE_NAME'], size))
                elif days <= 90:
                    categories['30-90 Tage'].append((table['TABLE_NAME'], size))
                elif days <= 365:
                    categories['90-365 Tage'].append((table['TABLE_NAME'], size))
                else:
                    categories['1+ Jahr'].append((table['TABLE_NAME'], size))

            print(f"\n{'='*80}")
            print("ZUSAMMENFASSUNG NACH INAKTIVITÄTSDAUER")
            print(f"{'='*80}")

            for category, tables in categories.items():
                if tables:
                    total_cat_size = sum(size for _, size in tables)
                    print(f"\n{category}: {len(tables)} Tabellen ({format_size(total_cat_size)})")
                    for name, size in sorted(tables, key=lambda x: x[1], reverse=True)[:5]:
                        print(f"  - {name:<45} {format_size(size):>12}")
                    if len(tables) > 5:
                        print(f"  ... und {len(tables) - 5} weitere")

            print(f"\n{'='*80}")

    except Exception as e:
        print(f"❌ Ein Fehler ist aufgetreten: {e}")
        import traceback
        traceback.print_exc()
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == "__main__":
    find_inactive_tables()
