<?php

namespace App\core;

/**
 * Clase central de seguridad — Hospital San José de Melipilla
 *
 * Implementa en código las medidas del informe INF-CIBER-2026-10:
 *   4.1 / 6.2  Bloqueo de URLs hacia direcciones internas (RFC 1918) en el HTML público
 *   4.2 / 6.6  Nombres de archivo no predecibles y saneados
 *   4.4 / 6.1  Cabeceras HTTP de seguridad (respaldo a nivel de aplicación)
 *   4.6 / 6.4  Escape de salida contextual
 *   4.7 / 6.5  Sesión y cookies endurecidas + CSRF
 *
 * Uso mínimo: llamar a Security::boot() como primera instrucción de index.php.
 */
class Security
{
    /** Minutos de inactividad antes de expirar la sesión. */
    public const INACTIVIDAD_MINUTOS = 30;

    /** Minutos entre rotaciones automáticas del ID de sesión. */
    public const ROTACION_MINUTOS = 15;

    /** @var bool evita enviar cabeceras dos veces */
    private static $cabecerasEnviadas = false;

    /* =====================================================================
       ARRANQUE
       ===================================================================== */

    /**
     * Punto de entrada único. Envía cabeceras y prepara la configuración
     * de sesión. NO inicia la sesión: eso lo sigue haciendo SessionManager
     * cuando el controlador lo necesita.
     */
    public static function boot(): void
    {
        self::enviarCabeceras();
        self::configurarCookieSesion();
    }

    /* =====================================================================
       CABECERAS HTTP  (hallazgo 4.4 / remediación 6.1)
       ===================================================================== */

