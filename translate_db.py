#!/usr/bin/python3
import pymysql
import time
from deep_translator import GoogleTranslator

# Konfiguration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'gh',
    'password': 'a12345',
    'database': 'wagodb',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

# Mapping: Tabelle -> (ID-Spalte, Deutsche Spalte, Englische Spalte)
TRANSLATION_MAP = [
    ('gun_categories', 'category_id', 'name_de', 'name_en'),
    ('gun_categories', 'category_id', 'description_de', 'description_en'),
    ('gun_listings', 'listing_id', 'title', 'title_en'),
    ('gun_listings', 'listing_id', 'description_de', 'description_en'),
    ('gun_requests', 'request_id', 'description_de', 'description_en'),
    ('lg_lagerraeume', 'lagerraum_id', 'bemerkung_de', 'bemerkung_en'),
    ('lg_suchanfragen', 'anfrage_id', 'ort_wunsch_de', 'ort_wunsch_en'),
    ('lg_bilder', 'bild_id', 'beschreibung_de', 'beschreibung_en')
]

def translate_content():
    translator = GoogleTranslator(source='de', target='en')
    
    try:
        connection = pymysql.connect(**DB_CONFIG)
        print("Starte erzwungene Übersetzung (Überschreiben aktiv)...")

        with connection.cursor() as cursor:
            for table, id_col, col_de, col_en in TRANSLATION_MAP:
                # Modifiziertes SQL: Holt alle Zeilen, egal ob EN leer ist oder nicht
                sql_select = f"SELECT {id_col}, `{col_de}` FROM `{table}` WHERE `{col_de}` IS NOT NULL AND `{col_de}` != ''"
                cursor.execute(sql_select)
                rows = cursor.fetchall()

                if not rows:
                    continue

                print(f"\nVerarbeite {len(rows)} Einträge in Tabelle '{table}' ({col_de} -> {col_en})...")

                for row in rows:
                    text_de = row[col_de]
                    
                    try:
                        # Übersetzung durchführen
                        translated_text = translator.translate(text_de)
                        
                        # Update in der DB (überschreibt Existierendes)
                        sql_update = f"UPDATE `{table}` SET `{col_en}` = %s WHERE {id_col} = %s"
                        cursor.execute(sql_update, (translated_text, row[id_col]))
                        connection.commit()
                        print(f"  [OK] ID {row[id_col]}: '{text_de[:20]}...' -> '{translated_text[:20]}...'")
                        
                        # Kurze Pause, um Google-Sperren bei vielen Anfragen zu vermeiden
                        time.sleep(0.2) 
                        
                    except Exception as translate_error:
                        print(f"  [Fehler] ID {row[id_col]} konnte nicht übersetzt werden: {translate_error}")

        print("\nÜbersetzung abgeschlossen!")

    except Exception as e:
        print(f"Ein schwerwiegender Fehler ist aufgetreten: {e}")
    finally:
        if 'connection' in locals():
            connection.close()

if __name__ == "__main__":
    translate_content()
