<?php
// Include database configuration
require_once '../webhook/config/config.php';

// Initialize response array
$response = [
    'draw' => intval($_GET['draw']),
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => []
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build query based on DataTables parameters
    $limit = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $offset = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Order column
    $orderColumn = 0; // Default to first column
    if (isset($_GET['order'][0]['column'])) {
        $orderColumn = intval($_GET['order'][0]['column']);
    }
    
    // Order direction
    $orderDir = 'ASC';
    if (isset($_GET['order'][0]['dir']) && in_array(strtoupper($_GET['order'][0]['dir']), ['ASC', 'DESC'])) {
        $orderDir = strtoupper($_GET['order'][0]['dir']);
    }
    
    // Map column index to database column name
    $columns = ['user_id', 'username', 'email', 'role', 'status', 'created_at'];
    $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'user_id';
    
    // Build query
    $whereClause = '';
    $params = [];
    
    if (!empty($search)) {
        $whereClause = " WHERE username LIKE ? OR email LIKE ? OR role LIKE ? OR status LIKE ?";
        $searchParam = '%' . $search . '%';
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    // Count total records
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $response['recordsTotal'] = $stmt->fetchColumn();
    
    // Count filtered records
    if (!empty($whereClause)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users" . $whereClause);
        $stmt->execute($params);
        $response['recordsFiltered'] = $stmt->fetchColumn();
    } else {
        $response['recordsFiltered'] = $response['recordsTotal'];
    }
    
    // Get data
    $sql = "SELECT user_id, username, email, role, status, created_at FROM users" . 
           $whereClause . 
           " ORDER BY " . $orderColumnName . " " . $orderDir . 
           " LIMIT " . $limit . " OFFSET " . $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Log error
    error_log('Database Error: ' . $e->getMessage());
    
    // Return error response
    $response['error'] = 'Database Error: ' . $e->getMessage();
    echo json_encode($response);
}
?> 