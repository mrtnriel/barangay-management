<?php
/**
 * Database & Environment Diagnostic Utility
 * Barangay Health Outreach Management System
 * 
 * Verifies PDO connectivity, TiDB/MySQL engine version, SSL status,
 * and essential table integrity.
 */

// Load database configuration
require_once __DIR__ . '/../config/database.php';

$isCli = (php_sapi_name() === 'cli');

try {
    // 1. Get database server version and current database
    $versionStmt = $pdo->query("SELECT VERSION() AS version, DATABASE() AS db_name, CURRENT_USER() AS db_user");
    $dbMeta = $versionStmt->fetch();

    // 2. Check for TiDB specific variables or engine characteristics
    $isTiDB = (stripos($dbMeta['version'], 'TiDB') !== false);
    
    // Check SSL status
    $sslStmt = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
    $sslRow = $sslStmt->fetch();
    $sslCipher = !empty($sslRow['Value']) ? $sslRow['Value'] : 'None (Plaintext)';

    // 3. Count rows in key tables
    $tableCounts = [];
    $expectedTables = ['residents', 'programs', 'registrations', 'service_records', 'users', 'appointments', 'inventory', 'sessions'];
    
    foreach ($expectedTables as $table) {
        try {
            $cntStmt = $pdo->query("SELECT COUNT(*) AS total FROM `{$table}`");
            $tableCounts[$table] = (int)$cntStmt->fetchColumn();
        } catch (PDOException $ex) {
            $tableCounts[$table] = 'Missing (Not found)';
        }
    }

    if ($isCli) {
        echo "========================================\n";
        echo " [✓] DATABASE CONNECTION SUCCESSFUL!\n";
        echo "========================================\n";
        echo "Server Engine:  " . ($isTiDB ? "TiDB Cloud" : "MySQL/MariaDB") . " (" . $dbMeta['version'] . ")\n";
        echo "Database Name:  " . $dbMeta['db_name'] . "\n";
        echo "Database User:  " . $dbMeta['db_user'] . "\n";
        echo "SSL Cipher:     " . $sslCipher . "\n";
        echo "----------------------------------------\n";
        echo "Table Records:\n";
        foreach ($tableCounts as $tbl => $cnt) {
            echo " - {$tbl}: {$cnt}\n";
        }
        echo "========================================\n";
        exit(0);
    }

    // HTML Output for Browser
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Database Diagnostic - Barangay Health System</title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f7f9f7; color: #202923; padding: 40px 20px; }
        .card { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8e4; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
        .badge-success { background: #dcfce7; color: #15803d; padding: 6px 12px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .meta-table th, .meta-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f0f4f1; font-size: 14px; }
        .meta-table th { color: #64748b; font-weight: 500; width: 40%; }
        .meta-table td { font-weight: 600; }
        a { color: #245b45; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
      </style>
    </head>
    <body>
      <div class="card">
        <span class="badge-success">✓ Database Connected Successfully</span>
        <h2 style="margin: 16px 0 8px;">Barangay Health System Diagnostics</h2>
        <p style="color: #64748b; margin: 0 0 20px; font-size: 14px;">Connectivity and schema verification for Vercel & TiDB Cloud.</p>

        <table class="meta-table">
          <tr><th>Engine Type</th><td><?= $isTiDB ? '⚡ TiDB Cloud (Distributed SQL)' : '🐬 MySQL / MariaDB' ?></td></tr>
          <tr><th>Server Version</th><td><?= htmlspecialchars($dbMeta['version']) ?></td></tr>
          <tr><th>Active Database</th><td><?= htmlspecialchars($dbMeta['db_name']) ?></td></tr>
          <tr><th>Connected User</th><td><?= htmlspecialchars($dbMeta['db_user']) ?></td></tr>
          <tr><th>TLS / SSL Cipher</th><td><?= htmlspecialchars($sslCipher) ?></td></tr>
        </table>

        <h3 style="margin: 24px 0 10px; font-size: 16px;">Verified Tables & Counts</h3>
        <table class="meta-table">
          <?php foreach ($tableCounts as $tbl => $cnt): ?>
            <tr>
              <th><?= htmlspecialchars($tbl) ?></th>
              <td><?= is_numeric($cnt) ? number_format($cnt) . ' records' : '<span style="color:#dc2626;">' . htmlspecialchars($cnt) . '</span>' ?></td>
            </tr>
          <?php endforeach; ?>
        </table>

        <div style="margin-top: 28px; padding-top: 16px; border-top: 1px solid #e2e8e4; display: flex; justify-content: space-between;">
          <a href="index.php">← Return to Application</a>
          <a href="login.php">Go to Sign In →</a>
        </div>
      </div>
    </body>
    </html>
    <?php

} catch (Throwable $e) {
    if ($isCli) {
        echo "========================================\n";
        echo " [✕] DATABASE CONNECTION FAILED\n";
        echo "========================================\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Connection Error - Barangay Health System</title>
      <style>
        body { font-family: sans-serif; background: #fff5f5; color: #991b1b; padding: 40px 20px; }
        .card { max-width: 650px; margin: 0 auto; background: #fff; border: 1px solid #fecaca; border-radius: 12px; padding: 28px; }
        code { background: #fef2f2; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
      </style>
    </head>
    <body>
      <div class="card">
        <h2>Database Connection Error</h2>
        <p>Could not connect to the database. Details:</p>
        <p><code><?= htmlspecialchars($e->getMessage()) ?></code></p>
        <p>If connecting to TiDB Cloud, please verify:</p>
        <ul>
          <li><strong>TIDB_HOST</strong> / <strong>DB_HOST</strong></li>
          <li><strong>TIDB_PORT</strong> (typically <code>4000</code>)</li>
          <li><strong>TIDB_USER</strong> & <strong>TIDB_PASSWORD</strong></li>
          <li><strong>IP Access List:</strong> Ensure <code>0.0.0.0/0</code> is added in TiDB Cloud Security settings.</li>
        </ul>
      </div>
    </body>
    </html>
    <?php
}
