{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="Ayuda" href="https://wiki.mikrotik.com/wiki/Manual:Queue" target="_blank">
                        <span class="glyphicon glyphicon-question-sign"></span> Ayuda MikroTik
                    </a>
                </div>
                MikroTik Simple Queue Manager - Configuración
            </div>
            <div class="panel-body">
                
                {if isset($test_result)}
                    {if $test_result.success}
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong><i class="fa fa-check-circle"></i> ¡Conexión Exitosa!</strong><br>
                            {$test_result.message}
                        </div>
                    {else}
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong><i class="fa fa-exclamation-triangle"></i> Error de Conexión</strong><br>
                            {$test_result.message}
                        </div>
                    {/if}
                {/if}

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h4><i class="fa fa-info-circle"></i> Acerca de este Plugin</h4>
                            <p>Este plugin gestiona automáticamente las colas simples (Simple Queues) en tu router MikroTik.</p>
                            <p>Permite controlar el ancho de banda de tus clientes directamente desde PHPNuxBill sin necesidad de acceder al router.</p>
                        </div>
                    </div>
                </div>

                <form class="form-horizontal" method="post" role="form" action="{$_url}plugin/mikrotik_queue">
                    
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-server"></i> Configuración de Conexión MikroTik</h3>
                        </div>
                        <div class="panel-body">
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Host / IP del Router</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="mikrotik_host" 
                                           value="{$_c['mikrotik_host']}" 
                                           placeholder="192.168.1.1" required>
                                    <span class="help-block">Dirección IP o hostname de tu router MikroTik</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">Puerto API</label>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" name="mikrotik_port" 
                                           value="{if $_c['mikrotik_port']}{$_c['mikrotik_port']}{else}8728{/if}" 
                                           placeholder="8728">
                                    <span class="help-block">Puerto del servicio API (por defecto: 8728, con SSL: 8729)</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">Usuario</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="mikrotik_user" 
                                           value="{$_c['mikrotik_user']}" 
                                           placeholder="admin" required autocomplete="off">
                                    <span class="help-block">Usuario de MikroTik con permisos de API</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">Contraseña</label>
                                <div class="col-md-6">
                                    <input type="password" class="form-control" name="mikrotik_password" 
                                           value="{$_c['mikrotik_password']}" 
                                           placeholder="••••••••" required autocomplete="new-password">
                                    <span class="help-block">Contraseña del usuario de MikroTik</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-6">
                                    <button type="submit" name="test_connection" class="btn btn-info">
                                        <i class="fa fa-plug"></i> Probar Conexión
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-cogs"></i> Opciones de Automatización</h3>
                        </div>
                        <div class="panel-body">
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Habilitar Automáticamente</label>
                                <div class="col-md-6">
                                    <label>
                                        <input type="checkbox" name="mikrotik_auto_enable" value="1"
                                               {if $_c['mikrotik_auto_enable'] eq '1'}checked{/if}>
                                        Activar cola cuando el cliente pague o agregue saldo
                                    </label>
                                    <p class="help-block">
                                        Cuando un cliente realiza un pago exitoso, su cola en MikroTik se habilitará automáticamente.
                                    </p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">Suspender Automáticamente</label>
                                <div class="col-md-6">
                                    <label>
                                        <input type="checkbox" name="mikrotik_auto_disable" value="1"
                                               {if $_c['mikrotik_auto_disable'] eq '1'}checked{/if}>
                                        Deshabilitar cola cuando expire el servicio del cliente
                                    </label>
                                    <p class="help-block">
                                        La cola se suspenderá (no se eliminará) cuando el servicio del cliente expire.
                                    </p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-3 control-label">Eliminar Automáticamente</label>
                                <div class="col-md-6">
                                    <label>
                                        <input type="checkbox" name="mikrotik_auto_remove" value="1"
                                               {if $_c['mikrotik_auto_remove'] eq '1'}checked{/if}>
                                        Eliminar cola cuando se elimine el cliente
                                    </label>
                                    <p class="help-block">
                                        La cola se eliminará completamente de MikroTik si eliminas el cliente de PHPNuxBill.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-offset-3 col-md-6">
                            <button type="submit" name="save_config" class="btn btn-success btn-block btn-lg">
                                <i class="fa fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </div>

                </form>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-book"></i> Instrucciones de Configuración</h3>
                            </div>
                            <div class="panel-body">
                                
                                <h4>1. Configuración en MikroTik Router</h4>
                                <p>Antes de usar este plugin, debes configurar tu router MikroTik:</p>
                                
                                <div class="well well-sm">
                                    <strong>Comandos para ejecutar en MikroTik (Terminal/SSH):</strong>
                                    <pre><code># Habilitar servicio API
/ip service enable api

# Crear usuario para PHPNuxBill
/user add name=phpnuxbill group=full password=TU_PASSWORD_SEGURO

# Verificar que el servicio esté activo
/ip service print</code></pre>
                                </div>

                                <h4>2. Asignar IPs a los Clientes</h4>
                                <p>El plugin necesita saber la IP de cada cliente. Puedes hacerlo de dos formas:</p>
                                <ul>
                                    <li><strong>Opción 1 (Recomendada):</strong> Usar el campo <code>Service ID</code> en el perfil del cliente para guardar su IP</li>
                                    <li><strong>Opción 2:</strong> Modificar el plugin para usar un campo personalizado de tu base de datos</li>
                                </ul>

                                <h4>3. Cómo Funciona</h4>
                                <ul>
                                    <li><i class="fa fa-check text-success"></i> Cuando un cliente <strong>paga</strong>, se crea/habilita su cola en MikroTik automáticamente</li>
                                    <li><i class="fa fa-pause text-warning"></i> Cuando el servicio <strong>expira</strong>, se suspende la cola (sin eliminarla)</li>
                                    <li><i class="fa fa-trash text-danger"></i> Cuando se <strong>elimina</strong> un cliente, se elimina su cola de MikroTik</li>
                                    <li><i class="fa fa-tachometer text-info"></i> Las <strong>velocidades</strong> se toman del plan contratado por el cliente</li>
                                </ul>

                                <h4>4. Verificar en MikroTik</h4>
                                <p>Para ver las colas creadas por este plugin, ejecuta en MikroTik:</p>
                                <div class="well well-sm">
                                    <pre><code># Ver todas las colas simples
/queue simple print

# Ver solo colas de PHPNuxBill
/queue simple print where comment~"PHPNuxBill"</code></pre>
                                </div>

                                <h4>5. Seguridad</h4>
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> <strong>Recomendaciones:</strong>
                                    <ul>
                                        <li>Usa una contraseña fuerte para el usuario de API</li>
                                        <li>Considera usar API-SSL (puerto 8729) en lugar de API (puerto 8728)</li>
                                        <li>Limita el acceso al puerto API solo desde la IP del servidor PHPNuxBill</li>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}