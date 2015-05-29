<?php

function trapit_plugin_categories() {
    $category_ids_names = array();
    $category_ids_traps = array();
    $trap_ids_names = array();
    $trap_ids_queues = array();

    load_categories_traps($category_ids_names, $category_ids_traps, $trap_ids_names, $trap_ids_queues);

    $opt_vals = trapit_load_opt_vals();
    $hidden_field_name = 'trapit_submit_hidden';
    $trapit_post_item = 'trapit_post_item';
    $trapit_post_field_names = array('title', 'summary', 'id', 'image_url', 'original');
    if ( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        
        $trapit_post_fields = array();
        foreach ($trapit_post_field_names as $trapit_post_field_name) {
            $field_name = 'trapit_' . $trapit_post_field_name;
            $trapit_post_fields[$field_name] = $_POST[$field_name];
        }
        
        $original_sanitized = htmlspecialchars($trapit_post_fields['trapit_original']);
        $image_url_sanitized = htmlspecialchars($trapit_post_fields['trapit_image_url']);
        $post_content = "<img src='{$image_url_sanitized}' width='320' />\n\n"
                      . htmlspecialchars($trapit_post_fields['trapit_summary'])
                      . "\n\n<a href='{$original_sanitized}'>Source</a>\n\n";

        $post = array(
            'post_content'   => $post_content, // The full text of the post.
            'post_content_filtered' => $post_content,
            'post_name'      => htmlspecialchars(strtolower($trapit_post_fields['trapit_title']), ENT_QUOTES), // The name (slug) for your post
            'post_title'     => $trapit_post_fields['trapit_title'], // The title of your post.
            'post_status'    => 'draft',
            'post_type'      => 'post',
            'post_excerpt'   => $trapit_post_fields['trapit_summary'] // For all your post excerpt needs.
            //'post_category'  => array("category id", "..."), // Default empty.
            //'tags_input'     => array('<tag>', '<tag>', '...'), // Default empty.
            //'tax_input'      => array( '<taxonomy>' => array() ) // For custom taxonomies. Default empty.
        );

        $post_id = wp_insert_post($post);
        
        $forward_url = "post.php?post={$post_id}&action=edit";
        // forward browser
        echo "<script type='text/javascript'>window.location = '{$forward_url}';</script>";
        return;
    }

    echo '<div id="trapit-window">';

    echo '<template id="trapit-template">';
    ?><!-- Google Fonts -->
      <style>@import 'https://fonts.googleapis.com/css?family=Merriweather';</style>
      <style><?php readfile(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'base.css'))); ?></style>
      <style><?php readfile(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'trapit_traps_list.css'))); ?></style>
      <style><?php readfile(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'spinner.css'))); ?></style>
