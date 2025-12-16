# Plugin MikroTik Simple Queue Manager para PHPNuxBill

Plugin completo para gestionar automáticamente las colas simples (Simple Queues) en routers MikroTik desde PHPNuxBill.

## 🎯 Características

- ✅ Crear colas simples automáticamente
- ✅ Habilitar/deshabilitar colas según estado de pago
- ✅ Actualizar velocidades de ancho de banda
- ✅ Eliminar colas cuando se elimina un cliente
- ✅ Configuración de burst y prioridades
- ✅ Interfaz web amigable para configuración
- ✅ Test de conexión integrado
- ✅ Hooks automáticos para eventos de PHPNuxBill

## 📋 Requisitos Previos

### En MikroTik:

1. **RouterOS v6.x o superior**
2. **Servicio API habilitado** (puerto 8728)
3. **Usuario con permisos completos** para gestionar colas
4. **Pool de IPs configurado** para asignar a clientes

### En el servidor PHPNuxBill:

1. **PHPNuxBill instalado** y funcionando
2. **PHP 5.6 o superior**
3. **Extensión sockets de PHP** habilitada
4. **Conectividad de red** al router MikroTik (puerto 8728 accesible)

## 📦 Instalación

### Paso 1: Preparar MikroTik

Conéctate a tu router MikroTik vía SSH o Winbox y ejecuta:

```bash
# Habilitar servicio API
/ip service enable api

# Crear usuario para PHPNuxBill (recomendado usuario dedicado)
/user add name=phpnuxbill group=full password=TU_PASSWORD_SEGURO comment="PHPNuxBill API User"

# Verificar que el servicio API esté corriendo
/ip service print
```

**Importante:** El grupo `full` da acceso completo. Si deseas restringir permisos, crea un grupo personalizado con permisos en `/queue`.

### Paso 2: Instalar el Plugin

1. **Descargar los archivos del plugin**

2. **Crear estructura de carpetas:**
   ```
   phpnuxbill/
   ├── system/
   │   └── plugin/
   │       ├── mikrotik_queue.php          (archivo principal)
   │       └── mikrotik_queue/
   │           └── routeros_api.class.php  (librería API)
   └── ui/
       └── ui/
           └── mikrotik_queue.tpl          (template)
   ```

3. **Copiar archivos:**
   ```bash
   # Copiar archivo principal del plugin
   cp mikrotik_queue.php /ruta/phpnuxbill/system/plugin/
   
   # Crear carpeta y copiar librería API
   mkdir -p /ruta/phpnuxbill/system/plugin/mikrotik_queue/
   cp routeros_api.class.php /ruta/phpnuxbill/system/plugin/mikrotik_queue/
   
   # Copiar template
   cp mikrotik_queue.tpl /ruta/phpnuxbill/ui/ui/
   ```

4. **Establecer permisos correctos:**
   ```bash
   chmod 644 /ruta/phpnuxbill/system/plugin/mikrotik_queue.php
   chmod 644 /ruta/phpnuxbill/system/plugin/mikrotik_queue/routeros_api.class.php
   chmod 644 /ruta/phpnuxbill/ui/ui/mikrotik_queue.tpl
   ```

### Paso 3: Activar el Plugin en PHPNuxBill

1. Inicia sesión como administrador en PHPNuxBill
2. Ve a **Configuración > Plugins**
3. Busca "**MikroTik Queue Manager**"
4. Haz clic en **Activar**

### Paso 4: Configurar el Plugin

1. Ve a **Configuración > MikroTik Queue**
2. Completa los campos de conexión:
   - **Host/IP MikroTik:** IP de tu router (ej: 192.168.1.1)
   - **Puerto API:** 8728 (por defecto)
   - **Usuario:** phpnuxbill
   - **Contraseña:** La contraseña que configuraste

3. Haz clic en **Probar Conexión** para verificar

4. Activa las opciones automáticas según necesites:
   - ✅ **Habilitar Automáticamente:** Activa cola cuando el cliente paga
   - ✅ **Suspender Automáticamente:** Suspende cola cuando expira el servicio
   - ✅ **Eliminar Automáticamente:** Elimina cola cuando se elimina el cliente

5. Guarda la configuración

## 🚀 Uso

### Creación Automática de Colas

El plugin se ejecuta automáticamente cuando:

1. **Un cliente realiza un pago** → Se crea/habilita su cola
2. **Expira el servicio del cliente** → Se suspende la cola
3. **Se elimina un cliente** → Se elimina la cola de MikroTik

### Gestión Manual

Si necesitas gestionar colas manualmente, puedes usar las funciones PHP:

```php
// Crear cola para un cliente
mikrotik_queue_create_queue(
    '192.168.100.10',  // IP del cliente
    'Cliente-Juan',     // Nombre de la cola
    5,                  // Upload en Mbps
    10                  // Download en Mbps
);

// Habilitar cola
mikrotik_queue_enable_queue('192.168.100.10');

// Suspender cola
mikrotik_queue_disable_queue('192.168.100.10');

// Actualizar velocidades
mikrotik_queue_update_speed('192.168.100.10', 10, 20);

// Eliminar cola
mikrotik_queue_remove_queue('192.168.100.10');
```

