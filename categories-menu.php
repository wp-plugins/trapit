<?php

function trapit_plugin_categories() {
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
        $post_content = "<img src='{$image_url_sanitized}' width='500' />\n\n"
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
