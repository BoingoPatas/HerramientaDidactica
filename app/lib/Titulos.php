<?php
/**
 * Librería de funciones para generar títulos y textos consistentes y adaptados al rol del usuario
 *
 * - Rol Usuario (estudiante): Lenguaje informal y agradable
 * - Rol Docente/Administrador: Lenguaje formal y administrativo
 * - Todos los mensajes en español
 */

// Función para generar títulos de página basados en el rol y la página actual
function generarTituloPagina($rol, $pagina, $tituloBase = '') {
    $roles = [
        'Administrador' => 'Administrador',
        'Docente' => 'Docente',
        'Usuario' => 'Estudiante'
    ];

    $paginaRol = isset($roles[$rol]) ? $roles[$rol] : 'Usuario';

    $titulosBase = [
        'home' => 'Dashboard',
        'content' => 'Contenido Teórico',
        'practices' => 'Prácticas Interactivas',
        'evaluation' => 'Evaluaciones',
        'settings' => 'Configuración',
        'exercise' => 'Ejercicio Práctico',
        'configuracion' => 'Configuración',
        'manual' => 'Manual de Uso',
        'secciones' => 'Secciones',
        'docentes' => 'Gestión de Docentes',
        'reportes' => 'Reportes del Sistema'
    ];

    $tituloPagina = isset($titulosBase[$pagina]) ? $titulosBase[$pagina] : $tituloBase;

    if (!empty($tituloBase)) {
        return $tituloBase . ' - Herramienta Didáctica';
    }

    return $paginaRol . ' - ' . $tituloPagina . ' - Herramienta Didáctica';
}

// Función para generar saludo de bienvenida basado en el rol (con lenguaje adaptado)
function generarSaludoBienvenida($rol, $nombreUsuario) {
    $saludos = [
        'Administrador' => 'Bienvenido/a Administrador/a',
        'Docente' => 'Bienvenido/a Docente',
        'Usuario' => '¡Hola, ' . htmlspecialchars($nombreUsuario) . '!'
    ];

    return isset($saludos[$rol]) ? $saludos[$rol] : '¡Hola!';
}

// Función para generar título de encabezado de página con lenguaje adaptado
function generarTituloEncabezado($rol, $pagina, $tituloPersonalizado = '') {
    if (!empty($tituloPersonalizado)) {
        return $tituloPersonalizado;
    }

    $encabezados = [
        'home' => [
            'Administrador' => 'Panel de Administración',
            'Docente' => 'Panel del Docente',
            'Usuario' => '¡Hola, bienvenido/a a tu panel!'
        ],
        'content' => [
            'Administrador' => 'Gestión de Contenido',
            'Docente' => 'Contenido Didáctico',
            'Usuario' => 'Contenido teórico'
        ],
        'practices' => [
            'Administrador' => 'Gestión de Prácticas',
            'Docente' => 'Prácticas Interactivas',
            'Usuario' => 'Prácticas interactivas'
        ],
        'evaluation' => [
            'Administrador' => 'Gestión de Evaluaciones',
            'Docente' => 'Evaluaciones',
            'Usuario' => 'Evaluaciones'
        ],
        'settings' => [
            'Administrador' => 'Configuración del Sistema',
            'Docente' => 'Configuración de Cuenta',
            'Usuario' => 'Configuración de tu cuenta'
        ],
        'configuracion' => [
            'Administrador' => 'Configuración del Sistema',
            'Docente' => 'Configuración de Cuenta',
            'Usuario' => 'Configuración de tu cuenta'
        ],
        'manual' => [
            'Administrador' => 'Manual de Uso del Sistema',
            'Docente' => 'Manual de Uso de la Plataforma',
            'Usuario' => 'Manual de uso de la plataforma'
        ],
        'secciones' => [
            'Administrador' => 'Secciones y Estadísticas',
            'Docente' => 'Información no disponible',
            'Usuario' => 'Información no disponible'
        ],
        'docentes' => [
            'Administrador' => 'Gestión de Docentes',
            'Docente' => 'Información no disponible',
            'Usuario' => 'Información no disponible'
        ],
        'reportes' => [
            'Administrador' => 'Reportes del Sistema',
            'Docente' => 'Información no disponible',
            'Usuario' => 'Información no disponible'
        ]
    ];

    if (isset($encabezados[$pagina][$rol])) {
        return $encabezados[$pagina][$rol];
    }

    return 'Panel Principal';
}

