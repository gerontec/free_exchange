<?php
// includes/ticker.php
try {
    $ticker_sql = "SELECT 
                        mkt.code AS Markt, 
                        met.symbol AS Metall, 
                        p_usd.price AS usd_oz,
                        p_eur.price AS eur_oz, 
                        ROUND(p_eur.price / 31.1035 * 1000, 2) AS kg_price,
                        CASE 
                            WHEN met.symbol = 'XAG' AND mkt.code != 'SGE' THEN ROUND((p_eur.price / 31.1035 * 1000) * 1.10, 2)
                            WHEN mkt.code = 'SGE' THEN ROUND(p_eur.price / 31.1035 * 1000, 2)
                            ELSE ROUND((p_eur.price / 31.1035 * 1000) * 1.01, 2)
                        END AS target,
                        p_eur.updated_at AS updated_at
                    FROM em_current_prices p_usd
                    JOIN em_current_prices p_eur ON 
                        p_usd.metal_id = p_eur.metal_id AND 
                        p_usd.market_id = p_eur.market_id AND 
                        p_eur.currency_code = 'EUR'
                    JOIN em_markets mkt ON p_usd.market_id = mkt.market_id
                    JOIN em_metals met ON p_usd.metal_id = met.metal_id
                    WHERE p_usd.currency_code = 'USD'
                      AND mkt.code IN ('LBMA', 'SGE')
                      AND met.symbol IN ('XAG', 'XAU')
                    ORDER BY Markt, Metall";
    
    $ticker_stmt = $pdo->query($ticker_sql);
?>
<div class="table-responsive mt-2 mb-3">
    <table class="table table-sm table-dark table-bordered mb-0" style="font-family: monospace; font-size: 0.72rem;">
        <thead>
            <tr class="text-secondary" style="background: #1a1a1a;">
                <th>Markt</th>
                <th>Metall</th>
                <th class="text-end text-info">US$/oz</th>
                <th class="text-end">EUR/oz</th>
                <th class="text-end">EUR/1kg</th>
                <th class="text-end text-success">eBay Target</th>
                <th class="text-center">Update (UTC)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($t = $ticker_stmt->fetch()): ?>
            <tr>
                <td><?= htmlspecialchars($t['Markt']) ?></td>
                <td><strong><?= htmlspecialchars($t['Metall']) ?></strong></td>
                <td class="text-end text-info fw-bold"><?= number_format($t['usd_oz'], 2, '.', '') ?></td>
                <td class="text-end"><?= number_format($t['eur_oz'], 2, '.', '') ?></td>
                <td class="text-end"><?= number_format($t['kg_price'], 2, '.', '') ?></td>
                <td class="text-end text-success fw-bold"><?= number_format($t['target'], 2, '.', '') ?></td>
                <td class="text-center text-warning" style="font-size: 0.68rem;">
                    <?php 
                        $date = date_create($t['updated_at']);
                        echo $date ? date_format($date, 'd.m.Y H:i:s') : '---';
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php 
} catch (Exception $e) { 
    echo ""; 
} 
?>
