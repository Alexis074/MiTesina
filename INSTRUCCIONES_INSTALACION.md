# Instrucciones de Instalación - Sistema de Usuarios y Permisos

## Paso 1: Crear las Tablas en la Base de Datos

1. Abre **EasyPHP Devserver 17**
2. Abre **phpMyAdmin** (normalmente en http://localhost/phpmyadmin)
3. Selecciona tu base de datos (probablemente `repuestos`)
4. Ve a la pestaña **SQL**
5. Copia y pega el contenido completo del archivo `database_setup.sql`
6. Haz clic en **Ejecutar**

Esto creará:
- Tabla `usuarios` - Para almacenar usuarios del sistema
- Tabla `permisos` - Para almacenar permisos por rol y módulo
- Tabla `sesiones` - Para registrar sesiones (auditoría)
- Usuario administrador por defecto (admin/admin)
- Permisos predefinidos para Administrador y Vendedor

## Paso 2: Verificar el Usuario Administrador

El usuario administrador por defecto es:
- **Usuario:** admin
- **Contraseña:** admin

**NOTA IMPORTANTE:** La contraseña en la base de datos está hasheada. Si necesitas cambiarla, puedes usar este código PHP:

```php
<?php
echo password_hash('admin', PASSWORD_DEFAULT);
?>
```

## Paso 3: Estructura de Permisos

### Administrador
- Tiene acceso completo a todos los módulos
- Puede crear, editar y eliminar usuarios
- Puede acceder a todos los módulos

### Vendedor
- Solo puede acceder a:
  - **Ventas:** Ver y crear (no editar ni eliminar)
  - **Clientes:** Ver y crear (no eliminar)
  - **Productos:** Solo ver
  - **Stock:** Solo ver

## Paso 4: Crear Nuevos Usuarios

1. Inicia sesión como administrador
2. Ve a la pestaña **Usuarios** en el menú
3. Haz clic en **+ Agregar Usuario**
4. Completa el formulario:
   - Usuario (único)
   - Contraseña
   - Nombre completo
   - Rol (Administrador o Vendedor)
   - Estado (Activo/Inactivo)

## Paso 5: Modificar Permisos (Opcional)

Si necesitas crear roles personalizados o modificar permisos:

1. Abre phpMyAdmin
2. Ve a la tabla `permisos`
3. Puedes agregar nuevos roles o modificar permisos existentes

Ejemplo de inserción de permisos:
```sql
INSERT INTO permisos (rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar) 
VALUES ('Vendedor', 'productos', 1, 0, 0, 0);
```

## Archivos Creados

- `login.php` - Página de inicio de sesión
- `logout.php` - Cerrar sesión
- `includes/session.php` - Gestión de sesiones
- `includes/auth.php` - Sistema de autenticación y permisos
- `modulos/usuarios/usuarios.php` - Lista de usuarios
- `modulos/usuarios/agregar_usuario.php` - Crear nuevo usuario
- `database_setup.sql` - Script de creación de tablas

## Protección de Páginas

Todas las páginas principales ahora requieren:
- Inicio de sesión
- Permisos específicos según el módulo

Si un usuario sin permisos intenta acceder, será redirigido al inicio.

## Notas Importantes

- El sistema usa `password_hash()` de PHP 5.6+
- Las contraseñas se almacenan de forma segura (hasheadas)
- Las sesiones se registran en la tabla `sesiones` para auditoría
- El usuario administrador no puede ser eliminado por otros usuarios