// Función para generar subtítulos/descripciones con lenguaje adaptado
function generarSubtituloPagina($rol, $pagina) {
    $subtitulos = [
        'home' => [
            'Administrador' => 'Aquí puedes gestionar usuarios, contenido y configurar el sistema.',
            'Docente' => 'Aquí puedes gestionar contenido, prácticas y evaluaciones.',
            'Usuario' => 'Este es tu espacio personal. Aquí podrás ver tu progreso y acceder a todas las actividades.'
        ],
        'content' => [
            'Administrador' => 'Gestiona el contenido teórico disponible en la plataforma.',
            'Docente' => 'Administra el contenido didáctico disponible para los estudiantes.',
            'Usuario' => 'Aquí encontrarás todo el contenido teórico necesario para aprender programación en C.'
        ],
        'practices' => [
            'Administrador' => 'Gestiona las unidades de práctica y ejercicios disponibles.',
            'Docente' => 'Administra las prácticas interactivas disponibles para los estudiantes.',
            'Usuario' => 'Selecciona una unidad y comienza con los ejercicios prácticos. ¡Vamos a programar!'
        ],
        'evaluation' => [
            'Administrador' => 'Gestiona las evaluaciones disponibles en la plataforma.',
            'Docente' => 'Administra las evaluaciones disponibles para los estudiantes.',
            'Usuario' => 'Aquí podrás realizar las evaluaciones de cada unidad. ¡Demuestra lo que has aprendido!'
        ],
        'settings' => [
            'Administrador' => 'Configura los parámetros generales del sistema.',
            'Docente' => 'Gestiona la configuración de tu cuenta docente.',
            'Usuario' => 'Aquí puedes cambiar tu nombre de usuario y contraseña.'
        ],
        'configuracion' => [
            'Administrador' => 'Configura los parámetros generales del sistema.',
            'Docente' => 'Gestiona la configuración de tu cuenta docente.',
            'Usuario' => 'Aquí puedes cambiar tu nombre de usuario y contraseña.'
        ],
        'manual' => [
            'Administrador' => 'Accede al manual completo de uso del sistema.',
            'Docente' => 'Accede al manual de uso de la plataforma docente.',
            'Usuario' => 'Aprende a usar la plataforma paso a paso. ¡Es muy fácil!'
        ]
    ];

    if (isset($subtitulos[$pagina][$rol])) {
        return $subtitulos[$pagina][$rol];
    }

    return '';
}

// Función para generar mensajes específicos del header según la vista
function generarMensajeHeader($rol, $pagina) {
    $mensajes = [
        'content' => [
            'Usuario' => 'Explorando contenido teórico',
            'Docente' => 'Gestionando contenido didáctico',
            'Administrador' => 'Administrando contenido del sistema'
        ],
        'practices' => [
            'Usuario' => 'Practicando programación',
            'Docente' => 'Gestionando prácticas interactivas',
            'Administrador' => 'Administrando unidades de práctica'
        ],
        'evaluation' => [
            'Usuario' => 'Realizando evaluaciones',
            'Docente' => 'Gestionando evaluaciones',
            'Administrador' => 'Administrando evaluaciones del sistema'
        ],
        'settings' => [
            'Usuario' => 'Configurando tu cuenta',
            'Docente' => 'Configurando tu cuenta docente',
            'Administrador' => 'Configurando el sistema'
        ]
    ];

    if (isset($mensajes[$pagina][$rol])) {
        return $mensajes[$pagina][$rol];
    }

    return 'Navegando por la plataforma';
}

// Función auxiliar para determinar si un rol debe usar lenguaje informal
function esLenguajeInformal($rol) {
    return $rol === 'Usuario';
}
