<?php
/**
 * Cliente SMTP mínimo, sin dependencias externas (no requiere Composer ni
 * PHPMailer instalado). Habla el protocolo SMTP directamente por un socket
 * de PHP, soportando conexión SSL implícita (puerto 465) o STARTTLS
 * (puerto 587) con autenticación AUTH LOGIN — el esquema estándar que
 * ofrecen los correos de hPanel en Hostinger.
 *
 * Uso:
 *   $mailer = new SmtpMailer(SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS);
 *   $resultado = $mailer->enviar($destinatarioEmail, $destinatarioNombre, $asunto, $htmlBody, SMTP_FROM_EMAIL, SMTP_FROM_NAME);
 *   // $resultado = ['success' => bool, 'error' => string|null]
 */
class SmtpMailer {
    private $host;
    private $port;
    private $seguridad; // 'ssl' (puerto 465) | 'tls' (STARTTLS, puerto 587) | ''
    private $usuario;
    private $clave;
    private $timeout;

    public function __construct($host, $port, $seguridad, $usuario, $clave, $timeout = 15) {
        $this->host = $host;
        $this->port = $port;
        $this->seguridad = $seguridad;
        $this->usuario = $usuario;
        $this->clave = $clave;
        $this->timeout = $timeout;
    }

    public function enviar($destinatarioEmail, $destinatarioNombre, $asunto, $htmlBody, $fromEmail, $fromNombre) {
        $socket = null;
        try {
            $transporte = ($this->seguridad === 'ssl') ? 'ssl://' : '';
            $socket = @stream_socket_client(
                $transporte . $this->host . ':' . $this->port,
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT
            );
            if (!$socket) {
                return ['success' => false, 'error' => "No se pudo conectar a $this->host:$this->port ($errstr)"];
            }
            stream_set_timeout($socket, $this->timeout);

            $this->leerRespuesta($socket, 220);
            $this->enviarComando($socket, "EHLO " . $this->dominioLocal(), 250);

            if ($this->seguridad === 'tls') {
                $this->enviarComando($socket, "STARTTLS", 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return ['success' => false, 'error' => 'No se pudo iniciar STARTTLS con el servidor SMTP'];
                }
                // Tras STARTTLS hay que volver a saludar
                $this->enviarComando($socket, "EHLO " . $this->dominioLocal(), 250);
            }

            $this->enviarComando($socket, "AUTH LOGIN", 334);
            $this->enviarComando($socket, base64_encode($this->usuario), 334);
            $this->enviarComando($socket, base64_encode($this->clave), 235);

            $this->enviarComando($socket, "MAIL FROM:<{$fromEmail}>", 250);
            $this->enviarComando($socket, "RCPT TO:<{$destinatarioEmail}>", 250);
            $this->enviarComando($socket, "DATA", 354);

            $cuerpo = $this->armarMensaje($destinatarioEmail, $destinatarioNombre, $asunto, $htmlBody, $fromEmail, $fromNombre);
            // Dot-stuffing: una línea que empiece con "." se duplica, para que
            // el servidor no la confunda con el terminador de DATA.
            $cuerpo = preg_replace('/^\./m', '..', $cuerpo);
            fwrite($socket, $cuerpo . "\r\n.\r\n");
            $this->leerRespuesta($socket, 250);

            $this->enviarComando($socket, "QUIT", 221, false);
            fclose($socket);
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            if ($socket) { @fclose($socket); }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function dominioLocal() {
        return !empty($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.\-]/', '', $_SERVER['HTTP_HOST']) : 'localhost';
    }

    private function enviarComando($socket, $comando, $codigoEsperado, $exigirCodigo = true) {
        fwrite($socket, $comando . "\r\n");
        return $this->leerRespuesta($socket, $codigoEsperado, $exigirCodigo);
    }

    private function leerRespuesta($socket, $codigoEsperado, $exigirCodigo = true) {
        $respuesta = '';
        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;
            // La respuesta termina cuando la línea tiene un espacio después
            // del código (no un guion), ej: "250 OK" vs "250-más líneas".
            if (preg_match('/^\d{3} /', $linea)) {
                break;
            }
        }
        if ($respuesta === '') {
            throw new Exception('El servidor SMTP no respondió (posible timeout o credenciales inválidas)');
        }
        $codigo = (int)substr($respuesta, 0, 3);
        if ($exigirCodigo && $codigo !== $codigoEsperado) {
            throw new Exception("Respuesta SMTP inesperada (se esperaba $codigoEsperado): " . trim($respuesta));
        }
        return $respuesta;
    }

    private function armarMensaje($destinatarioEmail, $destinatarioNombre, $asunto, $htmlBody, $fromEmail, $fromNombre) {
        $fecha = date('r');
        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
        $fromNombreCodificado = '=?UTF-8?B?' . base64_encode($fromNombre) . '?=';
        $destinatarioNombreCodificado = '=?UTF-8?B?' . base64_encode($destinatarioNombre) . '?=';
        $messageId = '<' . uniqid('gmg', true) . '@' . $this->dominioLocal() . '>';

        $headers = [];
        $headers[] = "Date: $fecha";
        $headers[] = "From: {$fromNombreCodificado} <{$fromEmail}>";
        $headers[] = "To: {$destinatarioNombreCodificado} <{$destinatarioEmail}>";
        $headers[] = "Subject: {$asuntoCodificado}";
        $headers[] = "Message-ID: {$messageId}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        // base64 en líneas de 76: con 8bit, una línea de más de 998 caracteres hace que el servidor rechace o corte el correo.
        $headers[] = "Content-Transfer-Encoding: base64";

        return implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($htmlBody), 76, "\r\n"));
    }
}
?>
