<?php
/**
 * Select2 Handler for Enhanced Form Selects
 * Integra Select2 en formularios de Elementor cuando está activado
 */

if (!defined('ABSPATH')) exit;

class MimerSelect2Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Solo cargar si Select2 está habilitado
        if (get_option('mimer_select2_enabled', 0)) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_select2_assets'));
            add_action('wp_footer', array($this, 'initialize_select2'));
        }
    }
    
    /**
     * Cargar assets de Select2
     */
    public function enqueue_select2_assets() {
        // Solo cargar en páginas que puedan contener formularios
        if (!is_page() && !is_single() && !is_front_page()) {
            return;
        }
        
        // Select2 CSS
        wp_enqueue_style(
            'mimer-select2-css', 
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            array(),
            '4.1.0-rc.0'
        );
        
        // Select2 JS
        wp_enqueue_script(
            'mimer-select2-js',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            '4.1.0-rc.0',
            true
        );
        
        // CSS personalizado
        wp_add_inline_style('mimer-select2-css', $this->get_custom_css());
    }
    
    /**
     * Inicializar Select2 en formularios
     */
    public function initialize_select2() {
        if (!get_option('mimer_select2_enabled', 0)) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Función para inicializar Select2
            function initializeSelect2() {
                // Buscar todos los selects en formularios de Elementor
                $('.elementor-form select').each(function() {
                    var $select = $(this);
                    
                    // No aplicar si ya tiene Select2
                    if ($select.hasClass('select2-hidden-accessible')) {
                        return;
                    }
                    
                    // Contar opciones para determinar si mostrar búsqueda
                    var optionCount = $select.find('option').length;
                    var searchThreshold = 5;
                    
                    // Configuración de Select2
                    var select2Config = {
                        width: '100%',
                        minimumResultsForSearch: optionCount >= searchThreshold ? 0 : Infinity,
                        placeholder: $select.attr('data-placeholder') || 'Select an option...',
                        allowClear: false,
                        escapeMarkup: function(markup) {
                            return markup;
                        }
                    };
                    
                    // Aplicar Select2
                    $select.select2(select2Config);
                });
            }
            
            // Inicializar inmediatamente
            initializeSelect2();
            
            // Re-inicializar cuando Elementor carga contenido dinámicamente
            $(document).on('elementor/popup/show', function() {
                setTimeout(initializeSelect2, 100);
            });
            
            // Re-inicializar en formularios multi-step
            $(document).on('click', '.e-form__buttons__wrapper__button-next, .e-form__buttons__wrapper__button-previous', function() {
                setTimeout(initializeSelect2, 300);
            });
            
            // Debug log
            console.log('🎨 Mimer Select2 initialized for Elementor forms');
        });
        </script>
        <?php
    }
    
    /**
     * CSS personalizado para Select2
     */
    private function get_custom_css() {
        return '
        /* Select2 Container */
        .select2-container {
            display: block !important;
            width: 100% !important;
        }
        
        /* Select2 Selection Box */
        .select2-container .select2-selection--single {
            height: 44px !important;
            border: 1px solid #e1e5e9 !important;
            border-radius: 6px !important;
            background: #ffffff !important;
            box-shadow: none !important;
            transition: all 0.3s ease !important;
        }
        
        /* Selection Text */
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 12px !important;
            padding-right: 30px !important;
            color: #333333 !important;
            font-size: 14px !important;
            font-weight: 400 !important;
        }
        
        /* Arrow */
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 8px !important;
            width: 20px !important;
        }
        
        .select2-container .select2-selection--single .select2-selection__arrow b {
            border-color: #666 transparent transparent transparent !important;
            border-style: solid !important;
            border-width: 5px 4px 0 4px !important;
            height: 0 !important;
            left: 50% !important;
            margin-left: -4px !important;
            margin-top: -2px !important;
            position: absolute !important;
            top: 50% !important;
            width: 0 !important;
        }
        
        /* Hover State */
        .select2-container .select2-selection--single:hover {
            border-color: #007cba !important;
        }
        
        /* Focus State */
        .select2-container--open .select2-selection--single {
            border-color: #007cba !important;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2) !important;
        }
        
        .select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #666 transparent !important;
            border-width: 0 4px 5px 4px !important;
        }
        
        /* Dropdown */
        .select2-dropdown {
            border: 1px solid #e1e5e9 !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            background: white !important;
        }
        
        /* Search Box */
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #e1e5e9 !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }
        
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #007cba !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2) !important;
        }
        
        /* Options */
        .select2-results__option {
            padding: 12px 16px !important;
            font-size: 14px !important;
            color: #333 !important;
            cursor: pointer !important;
            transition: background-color 0.2s ease !important;
        }
        
        .select2-results__option:hover,
        .select2-results__option--highlighted {
            background-color: #007cba !important;
            color: white !important;
        }
        
        .select2-results__option[aria-selected="true"] {
            background-color: #f8f9fa !important;
            color: #333 !important;
            font-weight: 500 !important;
        }
        
        .select2-results__option[aria-selected="true"]:hover {
            background-color: #007cba !important;
            color: white !important;
        }
        
        /* No Results */
        .select2-results__option--no-results {
            color: #666 !important;
            font-style: italic !important;
        }
        
        /* Disabled State */
        .select2-container--disabled .select2-selection--single {
            background-color: #f8f9fa !important;
            color: #999 !important;
            cursor: not-allowed !important;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .select2-container .select2-selection--single {
                height: 48px !important;
                font-size: 16px !important;
            }
            
            .select2-container .select2-selection--single .select2-selection__rendered {
                line-height: 46px !important;
                font-size: 16px !important;
            }
            
            .select2-container .select2-selection--single .select2-selection__arrow {
                height: 46px !important;
            }
            
            .select2-results__option {
                padding: 14px 16px !important;
                font-size: 16px !important;
            }
        }
        
        /* Integration with Elementor Forms */
        .elementor-form .elementor-field-group .select2-container {
            margin: 0 !important;
        }
        
        .elementor-form .elementor-field-group .select2-container .select2-selection--single {
            border-color: var(--e-form-fields-border-color, #e1e5e9) !important;
            border-radius: var(--e-form-fields-border-radius, 6px) !important;
        }
        
        .elementor-form .elementor-field-group .select2-container .select2-selection--single:hover,
        .elementor-form .elementor-field-group .select2-container--open .select2-selection--single {
            border-color: var(--e-form-fields-focus-border-color, #007cba) !important;
        }
        ';
    }
}

// Inicializar el handler
MimerSelect2Handler::get_instance();
?>
