<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^https?:\/\/.*\.vercel\.app$/', $origin) || $origin === 'http://localhost:5173') {
    header("Access-Control-Allow-Origin: $origin");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$db_host = "aws-1-us-east-1.pooler.supabase.com";
$db_user = "postgres.ugqunucxnoxvdzztxeim";
$db_pass = "12345678Examen--";
$db_name = "postgres";   
$db_port = "5432";



try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de BD: " . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['usuario']) || empty($data['password'])) {
    echo json_encode(["success" => false, "message" => "Credenciales incompletas"]);
    exit;
}

$input_usuario = trim($data['usuario']);
$input_password = $data['password'];

$stmt = $pdo->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?");
$stmt->execute([$input_usuario]);
$user = $stmt->fetch();

if ($user && password_verify($input_password, $user['password'])) {
    
    $issuedAt = time();
    $expirationTime = $issuedAt + (60 * 60 * 24); 
    $payload = [
        "iss" => "ferreteria_america_backend",
        "iat" => $issuedAt,
        "exp" => $expirationTime,
        "userId" => $user['id'],
        "rol" => $user['rol']
    ];

    function base64UrlEncode($text) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload_json = json_encode($payload);

    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload_json);

    // Aquí usamos el $jwt_secret que definimos arriba
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $jwt_secret, true);
    $base64UrlSignature = base64UrlEncode($signature);

    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    echo json_encode([
        "success" => true,
        "token" => $jwt,
        "user" => [
            "id" => (int)$user['id'],
            "usuario" => $user['usuario'],
            "rol" => $user['rol']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Usuario o contrasena incorrectos"
    ]);
}
?>
