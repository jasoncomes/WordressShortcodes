<?php 

namespace WordpressShortcodes;


/**
 * Shortcodes
 *
 * @since      1.0.1
 */

class Shortcodes
{

    /**
     * Theme Directory Path
     *
     * @access   private
     * @var      string $theme_directory
     */
    private $theme_directory;


    /**
     * Shortcode Folder in Theme
     *
     * @access   private
     * @var      string $shortcode_folder
     */
    private $shortcode_folder = 'shortcode-templates';


    /**
     * Shortcode Directory Path
     *
     * @access   private
     * @var      string $shorcode_directory
     */
    private $shorcode_directory;


    /**
     * Shortcode JSON Path
     *
     * @access   private
     * @var      string $shortcode_json
     */
    private $shortcode_json;


    /**
     * Shortcode Meta Information
     *
     * @access   private
     * @var      array $shortcode_meta
     */
    private $shortcode_meta = [
        'text'         => 'Title',
        'shortcode'    => 'Shortcode',
        'html'         => 'HTML',
        'styleguide'   => 'Styleguide',
        'styleguide_2' => 'Styleguide_2',
        'styleguide_3' => 'Styleguide_3',
        'styleguide_4' => 'Styleguide_4',
        'styleguide_5' => 'Styleguide_5',
        'instructions' => 'Instructions'
    ];


    /**
     * Initiate
     *
     */
    public function init()
    {
        $this->set_defaults();
        $this->wpex_fix_shortcodes();
        $this->overwrite_styleguide_page();

        if (WP_ENV === 'local') {
            $this->local_setup();
        } elseif (file_exists($this->shortcode_json)) {
            $this->live_setup();
        }
    }


    /**
     * Buildout Shortcodes Files
     *
     */
    public function local_setup() 
    {
        $this->create_shortcode_theme_dir();
        $this->parse_shortcode_templates();
        $this->live_setup();
    }


    /**
     * Setup Shortcodes
     *
     */
    public function live_setup() 
    {
        if (file_exists($this->shortcode_json)) {
            $this->register_json_shortcodes();
            $this->add_tinymce_button();
            $this->add_shortcode_tinymce_js();
        }
    }


    /**
     * Set Defaults
     *
     */
    public function set_defaults() 
    {
        $this->theme_directory    = self::get_relative_directory(get_template_directory());
        $this->shorcode_directory = WP_CONTENT_DIR . $this->theme_directory . '/' . $this->shortcode_folder;
        $this->shortcode_json     = getenv('DOCUMENT_ROOT') . '/shortcodes.json';
    }


    /**
     * Get Relative Directory
     *
     */
    static public function get_relative_directory($path) 
    {
        $theme = explode('wp-content', $path);
        return $theme[1];
    }


    /**
     * Create Shortcodes Template Directory in WP Theme
     *
     */
    public function create_shortcode_theme_dir()
    {
        if (!file_exists($this->shorcode_directory)) {
            mkdir($this->shorcode_directory, 0777, true);
        }
    }


    /**
     * Parse Templates Directory for Shortcodes & HTML Snippets
     *
     */
    public function parse_shortcode_templates()
    {
        $files = glob($this->shorcode_directory . '/*');

        if (count($files !== 0)) {

            add_action('init', function() use ($files)
            {
                $shortcodes = [];

                foreach ($files as $file) {
                    if (is_dir($file)) {
                        $shortcode_children = [];
                        $children_files     = glob($file . '/*');

                        if (count($children_files !== 0)) {
                            foreach ($children_files as $child_file) {
                                $shortcode_children = self::create_shortcodes_json_array_element($child_file, $shortcode_children);
                            }
                        }
                        $shortcodes[] = array('text' => ucfirst(basename($file)), 'menu' => $shortcode_children);
                    } else {
                        $shortcodes = self::create_shortcodes_json_array_element($file, $shortcodes);
                    }
                }
                $this->create_shortcodes_json($shortcodes);
            });
        }
    }


    /**
     * Create Shortcode Array Element for JSON File
     *
     * @param string $file
     * @param string $shortcodes
     *
     * @return array
     */
    public function create_shortcodes_json_array_element($file, $shortcodes)
    {
        $header_meta         = get_file_data($file, $this->shortcode_meta);
        $header_meta['file'] = self::get_relative_directory($file);

        // Shortcode || HTML
        if (!empty($header_meta['shortcode'])) {
            $header_meta['value'] =  $header_meta['shortcode'];
        } else {
            $header_meta['value'] = $header_meta['html']; // Inserts HTML
        }

        // Public || Developer Use
        if (!Pub\PublicHelpers::starts_with(basename($file), '_'))
            {
                $header_meta['public'] = 1;
            }
            else
            {
                $header_meta['public'] = 0;
            }

            $shortcodes[] = $header_meta;
            return $shortcodes;
        }


