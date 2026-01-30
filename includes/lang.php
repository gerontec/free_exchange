<?php
// Session-Management (Sicherstellen, dass die Session für die Sprachwahl existiert)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 1. SPRACH-ERKENNUNG & SETZUNG
 */
if (isset($_GET['lang'])) {
    // Sprache via URL-Parameter (?lang=en)
    $LANG = $_GET['lang'] === 'en' ? 'en' : 'de';
    $_SESSION['lang'] = $LANG;
    setcookie('lang', $LANG, time() + 86400 * 365, '/');
} elseif (isset($_SESSION['lang'])) {
    // Sprache aus der laufenden Session
    $LANG = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    // Sprache aus dem Cookie (für wiederkehrende Nutzer)
    $LANG = $_COOKIE['lang'];
    $_SESSION['lang'] = $LANG;
} else {
    // Automatische Erkennung via Browser-Einstellung
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'de', 0, 2);
    $LANG = in_array($browser_lang, ['de', 'en']) ? $browser_lang : 'de';
    $_SESSION['lang'] = $LANG;
}

/**
 * 2. ÜBERSETZUNGS-ARRAY
 */
$translations = [
    'de' => [
        // Navigation
        'nav_offers' => 'Angebote',
        'nav_requests' => 'Nachfragen',
        'nav_create_offer' => 'Angebot einstellen',
        'nav_create_request' => 'Nachfrage einstellen',
        'nav_my_offers' => 'Meine Angebote',
        'nav_login' => 'Anmelden',
        'nav_logout' => 'Abmelden',
        
        // Börsen-Switcher
        'exchange_storage' => 'Lagerraumbörse',
        'exchange_guns' => 'Waffenbörse',
        'exchange_select' => 'Wählen Sie Ihre Börse',
        
        // Waffen-spezifisch (Statische Labels)
        'gun_offers' => 'Waffen-Angebote',
        'gun_filter' => 'Filter',
        'gun_category' => 'Kategorie',
        'gun_all_categories' => 'Alle Kategorien',
        'gun_manufacturer' => 'Hersteller',
        'gun_all_manufacturers' => 'Alle Hersteller',
        'gun_caliber' => 'Kaliber',
        'gun_max_price' => 'Max. Preis (€)',
        'gun_condition' => 'Zustand',
        'gun_all_conditions' => 'Alle',
        'gun_condition_neu' => 'Neu',
        'gun_condition_wie_neu' => 'Wie neu',
        'gun_condition_sehr_gut' => 'Sehr gut',
        'gun_condition_gut' => 'Gut',
        'gun_condition_gebraucht' => 'Gebraucht',
        'gun_license' => 'Waffenschein',
        'gun_license_none' => 'Frei ab 18',
        'gun_license_kleiner_waffenschein' => 'Kleiner WS',
        'gun_license_wbk' => 'WBK erforderlich',
        'gun_search' => 'Suchen',
        'gun_reset' => 'Zurücksetzen',
        'gun_important_notes' => 'Wichtige Hinweise',
        'gun_no_offers' => 'Keine Angebote gefunden.',
        'gun_shipping_possible' => 'Versand möglich',
        'gun_details' => 'Details ansehen',
        'gun_vb' => 'VB',

        // Edelmetall-Börse
        'my_metal_offers' => 'Meine Edelmetall-Angebote',
        'deleted_success' => 'Erfolgreich gelöscht',
        'no_metal_offers_yet' => 'Noch keine Angebote',
        'create_first_metal' => 'Erstellen Sie Ihr erstes Edelmetall-Angebot',
        'new_metal_offer' => 'Neues Angebot',
        'create_metal_offer' => 'Angebot erstellen',
        'metal' => 'Metall',
        'quantity' => 'Menge',
        'purity' => 'Feinheit',
        'form' => 'Form',
        'manufacturer' => 'Hersteller',
        'created' => 'Erstellt',
        'days' => 'Tage',
        'ago' => 'her',
        'status_active' => 'Aktiv',
        'status_sold' => 'Verkauft',
        'status_inactive' => 'Inaktiv',
        'total' => 'Gesamt',
        'btn_edit' => 'Bearbeiten',
        'btn_view' => 'Ansehen',
        'btn_delete' => 'Löschen',
        'delete_confirm_title' => 'Löschen bestätigen',
        'delete_confirm_text' => 'Möchten Sie dieses Angebot wirklich löschen?',
        'delete_warning' => 'Diese Aktion kann nicht rückgängig gemacht werden.',
        'btn_cancel' => 'Abbrechen',
        'btn_yes_delete' => 'Ja, löschen',
    ],
    
    'en' => [
        // Navigation
        'nav_offers' => 'Offers',
        'nav_requests' => 'Requests',
        'nav_create_offer' => 'Post Offer',
        'nav_create_request' => 'Post Request',
        'nav_my_offers' => 'My Offers',
        'nav_login' => 'Sign In',
        'nav_logout' => 'Sign Out',
        
        // Exchange Switcher
        'exchange_storage' => 'Storage Exchange',
        'exchange_guns' => 'Firearms Exchange',
        'exchange_select' => 'Select your exchange',
        
        // Waffen-spezifisch (Static Labels)
        'gun_offers' => 'Firearms Offers',
        'gun_filter' => 'Filters',
        'gun_category' => 'Category',
        'gun_all_categories' => 'All Categories',
        'gun_manufacturer' => 'Manufacturer',
        'gun_all_manufacturers' => 'All Manufacturers',
        'gun_caliber' => 'Caliber',
        'gun_max_price' => 'Max. Price (€)',
        'gun_condition' => 'Condition',
        'gun_all_conditions' => 'All',
        'gun_condition_neu' => 'New',
        'gun_condition_wie_neu' => 'Like New',
        'gun_condition_sehr_gut' => 'Very Good',
        'gun_condition_gut' => 'Good',
        'gun_condition_gebraucht' => 'Used',
        'gun_license' => 'License',
        'gun_license_none' => '18+ (No License)',
        'gun_license_kleiner_waffenschein' => 'Small License',
        'gun_license_wbk' => 'License Required',
        'gun_search' => 'Search',
        'gun_reset' => 'Reset',
        'gun_important_notes' => 'Important Notes',
        'gun_no_offers' => 'No offers found.',
        'gun_shipping_possible' => 'Shipping possible',
        'gun_details' => 'View Details',
        'gun_vb' => 'OBO',

        // Precious Metals Exchange
        'my_metal_offers' => 'My Precious Metals Offers',
        'deleted_success' => 'Successfully deleted',
        'no_metal_offers_yet' => 'No offers yet',
        'create_first_metal' => 'Create your first precious metals offer',
        'new_metal_offer' => 'New Offer',
        'create_metal_offer' => 'Create Offer',
        'metal' => 'Metal',
        'quantity' => 'Quantity',
        'purity' => 'Purity',
        'form' => 'Form',
        'manufacturer' => 'Manufacturer',
        'created' => 'Created',
        'days' => 'days',
        'ago' => 'ago',
        'status_active' => 'Active',
        'status_sold' => 'Sold',
        'status_inactive' => 'Inactive',
        'total' => 'Total',
        'btn_edit' => 'Edit',
        'btn_view' => 'View',
        'btn_delete' => 'Delete',
        'delete_confirm_title' => 'Confirm Deletion',
        'delete_confirm_text' => 'Do you really want to delete this offer?',
        'delete_warning' => 'This action cannot be undone.',
        'btn_cancel' => 'Cancel',
        'btn_yes_delete' => 'Yes, delete',
    ],
];

/**
 * 3. HELPER-FUNKTIONEN
 */

// Für statische Texte aus dem Array oben
function t($key) {
    global $translations, $LANG;
    return $translations[$LANG][$key] ?? $key;
}

// Für dynamische Datenbank-Inhalte (z.B. title -> title_en)
function __t($item, $field) {
    global $LANG;
    $field_en = $field . '_en';
    
    // Wenn Englisch gewählt ist UND das Feld in der DB existiert UND nicht leer ist
    if ($LANG === 'en' && isset($item[$field_en]) && !empty($item[$field_en])) {
        return $item[$field_en];
    }
    
    // Standard: Deutsch (Originalfeld)
    return $item[$field] ?? '';
}

// Gibt die aktuelle Sprache zurück (de/en)
function current_lang() {
    global $LANG;
    return $LANG;
}
