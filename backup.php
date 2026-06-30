<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\backup.php

require_once __DIR__ . '/config/auth.php';

// Enforce access: only Super Admin and Admin can execute backups
require_role(['Super Admin', 'Admin']);

try {
    $tables = [
        'roles', 'users', 'guru', 'kelas', 'siswa', 
        'karyawan', 'nilai', 'ruangan', 'kategori_barang', 
        'inventaris', 'peminjaman', 'alumni', 'pengumuman', 'audit_log'
    ];

    $sql_backup = "-- SD Kristen Petra 1 Database Backup\n";
    $sql_backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sql_backup .= "-- Host: localhost\n";
    $sql_backup .= "-- Database: db_petraschool\n\n";
    $sql_backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Get Create Table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch();
        
        $sql_backup .= "-- Table structure for `$table`\n";
        $sql_backup .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_backup .= $row['Create Table'] . ";\n\n";

        // Get Table Data
        $data_stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $data_stmt->fetchAll();

        if (count($rows) > 0) {
            $sql_backup .= "-- Seeding data for `$table`\n";
            foreach ($rows as $data) {
                $keys = array_keys($data);
                $values = array_values($data);

                // Escape and format values
                $escaped_values = array_map(function($val) use ($pdo) {
                    if ($val === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($val);
                }, $values);

                $sql_backup .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped_values) . ");\n";
            }
            $sql_backup .= "\n";
        }
    }

    $sql_backup .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Write audit log
    write_audit_log("Melakukan backup basis data sistem.");

    // Trigger file download
    $filename = "backup_petraschool_" . date('Ymd_His') . ".sql";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Length: ' . strlen($sql_backup));
    echo $sql_backup;
    exit;

} catch (Exception $e) {
    error_log("Backup error: " . $e->getMessage());
    die("Gagal melakukan backup database. Silakan hubungi administrator.");
}
?>
