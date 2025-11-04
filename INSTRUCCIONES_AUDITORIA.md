# Instrucciones de Instalación - Sistema de Auditoría y Facturas Anuladas

## 1. Ejecutar Script SQL

Ejecuta el archivo `database_auditoria_setup.sql` en phpMyAdmin o desde la línea de comandos:

```sql
-- Este script crea:
-- 1. Tabla de auditoría
-- 2. Campos anulada, fecha_anulacion, usuario_anulacion_id, motivo_anulacion en cabecera_factura_ventas
```

**Pasos:**

1. Abre phpMyAdmin
2. Selecciona la base de datos `db_repuestos`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `database_auditoria_setup.sql`
5. Haz clic en "Continuar"

**Nota:** Si MySQL no soporta `IF NOT EXISTS` en `ALTER TABLE`, ejecuta estos comandos manualmente:

```sql
ALTER TABLE cabecera_factura_ventas ADD COLUMN anulada TINYINT(1) DEFAULT 0;
ALTER TABLE cabecera_factura_ventas ADD COLUMN fecha_anulacion DATETIME NULL;
ALTER TABLE cabecera_factura_ventas ADD COLUMN usuario_anulacion_id INT NULL;
ALTER TABLE cabecera_factura_ventas ADD COLUMN motivo_anulacion TEXT NULL;
```

## 2. Funcionalidades Implementadas

### Facturas Anuladas

- **Visualización:** Las facturas anuladas aparecen con estilo opaco y etiqueta "ANULADA" en rojo
- **Anulación:** Desde `facturacion.php`, click en "Anular" lleva a `anular_factura.php` donde se puede:
  - Ver información de la factura
  - Ingresar motivo de anulación
  - Confirmar anulación (revierte el stock automáticamente)
- **Exclusión de Totales:** Las facturas anuladas NO se incluyen en:
  - Cálculos de cierre de caja
  - Reportes y estadísticas
- **Auditoría:** Todas las anulaciones se registran automáticamente en auditoría

### Sistema de Auditoría

El sistema registra automáticamente:

- ✅ **Login/Logout:** Inicio y cierre de sesión
- ✅ **Usuarios:** Creación, edición y eliminación de usuarios
- ✅ **Ventas:** Creación de facturas de venta
- ✅ **Compras:** Creación de compras
- ✅ **Facturas:** Anulación de facturas
- ✅ **Otros eventos:** Cualquier acción importante del sistema

**Ver Auditoría:** Ve a `modulos/auditoria/auditoria.php` para ver todos los registros con:
- Fecha y hora
- Usuario que realizó la acción
- Tipo de acción (con colores)
- Módulo
- Detalle completo
- IP address

## 3. Archivos Creados/Modificados

### Nuevos Archivos:
- `includes/auditoria.php` - Funciones de auditoría
- `database_auditoria_setup.sql` - Script de creación de tablas
- `modulos/facturacion/anular_factura.php` - Proceso de anulación
- `modulos/usuarios/eliminar_usuario.php` - Eliminación de usuarios con auditoría

### Archivos Modificados:
- `modulos/facturacion/facturacion.php` - Visualización de facturas anuladas
- `modulos/auditoria/auditoria.php` - Muestra registros reales
- `login.php` - Registra login
- `logout.php` - Registra logout
- `modulos/ventas/ventas.php` - Registra creación de ventas
- `modulos/compras/compras.php` - Registra creación de compras
- `modulos/usuarios/agregar_usuario.php` - Registra creación de usuarios
- `modulos/usuarios/editar_usuario.php` - Registra edición de usuarios
- `style.css` - Estilos para facturas anuladas

## 4. Notas Importantes

1. **Tabla de Auditoría:** Se crea automáticamente si no existe al intentar registrar un evento
2. **Facturas Anuladas:** Una vez anulada, la factura no puede ser anulada nuevamente
3. **Stock:** Al anular una factura, el stock de productos se revierte automáticamente
4. **Permisos:** Se requiere permiso 'facturacion', 'editar' para anular facturas
5. **Exclusión:** Las facturas anuladas se excluyen automáticamente de cálculos de totales

## 5. Próximos Pasos (Opcional)

Si necesitas excluir facturas anuladas de reportes específicos, agrega esta condición a las consultas:

```sql
WHERE anulada = 0 OR anulada IS NULL
```

Ejemplo:
```sql
SELECT SUM(monto_total) FROM cabecera_factura_ventas 
WHERE fecha_hora BETWEEN ? AND ? 
AND (anulada = 0 OR anulada IS NULL)
```

