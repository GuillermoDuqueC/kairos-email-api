<?php

    // **CRÍTICO**: Headers CORS y permisivos ANTES de cualquier lógica
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: *');

    require __DIR__ . '/vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // **NUEVO**: Logging para diagnóstico
    $logFile = __DIR__ . '/api_requests.log';
    
   // **NUEVO**: Log completo para diagnóstico del cliente problemático
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT SET',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'none',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'https' => $_SERVER['HTTPS'] ?? 'off'
    ];
    file_put_contents(__DIR__ . '/access_log.txt', json_encode($logData, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);

    function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    // **NUEVO**: Permitir OPTIONS para CORS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        http_response_code(200);
        exit;
    }
    
        file_put_contents('debug_http.txt',
        "TIME: " . date('Y-m-d H:i:s') . "\n" .
        "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A') . "\n" .
        "RAW_BODY:\n" . file_get_contents("php://input") . "\n\n",
        FILE_APPEND
    );


    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['status' => 'ERROR', 'Description' => 'Método HTTP no permitido'], 405);
    }

    // **MEJORADO**: Aceptar múltiples formatos de entrada
    $rawInput = file_get_contents("php://input");
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // Intentar decodificar JSON
    $data = json_decode($rawInput, true);

    // **NUEVO**: Si falla JSON, intentar form-data
    if (!$data && strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        parse_str($rawInput, $data);
    }

    // **NUEVO**: Si falla, intentar multipart
    if (!$data && strpos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;
        if (!empty($_FILES['attachment'])) {
            $data['attachment_b64'] = base64_encode(file_get_contents($_FILES['attachment']['tmp_name']));
            $data['attachment_name'] = $_FILES['attachment']['name'];
        }
    }

    if (!$data) {
        file_put_contents($logFile, "ERROR: No se pudo decodificar input\n", FILE_APPEND);
        sendJsonResponse(['status' => 'ERROR', 'Description' => 'Datos inválidos o Content-Type no soportado'], 400);
    }

    $from = trim($data['from'] ?? '');
    $to = trim($data['to'] ?? '');
    $password = trim($data['password'] ?? '');
    $subject = trim($data['subject'] ?? '');

    try {

        // Decodificar body Base64 y guardar HTML temporal
        $bodyHtml = !empty($data['body']) ? base64_decode($data['body']) : ($data['body'] ?? '');
        $tempHtmlFile = sys_get_temp_dir() . "/correo_body_" . uniqid() . ".html";
        file_put_contents($tempHtmlFile, $bodyHtml);

        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(['status' => 'ERROR', 'Description' => "Remitente inválido: $from"], 400);
        }
        
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(['status' => 'ERROR', 'Description' => "Dirección de correo inválida: $to"], 400);
        }    

        // Inicializar PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        /*
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $from;
        $mail->Password = $password;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        */
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $from;
        $mail->Password = $password;
        $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
        $mail->Port = getenv('SMTP_PORT') ?: 587;        
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($from);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->MsgHTML(file_get_contents($tempHtmlFile));

        // Adjuntos Base64
        if (!empty($data['attachment_b64'])) {
            $attachmentName = !empty($data['attachment_name']) ? $data['attachment_name'] : 'archivo_adjunto';
            $tmpPath = sys_get_temp_dir() . "/" . basename($attachmentName);
            file_put_contents($tmpPath, base64_decode($data['attachment_b64']));
            $mail->addAttachment($tmpPath, $attachmentName);
        }

        // Enviar correo
        $mail->send();

        // Limpiar archivos temporales
        if (file_exists($tempHtmlFile)) unlink($tempHtmlFile);
        if (!empty($tmpPath) && file_exists($tmpPath)) unlink($tmpPath);

        sendJsonResponse([
            'status' => 'OK',
            'Description' => 'El correo ha sido enviado correctamente.'
        ]);

    } catch (Exception $e) {
        // Intentar limpiar archivos temporales si hubo error
        if (file_exists($tempHtmlFile)) unlink($tempHtmlFile);
        if (!empty($tmpPath) && file_exists($tmpPath)) unlink($tmpPath);

        sendJsonResponse([
            'status' => 'ERROR',
            'Description' => $mail->ErrorInfo
        ], 500);
    }
