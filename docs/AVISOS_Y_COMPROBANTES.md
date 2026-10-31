# Avisos operativos y comprobantes locales

## Notificaciones internas

En `/notificaciones`, cada cuenta consulta exclusivamente sus propios avisos. Hay filtro de avisos sin leer, paginación, enlaces al detalle y opciones para marcar uno o todos como leídos. El menú muestra el contador de pendientes. Abrir el detalle no marca automáticamente el aviso como leído.

Se generan avisos al:

- Recibir una solicitud de reserva local.
- Confirmar, cancelar o vencer una reserva.
- Responder o cancelar una cotización desde la atención de solicitudes.

Los reintentos que no cambian el estado de la operación no generan avisos repetidos. La notificación interna se guarda en la misma transacción que la operación; si esta falla, ambas se revierten. No se incluyen respuestas completas ni datos del pagador en el aviso. Los eventos de Wompi sandbox no se presentan como un pago real confirmado.

Las cuentas operadoras siguen usando sus bandejas de solicitudes y reservas; esta entrega avisa al cliente afectado. No se implementaron campañas, alertas de precios ni WhatsApp.

## Correo en cola

El correo operativo está desactivado por defecto:

```dotenv
OPERATIONAL_MAIL_ENABLED=false
```

Los avisos internos funcionan aunque el correo esté apagado. Esta opción no cambia los correos de verificación de cuenta o recuperación de contraseña de Fortify.

Para habilitar el correo operativo, configura y comprueba el transporte de correo del servidor y luego establece `OPERATIONAL_MAIL_ENABLED=true`. Ejecuta `php artisan config:clear` y reinicia los workers con `php artisan queue:restart` después de cambiar la configuración.

`MAIL_MAILER=log` solo registra el mensaje en el log; no entrega correo a una bandeja real. El estado mostrado en Integraciones comprueba configuración local y no certifica la entrega.

Usa una cola asíncrona como `database` o `redis`. Ejecuta el worker:

```sh
php artisan queue:work --tries=3 --timeout=60
```

`composer run dev` ya incluye el proceso de cola. En producción, mantén el worker con un gestor de procesos. Los envíos esperan al commit de la transacción y tienen hasta tres intentos, con esperas de 60 y 300 segundos. Un error SMTP no debe revertir la reserva ya confirmada: el trabajo queda para reintento o en `failed_jobs`.

Para inspeccionar fallos:

```sh
php artisan queue:failed
```

No ejecutes reintentos masivos sin revisar el fallo y los destinatarios. Esta entrega no se probó con un servidor SMTP real y no registra confirmaciones de recepción o lectura de correo.

## Comprobante de reserva local

En **Mis reservas** y **Gestionar reservas**, el botón **Ver comprobante / Imprimir** abre un documento HTML protegido. Solo acceden el titular de la reserva y el operador autorizado. El acceso queda en auditoría.

Incluye referencia, estado actual, titular, proveedor, fecha, viajeros, tarifa original, total en COP, condiciones aceptadas y fechas de confirmación o cancelación. Una solicitud vencida se muestra como vencida aunque el proceso de expiración no haya actualizado aún la fila.

El documento indica que no acredita pagos reales, no es una factura y no sustituye documentos de un proveedor externo. Los importes de pruebas Wompi no se presentan como dinero cobrado.

El navegador permite imprimirlo o guardarlo como PDF desde **Imprimir / Guardar como PDF**. No hay generación de archivos PDF en el servidor, almacenamiento de documentos ni envío de adjuntos en esta entrega. Al volver a consultarlo, refleja el estado vigente de la reserva; no es una copia histórica inmutable.

El contenido se escapa para evitar que descripciones o condiciones ejecuten HTML o scripts. La respuesta usa caché privada sin almacenamiento y no permite que otro sitio la incruste en un iframe. Los avisos de una cuenta se eliminan cuando se elimina su perfil; las cuentas con historial de reservas siguen requiriendo gestión asistida para su eliminación.
