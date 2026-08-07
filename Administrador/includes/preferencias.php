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

$modo_oscuro = $preferencias['modo_oscuro'];
$tamano_texto = $preferencias['tamano_texto'];
$alto_contraste = $preferencias['alto_contraste'];
$idioma_actual = $preferencias['idioma'];

$clases_body = '';
if ($modo_oscuro == 1) $clases_body .= 'modo-oscuro';
if ($alto_contraste == 1) $clases_body .= ' alto-contraste';
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
        'fecha_registro' => 'Fecha de registro'
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
        'fecha_registro' => 'Registration date'
    ]
];

function __($texto) {
    global $idioma_actual, $traducciones;
    return $traducciones[$idioma_actual][$texto] ?? $texto;
}
?>