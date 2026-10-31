# Integraciones seleccionadas

El proyecto tiene seleccionados Wompi Colombia, Netactica Colombia y Avianca Link. La elección de un proveedor no concede acceso a sus servicios. No se han creado cuentas, firmado contratos ni obtenido credenciales por cuenta del usuario.

## Estado de esta entrega

| Integración        | Implementación                                                                                                                 | Activación externa                                                                                                                                                                     |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Wompi Colombia     | Checkout web sandbox, firma de integridad, webhook firmado, consulta privada de transacciones, estados, reintentos e historial | Desactivado. Requiere claves de prueba del comercio y una URL pública para el webhook. Las pruebas automatizadas simulan respuestas HTTP; no son una certificación en el sandbox real. |
| Netactica Colombia | Selección, URL base opcional y estado visible en administración                                                                | Conector pendiente de documentación técnica contractual y acceso de pruebas. No se hacen llamadas.                                                                                     |
| Avianca Link       | Selección, URL base opcional y estado visible en administración                                                                | Conector NDC pendiente de documentación, autenticación, credenciales y alcance comercial. No se hacen llamadas.                                                                        |

La vista `/gestion/integraciones` está reservada a superadministración. Muestra nombres de variables y estado de configuración; nunca muestra secretos. El estado “Configurado para pruebas” de Wompi comprueba prefijos y presencia de variables locales, no la validez de las credenciales en los servidores del proveedor.

## Configurar Wompi sandbox

Obtén las credenciales de sandbox desde el panel del comercio de Wompi y escríbelas directamente en `.env`. No las publiques en el repositorio ni las envíes por el chat.

```dotenv
WOMPI_ENABLED=true
WOMPI_ENVIRONMENT=sandbox
WOMPI_PUBLIC_KEY=pub_test_...
WOMPI_PRIVATE_KEY=prv_test_...
WOMPI_INTEGRITY_SECRET=test_integrity_...
WOMPI_EVENTS_SECRET=test_events_...
```

Los puntos suspensivos son marcadores, no claves válidas. Después ejecuta `php artisan config:clear`. La aplicación rechaza ambientes de producción y claves con prefijos de producción, aunque se cambien las variables.

Configura el webhook de sandbox en el panel de Wompi:

```text
https://TU-DOMINIO/webhooks/wompi/sandbox
```

El dominio debe apuntar a esta aplicación y ser accesible desde Wompi. `localhost` no funciona como receptor externo. El endpoint está exceptuado de CSRF únicamente en esa ruta y valida la firma criptográfica del evento. Debe conservarse el secreto de eventos para procesar notificaciones en curso, incluso si se deshabilita la creación de nuevos checkouts.

## Probar desde la aplicación

1. Crea una actividad o paquete propio con salida abierta y tarifa por viajero.
2. Desde una cuenta cliente verificada, solicita la reserva.
3. Confírmala con una cuenta operadora autorizada.
4. En **Mis reservas**, aparecerá **Probar pago con Wompi** cuando la configuración sandbox esté habilitada.
5. El checkout se prepara internamente, con el monto de la reserva convertido a centavos, referencia única y firma de integridad.
6. Pulsa **Abrir Wompi sandbox** y usa los datos de prueba publicados por Wompi. Los datos de tarjeta se ingresan en su checkout y no pasan por Laravel.
7. Al regresar, pulsa **Actualizar estado**. El retorno del navegador no aprueba un pago; se necesita el webhook verificado.

Administración y finanzas pueden consultar `/gestion/pagos`. Todos los registros de esta entrega están marcados como sandbox y deben excluirse de ingresos, liquidaciones y conciliación real.

## Validación de eventos

La firma se calcula con los campos indicados dinámicamente en `signature.properties`, el timestamp y el secreto de eventos. El identificador de transacción debe estar firmado. Después Laravel consulta la transacción por ID con la **llave privada** en el API sandbox y valida referencia, moneda y monto contra el intento local. Esto evita confiar en propiedades no firmadas del cuerpo o en datos de la URL de retorno.

Los eventos procesados se deduplican y los estados finales no retroceden por notificaciones pendientes tardías. Los fallos de consulta devuelven un error para permitir el reintento del proveedor. No se almacena el cuerpo completo del evento, información de tarjeta ni datos personales del pagador.

La creación repetida de un checkout reutiliza el intento activo. Tras una prueba rechazada o con error puede generarse otro intento con nueva referencia. Una prueba aprobada bloquea la creación de otro intento para la misma reserva. Un evento sandbox tardío no reactiva una reserva cancelada ni vuelve a retener cupos.

## Completar Netactica y Avianca Link

Solicita a cada proveedor:

- Documentación técnica de la versión contratada y ambiente de pruebas.
- URLs base oficiales y método de autenticación, sin asumir un formato de token.
- Credenciales, identificadores comerciales y permisos concedidos a la agencia.
- Productos/aerolíneas habilitados, monedas y reglas de tarifas.
- Flujos autorizados de búsqueda, revalidación, reserva, emisión y postventa.
- Límites, tiempos de espera, reglas de reintento y casos de certificación.

Se pueden registrar como referencia `NETACTICA_BASE_URL` y `AVIANCA_LINK_BASE_URL` en el entorno. Estas variables por sí solas no activan llamadas ni autentican al comercio. No se inventaron endpoints o credenciales para estos dos servicios.

Netactica puede actuar como integrador de contenido de Avianca; debe confirmarse comercialmente si se usará esa ruta o una conexión Avianca Link independiente antes de duplicar conectores.

## Fuentes técnicas

- [Wompi: checkout web e integridad](https://docs.wompi.co/docs/colombia/widget-checkout-web/)
- [Wompi: ambientes y claves](https://docs.wompi.co/docs/colombia/ambientes-y-llaves/)
- [Wompi: firma de eventos](https://docs.wompi.co/docs/colombia/eventos/)
- [Wompi: consulta de transacciones con llave privada](https://docs.wompi.co/docs/colombia/seguimiento-de-transacciones/)
- [Wompi: datos de prueba](https://docs.wompi.co/docs/colombia/datos-de-prueba-en-sandbox/)
- [Netactica](https://www.netactica.co/)
- [Avianca: capacidades y canales NDC](https://static.avianca.com/media/rjqbkhfy/20250512-formato-capacidades-ndc-avianca-direct.pdf)

## Pendiente para producción

Pagos reales, reembolsos, resolución de pagos tardíos sobre reservas canceladas, conciliación, dispersión a proveedores, certificación y pruebas reales con los proveedores. Los estados sandbox no marcan una reserva como pagada, no cambian su confirmación y no constituyen evidencia de un cobro real.