## ⚙️ Configuración de IPs en PHPNuxBill

El plugin necesita saber la IP de cada cliente. Hay dos formas:

### Opción 1: Campo service_id (Recomendado)

Edita el perfil del cliente y asigna su IP en el campo `service_id`:

1. Ve a **Clientes > Editar Cliente**
2. En el campo **Service ID**, ingresa la IP del cliente (ej: 192.168.100.10)
3. Guarda

### Opción 2: Campo personalizado

Modifica la función `mikrotik_queue_get_customer_ip()` en el plugin para usar tu campo personalizado.

## 🔧 Configuración Avanzada

### Personalizar Parámetros de Cola

Edita la función `mikrotik_queue_create_queue()` para ajustar:

```php
// Límites garantizados (70% del máximo)
'=limit-at=' . ($upload_speed * 0.7) . 'M/' . ($download_speed * 0.7) . 'M'

// Límites de burst (150% del máximo)
'=burst-limit=' . ($upload_speed * 1.5) . 'M/' . ($download_speed * 1.5) . 'M'

// Umbral de burst (80% del máximo)
'=burst-threshold=' . ($upload_speed * 0.8) . 'M/' . ($download_speed * 0.8) . 'M'

// Tiempo de burst
'=burst-time=8s/8s'
```

### Usar API con SSL (Puerto 8729)

Si has habilitado API-SSL en MikroTik:

1. En MikroTik:
   ```bash
   /ip service enable api-ssl
   ```

2. Modifica el plugin:
   ```php
   // En mikrotik_queue_connect()
   $API->ssl = true;
   $port = 8729;
   ```

### Configurar Prioridades

Para agregar prioridades a las colas, modifica la función de creación:

```php
$API->write('=priority=4/4');  // 1=alta, 8=baja
```

## 🐛 Solución de Problemas

### Error: "No se pudo conectar a MikroTik"

**Posibles causas:**
1. IP incorrecta o router inaccesible
2. Puerto 8728 bloqueado por firewall
3. Servicio API no habilitado en MikroTik

**Solución:**
```bash
# Verificar conectividad
ping IP_MIKROTIK

# Probar puerto API
telnet IP_MIKROTIK 8728

# En MikroTik, verificar servicio
/ip service print
/ip service enable api
```

### Error: "Login failed"

**Posibles causas:**
1. Usuario o contraseña incorrectos
2. Usuario sin permisos suficientes

**Solución:**
```bash
# Verificar usuario
/user print

# Resetear contraseña
/user set phpnuxbill password=NUEVA_PASSWORD

# Verificar permisos
/user group print detail
```

### Error: "Cola no encontrada"

**Posibles causas:**
1. IP del cliente no configurada correctamente
2. Cola eliminada manualmente desde MikroTik

**Solución:**
1. Verifica que el cliente tenga IP asignada en PHPNuxBill
2. Revisa las colas en MikroTik: `/queue simple print`

### Las colas no se crean automáticamente

**Verificar:**
1. Plugin activado en PHPNuxBill
2. Opciones automáticas habilitadas en configuración
3. Cliente tiene IP asignada
4. Logs de PHPNuxBill para errores

## 📊 Verificación en MikroTik

Para ver las colas creadas por el plugin:

```bash
# Ver todas las colas simples
/queue simple print

# Ver colas con detalles
/queue simple print detail

# Ver solo colas de PHPNuxBill
/queue simple print where comment~"PHPNuxBill"

# Filtrar por IP específica
/queue simple print where target="192.168.100.10/32"
```

## 🔐 Seguridad

### Recomendaciones:

1. **Usar usuario dedicado** en MikroTik solo para API
2. **Contraseña fuerte** de al menos 12 caracteres
3. **Limitar acceso por IP** al servicio API:
   ```bash
   /ip service set api address=IP_SERVIDOR_PHPNUXBILL
   ```
4. **Usar API-SSL** en producción (puerto 8729)
5. **Firewall rules** para proteger puerto API

## 📝 Notas Importantes

- Las velocidades se especifican en **Mbps** (Megabits por segundo)
- El plugin usa el formato `/32` para IPs individuales
- Los cambios en MikroTik son **instantáneos**
- Se recomienda hacer **respaldo** antes de instalar

## 🆘 Soporte

Si encuentras problemas:

1. Verifica los logs de PHPNuxBill
2. Revisa logs de MikroTik: `/log print where topics~"api"`
3. Usa el test de conexión en la configuración del plugin
4. Consulta la documentación oficial de MikroTik

## 📜 Licencia

Este plugin es de código abierto. Puedes modificarlo según tus necesidades.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Haz fork del repositorio
2. Crea una rama para tu función
3. Envía un pull request

---

**Desarrollado para PHPNuxBill + MikroTik RouterOS**

**Versión:** 1.0  
**Fecha:** Diciembre 2024