<?php 

/**
 * Returns the current exhibit.
 *
 * @return Exhibit|null
 **/
function exhibit_builder_get_current_exhibit()
{
    return __v()->exhibit;
}

/**
 * Sets the current exhibit.
 *
 * @param Exhibit|null $exhibit
 * @return void
 **/
function exhibit_builder_set_current_exhibit($exhibit=null)
{
    __v()->exhibit = $exhibit;
}

/**
 * Returns whether an exhibit is the current exhibit.
 *
 * @param Exhibit|null $exhibit
 * @return boolean
 **/
function exhibit_builder_is_current_exhibit($exhibit)
{
    $currentExhibit = exhibit_builder_get_current_exhibit();
    return ($exhibit == $currentExhibit || ($exhibit && $currentExhibit && $exhibit->id == $currentExhibit->id));
}

/**
 * Returns a link to the exhibit
 *
 * @param Exhibit $exhibit|null If null, it uses the current exhibit
 * @param string|null $text The text of the link
 * @param array $props
 * @param ExhibitSection|null $section
 * @param ExhibitPage|null $page
 * @return string
 **/
function exhibit_builder_link_to_exhibit($exhibit=null, $text=null, $props=array(), $section=null, $page = null)
{   
    if (!$exhibit) {
        $exhibit = exhibit_builder_get_current_exhibit();
    }
    $uri = exhibit_builder_exhibit_uri($exhibit, $section, $page);
    $text = !empty($text) ? $text : $exhibit->title;
    return '<a href="' . html_escape($uri) .'" '. _tag_attributes($props) . '>' . $text . '</a>';
}

/**
 * Returns a URI to the exhibit
 *
 * @param Exhibit $exhibit|null If null, it uses the current exhibit.
 * @param ExhibitSection|null $section
 * @param ExhibitPage|null $page 
 * @internal This relates to: ExhibitsController::showAction(), ExhibitsController::summaryAction()
 * @return string
 **/
function exhibit_builder_exhibit_uri($exhibit=null, $section=null, $page=null)
{
    if (!$exhibit) {
        $exhibit = exhibit_builder_get_current_exhibit();
    }
    $exhibitSlug = ($exhibit instanceof Exhibit) ? $exhibit->slug : $exhibit;
    $sectionSlug = ($section instanceof ExhibitSection) ? $section->slug : $section;
    $pageSlug = ($page instanceof ExhibitPage) ? $page->slug : $page;

    //If there is no section slug available, we want to build a URL for the summary page
    if (empty($sectionSlug)) {
        $uri = public_uri(array('slug'=>$exhibitSlug), 'exhibitSimple');
    } else {
        $uri = public_uri(array('slug'=>$exhibitSlug, 'section'=>$sectionSlug, 'page'=>$pageSlug), 'exhibitShow');
    }
    return $uri;
}

/**
 * Returns a link to the item within the exhibit.
 * 
 * @param string|null $text
 * @param array $props
 * @param Item|null $item If null, will use the current item.
 * @return string
 **/
function exhibit_builder_link_to_exhibit_item($text = null, $props=array('class' => 'exhibit-item-link'), $item=null)
{   
    if (!$item) {
        $item = get_current_item();
    }
    
    $uri = exhibit_builder_exhibit_item_uri($item);
    $text = (!empty($text) ? $text : strip_formatting(item('Dublin Core', 'Title')));
    return '<a href="' . html_escape($uri) . '" '. _tag_attributes($props) . '>' . $text . '</a>';
}

/**
 * Returns a URI to the exhibit item
 * 
 * @param Item $item
 * @param Exhibit|null $exhibit If null, will use the current exhibit.
 * @param ExhibitSection|null $section If null, will use the current exhibit section
 * @return string
 **/
