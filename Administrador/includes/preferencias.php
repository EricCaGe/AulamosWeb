<?php
// ========================================== */
// ARCHIVO: includes/preferencias.php        */
// CARGAR PREFERENCIAS DESDE SESIÓN          */
// ========================================== */

if (!isset($_SESSION)) {
    session_start();
}

// Cargar preferencias desde sesión
if (isset($_SESSION['preferencias'])) {
    $preferencias = $_SESSION['preferencias'];
} else {
    $preferencias = [
        'modo_oscuro' => 0,
        'tamano_texto' => 'normal',
        'alto_contraste' => 0,
        'idioma' => 'es'
    ];
    $_SESSION['preferencias'] = $preferencias;
}

// ✅ NUEVO: Cargar colores de contraste personalizado
$contraste_fondo = $_SESSION['contraste_fondo'] ?? 'negro';
$contraste_color = $_SESSION['contraste_color'] ?? 'azul';

$modo_oscuro = $preferencias['modo_oscuro'];
$tamano_texto = $preferencias['tamano_texto'];
$alto_contraste = $preferencias['alto_contraste'];
$idioma_actual = $preferencias['idioma'];

$clases_body = '';
if ($modo_oscuro == 1) $clases_body .= 'modo-oscuro';
if ($alto_contraste == 1) {
    $clases_body .= ' alto-contraste';
    // ✅ AGREGAR ESTO: Aplicar fondo y color
    $fondo = $_SESSION['contraste_fondo'] ?? 'negro';
    $color = $_SESSION['contraste_color'] ?? 'azul';
    $clases_body .= ' fondo-' . $fondo;
    $clases_body .= ' color-' . $color;
}
if (strtolower($tamano_texto) == 'grande') $clases_body .= ' texto-grande';

// ========================================== */
// TRADUCCIONES COMPLETAS                    */
// ========================================== */
$traducciones = [
    'es' => [
        'configuracion' => 'Configuración',
        'administra_config' => 'Administra la configuración general de la plataforma',
        'informacion_general' => 'Información general',
        'nombre_sistema' => 'Nombre del sistema',
        'ciclo_actual' => 'Ciclo escolar actual',
        'version' => 'Versión de la plataforma',
        'preferencias' => 'Preferencias de la plataforma',
        'tema' => 'Tema por defecto',
        'claro' => 'Claro',
        'oscuro' => 'Oscuro',
        'sistema' => 'Sistema',
        'idioma' => 'Idioma',
        'español' => 'Español',
        'ingles' => 'Inglés',
        'tamano_texto' => 'Tamaño de texto',
        'pequeño' => 'Pequeño',
        'normal' => 'Normal',
        'grande' => 'Grande',
        'alto_contraste' => 'Alto contraste',
        'desactivado' => 'Desactivado',
        'activado' => 'Activado',
        'guardar' => 'Guardar cambios',
        'dashboard' => 'Dashboard',
        'ciclos' => 'Ciclos escolares',
        'periodos' => 'Periodos',
        'materias' => 'Materias',
        'grupos' => 'Grupos',
        'cursos' => 'Cursos',
        'inscripciones' => 'Inscripciones',
        'accesibilidad' => 'Accesibilidad',
        'cerrar_sesion' => 'Cerrar sesión',
        'mi_cuenta' => 'Mi cuenta',
        'nombre_completo' => 'Nombre completo',
        'correo' => 'Correo electrónico',
        'rol' => 'Rol',
        'ultimo_acceso' => 'Último acceso',
        'fecha_registro' => 'Fecha de registro',
        'administra_periodos' => 'Administra los periodos de cada ciclo escolar.',
        'periodos_registrados' => 'Periodos registrados',
        'periodos_encontrados' => 'periodos encontrados',
        'nuevo_periodo' => 'Nuevo periodo',
        'sin_periodos' => 'No hay periodos registrados',
        'crear_primer_periodo' => 'Comienza creando un nuevo periodo para este ciclo.',
        'crear_periodo' => 'Crear primer periodo',
        'nombre_periodo' => 'Nombre del periodo',
        'ej_periodo' => 'Ej. Primer periodo',
        'fechas_permitidas' => 'Fechas permitidas',
        'cerrar' => 'Cerrar',
        'cerrado' => 'Cerrado',
        'inicio' => 'Inicio',
        'fin' => 'Fin',
        'personaliza_experiencia' => 'Personaliza tu experiencia en cualquier momento.',
        'leer_pantalla' => 'Leer pantalla',
        'subtitulos' => 'Subtítulos',
        'navegacion' => 'Navegación',
        'activo' => 'Activo',
        'inactivo' => 'Inactivo',
        // ✅ NUEVA TRADUCCIÓN
        'personalizar_contraste' => 'Personalizar alto contraste'
    ],
    'en' => [
        'configuracion' => 'Settings',
        'administra_config' => 'Manage general platform settings',
        'informacion_general' => 'General information',
        'nombre_sistema' => 'System name',
        'ciclo_actual' => 'Current school year',
        'version' => 'Platform version',
        'preferencias' => 'Platform preferences',
        'tema' => 'Default theme',
        'claro' => 'Light',
        'oscuro' => 'Dark',
        'sistema' => 'System',
        'idioma' => 'Language',
        'español' => 'Spanish',
        'ingles' => 'English',
        'tamano_texto' => 'Text size',
        'pequeño' => 'Small',
        'normal' => 'Normal',
        'grande' => 'Large',
        'alto_contraste' => 'High contrast',
        'desactivado' => 'Disabled',
        'activado' => 'Enabled',
        'guardar' => 'Save changes',
        'dashboard' => 'Dashboard',
        'ciclos' => 'School cycles',
        'periodos' => 'Periods',
        'materias' => 'Subjects',
        'grupos' => 'Groups',
        'cursos' => 'Courses',
        'inscripciones' => 'Enrollments',
        'accesibilidad' => 'Accessibility',
        'cerrar_sesion' => 'Logout',
        'mi_cuenta' => 'My account',
        'nombre_completo' => 'Full name',
        'correo' => 'Email',
        'rol' => 'Role',
        'ultimo_acceso' => 'Last access',
        'fecha_registro' => 'Registration date',
        'administra_periodos' => 'Manage periods for each school cycle.',
        'periodos_registrados' => 'Registered periods',
        'periodos_encontrados' => 'periods found',
        'nuevo_periodo' => 'New period',
        'sin_periodos' => 'No periods registered',
        'crear_primer_periodo' => 'Start by creating a new period for this cycle.',
        'crear_periodo' => 'Create first period',
        'nombre_periodo' => 'Period name',
        'ej_periodo' => 'E.g. First period',
        'fechas_permitidas' => 'Allowed dates',
        'cerrar' => 'Close',
        'cerrado' => 'Closed',
        'inicio' => 'Start',
        'fin' => 'End',
        'personaliza_experiencia' => 'Customize your experience at any time.',
        'leer_pantalla' => 'Read screen',
        'subtitulos' => 'Subtitles',
        'navegacion' => 'Navigation',
        'activo' => 'Active',
        'inactivo' => 'Inactive',
        // ✅ NUEVA TRADUCCIÓN
        'personalizar_contraste' => 'Customize high contrast'
    ]
];

function __($texto) {
    global $idioma_actual, $traducciones;
    return $traducciones[$idioma_actual][$texto] ?? $texto;
}
?>