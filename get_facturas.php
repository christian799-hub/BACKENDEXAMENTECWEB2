<?php
$allowed_origin = "https://examen-final-christian-ferrufino.vercel.app";
header("Access-Control-Allow-Origin: $allowed_origin");
header("Content-Type: application/json; charset=UTF-8");

// Configuración de la base de datos
$db_host = "aws-1-us-east-1.pooler.supabase.com";
$db_user = "postgres.ugqunucxnoxvdzztxeim";
$db_pass = "12345678Examen--";
$db_name = "postgres";   
$db_port = "5432";


try {
    $pdo = new PDO("pgsql:host=$db_host;dbname=$db_name", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // CORRECCIÓN: Envolvimos las columnas con mayúsculas en comillas dobles ""
    $sql = 'SELECT 
                id, 
                fecha, 
                nit, 
                "razonSocial", 
                estado, 
                "subTotal", 
                total 
            FROM facturas 
            ORDER BY id DESC';

    $stmt = $pdo->query($sql);
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $facturas
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de BD: " . $e->getMessage()
    ]);
}
?>
