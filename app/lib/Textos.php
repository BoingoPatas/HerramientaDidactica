<?php

/**
 * Textos - Sistema centralizado de textos de interfaz de usuario
 *
 * Este archivo contiene textos comunes de la interfaz que varían según el rol del usuario.
 * - Rol Usuario (estudiante): Lenguaje informal y agradable
 * - Rol Docente/Administrador: Lenguaje formal y administrativo
 * - Todos los mensajes en español
 */

class Textos
{
    /**
     * Textos para botones y acciones comunes
     */
    public static function getBoton($accion, $rol = 'Usuario') {
        $botones = [
            'guardar' => [
                'Usuario' => 'Guardar',
                'Docente' => 'Guardar',
                'Administrador' => 'Guardar'
            ],
            'cancelar' => [
                'Usuario' => 'Cancelar',
                'Docente' => 'Cancelar',
                'Administrador' => 'Cancelar'
            ],
            'editar' => [
                'Usuario' => 'Editar',
                'Docente' => 'Editar',
                'Administrador' => 'Editar'
            ],
            'eliminar' => [
                'Usuario' => 'Eliminar',
                'Docente' => 'Eliminar',
                'Administrador' => 'Eliminar'
            ],
            'crear' => [
                'Usuario' => 'Crear',
                'Docente' => 'Crear',
                'Administrador' => 'Crear'
            ],
            'verificar' => [
                'Usuario' => 'Verificar',
                'Docente' => 'Verificar',
                'Administrador' => 'Verificar'
            ],
            'comenzar' => [
                'Usuario' => '¡Comenzar!',
                'Docente' => 'Comenzar',
                'Administrador' => 'Comenzar'
            ],
            'continuar' => [
                'Usuario' => 'Continuar',
                'Docente' => 'Continuar',
                'Administrador' => 'Continuar'
            ]
        ];

        return $botones[$accion][$rol] ?? $botones[$accion]['Usuario'];
    }

    /**
     * Textos para mensajes de confirmación
     */
    public static function getMensajeConfirmacion($tipo, $rol = 'Usuario') {
        $mensajes = [
            'eliminar_contenido' => [
                'Usuario' => '¿Estás seguro de que quieres eliminar este contenido?',
                'Docente' => '¿Está seguro de que desea eliminar este contenido?',
                'Administrador' => '¿Está seguro de que desea eliminar este contenido?'
            ],
            'cerrar_sesion' => [
                'Usuario' => '¿Estás seguro de que quieres cerrar sesión?',
                'Docente' => '¿Está seguro de que desea cerrar sesión?',
                'Administrador' => '¿Está seguro de que desea cerrar sesión?'
            ],
            'guardar_cambios' => [
                'Usuario' => '¿Quieres guardar los cambios realizados?',
                'Docente' => '¿Desea guardar los cambios realizados?',
                'Administrador' => '¿Desea guardar los cambios realizados?'
            ]
        ];

        return $mensajes[$tipo][$rol] ?? $mensajes[$tipo]['Usuario'];
    }

    /**
     * Textos para placeholders y mensajes de ayuda
     */
    public static function getPlaceholder($campo, $rol = 'Usuario') {
        $placeholders = [
            'titulo' => [
                'Usuario' => 'Escribe un título aquí...',
                'Docente' => 'Ingrese el título...',
                'Administrador' => 'Ingrese el título...'
            ],
            'descripcion' => [
                'Usuario' => 'Describe brevemente...',
                'Docente' => 'Ingrese la descripción...',
                'Administrador' => 'Ingrese la descripción...'
            ],
            'codigo' => [
                'Usuario' => '// Escribe tu código aquí...',
                'Docente' => '// Ingrese el código aquí...',
                'Administrador' => '// Ingrese el código aquí...'
            ],
            'comentario' => [
                'Usuario' => 'Deja tu comentario...',
                'Docente' => 'Ingrese su comentario...',
                'Administrador' => 'Ingrese su comentario...'
            ]
        ];

        return $placeholders[$campo][$rol] ?? $placeholders[$campo]['Usuario'];
    }

