<?php
/**
 * WP Bootstrap Navwalker
 *
 * @package WP-Bootstrap-Navwalker
 *
 * @wordpress-plugin
 * Plugin Name: WP Bootstrap Navwalker
 * Plugin URI:  https://github.com/wp-bootstrap/wp-bootstrap-navwalker
 * Description: A custom WordPress nav walker class to implement the Bootstrap 4 navigation style in a custom theme using the WordPress built in menu manager.
 * Author: Edward McIntyre - @twittem, WP Bootstrap, William Patton - @pattonwebz
 * Version: 5.0.0
 * Author URI: https://github.com/wp-bootstrap
 * GitHub Plugin URI: https://github.com/wp-bootstrap/wp-bootstrap-navwalker
 * GitHub Branch: master
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! class_exists( 'WP_Bootstrap_Navwalker' ) ) {
	/**
	 * WP_Bootstrap_Navwalker class.
	 *
	 * @extends Walker_Nav_Menu
	 */
	class WP_Bootstrap_Navwalker extends Walker_Nav_Menu {

		/**
		 * Add filters.
		 *
		 * @since 5.0.0
		 */
		public function __construct() {
			add_filter( 'nav_menu_css_class', array( $this, 'nav_menu_css_class' ), 10, 4 );
			add_filter( 'nav_menu_item_id', array( $this, 'unset_menu_item_id' ), 10, 1 );
			add_filter( 'nav_menu_item_title', array( $this, 'setup_nav_menu_item_title' ), 10, 1 );
			add_filter( 'nav_menu_link_attributes', array( $this, 'setup_nav_menu_link_attributes' ), 10, 4 );
			add_filter( 'nav_menu_submenu_css_class', array( $this, 'set_nav_menu_submenu_css_class' ), 10, 1 );
			add_filter( 'walker_nav_menu_start_el', array( $this, 'remove_anchor' ), 10, 2 );
			add_filter( 'wp_nav_menu_items', array( $this, 'unwrap_dropdown_items' ), 10, 1 );
			add_filter( 'wp_nav_menu_items', array( $this, 'set_nav_menu_item_attributes' ), 10, 2 );
		}

		/**
		 * Replace <a> tag with <span> or <div> tag for dropdown headers, dividers,
		 * text only and the current menu item.
		 *
		 * @since 5.0.0
		 *
		 * @param string  $item_output The menu item's starting HTML output.
		 * @param WP_Post $item        The current menu item.
		 * @return string
		 */
		public function remove_anchor( $item_output, $item ) {
			if ( ! $this->dropdown_mod && ! $item->current ) {
				return $item_output;
			}
			if ( $this->dropdown_item_text || $this->dropdown_header || $item->current ) {
				$replace_pairs = array(
					'<a '  => '<span ',
					'</a>' => '</span>',
				);
			} else {
				// The dropdown-divider.
				$replace_pairs = array(
					'<a '  => '<div ',
					'</a>' => '</div>',
				);
			}
			return strtr( $item_output, $replace_pairs );
		}

		/**
		 * Add Bootstrap CSS classes to nav menu items.
		 *
		 * @since 5.0.0
		 *
		 * @param string[] $classes Array of the CSS classes that are applied to the menu item's <li> element.
		 * @param WP_Post  $item    The current menu item.
		 * @param object   $args    An object of wp_nav_menu() arguments.
		 * @param int      $depth   Depth of menu item.
		 * @return string[]
		 */
		public function nav_menu_css_class( $classes, $item, $args, $depth ) {
			$classes[] = 'nav-item';
			if ( $this->has_children ) {
				$classes[] = 'dropdown';
			}
			if ( $item->current && 0 === $depth ) {
				$classes[] = 'active';
			}
			if ( $depth > 0 ) {
				$classes = array();
			}
			return $classes;
		}

		/**
		 * Add attributes to nav items.
		 *
		 * @since 5.0.0
		 *
		 * @param string $items The HTML list content for the menu items.
		 * @return string
		 */
		public function unwrap_dropdown_items( $items ) {
			if ( strpos( $items, 'dropdown-item' ) ) {
				$replace_pairs = array(
					'<li>'  => '',
					'</li>' => '',
				);
				return strtr( $items, $replace_pairs );
			}
			return $items;
		}

		/**
		 * Set attributes for the menu item's <li> element.
		 *
		 * @param string $items The HTML list content for the menu items.
		 * @param object $args  An object containing wp_nav_menu() arguments.
		 * @return string
		 */
		public function set_nav_menu_item_attributes( $items, $args ) {
			if ( property_exists( $args, 'schema_markup' ) && true === $args->schema_markup ) {
				$scope = ' itemscope="itemscope" ';
				$type  = 'itemtype="https://www.schema.org/SiteNavigationElement"';
				return str_replace( '<li', '<li' . $scope . $type, $items );
			}
			return $items;
		}

		/**
		 * Unset the menu item's id.
		 *
		 * @param string $id The ID that is applied to the menu item's <li> element.
		 * @return string Empty string.
		 */
		public function unset_menu_item_id( $id ) {
			return '';
		}

		/**
		 * Add Bootstrap markup to nav links.
		 *
		 * @since 5.0.0
		 *
		 * @param array   $atts {
		 * The HTML attributes applied to the menu item's <a> element, empty strings are ignored.
		 *
		 *  @type string $title        The title attribute.
		 *  @type string $target       The target attribute.
		 *  @type string $rel          The rel attribute.
		 *  @type string $href         The href attribute.
		 *  @type string $aria_current The aria-current attribute.
		 * }
		 * @param WP_Post $item  The current menu item.
		 * @param object  $args  An object of wp_nav_menu() arguments.
		 * @param int     $depth Depth of menu item.
		 * @return array
		 */
		public function setup_nav_menu_link_attributes( $atts, $item, $args, $depth ) {
			$atts['id']    = 'menu-item-' . $item->ID;
			$atts['class'] = implode( ' ', $item->classes );

			if ( isset( $this->has_children ) && $this->has_children && 0 === $depth ) {
				$atts['href']          = '#';
				$atts['data-toggle']   = 'dropdown';
				$atts['aria-haspopup'] = 'true';
				$atts['aria-expanded'] = 'false';
				$atts['class']         = $atts['class'] . ' nav-link dropdown-toggle';
				$atts['id']            = 'menu-item-dropdown-' . $item->ID;
			} else {
				if ( $depth > 0 ) {
					$classes       = $item->current ? 'dropdown-item active' : 'dropdown-item';
					$atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' ' . $classes : $classes;
				} else {
					$atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' nav-link' : 'nav-link';
				}
			}

			if ( $this->linkmod_classes ) {
				// Check for special class types we need additional handling for.
				if ( $this->disabled ) {
					// Convert link to '#' and unset open targets.
					unset( $atts['target'] );
					$atts['href']          = '#';
					$atts['tabindex']      = -1;
					$atts['aria-disabled'] = true;
					$atts['class']         = isset( $atts['class'] ) ? $atts['class'] . ' disabled' : 'disabled';
				} elseif ( $this->dropdown_header || $this->dropdown_divider || $this->dropdown_item_text ) {
					unset( $atts['href'] );
					unset( $atts['target'] );
					$atts['class'] = 'dropdown-divider';
					if ( $this->dropdown_item_text ) {
						$atts['class'] = 'dropdown-item-text';
					} elseif ( $this->dropdown_header ) {
						// Use .h6 class instead of <h6> tag so to not confuse screen readers.
						$atts['class'] = 'dropdown-header h6';
					}
				}
			}

			if ( $this->add_schema ) {
				if ( $depth > 0 ) {
					$atts['itemscope'] = 'itemscope';
					$atts['itemtype']  = 'https://www.schema.org/SiteNavigationElement';
				}
				$atts['itemprop'] = 'url';
			}

			return $atts;
		}

		/**
		 * Add Bootstrap CSS class to submenu <ul>.
		 *
		 * @since 5.0.0
		 *
		 * @param string[] $classes Array of the CSS classes that are applied to the menu <ul> element.
		 * @return string[]
		 */
		public function set_nav_menu_submenu_css_class( $classes ) {
			$classes[] = 'dropdown-menu';
			return $classes;
		}

		/**
		 * Starts the list before the elements are added.
		 *
		 * @see Walker::start_lvl()
		 *
		 * @param string $output Used to append additional content (passed by reference).
		 * @param int    $depth  Depth of menu item. Used for padding.
		 * @param object $args   An object of wp_nav_menu() arguments.
		 */
		public function start_lvl( &$output, $depth = 0, $args = null ) {

			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
					$t = '';
					$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = str_repeat( $t, $depth );

			// Default class.
			$classes = array( 'sub-menu' );

			/**
			 * Filters the CSS class(es) applied to a menu list element.
			 *
			 * @since WP 4.8.0
			 *
			 * @param string[] $classes Array of the CSS classes that are applied to the menu `<ul>` element.
			 * @param object $args    An object of `wp_nav_menu()` arguments.
			 * @param int      $depth   Depth of menu item. Used for padding.
			 */
			$class_names = implode( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			/*
			 * The `.dropdown-menu` container needs to have a labelledby
			 * attribute which points to it's trigger link.
			 */
			$labelledby = 'aria-labelledby="menu-item-dropdown-' . $this->current_item_id . '"';

			$output .= "{$n}{$indent}<div$class_names $labelledby>{$n}";
		}

		/**
		 * Ends the list of after the elements are added.
		 *
		 * @since WP 3.0.0
		 *
		 * @see Walker::end_lvl()
		 *
		 * @param string   $output Used to append additional content (passed by reference).
		 * @param int      $depth  Depth of menu item. Used for padding.
		 * @param stdClass $args   An object of wp_nav_menu() arguments.
		 */
		public function end_lvl( &$output, $depth = 0, $args = null ) {
			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
					$t = '';
					$n = '';
			} else {
					$t = "\t";
					$n = "\n";
			}
			$indent  = str_repeat( $t, $depth );
			$output .= "$indent</div>{$n}";
		}

		/**
		 * Starts the element output.
		 *
		 * @see Walker::start_el()
		 *
		 * @param string  $output Used to append additional content (passed by reference).
		 * @param WP_Post $item   The current menu item.
		 * @param int     $depth  Depth of menu item.
		 * @param object  $args   An object of wp_nav_menu() arguments.
		 * @param int     $id     Current item ID.
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$this->current_item_id = $item->ID;

			$setup = self::setup_classes( $item, $args, $depth );

			return parent::start_el( $output, $item, $depth, $args, $id );
		}

		/**
		 * Menu fallback.
		 *
		 * If this function is assigned to the wp_nav_menu's fallback_cb variable
		 * and a menu has not been assigned to the theme location in the WordPress
		 * menu manager the function will display nothing to a non-logged in user,
		 * and will add a link to the WordPress menu manager if logged in as an admin.
		 *
		 * @param array $args passed from the wp_nav_menu function.
		 * @return string|void String when echo is false.
		 */
		public static function fallback( $args ) {
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				return;
			}

			// Initialize var to store fallback html.
			$fallback_output = '';

			// Menu container opening tag.
			$show_container = false;
			if ( $args['container'] ) {
				/**
				 * Filters the list of HTML tags that are valid for use as menu containers.
				 *
				 * @since WP 3.0.0
				 *
				 * @param array $tags The acceptable HTML tags for use as menu containers.
				 *                    Default is array containing 'div' and 'nav'.
				 */
				$allowed_tags = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav' ) );
				if ( is_string( $args['container'] ) && in_array( $args['container'], $allowed_tags, true ) ) {
					$show_container   = true;
					$class            = $args['container_class'] ? ' class="menu-fallback-container ' . esc_attr( $args['container_class'] ) . '"' : ' class="menu-fallback-container"';
					$id               = $args['container_id'] ? ' id="' . esc_attr( $args['container_id'] ) . '"' : '';
					$fallback_output .= '<' . $args['container'] . $id . $class . '>';
				}
			}

			// The fallback menu.
			$class            = $args['menu_class'] ? ' class="menu-fallback-menu ' . esc_attr( $args['menu_class'] ) . '"' : ' class="menu-fallback-menu"';
			$id               = $args['menu_id'] ? ' id="' . esc_attr( $args['menu_id'] ) . '"' : '';
			$fallback_output .= '<ul' . $id . $class . '>';
			$fallback_output .= '<li class="nav-item"><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" class="nav-link" title="' . esc_attr__( 'Add a menu', 'wp-bootstrap-navwalker' ) . '">' . esc_html__( 'Add a menu', 'wp-bootstrap-navwalker' ) . '</a></li>';
			$fallback_output .= '</ul>';

			// Menu container closing tag.
			if ( $show_container ) {
				$fallback_output .= '</' . $args['container'] . '>';
			}

			// if $args has 'echo' key and it's true echo, otherwise return.
			if ( array_key_exists( 'echo', $args ) && $args['echo'] ) {
				echo $fallback_output; // WPCS: XSS OK.
			} else {
				return $fallback_output;
			}
		}

		/**
		 * Setup classes properties and remove special classes from the item's classes property.
		 *
		 * Supported linkmods: .disabled, .dropdown-header, .dropdown-divider, .sr-only
		 * Supported iconsets: Font Awesome 4/5, Glypicons
		 *
		 * @since 5.0.0
		 *
		 * @param WP_Post $item  The current menu item.
		 * @param object  $args  An object of wp_nav_menu() arguments.
		 * @param int     $depth Depth of menu item.
		 *
		 * @return bool Whether the properties have been setup.
		 */
		private function setup_classes( $item, $args, $depth ) {
			if ( ! isset( $item->classes ) ) {
				return false;
			}

			$classes         = $item->classes;
			$linkmod_classes = array();
			$icon_classes    = array();

			// Initialize properties.
			$this->linkmod_classes    = false;
			$this->icon_classes       = false;
			$this->rest_classes       = false;
			$this->sr_only            = false;
			$this->disabled           = false;
			$this->dropdown_header    = false;
			$this->dropdown_divider   = false;
			$this->dropdown_item_text = false;
			$this->dropdown_mod       = false;
			$this->icon_classes       = array();
			$this->add_schema         = false;

			// Loop through $classes array to find linkmod or icon classes.
			foreach ( $classes as $key => $class ) {
				// If any special class is found, unset it from $classes.
				if ( 'disabled' === $class || 'sr-only' === $class ) {
					// Test for .disabled and .sr-only.
					unset( $item->classes[ $key ] );
					$this->sr_only         = 'sr-only' === $class ? true : false;
					$this->disabled        = 'disabled' === $class ? true : false;
					$this->linkmod_classes = true;
				} elseif ( ( 'dropdown-header' === $class || 'dropdown-divider' === $class || 'dropdown-item-text' === $class ) && $depth > 0 ) {
					/*
					 * Test for .dropdown-header or .dropdown-divider and a
					 * depth greater than 0 - IE inside a dropdown.
					 */
					unset( $item->classes[ $key ] );
					$this->dropdown_header    = 'dropdown-header' === $class ? true : false;
					$this->dropdown_divider   = 'dropdown-divider' === $class ? true : false;
					$this->dropdown_item_text = 'dropdown-item-text' === $class ? true : false;
					$this->linkmod_classes    = true;
					$this->dropdown_mod       = true;
				} elseif ( preg_match( '/^fa-(\S*)?|^fa(s|r|l|b)?(\s?)?$/i', $class ) ) {
					// Test for Fontawesome.
					unset( $item->classes[ $key ] );
					$this->icon_classes[] = $class;
				} elseif ( preg_match( '/^glyphicon-(\S*)?|^glyphicon(\s?)$/i', $class ) ) {
					// Test for Glyphicons.
					unset( $item->classes[ $key ] );
					$this->icon_classes[] = $class;
				}
			}

			if ( property_exists( $args, 'schema_markup' ) && true === $args->schema_markup && ! $this->dropdown_mod && ! $this->disabled ) {
				$this->add_schema = true;
			}

			return true;
		}

		/**
		 * Wraps the title in a <span> tag with corresponding classes and prepends icon.
		 *
		 * @since 5.0.0
		 *
		 * @param string $title The menu item's title.
		 * @return string
		 */
		public function setup_nav_menu_item_title( $title = '' ) {
			if ( $title ) {
				if ( $this->dropdown_divider ) {
					return '';
				}
				if ( ! $this->sr_only ) {
					$title = '<span class="menu-item-title">' . $title . '</span>';
				} else {
					$title = '<span class="menu-item-title sr-only">' . $title . '</span>';
				}
			}
			if ( ! empty( $this->icon_classes ) ) {
				$icon_html = '<i class="' . esc_attr( implode( ' ', $this->icon_classes ) ) . '" aria-hidden="true"></i> ';
				return $icon_html . $title;
			}
			return $title;
		}

		/**
		 * Flattens a multidimensional array to a simple array.
		 *
		 * @param array $array a multidimensional array.
		 *
		 * @return array a simple array
		 */
		public function flatten( $array ) {
			$result = array();
			foreach ( $array as $element ) {
				if ( is_array( $element ) ) {
					array_push( $result, ...$this->flatten( $element ) );
				} else {
					$result[] = $element;
				}
			}
			return $result;
		}

	}
}