    /**
     * Create JSON Shortcode File
     *
     * @param string $shortcodes
     */
    public function create_shortcodes_json($shortcodes)
    {
      $fp = fopen($this->shortcode_json , 'w');
      fwrite($fp, json_encode($shortcodes));
      fclose($fp);
  }


    /**
     * Register JSON Shortcodes
     *
     */
    public function register_json_shortcodes()
    {
      $shortcode_file = file_get_contents($this->shortcode_json);
      $shortcode_json = json_decode($shortcode_file, TRUE);

      foreach ($shortcode_json as $shortcode)
      {
        if (!empty($shortcode['menu'])) // Children Shortcodes
        {
          foreach ($shortcode['menu'] as $shortcode_child)
          {
            if (!empty($shortcode_child['shortcode']))
            {
              self::register_shortcodes($shortcode_child['file'], $shortcode_child['shortcode']);
          }
      }
  }
        else // Parent Shortcodes
        {
          if (!empty($shortcode['shortcode']))
          {
            self::register_shortcodes($shortcode['file'], $shortcode['shortcode']);
        }
    }
}
}


    /**
     * Register Shortcode
     *
     * @param string $file
     * @param string $shortcode
     */
    static public function register_shortcodes($file, $shortcode)
    {
        add_shortcode(self::get_shortcode_slug($shortcode), function($atts, $content, $tag) use ($file)
        {
            if (!empty($atts)) {
                extract(self::parse_atts($atts));  
            } 
            
            ob_start();
            include WP_CONTENT_DIR . $file;
            return ob_get_clean();
        });
    }


    /**
     * Get Shortcode Slug to Register
     *
     * @param string $shortcode
     *
     * @return string
     */
    static public function get_shortcode_slug($shortcode)
    {
        preg_match('/\[(\w*)/', $shortcode, $match);
        return $match[1];
    }


    /**
     * Parse Attributes
     *
     * @param array $atts
     *
     * @return array
     */
    static public function parse_atts($atts)
    {
        foreach ($atts as $key => $value) {
            $atts[ $key ] = self::covert_tags_to_urls($value);
        }

        return $atts;
    }


    /**
     * Finds <img src="###">, <a href="###"">
     *
     * @param string $string
     *
     * @return string
     */
    static public function covert_tags_to_urls($string)
    {
        if (preg_match('/^\s*\<a.*\<\/a\>\s*$/', $string) && preg_match('/href\=\"(.*?)\"/', $string, $key)) {
            return self::set_absolute_url($key[1]);
        } elseif (preg_match('/^\s*\<img.*(\/\>|\>)\s*$/', $string) && preg_match('/src\=\"(.*?)\"/', $string, $key)) {
            return self::set_absolute_url($key[1]);
        } elseif (!empty($string)) {
            return self::set_absolute_url($string);
        }
    }


    /**
     * Set Absolute URLS
     *
     * @param string $string
     *
     * @return string
     */
    static public function set_absolute_url($string)
    {
        if (preg_match('/^www./', $string, $key)) {
            return 'http://' . $string;
        } elseif (preg_match('/^\/?wp-content\/(.*?)$/', $string, $key)) {
            return WWW_URL . '/wp-content/' . $key[1];
        } elseif (!empty($string)) {
            return $string;
        }
    }


    /**
     * Add Button to WP Wysiwyg
     *
     */
    public function add_tinymce_button()
    {
        add_filter('mce_buttons', function($buttons)
        {
            array_unshift($buttons, 'he_shortcode_button');
            
            return $buttons;
        });
    }


    /**
     * Button WP Functionality
     *
     */
    public function add_shortcode_tinymce_js()
    {
        add_filter('mce_external_plugins', function($plugin_array)
        {
            $plugin_array['he_shortcode_button'] = plugin_dir_url(basename(__FILE__)) .  'he-wordpress-plugin/admin/js/shortcodes.js';

            return $plugin_array;
        });
    }


