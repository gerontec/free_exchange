#!/usr/bin/python3
import pymysql

# Konfiguration (Passwort bitte anpassen)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'gh',
    'password': 'a12345', 
    'database': 'wagodb',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

OUTPUT_FILE = './create_tables.sql'
PREFIXES = ('lg_', 'gun_')

def export_ddl():
    try:
        connection = pymysql.connect(**DB_CONFIG)
        
        with connection.cursor() as cursor:
            cursor.execute("SHOW TABLES")
            tables = cursor.fetchall()
            
            if not tables:
                print("Keine Tabellen in der DB gefunden.")
                return
            
            key_name = list(tables[0].keys())[0]
            target_tables = [t[key_name] for t in tables if t[key_name].startswith(PREFIXES)]

            with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
                for table in target_tables:
                    cursor.execute(f"SHOW CREATE TABLE `{table}`")
                    result = cursor.fetchone()
                    
                    # Hier ist die Korrektur: Wir prüfen, ob es eine Table oder eine View ist
                    # 'Create Table' für echte Tabellen, 'Create View' für Views
                    ddl = result.get('Create Table') or result.get('Create View')
                    
                    if ddl:
                        f.write(f"-- Object: {table}\n")
                        f.write(f"{ddl};\n\n")
                        print(f"Exportiert: {table}")
                    else:
                        print(f"Warnung: Konnte DDL für {table} nicht lesen.")

            print(f"\nFertig! {len(target_tables)} Objekte in {OUTPUT_FILE} exportiert.")

    except Exception as e:
        print(f"Ein Fehler ist aufgetreten: {e}")
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == "__main__":
    export_ddl()
