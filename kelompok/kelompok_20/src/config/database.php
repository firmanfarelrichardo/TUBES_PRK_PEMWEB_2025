<?php

declare(strict_types=1);

final class Database
{
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'myunila_lostfound';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';
    
    private static ?PDO $connection = null;
    
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    self::DB_HOST,
                    self::DB_NAME,
                    self::DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Database Connection Error: ' . $e->getMessage());
                die('Koneksi database gagal. Silakan hubungi administrator.');
            }
        }
        
        return self::$connection;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public static function getHotspots($days = 7) {
        $sql = "
            SELECT 
                l.id,
                l.name,
                l.latitude,
                l.longitude,
                l.location_type,
                COUNT(i.id) as report_count,
                SUM(CASE WHEN i.type = 'lost' THEN 1 ELSE 0 END) as lost_count,
                SUM(CASE WHEN i.type = 'found' THEN 1 ELSE 0 END) as found_count
            FROM locations l
            LEFT JOIN items i ON l.id = i.location_id 
                AND i.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND i.deleted_at IS NULL
            WHERE l.deleted_at IS NULL 
                AND l.latitude IS NOT NULL 
                AND l.longitude IS NOT NULL
            GROUP BY l.id
            HAVING report_count > 0
            ORDER BY report_count DESC
            LIMIT 10
        ";
        
        return self::fetchAll($sql, [$days]);
    }
    
    private function __construct()
    {
    }
    
    private function __clone()
    {
    }
    
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }
}