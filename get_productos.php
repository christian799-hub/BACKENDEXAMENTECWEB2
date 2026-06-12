<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^https?:\/\/.*\.vercel\.app$/', $origin) || $origin === 'http://localhost:5173') {
    header("Access-Control-Allow-Origin: $origin");
}
header("Content-Type: application/json; charset=UTF-8");

$db_host = "aws-1-us-east-1.pooler.supabase.com";
$db_user = "postgres.ugqunucxnoxvdzztxeim";
$db_pass = "12345678Examen--";
$db_name = "postgres";   
$db_port = "5432";

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
    
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = "SELECT id, descripcion, unidad, procedencia, precio, barras FROM productos ORDER BY descripcion ASC";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as &$prod) {
        $prod['precio'] = (float)$prod['precio'];
    }

    echo json_encode([
        "success" => true,
        "data" => $productos
    ]);

} catch (Throwable $e) { 
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error crítico: " . $e->getMessage()
    ]);
}
?>
