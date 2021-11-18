<html>
    <head>
        <title>Certificación de Kijam Lopez</title>
    </head>
    <body>
    <?php
    require_once 'vendor/autoload.php';
    MercadoPago\SDK::setAccessToken("APP_USR-6317427424180639-042414-47e969706991d3a442922b0702a0da44-469485398");
    MercadoPago\SDK::setIntegratorId("dev_24c65fb163bf11ea96500242ac130004");
    $mbd = new PDO('mysql:host=us-cdbr-east-04.cleardb.com;dbname=heroku_d6116dc28383b00', 'b8094a05bdf3ed', 'e8903174');
    function url_origin() {
        $s = $_SERVER;
        $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
        $sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        $port     = $s['SERVER_PORT'];
        $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
        $host     = ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
        $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
        return $protocol . '://' . $host.$s['REQUEST_URI'];
    }
    function dump_get() {
        foreach($_GET as $key => $value) {
            if (strstr($key, '-mercadopago')) continue;
            echo '<b>'.ucwords(str_replace('_', ' ', $key)).'</b>: '.$value.'<br />';
        }
    }
    if (isset($_GET['ipn-mercadopago'])) {
        $sql = 'INSERT INTO mercadopago_ipn (payload) VALUES (?)';
        $statement = $mbd->prepare($sql);
        $statement->execute([file_get_contents('php://input')]);
    } elseif (isset($_GET['success-mercadopago'])) {
        echo '<h1>Pago Exitoso</h1><br />';
        dump_get();
    } elseif (isset($_GET['failure-mercadopago'])) {
        echo '<h1>Pago Fallido</h1><br />';
        dump_get();
    } elseif (isset($_GET['pending-mercadopago'])) {
        echo '<h1>Su pago quedo en espera</h1><br />';
        dump_get();
    } else {
        // Crea un objeto de preferencia
        $preference = new MercadoPago\Preference();

        // Crea un ítem en la preferencia
        $item = new MercadoPago\Item();
        $item->title = 'Nombre del producto seleccionado del carrito del ejercicio';
        $item->description = 'Dispositivo móvil de Tienda e-commerce';
        $item->currency_id = 'ARS';
        $item->picture_url = 'https://kijam.com/tienda/wp-content/uploads/2020/09/cropped-Imagotipo-1-1.png';
        $item->quantity = 1;
        $item->unit_price = 150;
        $preference->items = array($item);
        $payer = new MercadoPago\Payer();
        $payer->name = 'Lalo';
        $payer->surname = 'Landa';
        $payer->email = 'test_user_63274575@testuser.com';
        $payer->phone = array('area_code' => '11', 'number' => '22223333');
        $payer->address = array('street_name' => 'Falsa', 'street_number' => 123, 'zip_code' => '1111');
        $preference->payer = $payer;
        $preference->notification_url = url_origin()."?ipn-mercadopago";
        $preference->external_reference = "info_ar@kijam.com";
        $preference->auto_return = "approved";
        $preference->back_urls = array(
            "success" => url_origin()."?success-mercadopago",
            "failure" => url_origin()."?failure-mercadopago",
            "pending" => url_origin()."?pending-mercadopago"
        );
        $preference->payment_methods = array(
            "excluded_payment_methods" =>array(
                array(
                    "id" => "amex"
                )
            ),
            "excluded_payment_types" =>array(
                array(
                    "id" => "atm"
                )
            ),
            "installments" => 6
        );
        $preference->save();
        ?>
        <script
          src="https://www.mercadopago.com.ar/integrations/v1/web-payment-checkout.js"
          data-preference-id="<?php echo $preference->id; ?>">
        </script>
        
        <h1>Ultimos 10 webhooks recibidos de MercadoPago:</h1>
        <pre><?php
        try {
            $statement = $mbd->prepare("SELECT * FROM mercadopago_ipn ORDER BY created_at DESC LIMIT 10");
            $statement->execute([]);
            // fetch all rows
            $all = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach($all as $row) {
                echo $row['created_at'].': '.$row['payload']."\n\n";
            }
        } catch (Exception $e) {
            $mbd->query("CREATE TABLE `mercadopago_ipn` (
                          `id` int(11) NOT NULL,
                          `payload` longtext NOT NULL,
                          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }
        ?>
        </pre>
    <?php
    }
    ?>
    </body>
</html>