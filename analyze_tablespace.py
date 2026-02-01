#!/usr/bin/python3
import pymysql

# Konfiguration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'gh',
    'password': 'a12345',
    'database': 'wagodb',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def format_size(bytes_size):
    """Konvertiert Bytes in lesbare Größe (KB, MB, GB)"""
    if bytes_size is None:
        return "0 B"

    for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
        if bytes_size < 1024.0:
            return f"{bytes_size:.2f} {unit}"
        bytes_size /= 1024.0
    return f"{bytes_size:.2f} PB"

def analyze_tablespace():
    try:
        connection = pymysql.connect(**DB_CONFIG)

        with connection.cursor() as cursor:
            # Abfrage für Tabellengröße aus information_schema
            sql = """
                SELECT
                    TABLE_NAME,
                    TABLE_TYPE,
                    ENGINE,
                    TABLE_ROWS,
                    DATA_LENGTH,
                    INDEX_LENGTH,
                    DATA_FREE,
                    (DATA_LENGTH + INDEX_LENGTH) AS TOTAL_SIZE,
                    (DATA_LENGTH + INDEX_LENGTH + DATA_FREE) AS TOTAL_SIZE_WITH_FREE
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = %s
                ORDER BY TOTAL_SIZE DESC
            """

            cursor.execute(sql, (DB_CONFIG['database'],))
            tables = cursor.fetchall()

            print("\n" + "=" * 120)
            print(f"{'TABLE NAME':<40} {'TYPE':<10} {'ENGINE':<10} {'ROWS':>12} {'DATA':>12} {'INDEX':>12} {'TOTAL':>12}")
            print("=" * 120)

            total_data = 0
            total_index = 0
            total_rows = 0

            for table in tables:
                name = table['TABLE_NAME']
                ttype = table['TABLE_TYPE'] or 'N/A'
                engine = table['ENGINE'] or 'VIEW'
                rows = table['TABLE_ROWS'] or 0
                data_len = table['DATA_LENGTH'] or 0
                index_len = table['INDEX_LENGTH'] or 0
                total_size = table['TOTAL_SIZE'] or 0

                total_data += data_len
                total_index += index_len
                total_rows += rows

                print(f"{name:<40} {ttype:<10} {engine:<10} {rows:>12,} {format_size(data_len):>12} {format_size(index_len):>12} {format_size(total_size):>12}")

            print("=" * 120)
            print(f"{'TOTAL':<40} {'':<10} {'':<10} {total_rows:>12,} {format_size(total_data):>12} {format_size(total_index):>12} {format_size(total_data + total_index):>12}")
            print("=" * 120)

            # Top 10 größte Tabellen
            print("\n" + "=" * 80)
            print("TOP 10 GRÖSSTE TABELLEN")
            print("=" * 80)

            for i, table in enumerate(tables[:10], 1):
                name = table['TABLE_NAME']
                total_size = table['TOTAL_SIZE'] or 0
                rows = table['TABLE_ROWS'] or 0
                print(f"{i:>2}. {name:<40} {format_size(total_size):>12} ({rows:,} rows)")

            print("=" * 80)

    except Exception as e:
        print(f"❌ Ein Fehler ist aufgetreten: {e}")
        import traceback
        traceback.print_exc()
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == "__main__":
    analyze_tablespace()
