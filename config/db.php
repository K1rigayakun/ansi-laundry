<?php
/**
 * Supabase Database Wrapper
 * Menggantikan mysqli dengan Supabase PostgreSQL via REST API
 * 
 * Cara pakai:
 *   require_once 'db.php';
 *   // $conn sudah tersedia sebagai instance SupabaseDB
 */

class SupabaseDB {
    private $url;
    private $key;
    private $connString;
    private $pgConn = null;
    
    public function __construct($supabaseUrl, $serviceRoleKey, $connString = '') {
        $this->url = rtrim($supabaseUrl, '/');
        $this->key = $serviceRoleKey;
        $this->connString = $connString;
    }
    
    /**
     * Get PostgreSQL connection (lazy init)
     */
    private function getPg() {
        if ($this->pgConn === null) {
            $this->pgConn = pg_connect($this->connString);
            if (!$this->pgConn) {
                throw new Exception('PostgreSQL Connection Failed');
            }
            pg_set_client_encoding($this->pgConn, 'UTF8');
        }
        return $this->pgConn;
    }
    
    /**
     * Execute a raw query
     */
    public function query($sql) {
        $pg = $this->getPg();
        $result = pg_query($pg, $sql);
        if ($result === false) {
            throw new Exception('Query Error: ' . pg_last_error($pg));
        }
        return new SupabaseResult($result, $pg);
    }
    
    /**
     * Prepare a statement (convert ? placeholders to $1, $2, ...)
     */
    public function prepare($sql) {
        $counter = 0;
        $pgSql = preg_replace_callback('/\?/', function($matches) use (&$counter) {
            $counter++;
            return '$' . $counter;
        }, $sql);
        
        return new SupabaseStatement($this->getPg(), $pgSql);
    }
    
    public function set_charset($charset) {
        // dummy method for mysqli compatibility
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->pgConn !== null) {
            pg_close($this->pgConn);
            $this->pgConn = null;
        }
    }
    
    public function getError() {
        if ($this->pgConn) {
            return pg_last_error($this->pgConn);
        }
        return '';
    }
    
    public $connect_error = null;
    public $error = '';
}

class SupabaseStatement {
    private $pg;
    private $sql;
    private $params = [];
    private $result = null;
    public $error = '';
    public $insert_id = 0;
    public $num_rows = 0;
    public $affected_rows = 0;
    
    public function __construct($pg, $sql) {
        $this->pg = $pg;
        $this->sql = $sql;
    }
    
    public function bind_param($types, &...$params) {
        $this->params = [];
        foreach ($params as $param) {
            $this->params[] = $param;
        }
    }
    
    public function execute() {
        $needsReturning = false;
        $sql = $this->sql;
        
        if (preg_match('/^\s*INSERT\s+INTO\s+/i', $sql) && !preg_match('/RETURNING/i', $sql)) {
            if (preg_match('/INSERT\s+INTO\s+(\w+)/i', $sql, $m)) {
                $table = $m[1];
                $pkCol = 'id'; // default
                $sql .= " RETURNING $pkCol";
                $needsReturning = true;
            }
        }
        
        $result = pg_query_params($this->pg, $sql, $this->params);
        
        if ($result === false) {
            $this->error = pg_last_error($this->pg);
            return false;
        }
        
        $this->result = $result;
        $this->affected_rows = pg_affected_rows($result);
        $this->num_rows = pg_num_rows($result);
        
        if ($needsReturning && pg_num_rows($result) > 0) {
            $row = pg_fetch_row($result, 0);
            $this->insert_id = (int)$row[0];
            pg_result_seek($result, 0);
        }
        
        return true;
    }
    
    public function get_result() {
        return new SupabaseResult($this->result, $this->pg);
    }
    
    public function store_result() {
        if ($this->result) {
            $this->num_rows = pg_num_rows($this->result);
        }
    }
    
    public function bind_result(&...$vars) {
        if ($this->result && pg_num_rows($this->result) > 0) {
            $row = pg_fetch_row($this->result, 0);
            if ($row) {
                for ($i = 0; $i < count($vars) && $i < count($row); $i++) {
                    $vars[$i] = $row[$i];
                }
            }
        }
    }
    
    public function fetch() {
        if ($this->result) {
            $row = pg_fetch_row($this->result);
            return $row !== false;
        }
        return false;
    }
    
    public function close() {
        if ($this->result && is_resource($this->result)) {
            pg_free_result($this->result);
        }
        $this->result = null;
    }
}

class SupabaseResult {
    private $result;
    private $pg;
    
    public function __construct($result, $pg) {
        $this->result = $result;
        $this->pg = $pg;
    }
    
    public function fetch_assoc() {
        if ($this->result) {
            $row = pg_fetch_assoc($this->result);
            return $row !== false ? $row : null;
        }
        return null;
    }
    
    public function fetch_row() {
        if ($this->result) {
            return pg_fetch_row($this->result);
        }
        return null;
    }
    
    public function num_rows() {
        if ($this->result) {
            return pg_num_rows($this->result);
        }
        return 0;
    }
    
    public function __get($name) {
        if ($name === 'num_rows') {
            return $this->num_rows();
        }
        return null;
    }
}

// ============================================================
// KONFIGURASI SUPABASE (MENGGUNAKAN ENVIRONMENT VARIABLES)
// ============================================================

// Simple .env parser untuk environment lokal
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

// Fallback jika tidak ada env vars
$SUPABASE_URL = getenv('SUPABASE_URL') ?: '';
$SUPABASE_SERVICE_ROLE_KEY = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '';
$SUPABASE_PG_CONN = getenv('SUPABASE_PG_CONN') ?: '';

define('SUPABASE_URL', $SUPABASE_URL);
define('SUPABASE_SERVICE_ROLE_KEY', $SUPABASE_SERVICE_ROLE_KEY);
define('SUPABASE_PG_CONN', $SUPABASE_PG_CONN);

// Create connection
try {
    $conn = new SupabaseDB(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, SUPABASE_PG_CONN);
} catch (Exception $e) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee;border:1px solid #f00;border-radius:8px;margin:20px;">
        <strong>❌ Koneksi Database Gagal!</strong><br>
        Error: ' . $e->getMessage() . '<br><br>
        <em>Pastikan konfigurasi Supabase (SUPABASE_PG_CONN) Anda benar di Environment Variables.</em>
    </div>');
}
?>
