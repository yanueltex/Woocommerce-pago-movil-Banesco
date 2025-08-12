# WooCommerce Pago Móvil Banesco

Plugin para WooCommerce que habilita un nuevo método de pago mediante Pago Móvil Banesco, permitiendo a tus clientes pagar de forma rápida y segura a través de transferencias móviles verificadas directamente con el banco.

## Requisitos previos

1. **Habilitación del servicio:**  
   Debes contactar a Banesco para solicitar la activación de la función de Pago Móvil en tu cuenta o la de tu cliente.
2. **Credenciales de acceso:**  
   Una vez habilitado, el banco te proporcionará las siguientes credenciales:
   - **Client ID**
   - **Client Secret**
   - **Username**
   - **Password**

## Instalación

1. Descarga o clona este repositorio en la carpeta de plugins de tu instalación de WordPress.
2. Activa el plugin desde el panel de administración de WordPress.

## Configuración

1. Ve a **WooCommerce > Ajustes > Pagos** y selecciona **Pago Móvil Banesco**.
2. Completa los siguientes campos:
   - **Título:** Título que verá el cliente durante el checkout.
   - **Descripción:** Descripción que verá el cliente en el checkout.
   - **Datos de cuenta:** Información para la transferencia Pago Móvil (ejemplo: Banco, RIF, Cuenta).
   - **Client ID:** Proporcionado por Banesco.
   - **Client Secret:** Proporcionado por Banesco.
   - **Username:** Proporcionado por Banesco.
   - **Password:** Proporcionado por Banesco.
3. Guarda la información
4. Haz clic en el botón **Probar Conexión** para verificar que las credenciales sean correctas. Si la conexión es exitosa, se mostrará un token de acceso en pantalla.

## Uso en el checkout

En la pantalla de Checkout, el cliente verá el método de pago **Pago Móvil Banesco** y deberá completar los siguientes campos:

- Número de Referencia
- Fecha de la Transferencia
- Banco Emisor
- Número de Teléfono
- Monto

El plugin enviará estos datos a Banesco para su validación. Según la respuesta del banco, el cliente recibirá un mensaje en pantalla indicando el estado de su pago.

## Gestión de pagos

- Todos los datos ingresados por el cliente quedan registrados en el pedido de WooCommerce, incluyendo el estatus de validación del banco.
- El administrador puede consultar toda la información relevante desde el área de administración de WooCommerce.

## Notas

- Si el monto transferido es inferior al total del pedido, el plugin marcará el pedido como "Pago Incompleto" y notificará tanto al cliente como al administrador.
- Es importante mantener las credenciales seguras y actualizadas.

## Soporte

Para dudas o soporte, contacta al desarrollador o abre un issue en este repositorio.

---

**Desarrollado por Alejandro**
