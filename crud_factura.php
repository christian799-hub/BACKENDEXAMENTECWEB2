<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^https?:\/\/.*\.vercel\.app$/', $origin) || $origin === 'http://localhost:5173') {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Accept, body");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception("No se recibieron datos válidos.");
    }

    $accion = $data['accion'] ?? 'crear';
    $pdo->beginTransaction();

    if ($accion === 'crear') {
        $cliente = $data['cliente'];
        $totalFinal = $data['totalFinal'];
        $cufd = $data['cufdUsado'];
        $fechaEmision = date('Y-m-d H:i:s', strtotime($data['fechaEmision'])); 

        $sqlFactura = 'INSERT INTO facturas (fecha, nit, "razonSocial", estado, "subTotal", total, "tipoDoc", correo, "metodoPago", "cufdUsado") 
                       VALUES (:fecha, :nit, :razonSocial, :estado, :subTotal, :total, :tipoDoc, :correo, :metodoPago, :cufdUsado) 
                       RETURNING id';
        
        $stmtFactura = $pdo->prepare($sqlFactura);
        $stmtFactura->execute([
            ':fecha' => $fechaEmision,
            ':nit' => $cliente['nit'],
            ':razonSocial' => $cliente['razonSocial'],
            ':estado' => 'Aceptado', 
            ':subTotal' => $totalFinal,
            ':total' => $totalFinal,
            ':tipoDoc' => $cliente['tipoDoc'],            
            ':correo' => $cliente['correo'],              
            ':metodoPago' => $cliente['metodoPago'],      
            ':cufdUsado' => $cufd                         
        ]);

        $idFactura = $stmtFactura->fetchColumn();
        $mensaje = "Factura creada correctamente";

    } elseif ($accion === 'modificar') {
        $idFactura = $data['id'] ?? null;
        if (!$idFactura) throw new Exception("Se requiere el ID de la factura para modificarla.");

        $cliente = $data['cliente'];
        $totalFinal = $data['totalFinal'];

        $sqlActualizar = 'UPDATE facturas 
                          SET nit = :nit, "razonSocial" = :razonSocial, "subTotal" = :subTotal, total = :total, 
                              "tipoDoc" = :tipoDoc, correo = :correo, "metodoPago" = :metodoPago
                          WHERE id = :id';
        
        $stmtActualizar = $pdo->prepare($sqlActualizar);
        $stmtActualizar->execute([
            ':nit' => $cliente['nit'],
            ':razonSocial' => $cliente['razonSocial'],
            ':subTotal' => $totalFinal,
            ':total' => $totalFinal,
            ':tipoDoc' => $cliente['tipoDoc'],            
            ':correo' => $cliente['correo'],              
            ':metodoPago' => $cliente['metodoPago'],
            ':id' => $idFactura
        ]);

        $mensaje = "Factura actualizada correctamente";

    } elseif ($accion === 'anular') {
        $idFactura = $data['id'] ?? null;
        if (!$idFactura) throw new Exception("Se requiere el ID de la factura para anularla.");

        $sqlAnular = 'UPDATE facturas SET estado = :estado WHERE id = :id';
        $stmtAnular = $pdo->prepare($sqlAnular);
        $stmtAnular->execute([
            ':estado' => 'Anulado',
            ':id' => $idFactura
        ]);

        $mensaje = "Factura anulada correctamente";

    } else {
        throw new Exception("Acción no reconocida. Use 'crear', 'modificar' o 'anular'.");
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => $mensaje,
        "factura_id" => $idFactura ?? null 
    ]);

} catch (Throwable $e) { 
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error de servidor: " . $e->getMessage()
    ]);
}
?>