function exhibit_builder_exhibit_item_uri($item, $exhibit=null, $section=null)
{
    if (!$exhibit) {
        $exhibit = exhibit_builder_get_current_exhibit();
    }
    
    if (!$section) {
        $section = exhibit_builder_get_current_section();
    }
    
    //If the exhibit has a theme associated with it
    if (!empty($exhibit->theme)) {
        return uri(array('slug'=>$exhibit->slug,'section'=>$section->slug,'item_id'=>$item->id), 'exhibitItem');
    } else {
        return uri(array('controller'=>'items','action'=>'show','id'=>$item->id), 'id');
    }   
}

/**
 * Returns an array of exhibits
 * 
 * @param array $params
 * @return array
 **/
function exhibit_builder_get_exhibits($params = array()) 
{
    return get_db()->getTable('Exhibit')->findBy($params);
}

/**
 * Returns an array of recent exhibits
 * 
 * @param int $num The maximum number of exhibits to return
 * @return array
 **/
function exhibit_builder_recent_exhibits($num = 10) 
{
    return exhibit_builder_get_exhibits(array('recent'=>true,'limit'=>$num));
}

/**
 * Returns an Exhibit by id
 * 
 * @param int $exhibitId The id of the exhibit
 * @return Exhibit
 **/
function exhibit_builder_get_exhibit_by_id($exhibitId) 
{
    return get_db()->getTable('Exhibit')->find($exhibitId);
}

/**
 * Displays the exhibit header
 *
 * @return void
 **/
function exhibit_builder_exhibit_head()
{
    $exhibit = exhibit_builder_get_current_exhibit();
    if ($exhibit->theme) {
        common('header',compact('exhibit'), EXHIBIT_THEMES_DIR_NAME.DIRECTORY_SEPARATOR.$exhibit->theme);
    } else {
        head(compact('exhibit'));
    }
}

/**
 * Displays the exhibit footer
 *
 * @return void
 **/
function exhibit_builder_exhibit_foot()
{
    $exhibit = exhibit_builder_get_current_exhibit();
    if ($exhibit->theme) {
        common('footer',compact('exhibit'), EXHIBIT_THEMES_DIR_NAME.DIRECTORY_SEPARATOR.$exhibit->theme);
    } else {
        foot(compact('exhibit'));
    }
}


/**
 * Returns the HTML code of the item drag and drop section of the exhibit form
 *
 * @param Item $item
 * @param int $orderOnForm
 * @param string $label
 * @return string
 **/
