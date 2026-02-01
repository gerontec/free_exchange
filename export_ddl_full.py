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

OUTPUT_FILE = './create_tables_full.sql'
PREFIXES = ('lg_', 'gun_', 'em_')

def export_ddl():
    try:
        connection = pymysql.connect(**DB_CONFIG)

        with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
            with connection.cursor() as cursor:
                # 1. TABELLEN UND VIEWS exportieren
                cursor.execute("SHOW TABLES")
                tables = cursor.fetchall()

                if tables:
                    key_name = list(tables[0].keys())[0]
                    #target_tables = [t[key_name] for t in tables if t[key_name].startswith(PREFIXES)]
                    target_tables = [t[key_name] for t in tables]

                    f.write("-- ============================================\n")
                    f.write("-- TABELLEN UND VIEWS\n")
                    f.write("-- ============================================\n\n")

                    for table in target_tables:
                        cursor.execute(f"SHOW CREATE TABLE `{table}`")
                        result = cursor.fetchone()

                        ddl = result.get('Create Table') or result.get('Create View')

                        if ddl:
                            f.write(f"-- Object: {table}\n")
                            f.write(f"{ddl};\n\n")
                            print(f"✓ Tabelle/View: {table}")

                # 2. STORED PROCEDURES exportieren
                cursor.execute("SHOW PROCEDURE STATUS WHERE Db = %s", (DB_CONFIG['database'],))
                procedures = cursor.fetchall()

                target_procs = [p['Name'] for p in procedures if p['Name'].startswith(PREFIXES)]

                if target_procs:
                    f.write("-- ============================================\n")
                    f.write("-- STORED PROCEDURES\n")
                    f.write("-- ============================================\n\n")

                    for proc in target_procs:
                        cursor.execute(f"SHOW CREATE PROCEDURE `{proc}`")
                        result = cursor.fetchone()

                        if result and 'Create Procedure' in result:
                            f.write(f"-- Procedure: {proc}\n")
                            f.write(f"DROP PROCEDURE IF EXISTS `{proc}`;\n")
                            f.write(f"DELIMITER $$\n")
                            f.write(f"{result['Create Procedure']}$$\n")
                            f.write(f"DELIMITER ;\n\n")
                            print(f"✓ Procedure: {proc}")

                # 3. FUNCTIONS exportieren
                cursor.execute("SHOW FUNCTION STATUS WHERE Db = %s", (DB_CONFIG['database'],))
                functions = cursor.fetchall()

                target_funcs = [fn['Name'] for fn in functions if fn['Name'].startswith(PREFIXES)]

                if target_funcs:
                    f.write("-- ============================================\n")
                    f.write("-- FUNCTIONS\n")
                    f.write("-- ============================================\n\n")

                    for func in target_funcs:
                        cursor.execute(f"SHOW CREATE FUNCTION `{func}`")
                        result = cursor.fetchone()

                        if result and 'Create Function' in result:
                            f.write(f"-- Function: {func}\n")
                            f.write(f"DROP FUNCTION IF EXISTS `{func}`;\n")
                            f.write(f"DELIMITER $$\n")
                            f.write(f"{result['Create Function']}$$\n")
                            f.write(f"DELIMITER ;\n\n")
                            print(f"✓ Function: {func}")

                # 4. TRIGGERS exportieren
                cursor.execute("SHOW TRIGGERS")
                triggers = cursor.fetchall()

                target_triggers = [t['Trigger'] for t in triggers if t['Trigger'].startswith(PREFIXES)]

                if target_triggers:
                    f.write("-- ============================================\n")
                    f.write("-- TRIGGERS\n")
                    f.write("-- ============================================\n\n")

                    for trigger in target_triggers:
                        cursor.execute(f"SHOW CREATE TRIGGER `{trigger}`")
                        result = cursor.fetchone()

                        if result and 'SQL Original Statement' in result:
                            f.write(f"-- Trigger: {trigger}\n")
                            f.write(f"DROP TRIGGER IF EXISTS `{trigger}`;\n")
                            f.write(f"DELIMITER $$\n")
                            f.write(f"{result['SQL Original Statement']}$$\n")
                            f.write(f"DELIMITER ;\n\n")
                            print(f"✓ Trigger: {trigger}")

            total = len(target_tables if 'target_tables' in locals() else [])
            total += len(target_procs if 'target_procs' in locals() else [])
            total += len(target_funcs if 'target_funcs' in locals() else [])
            total += len(target_triggers if 'target_triggers' in locals() else [])

            print(f"\n✅ Fertig! {total} Objekte in {OUTPUT_FILE} exportiert.")

    except Exception as e:
        print(f"❌ Ein Fehler ist aufgetreten: {e}")
        import traceback
        traceback.print_exc()
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == "__main__":
    export_ddl()
