<?php
if (!defined('ABSPATH')) exit;

class MimerPhoneValidatorAdmin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Forms VDI', // Nuevo título de la página
            'Forms VDI', // Nuevo título del menú
            'manage_options',
            'mimer-phone-validator',
            array($this, 'settings_page_html'),
            'dashicons-phone',
            80
        );
    }

    public function register_settings() {
        register_setting('mimer_phone_validator_group', 'mimer_phone_validator_api_key');
        register_setting('mimer_phone_validator_group', 'mimer_phone_validator_enabled');
    }

    public function settings_page_html() {
        // Determinar la pestaña activa
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'phone_validator';
        ?>
        <div class="wrap">
            <h1>Forms VDI</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=mimer-phone-validator&tab=phone_validator" class="nav-tab <?php echo $active_tab == 'phone_validator' ? 'nav-tab-active' : ''; ?>">Phone Validator</a>
                <a href="?page=mimer-phone-validator&tab=api" class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">API</a>
            </h2>
            <?php if ($active_tab == 'phone_validator'): ?>
                <form method="post">
                    <?php
                    if (isset($_POST['test_api_key'])) {
                        $test_key = get_option('mimer_phone_validator_api_key');
                        $test_response = wp_remote_get("http://apilayer.net/api/validate?access_key={$test_key}&number=12025550123&country_code=US&format=1");
                        if (!is_wp_error($test_response)) {
                            $body = json_decode(wp_remote_retrieve_body($test_response), true);
                            if (isset($body['valid'])) {
                                if ($body['valid']) {
                                    echo '<p style="color: green;">✔ API Key válida y funcional.</p>';
                                } else {
                                    echo '<p style="color: orange;">✖ API Key válida pero sin crédito o sin acceso completo.</p>';
                                }
                            } else {
                                echo '<p style="color: red;">✖ Respuesta inválida. Verifica tu API Key.</p>';
                            }
                        } else {
                            echo '<p style="color: red;">✖ Error al conectar con la API de Numverify.</p>';
                        }
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        update_option('mimer_phone_validator_api_key', sanitize_text_field($_POST['mimer_phone_validator_api_key']));
                        update_option('mimer_phone_validator_enabled', isset($_POST['mimer_phone_validator_enabled']) ? 1 : 0);
                        echo '<div class="updated"><p>Opciones guardadas.</p></div>';
                    }
                    settings_fields('mimer_phone_validator_group');
                    do_settings_sections('mimer_phone_validator_group');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">API Key de Numverify</th>
                            <td>
                                <input type="text" name="mimer_phone_validator_api_key"
                                    value="<?php echo esc_attr(get_option('mimer_phone_validator_api_key')); ?>"
                                    class="regular-text" />
                                <br/><br/>
                                <input type="submit" name="test_api_key" class="button button-secondary" value="Verificar API Key" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Activar validación</th>
                            <td>
                                <input type="checkbox" name="mimer_phone_validator_enabled" value="1" <?php checked(1, get_option('mimer_phone_validator_enabled'), true); ?> />
                                <label for="mimer_phone_validator_enabled">Activar o desactivar la validación del teléfono</label>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            <?php elseif ($active_tab == 'api'): ?>
                <p>Próximamente: configuración de la API.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}

new MimerPhoneValidatorAdmin();