    /**
     * Content Shortcodes to Array
     *
     * @param string $content
     *
     * @return array
     */
    static public function content_shortcodes_to_array($content)
    {
      $shortcodes = [];

      foreach (self::get_children_shortcodes_array($content) as $child_shortcode) {
            // Set Variables
            $shortcode = self::get_shortcode_attributes_array($child_shortcode);

            // Set Content
            $shortcode['content'] = self::get_shortcode_content($child_shortcode);

            // Set Active State
            if (!empty($shortcode['active']) && ($shortcode['active'] == 'true' || $shortcode['active'] == 1)) {
                $shortcode['active'] = 1;
            } else {
                $shortcode['active'] = '';
            }

            // Set Key as ID
            if (!empty($shortcode['post_id'])) {
                $shortcodes[ $shortcode['post_id'] ] = array_filter($shortcode);
            } else {
                $shortcodes[] = array_filter($shortcode);
            }
        }

        return $shortcodes;
    }


    /**
     * Get Children Shortode Array from Parent Shortcode
     *
     * @param string $parent_shortcode
     *
     * @return array
     */
    static public function get_children_shortcodes_array($parent_shortcode)
    {
        $shortcode_slug = self::get_shortcode_slug($parent_shortcode);
        preg_match_all('/\\[' . $shortcode_slug . '(.*?)\\[\/' . $shortcode_slug . ']/s', $parent_shortcode, $content);

        return $content[0];
    }


    /**
     * Get Attributes into Array
     *
     * @param string $shortcode
     *
     * @return array
     */
    static public function get_shortcode_attributes_array($shortcode)
    {
        $shortcode    = explode(']', $shortcode);
        $array_single = [];
        $array_double = [];

        // Single Quote Surroundings
        preg_match_all('/\s(\w*?)\=\'(.*?)\'/', $shortcode[0], $content_single);

        if (!empty($content_single)) {
            $array_single = array_combine($content_single[1], $content_single[2]);
        }

        // Double Quote Surroundings
        preg_match_all('/\s(\w*?)\=\"(.*?)\"/', $shortcode[0], $content_double);

        if (!empty($content_double)) {
            $array_double = array_combine($content_double[1], $content_double[2]);
        }

        return array_merge($array_single, $array_double);
    }


    /**
     * Get Shortcode Content
     *
     * @param string $shortcode
     *
     * @return string
     */
    static public function get_shortcode_content($shortcode)
    {
        $shortcode_slug = self::get_shortcode_slug($shortcode);
        preg_match('/\]\s*(.*?)\[\/' . $shortcode_slug . '\]\s*$/s', $shortcode, $content);

        return $content[1];
    }


    /**
     * Overwrite Styleguide Page
     *
     * @return string
     */
    public function overwrite_styleguide_page()
    {
        add_filter('the_content', function($content)
        {
            if (is_page(array('styleguide', 'style-guide')) && file_exists($this->shortcode_json)) {
                $content .= $this->build_styleguide();
            }

            return $content;
        });
    }


    /**
     * Display JSON Shortcodes
     *
     * @return string
     */
    public function build_styleguide()
    {
        $shortcode_file = file_get_contents($this->shortcode_json);
        $shortcode_json = json_decode($shortcode_file, TRUE);
        $content        = '';

        foreach ($shortcode_json as $shortcode) {
             // Children Shortcodes
            if (!empty($shortcode['menu'])) {
                $waypoint = sanitize_title($shortcode['text']);
                $content .= '<h2 class="waypoint" data-destination="wp-' . $waypoint . '" id="' . $waypoint . '">' . $shortcode['text'] . '<a href="#' . $waypoint . '"><sup>&#9875;</sup></a></h2>';

                foreach ($shortcode['menu'] as $shortcode_child) {
                    $content .= self::build_styleguide_block($shortcode_child);
                }
            } else {
                // Parent Shortcodes
                $content .= self::build_styleguide_block($shortcode);
            }
            $content .= '<hr />';
        }

        return $content;
    }