    /**
     * Textos para estados y mensajes informativos
     */
    public static function getEstado($estado, $rol = 'Usuario') {
        $estados = [
            'cargando' => [
                'Usuario' => 'Cargando...',
                'Docente' => 'Cargando...',
                'Administrador' => 'Cargando...'
            ],
            'guardando' => [
                'Usuario' => 'Guardando...',
                'Docente' => 'Guardando...',
                'Administrador' => 'Guardando...'
            ],
            'procesando' => [
                'Usuario' => 'Procesando...',
                'Docente' => 'Procesando...',
                'Administrador' => 'Procesando...'
            ],
            'exito' => [
                'Usuario' => '¡Perfecto!',
                'Docente' => 'Operación exitosa',
                'Administrador' => 'Operación exitosa'
            ],
            'error' => [
                'Usuario' => 'Ups, algo salió mal',
                'Docente' => 'Error en la operación',
                'Administrador' => 'Error en la operación'
            ],
            'sin_resultados' => [
                'Usuario' => 'No encontramos nada aquí',
                'Docente' => 'No se encontraron resultados',
                'Administrador' => 'No se encontraron resultados'
            ]
        ];

        return $estados[$estado][$rol] ?? $estados[$estado]['Usuario'];
    }

    /**
     * Textos para secciones específicas
     */
    public static function getSeccionTexto($seccion, $elemento, $rol = 'Usuario') {
        $textos = [
            'contenido' => [
                'titulo' => [
                    'Usuario' => 'Material teórico',
                    'Docente' => 'Contenido Didáctico',
                    'Administrador' => 'Gestión de Contenido'
                ],
                'descripcion' => [
                    'Usuario' => 'Aquí encontrarás todo el contenido teórico',
                    'Docente' => 'Administre el contenido disponible',
                    'Administrador' => 'Gestione el contenido del sistema'
                ],
                'sin_contenido' => [
                    'Usuario' => 'Aún no hay contenido disponible',
                    'Docente' => 'No hay contenido disponible',
                    'Administrador' => 'No hay contenido disponible'
                ]
            ],
            'practicas' => [
                'titulo' => [
                    'Usuario' => 'Ejercicios prácticos',
                    'Docente' => 'Prácticas Interactivas',
                    'Administrador' => 'Gestión de Prácticas'
                ],
                'descripcion' => [
                    'Usuario' => '¡Vamos a programar juntos!',
                    'Docente' => 'Gestione las prácticas disponibles',
                    'Administrador' => 'Administre las unidades de práctica'
                ],
                'sin_ejercicios' => [
                    'Usuario' => 'No hay ejercicios disponibles aún',
                    'Docente' => 'No hay ejercicios disponibles',
                    'Administrador' => 'No hay ejercicios disponibles'
                ]
            ],
            'evaluaciones' => [
                'titulo' => [
                    'Usuario' => 'Evaluaciones',
                    'Docente' => 'Evaluaciones',
                    'Administrador' => 'Gestión de Evaluaciones'
                ],
                'descripcion' => [
                    'Usuario' => 'Demuestra lo que has aprendido',
                    'Docente' => 'Gestione las evaluaciones',
                    'Administrador' => 'Administre las evaluaciones del sistema'
                ]
            ]
        ];

        return $textos[$seccion][$elemento][$rol] ?? '';
    }

    /**
     * Textos para consejos y ayuda
     */
    public static function getConsejo($tipo, $rol = 'Usuario') {
        $consejos = [
            'programacion' => [
                'Usuario' => '💡 Recuerda leer las instrucciones con cuidado y observar los ejemplos',
                'Docente' => '💡 Verifique que los estudiantes comprendan las instrucciones',
                'Administrador' => '💡 Asegúrese de que el contenido esté actualizado'
            ],
            'seguridad' => [
                'Usuario' => '🔒 Tu contraseña debe tener al menos 8 caracteres',
                'Docente' => '🔒 Mantenga credenciales seguras',
                'Administrador' => '🔒 Implemente políticas de seguridad'
            ]
        ];

        return $consejos[$tipo][$rol] ?? '';
    }

    /**
     * Función auxiliar para determinar el rol actual
     */
    public static function getRolActual() {
        return $_SESSION['rol'] ?? 'Usuario';
    }
}
