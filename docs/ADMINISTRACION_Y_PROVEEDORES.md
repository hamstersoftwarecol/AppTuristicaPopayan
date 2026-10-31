# Administración y proveedores

## Entrar al panel

1. Crea una cuenta administrativa con `php artisan tourism:create-admin`.
2. Inicia sesión y verifica el correo. En local, el enlace se guarda en `storage/logs/laravel.log`; en producción debes configurar SMTP.
3. En Seguridad, configura y confirma el doble factor.
4. Abre `/dashboard`. El menú muestra las herramientas autorizadas para tu cuenta.

No se crean cuentas ni contraseñas predeterminadas. Las cuentas que se registran en la web reciben exclusivamente el rol cliente.

## Incorporar un proveedor

1. El responsable crea su cuenta y verifica su correo.
2. Superadministración abre **Usuarios y roles** (`/gestion/usuarios`) y asigna el rol **proveedor** a esa cuenta.
3. Administración abre **Proveedores** (`/gestion/proveedores`) y registra nombre comercial y correo de contacto. Puede añadir teléfono, web HTTPS, notas internas y cuenta responsable.
4. El proveedor comienza como pendiente. Administración puede activarlo o desactivarlo.
5. El responsable verá **Mi organización** (`/gestion/mi-organizacion`) para consultar el estado y editar sus contactos, e **Inventario** (`/gestion/servicios`) para cargar servicios.

Las notas internas y los contactos del proveedor no se exponen en el catálogo público. Un proveedor solo puede ver y modificar su propio inventario y sus propios datos de contacto.

## Publicar servicios

Los servicios admiten categoría, destino, descripción, condiciones y precio de referencia opcional en COP. Un proveedor puede guardar borradores o enviar servicios a revisión. Administración revisa y publica; solo los servicios publicados de proveedores activos aparecen en `/explorar`.

Si un proveedor edita un servicio publicado, debe guardarlo como borrador o enviarlo nuevamente a revisión. Deja de estar visible hasta que administración vuelva a aprobarlo. Administración puede archivarlo desde el selector de estado. Desactivar un proveedor oculta todo su inventario sin borrar solicitudes ni registros históricos.

Para configurar cupos por fecha y reservas de actividades/paquetes propios, consulta [Reservas locales](RESERVAS_LOCALES.md).

El catálogo tiene búsqueda por texto, categoría, destino, precio máximo de referencia, orden por precio o fecha y paginación. No consulta disponibilidad externa ni emite tiquetes.

## Cotizaciones y favoritos

El cliente puede guardar servicios en favoritos y solicitar una cotización desde la ficha de un servicio publicado. Debe verificar su correo y proporcionar fecha estimada, viajeros y un mensaje.

- **Mis solicitudes** (`/mis-solicitudes`): historial propio, respuestas y cancelación de solicitudes pendientes o en atención.
- **Atender solicitudes** (`/gestion/solicitudes`): administración, agentes y soporte pueden filtrar, marcar en atención, responder o cancelar.
- Al responder, es obligatorio escribir un mensaje. Las solicitudes respondidas o canceladas quedan cerradas y no pueden sobrescribirse.
- La respuesta aparece en el panel del cliente. Se genera un aviso interno al cliente. El correo operativo en cola puede configurarse según [Avisos y comprobantes](AVISOS_Y_COMPROBANTES.md); WhatsApp sigue pendiente.

Una solicitud no es una reserva, no bloquea inventario y no cobra al cliente. El precio de referencia no es una cotización vinculante ni una tarifa en tiempo real.

## Usuarios y seguridad

Solo superadministración puede cambiar roles desde la interfaz. No puede cambiar su propio rol desde ese formulario. Administración y finanzas deben confirmar el doble factor antes de usar herramientas protegidas. La autorización se valida en Laravel en cada petición, aunque alguien intente abrir una URL o enviar un formulario directamente.

## Blog

Administración y editores pueden crear y editar artículos en `/gestion/articulos`. El contenido se guarda como texto sin formato. Una fecha vacía conserva el borrador; una fecha futura programa la publicación a medianoche, hora de Colombia. Solo los artículos cuya fecha ya llegó son públicos en `/blog`. Hay búsqueda, filtro de categorías y paginación.

## Auditoría

`/gestion/auditoria` registra actor, acción, recurso, fecha y cambios de estado/rol para las operaciones nuevas. Incluye proveedores, contactos, servicios, artículos, solicitudes y roles. No registra contraseñas ni secretos de doble factor. El panel es de consulta; no permite alterar los registros. No es un registro inmutable externo ni cubre todavía todos los eventos de autenticación.

## Alcance pendiente

Las APIs de proveedores, contratos de afiliación, disponibilidad y reservas de proveedores externos, checkout, pasarela de pagos, vouchers, conciliación, reembolsos, S3, Horizon, Sanctum, notificaciones de cotizaciones y despliegue siguen pendientes. El blog no incluye todavía galerías, etiquetas ni subida de imágenes. La gestión de proveedores es un directorio operativo y portal de inventario; no es un conector REST.
