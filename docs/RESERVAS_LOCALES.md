# Disponibilidad y reservas de operación propia

Esta entrega permite gestionar salidas diarias de actividades y paquetes propios. No consulta inventario de aerolíneas u hoteles, no procesa pagos y no emite tiquetes o documentos de proveedores externos.

## Configurar fechas y cupos

1. En Inventario, crea o edita un servicio de categoría Actividades o Paquetes.
2. Comprueba que el proveedor esté activo y que el servicio esté publicado.
3. En la tarjeta del servicio, abre **Fechas y cupos**.
4. Registra una fecha futura, capacidad total, tarifa final por viajero en COP y condiciones. Hay una salida por servicio y fecha; no se gestionan horarios o franjas dentro del mismo día.
5. Administración debe abrir la salida. Un proveedor puede preparar y editar las salidas de su propio inventario; sus cambios las cierran hasta una nueva aprobación.

No es posible reducir la capacidad por debajo de los cupos retenidos o confirmados. Una salida con historial de reservas no puede cambiar de fecha. Los servicios con reservas no pueden cambiar de proveedor o categoría. Cerrar una salida detiene nuevas solicitudes sin cancelar las existentes. Para editar una salida cuya fecha ya pasó, se debe crear una nueva fecha; no se reescribe su historial.

## Solicitar una reserva

La ficha pública del servicio muestra las primeras 90 salidas futuras abiertas y su disponibilidad. El cliente elige fecha y viajeros, revisa tarifa y condiciones y solicita la reserva desde una cuenta con correo verificado.

El servidor revalida la publicación, el proveedor, la fecha, los cupos y la versión de la tarifa/condiciones. No acepta totales enviados por el navegador. Si cambia la oferta, el cliente debe recargarla y revisarla de nuevo.

Al solicitar:

- Se retienen cupos de manera transaccional.
- Se genera una referencia única y se conserva el nombre del servicio, proveedor, fecha, tarifa por viajero, total, moneda y condiciones aceptadas.
- La solicitud queda pendiente de confirmación durante un máximo de 24 horas, o hasta el comienzo del día de la salida si sucede antes.
- Los reintentos con la misma clave de idempotencia y los mismos datos devuelven la solicitud original, sin duplicar cupos. La misma clave con datos diferentes se rechaza.
- No se realiza un cobro. La referencia es un registro de operación propia, no un tiquete de una aerolínea ni un voucher emitido por una API.

## Confirmar, cancelar y vencer

**Mis reservas** (`/mis-reservas`) muestra únicamente el historial del cliente. **Gestionar reservas** (`/gestion/reservas`) permite operar a administración, agentes y soporte; cada proveedor solo ve y opera las reservas de su propio inventario.

Solo se pueden confirmar solicitudes pendientes, vigentes y para fechas futuras, cuyo servicio siga publicado y proveedor activo. La confirmación mantiene la tarifa y las condiciones originales. Repetir la confirmación no duplica efectos.

El cliente y el operador autorizado pueden cancelar antes del día de la salida, indicando un motivo. La cancelación libera cupos una sola vez. Las solicitudes canceladas o vencidas no pueden confirmarse. Desde el día de la salida se debe contactar a soporte; no se gestionan cambios desde estos botones.

Las solicitudes vencidas liberan cupos mediante el planificador. Antes de nuevas reservas, confirmaciones, cancelaciones y ajustes de capacidad también se procesan los vencimientos de esa salida. La disponibilidad pública descuenta solo solicitudes vigentes y reservas confirmadas; no incluye retenciones vencidas aunque el planificador aún no haya corrido.

## Ejecutar los vencimientos

`composer run dev` ahora incluye `php artisan schedule:work`. Si ejecutas solo `php artisan serve`, abre otra terminal con:

```sh
php artisan schedule:work
```

Para procesar manualmente los vencimientos:

```sh
php artisan tourism:expire-bookings
```

En producción configura el planificador estándar de Laravel para ejecutar `php artisan schedule:run` cada minuto. Se registra en auditoría la solicitud, confirmación, cancelación y vencimiento. Se generan avisos internos para el cliente. El correo operativo está implementado en cola y desactivado por defecto; WhatsApp sigue pendiente. Consulta [Avisos y comprobantes](AVISOS_Y_COMPROBANTES.md).

## Verificación y límites

Las pruebas incluyen reintentos, cambios de tarifa, sobrecupo, propiedad de las reservas, estados terminales, expiraciones, cupos liberados y dos procesos compitiendo simultáneamente por el último cupo en una base SQLite aislada. SQLite utiliza transacciones IMMEDIATE y espera de bloqueo de 5 segundos. PostgreSQL mantiene los bloqueos por fila, pero esta entrega no incluye una prueba de concurrencia contra PostgreSQL.

No hay checkout, cobros, conciliación, reembolsos, emisión de vouchers externos ni confirmación mediante APIs. Los pagos realizados fuera de esta plataforma no se registran aquí. Las reglas actuales son tarifa por viajero y cancelación antes del día de salida; no hay noches de hotel, tarifas por edad, multidestino, descuentos o múltiples franjas horarias.

Las cuentas con historial de reservas no pueden eliminarse automáticamente desde el perfil; se solicita gestionar el caso con soporte para conservar la trazabilidad de la operación. No se implementa todavía un flujo de anonimización o retención configurable.