function exhibit_builder_exhibit_form_item($item, $orderOnForm=null, $label=null)
{
    $html = '<div class="item-drop">';  

    if ($item and $item->exists()) {
        set_current_item($item);
        $html .= '<div class="item-drag"><div class="item_id">' . html_escape($item->id) . '</div>';
        $html .=  item_has_thumbnail() ? item_square_thumbnail() : '<div class="title">' . item('Dublin Core', 'Title', ', ') . '</div>';
        $html .= '</div>';      
    }
    
    // If this is ordered on the form, make sure the generated form element indicates its order on the form.
    if ($orderOnForm) {
        $html .= __v()->formText('Item['.$orderOnForm.']', $item->id, array('size'=>2));
    } else {
        $html .= '<div class="item_id">' . html_escape($item->id) . '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Returns the HTML code for an item on a layout form
 *
 * @param int $order The order of the item
 * @param string $label
 * @return string
 **/
function exhibit_builder_layout_form_item($order, $label='Enter an Item ID #') 
{   
    return exhibit_builder_exhibit_form_item(exhibit_builder_page_item($order), $order, $label);
}

/**
 * Returns the HTML code for a textarea on a layout form
 *
 * @param int $order The order of the item
 * @param string $label
 * @return string
 **/
function exhibit_builder_layout_form_text($order, $label='Text') 
{
    $html = '<div class="textfield">';
    $html .= textarea(array('name'=>'Text['.$order.']','rows'=>'15','cols'=>'70','class'=>'textinput'), exhibit_builder_page_text($order, false)); 
    $html .= '</div>';
    return $html;
}

/**
 * Returns an array of available exhibit themes
 *
 * @return array
 **/
function exhibit_builder_get_ex_themes() 
{   
    $iter = new VersionedDirectoryIterator(PUBLIC_THEME_DIR);
    $array = $iter->getValid();
    return array_combine($array,$array);
}

/**
 * Returns an array of available exhibit layouts
 *
 * @return array
 **/
function exhibit_builder_get_ex_layouts()
{
    $it = new VersionedDirectoryIterator(EXHIBIT_LAYOUTS_DIR,false);
    $array = $it->getValid();
    
    //strip off file extensions
    foreach ($array as $k=>$file) {
        $array[$k] = array_shift(explode('.',$file));
    }
    
    natsort($array);
    
    //get rid of duplicates
    $array = array_flip(array_flip($array));
    return $array;
}

/**
 * Returns the HTML code for an exhibit layout
 *
 * @param string $layout The layout name
 * @param boolean $input Whether or not to include the input to select the layout
 * @return string
 **/
function exhibit_builder_exhibit_layout($layout, $input=true)
{   
    //Load the thumbnail image
    $imgFile = web_path_to(EXHIBIT_LAYOUTS_DIR_NAME . "/$layout/layout.gif");
    
    $page = exhibit_builder_get_current_page();
    $isSelected = ($page->layout == $layout) and $layout;
    
    $html = '';
    $html .= '<div class="layout' . ($isSelected ? ' current-layout' : '') . '" id="'. html_escape($layout) .'">';
    $html .= '<img src="'. html_escape($imgFile) .'" />';
    if ($input) {
        $html .= '<div class="input">';
        $html .= '<input type="radio" name="layout" value="'. html_escape($layout) .'" ' . ($isSelected ? 'checked="checked"' : '') . '/>';
        $html .= '</div>';
    }
    $html .= '<div class="layout-name">'.html_escape($layout).'</div>'; 
    $html .= '</div>';
    return $html;
}

/**
 * Returns the web path to the exhibit css
 *
 * @param string $fileName The name of the CSS file (does not include file extension)
 * @return string
 **/
function exhibit_builder_exhibit_css($fileName)
{
    if ($exhibit = exhibit_builder_get_current_exhibit()) {
        return css($fileName, EXHIBIT_THEMES_DIR_NAME . DIRECTORY_SEPARATOR . $exhibit->theme);
    }   
}

/**
 * Returns the web path to the layout css
 *
 * @param string $fileName The name of the CSS file (does not include file extension)
 * @return string
 **/
function exhibit_builder_layout_css($fileName='layout')
{
    if ($page = exhibit_builder_get_current_page()) {
        return css($fileName, EXHIBIT_LAYOUTS_DIR_NAME . DIRECTORY_SEPARATOR . $page->layout);
    }
}

/**
 * Displays an exhibit page
 * 
 * @param ExhibitPage $page If null, will use the current exhibit page.
 * @return void
 **/
function exhibit_builder_render_exhibit_page($page = null)
{
    if (!$page) {
        $page = exhibit_builder_get_current_page();
    }
    if ($page->layout) {
     include EXHIBIT_LAYOUTS_DIR.DIRECTORY_SEPARATOR.$page->layout.DIRECTORY_SEPARATOR.'layout.php';
    } else {
     echo "This page does not have a layout.";
    }
}

/**
 * Displays an exhibit layout form
 * 
 * @param string The name of the layout
 * @return void
 **/
function exhibit_builder_render_layout_form($layout)
{   
    include EXHIBIT_LAYOUTS_DIR.DIRECTORY_SEPARATOR.$layout.DIRECTORY_SEPARATOR.'form.php';
}

/**
 * Returns HTML for a set of linked thumbnails for the items on a given exhibit page.  Each 
 * thumbnail is wrapped with a div of class = "exhibit-item"
 *
 * @param int $start The range of items on the page to display as thumbnails
 * @param int $end The end of the range
 * @param array $props Properties to apply to the <img> tag for the thumbnails
 * @param string $thumbnailType The type of thumbnail to display
 * @return string HTML output
 **/
function exhibit_builder_display_exhibit_thumbnail_gallery($start, $end, $props=array(), $thumbnailType="square_thumbnail")
{
    $html = '';
    for ($i=(int)$start; $i <= (int)$end; $i++) { 
        if (exhibit_builder_use_exhibit_page_item($i)) {    
            $html .= "\n" . '<div class="exhibit-item">';
            $thumbnail = item_image($thumbnailType, $props);
            $html .= exhibit_builder_link_to_exhibit_item($thumbnail);
            $html .= '</div>' . "\n";
        }
    }
    return $html;
}

/**
 * Returns the HTML of a random featured exhibit
 *
 * @return string
 **/
function exhibit_builder_display_random_featured_exhibit()
{
    $html = '<div id="featured-exhibit">';
    $featuredExhibit = exhibit_builder_random_featured_exhibit();
    $html .= '<h2>Featured Exhibit</h2>';
    if ($featuredExhibit) {
       $html .= '<h3>' . exhibit_builder_link_to_exhibit($featuredExhibit) . '</h3>';
    } else {
       $html .= '<p>You have no featured exhibits.</p>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Returns a random featured exhibit
 *
 * @return Exhibit
 **/
function exhibit_builder_random_featured_exhibit()
{
    return get_db()->getTable('Exhibit')->findRandomFeatured();
}

/**
 * Returns the html code an exhibit item
 * 
 * @param array $displayFilesOptions
 * @param array $linkProperties
 * @return string
 **/
function exhibit_builder_exhibit_display_item($displayFilesOptions = array(), $linkProperties = array())
{
    $item = get_current_item();

    // Always just display the first file (may change this in future).
    $fileIndex = 0;
    $linkProperties['href'] = exhibit_builder_exhibit_item_uri($item);
    
    // Don't link to the file b/c it overrides the link to the item.
    $displayFilesOptions['linkToFile'] = false;
    
    $html = '<a ' . _tag_attributes($linkProperties) . '>';
    
    // Pass null as the 3rd arg so that it doesn't output the item-file div.
    $fileWrapperClass = null;
    $file = $item->Files[$fileIndex];
    $itemHtml = '';
    if ($file) {
        $itemHtml .= display_file($file, $displayFilesOptions, $fileWrapperClass);
    } else {
        $itemHtml = item('Dublin Core', 'Title');
    }

    $html .= $itemHtml;
    $html .= '</a>';
    return $html;
}

/**
 * Returns the HTML code for an exhibit thumbnail image.
 *
 * @param Item $item
 * @param array $props
 * @param int $index The index of the image for the item
 * @return string
 **/
function exhibit_builder_exhibit_thumbnail($item, $props=array('class'=>'permalink'), $index=0) 
{     
    $uri = exhibit_builder_exhibit_item_uri($item);
    $html = '<a href="' . html_escape($uri) . '">';
    $html .= item_thumbnail($props, $index, $item);
    $html .= '</a>';    
    return $html;
}

/**
 * Returns the HTML code for an exhibit fullsize image.
 *
 * @param Item $item
 * @param array $props
 * @param int $index The index of the image for the item
 * @return string
 **/
function exhibit_builder_exhibit_fullsize($item, $props=array('class'=>'permalink'), $index=0)
{
    $uri = exhibit_builder_exhibit_item_uri($item);
    $html = '<a href="' . html_escape($uri) . '">';
    $html .= item_fullsize($props, $index, $item);
    $html .= '</a>';
    return $html;
}

/**
 * Returns the html code that lists the exhibits
 * 
 * @return string
 **/
function exhibit_builder_show_exhibit_list()
{
    $exhibits = exhibit_builder_get_exhibits();
    if ($exhibits) {
        ob_start();
        foreach( $exhibits as $key=>$exhibit ) { 
?>
    <div class="exhibit <?php if ($key%2==1) { echo ' even'; } else { echo ' odd'; } ?>">
        <h2><?php echo exhibit_builder_link_to_exhibit($exhibit); ?></h2>
        <div class="description"><?php echo $exhibit->description; ?></div>
        <p class="tags"><?php echo tag_string($exhibit, uri('exhibits/browse/tag/')); ?></p>
    </div>
<?php  
        } 
    } else { 
?>
    <p>There are no exhibits.</p>
<?php 
    }
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}

/**
 * Returns true if a given user can edit a given exhibit.
 * 
 * @param Exhibit|null $exhibit If null, will use the current exhibit
 * @param User|null $user If null, will use the current user.
 * @return boolean
 **/
function exhibit_builder_user_can_edit($exhibit=null, $user = null)
{
    if (!$exhibit) {
        $exhibit = exhibit_builder_get_current_exhibit();
    }
    if (!$user) { 
        $user = current_user();
    }
    return (($exhibit->wasAddedBy($user) && get_acl()->checkUserPermission('ExhibitBuilder_Exhibits', 'editSelf')) || 
         get_acl()->checkUserPermission('ExhibitBuilder_Exhibits', 'editAll'));
}

/**
* Gets the current exhibit
*
* @return Exhibit|null
**/
function get_current_exhibit()
{
    return exhibit_builder_get_current_exhibit();
}

/**
 * Sets the current exhibit
 *
 * @see loop_exhibits()
 * @param Exhibit
 * @return void
 **/
function set_current_exhibit(Exhibit $exhibit)
{
   exhibit_builder_set_current_exhibit($exhibit);
}

/**
 * Sets the exhibits for loop
 *
 * @param array $exhibits
 * @return void
 **/
function set_exhibits_for_loop($exhibits)
{
    __v()->exhibits = $exhibits;
}

/**
 * Get the set of exhibits for the current loop.
 * 
 * @return array
 **/
function get_exhibits_for_loop()
{
    return __v()->exhibits;
}

/**
 * Loops through exhibits assigned to the view.
 * 
 * @return mixed The current exhibit
 */
function loop_exhibits()
{
    return loop_records('exhibits', get_exhibits_for_loop(), 'set_current_exhibit');
}

/**
 * Determine whether or not there are any exhibits in the database.
 * 
 * @return boolean
 **/
function has_exhibits()
{
    return (total_exhibits() > 0);    
}

/**
 * Determines whether there are any exhibits for loop.
 * @return boolean
 */
function has_exhibits_for_loop()
{
    $view = __v();
    return ($view->exhibits and count($view->exhibits));
}

/**
  * Returns the total number of exhibits in the database
  *
  * @return integer
  **/
 function total_exhibits() 
 {	
 	return get_db()->getTable('Exhibit')->count();
 }

/**
* Gets a property from an exhibit
*
* @param string $propertyName
* @param array $options
* @param Exhibit $exhibit  The exhibit
* @return mixed The exhibit property value
**/
function exhibit($propertyName, $options=array(), $exhibit=null)
{
    if (!$exhibit) {
        $exhibit = get_current_exhibit();
    }
        
	if (property_exists(get_class($exhibit), $propertyName)) {
	    return $exhibit->$propertyName;
	} else {
	    return null;
	}
}

/**
* Returns a link to an exhibit, exhibit section, or exhibit page.
* @uses exhibit_builder_link_to_exhibit
*
* @param string|null $text The text of the link
* @param array $props
* @param ExhibitSection|null $section
* @param ExhibitPage|null $page
* @param Exhibit $exhibit|null If null, it uses the current exhibit
* @return string
**/
function link_to_exhibit($text = null, $props = array(), $section=null, $page=null, $exhibit=null)
{
	return exhibit_builder_link_to_exhibit($exhibit, $text, $props, $section, $page);
}