    /**
     * Content Shortcodes to Array
     *
     * @param string $shortcode
     *
     * @return string
     */
    static public function build_styleguide_block($shortcode)
    {
        if ($shortcode['public'] && $shortcode['styleguide'] != 'hidden') {
            // Title
            $waypoint = sanitize_title($shortcode['text']);
            $content  = '<h3 id="' . $waypoint . '">' . $shortcode['text'] . '<a href="#' . $waypoint . '"><sup>&#9875;</sup></a></h3>';

            if (!empty($shortcode['shortcode'])) {
                // Shortcode
                $content .= '<div class="shortcode-' . sanitize_title($shortcode['text']) . '">';
                $content .= self::get_styleguide_display_code($shortcode, 'shortcode');
                $content .= '</div>';
                $content .= '<h5>Shortcode</h5>';
                $content .= '<pre class="shortcode">' . self::setup_pre_text($shortcode['shortcode']) . '</pre>';

                // Attributes
                $attributes = self::get_shortcode_attributes_array($shortcode['shortcode']);
                if (!empty($attributes)) {
                    $content .= '<h5>Attributes</h5>';
                    $content .= '<ul class="attributes">';
                    foreach ($attributes as $attribute => $value)
                    {
                      $value    = (!empty($value) ? ' <span>' . $value . '</span>' : '');
                      $content .= '<li>' . $attribute . $value . '</li>';
                    }
                    $content .= '</ul>';
                }
            } elseif (!empty($shortcode['html'])) {
                // HTML
                $content .= '<div class="html-' . sanitize_title($shortcode['text']) . '">';
                $content .= self::get_styleguide_display_code($shortcode, 'html');
                $content .= '</div>';
                $content .= '<h5>HTML</h5>';
                $content .= '<pre class="html">' . self::setup_pre_text($shortcode['html']) . '</pre>';
            }

            // Instructions
            if (!empty($shortcode['instructions'])) {
                $content .= '<h5>Instructions</h5>';
                $content .= $shortcode['instructions'];
            }

            // File Location
            $content .= '<h5>File Template</h5>';
            $content .= '<small class="file">' . $shortcode['file'] . '</small>';

            return $content;
        }
    }


    /**
     * Setup Pre Text
     *
     * @param string $content
     *
     * @return string
     */
    static public function setup_pre_text($content)
    {
        $search_replace = [
            '][/'          => "&#93;\n&#91;&#47;",
            '[/'           => "\n&#91;&#47;",
            '['            => "&#91;",
            ']'            => "&#93;\n",
            '</ul>'        => "\n</ul>",
            '<li'          => "\n<li",
            '<div'         => "\n<div",
            '</div></div>' => "</div>\n</div>",
            '</table>'     => "\n</table>",
            '<thead'       => "<thead",
            '</thead>'     => "\n</thead>",
            '<tfoot'       => "\n<tfoot",
            '</tfoot'      => "\n</tfoot>",
            '<tbody'       => "\n<tbody",
            '</tbody>'     => "\n</tbody>",
            '<caption'     => "\n<caption",
            '<tr'          => "\n<tr",
            '</tr'         => "\n</tr",
            '<td'          => "\n<td",
            '<th'          => "\n<th",
        ];

        $pre_text = str_replace(array_keys($search_replace), array_values($search_replace), $content);
        $pre_text = htmlentities($pre_text, ENT_NOQUOTES, 'UTF-8', false);
        $pre_text = str_replace(array('<', '>'), array('&lt;', '&gt;'), $pre_text);

        return $pre_text;
    }


    /**
     * Get Styleguide Display Code
     *
     * @param string $content
     *
     * @return string
     */
    static public function get_styleguide_display_code($shortcode, $type)
    {
        $content  = self::setup_fillers((!empty($shortcode['styleguide']) ? $shortcode['styleguide'] : $shortcode[ $type ]));
        $content .= self::setup_fillers((!empty($shortcode['styleguide_2']) ? $shortcode['styleguide_2'] : ''));
        $content .= self::setup_fillers((!empty($shortcode['styleguide_3']) ? $shortcode['styleguide_3'] : ''));
        $content .= self::setup_fillers((!empty($shortcode['styleguide_4']) ? $shortcode['styleguide_4'] : ''));
        $content .= self::setup_fillers((!empty($shortcode['styleguide_5']) ? $shortcode['styleguide_5'] : ''));

        return $content;
    }


    /**
     * Setup Fillers for Content
     *
     * @param string $content
     *
     * @return string
     */
    static public function setup_fillers($content)
    {
        preg_match_all('/copy\-(\d*)|image\-(\d*x\d*)|ul\-(\d*\-?\d*)|ol\-(\d*\-?\d*)/', $content, $filler);

        if (!empty($filler[0])) {
            $fillers = array_flip(array_unique($filler[0]));
            
            foreach ($fillers as $filler => $value) {
                $fillers[ $filler ] = self::get_filler($filler);
            }

            arsort($fillers);
            $content = str_replace(array_keys($fillers), array_values($fillers), $content);
        }

        return $content;
    }


    /**
     * Get Filler
     *
     * @param string $content
     *
     * @return string
     */
    static public function get_filler($args)
    {
        $filler           = explode('-', $args, 2);
        $filler_type_func = 'get_filler_' . $filler[0];

        return self::$filler_type_func($filler[1]);
    }