<?php
    
    //echo '<div id="trapit-list-container" class="trapit-list-container" style="overflow-y: scroll; width:300px;">';  // list-container
    /*
    $divs = Div('list', array(
        Div('header', array(
            Div('title',
                Span('title-span', strtoupper($category_name))),
            Div('meta',
                Div('expander-collapser')))),
        Div('item', Div('info', Div('titleWrapper', Span('trap-name', $trap_name)))),
        Div('item', Div('info', Div('titleWrapper', Span('trap-name', $trap_name))))));
    */

    $icon_minus = new Img('icon-minus', null, 'title="Collapse" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTEiIGhlaWdodD0iMyIgdmlld0JveD0iMCAwIDExIDMiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHRpdGxlPlJlY3RhbmdsZSAxMTE8L3RpdGxlPjxwYXRoIGQ9Ik0xMSAwSDB2M2gxMVYweiIgZmlsbD0iI0Q0RDRENCIgZmlsbC1ydWxlPSJldmVub2RkIi8+PC9zdmc+"');
    $icon_plus = new Img('icon-plus', null, 'title="Expand" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTEiIGhlaWdodD0iMTEiIHZpZXdCb3g9IjAgMCAxMSAxMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48dGl0bGU+cGx1czwvdGl0bGU+PHBhdGggZD0iTTcgNGg0djNIN3Y0SDRWN0gwVjRoNFYwaDN2NHoiIGZpbGw9IiM5QjlCOUIiIGZpbGwtcnVsZT0iZXZlbm9kZCIvPjwvc3ZnPg=="');
    
    $section_els = array();
    foreach ($category_ids_names as $category_id => $category_name) {
        $category_handler = "onclick='TRAPIT.trap_category_click_handler(this, \"{$category_id}\");'";
        $section_els[] = new Div('header trapit-category-header', array(
            new Div('title',
                    new Span('title-span', $category_name)),
            new Div('meta',
                    new Div('expander-collapser', array($icon_plus,
                                                        $icon_minus
                    )))),
        $category_handler);
        
        // lookup traps by category_id from category_ids_traps
        $trap_ids = $category_ids_traps[$category_id];

        // sort traps in alphabetical order per category
        $temp_ids = array_flip($trap_ids);
        $temp_ids_names = array_intersect_key($trap_ids_names, $temp_ids);
        asort($temp_ids_names, SORT_STRING | SORT_FLAG_CASE);

        foreach ($temp_ids_names as $trap_id => $trap_name) {
            $other = "onclick='TRAPIT.trap_name_click_handler(\"{$trap_id}\", this);'";
            $section_els[] = new Div('item primary trapit-category-trap', new Div('info', new Div('titleWrapper', new Span('trap-name', $trap_name))), $other);
        }
    }
    $icon_all = new Img('icon-all icon-all-traps', null, 'src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMTkiIHZpZXdCb3g9IjAgMCAyMCAxOSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48dGl0bGU+UGF0aCArIFBhZ2UgMzwvdGl0bGU+PGcgZmlsbD0iIzAwNUI1NSIgZmlsbC1ydWxlPSJldmVub2RkIj48cGF0aCBkPSJNOCAxNkg0VjNoMTJ2M2gtNlY1SDZ2NWgydjFINnYxaDJ2MUg2djFoMnYyem0zLTEwaDNWNWgtM3YxeiIgaWQ9IlBhdGgiLz48cGF0aCBkPSJNOCAxOWgxMlY2SDh2MTN6bTItMmg4di0xaC04djF6bTAtMmg4di0xaC04djF6bTUtNGgzdi0xaC0zdjF6bTAgMmgzdi0xaC0zdjF6bTAtNGgzVjhoLTN2MXptLTUgNGg0VjhoLTR2NXoiLz48cGF0aCBkPSJNNCAxM0gwVjBoMTJ2M0g2VjJIMnY1aDJ2MUgydjFoMnYxSDJ2MWgydjJ6TTcgM2gzVjJIN3YxeiIvPjwvZz48L3N2Zz4="');
    $all_onclick = 'onclick="TRAPIT.all_traps_click_handler();"';
    $list = new Div('list', $section_els);
    $app = new Div('app', array(null, //new Nav('navbar navbar-default navbar-fixed-top'),
                                new Div('wrapper',
                                        array(new Div('sidebar-wrapper',
                                                      new Div('sidebar traps',
                                                              array(new Div('all-traps', array($icon_all,
                                                                                               new Span(null, 'All')),
                                                                            $all_onclick),
                                                                    new Div('list-container', $list, 'id="trapit-list-container"')
                                                              ))),
                                              //new Div('notification'),
                                              new Div(null),
                                              // right-hand-side elements
                                              new Div('content traps',
                                                      array(new Div('queue',
                                                                    array(//new Div('filter row'),
                                                                        new Div(null),
                                                                        new Div('body masonry trapit-body-masonry')))))))));
    
    //echo '</div>'; // trapit-list-container
    echo (string) $app;
    echo '</template>';
    echo '</div>'; // trapit-window
}
        

class Element {
    public $description = null;
    public $contents = null;
    protected $eletype = null;
    
    function __construct($description, $contents=null, $other=null) {
        $this->description = $description;
        $this->contents = $contents;
        $this->other = $other;
    }

    function __toString() {
        $class_prop = is_null($this->description) ? '' : " class='{$this->description}'";
        $other = is_null($this->other) ? '' : ' ' . $this->other;
        $accumulator = "<{$this->eletype}{$class_prop}{$other}>";
        if (is_array($this->contents)) {
            foreach($this->contents as $content) {
                $accumulator .= $content;
            }
        } else {
            $accumulator .= $this->contents;
        }
        $accumulator .= "</{$this->eletype}>";
        return $accumulator;
    }
}

class Div extends Element {
    protected $eletype = 'div';
}

class Span extends Element {
    protected $eletype = 'span';
}

class I extends Element {
    protected $eletype = 'i';
}

class Nav extends Element {
    protected $eletype = 'nav';
}

class Template extends Element {
    protected $eletype = 'template';
}

class Img extends Element {
    protected $eletype = 'img';
}
