<?php

/**
 * MikroTik Simple Queue Manager Plugin for PHPNuxBill
 * File: mikrotik_queue.php
 * Location: system/plugin/mikrotik_queue.php
 * 
 * IMPORTANTE: Todas las funciones deben comenzar con "mikrotik_queue_"
 */

// Registrar menú en configuración (usando el nombre de la función principal)
register_menu("MikroTik Queue", true, "mikrotik_queue", 'SETTINGS', 'ion-wifi', '', 'primary', ['SuperAdmin', 'Admin']);

// Registrar hooks para eventos de clientes
register_hook('customer_add_balance', 'mikrotik_queue_hook_activate');
register_hook('customer_expired', 'mikrotik_queue_hook_suspend');
register_hook('customer_delete', 'mikrotik_queue_hook_remove');

/**
 * Función principal del menú - Página de configuración
 * Esta es la función que se llama cuando haces clic en el menú
 */
function mikrotik_queue()
{
    global $ui, $config;
    _admin();
    
    $ui->assign('_title', 'MikroTik Queue Manager');
    $ui->assign('_system_menu', 'settings');
    
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    
    // Procesar formulario de guardado
    if (isset($_POST['save_config'])) {
        mikrotik_queue_save_config();
        r2(U . 'plugin/mikrotik_queue', 's', 'Configuración guardada exitosamente');
    }
    
    // Test de conexión
    if (isset($_POST['test_connection'])) {
        $result = mikrotik_queue_test_connection();
        $ui->assign('test_result', $result);
    }
    
    // Mostrar página
    $ui->display('mikrotik_queue.tpl');
}

/**
 * Guardar configuración del plugin
 */
function mikrotik_queue_save_config()
{
    // Host
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_host')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_host';
    }
    $d->value = _post('mikrotik_host');
    $d->save();
    
    // Usuario
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_user')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_user';
    }
    $d->value = _post('mikrotik_user');
    $d->save();
    
    // Contraseña
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_password')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_password';
    }
    $d->value = _post('mikrotik_password');
    $d->save();
    
    // Puerto
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_port')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_port';
    }
    $d->value = _post('mikrotik_port', 8728);
    $d->save();
    
    // Auto enable
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_auto_enable')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_auto_enable';
    }
    $d->value = isset($_POST['mikrotik_auto_enable']) ? '1' : '0';
    $d->save();
    
    // Auto disable
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_auto_disable')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_auto_disable';
    }
    $d->value = isset($_POST['mikrotik_auto_disable']) ? '1' : '0';
    $d->save();
    
    // Auto remove
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mikrotik_auto_remove')->find_one();
    if (!$d) {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mikrotik_auto_remove';
    }
    $d->value = isset($_POST['mikrotik_auto_remove']) ? '1' : '0';
    $d->save();
}

/**
 * Test de conexión a MikroTik
 */
function mikrotik_queue_test_connection()
{
    $connection = mikrotik_queue_connect();
    
    if ($connection['success']) {
        $connection['api']->disconnect();
        return ['success' => true, 'message' => 'Conexión exitosa a MikroTik'];
    }
    
    return $connection;
}

/**
 * Conexión a MikroTik usando RouterOS API
 */