    /**
     * Get Filler Copy
     *
     * @param string $length
     *
     * @return string
     */
    static public function get_filler_copy($length)
    {
        $string = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sapiente, facere, molestias! Consectetur rem, voluptatem. Vero blanditiis exercitationem quo repellendus aut rerum. Blanditiis, nulla officia doloribus non praesentium architecto voluptatem quos porro nobis sed vero exercitationem voluptas corporis, ducimus dolorem temporibus consectetur ipsam consequuntur? Quidem, veniam. Tenetur, aliquam impedit nam, commodi necessitatibus sunt perferendis, nostrum sit quia voluptatum cupiditate temporibus dignissimos atque. Neque repellat suscipit eveniet dolorem error aliquam eius veritatis saepe quia rem minima, beatae laboriosam nesciunt vitae vero. Cumque quos laborum accusantium, magnam molestias optio, animi asperiores est explicabo, quia repellendus quidem iusto! Modi, nesciunt, autem. Ullam adipisci officiis laboriosam nisi pariatur nulla eum aliquid dignissimos odio eveniet dolorum, totam quam nostrum, blanditiis eos id esse modi molestiae natus provident accusamus explicabo accusantium. Nobis dignissimos ipsum, asperiores sapiente nihil aliquid! Quasi alias incidunt, cum placeat, ipsam quaerat dignissimos aperiam ullam id laborum numquam enim iusto praesentium quod distinctio deleniti porro explicabo. Dolorum sapiente tempore iste atque debitis quisquam repudiandae similique ipsam, vitae porro quaerat officia, quidem eius. Cum odio inventore itaque consectetur dolor tempore. Unde ea maiores quo ratione quia ipsum non incidunt dignissimos nostrum tempore optio nam eum adipisci suscipit quis voluptatum, tenetur error obcaecati magni rerum, ipsam consequatur, molestiae aliquid. Cum fugiat est facilis assumenda, illum veniam enim placeat tempora a quae delectus soluta neque saepe aliquam omnis libero quaerat reiciendis reprehenderit dolorum natus? Iusto corporis recusandae nostrum deleniti neque impedit doloribus accusantium similique qui provident eaque unde possimus tenetur sit explicabo rem sed consequuntur ea est velit, eos porro quisquam minus molestias. Aperiam, deserunt, pariatur! Laudantium est quos laboriosam voluptates rem culpa assumenda omnis officia enim deserunt praesentium dolor alias atque eveniet officiis deleniti animi quibusdam, amet illo tenetur! Fugit dolorem maxime voluptate veritatis placeat facere deleniti fugiat debitis sed, labore, ad laboriosam officia laborum aut.';

        if (empty($length)) {
            $length = str_word_count($string);
        }

        return wp_trim_words($string, $length, '');
    }


    /**
     * Get Filler Image
     *
     * @param string $args
     *
     * @return string
     */
    static public function get_filler_image($args)
    {
        $image = 'http://placehold.it/';

        if (empty($args)) {
            $args = '1200x360';  
        }

        return $image . $args;
    }


    /**
     * Get Filler UL
     *
     * @param string $args
     *
     * @return string
     */
    static public function get_filler_ul($args)
    {
        $args = explode('-', $args);
        $copy = self::get_filler_copy((!empty($args[1]) ? $args[1] : rand(2, 7)));

        // String
        $string = '<ul>';
        for($i = 1; $i <= $args[0]; $i++) {
            $string .= '<li>' . $copy . '</li>';
        } 
        $string .= '</ul>';

        return $string;
    }


    /**
     * Get Filler OL
     *
     * @param string $args
     *
     * @return string
     */
    static public function get_filler_ol($args)
    {
        $args = explode('-', $args);
        $copy = self::get_filler_copy((!empty($args[1]) ? $args[1] : rand(2, 7)));

        // String
        $string = '<ol>';
        for($i = 1; $i <= $args[0]; $i++) {
            $string .= '<li>' . $copy . '</li>';  
        } 
        $string .= '</ol>';

        return $string;
    }


    /**
     * WPEX Fix Shortcode
     *
     * @return mixed
     */
    public function wpex_fix_shortcodes()
    {
        add_filter('the_content', function($content)
        {
            $array = [
                '<p>[' => '[',
                ']</p>' => ']',
                ']<br />' => ']'
            ];

            $content = strtr($content, $array);
            return $content;
        });
    }
}