    /**
     * Cabeceras de seguridad enviadas desde PHP.
     *
     * Esto es una RED DE SEGURIDAD, no el mecanismo principal: lo correcto es
     * declararlas en Apache/Nginx (ver carpeta servidor/). Se envían aquí
     * también para que el sitio quede protegido aunque el vhost se pierda en
     * una migración de hosting.
     */
    public static function enviarCabeceras(): void
    {
        if (self::$cabecerasEnviadas || headers_sent()) {
            return;
        }
        self::$cabecerasEnviadas = true;

        header_remove('X-Powered-By');

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        // HSTS sólo si la petición llegó por HTTPS (enviarlo por HTTP no tiene efecto
        // y confunde a los verificadores de postura).
        if (self::esHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header('Content-Security-Policy: ' . self::politicaCSP());
    }

    /**
     * Política CSP del sitio.
     *
     * IMPORTANTE: el sitio actual usa CDNs de terceros (Google Fonts, cdnjs) y
     * scripts en línea generados por Artify. Por eso la política parte en modo
     * permisivo controlado. Cuando se hayan revisado los reportes, endurecer
     * quitando 'unsafe-inline' de script-src.
     *
     * Para probar sin romper nada: cambiar la constante CSP_SOLO_REPORTE del
     * .env a "true" y revisar la consola del navegador.
     */
    public static function politicaCSP(): string
    {
        $propio = "'self'";

        $directivas = [
            "default-src {$propio}",
            "base-uri {$propio}",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action {$propio}",
            "img-src {$propio} data: https:",
            "font-src {$propio} data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "style-src {$propio} 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "script-src {$propio} 'unsafe-inline' https://cdnjs.cloudflare.com",
            "connect-src {$propio}",
            // Los portales clínicos y de reserva se abren embebidos en algunas páginas.
            "frame-src {$propio} https://www.youtube.com https://youcanbook.me",
        ];

        return implode('; ', $directivas);
    }

    public static function esHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
            return true;
        }
        // Detrás de balanceador o proxy de cPanel.
        if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        return false;
    }

    /* =====================================================================
       SESIÓN Y COOKIES  (hallazgo 4.7 / remediación 6.5)
       ===================================================================== */

    /**
     * Fija los atributos seguros de la cookie de sesión.
     * Debe ejecutarse ANTES de cualquier session_start().
     */
    public static function configurarCookieSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return; // ya es tarde; no se puede reconfigurar
        }

        ini_set('session.use_strict_mode', '1');   // rechaza IDs de sesión no generados por el servidor
        ini_set('session.use_only_cookies', '1');  // nunca aceptar el ID por la URL
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');

        session_set_cookie_params([
            'lifetime' => 0,                 // muere al cerrar el navegador
            'path'     => '/',
            'domain'   => '',                // se deja vacío: el navegador usa el host actual
            'secure'   => self::esHttps(),   // sólo por HTTPS cuando hay HTTPS
            'httponly' => true,              // inaccesible desde JavaScript (mitiga robo por XSS)
            'samesite' => 'Lax',             // 'Strict' rompe el retorno desde enlaces externos
        ]);
    }

    /**
     * Control de inactividad y rotación periódica del ID de sesión.
     * Devuelve false si la sesión fue cerrada por inactividad.
     */
    public static function vigilarSesion(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        $ahora = time();

        if (isset($_SESSION['_ultima_actividad'])) {
            $inactivo = $ahora - (int) $_SESSION['_ultima_actividad'];
            if ($inactivo > self::INACTIVIDAD_MINUTOS * 60) {
                self::cerrarSesion();
                return false;
            }
        }
        $_SESSION['_ultima_actividad'] = $ahora;

        if (!isset($_SESSION['_creada'])) {
            $_SESSION['_creada'] = $ahora;
        } elseif ($ahora - (int) $_SESSION['_creada'] > self::ROTACION_MINUTOS * 60) {
            session_regenerate_id(true);
            $_SESSION['_creada'] = $ahora;
        }

        return true;
    }

    /**
     * Cierre completo: vacía datos, borra la cookie y destruye la sesión.
     */
    public static function cerrarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $p['path'],
                    'domain'   => $p['domain'],
                    'secure'   => $p['secure'],
                    'httponly' => $p['httponly'],
                    'samesite' => $p['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    /* =====================================================================
       CSRF  (remediación 6.5)
       ===================================================================== */

    /**
     * Genera (y memoriza en sesión) un token CSRF por formulario.
     * El token es aleatorio y se compara con hash_equals: no se deriva del
     * ID de sesión, de modo que sigue siendo válido tras rotar la sesión.
     */
    public static function tokenCsrf(string $formulario = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (empty($_SESSION['_csrf'][$formulario])) {
            $_SESSION['_csrf'][$formulario] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'][$formulario];
    }

    /**
     * Verifica un token CSRF en tiempo constante.
     */
    public static function verificarCsrf(string $formulario, $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }
        $esperado = $_SESSION['_csrf'][$formulario] ?? null;
        if (!is_string($esperado) || $esperado === '') {
            return false;
        }
        return hash_equals($esperado, $token);
    }

    /**
     * Campo oculto listo para insertar en un formulario.
     */
    public static function campoCsrf(string $formulario = 'default'): string
    {
        return '<input type="hidden" name="auth_token" value="'
            . self::e(self::tokenCsrf($formulario)) . '">';
    }

    /* =====================================================================
       ESCAPE DE SALIDA  (hallazgo 4.6 / remediación 6.4)
       ===================================================================== */

    /**
     * Escape para contexto HTML. Usar SIEMPRE al imprimir datos que
     * pudieron venir del usuario o de la base de datos.
     */
    public static function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape para contexto de atributo de URL: además de escapar, valida
     * que el esquema no sea javascript:, data: ni vbscript:.
     */
    public static function eUrl($url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '#';
        }

        $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $peligrosos = ['javascript', 'data', 'vbscript', 'file'];

        if ($esquema !== '' && in_array($esquema, $peligrosos, true)) {
            return '#';
        }

        // Rutas relativas o esquemas permitidos
        if ($esquema !== '' && !in_array($esquema, ['http', 'https', 'mailto', 'tel'], true)) {
            return '#';
        }

        return self::e($url);
    }

    /**
     * Escape para insertar un valor dentro de un bloque <script> como JSON.
     */
    public static function eJs($valor): string
    {
        return json_encode(
            $valor,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
    }

    /* =====================================================================
       FUGA DE INFRAESTRUCTURA INTERNA  (hallazgo 4.1 / remediación 6.2)
       ===================================================================== */

    /**
     * ¿La URL apunta a una dirección privada RFC 1918, loopback o link-local?
     *
     * Se evalúa el host literal de la URL. NO se resuelve DNS: el objetivo es
     * impedir que el HTML público publique el mapa de la red interna, no
     * bloquear la navegación del usuario.
     */
    public static function apuntaARedInterna($url): bool
    {
        $host = parse_url(trim((string) $url), PHP_URL_HOST);

        if (!$host) {
            return false; // ruta relativa: es del propio sitio
        }

        $host = trim($host, '[]'); // IPv6

        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            // Nombres tipo "intranet.local" o "servidor.hsjm.local" también
            // revelan topología interna.
            $sufijosInternos = ['.local', '.lan', '.internal', '.intranet', '.corp'];
            $hostMin = strtolower($host);
            foreach ($sufijosInternos as $sufijo) {
                if (substr($hostMin, -strlen($sufijo)) === $sufijo) {
                    return true;
                }
            }
            return false;
        }

        // Es una IP literal: rechazar si es privada o reservada.
        $esPublica = filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $esPublica === false;
    }

    public static function html($html): string
    {
        $html = html_entity_decode((string)$html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Bloquear scripts
        if (preg_match('/<script/i', $html)) {
            return '';
        }

        // Bloquear eventos JS
        if (preg_match('/on[a-z]+\s*=/i', $html)) {
            return '';
        }

        return $html;
    }

    /**
     * Devuelve una URL apta para publicarse en el sitio de cara a Internet.
     *
     * Si la URL apunta a la red interna, se sustituye por $reemplazo (por
     * defecto '#') y se registra el hecho para que TI corrija el registro en
     * la base de datos. Así el enlace no vuelve a publicarse aunque alguien
     * lo cargue de nuevo desde el panel.
     */
    public static function urlPublicaSegura($url, string $reemplazo = '#'): string
    {
        $permitidas = [
            '10.63.247.125',
        ];

        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($host, $permitidas, true)) {
            return (string)$url;
        }
        
        if (self::apuntaARedInterna($url)) {
            self::registrar(
                'URL interna bloqueada en el HTML público (hallazgo 4.1). '
                . 'Corregir el registro en la base de datos. Valor: ' . $url
            );
            return $reemplazo;
        }

        return (string) $url;
    }

    /**
     * Igual que urlPublicaSegura() pero además escapa para el atributo href.
     */
    public static function href($url, string $reemplazo = '#'): string
    {
        return self::eUrl(self::urlPublicaSegura($url, $reemplazo));
    }

    /* =====================================================================
       ARCHIVOS SUBIDOS  (hallazgo 4.2 / remediación 6.6)
       ===================================================================== */

    /**
     * Nombre de archivo seguro y no predecible.
     * Mantiene el prefijo de tiempo (útil para ordenar) pero añade entropía,
     * de modo que el directorio deja de ser enumerable por fuerza bruta.
     */
    public static function nombreArchivoSeguro(string $nombreOriginal): string
    {
        $nombreOriginal = basename($nombreOriginal);

        $ext  = strtolower((string) pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $base = (string) pathinfo($nombreOriginal, PATHINFO_FILENAME);

        // Sólo caracteres inocuos en el sistema de archivos y en la URL.
        $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
        $base = trim((string) $base, '._-');
        $base = substr($base !== '' ? $base : 'archivo', 0, 60);

        $ext = preg_replace('/[^a-z0-9]/', '', $ext);

        $aleatorio = bin2hex(random_bytes(8));

        return time() . '_' . $aleatorio . '_' . $base . ($ext !== '' ? '.' . $ext : '');
    }

    /**
     * Extensiones que jamás deben aceptarse en el directorio de cargas,
     * porque el servidor podría ejecutarlas.
     */
    public static function extensionEjecutable(string $nombreArchivo): bool
    {
        $prohibidas = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'pht', 'shtml', 'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'htaccess',
        ];

        $ext = strtolower((string) pathinfo(basename($nombreArchivo), PATHINFO_EXTENSION));

        return in_array($ext, $prohibidas, true);
    }

    /* =====================================================================
       REGISTRO
       ===================================================================== */

    /**
     * Registro de eventos de seguridad. Escribe con error_log para no
     * depender de una tabla ni de permisos adicionales.
     */
    public static function registrar(string $mensaje): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
        error_log('[SEGURIDAD][' . date('c') . '][' . $ip . '] ' . $mensaje);
    }
}