function mikrotik_queue_connect()
{
    global $config;
    
    // Validar configuración
    if (empty($config['mikrotik_host']) || empty($config['mikrotik_user']) || empty($config['mikrotik_password'])) {
        return ['success' => false, 'message' => 'MikroTik no configurado correctamente'];
    }
    
    try {
        // Cargar librería RouterOS API
        require_once 'system/plugin/mikrotik_queue/routeros_api.class.php';
        
        $API = new RouterosAPI();
        $API->debug = false;
        
        $port = !empty($config['mikrotik_port']) ? $config['mikrotik_port'] : 8728;
        
        if ($API->connect($config['mikrotik_host'], $config['mikrotik_user'], $config['mikrotik_password'], $port)) {
            return ['success' => true, 'api' => $API];
        } else {
            return ['success' => false, 'message' => 'No se pudo conectar a MikroTik'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
    }
}

/**
 * Crear cola simple en MikroTik
 */
function mikrotik_queue_create($customer_ip, $customer_name, $upload_speed, $download_speed)
{
    $connection = mikrotik_queue_connect();
    
    if (!$connection['success']) {
        return $connection;
    }
    
    $API = $connection['api'];
    
    try {
        // Verificar si ya existe la cola
        $API->write('/queue/simple/print', false);
        $API->write('?target=' . $customer_ip . '/32');
        $existing = $API->read();
        
        if (!empty($existing)) {
            $API->disconnect();
            return ['success' => false, 'message' => 'La cola ya existe para esta IP'];
        }
        
        // Crear nueva cola simple
        $API->write('/queue/simple/add', false);
        $API->write('=name=' . $customer_name);
        $API->write('=target=' . $customer_ip . '/32');
        $API->write('=max-limit=' . $upload_speed . 'M/' . $download_speed . 'M');
        $API->write('=limit-at=' . ($upload_speed * 0.7) . 'M/' . ($download_speed * 0.7) . 'M');
        $API->write('=burst-limit=' . ($upload_speed * 1.5) . 'M/' . ($download_speed * 1.5) . 'M');
        $API->write('=burst-threshold=' . ($upload_speed * 0.8) . 'M/' . ($download_speed * 0.8) . 'M');
        $API->write('=burst-time=8s/8s');
        $API->write('=comment=PHPNuxBill-' . date('Y-m-d'));
        $READ = $API->read();
        
        $API->disconnect();
        
        if (isset($READ['!trap'])) {
            return ['success' => false, 'message' => 'Error al crear cola: ' . $READ['!trap'][0]['message']];
        }
        
        return ['success' => true, 'message' => 'Cola creada exitosamente'];
        
    } catch (Exception $e) {
        $API->disconnect();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Habilitar cola de cliente
 */
function mikrotik_queue_enable($customer_ip)
{
    $connection = mikrotik_queue_connect();
    
    if (!$connection['success']) {
        return $connection;
    }
    
    $API = $connection['api'];
    
    try {
        // Buscar la cola por IP
        $API->write('/queue/simple/print', false);
        $API->write('?target=' . $customer_ip . '/32');
        $queue = $API->read();
        
        if (empty($queue) || !isset($queue[0]['.id'])) {
            $API->disconnect();
            return ['success' => false, 'message' => 'Cola no encontrada'];
        }
        
        $queue_id = $queue[0]['.id'];
        
        // Habilitar la cola
        $API->write('/queue/simple/enable', false);
        $API->write('=.id=' . $queue_id);
        $API->read();
        
        $API->disconnect();
        
        return ['success' => true, 'message' => 'Cola habilitada'];
        
    } catch (Exception $e) {
        $API->disconnect();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Deshabilitar cola de cliente
 */
function mikrotik_queue_disable($customer_ip)
{
    $connection = mikrotik_queue_connect();
    
    if (!$connection['success']) {
        return $connection;
    }
    
    $API = $connection['api'];
    
    try {
        // Buscar la cola por IP
        $API->write('/queue/simple/print', false);
        $API->write('?target=' . $customer_ip . '/32');
        $queue = $API->read();
        
        if (empty($queue) || !isset($queue[0]['.id'])) {
            $API->disconnect();
            return ['success' => false, 'message' => 'Cola no encontrada'];
        }
        
        $queue_id = $queue[0]['.id'];
        
        // Deshabilitar la cola
        $API->write('/queue/simple/disable', false);
        $API->write('=.id=' . $queue_id);
        $API->read();
        
        $API->disconnect();
        
        return ['success' => true, 'message' => 'Cola deshabilitada'];
        
    } catch (Exception $e) {
        $API->disconnect();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Eliminar cola de cliente
 */
function mikrotik_queue_remove($customer_ip)
{
    $connection = mikrotik_queue_connect();
    
    if (!$connection['success']) {
        return $connection;
    }
    
    $API = $connection['api'];
    
    try {
        // Buscar la cola por IP
        $API->write('/queue/simple/print', false);
        $API->write('?target=' . $customer_ip . '/32');
        $queue = $API->read();
        
        if (empty($queue) || !isset($queue[0]['.id'])) {
            $API->disconnect();
            return ['success' => false, 'message' => 'Cola no encontrada'];
        }
        
        $queue_id = $queue[0]['.id'];
        
        // Eliminar la cola
        $API->write('/queue/simple/remove', false);
        $API->write('=.id=' . $queue_id);
        $API->read();
        
        $API->disconnect();
        
        return ['success' => true, 'message' => 'Cola eliminada'];
        
    } catch (Exception $e) {
        $API->disconnect();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Actualizar velocidades de cola
 */
function mikrotik_queue_update($customer_ip, $upload_speed, $download_speed)
{
    $connection = mikrotik_queue_connect();
    
    if (!$connection['success']) {
        return $connection;
    }
    
    $API = $connection['api'];
    
    try {
        // Buscar la cola por IP
        $API->write('/queue/simple/print', false);
        $API->write('?target=' . $customer_ip . '/32');
        $queue = $API->read();
        
        if (empty($queue) || !isset($queue[0]['.id'])) {
            $API->disconnect();
            return ['success' => false, 'message' => 'Cola no encontrada'];
        }
        
        $queue_id = $queue[0]['.id'];
        
        // Actualizar velocidades
        $API->write('/queue/simple/set', false);
        $API->write('=.id=' . $queue_id);
        $API->write('=max-limit=' . $upload_speed . 'M/' . $download_speed . 'M');
        $API->write('=limit-at=' . ($upload_speed * 0.7) . 'M/' . ($download_speed * 0.7) . 'M');
        $API->read();
        
        $API->disconnect();
        
        return ['success' => true, 'message' => 'Velocidades actualizadas'];
        
    } catch (Exception $e) {
        $API->disconnect();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtener IP del cliente desde PHPNuxBill
 */
function mikrotik_queue_get_ip($customer_id)
{
    // Buscar en la tabla de clientes
    $customer = ORM::for_table('tbl_customers')->find_one($customer_id);
    
    if (!$customer) {
        return null;
    }
    
    // Opción 1: Si usas el campo service_id para almacenar IP
    if (!empty($customer['service_id'])) {
        return $customer['service_id'];
    }
    
    // Opción 2: Buscar en servicios activos
    if (!empty($customer['pppoe_username'])) {
        $service = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $customer_id)
            ->where('status', 'on')
            ->order_by_desc('id')
            ->find_one();
        
        if ($service && !empty($service['routers'])) {
            return $service['routers'];
        }
    }
    
    return null;
}

/**
 * Hook: Activar cliente cuando agrega saldo o paga
 */
function mikrotik_queue_hook_activate($customer_id)
{
    global $config;
    
    // Verificar si la opción automática está habilitada
    if (empty($config['mikrotik_auto_enable'])) {
        return;
    }
    
    // Obtener IP del cliente
    $customer_ip = mikrotik_queue_get_ip($customer_id);
    
    if (empty($customer_ip)) {
        return;
    }
    
    // Habilitar cola
    mikrotik_queue_enable($customer_ip);
}

/**
 * Hook: Suspender cliente cuando expira
 */
function mikrotik_queue_hook_suspend($customer_id)
{
    global $config;
    
    // Verificar si la opción automática está habilitada
    if (empty($config['mikrotik_auto_disable'])) {
        return;
    }
    
    // Obtener IP del cliente
    $customer_ip = mikrotik_queue_get_ip($customer_id);
    
    if (empty($customer_ip)) {
        return;
    }
    
    // Deshabilitar cola
    mikrotik_queue_disable($customer_ip);
}

/**
 * Hook: Eliminar cola cuando se elimina cliente
 */
function mikrotik_queue_hook_remove($customer_id)
{
    global $config;
    
    // Verificar si la opción automática está habilitada
    if (empty($config['mikrotik_auto_remove'])) {
        return;
    }
    
    // Obtener IP del cliente
    $customer_ip = mikrotik_queue_get_ip($customer_id);
    
    if (empty($customer_ip)) {
        return;
    }
    
    // Eliminar cola
    mikrotik_queue_remove($customer_ip);
}

?>